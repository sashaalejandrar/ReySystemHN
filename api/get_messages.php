<?php
/**
 * API: Get Chat Messages
 * Returns messages between two users (for polling)
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    $conexion->set_charset("utf8mb4");
    
    // Get current user ID
    $stmt_user = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $_SESSION['usuario']);
    $stmt_user->execute();
    $current_user_id = $stmt_user->get_result()->fetch_assoc()['Id'];
    
    $other_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;
    
    // Get messages
    $sql = "SELECT m.*, 
            u1.Nombre as from_nombre, u1.Apellido as from_apellido, u1.Perfil as from_avatar,
            u2.Nombre as to_nombre, u2.Apellido as to_apellido
        FROM chat_messages m
        JOIN usuarios u1 ON m.from_user_id = u1.Id
        JOIN usuarios u2 ON m.to_user_id = u2.Id
        WHERE ((m.from_user_id = ? AND m.to_user_id = ?) 
            OR (m.from_user_id = ? AND m.to_user_id = ?))
            AND m.id > ?
        ORDER BY m.created_at ASC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiiii", $current_user_id, $other_user_id, $other_user_id, $current_user_id, $last_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
