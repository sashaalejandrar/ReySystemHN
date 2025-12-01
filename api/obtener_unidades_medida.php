<?php
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$conexion->set_charset("utf8mb4");

// Obtener todas las unidades activas
$query = "SELECT * FROM unidades_medida WHERE activo = TRUE ORDER BY 
    CASE tipo
        WHEN 'cantidad' THEN 1
        WHEN 'peso' THEN 2
        WHEN 'volumen' THEN 3
        WHEN 'empaque' THEN 4
    END,
    nombre ASC";

$result = $conexion->query($query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Error en la consulta']);
    exit;
}

$unidades = [];
while ($row = $result->fetch_assoc()) {
    $unidades[] = $row;
}

// Agrupar por tipo
$agrupadas = [
    'cantidad' => [],
    'peso' => [],
    'volumen' => [],
    'empaque' => []
];

foreach ($unidades as $unidad) {
    $agrupadas[$unidad['tipo']][] = $unidad;
}

$conexion->close();

echo json_encode([
    'success' => true,
    'unidades' => $unidades,
    'agrupadas' => $agrupadas
]);
?>
