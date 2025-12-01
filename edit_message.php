<?php
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$data = json_decode(file_get_contents('php://input'), true);

$messageId = (int)$data['messageId'];
$userId = (int)$data['userId'];
$newMessage = $conexion->real_escape_string($data['newMessage']);

$stmt = $conexion->prepare("UPDATE mensajes_chat SET Mensaje = ?, Editado = 1 WHERE Id = ? AND Id_Emisor = ?");
$stmt->bind_param("sii", $newMessage, $messageId, $userId);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
}

$stmt->close();
$conexion->close();