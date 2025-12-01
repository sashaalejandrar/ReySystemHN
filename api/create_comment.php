<?php
/**
 * API: Create Comment
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
    
    // Get user info
    $stmt_user = $conexion->prepare("SELECT Id, Nombre, Apellido, Perfil FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $_SESSION['usuario']);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    
    $usuario_nombre = $user['Nombre'] . ' ' . $user['Apellido'];
    $usuario_avatar = $user['Perfil'];
    
    // Insert comment
    $sql = "INSERT INTO foro_comments (post_id, usuario_id, usuario_nombre, usuario_avatar, contenido) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iisss", 
        $data['post_id'],
        $user['Id'],
        $usuario_nombre,
        $usuario_avatar,
        $data['contenido']
    );
    
    if ($stmt->execute()) {
        // Update comments count
        $conexion->query("UPDATE foro_posts SET comments_count = comments_count + 1 WHERE id = " . $data['post_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Comentario agregado',
            'comment' => [
                'id' => $conexion->insert_id,
                'usuario_nombre' => $usuario_nombre,
                'usuario_avatar' => $usuario_avatar,
                'contenido' => $data['contenido'],
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Error al crear comentario');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
