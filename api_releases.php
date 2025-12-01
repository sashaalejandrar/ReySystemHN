<?php
/**
 * API para gestionar releases del sistema
 */

// Cargar variables de entorno desde .env
if (file_exists(__DIR__ . '/.env')) {
    $env_lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Establecer en putenv y $_ENV
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$usuario = $_SESSION['usuario'];
$action = $_GET['action'] ?? '';

// Función para logging
function logRelease($message) {
    $log_file = __DIR__ . '/logs/releases.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

switch ($action) {
    case 'create':
        createRelease($conexion, $usuario);
        break;
        
    case 'publish':
        publishRelease($conexion, $_GET['id'] ?? 0);
        break;
        
    case 'delete':
        deleteRelease($conexion, $_GET['id'] ?? 0);
        break;
        
    case 'upload_github':
        uploadToGitHub($conexion, $_GET['id'] ?? 0);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

function createRelease($conexion, $usuario) {
    try {
        $version = $_POST['version'] ?? '';
        $codename = $_POST['codename'] ?? '';
        $release_type = $_POST['release_type'] ?? 'minor';
        $release_date = $_POST['release_date'] ?? date('Y-m-d');
        $file_type = $_POST['file_type'] ?? 'tar.gz';
        $changes = $_POST['changes'] ?? '';
        $create_file = isset($_POST['create_file']);
        $git_commit = isset($_POST['git_commit']);
        
        // Validar versión
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            throw new Exception('Formato de versión inválido. Usa MAJOR.MINOR.PATCH');
        }
        
        // Procesar cambios
        $changes_array = array_filter(array_map('trim', explode("\n", $changes)));
        $changes_json = json_encode($changes_array);
        
        // Generar build number
        $build = date('Ymd');
        
        // Insertar en BD
        $stmt = $conexion->prepare("
            INSERT INTO updates (version, codename, build, release_date, release_type, changelog, changes_json, file_type, created_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
        ");
        
        $changelog = implode("\n", $changes_array);
        $stmt->bind_param("sssssssss", $version, $codename, $build, $release_date, $release_type, $changelog, $changes_json, $file_type, $usuario);
        
        if (!$stmt->execute()) {
            throw new Exception('Error al guardar en BD: ' . $stmt->error);
        }
        
        $release_id = $stmt->insert_id;
        
        // Crear archivo comprimido si se solicitó
        $file_path = null;
        $file_size = null;
        $file_result = null;
        
        if ($create_file) {
            $result = createReleaseFile($version, $file_type);
            $file_result = $result;
            
            if ($result['success']) {
                $file_path = $result['file_path'];
                $file_size = $result['file_size'];
                
                // Actualizar BD con info del archivo
                $stmt = $conexion->prepare("UPDATE updates SET file_path = ?, file_size = ? WHERE id = ?");
                $stmt->bind_param("ssi", $file_path, $file_size, $release_id);
                $stmt->execute();
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Release creada exitosamente',
            'release_id' => $release_id,
            'file_created' => $create_file,
            'file_path' => $file_path,
            'file_size' => $file_size,
            'file_result' => $file_result
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function publishRelease($conexion, $id) {
    try {
        // Obtener release
        $stmt = $conexion->prepare("SELECT * FROM updates WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Release no encontrada');
        }
        
        $release = $result->fetch_assoc();
        
        // Verificar que version.json existe
        if (!file_exists('version.json')) {
            throw new Exception('Archivo version.json no encontrado');
        }
        
        // Verificar permisos de escritura
        if (!is_writable('version.json')) {
            // Intentar cambiar permisos automáticamente
            if (@chmod('version.json', 0666)) {
                // Permisos cambiados exitosamente
                clearstatcache(true, 'version.json');
            } else {
                throw new Exception('No se puede escribir en version.json. Ejecuta: chmod 666 version.json');
            }
        }
        
        // Leer version.json
        $version_content = file_get_contents('version.json');
        if ($version_content === false) {
            throw new Exception('Error al leer version.json');
        }
        
        $version_data = json_decode($version_content, true);
        if ($version_data === null) {
            throw new Exception('Error al decodificar version.json: ' . json_last_error_msg());
        }
        
        // Crear backup
        $backup_file = 'version.json.backup.' . time();
        copy('version.json', $backup_file);
        
        // Agregar al changelog
        $new_changelog_entry = [
            'version' => $release['version'],
            'date' => $release['release_date'],
            'type' => $release['release_type'],
            'changes' => json_decode($release['changes_json'], true)
        ];
        
        // Verificar que no exista ya esta versión en el changelog
        $version_exists = false;
        foreach ($version_data['changelog'] as $entry) {
            if ($entry['version'] === $release['version']) {
                $version_exists = true;
                break;
            }
        }
        
        if (!$version_exists) {
            array_unshift($version_data['changelog'], $new_changelog_entry);
        }
        
        // Actualizar versión actual
        $version_data['version'] = $release['version'];
        $version_data['build'] = $release['build'];
        $version_data['release_date'] = $release['release_date'];
        if (!empty($release['codename'])) {
            $version_data['codename'] = $release['codename'];
        }
        
        // Guardar version.json
        $json_output = json_encode($version_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $bytes_written = file_put_contents('version.json', $json_output);
        
        if ($bytes_written === false) {
            throw new Exception('Error al escribir version.json');
        }
        
        // Verificar que se escribió correctamente
        $verify_content = file_get_contents('version.json');
        $verify_data = json_decode($verify_content, true);
        if ($verify_data['version'] !== $release['version']) {
            throw new Exception('Error: version.json no se actualizó correctamente');
        }
        
        // Hacer commit a Git y push
        $git_tag = "v{$release['version']}";
        $git_result = [];
        $git_success = false;
        
        logRelease("Iniciando publicación de release {$git_tag}");
        
        if (function_exists('exec')) {
            // Cambiar al directorio del proyecto
            chdir(__DIR__);
            logRelease("Directorio: " . getcwd());
            
            // Configurar directorio como seguro para Git
            $safe_dir = __DIR__;
            exec("git config --global --add safe.directory {$safe_dir} 2>&1", $safe_output, $safe_code);
            logRelease("Configurando safe.directory: código $safe_code");
            
            // Verificar si es un repositorio Git
            exec("git rev-parse --git-dir 2>&1", $git_check, $git_exists);
            logRelease("Git repo check código: $git_exists");
            
            if ($git_exists !== 0) {
                // No es un repositorio Git - inicializar
                exec("git init 2>&1", $init_output, $init_code);
                $git_result[] = "Git init: " . implode("\n", $init_output);
                logRelease("Git inicializado");
            }
            
            // Verificar si hay remote configurado
            exec("git remote 2>&1", $remote_list, $remote_list_code);
            logRelease("Git remotes: " . implode(", ", $remote_list) . " [código: $remote_list_code]");
            
            if (empty($remote_list) || !in_array('origin', $remote_list)) {
                // No hay remote origin, intentar agregarlo
                logRelease("Intentando agregar remote origin");
                $remote_url = 'https://github.com/sashaalejandrar/ReySystemHN.git';
                exec("git remote add origin {$remote_url} 2>&1", $add_remote_output, $add_remote_code);
                logRelease("git remote add código: $add_remote_code, output: " . implode(", ", $add_remote_output));
                
                if ($add_remote_code !== 0) {
                    // Si falla, verificar si ya existe
                    exec("git remote get-url origin 2>&1", $check_origin, $check_origin_code);
                    if ($check_origin_code !== 0) {
                        logRelease("ERROR: No se pudo configurar remote");
                        throw new Exception('No se pudo configurar remote de GitHub. Verifica la configuración de Git.');
                    }
                }
            }
            
            // Verificar URL del remote
            exec("git remote get-url origin 2>&1", $remote_url_check, $remote_url_code);
            if ($remote_url_code === 0 && !empty($remote_url_check[0])) {
                logRelease("Remote origin URL: " . $remote_url_check[0]);
            } else {
                logRelease("WARNING: No se pudo obtener URL del remote");
            }
            
            // 1. Add version.json
            logRelease("Ejecutando: git add version.json");
            exec("git add version.json 2>&1", $output1, $code1);
            $git_result[] = "git add: " . (empty($output1) ? "(vacío)" : implode("\n", $output1)) . " [código: $code1]";
            logRelease("git add código: $code1");
            
            // 2. Commit
            $commit_msg = "Release {$git_tag}" . ($release['codename'] ? " - {$release['codename']}" : "");
            logRelease("Ejecutando: git commit -m '$commit_msg'");
            exec("git commit -m '{$commit_msg}' 2>&1", $output2, $code2);
            $git_result[] = "git commit: " . implode("\n", $output2) . " [código: $code2]";
            logRelease("git commit código: $code2, output: " . implode(", ", $output2));
            
            // 3. Tag
            $tag_msg = $release['codename'] ?: "Release {$git_tag}";
            logRelease("Ejecutando: git tag -a {$git_tag} -m '$tag_msg'");
            exec("git tag -a {$git_tag} -m '{$tag_msg}' 2>&1", $output3, $code3);
            $git_result[] = "git tag: " . (empty($output3) ? "(vacío)" : implode("\n", $output3)) . " [código: $code3]";
            logRelease("git tag código: $code3");
            
            // Obtener token de GitHub para push
            $gh_token_for_push = null;
            if (isset($_ENV['GH_TOKEN'])) {
                $gh_token_for_push = $_ENV['GH_TOKEN'];
            } elseif (file_exists(__DIR__ . '/.env')) {
                $env_content = file_get_contents(__DIR__ . '/.env');
                if (preg_match('/GH_TOKEN=(.+)/', $env_content, $matches)) {
                    $gh_token_for_push = trim($matches[1]);
                }
            }
            
            // Construir URL con token para push
            $push_url = 'origin';
            if ($gh_token_for_push) {
                $push_url = "https://sashaalejandrar:{$gh_token_for_push}@github.com/sashaalejandrar/ReySystemHN.git";
                logRelease("Usando URL con token para push");
            } else {
                logRelease("WARNING: No hay token, usando origin normal");
            }
            
            // 4. Push main
            logRelease("Ejecutando: git push {$push_url} main");
            exec("git push {$push_url} main 2>&1", $output4, $code4);
            $git_result[] = "git push main: " . implode("\n", $output4) . " [código: $code4]";
            logRelease("git push main código: $code4, output: " . implode(", ", $output4));
            
            if ($code4 !== 0) {
                logRelease("ERROR: git push main falló");
                throw new Exception('Error al hacer push a main: ' . implode("\n", $output4));
            }
            
            // 5. Push tag
            logRelease("Ejecutando: git push {$push_url} {$git_tag}");
            exec("git push {$push_url} {$git_tag} 2>&1", $output5, $code5);
            $git_result[] = "git push tag: " . implode("\n", $output5) . " [código: $code5]";
            logRelease("git push tag código: $code5, output: " . implode(", ", $output5));
            logRelease("git push tag código: $code5, output: " . implode(", ", $output5));
            
            if ($code5 !== 0) {
                logRelease("ERROR: git push tag falló");
                throw new Exception('Error al hacer push del tag: ' . implode("\n", $output5));
            }
            
            $git_success = true;
            logRelease("Push exitoso");
            
            // Crear release en GitHub con gh CLI
            logRelease("Verificando GitHub CLI...");
            exec("which gh 2>&1", $gh_check, $gh_exists);
            logRelease("GitHub CLI existe: " . ($gh_exists === 0 ? 'true' : 'false'));
            
            if ($gh_exists === 0) {
                // Obtener token de GitHub desde múltiples fuentes
                $gh_token = null;
                
                // 1. Intentar desde variables de entorno
                if (isset($_ENV['GH_TOKEN'])) {
                    $gh_token = $_ENV['GH_TOKEN'];
                    logRelease("Token obtenido de \$_ENV['GH_TOKEN']");
                } elseif (isset($_ENV['GITHUB_TOKEN'])) {
                    $gh_token = $_ENV['GITHUB_TOKEN'];
                    logRelease("Token obtenido de \$_ENV['GITHUB_TOKEN']");
                } elseif (getenv('GH_TOKEN')) {
                    $gh_token = getenv('GH_TOKEN');
                    logRelease("Token obtenido de getenv('GH_TOKEN')");
                } elseif (getenv('GITHUB_TOKEN')) {
                    $gh_token = getenv('GITHUB_TOKEN');
                    logRelease("Token obtenido de getenv('GITHUB_TOKEN')");
                }
                
                // 2. Si no hay token, intentar leer directamente del .env
                if (!$gh_token && file_exists(__DIR__ . '/.env')) {
                    $env_content = file_get_contents(__DIR__ . '/.env');
                    if (preg_match('/GH_TOKEN=(.+)/', $env_content, $matches)) {
                        $gh_token = trim($matches[1]);
                        logRelease("Token obtenido directamente de .env");
                    }
                }
                
                // 3. Como último recurso, intentar gh auth token
                if (!$gh_token) {
                    exec("gh auth token 2>&1", $token_output, $token_code);
                    if ($token_code === 0 && !empty($token_output[0])) {
                        $gh_token = trim($token_output[0]);
                        logRelease("Token obtenido de gh auth token");
                    }
                }
                
                // Si aún no hay token, error
                if (!$gh_token) {
                    logRelease("ERROR: No se pudo obtener token de GitHub de ninguna fuente");
                    throw new Exception('GitHub CLI no está autenticado. Ejecuta: gh auth login');
                }
                
                logRelease("Token de GitHub disponible: " . substr($gh_token, 0, 10) . "...");
                
                // Preparar changelog para GitHub
                $changes = json_decode($release['changes_json'], true);
                $github_notes = "## 🎉 Novedades\n\n";
                foreach ($changes as $change) {
                    $github_notes .= "- {$change}\n";
                }
                $github_notes .= "\n## 📦 Instalación\n\nDescarga el archivo adjunto y extrae en tu servidor.";
                
                // Crear archivo temporal con notas
                $temp_notes = tempnam(sys_get_temp_dir(), 'gh_notes_');
                file_put_contents($temp_notes, $github_notes);
                logRelease("Notas guardadas en: $temp_notes");
                
                // Configurar variables de entorno para gh
                $env_vars = [
                    "GH_TOKEN={$gh_token}",
                    "GITHUB_TOKEN={$gh_token}",
                    "GH_HOST=github.com"
                ];
                $env_string = implode(' ', $env_vars);
                
                // Comando base con variables de entorno
                $gh_cmd = "{$env_string} gh release create {$git_tag} --title " . escapeshellarg($commit_msg) . " --notes-file " . escapeshellarg($temp_notes);
                
                // Agregar archivo si existe
                if ($release['file_path'] && file_exists($release['file_path'])) {
                    $gh_cmd .= " " . escapeshellarg($release['file_path']);
                    logRelease("Archivo a subir: {$release['file_path']}");
                }
                
                // Ejecutar
                logRelease("Ejecutando: gh release create (con token)");
                exec($gh_cmd . " 2>&1", $output6, $code6);
                $git_result[] = "GitHub Release: " . implode("\n", $output6) . " [código: $code6]";
                logRelease("gh release create código: $code6, output: " . implode(", ", $output6));
                
                // Limpiar archivo temporal
                unlink($temp_notes);
                
                if ($code6 === 0) {
                    // Obtener URL de la release
                    exec("{$env_string} gh release view {$git_tag} --json url -q .url 2>&1", $output7, $code7);
                    if ($code7 === 0 && !empty($output7[0])) {
                        $github_url = trim($output7[0]);
                        $stmt = $conexion->prepare("UPDATE updates SET github_release_url = ? WHERE id = ?");
                        $stmt->bind_param("si", $github_url, $id);
                        $stmt->execute();
                        $git_result[] = "Release URL: {$github_url}";
                    }
                } else {
                    $git_result[] = "⚠️ Error al crear release en GitHub (código: {$code6})";
                }
            } else {
                $git_result[] = "⚠️ GitHub CLI (gh) no está instalado. Instala con: sudo apt install gh";
                $git_result[] = "📝 Puedes crear la release manualmente en GitHub";
            }
        }
        
        // Actualizar estado en BD
        $stmt = $conexion->prepare("UPDATE updates SET status = 'published', published_at = NOW(), github_tag = ? WHERE id = ?");
        $stmt->bind_param("si", $git_tag, $id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Release publicada exitosamente' . ($git_success ? ' y subida a GitHub' : ''),
            'git_tag' => $git_tag,
            'git_success' => $git_success,
            'git_output' => $git_result,
            'version_updated' => true,
            'version_info' => [
                'version' => $release['version'],
                'build' => $release['build'],
                'codename' => $release['codename'],
                'backup_file' => $backup_file
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteRelease($conexion, $id) {
    try {
        // Obtener info del archivo
        $stmt = $conexion->prepare("SELECT file_path FROM updates WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $release = $result->fetch_assoc();
            
            // Eliminar archivo si existe
            if ($release['file_path'] && file_exists($release['file_path'])) {
                unlink($release['file_path']);
            }
        }
        
        // Eliminar de BD
        $stmt = $conexion->prepare("DELETE FROM updates WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Release eliminada']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function createReleaseFile($version, $type) {
    try {
        $filename = "ReySystem-v{$version}";
        $output_dir = __DIR__ . '/releases';
        
        if (!is_dir($output_dir)) {
            mkdir($output_dir, 0755, true);
        }
        
        $result = ['success' => true, 'files' => []];
        
        // Crear TAR.GZ
        if ($type === 'tar.gz' || $type === 'both') {
            $tar_file = "{$output_dir}/{$filename}.tar.gz";
            
            // Usar comando tar del sistema (más confiable)
            $exclude_dirs = '--exclude=.git --exclude=temp_updates --exclude=backups --exclude=logs --exclude=uploads --exclude=releases --exclude=node_modules --exclude=vendor';
            $command = "tar -czf {$tar_file} {$exclude_dirs} -C " . __DIR__ . " . 2>&1";
            
            exec($command, $output, $return_code);
            
            if ($return_code === 0 && file_exists($tar_file)) {
                $result['files'][] = [
                    'type' => 'tar.gz',
                    'path' => $tar_file,
                    'size' => round(filesize($tar_file) / 1024 / 1024, 2) . ' MB'
                ];
                $result['file_path'] = $tar_file;
                $result['file_size'] = round(filesize($tar_file) / 1024 / 1024, 2) . ' MB';
            } else {
                throw new Exception('Error al crear tar.gz: ' . implode("\n", $output));
            }
        }
        
        // Crear ZIP
        if ($type === 'zip' || $type === 'both') {
            $zip_file = "{$output_dir}/{$filename}.zip";
            
            if (!class_exists('ZipArchive')) {
                throw new Exception('ZipArchive no está disponible en PHP');
            }
            
            $zip = new ZipArchive();
            if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                $count = 0;
                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen(__DIR__) + 1);
                        
                        // Excluir directorios específicos
                        if (!preg_match('#^(\.git|temp_updates|backups|logs|uploads|releases|node_modules|vendor)/#', $relativePath)) {
                            $zip->addFile($filePath, $relativePath);
                            $count++;
                        }
                    }
                }
                
                $zip->close();
                
                if ($count > 0 && file_exists($zip_file)) {
                    $result['files'][] = [
                        'type' => 'zip',
                        'path' => $zip_file,
                        'size' => round(filesize($zip_file) / 1024 / 1024, 2) . ' MB',
                        'files_count' => $count
                    ];
                    
                    // Si solo es ZIP o no hay tar.gz, usar ZIP como principal
                    if ($type === 'zip') {
                        $result['file_path'] = $zip_file;
                        $result['file_size'] = round(filesize($zip_file) / 1024 / 1024, 2) . ' MB';
                    }
                } else {
                    throw new Exception('No se agregaron archivos al ZIP');
                }
            } else {
                throw new Exception('No se pudo crear el archivo ZIP');
            }
        }
        
        if (empty($result['files'])) {
            throw new Exception('Tipo de archivo no válido');
        }
        
        return $result;
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function uploadToGitHub($conexion, $id) {
    try {
        // Obtener release
        $stmt = $conexion->prepare("SELECT * FROM updates WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Release no encontrada');
        }
        
        $release = $result->fetch_assoc();
        $git_tag = "v{$release['version']}";
        $git_result = [];
        
        if (!function_exists('exec')) {
            throw new Exception('Función exec() no disponible');
        }
        
        chdir(__DIR__);
        
        // Verificar si Git está inicializado
        exec("git rev-parse --git-dir 2>&1", $git_check, $git_exists);
        
        if ($git_exists !== 0) {
            // Inicializar Git
            exec("git init 2>&1", $init_output, $init_code);
            $git_result[] = "Git init: " . implode("\n", $init_output);
            
            // Agregar todos los archivos
            exec("git add . 2>&1", $add_output, $add_code);
            exec("git commit -m 'Initial commit' 2>&1", $commit_output, $commit_code);
            $git_result[] = "Initial commit: " . implode("\n", $commit_output);
        }
        
        // Verificar remote
        exec("git remote get-url origin 2>&1", $remote_check, $remote_exists);
        
        if ($remote_exists !== 0) {
            throw new Exception('No hay remote configurado. Ejecuta: git remote add origin https://github.com/TU-USUARIO/ReySystem.git');
        }
        
        // Verificar si el tag ya existe
        exec("git tag -l {$git_tag} 2>&1", $tag_check, $tag_exists);
        
        if (empty($tag_check)) {
            // Tag no existe, crearlo
            $commit_msg = "Release {$git_tag}" . ($release['codename'] ? " - {$release['codename']}" : "");
            
            exec("git add version.json 2>&1", $output1, $code1);
            exec("git commit -m '{$commit_msg}' 2>&1", $output2, $code2);
            exec("git tag -a {$git_tag} -m '{$release['codename']}' 2>&1", $output3, $code3);
            
            $git_result[] = "Commit y tag creados";
        } else {
            $git_result[] = "Tag {$git_tag} ya existe";
        }
        
        // Push
        exec("git push origin main 2>&1", $output4, $code4);
        $git_result[] = "Push main: " . implode("\n", $output4);
        
        exec("git push origin {$git_tag} 2>&1", $output5, $code5);
        $git_result[] = "Push tag: " . implode("\n", $output5);
        
        // Crear release en GitHub con gh CLI
        exec("which gh 2>&1", $gh_check, $gh_exists);
        
        if ($gh_exists === 0) {
            // Verificar autenticación de gh
            exec("gh auth status 2>&1", $auth_check, $auth_code);
            $git_result[] = "Auth status: " . implode("\n", $auth_check);
            
            if ($auth_code !== 0) {
                throw new Exception('GitHub CLI no está autenticado. Ejecuta: gh auth login');
            }
            
            // Verificar si la release ya existe
            exec("gh release view {$git_tag} 2>&1", $release_check, $release_exists);
            
            if ($release_exists !== 0) {
                // Release no existe, crearla
                $changes = json_decode($release['changes_json'], true);
                $github_notes = "## 🎉 Novedades\n\n";
                foreach ($changes as $change) {
                    $github_notes .= "- {$change}\n";
                }
                $github_notes .= "\n## 📦 Instalación\n\nDescarga el archivo adjunto y extrae en tu servidor.";
                
                $temp_notes = tempnam(sys_get_temp_dir(), 'gh_notes_');
                file_put_contents($temp_notes, $github_notes);
                
                $title = $release['codename'] ? "{$git_tag} - {$release['codename']}" : $git_tag;
                $gh_cmd = "gh release create {$git_tag} --title " . escapeshellarg($title) . " --notes-file " . escapeshellarg($temp_notes);
                
                // Agregar archivo si existe
                if ($release['file_path'] && file_exists($release['file_path'])) {
                    $gh_cmd .= " " . escapeshellarg($release['file_path']);
                }
                
                exec($gh_cmd . " 2>&1", $output6, $code6);
                $git_result[] = "GitHub Release create: " . implode("\n", $output6);
                
                unlink($temp_notes);
                
                if ($code6 === 0) {
                    // Obtener URL
                    exec("gh release view {$git_tag} --json url -q .url 2>&1", $output7, $code7);
                    if ($code7 === 0 && !empty($output7[0])) {
                        $github_url = trim($output7[0]);
                        $stmt = $conexion->prepare("UPDATE updates SET github_release_url = ?, status = 'published', published_at = NOW() WHERE id = ?");
                        $stmt->bind_param("si", $github_url, $id);
                        $stmt->execute();
                        $git_result[] = "✅ Release URL: {$github_url}";
                    }
                } else {
                    throw new Exception('Error al crear release en GitHub: ' . implode("\n", $output6));
                }
            } else {
                // Release ya existe, actualizar archivo
                $git_result[] = "Release {$git_tag} ya existe en GitHub";
                
                if ($release['file_path'] && file_exists($release['file_path'])) {
                    exec("gh release upload {$git_tag} " . escapeshellarg($release['file_path']) . " --clobber 2>&1", $output8, $code8);
                    $git_result[] = "Upload archivo: " . implode("\n", $output8);
                    
                    if ($code8 !== 0) {
                        throw new Exception('Error al subir archivo: ' . implode("\n", $output8));
                    }
                } else {
                    $git_result[] = "⚠️ No hay archivo para subir";
                }
            }
        } else {
            throw new Exception('GitHub CLI (gh) no está instalado. Instala con: sudo apt install gh');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Subido a GitHub exitosamente',
            'git_output' => $git_result
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

$conexion->close();
?>
