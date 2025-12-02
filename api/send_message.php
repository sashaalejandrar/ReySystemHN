<?php
/**
 * API: Send Chat Message
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
    $result = $stmt_user->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    $from_user_id = $result->fetch_assoc()['Id'];
    
    // Check if this is FormData (with files) or JSON
    $to_user_id = null;
    $mensaje = '';
    $file_path = null;
    
    if (isset($_POST['to_user_id'])) {
        // FormData request
        $to_user_id = (int)$_POST['to_user_id'];
        $mensaje = $_POST['mensaje'] ?? '';
        
        // Handle file upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/chat/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('chat_') . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $upload_path)) {
                $file_path = 'uploads/chat/' . $new_filename;
            }
        }
    } else {
        // JSON request
        $data = json_decode(file_get_contents('php://input'), true);
        $to_user_id = isset($data['to_user_id']) ? (int)$data['to_user_id'] : null;
        $mensaje = $data['mensaje'] ?? '';
    }
    
    // Validate to_user_id
    if (!$to_user_id) {
        echo json_encode(['success' => false, 'message' => 'ID de usuario destino requerido']);
        exit;
    }
    
    // Allow empty message if there's a file
    if (empty($mensaje) && !$file_path) {
        echo json_encode(['success' => false, 'message' => 'Mensaje o archivo requerido']);
        exit;
    }
    
    // If message is empty but we have a file, set a default message
    if (empty($mensaje) && $file_path) {
        $mensaje = '[Archivo adjunto]';
    }
    
    // Insert message
    $sql = "INSERT INTO chat_messages (from_user_id, to_user_id, mensaje, file_path) VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiss", $from_user_id, $to_user_id, $mensaje, $file_path);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message_id' => $conexion->insert_id,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } else {
        throw new Exception('Error al enviar mensaje: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
