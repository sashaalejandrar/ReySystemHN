<?php
/**
 * Migration: Create Mermas (Waste/Loss) Table
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

echo "=== Migration: Create Mermas Table ===\n\n";

// Create mermas table
$sql = "
CREATE TABLE IF NOT EXISTS mermas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL,
    producto_nombre VARCHAR(255) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    motivo ENUM('dañado', 'vencido', 'robo', 'otro') NOT NULL,
    descripcion TEXT,
    costo_unitario DECIMAL(10,2) NOT NULL,
    costo_total DECIMAL(10,2) NOT NULL,
    usuario_id INT NOT NULL,
    usuario_nombre VARCHAR(255),
    fecha DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_producto (producto_id),
    INDEX idx_motivo (motivo),
    INDEX idx_fecha (fecha),
    INDEX idx_usuario (usuario_id),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(Id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de mermas y pérdidas de productos'
";

if ($conexion->query($sql)) {
    echo "✓ Tabla 'mermas' creada exitosamente.\n";
} else {
    die("✗ Error creando tabla: " . $conexion->error . "\n");
}

echo "\n=== Migration completed! ===\n";
$conexion->close();
?>
