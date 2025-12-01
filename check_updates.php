<?php
/**
 * API para verificar actualizaciones del sistema
 */
session_start();
header('Content-Type: application/json');

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

// Obtener rol desde la base de datos para estar seguros
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if (!$conexion->connect_error) {
    $usuario = $_SESSION['usuario'];
    $stmt = $conexion->prepare("SELECT rol FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $rol = strtolower($row['rol'] ?? '');
        
        if ($rol !== 'admin' && $rol !== 'administrador') {
            echo json_encode([
                'success' => false, 
                'message' => 'Acceso denegado - Solo administradores'
            ]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit();
    }
    
    $stmt->close();
    $conexion->close();
} else {
    // Si no hay conexión, verificar por sesión
    $rol = strtolower($_SESSION['rol'] ?? '');
    if ($rol !== 'admin' && $rol !== 'administrador') {
        echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
        exit();
    }
}

// Leer versión actual
$current_version = json_decode(file_get_contents('version.json'), true);

// Cargar configuración
$config = require_once 'update_config.php';
$github_user = $config['github']['user'];
$github_repo = $config['github']['repo'];
$github_api = "https://api.github.com/repos/{$github_user}/{$github_repo}/releases/latest";

$action = $_GET['action'] ?? 'check';

switch ($action) {
    case 'debug':
        // Endpoint de debug para verificar sesión
        echo json_encode([
            'success' => true,
            'session' => [
                'usuario' => $_SESSION['usuario'] ?? 'no definido',
                'rol' => $_SESSION['rol'] ?? 'no definido',
                'user_id' => $_SESSION['user_id'] ?? 'no definido',
                'all_keys' => array_keys($_SESSION)
            ]
        ]);
        break;
        
    case 'check':
        // Verificar actualizaciones desde GitHub
        try {
            // Configurar contexto para la petición HTTP
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: ReySystem-Updater',
                        'Accept: application/vnd.github.v3+json'
                    ],
                    'timeout' => 10
                ]
            ]);
            
            // Obtener última release de GitHub
            $response = @file_get_contents($github_api, false, $context);
            
            if ($response === false) {
                // No se pudo conectar a GitHub
                echo json_encode([
                    'success' => true,
                    'update' => [
                        'available' => false,
                        'current_version' => $current_version['version'],
                        'latest_version' => $current_version['version'],
                        'message' => 'No se pudo conectar a GitHub. Verifica tu conexión.'
                    ],
                    'current' => $current_version
                ]);
                break;
            }
            
            $github_release = json_decode($response, true);
            
            // Extraer versión del tag (ej: v2.5.0 -> 2.5.0)
            $latest_version = ltrim($github_release['tag_name'] ?? '', 'v');
            $current = $current_version['version'];
            
            // Comparar versiones
            $is_newer = version_compare($latest_version, $current, '>');
            
            // Buscar archivo de actualización en los assets
            // Prioridad: .tar.gz > .zip > zipball automático
            $download_url = null;
            $file_size = null;
            $file_type = 'zip';
            
            if (isset($github_release['assets']) && is_array($github_release['assets'])) {
                // Primero buscar tar.gz
                foreach ($github_release['assets'] as $asset) {
                    if (strpos($asset['name'], '.tar.gz') !== false) {
                        $download_url = $asset['browser_download_url'];
                        $file_size = $asset['size'];
                        $file_type = 'tar.gz';
                        break;
                    }
                }
                
                // Si no hay tar.gz, buscar zip
                if (!$download_url) {
                    foreach ($github_release['assets'] as $asset) {
                        if (strpos($asset['name'], '.zip') !== false) {
                            $download_url = $asset['browser_download_url'];
                            $file_size = $asset['size'];
                            $file_type = 'zip';
                            break;
                        }
                    }
                }
            }
            
            // Si no hay archivos subidos, usar el zipball automático de GitHub
            if (!$download_url) {
                $download_url = $github_release['zipball_url'] ?? null;
                $file_type = 'zip';
            }
            
            echo json_encode([
                'success' => true,
                'update' => [
                    'available' => $is_newer,
                    'current_version' => $current,
                    'latest_version' => $latest_version,
                    'release_date' => substr($github_release['published_at'] ?? '', 0, 10),
                    'release_name' => $github_release['name'] ?? "v{$latest_version}",
                    'changelog' => $github_release['body'] ?? 'Sin descripción',
                    'download_url' => $download_url,
                    'file_type' => $file_type,
                    'size' => $file_size ? round($file_size / 1024 / 1024, 2) . ' MB' : 'Desconocido',
                    'html_url' => $github_release['html_url'] ?? null
                ],
                'current' => $current_version
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al verificar actualizaciones: ' . $e->getMessage(),
                'current' => $current_version
            ]);
        }
        break;
        
    case 'download':
        // Descargar actualización desde GitHub
        $download_url = $_POST['download_url'] ?? '';
        $file_type = $_POST['file_type'] ?? 'zip';
        
        if (empty($download_url)) {
            echo json_encode(['success' => false, 'message' => 'URL de descarga no proporcionada']);
            break;
        }
        
        try {
            // Crear directorio temporal si no existe
            $temp_dir = __DIR__ . '/temp_updates';
            if (!is_dir($temp_dir)) {
                mkdir($temp_dir, 0755, true);
            }
            
            // Determinar extensión del archivo
            $extension = ($file_type === 'tar.gz') ? '.tar.gz' : '.zip';
            $update_file = $temp_dir . '/update_' . time() . $extension;
            
            // Descargar archivo
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => 'User-Agent: ReySystem-Updater',
                    'timeout' => 300, // 5 minutos
                    'follow_location' => 1
                ]
            ]);
            
            $content = @file_get_contents($download_url, false, $context);
            
            if ($content === false) {
                throw new Exception('No se pudo descargar el archivo');
            }
            
            file_put_contents($update_file, $content);
            
            echo json_encode([
                'success' => true,
                'message' => 'Descarga completada',
                'file' => basename($update_file),
                'file_type' => $file_type,
                'size' => round(filesize($update_file) / 1024 / 1024, 2) . ' MB'
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error en descarga: ' . $e->getMessage()
            ]);
        }
        break;
        
    case 'install':
        // Instalar actualización descargada
        $filename = $_POST['filename'] ?? '';
        $file_type = $_POST['file_type'] ?? 'zip';
        
        if (empty($filename)) {
            echo json_encode(['success' => false, 'message' => 'Archivo no especificado']);
            break;
        }
        
        try {
            $temp_dir = __DIR__ . '/temp_updates';
            $update_file = $temp_dir . '/' . $filename;
            
            if (!file_exists($update_file)) {
                throw new Exception('Archivo de actualización no encontrado');
            }
            
            // Crear backup del sistema actual
            $backup_dir = __DIR__ . '/backups';
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }
            
            $backup_file = $backup_dir . '/backup_' . date('Y-m-d_H-i-s') . '.zip';
            
            // Crear ZIP del sistema actual (backup)
            $zip = new ZipArchive();
            if ($zip->open($backup_file, ZipArchive::CREATE) === true) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(__DIR__),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                foreach ($files as $file) {
                    if (!$file->isDir() && 
                        strpos($file->getPathname(), '/temp_updates/') === false &&
                        strpos($file->getPathname(), '/backups/') === false) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen(__DIR__) + 1);
                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();
            }
            
            // Extraer actualización según el tipo
            if ($file_type === 'tar.gz') {
                // Extraer TAR.GZ
                $phar = new PharData($update_file);
                $phar->extractTo(__DIR__, null, true);
                
                // Eliminar archivo temporal
                unlink($update_file);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Actualización instalada correctamente (tar.gz)',
                    'backup' => basename($backup_file)
                ]);
            } else {
                // Extraer ZIP
                $update_zip = new ZipArchive();
                if ($update_zip->open($update_file) === true) {
                    $update_zip->extractTo(__DIR__);
                    $update_zip->close();
                    
                    // Eliminar archivo temporal
                    unlink($update_file);
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Actualización instalada correctamente (zip)',
                        'backup' => basename($backup_file)
                    ]);
                } else {
                    throw new Exception('No se pudo extraer el archivo ZIP');
                }
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error en instalación: ' . $e->getMessage()
            ]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>
