<?php
require_once '../config.php';

echo "=== VERIFICACIÓN Y ARREGLO DE TABLA comparacion_precios ===\n\n";

// Verificar si la tabla existe
$result = $conexion->query("SHOW TABLES LIKE 'comparacion_precios'");
if ($result->num_rows == 0) {
    echo "❌ Tabla comparacion_precios no existe. Creándola...\n";
    
    $sql = "CREATE TABLE comparacion_precios (
        Codigo_Producto VARCHAR(50) PRIMARY KEY,
        Precio_Competencia DECIMAL(10,2),
        Fuente_Competencia VARCHAR(100),
        URL_Producto TEXT,
        Diferencia_Porcentual DECIMAL(10,2),
        Fecha_Actualizacion DATETIME,
        INDEX idx_fecha (Fecha_Actualizacion)
    )";
    
    if ($conexion->query($sql)) {
        echo "✅ Tabla creada exitosamente\n";
    } else {
        echo "❌ Error creando tabla: " . $conexion->error . "\n";
    }
} else {
    echo "✅ Tabla comparacion_precios existe\n";
}

// Verificar estructura
echo "\n--- Estructura de la tabla ---\n";
$result = $conexion->query("DESCRIBE comparacion_precios");
while ($row = $result->fetch_assoc()) {
    echo "{$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']}\n";
}

// Verificar datos
echo "\n--- Datos en la tabla ---\n";
$result = $conexion->query("SELECT COUNT(*) as total FROM comparacion_precios");
$row = $result->fetch_assoc();
echo "Total registros: {$row['total']}\n";

if ($row['total'] > 0) {
    echo "\n--- Últimos 5 registros ---\n";
    $result = $conexion->query("SELECT * FROM comparacion_precios ORDER BY Fecha_Actualizacion DESC LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        echo "Código: {$row['Codigo_Producto']} | Precio: {$row['Precio_Competencia']} | Fuente: {$row['Fuente_Competencia']}\n";
    }
}

$conexion->close();
?>
