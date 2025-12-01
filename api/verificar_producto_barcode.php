<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$codigo_barras = trim($_POST['codigo_barras'] ?? $_GET['codigo_barras'] ?? '');

if (empty($codigo_barras)) {
    echo json_encode(['success' => false, 'message' => 'Código de barras requerido']);
    exit;
}

try {
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8mb4");
    
    $response = [
        'success' => true,
        'codigo_barras' => $codigo_barras,
        'existe' => false,
        'estado' => 'no_existe',
        'producto' => null,
        'stock_actual' => 0,
        'mensaje' => ''
    ];
    
    // 1. Verificar en stock (productos ya ingresados)
    $stmt = $conexion->prepare("SELECT * FROM stock WHERE Codigo_Producto = ? LIMIT 1");
    $stmt->bind_param("s", $codigo_barras);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $producto = $result->fetch_assoc();
        $response['existe'] = true;
        $response['estado'] = 'en_stock';
        $response['producto'] = $producto;
        $response['stock_actual'] = intval($producto['Stock']);
        $response['mensaje'] = "✅ Producto en stock: {$producto['Nombre_Producto']} ({$producto['Stock']} unidades)";
        $stmt->close();
        $conexion->close();
        echo json_encode($response);
        exit;
    }
    $stmt->close();
    
    // 2. Verificar en creacion_de_productos (productos creados pero sin stock)
    $stmt = $conexion->prepare("SELECT * FROM creacion_de_productos WHERE CodigoProducto = ? LIMIT 1");
    $stmt->bind_param("s", $codigo_barras);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $producto = $result->fetch_assoc();
        $response['existe'] = true;
        $response['estado'] = 'creado_sin_stock';
        $response['producto'] = $producto;
        $response['stock_actual'] = 0;
        $response['mensaje'] = "⚠️ Producto creado pero sin stock: {$producto['NombreProducto']}";
        $stmt->close();
        $conexion->close();
        echo json_encode($response);
        exit;
    }
    $stmt->close();
    
    // 3. No existe en ninguna tabla
    $response['mensaje'] = "❌ Producto no encontrado. Listo para crear con IA.";
    
    $conexion->close();
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
