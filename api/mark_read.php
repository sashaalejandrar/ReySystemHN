<?php
/**
 * API: Mark Messages as Read
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    $conexion->set_charset("utf8mb4");
    
    // Get current user ID
    $stmt_user = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $_SESSION['usuario']);
    $stmt_user->execute();
    $current_user_id = $stmt_user->get_result()->fetch_assoc()['Id'];
    
    // Mark messages as read
    $sql = "UPDATE chat_messages SET is_read = 1 
            WHERE from_user_id = ? AND to_user_id = ? AND is_read = 0";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $data['from_user_id'], $current_user_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'marked' => $stmt->affected_rows
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
