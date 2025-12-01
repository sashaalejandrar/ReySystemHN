<?php
// Script para agregar columnas sobrante y faltante a tablas de caja
require_once 'db_connect.php';

echo "<h2>Agregando columnas 'sobrante' y 'faltante' a tablas de caja...</h2>";

// 1. CAJA (para aperturas)
echo "<h3>1. Tabla caja</h3>";
$sql = "ALTER TABLE caja 
        ADD COLUMN IF NOT EXISTS sobrante DECIMAL(10,2) DEFAULT 0.00 AFTER usuario,
        ADD COLUMN IF NOT EXISTS faltante DECIMAL(10,2) DEFAULT 0.00 AFTER sobrante";
if ($conexion->query($sql)) {
    echo "✓ Columnas 'sobrante' y 'faltante' agregadas a caja<br>";
} else {
    echo "Info: " . $conexion->error . "<br>";
}

// 2. ARQUEO_CAJA
echo "<h3>2. Tabla arqueo_caja</h3>";
$sql = "ALTER TABLE arqueo_caja 
        ADD COLUMN IF NOT EXISTS sobrante DECIMAL(10,2) DEFAULT 0.00 AFTER usuario,
        ADD COLUMN IF NOT EXISTS faltante DECIMAL(10,2) DEFAULT 0.00 AFTER sobrante";
if ($conexion->query($sql)) {
    echo "✓ Columnas 'sobrante' y 'faltante' agregadas a arqueo_caja<br>";
} else {
    echo "Info: " . $conexion->error . "<br>";
}

// 3. CIERRE_CAJA
echo "<h3>3. Tabla cierre_caja</h3>";
$sql = "ALTER TABLE cierre_caja 
        ADD COLUMN IF NOT EXISTS sobrante DECIMAL(10,2) DEFAULT 0.00 AFTER usuario,
        ADD COLUMN IF NOT EXISTS faltante DECIMAL(10,2) DEFAULT 0.00 AFTER sobrante";
if ($conexion->query($sql)) {
    echo "✓ Columnas 'sobrante' y 'faltante' agregadas a cierre_caja<br>";
} else {
    echo "Info: " . $conexion->error . "<br>";
}

echo "<br><h2 style='color: green;'>✓ Proceso completado exitosamente!</h2>";
echo "<p>Las columnas sobrante y faltante han sido agregadas a las tres tablas.</p>";

$conexion->close();
?>
