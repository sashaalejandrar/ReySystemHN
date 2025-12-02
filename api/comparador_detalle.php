<?php
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$codigo = $_GET['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(['success' => false, 'message' => 'Código requerido']);
    exit;
}

// Obtener datos del producto
$stmt = $conexion->prepare("SELECT Nombre_Producto, Precio_Unitario FROM stock WHERE Codigo_Producto = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();
$producto = $result->fetch_assoc();
$stmt->close();

if (!$producto) {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
    exit;
}

// Obtener precios de competencia
$stmt = $conexion->prepare("SELECT fuente, precio_competencia, url_producto, fecha_actualizacion 
                           FROM precios_competencia 
                           WHERE codigo_producto = ? 
                           AND fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                           ORDER BY precio_competencia ASC");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$result = $stmt->get_result();

$competidores = [];
$total = 0;
$count = 0;

while ($row = $result->fetch_assoc()) {
    $competidores[] = [
        'fuente' => $row['fuente'],
        'precio' => number_format($row['precio_competencia'], 2),
        'url' => $row['url_producto'],
        'fecha' => date('d/m/Y', strtotime($row['fecha_actualizacion']))
    ];
    $total += $row['precio_competencia'];
    $count++;
}
$stmt->close();

$promedio = $count > 0 ? $total / $count : 0;

echo json_encode([
    'success' => true,
    'producto' => [
        'codigo' => $codigo,
        'nombre' => $producto['Nombre_Producto'],
        'precio' => number_format($producto['Precio_Unitario'], 2)
    ],
    'competidores' => $competidores,
    'promedio' => number_format($promedio, 2),
    'total_fuentes' => $count
]);

$conexion->close();
?>
