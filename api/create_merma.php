<?php
/**
 * API: Create Merma (Waste/Loss Record)
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    $conexion->set_charset("utf8mb4");
    
    // Get user info
    $stmt_user = $conexion->prepare("SELECT Id, Nombre, Apellido FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $_SESSION['usuario']);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    $usuario_nombre = $user['Nombre'] . ' ' . $user['Apellido'];
    
    // Get product info
    $stmt_prod = $conexion->prepare("SELECT Nombre_Producto, Precio_Unitario FROM stock WHERE Id = ?");
    $stmt_prod->bind_param("i", $data['producto_id']);
    $stmt_prod->execute();
    $producto = $stmt_prod->get_result()->fetch_assoc();
    
    $costo_total = $data['cantidad'] * $producto['Precio_Unitario'];
    
    // Insert merma
    $sql = "INSERT INTO mermas (producto_id, producto_nombre, cantidad, motivo, descripcion, 
            costo_unitario, costo_total, usuario_id, usuario_nombre, fecha) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("isdssddiss", 
        $data['producto_id'],
        $producto['Nombre_Producto'],
        $data['cantidad'],
        $data['motivo'],
        $data['descripcion'],
        $producto['Precio_Unitario'],
        $costo_total,
        $user['Id'],
        $usuario_nombre,
        $data['fecha']
    );
    
    if ($stmt->execute()) {
        // Update inventory (reduce stock)
        $stmt_update = $conexion->prepare("UPDATE stock SET Stock = Stock - ? WHERE Id = ?");
        $stmt_update->bind_param("di", $data['cantidad'], $data['producto_id']);
        $stmt_update->execute();
        
        echo json_encode(['success' => true, 'message' => 'Merma registrada correctamente']);
    } else {
        throw new Exception('Error al registrar merma');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conexion->close();
?>
