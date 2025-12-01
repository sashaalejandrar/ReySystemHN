<?php
session_start();
header('Content-Type: application/json');

function logError($message) {
    $logFile = 'api_errors.log';
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[$timestamp] " . $message . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

try {
    $codigo = trim($_GET['codigo'] ?? '');
    $autocompletar = ($_GET['autocompletar'] ?? '0') === '1';

    if (empty($codigo)) {
        echo json_encode(['success' => false, 'message' => 'Código vacío']);
        exit;
    }

    // Conectar a la base de datos
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        logError("Error de conexión BD: " . $conexion->connect_error);
        echo json_encode(['success' => false, 'message' => 'Error de conexión']);
        exit;
    }
    
    $conexion->set_charset("utf8mb4");

    if ($autocompletar) {
        // OBTENER DE TABLA creacion_de_productos (catálogo completo)
        logError("Obteniendo producto de creacion_de_productos: $codigo");
        
        $stmt = $conexion->prepare("SELECT * FROM creacion_de_productos WHERE CodigoProducto = ? LIMIT 1");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $producto = $resultado->fetch_assoc();
            logError("Producto encontrado en creacion_de_productos");
            echo json_encode([
                'success' => true,
                'producto' => $producto,
                'source' => 'catalogo'
            ]);
        } else {
            logError("Producto no encontrado en creacion_de_productos");
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado en catálogo']);
        }
        
        $stmt->close();
        
    } else {
        // OBTENER DE TABLA stock (inventario local)
        logError("Obteniendo producto de stock: $codigo");
        
        $stmt = $conexion->prepare("SELECT * FROM stock WHERE Codigo_Producto = ? LIMIT 1");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            $producto = $resultado->fetch_assoc();
            logError("Producto encontrado en stock");
            echo json_encode([
                'success' => true,
                'producto' => $producto,
                'source' => 'local'
            ]);
        } else {
            logError("Producto no encontrado en stock");
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado en inventario local']);
        }
        
        $stmt->close();
    }
    
    $conexion->close();

} catch (Exception $e) {
    logError("Exception en obtener_producto: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>