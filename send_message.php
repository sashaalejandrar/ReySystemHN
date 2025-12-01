<?php
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

if (!isset($data['senderId']) || !isset($data['receiverId']) || !isset($data['message'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$senderId = (int)$data['senderId'];
$receiverId = (int)$data['receiverId'];
$message = $conexion->real_escape_string($data['message']);
$type = $data['type'] ?? 'text';

// Obtener datos del usuario emisor (Nombre, Apellido, Usuario, Perfil son NOT NULL)
$stmt = $conexion->prepare("SELECT Id, Nombre, Apellido, usuario, Perfil FROM usuarios WHERE Id = ?");
$stmt->bind_param("i", $senderId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario emisor no encontrado']);
    exit;
}
$sender = $result->fetch_assoc();
$stmt->close();

// Insertar mensaje con todos los campos requeridos
$stmt = $conexion->prepare("INSERT INTO mensajes_chat (Id_Emisor, Id_Receptor, Nombre, Apellido, Usuario, Perfil, Mensaje, Tipo_Mensaje, Fecha_Mensaje, Estado_Entrega, leido) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'sent', 0)");
$stmt->bind_param("iissssss", $senderId, $receiverId, $sender['Nombre'], $sender['Apellido'], $sender['usuario'], $sender['Perfil'], $message, $type);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'messageId' => $stmt->insert_id,
        'timestamp' => date('g:i A')
    ]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conexion->close();