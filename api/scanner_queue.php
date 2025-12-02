<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$usuario = $_SESSION['usuario'];

// Archivo temporal compartido por USUARIO (no por sesión)
$tempFile = sys_get_temp_dir() . '/scanner_queue_user_' . $usuario . '.json';

// Log para debug
error_log("Scanner Queue - Action: $action, User: $usuario, File: $tempFile");

switch ($action) {
    case 'push':
        // Guardar productos escaneados desde el móvil
        $productos = json_decode($_POST['productos'] ?? '[]', true);
        
        if (empty($productos)) {
            echo json_encode(['success' => false, 'message' => 'No hay productos']);
            exit;
        }
        
        // Guardar en archivo temporal
        $data = [
            'productos' => $productos,
            'timestamp' => time(),
            'usuario' => $usuario,
            'count' => count($productos)
        ];
        
        file_put_contents($tempFile, json_encode($data));
        error_log("Scanner Queue - Saved " . count($productos) . " products to: $tempFile");
        
        echo json_encode([
            'success' => true,
            'message' => 'Productos enviados a la PC',
            'count' => count($productos),
            'file' => $tempFile
        ]);
        break;
        
    case 'pull':
        // Obtener productos desde la PC
        if (!file_exists($tempFile)) {
            echo json_encode([
                'success' => true,
                'hasData' => false,
                'productos' => [],
                'debug' => 'File does not exist: ' . $tempFile
            ]);
            exit;
        }
        
        $data = json_decode(file_get_contents($tempFile), true);
        
        if (!$data) {
            echo json_encode([
                'success' => true,
                'hasData' => false,
                'productos' => [],
                'debug' => 'Could not decode file'
            ]);
            exit;
        }
        
        // Verificar que no sea muy antiguo (10 minutos)
        if (time() - $data['timestamp'] > 600) {
            unlink($tempFile);
            echo json_encode([
                'success' => true,
                'hasData' => false,
                'productos' => [],
                'debug' => 'Data too old'
            ]);
            exit;
        }
        
        // Devolver productos y eliminar archivo
        unlink($tempFile);
        error_log("Scanner Queue - Pulled " . count($data['productos']) . " products, deleted file");
        
        echo json_encode([
            'success' => true,
            'hasData' => true,
            'productos' => $data['productos'],
            'count' => count($data['productos']),
            'debug' => 'Data retrieved successfully'
        ]);
        break;
        
    case 'check':
        // Solo verificar si hay datos sin eliminar
        if (!file_exists($tempFile)) {
            echo json_encode([
                'success' => true,
                'hasData' => false,
                'file' => $tempFile
            ]);
            exit;
        }
        
        $data = json_decode(file_get_contents($tempFile), true);
        
        echo json_encode([
            'success' => true,
            'hasData' => true,
            'count' => count($data['productos'] ?? []),
            'timestamp' => $data['timestamp'] ?? 0
        ]);
        break;
        
    case 'clear':
        // Limpiar cola
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>
