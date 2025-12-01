<?php
require_once 'config.php';

echo "=== VERIFICACIÓN DE PRODUCTOS ===\n\n";

// Ver productos con código
$query = "SELECT Codigo_Producto, Nombre_Producto, Descripcion FROM stock WHERE Codigo_Producto IS NOT NULL AND Codigo_Producto != '' AND Stock > 0 LIMIT 5";
$result = $conexion->query($query);

echo "Productos con código de barras:\n";
echo "--------------------------------\n";
while ($row = $result->fetch_assoc()) {
    echo "Código: " . $row['Codigo_Producto'] . "\n";
    echo "Nombre: " . $row['Nombre_Producto'] . "\n";
    echo "Descripción: " . ($row['Descripcion'] ?? 'N/A') . "\n";
    echo "--------------------------------\n";
}

// Ver total de productos
$query = "SELECT COUNT(*) as total FROM stock WHERE Stock > 0";
$result = $conexion->query($query);
$row = $result->fetch_assoc();
echo "\nTotal productos con stock: " . $row['total'] . "\n";

// Ver total con código
$query = "SELECT COUNT(*) as total FROM stock WHERE Codigo_Producto IS NOT NULL AND Codigo_Producto != '' AND Stock > 0";
$result = $conexion->query($query);
$row = $result->fetch_assoc();
echo "Total con código de barras: " . $row['total'] . "\n";

$conexion->close();
?>
