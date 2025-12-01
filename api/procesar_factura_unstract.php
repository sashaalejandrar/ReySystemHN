<?php
header('Content-Type: application/json');
require_once '../config_ai.php';

// Recibir la imagen en base64
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['imagen'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibió ninguna imagen'
    ]);
    exit;
}

$imagenBase64 = $input['imagen'];

// Verificar que Unstract esté configurado
if (!USE_UNSTRACT || empty(UNSTRACT_API_KEY)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unstract LLM Whisperer no está configurado. Por favor, configura tu API key en config_ai.php'
    ]);
    exit;
}

try {
    // Extraer la parte base64 pura (sin el prefijo data:image/...)
    $mimeType = 'image/jpeg'; // Por defecto
    $extension = '.jpg';
    
    if (strpos($imagenBase64, 'data:image/') !== false) {
        // Extraer el tipo MIME de la imagen
        preg_match('/data:image\/([a-zA-Z0-9]+);base64,/', $imagenBase64, $matches);
        if (isset($matches[1])) {
            $imageType = strtolower($matches[1]);
            if ($imageType === 'png') {
                $mimeType = 'image/png';
                $extension = '.png';
            } elseif ($imageType === 'jpeg' || $imageType === 'jpg') {
                $mimeType = 'image/jpeg';
                $extension = '.jpg';
            } elseif ($imageType === 'webp') {
                $mimeType = 'image/webp';
                $extension = '.webp';
            }
        }
        $imagenBase64 = explode('base64,', $imagenBase64)[1];
    }
    
    $imagenBinaria = base64_decode($imagenBase64);
    
    if ($imagenBinaria === false) {
        throw new Exception('Error al decodificar la imagen base64');
    }
    
    // PASO 1: Enviar imagen a LLM Whisperer para procesamiento
    
    // Crear boundary para multipart/form-data
    $boundary = '----WebKitFormBoundary' . uniqid();
    
    // Construir el cuerpo de la petición manualmente
    $body = '';
    
    // Añadir parámetros
    $params = [
        'processing_mode' => UNSTRACT_PROCESSING_MODE,
        'output_mode' => 'line-printer',
        'page_seperator' => '<<<',
        'force_text_processing' => 'true',
        'pages_to_extract' => '',
        'timeout' => '200',
        'store_metadata_for_highlighting' => 'false'
    ];
    
    foreach ($params as $key => $value) {
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"{$key}\"\r\n\r\n";
        $body .= "{$value}\r\n";
    }
    
    // Añadir el archivo
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"invoice{$extension}\"\r\n";
    $body .= "Content-Type: {$mimeType}\r\n\r\n";
    $body .= $imagenBinaria . "\r\n";
    $body .= "--{$boundary}--\r\n";
    
    $ch = curl_init(UNSTRACT_API_URL . '/whisper');
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'unstract-key: ' . UNSTRACT_API_KEY,
            'Content-Type: multipart/form-data; boundary=' . $boundary,
            'Content-Length: ' . strlen($body)
        ],
        CURLOPT_TIMEOUT => 120,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('Error de cURL: ' . $error);
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 202) {
        throw new Exception('Error de API Unstract (HTTP ' . $httpCode . '): ' . $response);
    }
    
    $data = json_decode($response, true);
    
    if (!$data) {
        throw new Exception('Respuesta inválida de Unstract: ' . $response);
    }
    
    // Si la respuesta es 202, necesitamos hacer polling
    if ($httpCode === 202 && isset($data['whisper_hash'])) {
        $whisperHash = $data['whisper_hash'];
        
        // PASO 2: Hacer polling para obtener el resultado
        $maxAttempts = 30; // 30 intentos (60 segundos máximo)
        $attempt = 0;
        $textoExtraido = null;
        
        while ($attempt < $maxAttempts) {
            sleep(2); // Esperar 2 segundos entre intentos
            
            $ch = curl_init(UNSTRACT_API_URL . '/whisper-retrieve?whisper_hash=' . urlencode($whisperHash));
            
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'unstract-key: ' . UNSTRACT_API_KEY
                ]
            ]);
            
            $retrieveResponse = curl_exec($ch);
            $retrieveHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($retrieveHttpCode === 200) {
                $retrieveData = json_decode($retrieveResponse, true);
                
                if (isset($retrieveData['status']) && $retrieveData['status'] === 'processed') {
                    $textoExtraido = $retrieveData['extracted_text'] ?? null;
                    break;
                } elseif (isset($retrieveData['status']) && $retrieveData['status'] === 'failed') {
                    throw new Exception('El procesamiento falló en Unstract: ' . ($retrieveData['status_message'] ?? 'Error desconocido'));
                }
            }
            
            $attempt++;
        }
        
        if (!$textoExtraido) {
            throw new Exception('Timeout esperando el resultado de Unstract');
        }
        
    } elseif (isset($data['extracted_text'])) {
        // Respuesta inmediata (v2)
        $textoExtraido = $data['extracted_text'];
    } elseif (isset($data['text'])) {
        // Respuesta inmediata (v1 fallback)
        $textoExtraido = $data['text'];
    } else {
        throw new Exception('No se pudo extraer texto de la respuesta. Respuesta: ' . json_encode($data));
    }
    
    // PASO 3: Parsear el texto extraído para obtener productos
    $productos = parsearFactura($textoExtraido);
    
    // PASO 4: Verificar existencia de productos en la base de datos
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    $productosExtraidos = [];
    
    foreach ($productos as $producto) {
        $codigo = $producto['codigo'];
        $nombre = $producto['nombre'];
        $cantidad = $producto['cantidad'];
        $precio = $producto['precio'];
        
        // Buscar si existe el producto
        $existe = false;
        $stockActual = 0;
        
        if (!empty($codigo)) {
            $stmt = $conexion->prepare("SELECT Stock, Nombre_Producto FROM productos WHERE Codigo_Producto = ?");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $existe = true;
                $row = $resultado->fetch_assoc();
                $stockActual = $row['Stock'];
                // Usar el nombre de la BD si existe
                $nombre = $row['Nombre_Producto'];
            }
            $stmt->close();
        }
        
        $productosExtraidos[] = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'marca' => $producto['marca'] ?? '',
            'descripcion' => '',
            'existe' => $existe,
            'stockActual' => $stockActual
        ];
    }
    
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'productos' => $productosExtraidos,
        'texto_completo' => $textoExtraido,
        'message' => count($productosExtraidos) . ' productos detectados con Unstract LLM Whisperer',
        'debug' => [
            'metodo' => 'Unstract LLM Whisperer',
            'productos_encontrados' => count($productosExtraidos)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar con Unstract: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}

/**
 * Parsear texto de factura extraído por Unstract
 */
function parsearFactura($texto) {
    $productos = [];
    $lineas = explode("\n", $texto);
    
    foreach ($lineas as $i => $linea) {
        $linea = trim($linea);
        if (empty($linea)) continue;
        
        // Patrón 1: Código de barras (13 dígitos) seguido de descripción, cantidad y precio
        // Ejemplo: 7501234567890 COCA COLA 2L 2 45.50
        if (preg_match('/^(\d{13})\s+(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[1],
                'nombre' => $matches[1] . ' ' . trim($matches[2]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // Patrón 2: Código en una línea, descripción en la siguiente
        if (preg_match('/^(\d{13})$/', $linea) && isset($lineas[$i + 1])) {
            $codigo = $linea;
            $siguienteLinea = trim($lineas[$i + 1]);
            
            // Buscar cantidad y precio en las siguientes 2 líneas
            for ($j = $i + 1; $j < min($i + 4, count($lineas)); $j++) {
                $lineaBusqueda = trim($lineas[$j]);
                
                // Patrón: Descripción Cantidad Precio
                if (preg_match('/^(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $lineaBusqueda, $matches)) {
                    $productos[] = [
                        'codigo' => $codigo,
                        'nombre' => $codigo . ' ' . trim($matches[1]),
                        'cantidad' => intval($matches[2]),
                        'precio' => floatval($matches[3])
                    ];
                    break;
                }
                
                // Patrón alternativo: Solo cantidad y precio
                if (preg_match('/^(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $lineaBusqueda, $matches)) {
                    $productos[] = [
                        'codigo' => $codigo,
                        'nombre' => $codigo . ' ' . $siguienteLinea,
                        'cantidad' => intval($matches[1]),
                        'precio' => floatval($matches[2])
                    ];
                    break;
                }
            }
        }
        
        // Patrón 3: Descripción con código embebido
        // Ejemplo: PRODUCTO ABC (7501234567890) 2 45.50
        if (preg_match('/^(.+?)\(?(\d{13})\)?\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[2],
                'nombre' => $matches[2] . ' ' . trim($matches[1]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // Patrón 4: Formato de tabla con separadores
        // Ejemplo: PRODUCTO | 7501234567890 | 2 | 45.50
        if (preg_match('/^(.+?)\s*[\|\/]\s*(\d{13})\s*[\|\/]\s*(\d+)\s*[\|\/]\s*(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[2],
                'nombre' => $matches[2] . ' ' . trim($matches[1]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
    }
    
    return $productos;
}
?>
