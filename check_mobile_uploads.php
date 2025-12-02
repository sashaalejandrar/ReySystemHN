<?php
header('Content-Type: application/json');

$session_id = isset($_GET['session']) ? $_GET['session'] : '';

if (empty($session_id)) {
    echo json_encode(['files' => []]);
    exit;
}

$upload_dir = 'uploads/recibos_temp/' . $session_id . '/';

if (!file_exists($upload_dir)) {
    echo json_encode(['files' => []]);
    exit;
}

$files = [];
$scanned_files = scandir($upload_dir);

foreach ($scanned_files as $file) {
    if ($file !== '.' && $file !== '..') {
        $file_path = $upload_dir . $file;
        $mime_type = mime_content_type($file_path);
        
        $files[] = [
            'name' => $file,
            'path' => $file_path,
            'type' => $mime_type,
            'size' => filesize($file_path)
        ];
    }
}

echo json_encode(['files' => $files]);
?>
