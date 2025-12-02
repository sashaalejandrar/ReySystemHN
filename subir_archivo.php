<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar que se haya enviado un archivo
if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No se recibió ningún archivo']);
    exit;
}

$archivo = $_FILES['archivo'];
$usuario = getUsuarioActual($conn);

// Configuración de subida
$upload_dir = 'uploads/';
$max_size = 50 * 1024 * 1024; // 50 MB

// Crear directorio si no existe
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Validar tamaño
if ($archivo['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'El archivo es demasiado grande (máximo 50MB)']);
    exit;
}

// Obtener información del archivo
$nombre_original = basename($archivo['name']);
$extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
$tipo_mime = $archivo['type'];

// Determinar tipo de archivo (imagen o video)
$imagenes_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$videos_permitidos = ['mp4', 'webm', 'ogg', 'mov', 'avi'];

if (in_array($extension, $imagenes_permitidas)) {
    $tipo_archivo = 'imagen';
} elseif (in_array($extension, $videos_permitidos)) {
    $tipo_archivo = 'video';
} else {
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
    exit;
}

// Generar nombre único
$nombre_unico = uniqid() . '_' . time() . '.' . $extension;
$ruta_completa = $upload_dir . $nombre_unico;

// Mover archivo
if (!move_uploaded_file($archivo['tmp_name'], $ruta_completa)) {
    echo json_encode(['success' => false, 'message' => 'Error al subir el archivo']);
    exit;
}

// Respuesta exitosa
echo json_encode([
    'success' => true,
    'archivo' => [
        'nombre_original' => $nombre_original,
        'nombre_archivo' => $nombre_unico,
        'ruta' => $ruta_completa,
        'tipo' => $tipo_archivo,
        'tipo_mime' => $tipo_mime,
        'tamano' => $archivo['size']
    ]
]);
?>