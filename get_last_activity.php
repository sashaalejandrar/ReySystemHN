<?php
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$userId = isset($_GET['userId']) ? (int)$_GET['userId'] : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario inválido']);
    exit;
}

// Obtener última actividad del usuario
$stmt = $conexion->prepare("SELECT Ultima_Actividad FROM usuarios WHERE Id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'lastActivity' => $user['Ultima_Actividad']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
}

$stmt->close();
$conexion->close();
