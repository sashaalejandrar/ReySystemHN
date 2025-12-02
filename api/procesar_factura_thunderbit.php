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

// Verificar que Thunderbit esté configurado
if (!USE_THUNDERBIT || THUNDERBIT_API_KEY === 'TU_API_KEY_DE_THUNDERBIT_AQUI') {
    echo json_encode([
        'success' => false,
        'message' => 'Thunderbit no está configurado. Por favor, configura tu API key en config_ai.php'
    ]);
    exit;
}

try {
    // Preparar la solicitud a Thunderbit
    $ch = curl_init(THUNDERBIT_API_URL);
    
    $payload = [
        'image' => $imagenBase64,
        'workflow_id' => THUNDERBIT_WORKFLOW_ID,
        'extract_fields' => [
            'productos' => [
                'type' => 'array',
                'items' => [
                    'codigo' => 'string',
                    'nombre' => 'string',
                    'cantidad' => 'number',
                    'precio' => 'number',
                    'marca' => 'string',
                    'descripcion' => 'string'
                ]
            ],
            'proveedor' => 'string',
            'fecha' => 'string',
            'numero_factura' => 'string',
            'total' => 'number'
        ]
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . THUNDERBIT_API_KEY
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
        throw new Exception('Error de API Thunderbit (HTTP ' . $httpCode . '): ' . $response);
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['productos'])) {
        throw new Exception('Respuesta inválida de Thunderbit');
    }
    
    // Procesar productos extraídos
    $productosExtraidos = [];
    
    foreach ($data['productos'] as $producto) {
        // Verificar si el producto ya existe en la base de datos
        $conexion = new mysqli("localhost", "root", "", "tiendasrey");
        
        if ($conexion->connect_error) {
            throw new Exception("Error de conexión a la base de datos");
        }
        
        $codigo = $producto['codigo'] ?? '';
        $nombre = $producto['nombre'] ?? '';
        $cantidad = floatval($producto['cantidad'] ?? 1);
        $precio = floatval($producto['precio'] ?? 0);
        $marca = $producto['marca'] ?? '';
        $descripcion = $producto['descripcion'] ?? '';
        
        // Buscar si existe el producto
        $existe = false;
        $stockActual = 0;
        
        if (!empty($codigo)) {
            $stmt = $conexion->prepare("SELECT Stock FROM productos WHERE Codigo_Producto = ?");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $existe = true;
                $row = $resultado->fetch_assoc();
                $stockActual = $row['Stock'];
            }
            $stmt->close();
        }
        
        $conexion->close();
        
        $productosExtraidos[] = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'marca' => $marca,
            'descripcion' => $descripcion,
            'existe' => $existe,
            'stockActual' => $stockActual
        ];
    }
    
    echo json_encode([
        'success' => true,
        'productos' => $productosExtraidos,
        'metadata' => [
            'proveedor' => $data['proveedor'] ?? '',
            'fecha' => $data['fecha'] ?? '',
            'numero_factura' => $data['numero_factura'] ?? '',
            'total' => $data['total'] ?? 0
        ],
        'message' => count($productosExtraidos) . ' productos detectados con Thunderbit'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar con Thunderbit: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
