<?php
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$data = json_decode(file_get_contents('php://input'), true);

$messageId = (int)$data['messageId'];

// Toggle pin status
$result = $conexion->query("SELECT Fijado, Mensaje FROM mensajes_chat WHERE Id = $messageId");
$row = $result->fetch_assoc();

$newStatus = $row['Fijado'] ? 0 : 1;
$conexion->query("UPDATE mensajes_chat SET Fijado = $newStatus WHERE Id = $messageId");

echo json_encode([
    'success' => true,
    'pinned' => (bool)$newStatus,
    'message' => $row['Mensaje']
]);

$conexion->close();