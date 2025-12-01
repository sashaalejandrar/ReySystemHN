<?php
/**
 * API: Like/Unlike Post
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
    
    // Get user ID
    $stmt_user = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $_SESSION['usuario']);
    $stmt_user->execute();
    $user_id = $stmt_user->get_result()->fetch_assoc()['Id'];
    
    // Check if already liked
    $stmt_check = $conexion->prepare("SELECT id FROM foro_likes WHERE post_id = ? AND usuario_id = ?");
    $stmt_check->bind_param("ii", $data['post_id'], $user_id);
    $stmt_check->execute();
    $exists = $stmt_check->get_result()->num_rows > 0;
    
    if ($exists) {
        // Unlike
        $stmt_delete = $conexion->prepare("DELETE FROM foro_likes WHERE post_id = ? AND usuario_id = ?");
        $stmt_delete->bind_param("ii", $data['post_id'], $user_id);
        $stmt_delete->execute();
        
        // Decrease count
        $conexion->query("UPDATE foro_posts SET likes_count = likes_count - 1 WHERE id = " . $data['post_id']);
        
        $action = 'unliked';
    } else {
        // Like
        $stmt_insert = $conexion->prepare("INSERT INTO foro_likes (post_id, usuario_id) VALUES (?, ?)");
        $stmt_insert->bind_param("ii", $data['post_id'], $user_id);
        $stmt_insert->execute();
        
        // Increase count
        $conexion->query("UPDATE foro_posts SET likes_count = likes_count + 1 WHERE id = " . $data['post_id']);
        
        $action = 'liked';
    }
    
    // Get updated count
    $stmt_count = $conexion->prepare("SELECT likes_count FROM foro_posts WHERE id = ?");
    $stmt_count->bind_param("i", $data['post_id']);
    $stmt_count->execute();
    $likes_count = $stmt_count->get_result()->fetch_assoc()['likes_count'];
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes_count' => $likes_count
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
