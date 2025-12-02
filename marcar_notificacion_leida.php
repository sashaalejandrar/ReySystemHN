<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Obtener el ID del usuario
$stmt = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$resultado = $stmt->get_result();
$row = $resultado->fetch_assoc();
$usuario_id = $row['Id'];
$stmt->close();

// Marcar todas las notificaciones como leídas
$stmt = $conexion->prepare("UPDATE notificaciones SET leido = 1 WHERE usuario_id = ? AND leido = 0");
$stmt->bind_param("i", $usuario_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Notificaciones marcadas como leídas']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar']);
}

$stmt->close();
$conexion->close();
?>