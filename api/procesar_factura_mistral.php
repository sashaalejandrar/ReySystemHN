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

// Verificar que Mistral esté configurado
if (!defined('MISTRAL_API_KEY') || MISTRAL_API_KEY === 'TU_API_KEY_DE_MISTRAL_AQUI') {
    echo json_encode([
        'success' => false,
        'message' => 'Mistral no está configurado. Por favor, configura tu API key en config_ai.php'
    ]);
    exit;
}

try {
    // Extraer la parte base64 pura
    if (strpos($imagenBase64, 'base64,') !== false) {
        $imagenBase64 = explode('base64,', $imagenBase64)[1];
    }
    
    $imagenBinaria = base64_decode($imagenBase64);
    
    if ($imagenBinaria === false) {
        throw new Exception('Error al decodificar la imagen base64');
    }
    
    // Crear archivo temporal
    $tempFile = tempnam(sys_get_temp_dir(), 'invoice_') . '.jpg';
    file_put_contents($tempFile, $imagenBinaria);
    
    // Llamar a Mistral Vision API
    $response = llamarMistralVisionAPI($tempFile);
    
    // Eliminar archivo temporal
    unlink($tempFile);
    
    if (!$response['success']) {
        throw new Exception($response['error'] ?? 'Error al procesar con Mistral Vision API');
    }
    
    $textoExtraido = $response['texto'];
    
    // Parsear productos con los 7 patrones
    $productos = parsearFactura($textoExtraido);
    
    if (empty($productos)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se detectaron productos en la imagen',
            'texto_completo' => $textoExtraido
        ]);
        exit;
    }
    
    // Verificar existencia en BD
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
        
        $existe = false;
        $stockActual = 0;
        $productoId = null;
        
        if (!empty($codigo)) {
            $stmt = $conexion->prepare("SELECT Id, Stock, Nombre_Producto, Marca, Descripcion FROM stock WHERE Codigo_Producto = ?");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $existe = true;
                $row = $resultado->fetch_assoc();
                $stockActual = $row['Stock'];
                $nombre = $row['Nombre_Producto'];
                $productoId = $row['Id'];
                $marca = $row['Marca'] ?? '';
                $descripcion = $row['Descripcion'] ?? '';
            }
            $stmt->close();
        }
        
        $productosExtraidos[] = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'marca' => $marca ?? '',
            'descripcion' => $descripcion ?? '',
            'existe' => $existe,
            'stockActual' => $stockActual,
            'Id' => $productoId
        ];
    }
    
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'productos' => $productosExtraidos,
        'texto_completo' => $textoExtraido,
        'message' => count($productosExtraidos) . ' productos detectados con Mistral OCR'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar con Mistral: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]
    ]);
}

/**
 * Llama a Mistral Vision API para analizar la imagen
 */
function llamarMistralVisionAPI($imagePath) {
    $apiKey = MISTRAL_API_KEY;
    $url = MISTRAL_API_URL;
    
    // Leer la imagen y convertirla a base64
    $imageData = base64_encode(file_get_contents($imagePath));
    
    $prompt = "Analiza esta factura y extrae los productos en formato JSON.

Para cada producto extrae:
- codigo: código de barras de 13 dígitos
- nombre: descripción del producto
- cantidad: cantidad (número entero)
- precio: precio unitario (número decimal)

Formato de salida:
{
  \"productos\": [
    {\"codigo\": \"1234567890123\", \"nombre\": \"Producto 1\", \"cantidad\": 1, \"precio\": 10.50}
  ]
}

Devuelve SOLO el JSON, sin texto adicional.";
    
    $data = [
        'model' => MISTRAL_VISION_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => 'data:image/jpeg;base64,' . $imageData
                    ]
                ]
            ]
        ],
        'max_tokens' => 4096,
        'temperature' => 0.0
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Error de conexión: ' . $error];
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        return ['success' => false, 'error' => 'Error Mistral API: ' . ($errorData['error']['message'] ?? 'Código ' . $httpCode)];
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['choices'][0]['message']['content'])) {
        return ['success' => false, 'error' => 'Respuesta inválida de Mistral'];
    }
    
    return [
        'success' => true,
        'texto' => $result['choices'][0]['message']['content']
    ];
}

/**
 * Función de parseo con 7 patrones
 */
function parsearFactura($texto) {
    $productos = [];
    
    // Primero intentar extraer JSON directo de la respuesta de Mistral
    $jsonData = extraerJSONDeRespuesta($texto);
    if (!empty($jsonData['productos'])) {
        foreach ($jsonData['productos'] as $prod) {
            if (isset($prod['codigo'], $prod['nombre'], $prod['cantidad'], $prod['precio'])) {
                $productos[] = [
                    'codigo' => $prod['codigo'],
                    'nombre' => $prod['codigo'] . ' ' . trim($prod['nombre']),
                    'cantidad' => intval($prod['cantidad']),
                    'precio' => floatval($prod['precio'])
                ];
            }
        }
        
        if (!empty($productos)) {
            return $productos;
        }
    }
    
    // Si no hay JSON válido, parsear con los 7 patrones
    $lineas = explode("\n", $texto);
    
    foreach ($lineas as $i => $linea) {
        $linea = trim($linea);
        if (empty($linea)) continue;
        
        // PATRÓN 1: FORMATO DE FILA COMPLETA
        // Ejemplo: 7501234567890 Producto ABC 5 L125.50
        if (preg_match('/^(\d{13})\s+(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[1],
                'nombre' => $matches[1] . ' ' . trim($matches[2]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // PATRÓN 2: FORMATO POR COLUMNAS
        // Ejemplo: 
        // 7501234567890
        // Producto ABC
        // 5 L125.50
        if (preg_match('/^(\d{13})$/', $linea) && isset($lineas[$i + 1])) {
            $codigo = $linea;
            $siguienteLinea = trim($lineas[$i + 1]);
            
            // Buscar en las siguientes 3 líneas
            for ($j = $i + 1; $j < min($i + 4, count($lineas)); $j++) {
                $lineaBusqueda = trim($lineas[$j]);
                
                // Patrón: Nombre Cantidad Precio
                if (preg_match('/^(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $lineaBusqueda, $matches)) {
                    $productos[] = [
                        'codigo' => $codigo,
                        'nombre' => $codigo . ' ' . trim($matches[1]),
                        'cantidad' => intval($matches[2]),
                        'precio' => floatval($matches[3])
                    ];
                    break;
                }
                
                // Patrón: Cantidad Precio (nombre en línea anterior)
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
        
        // PATRÓN 3: CÓDIGO EMBEBIDO
        // Ejemplo: Producto ABC (7501234567890) 5 L125.50
        if (preg_match('/^(.+?)\(?\s*(\d{13})\s*\)?\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[2],
                'nombre' => $matches[2] . ' ' . trim($matches[1]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // PATRÓN 4: CON SEPARADORES
        // Ejemplo: Producto ABC | 7501234567890 | 5 | L125.50
        if (preg_match('/^(.+?)\s*[\|\/]\s*(\d{13})\s*[\|\/]\s*(\d+)\s*[\|\/]\s*(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[2],
                'nombre' => $matches[2] . ' ' . trim($matches[1]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // PATRÓN 5: INVERSO
        // Ejemplo: L125.50 5 Producto ABC 7501234567890
        if (preg_match('/^(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)\s+(\d+)\s+(.+?)\s+(\d{13})$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[4],
                'nombre' => $matches[4] . ' ' . trim($matches[3]),
                'cantidad' => intval($matches[2]),
                'precio' => floatval($matches[1])
            ];
            continue;
        }
        
        // PATRÓN 6: CON TABULACIONES (múltiples espacios)
        // Ejemplo: 7501234567890    Producto ABC    5    L125.50
        if (preg_match('/^(\d{13})\s{2,}(.+?)\s{2,}(\d+)\s{2,}(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[1],
                'nombre' => $matches[1] . ' ' . trim($matches[2]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // PATRÓN 7: COMPACTO (sin espacios entre código y nombre)
        // Ejemplo: 7501234567890ProductoABC5 L125.50
        if (preg_match('/^(\d{13})([A-Z\s]+)(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[1],
                'nombre' => $matches[1] . ' ' . trim($matches[2]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
    }
    
    // PATRÓN 8: FORMATO DE TABLA POR COLUMNAS SEPARADAS
    // Este patrón maneja cuando los códigos, descripciones, cantidades y precios
    // están en columnas separadas verticalmente
    if (empty($productos)) {
        $productos = parsearTablaPorColumnas($texto);
    }
    
    return $productos;
}

/**
 * Parsea facturas con formato de tabla por columnas
 * Ejemplo:
 * CÓDIGO           DESCRIPCIÓN      CANT    PRECIO UNIT
 * 7401005123456    Producto 1       2       15.00
 * 7401005123463    Producto 2       1       20.00
 */
function parsearTablaPorColumnas($texto) {
    $productos = [];
    
    // Extraer todos los códigos de barras (13 dígitos)
    preg_match_all('/\b(\d{13})\b/', $texto, $matchesCodigos);
    $codigos = $matchesCodigos[1];
    
    if (empty($codigos)) {
        return [];
    }
    
    // Buscar la sección de descripciones
    $lineas = explode("\n", $texto);
    $descripciones = [];
    $cantidades = [];
    $precios = [];
    
    // Intentar encontrar el patrón de encabezados
    $inicioProductos = -1;
    foreach ($lineas as $idx => $linea) {
        if (preg_match('/DESCRIPCI[OÓ]N|PRODUCTO|DETALLE/i', $linea)) {
            $inicioProductos = $idx + 1;
            break;
        }
    }
    
    // Si encontramos códigos, intentar extraer las otras columnas
    if (!empty($codigos)) {
        // Método 1: Buscar descripciones entre DESCRIPCIÓN y CANT
        if (preg_match('/DESCRIPCI[OÓ]N\s+(.+?)\s+CANT/is', $texto, $matchDesc)) {
            $textoDescripciones = $matchDesc[1];
            $lineasDesc = explode("\n", $textoDescripciones);
            foreach ($lineasDesc as $lineaDesc) {
                $lineaDesc = trim($lineaDesc);
                // Filtrar líneas que parecen descripciones (no son solo números)
                if (!empty($lineaDesc) && !preg_match('/^\d+$/', $lineaDesc) && !preg_match('/^\d{13}$/', $lineaDesc)) {
                    $descripciones[] = $lineaDesc;
                }
            }
        }
        
        // Método 2: Extraer cantidades (números pequeños, típicamente 1-999)
        if (preg_match('/CANT\s+(.+?)\s+PRECIO/is', $texto, $matchCant)) {
            $textoCantidades = $matchCant[1];
            preg_match_all('/\b(\d{1,3})\b/', $textoCantidades, $matchesCant);
            $cantidades = array_map('intval', $matchesCant[1]);
        }
        
        // Método 3: Extraer precios (números con o sin decimales)
        if (preg_match('/PRECIO\s+UNIT\s+(.+?)(?:TOTAL|$)/is', $texto, $matchPrecio)) {
            $textoPrecios = $matchPrecio[1];
            preg_match_all('/\b(\d+\.?\d*)\b/', $textoPrecios, $matchesPrecios);
            $precios = array_map('floatval', $matchesPrecios[1]);
        } else {
            // Buscar precios en todo el texto después de los códigos
            preg_match_all('/\b(\d+\.\d{2})\b/', $texto, $matchesPrecios);
            $precios = array_map('floatval', $matchesPrecios[1]);
        }
        
        // Combinar los arrays (usar el mínimo de elementos encontrados)
        $numProductos = min(
            count($codigos),
            max(count($descripciones), 1),
            max(count($cantidades), 1),
            max(count($precios), 1)
        );
        
        for ($i = 0; $i < $numProductos; $i++) {
            $codigo = $codigos[$i] ?? '';
            $descripcion = $descripciones[$i] ?? 'Producto ' . ($i + 1);
            $cantidad = $cantidades[$i] ?? 1;
            $precio = $precios[$i] ?? 0;
            
            // Validar que tengamos datos mínimos
            if (!empty($codigo) && $precio > 0) {
                $productos[] = [
                    'codigo' => $codigo,
                    'nombre' => $codigo . ' ' . trim($descripcion),
                    'cantidad' => $cantidad,
                    'precio' => $precio
                ];
            }
        }
    }
    
    return $productos;
}

/**
 * Extrae JSON de la respuesta de la IA
 */
function extraerJSONDeRespuesta($texto) {
    // Intentar encontrar JSON en la respuesta
    if (preg_match('/\{[\s\S]*"productos"[\s\S]*\}/', $texto, $matches)) {
        $json = json_decode($matches[0], true);
        if ($json !== null) {
            return $json;
        }
    }
    
    // Intentar parsear directamente
    $json = json_decode($texto, true);
    if ($json !== null && isset($json['productos'])) {
        return $json;
    }
    
    return ['productos' => []];
}
?>
