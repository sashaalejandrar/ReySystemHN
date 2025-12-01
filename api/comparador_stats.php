<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Tegucigalpa');

// Conexión directa
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Obtener estadísticas
$total = 0;
$comparados = 0;

$result = $conexion->query("SELECT COUNT(*) as total FROM stock WHERE Stock > 0");
if ($row = $result->fetch_assoc()) {
    $total = $row['total'];
}

$result = $conexion->query("SELECT COUNT(*) as comparados FROM precios_competencia");
if ($row = $result->fetch_assoc()) {
    $comparados = $row['comparados'];
}

echo json_encode([
    'success' => true,
    'total' => $total,
    'comparados' => $comparados
]);

$conexion->close();
?>
