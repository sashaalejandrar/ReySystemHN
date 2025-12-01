<?php
/**
 * Migration: Create Audit Log Table
 * Tracks all critical changes in the system
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

echo "=== Migration: Create Audit Log Table ===\n\n";

// Create auditoria table
$sql_auditoria = "
CREATE TABLE IF NOT EXISTS auditoria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tabla VARCHAR(100) NOT NULL COMMENT 'Table name where change occurred',
    registro_id INT NOT NULL COMMENT 'ID of the modified record',
    accion ENUM('crear', 'editar', 'eliminar') NOT NULL COMMENT 'Action type',
    campo_modificado VARCHAR(100) COMMENT 'Modified field name',
    valor_anterior TEXT COMMENT 'Previous value',
    valor_nuevo TEXT COMMENT 'New value',
    usuario_id INT NOT NULL COMMENT 'User who made the change',
    usuario_nombre VARCHAR(255) COMMENT 'User name for quick reference',
    ip_address VARCHAR(45) COMMENT 'IP address of user',
    user_agent TEXT COMMENT 'Browser/device info',
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'When change occurred',
    
    INDEX idx_tabla (tabla),
    INDEX idx_registro (registro_id),
    INDEX idx_accion (accion),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (fecha),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(Id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Audit log for tracking all system changes'
";

if ($conexion->query($sql_auditoria)) {
    echo "✓ Tabla 'auditoria' creada exitosamente.\n";
} else {
    die("✗ Error creando tabla auditoria: " . $conexion->error . "\n");
}

echo "\n=== Migration completed successfully! ===\n";
echo "Audit log system is ready to track changes.\n";

$conexion->close();
?>
