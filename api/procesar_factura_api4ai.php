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

try {
    // Extraer la parte base64 pura
    if (strpos($imagenBase64, 'base64,') !== false) {
        $imagenBase64 = explode('base64,', $imagenBase64)[1];
    }
    
    $imagenBinaria = base64_decode($imagenBase64);
    
    if ($imagenBinaria === false) {
        throw new Exception('Error al decodificar la imagen base64');
    }
    
    // API4AI usa multipart/form-data
    $boundary = '----WebKitFormBoundary' . uniqid();
    
    $body = '';
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"image\"; filename=\"invoice.jpg\"\r\n";
    $body .= "Content-Type: image/jpeg\r\n\r\n";
    $body .= $imagenBinaria . "\r\n";
    $body .= "--{$boundary}--\r\n";
    
    $ch = curl_init(API4AI_URL);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $body,
        CURLOPT_HTTPHEADER => [
            'Content-Type: multipart/form-data; boundary=' . $boundary
        ],
        CURLOPT_TIMEOUT => 60
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('Error de cURL: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Error de API4AI (HTTP ' . $httpCode . '): ' . $response);
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['results'])) {
        throw new Exception('Respuesta inválida de API4AI: ' . $response);
    }
    
    // Extraer texto de la respuesta de API4AI
    $textoCompleto = '';
    
    if (isset($data['results'][0]['entities'])) {
        foreach ($data['results'][0]['entities'] as $entity) {
            if (isset($entity['text'])) {
                $textoCompleto .= $entity['text'] . "\n";
            }
        }
    }
    
    if (empty($textoCompleto)) {
        throw new Exception('No se pudo extraer texto de la imagen. Intenta con mejor iluminación o calidad.');
    }
    
    // Parsear productos
    $productos = parsearFactura($textoCompleto);
    
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
        
        if (!empty($codigo)) {
            $stmt = $conexion->prepare("SELECT Stock, Nombre_Producto FROM productos WHERE Codigo_Producto = ?");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $existe = true;
                $row = $resultado->fetch_assoc();
                $stockActual = $row['Stock'];
                $nombre = $row['Nombre_Producto'];
            }
            $stmt->close();
        }
        
        $productosExtraidos[] = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'marca' => '',
            'descripcion' => '',
            'existe' => $existe,
            'stockActual' => $stockActual
        ];
    }
    
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'productos' => $productosExtraidos,
        'texto_completo' => $textoCompleto,
        'message' => count($productosExtraidos) . ' productos detectados con API4AI'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar con API4AI: ' . $e->getMessage()
    ]);
}

function parsearFactura($texto) {
    $productos = [];
    $lineas = explode("\n", $texto);
    
    foreach ($lineas as $i => $linea) {
        $linea = trim($linea);
        if (empty($linea)) continue;
        
        // Patrón 1: Código (13 dígitos) + descripción + cantidad + precio
        if (preg_match('/^(\d{13})\s+(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[1],
                'nombre' => $matches[1] . ' ' . trim($matches[2]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // Patrón 2: Código en línea separada
        if (preg_match('/^(\d{13})$/', $linea) && isset($lineas[$i + 1])) {
            $codigo = $linea;
            $siguienteLinea = trim($lineas[$i + 1]);
            
            for ($j = $i + 1; $j < min($i + 4, count($lineas)); $j++) {
                $lineaBusqueda = trim($lineas[$j]);
                
                if (preg_match('/^(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $lineaBusqueda, $matches)) {
                    $productos[] = [
                        'codigo' => $codigo,
                        'nombre' => $codigo . ' ' . trim($matches[1]),
                        'cantidad' => intval($matches[2]),
                        'precio' => floatval($matches[3])
                    ];
                    break;
                }
            }
        }
    }
    
    return $productos;
}
?>
