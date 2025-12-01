<?php
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$data = json_decode(file_get_contents('php://input'), true);

$messageId = (int)$data['messageId'];
$userId = (int)$data['userId'];

// Verificar que el mensaje pertenece al usuario
$stmt = $conexion->prepare("DELETE FROM mensajes_chat WHERE Id = ? AND Id_Emisor = ?");
$stmt->bind_param("ii", $messageId, $userId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No autorizado o mensaje no encontrado']);
}

$stmt->close();
$conexion->close();