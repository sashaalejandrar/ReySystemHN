<?php
/**
 * Migration: Create egresos_archivos table
 * Purpose: Store receipt file references for expenses
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Migration: Create egresos_archivos table ===\n\n";

// Database connection
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error . "\n");
}

$conexion->set_charset("utf8mb4");

// Check if table already exists
$check_table = "SHOW TABLES LIKE 'egresos_archivos'";
$result = $conexion->query($check_table);

if ($result->num_rows > 0) {
    echo "✓ Table 'egresos_archivos' already exists. Skipping creation.\n";
} else {
    // Create egresos_archivos table
    $sql_create_table = "
    CREATE TABLE egresos_archivos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        egreso_id INT NOT NULL,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_archivo VARCHAR(500) NOT NULL,
        tipo_archivo VARCHAR(100) NOT NULL,
        tamano INT NOT NULL,
        fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (egreso_id) REFERENCES egresos_caja(id) ON DELETE CASCADE,
        INDEX idx_egreso_id (egreso_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conexion->query($sql_create_table)) {
        echo "✓ Table 'egresos_archivos' created successfully.\n";
    } else {
        die("✗ Error creating table: " . $conexion->error . "\n");
    }
}

// Check if confirmado column exists in egresos_caja
$check_column = "SHOW COLUMNS FROM egresos_caja LIKE 'confirmado'";
$result = $conexion->query($check_column);

if ($result->num_rows > 0) {
    echo "✓ Column 'confirmado' already exists in egresos_caja. Skipping.\n";
} else {
    // Add confirmado column to egresos_caja
    $sql_add_column = "
    ALTER TABLE egresos_caja 
    ADD COLUMN confirmado TINYINT(1) DEFAULT 0,
    ADD COLUMN confirmado_por INT NULL,
    ADD COLUMN fecha_confirmacion TIMESTAMP NULL,
    ADD INDEX idx_confirmado (confirmado)";

    if ($conexion->query($sql_add_column)) {
        echo "✓ Column 'confirmado' added to egresos_caja successfully.\n";
    } else {
        die("✗ Error adding column: " . $conexion->error . "\n");
    }
}

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/egresos/';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0777, true)) {
        echo "✓ Directory 'uploads/egresos/' created successfully.\n";
    } else {
        echo "✗ Warning: Could not create directory 'uploads/egresos/'. Please create it manually.\n";
    }
} else {
    echo "✓ Directory 'uploads/egresos/' already exists.\n";
}

$conexion->close();

echo "\n=== Migration completed successfully! ===\n";
?>
