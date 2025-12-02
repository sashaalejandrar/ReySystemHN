<?php
/**
 * API: Create Forum Post
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
    
    // Get user info
    $stmt_user = $conexion->prepare("SELECT Id, Nombre, Apellido, Perfil FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $_SESSION['usuario']);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    $usuario_nombre = $user['Nombre'] . ' ' . $user['Apellido'];
    $usuario_avatar = $user['Perfil'];
    $contenido = '';
    $imagen = null;
    
    // Check if this is FormData (with files) or JSON
    if (isset($_POST['contenido'])) {
        // FormData request
        $contenido = trim($_POST['contenido']);
        
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/foro/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('img_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $imagen = 'uploads/foro/' . $new_filename;
            }
        }
    } else {
        // JSON request
        $data = json_decode(file_get_contents('php://input'), true);
        $contenido = trim($data['contenido'] ?? '');
        $imagen = $data['imagen'] ?? null;
    }
    
    // Allow empty content if there's an image
    if (empty($contenido) && !$imagen) {
        echo json_encode(['success' => false, 'message' => 'Debes escribir algo o adjuntar una imagen']);
        exit;
    }
    
    // If content is empty but we have an image, set a default message
    if (empty($contenido) && $imagen) {
        $contenido = '[Imagen adjunta]';
    }
    
    // Insert post
    $sql = "INSERT INTO foro_posts (usuario_id, usuario_nombre, usuario_avatar, contenido, imagen) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("issss", 
        $user['Id'],
        $usuario_nombre,
        $usuario_avatar,
        $contenido,
        $imagen
    );
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Post creado exitosamente',
            'post_id' => $conexion->insert_id
        ]);
    } else {
        throw new Exception('Error al crear post: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
