<?php
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$data = json_decode(file_get_contents('php://input'), true);

$messageId = (int)$data['messageId'];
$targetUserId = (int)$data['targetUserId'];
$senderId = $_SESSION['usuario_id'] ?? 0;

// Obtener mensaje original
$result = $conexion->query("SELECT Mensaje, Tipo_Mensaje FROM mensajes_chat WHERE Id = $messageId");
$msg = $result->fetch_assoc();

if ($msg) {
    $stmt = $conexion->prepare("INSERT INTO mensajes_chat (Id_Emisor, Id_Receptor, Mensaje, Tipo_Mensaje, Fecha_Mensaje) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("iiss", $senderId, $targetUserId, $msg['Mensaje'], $msg['Tipo_Mensaje']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'messageId' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Mensaje no encontrado']);
}

$conexion->close();