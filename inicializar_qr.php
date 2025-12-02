<?php
/**
 * Script de inicialización para el sistema de registro por QR
 * Crea la tabla de tokens de registro
 */

require_once 'db_connect.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Inicializar Sistema QR</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-radius: 5px; margin: 10px 0; }
        h1 { color: #1152d4; }
    </style>
</head>
<body>
    <h1>📱 Inicialización del Sistema de Registro por QR</h1>";

// Crear tabla de tokens
$sql = "
CREATE TABLE IF NOT EXISTS tokens_registro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token VARCHAR(64) UNIQUE NOT NULL,
    usado TINYINT(1) DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_uso DATETIME NULL,
    cliente_registrado VARCHAR(255) NULL,
    ip_registro VARCHAR(45) NULL,
    INDEX idx_token (token),
    INDEX idx_usado (usado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conexion->query($sql)) {
    echo "<div class='success'>✅ Tabla 'tokens_registro' creada exitosamente</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conexion->error . "</div>";
}

echo "<div class='success'>
    <strong>¡Sistema de QR inicializado correctamente!</strong><br><br>
    <a href='generar_qr.php' style='color: #1152d4; text-decoration: none; font-weight: bold;'>
        → Ir a Generar QR
    </a>
</div>";

echo "</body></html>";

$conexion->close();
?>
