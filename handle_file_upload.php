<?php
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

if (!isset($_FILES['file']) || !isset($_POST['senderId']) || !isset($_POST['receiverId'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$senderId = (int)$_POST['senderId'];
$receiverId = (int)$_POST['receiverId'];
$file = $_FILES['file'];

// Validar errores de subida
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Error al subir archivo: ' . $file['error']]);
    exit;
}

// Validar tamaño (10MB max)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Archivo demasiado grande (máx 10MB)']);
    exit;
}

// Crear directorio si no existe
$uploadDir = 'uploads/chat/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generar nombre único y seguro
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'mp4', 'webm', 'mp3', 'wav', 'ogg'];

if (!in_array($ext, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
    exit;
}

$fileName = uniqid('chat_', true) . '_' . time() . '.' . $ext;
$filePath = $uploadDir . $fileName;

try {
    // Determinar tipo de mensaje basado en MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo); // Close finfo resource after use
    
    $messageType = 'file';
    if (strpos($mimeType, 'image/') === 0) {
        $messageType = 'image';
    } elseif (strpos($mimeType, 'video/') === 0) {
        $messageType = 'video';
    } elseif (strpos($mimeType, 'audio/') === 0 || $ext === 'webm') { // Use $ext instead of $extension
        $messageType = 'audio';
    } elseif (in_array($ext, ['pdf', 'doc', 'docx'])) { // Use $ext instead of $extension
        $messageType = 'document';
    }
    
    $fileUrl = $filePath;
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'message' => 'Error al mover archivo']);
        exit;
    }

    // Obtener datos del usuario emisor (Nombre, Apellido, Usuario, Perfil son NOT NULL)
    $stmt = $conexion->prepare("SELECT Id, Nombre, Apellido, usuario, Perfil FROM usuarios WHERE Id = ?");
    $stmt->bind_param("i", $senderId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        // Si el usuario no existe, eliminar el archivo subido
        unlink($filePath); // Use $filePath
        echo json_encode(['success' => false, 'message' => 'Usuario emisor no encontrado']);
        exit;
    }
    $sender = $result->fetch_assoc();
    $stmt->close();
    
    // Guardar en BD con todos los campos requeridos
    $stmt = $conexion->prepare("INSERT INTO mensajes_chat (Id_Emisor, Id_Receptor, Nombre, Apellido, Usuario, Perfil, Mensaje, Tipo_Mensaje, Archivo_URL, Archivo_Nombre, Archivo_Tamano, Fecha_Mensaje, Estado_Entrega, leido) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'sent', 0)");
    
    $mensaje = $file['name'];
    $stmt->bind_param("iissssssssi", $senderId, $receiverId, $sender['Nombre'], $sender['Apellido'], $sender['usuario'], $sender['Perfil'], $mensaje, $messageType, $fileUrl, $file['name'], $file['size']);
    
    if (!$stmt->execute()) {
        // Si falla la inserción, eliminar el archivo
        unlink($filePath); // Use $filePath
        echo json_encode(['success' => false, 'message' => 'Error al guardar en BD: ' . $stmt->error]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'messageId' => $stmt->insert_id,
        'fileName' => $file['name'],
        'fileUrl' => $fileUrl,
        'messageType' => $messageType,
        'timestamp' => date('g:i A')
    ]);
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conexion->close();