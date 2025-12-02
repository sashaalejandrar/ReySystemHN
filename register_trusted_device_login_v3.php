<?php
// Versión 3 - Ultra robusta con logging completo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Función para enviar respuesta JSON
function sendJSON($success, $message, $debug = null) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json');
    $response = ['success' => $success, 'message' => $message];
    if ($debug) $response['debug'] = $debug;
    echo json_encode($response);
    exit();
}

try {
    session_start();
    
    // 1. Verificar sesión
    if (!isset($_SESSION['temp_usuario'])) {
        sendJSON(false, 'Sesión no válida', ['session' => array_keys($_SESSION)]);
    }
    
    $usuario = $_SESSION['temp_usuario'];
    $user_id = $_SESSION['temp_user_id'] ?? null;
    
    if (!$user_id) {
        sendJSON(false, 'ID de usuario no encontrado', ['temp_user_id' => $user_id]);
    }
    
    // 2. Conectar a BD
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    if ($conexion->connect_error) {
        sendJSON(false, 'Error de conexión BD', ['error' => $conexion->connect_error]);
    }
    
    // 3. Verificar/Crear tabla
    $result = $conexion->query("SHOW TABLES LIKE 'trusted_devices'");
    if (!$result || $result->num_rows == 0) {
        $sql = "CREATE TABLE `trusted_devices` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `device_token` varchar(255) NOT NULL,
          `device_name` varchar(255) DEFAULT NULL,
          `device_fingerprint` varchar(255) DEFAULT NULL,
          `browser` varchar(100) DEFAULT NULL,
          `os` varchar(100) DEFAULT NULL,
          `ip_address` varchar(45) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `last_used` timestamp NULL DEFAULT NULL,
          `expires_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `device_token` (`device_token`),
          KEY `idx_user` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (!$conexion->query($sql)) {
            sendJSON(false, 'Error al crear tabla', ['sql_error' => $conexion->error]);
        }
    }
    
    // 4. Generar datos del dispositivo
    $deviceToken = bin2hex(random_bytes(32));
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $deviceName = substr($user_agent, 0, 250);
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $accept_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $fingerprint = hash('sha256', $user_agent . $accept_lang . $ipAddress);
    
    // 5. Detectar navegador
    $browser = 'Unknown';
    if (stripos($user_agent, 'Firefox') !== false) $browser = 'Firefox';
    elseif (stripos($user_agent, 'Chrome') !== false) $browser = 'Chrome';
    elseif (stripos($user_agent, 'Safari') !== false) $browser = 'Safari';
    elseif (stripos($user_agent, 'Edge') !== false) $browser = 'Edge';
    
    // 6. Detectar OS
    $os = 'Unknown';
    if (stripos($user_agent, 'Windows') !== false) $os = 'Windows';
    elseif (stripos($user_agent, 'Mac') !== false) $os = 'macOS';
    elseif (stripos($user_agent, 'Linux') !== false) $os = 'Linux';
    elseif (stripos($user_agent, 'Android') !== false) $os = 'Android';
    elseif (stripos($user_agent, 'iOS') !== false) $os = 'iOS';
    
    // 7. Fecha de expiración
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // 8. Insertar dispositivo
    $stmt = $conexion->prepare("INSERT INTO trusted_devices (user_id, device_token, device_name, device_fingerprint, browser, os, ip_address, created_at, last_used, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)");
    
    if (!$stmt) {
        sendJSON(false, 'Error al preparar statement', ['error' => $conexion->error]);
    }
    
    $stmt->bind_param("isssssss", $user_id, $deviceToken, $deviceName, $fingerprint, $browser, $os, $ipAddress, $expires_at);
    
    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        $conexion->close();
        sendJSON(false, 'Error al insertar', ['error' => $error]);
    }
    
    // 9. Guardar cookie
    setcookie('trusted_device_token', $deviceToken, [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // 10. Completar login
    $_SESSION["usuario"] = $_SESSION['temp_usuario'];
    $_SESSION["user_id"] = $_SESSION['temp_user_id'];
    $_SESSION['usuario_id'] = $_SESSION['temp_user_id'];
    $_SESSION['rol'] = $_SESSION['temp_rol'] ?? 'usuario';
    $_SESSION['perfil'] = $_SESSION['temp_perfil'] ?? 'default';
    $_SESSION['nombre'] = $_SESSION['temp_nombre'] ?? $_SESSION['temp_usuario'];
    
    // 11. Limpiar sesión temporal
    unset($_SESSION['temp_usuario']);
    unset($_SESSION['temp_user_id']);
    unset($_SESSION['temp_rol']);
    unset($_SESSION['temp_perfil']);
    unset($_SESSION['temp_nombre']);
    unset($_SESSION['temp_security_check']);
    
    $stmt->close();
    $conexion->close();
    
    sendJSON(true, 'Dispositivo registrado exitosamente');
    
} catch (Exception $e) {
    sendJSON(false, 'Excepción capturada', [
        'exception' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
} catch (Error $e) {
    sendJSON(false, 'Error fatal', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
