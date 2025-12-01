<?php
/**
 * Script para crear la tabla pin_security
 * Accede desde el navegador: http://localhost/ReySystemDemo/setup_pin_security.php
 */

// Intentar diferentes configuraciones de conexión
$conexion = @new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    // Intentar con socket de XAMPP
    $conexion = @new mysqli("localhost", "root", "", "tiendasrey", 3306, "/opt/lampp/var/mysql/mysql.sock");
}

if ($conexion->connect_error) {
    // Intentar con 127.0.0.1
    $conexion = @new mysqli("127.0.0.1", "root", "", "tiendasrey");
}

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "<h2>Setup de Tablas de Seguridad</h2>";
echo "<pre>";
echo "Creando tabla pin_security...\n";

$sql = "CREATE TABLE IF NOT EXISTS `pin_security` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` varchar(50) NOT NULL,
  `pin_hash` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idUsuario` (`idUsuario`),
  KEY `idx_usuario_enabled` (`idUsuario`, `enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conexion->query($sql)) {
    echo "✅ Tabla pin_security creada exitosamente\n";
} else {
    echo "❌ Error al crear tabla: " . $conexion->error . "\n";
}

// Verificar si la tabla trusted_devices existe
echo "\nVerificando tabla trusted_devices...\n";

$sql_trusted = "CREATE TABLE IF NOT EXISTS `trusted_devices` (
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
  KEY `idx_user` (`user_id`),
  KEY `idx_token` (`device_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conexion->query($sql_trusted)) {
    echo "✅ Tabla trusted_devices verificada/creada exitosamente\n";
} else {
    echo "❌ Error al crear tabla trusted_devices: " . $conexion->error . "\n";
}

// Verificar si la tabla security_keys existe y tiene las columnas correctas
echo "\nVerificando tabla security_keys...\n";

$sql_security_keys = "CREATE TABLE IF NOT EXISTS `security_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` varchar(50) NOT NULL,
  `key_type` varchar(50) NOT NULL COMMENT 'webauthn, biometric, pin',
  `key_name` varchar(255) DEFAULT NULL,
  `credential_id` text DEFAULT NULL,
  `public_key` text DEFAULT NULL,
  `key_data` text DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`idUsuario`),
  KEY `idx_usuario_enabled` (`idUsuario`, `enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conexion->query($sql_security_keys)) {
    echo "✅ Tabla security_keys verificada/creada exitosamente\n";
} else {
    echo "❌ Error al crear tabla security_keys: " . $conexion->error . "\n";
}

echo "\n✅ Setup completado!\n";
echo "</pre>";
echo "<p><a href='login.php'>Ir al Login</a></p>";

$conexion->close();
?>
