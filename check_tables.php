<?php
require_once 'db_connect.php';

echo "<h3>Estructura de tabla clientes:</h3>";
$result = $conexion->query("DESCRIBE clientes");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
echo "</pre>";

echo "<h3>Estructura de tabla deudas:</h3>";
$result = $conexion->query("DESCRIBE deudas");
echo "<pre>";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
echo "</pre>";

$conexion->close();
?>
