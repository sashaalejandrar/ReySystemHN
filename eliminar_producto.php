<?php
session_start();
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Obtener datos JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
    exit;
}

$id = intval($data['id']);

// Eliminar producto de stock
$stmt = $conexion->prepare("DELETE FROM stock WHERE Id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Producto eliminado del inventario'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al eliminar producto'
    ]);
}

$conexion->close();
?>