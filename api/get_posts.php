<?php
/**
 * API: Get Forum Posts
 * Returns posts with pagination, likes, and comments
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
    
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
    
    // Get current user ID
    $stmt_user = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $_SESSION['usuario']);
    $stmt_user->execute();
    $current_user_id = $stmt_user->get_result()->fetch_assoc()['Id'];
    
    // Get posts with like status
    $sql = "SELECT 
        p.*,
        (SELECT COUNT(*) FROM foro_likes WHERE post_id = p.id AND usuario_id = ?) as user_liked
    FROM foro_posts p
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iii", $current_user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $posts = [];
    while ($row = $result->fetch_assoc()) {
        // Get comments for this post
        $stmt_comments = $conexion->prepare("
            SELECT * FROM foro_comments 
            WHERE post_id = ? 
            ORDER BY created_at ASC 
            LIMIT 5
        ");
        $stmt_comments->bind_param("i", $row['id']);
        $stmt_comments->execute();
        $comments_result = $stmt_comments->get_result();
        
        $comments = [];
        while ($comment = $comments_result->fetch_assoc()) {
            $comments[] = $comment;
        }
        
        $row['comments'] = $comments;
        $row['user_liked'] = $row['user_liked'] > 0;
        $posts[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $posts,
        'offset' => $offset,
        'limit' => $limit
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
