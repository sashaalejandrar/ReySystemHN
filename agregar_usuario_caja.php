<?php
// Script para agregar columna usuario a tablas de caja y poblarla
require_once 'db_connect.php';

echo "<h2>Agregando columna 'usuario' a tablas de caja...</h2>";

// 1. CAJA (para aperturas)
echo "<h3>1. Tabla caja</h3>";
$sql = "ALTER TABLE caja ADD COLUMN IF NOT EXISTS usuario VARCHAR(50) DEFAULT NULL AFTER Nota";
if ($conexion->query($sql)) {
    echo "✓ Columna 'usuario' agregada a caja<br>";
} else {
    echo "Info: " . $conexion->error . "<br>";
}

// Poblar con datos existentes basándose en Nota
$update = "UPDATE caja SET usuario = SUBSTRING_INDEX(SUBSTRING_INDEX(Nota, ' - ', -1), ' ', 1) WHERE usuario IS NULL AND Nota IS NOT NULL AND Nota != ''";
$conexion->query($update);
echo "✓ Datos poblados en caja<br>";

// 2. ARQUEO_CAJA
echo "<h3>2. Tabla arqueo_caja</h3>";
$sql = "ALTER TABLE arqueo_caja ADD COLUMN IF NOT EXISTS usuario VARCHAR(50) DEFAULT NULL AFTER Nota_justi";
if ($conexion->query($sql)) {
    echo "✓ Columna 'usuario' agregada a arqueo_caja<br>";
} else {
    echo "Info: " . $conexion->error . "<br>";
}

// Poblar con datos existentes basándose en Nota_justi
$update = "UPDATE arqueo_caja SET usuario = SUBSTRING_INDEX(SUBSTRING_INDEX(Nota_justi, ' - ', -1), ' ', 1) WHERE usuario IS NULL AND Nota_justi IS NOT NULL AND Nota_justi != ''";
$conexion->query($update);
echo "✓ Datos poblados en arqueo_caja<br>";

// 3. CIERRE_CAJA
echo "<h3>3. Tabla cierre_caja</h3>";
$sql = "ALTER TABLE cierre_caja ADD COLUMN IF NOT EXISTS usuario VARCHAR(50) DEFAULT NULL AFTER Nota_Justifi";
if ($conexion->query($sql)) {
    echo "✓ Columna 'usuario' agregada a cierre_caja<br>";
} else {
    echo "Info: " . $conexion->error . "<br>";
}

// Poblar con datos existentes basándose en Nota_Justifi
$update = "UPDATE cierre_caja SET usuario = SUBSTRING_INDEX(SUBSTRING_INDEX(Nota_Justifi, ' - ', -1), ' ', 1) WHERE usuario IS NULL AND Nota_Justifi IS NOT NULL AND Nota_Justifi != ''";
$conexion->query($update);
echo "✓ Datos poblados en cierre_caja<br>";

echo "<br><h2 style='color: green;'>✓ Proceso completado exitosamente!</h2>";
echo "<p>Ahora puedes actualizar los archivos PHP para insertar el usuario logueado.</p>";

$conexion->close();
?>
