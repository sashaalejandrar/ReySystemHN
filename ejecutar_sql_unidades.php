<?php
// Script para ejecutar la creación de unidades de medida
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

// Leer el archivo SQL
$sql = file_get_contents(__DIR__ . '/sql/crear_unidades_medida.sql');

// Dividir en statements individuales
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        return !empty($stmt) && !preg_match('/^--/', $stmt);
    }
);

$errores = [];
$exitosos = 0;

foreach ($statements as $statement) {
    if (empty(trim($statement))) continue;
    
    if ($conexion->query($statement)) {
        $exitosos++;
    } else {
        $errores[] = "Error: " . $conexion->error . "\nSQL: " . substr($statement, 0, 100) . "...";
    }
}

$conexion->close();

echo "✅ Statements exitosos: $exitosos\n";
if (!empty($errores)) {
    echo "❌ Errores encontrados:\n";
    foreach ($errores as $error) {
        echo $error . "\n\n";
    }
    exit(1);
} else {
    echo "✅ Base de datos actualizada correctamente\n";
    exit(0);
}
?>
