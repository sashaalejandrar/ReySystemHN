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

$action = $_GET['action'] ?? '';

// Verificar autenticación (excepto para get_recent_changes y generate_ai_changelog que solo leen Git)
if (!in_array($action, ['get_recent_changes', 'generate_ai_changelog']) && !isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$usuario = $_SESSION['usuario'] ?? 'system';

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
        
    case 'get_recent_changes':
        getRecentChanges();
        break;
        
    case 'generate_ai_changelog':
        generateAIChangelog();
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
        
        // Registrar en auditoría
        $usuario_audit = $usuario ?: ($_SESSION['usuario'] ?? 'system');
        $stmt_audit = $conexion->prepare("INSERT INTO auditoria (usuario, accion, modulo, detalles, ip_address) VALUES (?, ?, ?, ?, ?)");
        $accion_audit = 'CREAR_RELEASE';
        $modulo_audit = 'Releases';
        $detalles_audit = json_encode([
            'version' => $version,
            'codename' => $codename,
            'release_type' => $release_type,
            'changes_count' => count($changes_array)
        ]);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt_audit->bind_param("sssss", $usuario_audit, $accion_audit, $modulo_audit, $detalles_audit, $ip_address);
        $stmt_audit->execute();
        
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
        logRelease("=== INICIANDO PUBLICACIÓN CON SCRIPT BASH ===");
        logRelease("Release ID: $id");
        
        // Obtener datos de la release
        $stmt = $conexion->prepare("SELECT * FROM updates WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Release no encontrada');
        }
        
        $release = $result->fetch_assoc();
        
        // Actualizar version.json ANTES de ejecutar el script
        logRelease("Actualizando version.json...");
        
        $version_content = file_get_contents('version.json');
        $version_data = json_decode($version_content, true);
        
        // Agregar al changelog
        $new_changelog_entry = [
            'version' => $release['version'],
            'date' => $release['release_date'],
            'type' => $release['release_type'],
            'changes' => json_decode($release['changes_json'], true)
        ];
        
        // Verificar que no exista ya
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
        file_put_contents('version.json', $json_output);
        logRelease("version.json actualizado a v{$release['version']}");
        
        // Verificar que el script existe
        $script_path = __DIR__ . '/publish_release_script.sh';
        if (!file_exists($script_path)) {
            throw new Exception('Script de publicación no encontrado');
        }
        
        // Hacer el script ejecutable
        chmod($script_path, 0755);
        
        // Ejecutar el script bash directamente
        logRelease("Ejecutando script: $script_path $id");
        exec("bash {$script_path} {$id} 2>&1", $output, $return_code);
        
        logRelease("Script terminó con código: $return_code");
        logRelease("Output: " . implode("\n", $output));
        
        if ($return_code === 0) {
            // Registrar en auditoría
            $usuario_audit = $usuario ?: ($_SESSION['usuario'] ?? 'system');
            $stmt_audit = $conexion->prepare("INSERT INTO auditoria (usuario, accion, modulo, detalles, ip_address) VALUES (?, ?, ?, ?, ?)");
            $accion_audit = 'PUBLICAR_RELEASE';
            $modulo_audit = 'Releases';
            $detalles_audit = "Release ID: $id publicada exitosamente";
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt_audit->bind_param("sssss", $usuario_audit, $accion_audit, $modulo_audit, $detalles_audit, $ip_address);
            $stmt_audit->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Release publicada exitosamente',
                'output' => $output
            ]);
        } else {
            throw new Exception('Error al publicar release: ' . implode("\n", $output));
        }
        
    } catch (Exception $e) {
        logRelease("ERROR: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function publishRelease_OLD($conexion, $id) {
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
                logRelease("Intentando obtener token de GitHub...");
                if (isset($_ENV['GH_TOKEN'])) {
                    $gh_token = $_ENV['GH_TOKEN'];
                    logRelease("✅ Token obtenido de \$_ENV['GH_TOKEN']");
                } elseif (isset($_ENV['GITHUB_TOKEN'])) {
                    $gh_token = $_ENV['GITHUB_TOKEN'];
                    logRelease("✅ Token obtenido de \$_ENV['GITHUB_TOKEN']");
                } elseif (getenv('GH_TOKEN')) {
                    $gh_token = getenv('GH_TOKEN');
                    logRelease("✅ Token obtenido de getenv('GH_TOKEN')");
                } elseif (getenv('GITHUB_TOKEN')) {
                    $gh_token = getenv('GITHUB_TOKEN');
                    logRelease("✅ Token obtenido de getenv('GITHUB_TOKEN')");
                } else {
                    logRelease("❌ Token no encontrado en variables de entorno");
                }
                
                // 2. Si no hay token, intentar leer directamente del .env
                if (!$gh_token && file_exists(__DIR__ . '/.env')) {
                    logRelease("Intentando leer token directamente de .env...");
                    $env_content = file_get_contents(__DIR__ . '/.env');
                    if (preg_match('/GH_TOKEN=(.+)/', $env_content, $matches)) {
                        $gh_token = trim($matches[1]);
                        logRelease("✅ Token obtenido directamente de .env");
                    } else {
                        logRelease("❌ GH_TOKEN no encontrado en .env");
                    }
                } elseif (!$gh_token) {
                    logRelease("❌ Archivo .env no existe");
                }
                
                // 3. Como último recurso, intentar gh auth token
                if (!$gh_token) {
                    logRelease("Intentando obtener token con gh auth token...");
                    exec("gh auth token 2>&1", $token_output, $token_code);
                    if ($token_code === 0 && !empty($token_output[0])) {
                        $gh_token = trim($token_output[0]);
                        logRelease("✅ Token obtenido de gh auth token");
                    } else {
                        logRelease("❌ gh auth token falló (code: $token_code): " . implode(", ", $token_output));
                    }
                }
                
                // Si aún no hay token, error
                if (!$gh_token) {
                    logRelease("ERROR: No se pudo obtener token de GitHub de ninguna fuente");
                    throw new Exception('No se pudo obtener token de GitHub. Opciones: 1) Agrega GH_TOKEN en .env, 2) Ejecuta "gh auth login" como usuario del servidor web, 3) Verifica permisos del token');
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
                // Crear directorio temporal para config de gh
                $gh_config_dir = sys_get_temp_dir() . '/gh_config_' . uniqid();
                mkdir($gh_config_dir, 0755, true);
                
                $env_vars = [
                    "GH_TOKEN={$gh_token}",
                    "GITHUB_TOKEN={$gh_token}",
                    "GH_HOST=github.com",
                    "GH_CONFIG_DIR={$gh_config_dir}",
                    "HOME={$gh_config_dir}"  // Evitar acceso a /root
                ];
                $env_string = implode(' ', $env_vars);
                logRelease("Variables de entorno configuradas (GH_CONFIG_DIR: $gh_config_dir)");
                
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
                
                // Limpiar archivos temporales
                unlink($temp_notes);
                if (is_dir($gh_config_dir)) {
                    exec("rm -rf " . escapeshellarg($gh_config_dir));
                }
                
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
        // Obtener info completa del release antes de eliminar
        $stmt = $conexion->prepare("SELECT version, codename, file_path FROM updates WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $release = $result->fetch_assoc();
            
            // Registrar en auditoría ANTES de eliminar
            $usuario_audit = $usuario ?: ($_SESSION['usuario'] ?? 'system');
            $stmt_audit = $conexion->prepare("INSERT INTO auditoria (usuario, accion, modulo, detalles, ip_address) VALUES (?, ?, ?, ?, ?)");
            $accion_audit = 'ELIMINAR_RELEASE';
            $modulo_audit = 'Releases';
            $detalles_audit = json_encode([
                'version' => $release['version'],
                'codename' => $release['codename'],
                'release_id' => $id
            ]);
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $stmt_audit->bind_param("sssss", $usuario_audit, $accion_audit, $modulo_audit, $detalles_audit, $ip_address);
            $stmt_audit->execute();
            
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
            // Verificar autenticación de gh (intentar obtener token)
            exec("gh auth status 2>&1", $auth_check, $auth_code);
            $git_result[] = "Auth status check: " . ($auth_code === 0 ? "OK" : "Warning (code: $auth_code)");
            
            // Intentar obtener token para verificar autenticación real
            exec("gh auth token 2>&1", $token_check, $token_code);
            
            if ($token_code !== 0 || empty($token_check[0])) {
                // Si no podemos obtener token, verificar si al menos podemos listar repos
                exec("gh repo view 2>&1", $repo_check, $repo_code);
                if ($repo_code !== 0) {
                    throw new Exception('GitHub CLI no está autenticado correctamente. Ejecuta: gh auth login');
                }
                $git_result[] = "Auth verified via repo access";
            } else {
                $git_result[] = "Auth verified via token";
            }
            
            // Obtener token de GitHub
            $gh_token = null;
            if (isset($_ENV['GH_TOKEN'])) {
                $gh_token = $_ENV['GH_TOKEN'];
            } elseif (file_exists(__DIR__ . '/.env')) {
                $env_content = file_get_contents(__DIR__ . '/.env');
                if (preg_match('/GH_TOKEN=(.+)/', $env_content, $matches)) {
                    $gh_token = trim($matches[1]);
                }
            }
            
            if (!$gh_token) {
                exec("gh auth token 2>&1", $token_output, $token_code);
                if ($token_code === 0 && !empty($token_output[0])) {
                    $gh_token = trim($token_output[0]);
                }
            }
            
            // Configurar variables de entorno para gh
            $gh_config_dir = sys_get_temp_dir() . '/gh_config_' . uniqid();
            mkdir($gh_config_dir, 0755, true);
            
            $env_vars = [
                "GH_CONFIG_DIR={$gh_config_dir}",
                "HOME={$gh_config_dir}"
            ];
            
            if ($gh_token) {
                $env_vars[] = "GH_TOKEN={$gh_token}";
                $env_vars[] = "GITHUB_TOKEN={$gh_token}";
            }
            
            $env_string = implode(' ', $env_vars);
            
            // Verificar si la release ya existe
            exec("{$env_string} gh release view {$git_tag} 2>&1", $release_check, $release_exists);
            
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
                $gh_cmd = "{$env_string} gh release create {$git_tag} --title " . escapeshellarg($title) . " --notes-file " . escapeshellarg($temp_notes);
                
                // Agregar archivo si existe
                if ($release['file_path'] && file_exists($release['file_path'])) {
                    $gh_cmd .= " " . escapeshellarg($release['file_path']);
                }
                
                exec($gh_cmd . " 2>&1", $output6, $code6);
                $git_result[] = "GitHub Release create: " . implode("\n", $output6);
                
                unlink($temp_notes);
                
                // Limpiar directorio temporal
                if (is_dir($gh_config_dir)) {
                    exec("rm -rf " . escapeshellarg($gh_config_dir));
                }
                
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

function getRecentChanges() {
    try {
        chdir(__DIR__);
        
        // Obtener la última versión de la BD
        global $conexion;
        $result = $conexion->query("SELECT version FROM updates WHERE status = 'published' ORDER BY created_at DESC LIMIT 1");
        $last_version = null;
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_version = "v" . $row['version'];
        }
        
        // Obtener commits desde la última versión o los últimos 20
        $git_command = $last_version 
            ? "git log {$last_version}..HEAD --pretty=format:'%s' --no-merges 2>&1"
            : "git log -20 --pretty=format:'%s' --no-merges 2>&1";
        
        exec($git_command, $output, $return_code);
        
        if ($return_code !== 0 || empty($output)) {
            // Si falla git log, intentar obtener archivos modificados recientemente
            exec("git diff --name-only HEAD~10 HEAD 2>&1", $files_output, $files_code);
            
            if ($files_code === 0 && !empty($files_output)) {
                $changes = [];
                $file_groups = [];
                
                // Agrupar archivos por tipo
                foreach ($files_output as $file) {
                    if (preg_match('/\.php$/', $file)) {
                        $file_groups['php'][] = basename($file, '.php');
                    } elseif (preg_match('/\.js$/', $file)) {
                        $file_groups['js'][] = basename($file, '.js');
                    } elseif (preg_match('/\.css$/', $file)) {
                        $file_groups['css'][] = basename($file, '.css');
                    }
                }
                
                // Generar mensajes basados en archivos modificados
                if (!empty($file_groups['php'])) {
                    $changes[] = "Mejoras en módulos: " . implode(', ', array_slice($file_groups['php'], 0, 3));
                }
                if (!empty($file_groups['js'])) {
                    $changes[] = "Actualizaciones en interfaz de usuario";
                }
                if (!empty($file_groups['css'])) {
                    $changes[] = "Mejoras visuales y de diseño";
                }
                
                if (!empty($changes)) {
                    echo json_encode([
                        'success' => true,
                        'changes' => $changes,
                        'source' => 'files',
                        'last_version' => $last_version
                    ]);
                    return;
                }
            }
            
            // Si todo falla o no hay cambios, dar sugerencias genéricas útiles
            echo json_encode([
                'success' => true,
                'changes' => [
                    'Correcciones de errores y mejoras de estabilidad',
                    'Optimizaciones de rendimiento del sistema',
                    'Mejoras en la experiencia de usuario',
                    'Actualizaciones de seguridad',
                    'Corrección de bugs menores'
                ],
                'source' => 'default',
                'last_version' => $last_version,
                'note' => 'No se detectaron commits nuevos. Edita estos cambios según corresponda.'
            ]);
            return;
        }
        
        // Procesar commits y limpiar mensajes
        $changes = [];
        foreach ($output as $commit_msg) {
            $commit_msg = trim($commit_msg);
            
            // Ignorar commits de merge, release, etc.
            if (empty($commit_msg) || 
                stripos($commit_msg, 'merge') === 0 ||
                stripos($commit_msg, 'release') === 0 ||
                stripos($commit_msg, 'version') === 0) {
                continue;
            }
            
            // Limpiar y formatear mensaje
            $commit_msg = ucfirst($commit_msg);
            if (!preg_match('/[.!?]$/', $commit_msg)) {
                $commit_msg .= '';
            }
            
            $changes[] = $commit_msg;
            
            // Limitar a 15 cambios
            if (count($changes) >= 15) {
                break;
            }
        }
        
        // Si no hay cambios, dar mensaje por defecto
        if (empty($changes)) {
            $changes = [
                'Correcciones de errores y mejoras de estabilidad',
                'Optimizaciones de rendimiento'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'changes' => $changes,
            'source' => 'git',
            'last_version' => $last_version,
            'total_commits' => count($output)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'changes' => [
                'Correcciones de errores y mejoras de estabilidad',
                'Optimizaciones de rendimiento'
            ]
        ]);
    }
}

function generateAIChangelog() {
    try {
        chdir(__DIR__);
        
        // Obtener la última versión
        global $conexion;
        $result = $conexion->query("SELECT version FROM updates WHERE status = 'published' ORDER BY created_at DESC LIMIT 1");
        $last_version = null;
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $last_version = "v" . $row['version'];
        }
        
        // Obtener commits recientes
        $git_command = $last_version 
            ? "git log {$last_version}..HEAD --pretty=format:'%s' --no-merges 2>&1"
            : "git log -15 --pretty=format:'%s' --no-merges 2>&1";
        
        exec($git_command, $commits, $return_code);
        
        // Obtener archivos modificados
        exec("git diff --name-status HEAD~10 HEAD 2>&1", $files_output, $files_code);
        
        // Preparar contexto para la IA
        $context = "Commits recientes:\n";
        if (!empty($commits)) {
            $context .= implode("\n", array_slice($commits, 0, 10));
        } else {
            $context .= "No hay commits nuevos";
        }
        
        $context .= "\n\nArchivos modificados:\n";
        if (!empty($files_output)) {
            $context .= implode("\n", array_slice($files_output, 0, 15));
        } else {
            $context .= "No se detectaron cambios";
        }
        
        // Leer API key de Groq
        $groq_api_key = getenv('GROQ_API_KEY') ?: $_ENV['GROQ_API_KEY'] ?? null;
        
        if (!$groq_api_key || $groq_api_key === 'YOUR_GROQ_API_KEY_HERE') {
            throw new Exception('API key de Groq no configurada');
        }
        
        // Llamar a Groq API
        $prompt = "Eres un experto en redactar notas de lanzamiento (changelog) para software en ESPAÑOL. Basándote en los siguientes cambios de Git, genera una lista de 5-8 cambios para un changelog de release. 

IMPORTANTE:
- RESPONDE SIEMPRE EN ESPAÑOL con acentos y caracteres UTF-8 correctos (á, é, í, ó, ú, ñ)
- Sé creativo y varía los mensajes (no uses siempre las mismas frases)
- Usa lenguaje profesional pero amigable en español
- Enfócate en beneficios para el usuario, no en detalles técnicos
- Cada cambio debe ser una línea corta y clara
- NO uses viñetas, numeración ni emojis
- Responde SOLO con la lista de cambios, uno por línea
- Usa palabras como: mejorado, optimizado, actualizado, corregido, implementado

Contexto de cambios:
$context

Genera el changelog en ESPAÑOL:";

        $data = [
            'model' => 'llama-3.3-70b-versatile',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Eres un asistente que SIEMPRE responde en español con codificación UTF-8 correcta, usando acentos y la letra ñ cuando sea necesario.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.8,
            'max_tokens' => 500
        ];
        
        $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json; charset=utf-8',
            'Accept: application/json; charset=utf-8',
            'Authorization: Bearer ' . $groq_api_key
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception('Error al llamar a Groq API: ' . $http_code);
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['choices'][0]['message']['content'])) {
            throw new Exception('Respuesta inválida de Groq');
        }
        
        // Procesar respuesta de la IA
        $ai_text = trim($result['choices'][0]['message']['content']);
        $changes = array_filter(array_map('trim', explode("\n", $ai_text)));
        
        // Limpiar cambios (remover numeración, viñetas, etc.)
        $cleaned_changes = [];
        foreach ($changes as $change) {
            // Remover numeración, viñetas, guiones
            $change = preg_replace('/^[\d\-\*\•\→]+[\.\):\s]*/', '', $change);
            $change = trim($change);
            
            if (!empty($change) && strlen($change) > 10) {
                $cleaned_changes[] = $change;
            }
        }
        
        if (empty($cleaned_changes)) {
            throw new Exception('No se pudieron generar cambios');
        }
        
        echo json_encode([
            'success' => true,
            'changes' => array_slice($cleaned_changes, 0, 8),
            'source' => 'ai',
            'model' => 'Groq Llama 3.3 70B',
            'last_version' => $last_version
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage(),
            'changes' => [
                'Mejoras significativas en rendimiento y estabilidad',
                'Nuevas funcionalidades para mejorar la experiencia del usuario',
                'Corrección de errores reportados',
                'Optimizaciones en la interfaz de usuario',
                'Actualizaciones de seguridad importantes'
            ]
        ]);
    }
}

$conexion->close();
?>
