<?php
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Intentar obtener ID de usuario desde la sesión directamente
$usuario_id = $_SESSION['user_id'] ?? null;

// Si no está en sesión, obtenerlo desde el nombre de usuario
if (!$usuario_id) {
    $usuario_nombre = $_SESSION['usuario'] ?? null;
    
    if (!$usuario_nombre) {
        echo json_encode(['success' => false, 'message' => 'No autenticado']);
        exit;
    }
    
    // Obtener ID del usuario
    $stmt = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario_nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    
    if (!$usuario) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    $usuario_id = $usuario['Id'];
    $_SESSION['user_id'] = $usuario_id; // Guardar para próximas peticiones
}

// Actualizar última actividad
$stmt = $conexion->prepare("UPDATE usuarios SET Ultima_Actividad = NOW() WHERE Id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$stmt->close();

// Obtener usuarios en línea (últimos 60 segundos)
$result = $conexion->query("SELECT Id FROM usuarios WHERE Ultima_Actividad >= DATE_SUB(NOW(), INTERVAL 60 SECOND)");

$online = [];
while ($row = $result->fetch_assoc()) {
    $online[] = (int)$row['Id'];
}

echo json_encode([
    'success' => true,
    'online_users' => $online,
    'current_user_id' => $usuario_id
]);

$conexion->close();