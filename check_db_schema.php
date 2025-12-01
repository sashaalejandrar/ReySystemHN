<?php
// Quick script to check database schema
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error: " . $conexion->connect_error);
}

echo "=== EGRESOS_CAJA TABLE ===\n";
$result = $conexion->query("DESCRIBE egresos_caja");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']}\n";
    }
} else {
    echo "Table does not exist\n";
}

echo "\n=== EGRESOS_ARCHIVOS TABLE ===\n";
$result = $conexion->query("DESCRIBE egresos_archivos");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']}\n";
    }
} else {
    echo "Table does not exist - will need to create it\n";
}

$conexion->close();
?>
