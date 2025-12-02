<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $celular = isset($_POST['celular']) ? trim($_POST['celular']) : '';
    $direccion = isset($_POST['direccion']) ? trim($_POST['direccion']) : '';
    
    if ($id > 0 && !empty($nombre) && !empty($celular)) {
        $stmt = $conexion->prepare("UPDATE clientes SET Nombre = ?, Celular = ?, Direccion = ? WHERE Id = ?");
        $stmt->bind_param("sssi", $nombre, $celular, $direccion, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cliente actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el cliente']);
        }
        
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}

$conexion->close();
?>
