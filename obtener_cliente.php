<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $conexion->prepare("SELECT * FROM clientes WHERE Id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $cliente = $result->fetch_assoc();
        echo json_encode(['success' => true, 'cliente' => $cliente]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
}

$conexion->close();
?>
