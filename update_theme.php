<?php
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['theme'])) {
    echo json_encode(['success' => false]);
    exit;
}

$theme = $data['theme'];
$userId = $_SESSION['usuario_id'] ?? 0;

if ($userId > 0) {
    $stmt = $conexion->prepare("INSERT INTO preferencias_usuario (Id_Usuario, tema_color) VALUES (?, ?) ON DUPLICATE KEY UPDATE tema_color = ?");
    $stmt->bind_param("iss", $userId, $theme, $theme);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false]);
}

$conexion->close();