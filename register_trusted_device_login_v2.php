<?php
// Versión simplificada y robusta
error_reporting(E_ALL);
ini_set('display_errors', 1); // TEMPORAL: Mostrar errores para debugging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/register_device_errors.log');

// Función para enviar respuesta JSON
function sendJSON($success, $message) {
    // Limpiar cualquier output previo
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

try {
    session_start();

    // Verificar sesión
    if (!isset($_SESSION['temp_usuario'])) {
        sendJSON(false, 'Sesión no válida');
    }
} catch (Exception $e) {
    sendJSON(false, 'Error en sesión: ' . $e->getMessage());
}

try {
    // Conectar a BD
    $conexion = @new mysqli("localhost", "root", "", "tiendasrey");
    if ($conexion->connect_error) {
        sendJSON(false, 'Error de conexión: ' . $conexion->connect_error);
    }

    $usuario = $_SESSION['temp_usuario'];
    $user_id = $_SESSION['temp_user_id'] ?? null;
    
    if (!$user_id) {
        sendJSON(false, 'ID de usuario no encontrado en sesión');
    }
} catch (Exception $e) {
    sendJSON(false, 'Error de conexión: ' . $e->getMessage());
}

// Verificar/Crear tabla
$result = @$conexion->query("SHOW TABLES LIKE 'trusted_devices'");
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
    
    if (!@$conexion->query($sql)) {
        sendJSON(false, 'Error al crear tabla de dispositivos');
    }
}

// Generar datos del dispositivo
$deviceToken = bin2hex(random_bytes(32));
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Dispositivo desconocido';
$deviceName = substr($user_agent, 0, 250);
$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$accept_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
$fingerprint = hash('sha256', $user_agent . $accept_lang . $ipAddress);

// Extraer info del navegador
$browser = 'Unknown';
$os = 'Unknown';

// Detectar navegador
if (strpos($user_agent, 'Firefox') !== false) $browser = 'Firefox';
elseif (strpos($user_agent, 'Chrome') !== false) $browser = 'Chrome';
elseif (strpos($user_agent, 'Safari') !== false) $browser = 'Safari';
elseif (strpos($user_agent, 'Edge') !== false) $browser = 'Edge';

// Detectar OS
if (strpos($user_agent, 'Windows') !== false) $os = 'Windows';
elseif (strpos($user_agent, 'Mac') !== false) $os = 'macOS';
elseif (strpos($user_agent, 'Linux') !== false) $os = 'Linux';
elseif (strpos($user_agent, 'Android') !== false) $os = 'Android';
elseif (strpos($user_agent, 'iOS') !== false) $os = 'iOS';

// Calcular fecha de expiración (30 días)
$expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

// Insertar dispositivo
$stmt = @$conexion->prepare("INSERT INTO trusted_devices (user_id, device_token, device_name, device_fingerprint, browser, os, ip_address, created_at, last_used, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)");

if (!$stmt) {
    sendJSON(false, 'Error al preparar consulta');
}

$stmt->bind_param("isssssss", $user_id, $deviceToken, $deviceName, $fingerprint, $browser, $os, $ipAddress, $expires_at);

if (!$stmt->execute()) {
    $stmt->close();
    $conexion->close();
    sendJSON(false, 'Error al registrar dispositivo');
}

// Guardar cookie
@setcookie('trusted_device_token', $deviceToken, [
    'expires' => time() + (30 * 24 * 60 * 60),
    'path' => '/',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Completar login
$_SESSION["usuario"] = $_SESSION['temp_usuario'];
$_SESSION["user_id"] = $_SESSION['temp_user_id'];
$_SESSION['usuario_id'] = $_SESSION['temp_user_id'];
$_SESSION['rol'] = $_SESSION['temp_rol'] ?? 'usuario';
$_SESSION['perfil'] = $_SESSION['temp_perfil'] ?? 'default';
$_SESSION['nombre'] = $_SESSION['temp_nombre'] ?? $_SESSION['temp_usuario'];

// Limpiar sesión temporal
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
    if (isset($conexion)) {
        $conexion->close();
    }
    sendJSON(false, 'Error general: ' . $e->getMessage());
}
?>
