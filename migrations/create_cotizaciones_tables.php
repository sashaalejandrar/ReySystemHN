<?php
/**
 * Migration: Create Cotizaciones (Quotations) Tables
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

echo "=== Migration: Create Cotizaciones Tables ===\n\n";

// Create cotizaciones table
$sql_cotizaciones = "
CREATE TABLE IF NOT EXISTS cotizaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_cotizacion VARCHAR(50) UNIQUE NOT NULL,
    cliente_nombre VARCHAR(255) NOT NULL,
    cliente_telefono VARCHAR(20),
    cliente_email VARCHAR(255),
    fecha DATE NOT NULL,
    vigencia_dias INT DEFAULT 15,
    fecha_vencimiento DATE,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    descuento DECIMAL(10,2) DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    estado ENUM('pendiente', 'aprobada', 'rechazada', 'convertida', 'vencida') DEFAULT 'pendiente',
    notas TEXT,
    usuario_id INT NOT NULL,
    usuario_nombre VARCHAR(255),
    venta_id INT NULL COMMENT 'ID de venta si fue convertida',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_numero (numero_cotizacion),
    INDEX idx_cliente (cliente_nombre),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fecha),
    INDEX idx_usuario (usuario_id),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(Id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

if ($conexion->query($sql_cotizaciones)) {
    echo "✓ Tabla 'cotizaciones' creada exitosamente.\n";
} else {
    die("✗ Error creando tabla cotizaciones: " . $conexion->error . "\n");
}

// Create cotizaciones_items table
$sql_items = "
CREATE TABLE IF NOT EXISTS cotizaciones_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cotizacion_id INT NOT NULL,
    producto_id INT NOT NULL,
    producto_nombre VARCHAR(255) NOT NULL,
    cantidad DECIMAL(10,2) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_cotizacion (cotizacion_id),
    INDEX idx_producto (producto_id),
    
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

if ($conexion->query($sql_items)) {
    echo "✓ Tabla 'cotizaciones_items' creada exitosamente.\n";
} else {
    die("✗ Error creando tabla cotizaciones_items: " . $conexion->error . "\n");
}

echo "\n=== Migration completed! ===\n";
$conexion->close();
?>
