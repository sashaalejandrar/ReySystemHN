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

// Verificar que Cloudmersive esté configurado
if (!defined('CLOUDMERSIVE_API_KEY') || empty(CLOUDMERSIVE_API_KEY)) {
    echo json_encode([
        'success' => false,
        'message' => 'Cloudmersive no está configurado. Por favor, configura tu API key en config_ai.php'
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
    
    // Llamar a Cloudmersive OCR API
    $response = llamarCloudmersiveOCR($imagenBinaria);
    
    if (!$response['success']) {
        throw new Exception($response['error'] ?? 'Error al procesar con Cloudmersive OCR');
    }
    
    $textoExtraido = $response['texto'];
    
    // Parsear productos con los 8 patrones (usando la función de Mistral)
    require_once 'procesar_factura_mistral.php';
    $productos = parsearFactura($textoExtraido);
    
    if (empty($productos)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se detectaron productos en la imagen',
            'texto_completo' => $textoExtraido,
            'sugerencias' => [
                'Asegúrate de que la imagen sea clara y legible',
                'Verifica que la factura contenga códigos de barras de 13 dígitos',
                'Intenta con mejor iluminación o una foto más nítida'
            ]
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
        $marca = '';
        $descripcion = '';
        
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
            'marca' => $marca,
            'descripcion' => $descripcion,
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
        'message' => count($productosExtraidos) . ' productos detectados con Cloudmersive OCR'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar con Cloudmersive: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]
    ]);
}

/**
 * Llama a Cloudmersive OCR API para extraer texto de la imagen
 */
function llamarCloudmersiveOCR($imagenBinaria) {
    $apiKey = CLOUDMERSIVE_API_KEY;
    $url = CLOUDMERSIVE_API_URL;
    
    // Cloudmersive requiere multipart/form-data con el archivo
    $boundary = '----WebKitFormBoundary' . uniqid();
    
    $body = "--{$boundary}\r\n";
    $body .= "Content-Disposition: form-data; name=\"imageFile\"; filename=\"invoice.jpg\"\r\n";
    $body .= "Content-Type: image/jpeg\r\n\r\n";
    $body .= $imagenBinaria . "\r\n";
    $body .= "--{$boundary}--\r\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary,
        'Apikey: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Error de conexión: ' . $error];
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = 'Error Cloudmersive API: Código ' . $httpCode;
        
        if (isset($errorData['Successful']) && $errorData['Successful'] === false) {
            $errorMsg .= ' - ' . ($errorData['ErrorMessage'] ?? 'Error desconocido');
        }
        
        return ['success' => false, 'error' => $errorMsg];
    }
    
    $result = json_decode($response, true);
    
    // Cloudmersive devuelve el texto en diferentes formatos
    // Formato 1: { "TextResult": "texto..." }
    // Formato 2: { "MeanConfidenceLevel": 0.95, "TextResult": "texto..." }
    
    if (!isset($result['TextResult'])) {
        return ['success' => false, 'error' => 'Respuesta inválida de Cloudmersive OCR'];
    }
    
    $textoExtraido = $result['TextResult'];
    $confidence = $result['MeanConfidenceLevel'] ?? null;
    
    return [
        'success' => true,
        'texto' => $textoExtraido,
        'confidence' => $confidence
    ];
}
?>
