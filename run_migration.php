<?php
// Script para ejecutar la migración de base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Leer el archivo SQL
$sql = file_get_contents('migrations/configuracion_enhanced.sql');

// Dividir en consultas individuales
$queries = array_filter(array_map('trim', explode(';', $sql)));

$success = 0;
$errors = 0;

foreach ($queries as $query) {
    if (empty($query) || strpos($query, '--') === 0) {
        continue;
    }
    
    if ($conexion->query($query)) {
        $success++;
    } else {
        $errors++;
        echo "Error en consulta: " . $conexion->error . "\n";
        echo "Consulta: " . substr($query, 0, 100) . "...\n\n";
    }
}

echo "Migración completada:\n";
echo "- Consultas exitosas: $success\n";
echo "- Errores: $errors\n";

$conexion->close();
?>
