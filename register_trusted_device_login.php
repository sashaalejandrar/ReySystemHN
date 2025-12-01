<?php
// Iniciar output buffering para evitar problemas con headers
ob_start();

session_start();

if (!isset($_SESSION['temp_usuario'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conexion->connect_error]);
    exit();
}

require_once 'security_keys_helper.php';

$usuario = $_SESSION['temp_usuario'];

// Verificar si la tabla existe
$result = $conexion->query("SHOW TABLES LIKE 'trusted_devices'");
if ($result->num_rows == 0) {
    // Crear la tabla si no existe
    $sql = "CREATE TABLE IF NOT EXISTS `trusted_devices` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `idUsuario` varchar(50) NOT NULL,
      `device_token` varchar(255) NOT NULL,
      `device_name` varchar(255) DEFAULT NULL,
      `device_fingerprint` varchar(255) DEFAULT NULL,
      `ip_address` varchar(45) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `last_used` timestamp NULL DEFAULT NULL,
      `expires_at` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `device_token` (`device_token`),
      KEY `idx_usuario` (`idUsuario`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if (!$conexion->query($sql)) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error al crear tabla: ' . $conexion->error]);
        exit();
    }
}

// Registrar dispositivo de confianza
try {
    $deviceToken = bin2hex(random_bytes(32));
    $deviceName = $_SERVER['HTTP_USER_AGENT'] ?? 'Dispositivo desconocido';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Generar fingerprint manualmente para evitar errores
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $fingerprint = hash('sha256', $user_agent . $accept_language . $ipAddress);
    
    $stmt = $conexion->prepare("INSERT INTO trusted_devices (idUsuario, device_token, device_name, device_fingerprint, ip_address, created_at, last_used) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    
    if (!$stmt) {
        throw new Exception('Error al preparar statement: ' . $conexion->error);
    }
    
    $stmt->bind_param("sssss", $usuario, $deviceToken, $deviceName, $fingerprint, $ipAddress);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al ejecutar: ' . $stmt->error);
    }
    
    // Guardar token en cookie (30 días) - ANTES de enviar JSON
    setcookie('trusted_device_token', $deviceToken, [
        'expires' => time() + (30 * 24 * 60 * 60),
        'path' => '/',
        'secure' => false, // Cambiar a true en producción con HTTPS
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Completar login
    $_SESSION["usuario"] = $_SESSION['temp_usuario'];
    $_SESSION["user_id"] = $_SESSION['temp_user_id'];
    $_SESSION['usuario_id'] = $_SESSION['temp_user_id'];
    $_SESSION['rol'] = $_SESSION['temp_rol'];
    $_SESSION['perfil'] = $_SESSION['temp_perfil'];
    $_SESSION['nombre'] = $_SESSION['temp_nombre'];
    
    // Limpiar sesión temporal
    unset($_SESSION['temp_usuario']);
    unset($_SESSION['temp_user_id']);
    unset($_SESSION['temp_rol']);
    unset($_SESSION['temp_perfil']);
    unset($_SESSION['temp_nombre']);
    unset($_SESSION['temp_security_check']);
    
    $stmt->close();
    $conexion->close();
    
    // Limpiar buffer y enviar JSON
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Dispositivo registrado exitosamente']);
    
} catch (Exception $e) {
    $conexion->close();
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
