<?php
/**
 * Script para agregar columnas de tipo de precio a ventas_detalle
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "<h2>Migración: Agregar tipo_precio a ventas_detalle</h2>";

// Verificar si las columnas ya existen
$result = $conexion->query("SHOW COLUMNS FROM ventas_detalle LIKE 'tipo_precio_nombre'");
if ($result->num_rows > 0) {
    echo "<p style='color: orange;'>✓ La columna 'tipo_precio_nombre' ya existe</p>";
} else {
    // Agregar columna tipo_precio_nombre
    $sql = "ALTER TABLE ventas_detalle ADD COLUMN tipo_precio_nombre VARCHAR(100) DEFAULT 'Precio_Unitario' AFTER Precio";
    if ($conexion->query($sql)) {
        echo "<p style='color: green;'>✓ Columna 'tipo_precio_nombre' agregada exitosamente</p>";
    } else {
        echo "<p style='color: red;'>✗ Error al agregar 'tipo_precio_nombre': " . $conexion->error . "</p>";
    }
}

// Verificar si la columna tipo_precio_id ya existe
$result = $conexion->query("SHOW COLUMNS FROM ventas_detalle LIKE 'tipo_precio_id'");
if ($result->num_rows > 0) {
    echo "<p style='color: orange;'>✓ La columna 'tipo_precio_id' ya existe</p>";
} else {
    // Agregar columna tipo_precio_id
    $sql = "ALTER TABLE ventas_detalle ADD COLUMN tipo_precio_id INT DEFAULT 1 AFTER tipo_precio_nombre";
    if ($conexion->query($sql)) {
        echo "<p style='color: green;'>✓ Columna 'tipo_precio_id' agregada exitosamente</p>";
    } else {
        echo "<p style='color: red;'>✗ Error al agregar 'tipo_precio_id': " . $conexion->error . "</p>";
    }
}

echo "<h3>Migración completada</h3>";
echo "<p><a href='nueva_venta.php'>Volver a Nueva Venta</a></p>";

$conexion->close();
?>
