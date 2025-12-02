<?php
// Script para agregar columnas de proveedor a la tabla creacion_de_productos
$conn = new mysqli("localhost", "root", "", "tiendasrey");

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

echo "Agregando columnas de proveedor...\n";

// Agregar columna Proveedor
$sql1 = "ALTER TABLE creacion_de_productos ADD COLUMN IF NOT EXISTS Proveedor VARCHAR(255) DEFAULT NULL AFTER FotoProducto";
if ($conn->query($sql1)) {
    echo "✓ Columna 'Proveedor' agregada\n";
} else {
    echo "Info: " . $conn->error . "\n";
}

// Agregar columna DireccionProveedor
$sql2 = "ALTER TABLE creacion_de_productos ADD COLUMN IF NOT EXISTS DireccionProveedor TEXT DEFAULT NULL AFTER Proveedor";
if ($conn->query($sql2)) {
    echo "✓ Columna 'DireccionProveedor' agregada\n";
} else {
    echo "Info: " . $conn->error . "\n";
}

// Agregar columna ContactoProveedor
$sql3 = "ALTER TABLE creacion_de_productos ADD COLUMN IF NOT EXISTS ContactoProveedor VARCHAR(100) DEFAULT NULL AFTER DireccionProveedor";
if ($conn->query($sql3)) {
    echo "✓ Columna 'ContactoProveedor' agregada\n";
} else {
    echo "Info: " . $conn->error . "\n";
}

echo "\n¡Columnas agregadas exitosamente!\n";

$conn->close();
?>
