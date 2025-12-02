<?php
/**
 * Script para crear la tabla updates
 * Accede desde: http://localhost/ReySystemDemo/setup_releases_table.php
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("❌ Error de conexión: " . $conexion->connect_error);
}

echo "<h2>Setup de Tabla Updates</h2>";
echo "<pre>";

// Leer y ejecutar SQL
$sql = file_get_contents('create_updates_table.sql');

if ($conexion->multi_query($sql)) {
    echo "✅ Tabla 'updates' creada exitosamente\n\n";
    
    // Esperar a que termine
    while ($conexion->next_result()) {;}
    
    // Verificar estructura
    $result = $conexion->query("DESCRIBE updates");
    
    if ($result) {
        echo "📋 Estructura de la tabla:\n\n";
        while ($row = $result->fetch_assoc()) {
            echo "  - {$row['Field']} ({$row['Type']}) {$row['Null']} {$row['Key']}\n";
        }
    }
    
    echo "\n✅ Setup completado!\n";
    echo "\n<a href='gestionar_releases.php'>Ir a Gestionar Releases</a>";
    
} else {
    echo "❌ Error al crear tabla: " . $conexion->error . "\n";
}

echo "</pre>";

$conexion->close();
?>
