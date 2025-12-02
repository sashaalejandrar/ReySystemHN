<?php
session_start();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'funciones.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Funciones auxiliares
function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return "hace " . $diff . " segundos";
    } elseif ($diff < 3600) {
        return "hace " . floor($diff / 60) . " minutos";
    } elseif ($diff < 86400) {
        return "hace " . floor($diff / 3600) . " horas";
    } else {
        return "hace " . floor($diff / 86400) . " días";
    }
}

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

function registrarAuditoria($conexion, $usuario, $accion, $modulo, $detalles = null) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = $conexion->prepare("INSERT INTO audit_log (idUsuario, accion, modulo, detalles, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $usuario, $accion, $modulo, $detalles, $ip, $user_agent);
    $stmt->execute();
    $stmt->close();
}

function generarAPIKey() {
    return bin2hex(random_bytes(32));
}


function crearTablasConfiguracion($conexion) {
    $sql_notificaciones = "CREATE TABLE IF NOT EXISTS `configuracion_notificaciones` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `idUsuario` varchar(50) NOT NULL,
      `email_ventas` tinyint(1) NOT NULL DEFAULT 1,
      `email_deudas` tinyint(1) NOT NULL DEFAULT 1,
      `email_productos` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      UNIQUE KEY `idUsuario` (`idUsuario`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conexion->query($sql_notificaciones);
    
    $sql_app = "CREATE TABLE IF NOT EXISTS `configuracion_app` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `nombre_empresa` varchar(255) NOT NULL DEFAULT 'Tiendas Rey',
      `direccion_empresa` text DEFAULT NULL,
      `telefono_empresa` varchar(50) DEFAULT NULL,
      `email_empresa` varchar(255) DEFAULT NULL,
      `impuesto` decimal(5,2) NOT NULL DEFAULT 15.00,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conexion->query($sql_app);
    
    $sql_sesiones = "CREATE TABLE IF NOT EXISTS `sesiones_activas` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `idUsuario` varchar(50) NOT NULL,
      `session_id` varchar(255) NOT NULL,
      `ip_address` varchar(45) NOT NULL,
      `user_agent` text NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `is_current` tinyint(1) NOT NULL DEFAULT 0,
      PRIMARY KEY (`Id`),
      KEY `idUsuario` (`idUsuario`),
      KEY `session_id` (`session_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conexion->query($sql_sesiones);
    
    $sql_backups = "CREATE TABLE IF NOT EXISTS `backups` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `filename` varchar(255) NOT NULL,
      `filepath` varchar(255) NOT NULL,
      `size` int(11) NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `created_by` varchar(50) NOT NULL,
      PRIMARY KEY (`Id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conexion->query($sql_backups);
    
    $sql_tema = "CREATE TABLE IF NOT EXISTS `configuracion_tema` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `idUsuario` varchar(50) NOT NULL,
      `tema` enum('light','dark','auto') NOT NULL DEFAULT 'dark',
      `color_primario` varchar(7) NOT NULL DEFAULT '#1152d4',
      `tamano_fuente` enum('small','medium','large') NOT NULL DEFAULT 'medium',
      `alto_contraste` tinyint(1) NOT NULL DEFAULT 0,
      `densidad_interfaz` enum('compact','normal','spacious') NOT NULL DEFAULT 'normal',
      `idioma` varchar(5) NOT NULL DEFAULT 'es',
      `formato_fecha` varchar(20) NOT NULL DEFAULT 'd/m/Y',
      `formato_hora` varchar(20) NOT NULL DEFAULT 'H:i',
      `formato_moneda` varchar(10) NOT NULL DEFAULT 'L.',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      UNIQUE KEY `idUsuario` (`idUsuario`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conexion->query($sql_tema);
    
    $sql_2fa = "CREATE TABLE IF NOT EXISTS `autenticacion_2fa` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `idUsuario` varchar(50) NOT NULL,
      `secret` varchar(255) NOT NULL,
      `backup_codes` text DEFAULT NULL,
      `enabled` tinyint(1) NOT NULL DEFAULT 0,
      `verified_at` timestamp NULL DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      UNIQUE KEY `idUsuario` (`idUsuario`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conexion->query($sql_2fa);
    
    $sql_api_keys = "CREATE TABLE IF NOT EXISTS `api_keys` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `idUsuario` varchar(50) NOT NULL,
      `api_key` varchar(64) NOT NULL,
      `nombre` varchar(100) NOT NULL,
      `permisos` text NOT NULL,
      `last_used` timestamp NULL DEFAULT NULL,
      `expires_at` timestamp NULL DEFAULT NULL,
      `enabled` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      UNIQUE KEY `api_key` (`api_key`),
      KEY `idUsuario` (`idUsuario`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conexion->query($sql_api_keys);
    
    $sql_audit = "CREATE TABLE IF NOT EXISTS `audit_log` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `idUsuario` varchar(50) NOT NULL,
      `accion` varchar(100) NOT NULL,
      `modulo` varchar(50) NOT NULL,
      `detalles` text DEFAULT NULL,
      `ip_address` varchar(45) NOT NULL,
      `user_agent` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      KEY `idUsuario` (`idUsuario`),
      KEY `accion` (`accion`),
      KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conexion->query($sql_audit);
    
    $sql_privacidad = "CREATE TABLE IF NOT EXISTS `configuracion_privacidad` (
      `Id` int(11) NOT NULL AUTO_INCREMENT,
      `idUsuario` varchar(50) NOT NULL,
      `compartir_datos_analitica` tinyint(1) NOT NULL DEFAULT 1,
      `retencion_logs_dias` int(11) NOT NULL DEFAULT 90,
      `permitir_cookies_terceros` tinyint(1) NOT NULL DEFAULT 0,
      `mostrar_en_directorio` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`Id`),
      UNIQUE KEY `idUsuario` (`idUsuario`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conexion->query($sql_privacidad);
    
    $conexion->query("INSERT IGNORE INTO `configuracion_app` (`Id`, `nombre_empresa`, `impuesto`) VALUES (1, 'Tiendas Rey', 15.00)");
}

crearTablasConfiguracion($conexion);

// Procesadores de formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombreCompleto = $_POST['nombreCompleto'];
    $email = $_POST['email'];
    $celular = $_POST['celular'];
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['mensaje_error'] = "El correo electrónico no es válido.";
        header("Location: configuracion.php");
        exit;
    }
    
    $stmt = $conexion->prepare("UPDATE usuarios SET Nombre = ?, Apellido = ?, Email = ?, Celular = ? WHERE usuario = ?");
    $partes = explode(" ", $nombreCompleto);
    $nombre = $partes[0];
    $apellido = isset($partes[1]) ? implode(" ", array_slice($partes, 1)) : "";
    
    $stmt->bind_param("sssss", $nombre, $apellido, $email, $celular, $_SESSION['usuario']);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = "Tu perfil ha sido actualizado correctamente.";
    } else {
        $_SESSION['mensaje_error'] = "Error al actualizar el perfil: " . $stmt->error;
    }
    
    $stmt->close();
    header("Location: configuracion.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_contrasena'])) {
    $actual = $_POST['actual'];
    $nueva = $_POST['nueva'];
    $confirmar = $_POST['confirmar'];
    
    if ($nueva !== $confirmar) {
        $_SESSION['mensaje_error'] = "Las nuevas contraseñas no coinciden.";
        header("Location: configuracion.php");
        exit;
    }
    
    if (strlen($nueva) < 8) {
        $_SESSION['mensaje_error'] = "La nueva contraseña debe tener al menos 8 caracteres.";
        header("Location: configuracion.php");
        exit;
    }
    
    $stmt = $conexion->prepare("SELECT Password FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $_SESSION['usuario']);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();
        if (hash('sha256', $actual) === $row['Password']) {
            $nueva_hash = hash('sha256', $nueva);
            $stmt_update = $conexion->prepare("UPDATE usuarios SET Password = ? WHERE usuario = ?");
            $stmt_update->bind_param("ss", $nueva_hash, $_SESSION['usuario']);
            
            if ($stmt_update->execute()) {
                $_SESSION['mensaje_exito'] = "Tu contraseña ha sido actualizada correctamente.";
            } else {
                $_SESSION['mensaje_error'] = "Error al actualizar la contraseña: " . $stmt_update->error;
            }
            
            $stmt_update->close();
        } else {
            $_SESSION['mensaje_error'] = "La contraseña actual es incorrecta.";
        }
    }
    
    $stmt->close();
    header("Location: configuracion.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_notificaciones'])) {
    $email_ventas = isset($_POST['email_ventas']) ? 1 : 0;
    $email_deudas = isset($_POST['email_deudas']) ? 1 : 0;
    $email_productos = isset($_POST['email_productos']) ? 1 : 0;
    
    $stmt_check = $conexion->prepare("SELECT Id FROM configuracion_notificaciones WHERE idUsuario = ?");
    $stmt_check->bind_param("s", $_SESSION['usuario']);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();
    
    if ($resultado->num_rows > 0) {
        $stmt_update = $conexion->prepare("UPDATE configuracion_notificaciones SET email_ventas = ?, email_deudas = ?, email_productos = ? WHERE idUsuario = ?");
        $stmt_update->bind_param("iiis", $email_ventas, $email_deudas, $email_productos, $_SESSION['usuario']);
        $stmt_update->execute();
        $stmt_update->close();
    } else {
        $stmt_insert = $conexion->prepare("INSERT INTO configuracion_notificaciones (idUsuario, email_ventas, email_deudas, email_productos) VALUES (?, ?, ?, ?)");
        $stmt_insert->bind_param("siii", $_SESSION['usuario'], $email_ventas, $email_deudas, $email_productos);
        $stmt_insert->execute();
        $stmt_insert->close();
    }
    
    $stmt_check->close();
    $_SESSION['mensaje_exito'] = "Tu configuración de notificaciones ha sido actualizada.";
    header("Location: configuracion.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_config_app'])) {
    $nombre_empresa = $_POST['nombre_empresa'];
    $direccion_empresa = $_POST['direccion_empresa'];
    $telefono_empresa = $_POST['telefono_empresa'];
    $email_empresa = $_POST['email_empresa'];
    $impuesto = $_POST['impuesto'];
    
    $stmt_check = $conexion->prepare("SELECT Id FROM configuracion_app WHERE Id = 1");
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();
    
    if ($resultado->num_rows > 0) {
        $stmt = $conexion->prepare("UPDATE configuracion_app SET nombre_empresa = ?, direccion_empresa = ?, telefono_empresa = ?, email_empresa = ?, impuesto = ? WHERE Id = 1");
        $stmt->bind_param("ssssd", $nombre_empresa, $direccion_empresa, $telefono_empresa, $email_empresa, $impuesto);
    } else {
        $stmt = $conexion->prepare("INSERT INTO configuracion_app (nombre_empresa, direccion_empresa, telefono_empresa, email_empresa, impuesto) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssd", $nombre_empresa, $direccion_empresa, $telefono_empresa, $email_empresa, $impuesto);
    }
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = "La configuración de la aplicación ha sido actualizada correctamente.";
        registrarAuditoria($conexion, $_SESSION['usuario'], 'actualizar_config_app', 'aplicacion', 'Configuración actualizada');
    } else {
        $_SESSION['mensaje_error'] = "Error al actualizar la configuración: " . $stmt->error;
    }
    
    $stmt->close();
    $stmt_check->close();
    header("Location: configuracion.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_tema'])) {
    $tema = $_POST['tema'] ?? 'dark';
    $color_primario = $_POST['color_primario'] ?? '#1152d4';
    $tamano_fuente_raw = $_POST['tamano_fuente'] ?? 'medium';
    
    // Validate tamano_fuente - only allow enum values
    $tamano_fuente = in_array($tamano_fuente_raw, ['small', 'medium', 'large']) ? $tamano_fuente_raw : 'medium';
    
    $alto_contraste = isset($_POST['alto_contraste']) ? 1 : 0;
    $densidad_interfaz = $_POST['densidad_interfaz'] ?? 'normal';
    $idioma = $_POST['idioma'] ?? 'es';
    $formato_fecha = $_POST['formato_fecha'] ?? 'd/m/Y';
    $formato_hora = $_POST['formato_hora'] ?? 'H:i';
    $formato_moneda = $_POST['formato_moneda'] ?? 'L.';
    
    $stmt_check = $conexion->prepare("SELECT Id FROM configuracion_tema WHERE idUsuario = ?");
    $stmt_check->bind_param("s", $_SESSION['usuario']);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();
    
    // Validate all ENUM fields
    $tema = in_array($tema, ['light', 'dark', 'auto']) ? $tema : 'dark';
    $densidad_interfaz = in_array($densidad_interfaz, ['compact', 'normal', 'spacious']) ? $densidad_interfaz : 'normal';
    
    // Debug log
    error_log("Tema values - tema: $tema, tamano_fuente: $tamano_fuente, densidad: $densidad_interfaz");
    
    if ($resultado->num_rows > 0) {
        $stmt = $conexion->prepare("UPDATE configuracion_tema SET tema = ?, color_primario = ?, tamano_fuente = ?, alto_contraste = ?, densidad_interfaz = ?, idioma = ?, formato_fecha = ?, formato_hora = ?, formato_moneda = ? WHERE idUsuario = ?");
        $stmt->bind_param("sssississs", $tema, $color_primario, $tamano_fuente, $alto_contraste, $densidad_interfaz, $idioma, $formato_fecha, $formato_hora, $formato_moneda, $_SESSION['usuario']);
    } else {
        $stmt = $conexion->prepare("INSERT INTO configuracion_tema (idUsuario, tema, color_primario, tamano_fuente, alto_contraste, densidad_interfaz, idioma, formato_fecha, formato_hora, formato_moneda) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssississs", $_SESSION['usuario'], $tema, $color_primario, $tamano_fuente, $alto_contraste, $densidad_interfaz, $idioma, $formato_fecha, $formato_hora, $formato_moneda);
    }
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = "Configuración de tema actualizada correctamente.";
        registrarAuditoria($conexion, $_SESSION['usuario'], 'actualizar_tema', 'personalizacion', "Tema: $tema");
    } else {
        $_SESSION['mensaje_error'] = "Error al actualizar la configuración de tema.";
    }
    
    $stmt->close();
    $stmt_check->close();
    header("Location: configuracion.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['habilitar_2fa'])) {
    require_once '2fa_helper.php';
    
    $secret = generate2FASecret(16);
    $backup_codes = json_encode(generarCodigosRespaldo());
    
    $stmt_check = $conexion->prepare("SELECT Id FROM autenticacion_2fa WHERE idUsuario = ?");
    $stmt_check->bind_param("s", $_SESSION['usuario']);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();
    
    if ($resultado->num_rows > 0) {
        $stmt = $conexion->prepare("UPDATE autenticacion_2fa SET secret = ?, backup_codes = ?, enabled = 1 WHERE idUsuario = ?");
        $stmt->bind_param("sss", $secret, $backup_codes, $_SESSION['usuario']);
    } else {
        $stmt = $conexion->prepare("INSERT INTO autenticacion_2fa (idUsuario, secret, backup_codes, enabled) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $_SESSION['usuario'], $secret, $backup_codes);
    }
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = "Autenticación de dos factores habilitada. Escanea el código QR con Google Authenticator.";
        $_SESSION['backup_codes_2fa'] = json_decode($backup_codes);
        $_SESSION['2fa_secret'] = $secret;
        $_SESSION['2fa_qr_url'] = get2FAQRCodeURL($secret, $_SESSION['usuario'], 'ReySystem');
        registrarAuditoria($conexion, $_SESSION['usuario'], 'habilitar_2fa', 'seguridad', '2FA habilitado');
    } else {
        $_SESSION['mensaje_error'] = "Error al habilitar 2FA.";
    }
    
    $stmt->close();
    $stmt_check->close();
    header("Location: configuracion.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['regenerar_2fa'])) {
    require_once '2fa_helper.php';
    
    $new_secret = generate2FASecret(16);
    $backup_codes = json_encode(generarCodigosRespaldo());
    
    $stmt = $conexion->prepare("UPDATE autenticacion_2fa SET secret = ?, backup_codes = ? WHERE idUsuario = ?");
    $stmt->bind_param("sss", $new_secret, $backup_codes, $_SESSION['usuario']);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = "Secret 2FA regenerado. Escanea el nuevo código QR con Google Authenticator.";
        $_SESSION['backup_codes_2fa'] = json_decode($backup_codes);
        registrarAuditoria($conexion, $_SESSION['usuario'], 'regenerar_2fa', 'seguridad', '2FA secret regenerado');
    } else {
        $_SESSION['mensaje_error'] = "Error al regenerar el secret 2FA.";
    }
    
    $stmt->close();
    header("Location: configuracion.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deshabilitar_2fa'])) {
    $stmt = $conexion->prepare("UPDATE autenticacion_2fa SET enabled = 0 WHERE idUsuario = ?");
    $stmt->bind_param("s", $_SESSION['usuario']);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = "Autenticación de dos factores deshabilitada.";
        registrarAuditoria($conexion, $_SESSION['usuario'], 'deshabilitar_2fa', 'seguridad', '2FA deshabilitado');
    } else {
        $_SESSION['mensaje_error'] = "Error al deshabilitar 2FA.";
    }
    
    $stmt->close();
    header("Location: configuracion.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_api_key'])) {
    $nombre = $_POST['nombre_api_key'];
    $permisos = json_encode($_POST['permisos'] ?? ['read']);
    $api_key = generarAPIKey();
    $expires_at = isset($_POST['expires_at']) && !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    
    $stmt = $conexion->prepare("INSERT INTO api_keys (idUsuario, api_key, nombre, permisos, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $_SESSION['usuario'], $api_key, $nombre, $permisos, $expires_at);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = "API Key creada exitosamente: " . $api_key;
        $_SESSION['nueva_api_key'] = $api_key;
        registrarAuditoria($conexion, $_SESSION['usuario'], 'crear_api_key', 'seguridad', "Nombre: $nombre");
    } else {
        $_SESSION['mensaje_error'] = "Error al crear API Key.";
    }
    
    $stmt->close();
    header("Location: configuracion.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revocar_api_key'])) {
    $api_key_id = $_POST['api_key_id'];
    
    $stmt = $conexion->prepare("UPDATE api_keys SET enabled = 0 WHERE Id = ? AND idUsuario = ?");
    $stmt->bind_param("is", $api_key_id, $_SESSION['usuario']);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = "API Key revocada correctamente.";
        registrarAuditoria($conexion, $_SESSION['usuario'], 'revocar_api_key', 'seguridad', "ID: $api_key_id");
    } else {
        $_SESSION['mensaje_error'] = "Error al revocar API Key.";
    }
    
    $stmt->close();
    header("Location: configuracion.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_privacidad'])) {
    $compartir_datos = isset($_POST['compartir_datos_analitica']) ? 1 : 0;
    $retencion_logs = (int)$_POST['retencion_logs_dias'];
    $cookies_terceros = isset($_POST['permitir_cookies_terceros']) ? 1 : 0;
    $mostrar_directorio = isset($_POST['mostrar_en_directorio']) ? 1 : 0;
    
    $stmt_check = $conexion->prepare("SELECT Id FROM configuracion_privacidad WHERE idUsuario = ?");
    $stmt_check->bind_param("s", $_SESSION['usuario']);
    $stmt_check->execute();
    $resultado = $stmt_check->get_result();
    
    if ($resultado->num_rows > 0) {
        $stmt = $conexion->prepare("UPDATE configuracion_privacidad SET compartir_datos_analitica = ?, retencion_logs_dias = ?, permitir_cookies_terceros = ?, mostrar_en_directorio = ? WHERE idUsuario = ?");
        $stmt->bind_param("iiiis", $compartir_datos, $retencion_logs, $cookies_terceros, $mostrar_directorio, $_SESSION['usuario']);
    } else {
        $stmt = $conexion->prepare("INSERT INTO configuracion_privacidad (idUsuario, compartir_datos_analitica, retencion_logs_dias, permitir_cookies_terceros, mostrar_en_directorio) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("siiii", $_SESSION['usuario'], $compartir_datos, $retencion_logs, $cookies_terceros, $mostrar_directorio);
    }
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = "Configuración de privacidad actualizada.";
        registrarAuditoria($conexion, $_SESSION['usuario'], 'actualizar_privacidad', 'privacidad', "Retención: $retencion_logs días");
    } else {
        $_SESSION['mensaje_error'] = "Error al actualizar privacidad.";
    }
    
    $stmt->close();
    $stmt_check->close();
    header("Location: configuracion.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exportar_datos'])) {
    $datos_usuario = [];
    
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $_SESSION['usuario']);
    $stmt->execute();
    $datos_usuario['perfil'] = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $stmt = $conexion->prepare("SELECT * FROM configuracion_tema WHERE idUsuario = ?");
    $stmt->bind_param("s", $_SESSION['usuario']);
    $stmt->execute();
    $datos_usuario['tema'] = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $stmt = $conexion->prepare("SELECT * FROM audit_log WHERE idUsuario = ? ORDER BY created_at DESC LIMIT 1000");
    $stmt->bind_param("s", $_SESSION['usuario']);
    $stmt->execute();
    $result = $stmt->get_result();
    $datos_usuario['audit_log'] = [];
    while ($row = $result->fetch_assoc()) {
        $datos_usuario['audit_log'][] = $row;
    }
    $stmt->close();
    
    $json_data = json_encode($datos_usuario, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $filename = 'datos_personales_' . $_SESSION['usuario'] . '_' . date('Y-m-d') . '.json';
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $json_data;
    
    registrarAuditoria($conexion, $_SESSION['usuario'], 'exportar_datos', 'privacidad', 'Exportación GDPR');
    exit;
}

// Obtener datos
$stmt_notif = $conexion->prepare("SELECT * FROM configuracion_notificaciones WHERE idUsuario = ?");
$stmt_notif->bind_param("s", $_SESSION['usuario']);
$stmt_notif->execute();
$resultado_notif = $stmt_notif->get_result();

$email_ventas = 1;
$email_deudas = 1;
$email_productos = 1;

if ($resultado_notif->num_rows > 0) {
    $row_notif = $resultado_notif->fetch_assoc();
    $email_ventas = $row_notif['email_ventas'];
    $email_deudas = $row_notif['email_deudas'];
    $email_productos = $row_notif['email_productos'];
}
$stmt_notif->close();

$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Email = $row['Email'];
    $Celular = $row['Celular'];
    $Perfil = $row['Perfil'];
}
$rol_usuario = strtolower($Rol);

$config_app = [];
if ($rol_usuario === 'admin') {
    $resultado_config = $conexion->query("SELECT * FROM configuracion_app WHERE Id = 1");
    if ($resultado_config && $resultado_config->num_rows > 0) {
        $config_app = $resultado_config->fetch_assoc();
    } else {
        $config_app = [
            'nombre_empresa' => 'ReySystem Demo',
            'direccion_empresa' => '',
            'telefono_empresa' => '',
            'email_empresa' => '',
            'impuesto' => 15.00
        ];
    }
}

$config_tema = [];
$stmt_tema = $conexion->prepare("SELECT * FROM configuracion_tema WHERE idUsuario = ?");
$stmt_tema->bind_param("s", $_SESSION['usuario']);
$stmt_tema->execute();
$resultado_tema = $stmt_tema->get_result();
if ($resultado_tema->num_rows > 0) {
    $config_tema = $resultado_tema->fetch_assoc();
} else {
    $config_tema = [
        'tema' => 'dark',
        'color_primario' => '#1152d4',
        'tamano_fuente' => 'medium',
        'alto_contraste' => 0,
        'densidad_interfaz' => 'normal',
        'idioma' => 'es',
        'formato_fecha' => 'd/m/Y',
        'formato_hora' => 'H:i',
        'formato_moneda' => 'L.'
    ];
}
$stmt_tema->close();

$estado_2fa = ['enabled' => 0, 'secret' => null];
$stmt_2fa = $conexion->prepare("SELECT enabled, secret FROM autenticacion_2fa WHERE idUsuario = ?");
$stmt_2fa->bind_param("s", $_SESSION['usuario']);
$stmt_2fa->execute();
$resultado_2fa = $stmt_2fa->get_result();
if ($resultado_2fa->num_rows > 0) {
    $estado_2fa = $resultado_2fa->fetch_assoc();
}
$stmt_2fa->close();

$api_keys = [];
$stmt_api = $conexion->prepare("SELECT * FROM api_keys WHERE idUsuario = ? ORDER BY created_at DESC");
$stmt_api->bind_param("s", $_SESSION['usuario']);
$stmt_api->execute();
$resultado_api = $stmt_api->get_result();
while ($row = $resultado_api->fetch_assoc()) {
    $api_keys[] = $row;
}
$stmt_api->close();

$config_privacidad = [];
$stmt_priv = $conexion->prepare("SELECT * FROM configuracion_privacidad WHERE idUsuario = ?");
$stmt_priv->bind_param("s", $_SESSION['usuario']);
$stmt_priv->execute();
$resultado_priv = $stmt_priv->get_result();
if ($resultado_priv->num_rows > 0) {
    $config_privacidad = $resultado_priv->fetch_assoc();
} else {
    $config_privacidad = [
        'compartir_datos_analitica' => 1,
        'retencion_logs_dias' => 90,
        'permitir_cookies_terceros' => 0,
        'mostrar_en_directorio' => 1
    ];
}
$stmt_priv->close();

$audit_logs = [];
$stmt_audit = $conexion->prepare("SELECT * FROM audit_log WHERE idUsuario = ? ORDER BY created_at DESC LIMIT 50");
$stmt_audit->bind_param("s", $_SESSION['usuario']);
$stmt_audit->execute();
$resultado_audit = $stmt_audit->get_result();
while ($row = $resultado_audit->fetch_assoc()) {
    $audit_logs[] = $row;
}
$stmt_audit->close();

$mensaje_exito = isset($_SESSION['mensaje_exito']) ? $_SESSION['mensaje_exito'] : '';
$mensaje_error = isset($_SESSION['mensaje_error']) ? $_SESSION['mensaje_error'] : '';

unset($_SESSION['mensaje_exito']);
unset($_SESSION['mensaje_error']);
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>ReySystemAPP - Configuración</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#1152d4",
                    "background-light": "#f6f6f8",
                    "background-dark": "#101622",
                },
                fontFamily: {
                    "display": ["Manrope", "sans-serif"]
                },
                borderRadius: {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
                },
            },
        },
    }
</script>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transform: translateX(400px);
        transition: transform 0.3s ease-out;
        opacity: 0;
    }
    .notification.show {
        transform: translateX(0);
        opacity: 1;
    }
    .notification.success {
        background-color: #10b981;
    }
    .notification.error {
        background-color: #ef4444;
    }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<?php include 'menu_lateral.php'; ?>
<div class="flex flex-1 flex-col">
<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-slate-200 dark:border-b-[#232f48] bg-white dark:bg-[#111722] px-10 py-3 sticky top-0 z-10">
<div class="flex flex-1 items-center gap-4">
<label class="relative flex flex-col min-w-40 !h-10 max-w-sm">
<div class="text-slate-500 dark:text-[#92a4c9] absolute left-3 top-1/2 -translate-y-1/2">
<span class="material-symbols-outlined">search</span>
</div>
<input class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border-slate-200 dark:border-none bg-slate-100 dark:bg-[#232f48] h-full placeholder:text-slate-500 dark:placeholder:text-[#92a4c9] pl-11 pr-4 text-base font-normal" placeholder="Search" value=""/>
</label>
</div>
<div class="flex items-center justify-end gap-4">
<div class="flex gap-2">
<script defer src="https://unpkg.com/alpinejs@3.12.0/dist/cdn.min.js"></script>
<?php 
include_once 'generar_notificaciones.php';
$Id = $row['Id'] ?? 1;
generarNotificacionesStock($conexion, $Id);
$notificaciones_pendientes = obtenerNotificacionesPendientes($conexion, $Id);
$total_notificaciones = contarNotificacionesPendientes($conexion, $Id);
include 'notificaciones_component.php'; 
?>
<button class="flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 w-10 bg-slate-100 dark:bg-[#232f48] text-slate-600 dark:text-white">
<span class="material-symbols-outlined text-xl">help</span>
</button>
</div>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="User avatar image" style='background-image: url("<?php echo $Perfil;?>");'></div>
</div>
</header>
<main class="flex-1 p-10">
<div class="mx-auto max-w-7xl">
<div class="flex flex-wrap justify-between gap-3 pb-6">
<div class="flex min-w-72 flex-col gap-2">
<p class="text-slate-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">Configuración</p>
<p class="text-slate-500 dark:text-[#92a4c9] text-base font-normal leading-normal">Gestiona tu cuenta y las preferencias de la aplicación.</p>
</div>
</div>

<?php if (!empty($mensaje_exito)): ?>
<div id="successNotification" class="notification success">
    <div class="flex items-center">
        <span class="material-symbols-outlined mr-2">check_circle</span>
        <span><?php echo $mensaje_exito; ?></span>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($mensaje_error)): ?>
<div id="errorNotification" class="notification error">
    <div class="flex items-center">
        <span class="material-symbols-outlined mr-2">error</span>
        <span><?php echo $mensaje_error; ?></span>
    </div>
</div>
<?php endif; ?>

<div class="pb-3">
<div class="flex border-b border-slate-200 dark:border-[#324467] gap-8 overflow-x-auto">
<button class="tab-btn flex items-center justify-center border-b-[3px] border-b-primary text-primary dark:text-white gap-2 pb-[7px] pt-2.5 whitespace-nowrap" data-tab="perfil">
<span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">person</span>
<p class="text-sm font-bold">Perfil</p>
</button>
<button class="tab-btn flex items-center justify-center border-b-[3px] border-b-transparent text-slate-500 dark:text-slate-400 gap-2 pb-[7px] pt-2.5 hover:border-b-slate-300 whitespace-nowrap" data-tab="seguridad">
<span class="material-symbols-outlined text-2xl">security</span>
<p class="text-sm font-bold">Seguridad</p>
</button>
<button class="tab-btn flex items-center justify-center border-b-[3px] border-b-transparent text-slate-500 dark:text-slate-400 gap-2 pb-[7px] pt-2.5 hover:border-b-slate-300 whitespace-nowrap" data-tab="notificaciones">
<span class="material-symbols-outlined text-2xl">notifications</span>
<p class="text-sm font-bold">Notificaciones</p>
</button>
<button class="tab-btn flex items-center justify-center border-b-[3px] border-b-transparent text-slate-500 dark:text-slate-400 gap-2 pb-[7px] pt-2.5 hover:border-b-slate-300 whitespace-nowrap" data-tab="personalizacion">
<span class="material-symbols-outlined text-2xl">palette</span>
<p class="text-sm font-bold">Personalización</p>
</button>
<button class="tab-btn flex items-center justify-center border-b-[3px] border-b-transparent text-slate-500 dark:text-slate-400 gap-2 pb-[7px] pt-2.5 hover:border-b-slate-300 whitespace-nowrap" data-tab="privacidad">
<span class="material-symbols-outlined text-2xl">shield_person</span>
<p class="text-sm font-bold">Privacidad</p>
</button>
<?php if ($rol_usuario === 'admin'): ?>
<button class="tab-btn flex items-center justify-center border-b-[3px] border-b-transparent text-slate-500 dark:text-slate-400 gap-2 pb-[7px] pt-2.5 hover:border-b-slate-300 whitespace-nowrap" data-tab="aplicacion">
<span class="material-symbols-outlined text-2xl">settings</span>
<p class="text-sm font-bold">Aplicación</p>
</button>
<button class="tab-btn flex items-center justify-center border-b-[3px] border-b-transparent text-slate-500 dark:text-slate-400 gap-2 pb-[7px] pt-2.5 hover:border-b-slate-300 whitespace-nowrap" data-tab="sistema">
<span class="material-symbols-outlined text-2xl">system_update</span>
<p class="text-sm font-bold">Sistema</p>
</button>
<?php endif; ?>
</div>
</div>

<div class="pt-8">
<div class="flex flex-col gap-10">
<!-- Tab Perfil -->
<div id="perfil" class="tab-content active">
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722]">
<div class="p-6 space-y-6">
<div class="flex items-center gap-4">
<form id="formAvatar" method="POST" action="actualizar_avatar.php" enctype="multipart/form-data">
  <label for="avatarInput" class="cursor-pointer">
    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-20" id="avatarPreview"
      style='background-image: url("<?php echo $Perfil;?>");'></div>
  </label>
  <input type="file" name="avatar" id="avatarInput" accept="image/*" class="hidden" onchange="document.getElementById('formAvatar').submit();" />
</form>

<div>
<h3 class="text-slate-900 dark:text-white text-lg font-bold"><?php echo $Nombre_Completo;?></h3>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm"><?php echo $Email;?></p>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm">Rol: <?php echo $Rol;?></p>
</div>
</div>

<form method="POST" action="">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="nombreCompleto">Nombre Completo</label>
<input name="nombreCompleto" class="form-input mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white focus:border-primary focus:ring-primary/50" id="nombreCompleto" type="text" value="<?php echo $Nombre_Completo;?>" required/>
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="email">Correo Electrónico</label>
<input name="email" class="form-input mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white focus:border-primary focus:ring-primary/50" id="email" type="email" value="<?php echo $Email;?>" required/>
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="celular">Teléfono de Contacto</label>
<input name="celular" class="form-input mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white focus:border-primary focus:ring-primary/50" id="celular" type="tel" value="<?php echo $Celular;?>" required/>
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="rol">Rol</label>
<input name="rol" class="form-input mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400" id="rol" type="text" value="<?php echo $Rol;?>" readonly/>
</div>
</div>
<div class="flex justify-end gap-3 border-t border-slate-200 dark:border-[#232f48] bg-slate-50 dark:bg-black/20 p-4 rounded-b-xl mt-6">
<button type="button" class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-slate-200 dark:bg-[#232f48] text-slate-800 dark:text-white text-sm font-bold">Cancelar</button>
<button type="submit" name="actualizar_perfil" class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold">Guardar Cambios</button>
</div>
</form>
</div>
</div>
</div>

<!-- Tab Seguridad -->
<div id="seguridad" class="tab-content">
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722]">
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<div class="flex items-center gap-3">
<div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
<span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">lock_reset</span>
</div>
<div>
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Cambiar Contraseña</h2>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm mt-1">Actualiza tu contraseña para mantener tu cuenta segura</p>
</div>
</div>
</div>
<form method="POST" action="" class="p-6" id="passwordForm">
<div class="space-y-5">
<!-- Contraseña Actual -->
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center gap-2" for="actual">
<span class="material-symbols-outlined text-lg">key</span>
Contraseña Actual
</label>
<div class="relative mt-2">
<input 
name="actual" 
id="actual" 
type="password" 
required
placeholder="Ingresa tu contraseña actual"
class="form-input w-full rounded-lg border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/50 pl-4 pr-12 py-3 text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-primary focus:ring-primary/50 transition"
/>
<button 
type="button" 
onclick="togglePassword('actual')" 
class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
<span class="material-symbols-outlined text-xl" id="actual-icon">visibility</span>
</button>
</div>
</div>

<!-- Nueva Contraseña -->
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center gap-2" for="nueva">
<span class="material-symbols-outlined text-lg">password</span>
Nueva Contraseña
</label>
<div class="relative mt-2">
<input 
name="nueva" 
id="nueva" 
type="password" 
required 
minlength="8"
placeholder="Mínimo 8 caracteres"
class="form-input w-full rounded-lg border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/50 pl-4 pr-12 py-3 text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-primary focus:ring-primary/50 transition"
oninput="checkPasswordStrength()"
/>
<button 
type="button" 
onclick="togglePassword('nueva')" 
class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
<span class="material-symbols-outlined text-xl" id="nueva-icon">visibility</span>
</button>
</div>
<!-- Indicador de fortaleza -->
<div class="mt-2 space-y-1" id="strengthIndicator" style="display: none;">
<div class="flex gap-1">
<div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700" id="strength-bar-1"></div>
<div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700" id="strength-bar-2"></div>
<div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700" id="strength-bar-3"></div>
<div class="h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700" id="strength-bar-4"></div>
</div>
<p class="text-xs" id="strength-text"></p>
</div>
</div>

<!-- Confirmar Contraseña -->
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300 flex items-center gap-2" for="confirmar">
<span class="material-symbols-outlined text-lg">check_circle</span>
Confirmar Nueva Contraseña
</label>
<div class="relative mt-2">
<input 
name="confirmar" 
id="confirmar" 
type="password" 
required 
minlength="8"
placeholder="Repite la nueva contraseña"
class="form-input w-full rounded-lg border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/50 pl-4 pr-12 py-3 text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-primary focus:ring-primary/50 transition"
oninput="checkPasswordMatch()"
/>
<button 
type="button" 
onclick="togglePassword('confirmar')" 
class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition">
<span class="material-symbols-outlined text-xl" id="confirmar-icon">visibility</span>
</button>
</div>
<p class="text-xs mt-1" id="match-text"></p>
</div>

<!-- Consejos de Seguridad -->
<div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
<h4 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2 flex items-center gap-2">
<span class="material-symbols-outlined text-lg">tips_and_updates</span>
Consejos para una contraseña segura
</h4>
<ul class="text-xs text-blue-700 dark:text-blue-300 space-y-1 ml-6 list-disc">
<li>Usa al menos 8 caracteres</li>
<li>Combina letras mayúsculas y minúsculas</li>
<li>Incluye números y símbolos especiales</li>
<li>Evita información personal (nombre, fecha de nacimiento)</li>
<li>No reutilices contraseñas de otras cuentas</li>
</ul>
</div>
</div>

<div class="flex justify-end gap-3 border-t border-slate-200 dark:border-[#232f48] bg-slate-50 dark:bg-black/20 p-4 rounded-b-xl -mx-6 -mb-6 mt-6">
<button 
type="submit" 
name="cambiar_contrasena"
class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition shadow-sm hover:shadow-md">
<span class="material-symbols-outlined">shield_lock</span>
Actualizar Contraseña
</button>
</div>
</form>
</div>

<script>
function togglePassword(fieldId) {
  const field = document.getElementById(fieldId);
  const icon = document.getElementById(fieldId + '-icon');
  
  if (field.type === 'password') {
    field.type = 'text';
    icon.textContent = 'visibility_off';
  } else {
    field.type = 'password';
    icon.textContent = 'visibility';
  }
}

function checkPasswordStrength() {
  const password = document.getElementById('nueva').value;
  const indicator = document.getElementById('strengthIndicator');
  const text = document.getElementById('strength-text');
  const bars = [
    document.getElementById('strength-bar-1'),
    document.getElementById('strength-bar-2'),
    document.getElementById('strength-bar-3'),
    document.getElementById('strength-bar-4')
  ];
  
  if (password.length === 0) {
    indicator.style.display = 'none';
    return;
  }
  
  indicator.style.display = 'block';
  
  let strength = 0;
  if (password.length >= 8) strength++;
  if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
  if (password.match(/[0-9]/)) strength++;
  if (password.match(/[^a-zA-Z0-9]/)) strength++;
  
  // Reset bars
  bars.forEach(bar => {
    bar.className = 'h-1 flex-1 rounded-full bg-slate-200 dark:bg-slate-700';
  });
  
  // Update bars and text based on strength
  if (strength === 1) {
    bars[0].className = 'h-1 flex-1 rounded-full bg-red-500';
    text.className = 'text-xs text-red-600 dark:text-red-400';
    text.textContent = 'Débil - Agrega más caracteres y variedad';
  } else if (strength === 2) {
    bars[0].className = 'h-1 flex-1 rounded-full bg-orange-500';
    bars[1].className = 'h-1 flex-1 rounded-full bg-orange-500';
    text.className = 'text-xs text-orange-600 dark:text-orange-400';
    text.textContent = 'Regular - Considera agregar números o símbolos';
  } else if (strength === 3) {
    bars[0].className = 'h-1 flex-1 rounded-full bg-yellow-500';
    bars[1].className = 'h-1 flex-1 rounded-full bg-yellow-500';
    bars[2].className = 'h-1 flex-1 rounded-full bg-yellow-500';
    text.className = 'text-xs text-yellow-600 dark:text-yellow-400';
    text.textContent = 'Buena - Casi perfecta';
  } else if (strength === 4) {
    bars[0].className = 'h-1 flex-1 rounded-full bg-green-500';
    bars[1].className = 'h-1 flex-1 rounded-full bg-green-500';
    bars[2].className = 'h-1 flex-1 rounded-full bg-green-500';
    bars[3].className = 'h-1 flex-1 rounded-full bg-green-500';
    text.className = 'text-xs text-green-600 dark:text-green-400';
    text.textContent = '¡Excelente! Contraseña muy segura';
  }
  
  checkPasswordMatch();
}

function checkPasswordMatch() {
  const nueva = document.getElementById('nueva').value;
  const confirmar = document.getElementById('confirmar').value;
  const matchText = document.getElementById('match-text');
  
  if (confirmar.length === 0) {
    matchText.textContent = '';
    return;
  }
  
  if (nueva === confirmar) {
    matchText.className = 'text-xs mt-1 text-green-600 dark:text-green-400 flex items-center gap-1';
    matchText.innerHTML = '<span class="material-symbols-outlined text-sm">check_circle</span> Las contraseñas coinciden';
  } else {
    matchText.className = 'text-xs mt-1 text-red-600 dark:text-red-400 flex items-center gap-1';
    matchText.innerHTML = '<span class="material-symbols-outlined text-sm">cancel</span> Las contraseñas no coinciden';
  }
}
</script>

<!-- Sección 2FA -->
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722] mt-6">
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Autenticación de Dos Factores (2FA)</h2>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm mt-1">Agrega una capa adicional de seguridad a tu cuenta con Google Authenticator.</p>
</div>
<div class="p-6">
<?php if ($estado_2fa['enabled']): ?>
<div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-green-600 dark:text-green-400 text-3xl">verified_user</span>
<div>
<p class="font-semibold text-green-800 dark:text-green-200">2FA Activado</p>
<p class="text-sm text-green-700 dark:text-green-300">Tu cuenta está protegida con autenticación de dos factores.</p>
</div>
</div>
</div>

<!-- Mostrar QR y secret cuando está habilitado -->
<?php if (!empty($estado_2fa['secret'])): ?>
<?php 
require_once '2fa_helper.php';
$qr_url = get2FAQRCodeURL($estado_2fa['secret'], $_SESSION['usuario'], 'ReySystem');
?>
<div class="mt-4 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-2 border-blue-300 dark:border-blue-700 rounded-xl p-6 mb-4">
<h3 class="font-bold text-blue-900 dark:text-blue-100 mb-4 text-lg flex items-center gap-2">
<span class="material-symbols-outlined text-2xl">qr_code_scanner</span>
Tu Configuración de Google Authenticator
</h3>

<div class="grid md:grid-cols-2 gap-6">
<div class="flex flex-col items-center justify-center bg-white dark:bg-slate-800 rounded-lg p-6 border-2 border-dashed border-blue-300 dark:border-blue-700">
<p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">Escanea este código QR:</p>
<img src="<?php echo $qr_url; ?>" alt="QR Code" class="w-48 h-48 border-4 border-white dark:border-slate-700 rounded-lg shadow-lg">
<p class="text-xs text-slate-500 dark:text-slate-400 mt-3 text-center">Abre Google Authenticator y escanea</p>
</div>

<div class="flex flex-col justify-center">
<div class="bg-white dark:bg-slate-800 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
<p class="text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">O ingresa este código manualmente:</p>
<div class="flex items-center gap-2 bg-slate-100 dark:bg-slate-900 p-3 rounded border border-slate-300 dark:border-slate-600">
<code class="flex-1 text-lg font-mono font-bold text-blue-600 dark:text-blue-400 tracking-wider"><?php echo chunk_split($estado_2fa['secret'], 4, ' '); ?></code>
<button onclick="navigator.clipboard.writeText('<?php echo $estado_2fa['secret']; ?>'); this.innerHTML='<span class=\'material-symbols-outlined\'>check</span>';" class="px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
<span class="material-symbols-outlined">content_copy</span>
</button>
</div>
<p class="text-xs text-slate-500 dark:text-slate-400 mt-2">
<span class="material-symbols-outlined text-sm align-middle">info</span>
Usa este código si no puedes escanear el QR
</p>
</div>
</div>
</div>
</div>
<?php endif; ?>

<div class="flex gap-3">
<form method="POST" action="">
<button type="submit" name="regenerar_2fa" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
<span class="material-symbols-outlined">refresh</span>
Regenerar QR Code
</button>
</form>
<form method="POST" action="">
<button type="submit" name="deshabilitar_2fa" class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition">
<span class="material-symbols-outlined">lock_open</span>
Desactivar 2FA
</button>
</form>
</div>
<?php else: ?>
<div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
<div class="flex items-center gap-3">
<span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 text-3xl">warning</span>
<div>
<p class="font-semibold text-yellow-800 dark:text-yellow-200">2FA Desactivado</p>
<p class="text-sm text-yellow-700 dark:text-yellow-300">Tu cuenta no está protegida con autenticación de dos factores.</p>
</div>
</div>
</div>

<div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
<h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-2 flex items-center gap-2">
<span class="material-symbols-outlined">info</span>
Cómo configurar 2FA
</h3>
<ol class="text-sm text-blue-700 dark:text-blue-300 space-y-2 ml-4 list-decimal">
<li>Descarga Google Authenticator en tu dispositivo móvil</li>
<li>Haz clic en "Habilitar 2FA" abajo</li>
<li>Escanea el código QR que aparecerá con la app</li>
<li>Guarda los códigos de respaldo en un lugar seguro</li>
</ol>
</div>

<form method="POST" action="">
<button type="submit" name="habilitar_2fa" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition">
<span class="material-symbols-outlined">enhanced_encryption</span>
Habilitar 2FA con Google Authenticator
</button>
</form>
<?php endif; ?>

<!-- Mostrar códigos de respaldo solo cuando se acaban de generar -->
<?php if (isset($_SESSION['backup_codes_2fa'])): ?>
<div class="mt-4 bg-purple-50 dark:bg-purple-900/20 border-2 border-purple-300 dark:border-purple-700 rounded-xl p-6">
<h3 class="font-bold text-purple-900 dark:text-purple-100 mb-2 flex items-center gap-2">
<span class="material-symbols-outlined">key</span>
Códigos de Respaldo
</h3>
<p class="text-sm text-purple-700 dark:text-purple-300 mb-4">
<span class="material-symbols-outlined text-sm align-middle">info</span>
Guarda estos códigos en un lugar seguro. Puedes usarlos si pierdes acceso a tu dispositivo 2FA.
</p>
<div class="grid grid-cols-2 gap-2 mb-4">
<?php foreach ($_SESSION['backup_codes_2fa'] as $code): ?>
<code class="bg-white dark:bg-slate-800 px-3 py-2 rounded border-2 border-purple-300 dark:border-purple-700 text-sm font-mono font-bold text-purple-700 dark:text-purple-300"><?php echo $code; ?></code>
<?php endforeach; ?>
</div>
<button onclick="window.print();" class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg font-medium hover:bg-purple-700 transition text-sm">
<span class="material-symbols-outlined">print</span>
Imprimir Códigos
</button>
</div>
<?php unset($_SESSION['backup_codes_2fa']); ?>
<?php endif; ?>
</div>
</div>

<!-- Sección API Keys -->
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722] mt-6">
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Claves API</h2>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm mt-1">Gestiona las claves API para integraciones externas.</p>
</div>
<div class="p-6">
<?php if (isset($_SESSION['nueva_api_key'])): ?>
<div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
<p class="font-semibold text-green-800 dark:text-green-200 mb-2">API Key creada exitosamente:</p>
<div class="flex items-center gap-2">
<code class="flex-1 bg-white dark:bg-slate-800 px-3 py-2 rounded border border-green-300 dark:border-green-700 text-sm font-mono"><?php echo $_SESSION['nueva_api_key']; ?></code>
<button onclick="navigator.clipboard.writeText('<?php echo $_SESSION['nueva_api_key']; ?>')" class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
<span class="material-symbols-outlined text-sm">content_copy</span>
</button>
</div>
<p class="text-xs text-green-700 dark:text-green-300 mt-2">⚠️ Guarda esta clave ahora. No podrás verla de nuevo.</p>
</div>
<?php unset($_SESSION['nueva_api_key']); ?>
<?php endif; ?>

<details class="mb-4">
<summary class="cursor-pointer font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-800/50 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800">
<span class="material-symbols-outlined">add_circle</span>
Crear Nueva API Key
</summary>
<form method="POST" action="" class="mt-4 space-y-4 p-4 border border-slate-200 dark:border-slate-700 rounded-lg">
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Nombre de la API Key</label>
<input name="nombre_api_key" type="text" required class="form-input mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white" placeholder="Ej: Integración WhatsApp">
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Permisos</label>
<div class="mt-2 space-y-2">
<label class="flex items-center gap-2">
<input type="checkbox" name="permisos[]" value="read" checked class="rounded">
<span class="text-sm">Lectura</span>
</label>
<label class="flex items-center gap-2">
<input type="checkbox" name="permisos[]" value="write" class="rounded">
<span class="text-sm">Escritura</span>
</label>
<label class="flex items-center gap-2">
<input type="checkbox" name="permisos[]" value="delete" class="rounded">
<span class="text-sm">Eliminación</span>
</label>
</div>
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Fecha de Expiración (Opcional)</label>
<input name="expires_at" type="date" class="form-input mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white">
</div>
<button type="submit" name="crear_api_key" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-medium hover:bg-primary/90 transition">
<span class="material-symbols-outlined">key</span>
Generar API Key
</button>
</form>
</details>

<div class="space-y-3">
<h3 class="font-semibold text-slate-800 dark:text-slate-200">API Keys Activas</h3>
<?php if (empty($api_keys)): ?>
<p class="text-sm text-slate-500 dark:text-slate-400">No tienes API keys creadas.</p>
<?php else: ?>
<?php foreach ($api_keys as $key): ?>
<div class="flex items-center justify-between p-4 border border-slate-200 dark:border-slate-700 rounded-lg <?php echo $key['enabled'] ? '' : 'opacity-50'; ?>">
<div class="flex-1">
<p class="font-medium text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($key['nombre']); ?></p>
<p class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-1">
<?php echo substr($key['api_key'], 0, 16); ?>...<?php echo substr($key['api_key'], -8); ?>
</p>
<div class="flex items-center gap-4 mt-2 text-xs text-slate-500 dark:text-slate-400">
<span>Creada: <?php echo date('d/m/Y', strtotime($key['created_at'])); ?></span>
<?php if ($key['last_used']): ?>
<span>Último uso: <?php echo time_ago($key['last_used']); ?></span>
<?php endif; ?>
<?php if ($key['expires_at']): ?>
<span>Expira: <?php echo date('d/m/Y', strtotime($key['expires_at'])); ?></span>
<?php endif; ?>
</div>
</div>
<div class="flex items-center gap-2">
<span class="px-2 py-1 text-xs rounded <?php echo $key['enabled'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'; ?>">
<?php echo $key['enabled'] ? 'Activa' : 'Revocada'; ?>
</span>
<?php if ($key['enabled']): ?>
<form method="POST" action="" style="display:inline;">
<input type="hidden" name="api_key_id" value="<?php echo $key['Id']; ?>">
<button type="submit" name="revocar_api_key" class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700 transition">
Revocar
</button>
</form>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</div>

<!-- Sección Llaves de Seguridad -->
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722] mt-6">
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<div class="flex items-center gap-3">
<div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
<span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-2xl">security_key</span>
</div>
<div>
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Llaves de Seguridad</h2>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm mt-1">Protege tu cuenta con métodos de autenticación adicionales</p>
</div>
</div>
</div>
<div class="p-6 space-y-6">

<!-- Información General -->
<div class="bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-5">
<h3 class="font-semibold text-purple-900 dark:text-purple-100 mb-3 flex items-center gap-2">
<span class="material-symbols-outlined">info</span>
¿Qué son las llaves de seguridad?
</h3>
<p class="text-sm text-purple-800 dark:text-purple-200 mb-3">
Las llaves de seguridad son métodos adicionales de autenticación que hacen tu cuenta más segura. Puedes usar llaves físicas, el PIN de tu dispositivo, o registrar dispositivos de confianza.
</p>
<div class="grid md:grid-cols-3 gap-3 mt-4">
<div class="bg-white dark:bg-slate-800 rounded-lg p-3 border border-purple-200 dark:border-purple-700">
<span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-2xl mb-2">usb</span>
<p class="text-xs font-semibold text-slate-800 dark:text-slate-200">Llave Física</p>
<p class="text-xs text-slate-600 dark:text-slate-400 mt-1">YubiKey, Google Titan</p>
</div>
<div class="bg-white dark:bg-slate-800 rounded-lg p-3 border border-purple-200 dark:border-purple-700">
<span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-2xl mb-2">fingerprint</span>
<p class="text-xs font-semibold text-slate-800 dark:text-slate-200">Biometría</p>
<p class="text-xs text-slate-600 dark:text-slate-400 mt-1">Huella, Face ID</p>
</div>
<div class="bg-white dark:bg-slate-800 rounded-lg p-3 border border-purple-200 dark:border-purple-700">
<span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-2xl mb-2">devices</span>
<p class="text-xs font-semibold text-slate-800 dark:text-slate-200">Dispositivo</p>
<p class="text-xs text-slate-600 dark:text-slate-400 mt-1">PIN, Patrón</p>
</div>
</div>
</div>

<!-- Opciones de Llaves de Seguridad -->
<div class="space-y-4">

<!-- Opción 1: WebAuthn / FIDO2 -->
<div class="border border-slate-200 dark:border-slate-700 rounded-xl p-5 hover:border-purple-300 dark:hover:border-purple-700 transition">
<div class="flex items-start justify-between gap-4">
<div class="flex items-start gap-4 flex-1">
<div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30 flex-shrink-0">
<span class="material-symbols-outlined text-blue-600 dark:text-blue-400">vpn_key</span>
</div>
<div class="flex-1 min-w-0">
<h4 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2 flex-wrap">
Llave de Seguridad Física (WebAuthn)
<span class="px-2 py-0.5 text-xs rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">Recomendado</span>
</h4>
<p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
Usa una llave física USB o NFC como YubiKey, Google Titan Key, o cualquier dispositivo compatible con FIDO2.
</p>
<div class="mt-3 flex flex-wrap gap-2">
<span class="px-2 py-1 text-xs rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">✓ Máxima seguridad</span>
<span class="px-2 py-1 text-xs rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">✓ Resistente a phishing</span>
</div>
</div>
</div>
<button 
onclick="showWebAuthnModal()" 
class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition whitespace-nowrap flex-shrink-0">
<span class="material-symbols-outlined text-lg">add</span>
Registrar
</button>
</div>
</div>

<!-- Opción 2: PIN del Dispositivo -->
<div class="border border-slate-200 dark:border-slate-700 rounded-xl p-5 hover:border-purple-300 dark:hover:border-purple-700 transition">
<div class="flex items-start justify-between gap-4">
<div class="flex items-start gap-4 flex-1">
<div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30 flex-shrink-0">
<span class="material-symbols-outlined text-green-600 dark:text-green-400">pin</span>
</div>
<div class="flex-1 min-w-0">
<h4 class="font-semibold text-slate-900 dark:text-white">PIN de Seguridad</h4>
<p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
Configura un PIN de 6 dígitos como método de autenticación alternativo.
</p>
<div class="mt-3 flex flex-wrap gap-2">
<span class="px-2 py-1 text-xs rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">✓ Fácil de recordar</span>
<span class="px-2 py-1 text-xs rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">✓ Acceso rápido</span>
</div>
</div>
</div>
<button 
onclick="showPINModal()" 
class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition whitespace-nowrap flex-shrink-0">
<span class="material-symbols-outlined text-lg">add</span>
Configurar
</button>
</div>
</div>

<!-- Opción 3: Dispositivos de Confianza -->
<div class="border border-slate-200 dark:border-slate-700 rounded-xl p-5 hover:border-purple-300 dark:hover:border-purple-700 transition">
<div class="flex items-start justify-between gap-4">
<div class="flex items-start gap-4 flex-1">
<div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30 flex-shrink-0">
<span class="material-symbols-outlined text-orange-600 dark:text-orange-400">devices_other</span>
</div>
<div class="flex-1 min-w-0">
<h4 class="font-semibold text-slate-900 dark:text-white">Dispositivos de Confianza</h4>
<p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
Registra este dispositivo como confiable para no requerir autenticación adicional.
</p>
<div class="mt-3 flex flex-wrap gap-2">
<span class="px-2 py-1 text-xs rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">✓ Conveniente</span>
<span class="px-2 py-1 text-xs rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">✓ Recordar por 30 días</span>
</div>
</div>
</div>
<button 
onclick="showTrustedDeviceModal()" 
class="flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 transition whitespace-nowrap flex-shrink-0">
<span class="material-symbols-outlined text-lg">add</span>
Registrar
</button>
</div>
</div>

<!-- Opción 4: Autenticación Biométrica -->
<div class="border border-slate-200 dark:border-slate-700 rounded-xl p-5 hover:border-purple-300 dark:hover:border-purple-700 transition">
<div class="flex items-start justify-between gap-4">
<div class="flex items-start gap-4 flex-1">
<div class="flex h-10 w-10 items-center justify-center rounded-full bg-pink-100 dark:bg-pink-900/30 flex-shrink-0">
<span class="material-symbols-outlined text-pink-600 dark:text-pink-400">fingerprint</span>
</div>
<div class="flex-1 min-w-0">
<h4 class="font-semibold text-slate-900 dark:text-white">Autenticación Biométrica</h4>
<p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
Usa la huella dactilar, Face ID, Windows Hello u otro método biométrico de tu dispositivo para autenticarte.
</p>
<div class="mt-3 flex flex-wrap gap-2">
<span class="px-2 py-1 text-xs rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">✓ Máxima seguridad</span>
<span class="px-2 py-1 text-xs rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">✓ Requiere sensor biométrico</span>
</div>
</div>
</div>
<button 
onclick="showBiometricModal()" 
class="flex items-center gap-2 px-4 py-2 bg-pink-600 text-white rounded-lg font-medium hover:bg-pink-700 transition whitespace-nowrap flex-shrink-0">
<span class="material-symbols-outlined text-lg">add</span>
Registrar
</button>
</div>
</div>

</div>

<!-- Llaves Registradas -->
<div class="border-t border-slate-200 dark:border-slate-700 pt-6">
<h3 class="font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
<span class="material-symbols-outlined">list</span>
Llaves de Seguridad Registradas
</h3>

<?php
// Obtener llaves de seguridad registradas
$stmt_keys = $conexion->prepare("SELECT id, key_type, key_name, created_at FROM security_keys WHERE user_id = ? AND enabled = 1");
$stmt_keys->bind_param("i", $Id);
$stmt_keys->execute();
$result_keys = $stmt_keys->get_result();

// Obtener dispositivos de confianza
$stmt_devices = $conexion->prepare("SELECT id, device_name, browser, os, created_at, expires_at FROM trusted_devices WHERE user_id = ? AND expires_at > NOW()");
$stmt_devices->bind_param("i", $Id);
$stmt_devices->execute();
$result_devices = $stmt_devices->get_result();

$has_keys = ($result_keys->num_rows > 0 || $result_devices->num_rows > 0);
?>

<?php if ($has_keys): ?>
    <div class="space-y-3">
        <?php while ($key = $result_keys->fetch_assoc()): ?>
            <?php
            $icon = 'vpn_key';
            $color = 'blue';
            switch($key['key_type']) {
                case 'pin':
                    $icon = 'pin';
                    $color = 'green';
                    break;
                case 'biometric':
                    $icon = 'fingerprint';
                    $color = 'pink';
                    break;
                case 'webauthn':
                    $icon = 'usb';
                    $color = 'blue';
                    break;
            }
            ?>
            <div class="flex items-center justify-between p-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-<?php echo $color; ?>-100 dark:bg-<?php echo $color; ?>-900/30 flex items-center justify-center">
                        <span class="material-symbols-outlined text-<?php echo $color; ?>-600 dark:text-<?php echo $color; ?>-400"><?php echo $icon; ?></span>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($key['key_name']); ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Registrado: <?php echo date('d/m/Y', strtotime($key['created_at'])); ?>
                        </p>
                    </div>
                </div>
                <button onclick="deleteSecurityKey(<?php echo $key['id']; ?>)" 
                        class="px-3 py-1 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                    <span class="material-symbols-outlined text-lg">delete</span>
                </button>
            </div>
        <?php endwhile; ?>
        
        <?php while ($device = $result_devices->fetch_assoc()): ?>
            <div class="flex items-center justify-between p-4 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center">
                        <span class="material-symbols-outlined text-orange-600 dark:text-orange-400">devices_other</span>
                    </div>
                    <div>
                        <p class="font-medium text-slate-900 dark:text-white"><?php echo htmlspecialchars($device['device_name']); ?></p>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Expira: <?php echo date('d/m/Y', strtotime($device['expires_at'])); ?>
                        </p>
                    </div>
                </div>
                <button onclick="deleteTrustedDevice(<?php echo $device['id']; ?>)" 
                        class="px-3 py-1 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                    <span class="material-symbols-outlined text-lg">delete</span>
                </button>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="text-center py-8 text-slate-500 dark:text-slate-400">
        <span class="material-symbols-outlined text-4xl mb-2 opacity-50">key_off</span>
        <p class="text-sm">No tienes llaves de seguridad registradas</p>
        <p class="text-xs mt-1">Registra una llave para proteger mejor tu cuenta</p>
    </div>
<?php endif; ?>
</div>

<!-- Modales para Llaves de Seguridad -->
<!-- INSERTAR ANTES DE LA LÍNEA 1500 (antes de </div></div></div></div>) -->

<!-- Modal WebAuthn -->
<div id="webauthnModal"
    class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">vpn_key</span>
                Registrar Llave de Seguridad
            </h3>
            <button onclick="closeWebAuthnModal()"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="text-center py-6">
            <div
                class="mx-auto w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-4xl">usb</span>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                Conecta tu llave de seguridad física (YubiKey, Google Titan, etc.) y sigue las instrucciones de tu
                navegador.
            </p>
            <div id="webauthnStatus" class="text-sm text-slate-500 dark:text-slate-400 mb-4"></div>
        </div>
        <div class="flex gap-3">
            <button onclick="closeWebAuthnModal()"
                class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                Cancelar
            </button>
            <button onclick="registerWebAuthnKey()"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
                Registrar
            </button>
        </div>
    </div>
</div>

<!-- Modal PIN -->
<div id="pinSecurityModal"
    class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-green-600">pin</span>
                Configurar PIN de Seguridad
            </h3>
            <button onclick="closePINSecurityModal()"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
            Crea un PIN de 6 dígitos que usarás como método de autenticación alternativo.
        </p>
        <form id="pinSecurityForm" class="space-y-4">
            <div>
                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Nuevo PIN</label>
                <input type="password" id="newSecurityPin" maxlength="6" pattern="[0-9]{6}" placeholder="000000"
                    class="form-input mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 text-center text-2xl font-mono tracking-widest"
                    required />
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700 dark:text-slate-300">Confirmar PIN</label>
                <input type="password" id="confirmSecurityPin" maxlength="6" pattern="[0-9]{6}" placeholder="000000"
                    class="form-input mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 text-center text-2xl font-mono tracking-widest"
                    required />
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="closePINSecurityModal()"
                    class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition">
                    Guardar PIN
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Dispositivo de Confianza -->
<div id="trustedDeviceModal"
    class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-orange-600">devices_other</span>
                Registrar Dispositivo de Confianza
            </h3>
            <button onclick="closeTrustedDeviceModal()"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="space-y-4">
            <div
                class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                <p class="text-sm text-orange-800 dark:text-orange-200">
                    <strong>Información del dispositivo:</strong>
                </p>
                <div class="mt-2 space-y-1 text-xs text-orange-700 dark:text-orange-300">
                    <p id="deviceInfo"></p>
                </div>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400">
                Este dispositivo será recordado por 30 días. No necesitarás autenticación adicional al iniciar sesión
                desde este dispositivo.
            </p>
            <div class="flex gap-3">
                <button onclick="closeTrustedDeviceModal()"
                    class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button onclick="confirmTrustedDevice()"
                    class="flex-1 px-4 py-2 bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700 transition">
                    Registrar Dispositivo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Biometría -->
<div id="biometricModal"
    class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span class="material-symbols-outlined text-pink-600">fingerprint</span>
                Registrar Autenticación Biométrica
            </h3>
            <button onclick="closeBiometricModal()"
                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="text-center py-6">
            <div
                class="mx-auto w-20 h-20 bg-pink-100 dark:bg-pink-900/30 rounded-full flex items-center justify-center mb-4">
                <span class="material-symbols-outlined text-pink-600 dark:text-pink-400 text-4xl">fingerprint</span>
            </div>
            <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                Registra tu huella dactilar, Face ID, Windows Hello u otro método biométrico de tu dispositivo. Requiere hardware compatible.
            </p>
            <div id="biometricStatus" class="text-sm text-slate-500 dark:text-slate-400 mb-4"></div>
        </div>
        <div class="flex gap-3">
            <button onclick="closeBiometricModal()"
                class="flex-1 px-4 py-2 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 rounded-lg font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition">
                Cancelar
            </button>
            <button onclick="registerBiometric()"
                class="flex-1 px-4 py-2 bg-pink-600 text-white rounded-lg font-medium hover:bg-pink-700 transition">
                Registrar Biometría
            </button>
        </div>
    </div>
</div>

<script>
// ===== MODAL FUNCTIONS (Must be at top) =====
function showWebAuthnModal() {
  document.getElementById('webauthnModal').classList.remove('hidden');
}

function closeWebAuthnModal() {
  document.getElementById('webauthnModal').classList.add('hidden');
  document.getElementById('webauthnStatus').textContent = '';
}

function showPINModal() {
  document.getElementById('pinSecurityModal').classList.remove('hidden');
}

function closePINSecurityModal() {
  document.getElementById('pinSecurityModal').classList.add('hidden');
  document.getElementById('pinSecurityForm').reset();
}

function showTrustedDeviceModal() {
  const modal = document.getElementById('trustedDeviceModal');
  const deviceInfo = document.getElementById('deviceInfo');
  
  const browser = navigator.userAgent.includes('Chrome') ? 'Chrome' : 
                  navigator.userAgent.includes('Firefox') ? 'Firefox' : 
                  navigator.userAgent.includes('Safari') ? 'Safari' : 'Navegador';
  
  const os = navigator.userAgent.includes('Windows') ? 'Windows' : 
             navigator.userAgent.includes('Mac') ? 'macOS' : 
             navigator.userAgent.includes('Linux') ? 'Linux' : 'Sistema Operativo';
  
  deviceInfo.innerHTML = `
    <p><strong>Navegador:</strong> ${browser}</p>
    <p><strong>Sistema:</strong> ${os}</p>
    <p><strong>Fecha:</strong> ${new Date().toLocaleDateString('es-ES')}</p>
  `;
  
  modal.classList.remove('hidden');
}

function closeTrustedDeviceModal() {
  document.getElementById('trustedDeviceModal').classList.add('hidden');
}

function showBiometricModal() {
  document.getElementById('biometricModal').classList.remove('hidden');
}

function closeBiometricModal() {
  document.getElementById('biometricModal').classList.add('hidden');
  document.getElementById('biometricStatus').textContent = '';
}

// ===== WebAuthn Registration =====
async function registerWebAuthnKey() {
  const statusEl = document.getElementById('webauthnStatus');
  
  if (!window.PublicKeyCredential) {
    showNotification('Tu navegador no soporta llaves de seguridad WebAuthn', 'error');
    return;
  }
  
  try {
    statusEl.textContent = '🔄 Preparando registro...';
    statusEl.className = 'text-sm text-blue-600 dark:text-blue-400 mb-4';
    
    // REAL WebAuthn Registration
    const challenge = new Uint8Array(32);
    window.crypto.getRandomValues(challenge);
    
    const userId = new Uint8Array(16);
    window.crypto.getRandomValues(userId);
    
    const publicKeyCredentialCreationOptions = {
      challenge: challenge,
      rp: {
        name: "Rey System",
        id: window.location.hostname
      },
      user: {
        id: userId,
        name: "<?php echo $_SESSION['usuario']; ?>",
        displayName: "<?php echo $Nombre_Completo; ?>"
      },
      pubKeyCredParams: [
        { alg: -7, type: "public-key" },  // ES256
        { alg: -257, type: "public-key" } // RS256
      ],
      authenticatorSelection: {
        authenticatorAttachment: "cross-platform", // Physical key
        requireResidentKey: false,
        userVerification: "preferred"
      },
      timeout: 60000,
      attestation: "direct"
    };
    
    statusEl.textContent = '🔐 Toca tu llave de seguridad...';
    
    // Create credential using REAL WebAuthn API
    const credential = await navigator.credentials.create({
      publicKey: publicKeyCredentialCreationOptions
    });
    
    if (!credential) {
      throw new Error('No se pudo crear la credencial');
    }
    
    statusEl.textContent = '✅ Llave detectada, guardando...';
    
    // Convert ArrayBuffer to Base64 for storage
    const credentialId = btoa(String.fromCharCode(...new Uint8Array(credential.rawId)));
    const publicKey = btoa(String.fromCharCode(...new Uint8Array(credential.response.getPublicKey())));
    
    // Save to database
    const formData = new FormData();
    formData.append('action', 'save_webauthn');
    formData.append('key_name', 'Llave de Seguridad WebAuthn');
    formData.append('credential_id', credentialId);
    formData.append('public_key', publicKey);
    formData.append('key_data', JSON.stringify({
      id: credentialId,
      type: credential.type,
      transports: credential.response.getTransports ? credential.response.getTransports() : []
    }));
    
    const response = await fetch('api_security_keys.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    closeWebAuthnModal();
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      setTimeout(() => location.reload(), 1500);
    }
  } catch (error) {
    console.error('Error WebAuthn:', error);
    statusEl.textContent = '❌ Error: ' + (error.message || 'Error al registrar la llave');
    statusEl.className = 'text-sm text-red-600 dark:text-red-400 mb-4';
    showNotification('Error al registrar llave de seguridad', 'error');
  }
}

// PIN Form Handler
document.getElementById('pinSecurityForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const newPin = document.getElementById('newSecurityPin').value;
  const confirmPin = document.getElementById('confirmSecurityPin').value;
  
  if (newPin.length !== 6 || !/^\d{6}$/.test(newPin)) {
    showNotification('El PIN debe tener exactamente 6 dígitos', 'error');
    return;
  }
  
  if (newPin !== confirmPin) {
    showNotification('Los PINs no coinciden', 'error');
    return;
  }
  
  // Guardar en BD
  const formData = new FormData();
  formData.append('action', 'save_pin');
  formData.append('pin', newPin);
  
  try {
    const response = await fetch('api_security_keys.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    closePINSecurityModal();
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      setTimeout(() => location.reload(), 1500);
    }
  } catch (error) {
    console.error('Error PIN:', error);
    showNotification('Error al guardar PIN: ' + (error.message || 'Error desconocido'), 'error');
  }
});

// Trusted Device Confirmation Handler
async function confirmTrustedDevice() {
  const browser = navigator.userAgent.includes('Chrome') ? 'Chrome' : 
                  navigator.userAgent.includes('Firefox') ? 'Firefox' : 
                  navigator.userAgent.includes('Safari') ? 'Safari' : 'Navegador';
  
  const os = navigator.userAgent.includes('Windows') ? 'Windows' : 
             navigator.userAgent.includes('Mac') ? 'macOS' : 
             navigator.userAgent.includes('Linux') ? 'Linux' : 'Sistema Operativo';
  
  // Generar fingerprint del dispositivo
  const fingerprint = btoa(navigator.userAgent + navigator.language + screen.width + screen.height);
  
  const formData = new FormData();
  formData.append('action', 'save_trusted_device');
  formData.append('fingerprint', fingerprint);
  formData.append('device_name', `${os} - ${browser}`);
  formData.append('browser', browser);
  formData.append('os', os);
  
  try {
    const response = await fetch('api_security_keys.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    closeTrustedDeviceModal();
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      setTimeout(() => location.reload(), 1500);
    }
  } catch (error) {
    console.error('Error Trusted Device:', error);
    showNotification('Error al registrar dispositivo: ' + (error.message || 'Error desconocido'), 'error');
  }
}

// Biometric Registration Handler
async function registerBiometric() {
  const statusEl = document.getElementById('biometricStatus');
  
  if (!window.PublicKeyCredential) {
    showNotification('Tu dispositivo no soporta autenticación biométrica web', 'error');
    return;
  }
  
  try {
    statusEl.textContent = '🔄 Preparando autenticación biométrica...';
    statusEl.className = 'text-sm text-pink-600 dark:text-pink-400 mb-4';
    
    // REAL Biometric Authentication using WebAuthn Platform Authenticator
    const challenge = new Uint8Array(32);
    window.crypto.getRandomValues(challenge);
    
    const userId = new Uint8Array(16);
    window.crypto.getRandomValues(userId);
    
    const publicKeyCredentialCreationOptions = {
      challenge: challenge,
      rp: {
        name: "Rey System",
        id: window.location.hostname
      },
      user: {
        id: userId,
        name: "<?php echo $_SESSION['usuario']; ?>",
        displayName: "<?php echo $Nombre_Completo; ?>"
      },
      pubKeyCredParams: [
        { alg: -7, type: "public-key" },  // ES256
        { alg: -257, type: "public-key" } // RS256
      ],
      authenticatorSelection: {
        authenticatorAttachment: "platform", // Platform authenticator (biometric)
        requireResidentKey: false,
        userVerification: "required" // Require biometric verification
      },
      timeout: 60000,
      attestation: "direct"
    };
    
    statusEl.textContent = '👆 Usa tu huella, Face ID o Windows Hello...';
    
    // Create credential using REAL WebAuthn API with biometric
    const credential = await navigator.credentials.create({
      publicKey: publicKeyCredentialCreationOptions
    });
    
    if (!credential) {
      throw new Error('No se pudo crear la credencial biométrica');
    }
    
    statusEl.textContent = '✅ Biometría registrada, guardando...';
    
    // Convert ArrayBuffer to Base64 for storage
    const credentialId = btoa(String.fromCharCode(...new Uint8Array(credential.rawId)));
    const publicKey = btoa(String.fromCharCode(...new Uint8Array(credential.response.getPublicKey())));
    
    // Save to database
    const formData = new FormData();
    formData.append('action', 'save_biometric');
    formData.append('credential_id', credentialId);
    formData.append('public_key', publicKey);
    formData.append('key_data', JSON.stringify({
      id: credentialId,
      type: credential.type,
      authenticatorType: 'platform'
    }));
    
    const response = await fetch('api_security_keys.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    closeBiometricModal();
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      setTimeout(() => location.reload(), 1500);
    }
  } catch (error) {
    console.error('Error Biometric:', error);
    statusEl.textContent = '❌ Error: ' + (error.message || 'Error al habilitar biometría');
    statusEl.className = 'text-sm text-red-600 dark:text-red-400 mb-4';
    
    // Show user-friendly error messages
    if (error.name === 'NotAllowedError') {
      showNotification('Autenticación cancelada o no permitida', 'error');
    } else if (error.name === 'NotSupportedError') {
      showNotification('Tu dispositivo no tiene sensor biométrico compatible', 'warning');
    } else {
      showNotification('Error al registrar biometría: ' + error.message, 'error');
    }
  }
}

// Notification Function for Security Keys (must be before delete functions)
function showNotification(message, type = 'info') {
  const colors = {
    success: 'bg-green-500',
    error: 'bg-red-500',
    info: 'bg-blue-500',
    warning: 'bg-yellow-500'
  };
  
  const icons = {
    success: 'check_circle',
    error: 'error',
    info: 'info',
    warning: 'warning'
  };
  
  const notif = document.createElement('div');
  notif.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-4 rounded-lg shadow-2xl z-[9999] flex items-center gap-3 animate-slide-in`;
  notif.innerHTML = `
    <span class="material-symbols-outlined">${icons[type]}</span>
    <span class="font-medium">${message}</span>
  `;
  
  document.body.appendChild(notif);
  
  setTimeout(() => {
    notif.style.opacity = '0';
    notif.style.transform = 'translateX(100%)';
    notif.style.transition = 'all 0.3s ease';
    setTimeout(() => notif.remove(), 300);
  }, 4000);
}

// Delete Functions
async function deleteSecurityKey(keyId) {
  if (!confirm('¿Estás seguro de eliminar esta llave de seguridad?')) {
    return;
  }
  
  const formData = new FormData();
  formData.append('action', 'delete_key');
  formData.append('key_id', keyId);
  
  try {
    const response = await fetch('api_security_keys.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      setTimeout(() => location.reload(), 1000);
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification('Error al eliminar llave', 'error');
  }
}

async function deleteTrustedDevice(deviceId) {
  if (!confirm('¿Estás seguro de eliminar este dispositivo de confianza?')) {
    return;
  }
  
  const formData = new FormData();
  formData.append('action', 'delete_trusted_device');
  formData.append('device_id', deviceId);
  
  try {
    const response = await fetch('api_security_keys.php', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    showNotification(result.message, result.success ? 'success' : 'error');
    
    if (result.success) {
      setTimeout(() => location.reload(), 1000);
    }
  } catch (error) {
    console.error('Error:', error);
    showNotification('Error al eliminar dispositivo', 'error');
  }
}
</script>
</div>
</div>
</div>
</div>

<!-- Tab Notificaciones -->
<div id="notificaciones" class="tab-content">
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722]">
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Configuración de Notificaciones</h2>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm mt-1">Selecciona qué notificaciones por correo electrónico deseas recibir.</p>
</div>
<form method="POST" action="" class="p-6 space-y-6 divide-y divide-slate-200 dark:divide-slate-700">
<div class="flex items-center justify-between pt-0">
<div>
<h3 class="font-medium text-slate-800 dark:text-slate-200">Notificaciones de Ventas</h3>
<p class="text-sm text-slate-500 dark:text-slate-400">Recibir notificaciones por correo cuando se realicen nuevas ventas.</p>
</div>
<label class="relative inline-flex cursor-pointer items-center">
<input name="email_ventas" type="checkbox" class="peer sr-only" <?php echo $email_ventas ? 'checked' : ''; ?>/>
<div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:border-slate-600 dark:bg-slate-700"></div>
</label>
</div>
<div class="flex items-center justify-between pt-6">
<div>
<h3 class="font-medium text-slate-800 dark:text-slate-200">Notificaciones de Deudas</h3>
<p class="text-sm text-slate-500 dark:text-slate-400">Recibir notificaciones cuando se registren nuevas deudas.</p>
</div>
<label class="relative inline-flex cursor-pointer items-center">
<input name="email_deudas" type="checkbox" class="peer sr-only" <?php echo $email_deudas ? 'checked' : ''; ?>/>
<div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:border-slate-600 dark:bg-slate-700"></div>
</label>
</div>
<div class="flex items-center justify-between pt-6">
<div>
<h3 class="font-medium text-slate-800 dark:text-slate-200">Notificaciones de Productos</h3>
<p class="text-sm text-slate-500 dark:text-slate-400">Recibir alertas cuando el stock de productos esté bajo.</p>
</div>
<label class="relative inline-flex cursor-pointer items-center">
<input name="email_productos" type="checkbox" class="peer sr-only" <?php echo $email_productos ? 'checked' : ''; ?>/>
<div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:border-slate-600 dark:bg-slate-700"></div>
</label>
</div>
<div class="flex justify-end gap-3 border-t border-slate-200 dark:border-[#232f48] bg-slate-50 dark:bg-black/20 p-4 rounded-b-xl -mx-6 -mb-6 mt-6">
<button type="submit" name="actualizar_notificaciones" class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold">Guardar Preferencias</button>
</div>
</form>
</div>
</div>

<!-- Tab Personalización -->
<div id="personalizacion" class="tab-content">
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722]">
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Personalización de Interfaz</h2>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm mt-1">Personaliza la apariencia y el comportamiento de la aplicación.</p>
</div>
<form method="POST" action="" class="p-6 space-y-6">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tema</label>
<select name="tema" class="form-select mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white">
<option value="light" <?php echo ($config_tema['tema'] ?? 'dark') === 'light' ? 'selected' : ''; ?>>Claro</option>
<option value="dark" <?php echo ($config_tema['tema'] ?? 'dark') === 'dark' ? 'selected' : ''; ?>>Oscuro</option>
<option value="auto" <?php echo ($config_tema['tema'] ?? 'dark') === 'auto' ? 'selected' : ''; ?>>Automático</option>
</select>
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Color Primario</label>
<input name="color_primario" type="color" value="<?php echo $config_tema['color_primario'] ?? '#1152d4'; ?>" class="form-input mt-1 w-full h-10 rounded-lg border-slate-300 dark:border-slate-600">
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Tamaño de Fuente</label>
<select name="tamano_fuente" class="form-select mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white">
<option value="small" <?php echo ($config_tema['tamano_fuente'] ?? 'medium') === 'small' ? 'selected' : ''; ?>>Pequeño</option>
<option value="medium" <?php echo ($config_tema['tamano_fuente'] ?? 'medium') === 'medium' ? 'selected' : ''; ?>>Mediano</option>
<option value="large" <?php echo ($config_tema['tamano_fuente'] ?? 'medium') === 'large' ? 'selected' : ''; ?>>Grande</option>
</select>
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Densidad de Interfaz</label>
<select name="densidad_interfaz" class="form-select mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white">
<option value="compact" <?php echo ($config_tema['densidad_interfaz'] ?? 'normal') === 'compact' ? 'selected' : ''; ?>>Compacta</option>
<option value="normal" <?php echo ($config_tema['densidad_interfaz'] ?? 'normal') === 'normal' ? 'selected' : ''; ?>>Normal</option>
<option value="spacious" <?php echo ($config_tema['densidad_interfaz'] ?? 'normal') === 'spacious' ? 'selected' : ''; ?>>Espaciosa</option>
</select>
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Idioma</label>
<select name="idioma" class="form-select mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white">
<option value="es" <?php echo ($config_tema['idioma'] ?? 'es') === 'es' ? 'selected' : ''; ?>>Español</option>
<option value="en" <?php echo ($config_tema['idioma'] ?? 'es') === 'en' ? 'selected' : ''; ?>>English</option>
</select>
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Formato de Fecha</label>
<select name="formato_fecha" class="form-select mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white">
<option value="d/m/Y" <?php echo ($config_tema['formato_fecha'] ?? 'd/m/Y') === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/AAAA</option>
<option value="m/d/Y" <?php echo ($config_tema['formato_fecha'] ?? 'd/m/Y') === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/AAAA</option>
<option value="Y-m-d" <?php echo ($config_tema['formato_fecha'] ?? 'd/m/Y') === 'Y-m-d' ? 'selected' : ''; ?>>AAAA-MM-DD</option>
</select>
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Formato de Hora</label>
<select name="formato_hora" class="form-select mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white">
<option value="H:i" <?php echo ($config_tema['formato_hora'] ?? 'H:i') === 'H:i' ? 'selected' : ''; ?>>24 horas (HH:MM)</option>
<option value="h:i A" <?php echo ($config_tema['formato_hora'] ?? 'H:i') === 'h:i A' ? 'selected' : ''; ?>>12 horas (HH:MM AM/PM)</option>
</select>
</div>
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Símbolo de Moneda</label>
<input name="formato_moneda" type="text" value="<?php echo $config_tema['formato_moneda'] ?? 'L.'; ?>" class="form-input mt-1 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white" placeholder="L.">
</div>
</div>
<div class="flex items-center gap-2">
<input type="checkbox" name="alto_contraste" id="alto_contraste" <?php echo ($config_tema['alto_contraste'] ?? 0) ? 'checked' : ''; ?> class="rounded">
<label for="alto_contraste" class="text-sm font-medium text-slate-700 dark:text-slate-300">Activar Modo de Alto Contraste</label>
</div>
<div class="flex justify-end gap-3 border-t border-slate-200 dark:border-[#232f48] bg-slate-50 dark:bg-black/20 p-4 rounded-b-xl -mx-6 -mb-6 mt-6">
<button type="submit" name="actualizar_tema" class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold">
Guardar Cambios
</button>
</div>
</form>
</div>
</div>

<!-- Tab Privacidad -->
<div id="privacidad" class="tab-content">
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722]">
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Configuración de Privacidad</h2>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm mt-1">Controla cómo se utilizan y almacenan tus datos.</p>
</div>
<form method="POST" action="" class="p-6 space-y-6 divide-y divide-slate-200 dark:divide-slate-700">
<div class="flex items-center justify-between pt-0">
<div>
<h3 class="font-medium text-slate-800 dark:text-slate-200">Compartir Datos de Analítica</h3>
<p class="text-sm text-slate-500 dark:text-slate-400">Ayúdanos a mejorar compartiendo datos anónimos de uso.</p>
</div>
<label class="relative inline-flex cursor-pointer items-center">
<input name="compartir_datos_analitica" type="checkbox" class="peer sr-only" <?php echo ($config_privacidad['compartir_datos_analitica'] ?? 1) ? 'checked' : ''; ?>>
<div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:border-slate-600 dark:bg-slate-700"></div>
</label>
</div>
<div class="flex items-center justify-between pt-6">
<div>
<h3 class="font-medium text-slate-800 dark:text-slate-200">Permitir Cookies de Terceros</h3>
<p class="text-sm text-slate-500 dark:text-slate-400">Permitir cookies de servicios externos.</p>
</div>
<label class="relative inline-flex cursor-pointer items-center">
<input name="permitir_cookies_terceros" type="checkbox" class="peer sr-only" <?php echo ($config_privacidad['permitir_cookies_terceros'] ?? 0) ? 'checked' : ''; ?>>
<div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:border-slate-600 dark:bg-slate-700"></div>
</label>
</div>
<div class="flex items-center justify-between pt-6">
<div>
<h3 class="font-medium text-slate-800 dark:text-slate-200">Mostrar en Directorio</h3>
<p class="text-sm text-slate-500 dark:text-slate-400">Aparecer en el directorio de usuarios del sistema.</p>
</div>
<label class="relative inline-flex cursor-pointer items-center">
<input name="mostrar_en_directorio" type="checkbox" class="peer sr-only" <?php echo ($config_privacidad['mostrar_en_directorio'] ?? 1) ? 'checked' : ''; ?>>
<div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:start-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-primary peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none dark:border-slate-600 dark:bg-slate-700"></div>
</label>
</div>
<div class="pt-6">
<label class="text-sm font-medium text-slate-700 dark:text-slate-300">Retención de Logs (días)</label>
<input name="retencion_logs_dias" type="number" min="30" max="365" value="<?php echo $config_privacidad['retencion_logs_dias'] ?? 90; ?>" class="form-input mt-1 w-full max-w-xs rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white">
<p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Los logs de auditoría se eliminarán automáticamente después de este período.</p>
</div>
<div class="flex justify-end gap-3 border-t border-slate-200 dark:border-[#232f48] bg-slate-50 dark:bg-black/20 p-4 rounded-b-xl -mx-6 -mb-6 mt-6">
<button type="submit" name="actualizar_privacidad" class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold">
Guardar Configuración
</button>
</div>
</form>
</div>

<!-- Sección GDPR -->
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722] mt-6">
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Derechos GDPR</h2>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm mt-1">Gestiona tus datos personales según el RGPD.</p>
</div>
<div class="p-6 space-y-4">
<div class="flex items-center justify-between p-4 rounded-lg bg-slate-50 dark:bg-slate-800/50">
<div>
<p class="font-medium text-slate-800 dark:text-slate-200">Exportar Mis Datos</p>
<p class="text-sm text-slate-500 dark:text-slate-400">Descarga una copia de todos tus datos personales en formato JSON.</p>
</div>
<form method="POST" action="" style="display:inline;">
<button type="submit" name="exportar_datos" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition">
<span class="material-symbols-outlined">download</span>
Exportar
</button>
</form>
</div>
<div class="flex items-center justify-between p-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
<div>
<p class="font-medium text-red-800 dark:text-red-200">Eliminar Mi Cuenta</p>
<p class="text-sm text-red-700 dark:text-red-300">Esta acción es permanente y no se puede deshacer.</p>
</div>
<button onclick="alert('Contacta al administrador para eliminar tu cuenta.')" class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition">
<span class="material-symbols-outlined">delete_forever</span>
Eliminar
</button>
</div>
</div>
</div>

<!-- Registro de Auditoría -->
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722] mt-6">
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Registro de Actividad</h2>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm mt-1">Historial de tus últimas acciones en el sistema.</p>
</div>
<div class="p-6">
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
<thead class="bg-slate-50 dark:bg-slate-800">
<tr>
<th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Fecha</th>
<th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Acción</th>
<th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Módulo</th>
<th class="px-4 py-2 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Detalles</th>
</tr>
</thead>
<tbody class="bg-white divide-y divide-slate-200 dark:bg-slate-900 dark:divide-slate-700">
<?php if (empty($audit_logs)): ?>
<tr>
<td colspan="4" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">No hay actividad reciente</td>
</tr>
<?php else: ?>
<?php foreach (array_slice($audit_logs, 0, 10) as $log): ?>
<tr>
<td class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
<td class="px-4 py-2 text-sm font-medium text-slate-800 dark:text-slate-200"><?php echo htmlspecialchars($log['accion']); ?></td>
<td class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($log['modulo']); ?></td>
<td class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400"><?php echo htmlspecialchars($log['detalles'] ?? '-'); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- Tab Aplicación (solo admin) -->
<?php if ($rol_usuario === 'admin'): ?>
<div id="aplicacion" class="tab-content">
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722]">
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em]">Configuración de la Aplicación</h2>
<p class="text-slate-500 dark:text-[#92a4c9] text-sm mt-1">Configura los parámetros generales del sistema.</p>
</div>
<form method="POST" action="" class="p-6">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="nombre_empresa">
<span class="flex items-center gap-2">
<span class="material-symbols-outlined text-lg">store</span>
Nombre de la Empresa
</span>
</label>
<input 
name="nombre_empresa" 
id="nombre_empresa" 
type="text" 
value="<?php echo htmlspecialchars($config_app['nombre_empresa'] ?? 'ReySystem Demo'); ?>" 
required
class="form-input mt-2 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white focus:border-primary focus:ring-primary/50"
/>
</div>

<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="email_empresa">
<span class="flex items-center gap-2">
<span class="material-symbols-outlined text-lg">email</span>
Correo de la Empresa
</span>
</label>
<input 
name="email_empresa" 
id="email_empresa" 
type="email" 
value="<?php echo htmlspecialchars($config_app['email_empresa'] ?? ''); ?>"
class="form-input mt-2 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white focus:border-primary focus:ring-primary/50"
/>
</div>

<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="telefono_empresa">
<span class="flex items-center gap-2">
<span class="material-symbols-outlined text-lg">phone</span>
Teléfono de la Empresa
</span>
</label>
<input 
name="telefono_empresa" 
id="telefono_empresa" 
type="tel" 
value="<?php echo htmlspecialchars($config_app['telefono_empresa'] ?? ''); ?>"
class="form-input mt-2 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white focus:border-primary focus:ring-primary/50"
/>
</div>

<div>
<label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="impuesto">
<span class="flex items-center gap-2">
<span class="material-symbols-outlined text-lg">percent</span>
Impuesto (%)
</span>
</label>
<input 
name="impuesto" 
id="impuesto" 
type="number" 
step="0.01" 
min="0" 
max="100" 
value="<?php echo $config_app['impuesto'] ?? 15.00; ?>" 
required
class="form-input mt-2 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white focus:border-primary focus:ring-primary/50"
/>
</div>

<div class="md:col-span-2">
<label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="direccion_empresa">
<span class="flex items-center gap-2">
<span class="material-symbols-outlined text-lg">location_on</span>
Dirección de la Empresa
</span>
</label>
<textarea 
name="direccion_empresa" 
id="direccion_empresa" 
rows="3"
class="form-input mt-2 w-full rounded-lg border-slate-300 dark:border-slate-600 bg-transparent dark:text-white focus:border-primary focus:ring-primary/50"
><?php echo htmlspecialchars($config_app['direccion_empresa'] ?? ''); ?></textarea>
</div>
</div>

<div class="flex justify-end gap-3 border-t border-slate-200 dark:border-[#232f48] bg-slate-50 dark:bg-black/20 p-4 rounded-b-xl -mx-6 -mb-6 mt-6">
<button type="submit" name="actualizar_config_app" class="flex min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold hover:bg-primary/90 transition">
<span class="material-symbols-outlined mr-2">save</span>
Guardar Configuración
</button>
</div>
</form>
</div>
</div>

<!-- Tab Sistema (solo admin) -->
<div id="sistema" class="tab-content">
<div class="rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#111722]">
<!-- Información del Sistema -->
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
<span class="material-symbols-outlined">info</span>
Información del Sistema
</h3>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="systemInfo">
<div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
<p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Versión Actual</p>
<p class="text-2xl font-bold text-slate-900 dark:text-white" id="currentVersion">Cargando...</p>
</div>
<div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
<p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Fecha de Lanzamiento</p>
<p class="text-lg font-semibold text-slate-900 dark:text-white" id="releaseDate">-</p>
</div>
<div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
<p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Build</p>
<p class="text-lg font-semibold text-slate-900 dark:text-white" id="buildNumber">-</p>
</div>
<div class="p-4 bg-slate-50 dark:bg-slate-800 rounded-lg">
<p class="text-sm text-slate-600 dark:text-slate-400 mb-1">Nombre Código</p>
<p class="text-lg font-semibold text-slate-900 dark:text-white" id="codename">-</p>
</div>
</div>
</div>

<!-- Buscar Actualizaciones -->
<div class="border-b border-slate-200 dark:border-[#232f48] p-6">
<h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
<span class="material-symbols-outlined">system_update</span>
Actualizaciones
</h3>
<div id="updateStatus" class="mb-4">
<p class="text-sm text-slate-600 dark:text-slate-400">Verifica si hay nuevas versiones disponibles</p>
</div>
<div class="flex gap-2">
<button onclick="checkForUpdates()" id="checkUpdateBtn"
class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
<span class="material-symbols-outlined">refresh</span>
Buscar Actualizaciones
</button>
<button onclick="debugSession()" 
class="px-4 py-2 bg-slate-600 hover:bg-slate-700 text-white rounded-lg font-medium transition-colors flex items-center gap-2">
<span class="material-symbols-outlined">bug_report</span>
Debug
</button>
</div>
</div>

<!-- Changelog -->
<div class="p-6">
<h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
<span class="material-symbols-outlined">history</span>
Historial de Cambios
</h3>
<div id="changelogContainer" class="space-y-4">
<p class="text-sm text-slate-600 dark:text-slate-400">Cargando historial...</p>
</div>
</div>
</div>
</div>
<?php endif; ?>
</div>
</main>
</div>
</div>
</div>

<script>
console.log('🚀 SCRIPT INICIADO - Si ves esto, JavaScript está funcionando');

document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Debug: verificar que se encuentran los elementos
    console.log('=== TAB DEBUG ===');
    console.log('Botones encontrados:', tabButtons.length);
    console.log('Contenidos encontrados:', tabContents.length);
    
    if (tabButtons.length === 0) {
        console.error('ERROR: No se encontraron botones con clase .tab-btn');
    }
    if (tabContents.length === 0) {
        console.error('ERROR: No se encontraron contenidos con clase .tab-content');
    }
    
    const successNotification = document.getElementById('successNotification');
    const errorNotification = document.getElementById('errorNotification');
    
    function showNotification(element) {
        if (!element) return;
        
        setTimeout(() => {
            element.classList.add('show');
            
            setTimeout(() => {
                element.classList.remove('show');
                
                setTimeout(() => {
                    element.style.display = 'none';
                }, 300);
            }, 5000);
        }, 500);
    }
    
    showNotification(successNotification);
    showNotification(errorNotification);
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const targetTab = this.getAttribute('data-tab');
            
            console.log('Tab clicked:', targetTab); // Debug
            
            // Remove active classes from all buttons
            tabButtons.forEach(btn => {
                btn.classList.remove('border-b-primary', 'text-primary', 'dark:text-white');
                btn.classList.add('border-b-transparent', 'text-slate-500', 'dark:text-slate-400');
            });
            
            // Add active classes to clicked button
            this.classList.remove('border-b-transparent', 'text-slate-500', 'dark:text-slate-400');
            this.classList.add('border-b-primary', 'text-primary', 'dark:text-white');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Show target tab content
            const targetContent = document.getElementById(targetTab);
            if (targetContent) {
                targetContent.classList.add('active');
                console.log('Tab activated:', targetTab); // Debug
            } else {
                console.error('Tab content not found:', targetTab); // Debug
            }
        });
    });
    
    const avatarInput = document.getElementById('avatarInput');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.style.backgroundImage = `url('${e.target.result}')`;
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    const nuevaPassword = document.getElementById('nueva');
    const confirmarPassword = document.getElementById('confirmar');
    
    if (nuevaPassword && confirmarPassword) {
        confirmarPassword.addEventListener('input', function() {
            if (this.value !== nuevaPassword.value) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});

// ===== SISTEMA DE ACTUALIZACIONES =====
async function loadSystemInfo() {
    try {
        // Agregar timestamp para evitar cache
        const response = await fetch('version.json?t=' + new Date().getTime());
        const data = await response.json();
        
        document.getElementById('currentVersion').textContent = 'v' + data.version;
        document.getElementById('releaseDate').textContent = data.release_date;
        document.getElementById('buildNumber').textContent = data.build;
        document.getElementById('codename').textContent = data.codename;
        
        // Cargar changelog
        const changelogContainer = document.getElementById('changelogContainer');
        changelogContainer.innerHTML = '';
        
        data.changelog.forEach(release => {
            const releaseDiv = document.createElement('div');
            releaseDiv.className = 'border border-slate-200 dark:border-slate-700 rounded-lg p-4';
            
            const typeColors = {
                major: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                minor: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                patch: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
            };
            
            releaseDiv.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-bold text-slate-900 dark:text-white">v${release.version}</span>
                        <span class="px-2 py-1 rounded text-xs font-medium ${typeColors[release.type] || typeColors.minor}">
                            ${release.type.toUpperCase()}
                        </span>
                    </div>
                    <span class="text-sm text-slate-500 dark:text-slate-400">${release.date}</span>
                </div>
                <ul class="space-y-1">
                    ${release.changes.map(change => `
                        <li class="text-sm text-slate-600 dark:text-slate-400 flex items-start gap-2">
                            <span class="material-symbols-outlined text-green-500 text-sm mt-0.5">check_circle</span>
                            <span>${change}</span>
                        </li>
                    `).join('')}
                </ul>
            `;
            
            changelogContainer.appendChild(releaseDiv);
        });
        
    } catch (error) {
        console.error('Error loading system info:', error);
        document.getElementById('currentVersion').textContent = 'Error';
    }
}

async function checkForUpdates() {
    const btn = document.getElementById('checkUpdateBtn');
    const statusDiv = document.getElementById('updateStatus');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span> Verificando...';
    
    try {
        const response = await fetch('check_updates.php?action=check');
        const data = await response.json();
        
        if (data.success) {
            if (data.update.available) {
                // Guardar datos de actualización
                currentUpdateData = data.update;
                
                statusDiv.innerHTML = `
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">system_update</span>
                            <div class="flex-1">
                                <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-1">
                                    Nueva versión disponible: v${data.update.latest_version}
                                </h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                                    ${data.update.release_name || 'Nueva versión'}
                                </p>
                                <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                                    📅 ${data.update.release_date} | 📦 ${data.update.size || 'Tamaño desconocido'} | 📄 ${data.update.file_type || 'zip'}
                                </p>
                                <div class="flex gap-2">
                                    <button onclick="downloadUpdate()" 
                                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                                        <span class="material-symbols-outlined text-sm">download</span>
                                        Descargar e Instalar
                                    </button>
                                    ${data.update.html_url ? `
                                        <a href="${data.update.html_url}" target="_blank"
                                            class="px-4 py-2 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-sm font-medium transition-colors hover:bg-slate-300 dark:hover:bg-slate-600 flex items-center gap-2">
                                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                                            Ver en GitHub
                                        </a>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                statusDiv.innerHTML = `
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
                            <p class="text-sm font-medium text-green-900 dark:text-green-100">
                                Estás usando la última versión (v${data.current.version})
                            </p>
                        </div>
                    </div>
                `;
            }
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        statusDiv.innerHTML = `
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-red-600 dark:text-red-400">error</span>
                    <p class="text-sm text-red-900 dark:text-red-100">
                        Error al verificar actualizaciones: ${error.message}
                    </p>
                </div>
            </div>
        `;
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined">refresh</span> Buscar Actualizaciones';
    }
}

let currentUpdateData = null;

async function debugSession() {
    const statusDiv = document.getElementById('updateStatus');
    
    try {
        const response = await fetch('check_updates.php?action=debug');
        const data = await response.json();
        
        statusDiv.innerHTML = `
            <div class="bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg p-4">
                <h4 class="font-bold text-slate-900 dark:text-white mb-2">Debug de Sesión</h4>
                <pre class="text-xs text-slate-600 dark:text-slate-400 overflow-auto">${JSON.stringify(data, null, 2)}</pre>
            </div>
        `;
    } catch (error) {
        statusDiv.innerHTML = `
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <p class="text-sm text-red-900 dark:text-red-100">Error: ${error.message}</p>
            </div>
        `;
    }
}

async function downloadUpdate() {
    if (!currentUpdateData || !currentUpdateData.download_url) {
        alert('No hay datos de actualización disponibles');
        return;
    }
    
    const statusDiv = document.getElementById('updateStatus');
    
    // Confirmar descarga
    if (!confirm(`¿Deseas descargar e instalar la versión ${currentUpdateData.latest_version}?\n\nSe creará un backup automático del sistema actual.`)) {
        return;
    }
    
    try {
        // Paso 1: Descargar
        statusDiv.innerHTML = `
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 animate-spin">download</span>
                    <div class="flex-1">
                        <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-1">Descargando actualización...</h4>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Esto puede tomar unos minutos. No cierres esta ventana.
                        </p>
                    </div>
                </div>
            </div>
        `;
        
        const downloadResponse = await fetch('check_updates.php?action=download', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `download_url=${encodeURIComponent(currentUpdateData.download_url)}&file_type=${encodeURIComponent(currentUpdateData.file_type || 'zip')}`
        });
        
        const downloadData = await downloadResponse.json();
        
        if (!downloadData.success) {
            throw new Error(downloadData.message);
        }
        
        // Paso 2: Instalar
        statusDiv.innerHTML = `
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 animate-spin">settings</span>
                    <div class="flex-1">
                        <h4 class="font-bold text-yellow-900 dark:text-yellow-100 mb-1">Instalando actualización...</h4>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            Creando backup y extrayendo archivos...
                        </p>
                    </div>
                </div>
            </div>
        `;
        
        const installResponse = await fetch('check_updates.php?action=install', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `filename=${encodeURIComponent(downloadData.file)}&file_type=${encodeURIComponent(downloadData.file_type || 'zip')}&new_version=${encodeURIComponent(currentUpdateData.latest_version)}&release_name=${encodeURIComponent(currentUpdateData.release_name || '')}`
        });
        
        const installData = await installResponse.json();
        
        if (!installData.success) {
            throw new Error(installData.message);
        }
        
        // Éxito
        statusDiv.innerHTML = `
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-green-600 dark:text-green-400">check_circle</span>
                    <div class="flex-1">
                        <h4 class="font-bold text-green-900 dark:text-green-100 mb-1">
                            ¡Actualización instalada correctamente!
                        </h4>
                        <p class="text-sm text-green-700 dark:text-green-300 mb-3">
                            Backup guardado: ${installData.backup}
                        </p>
                        <button onclick="location.reload()" 
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors">
                            Recargar Sistema
                        </button>
                    </div>
                </div>
            </div>
        `;
        
    } catch (error) {
        statusDiv.innerHTML = `
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <span class="material-symbols-outlined text-red-600 dark:text-red-400">error</span>
                    <div class="flex-1">
                        <h4 class="font-bold text-red-900 dark:text-red-100 mb-1">Error en actualización</h4>
                        <p class="text-sm text-red-700 dark:text-red-300">${error.message}</p>
                    </div>
                </div>
            </div>
        `;
    }
}

// Cargar información del sistema al abrir el tab
document.addEventListener('DOMContentLoaded', function() {
    const sistemaTab = document.querySelector('[data-tab="sistema"]');
    if (sistemaTab) {
        sistemaTab.addEventListener('click', function() {
            loadSystemInfo();
        });
    }
});

</script>
</body>
</html>