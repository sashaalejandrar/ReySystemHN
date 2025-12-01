<?php
/**
 * Add Signature Field to Contracts Table
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("<h1>Error de conexión</h1><p>" . $conexion->connect_error . "</p>");
}

$conexion->set_charset("utf8mb4");

echo "<h1>Añadir Campo de Firma a Contratos</h1>";
echo "<hr>";

// Check if firma_empleado column exists
$check = $conexion->query("SHOW COLUMNS FROM contratos LIKE 'firma_empleado'");

if ($check->num_rows > 0) {
    echo "<p style='color: orange;'>✓ El campo 'firma_empleado' ya existe en la tabla contratos.</p>";
} else {
    // Add firma_empleado column
    $sql = "ALTER TABLE contratos ADD COLUMN firma_empleado TEXT AFTER contenido_adicional";
    
    if ($conexion->query($sql)) {
        echo "<p style='color: green;'><strong>✓ Campo 'firma_empleado' añadido exitosamente!</strong></p>";
        echo "<p>Este campo almacenará la firma electrónica del empleado en formato base64.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error añadiendo campo: " . $conexion->error . "</p>";
    }
}

$conexion->close();

echo "<hr>";
echo "<h2 style='color: green;'>✓ Proceso Completado!</h2>";
echo "<p>Ahora puedes capturar firmas electrónicas en los contratos.</p>";
echo "<p><a href='contratos.php' style='background: #1152d4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Ir a Contratos</a></p>";
?>
