<?php
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

$termino = $_GET['termino'] ?? '';

if (strlen($termino) < 2) {
    echo json_encode([]);
    exit;
}

$termino = '%' . $termino . '%';

$stmt = $conexion->prepare("
    SELECT Id, Codigo_Producto, Nombre_Producto, Stock, Precio_Unitario, Marca, Grupo
    FROM stock 
    WHERE Codigo_Producto LIKE ? OR Nombre_Producto LIKE ?
    LIMIT 10
");

$stmt->bind_param("ss", $termino, $termino);
$stmt->execute();
$resultado = $stmt->get_result();

$productos = [];
while ($row = $resultado->fetch_assoc()) {
    $productos[] = $row;
}

echo json_encode($productos);

$stmt->close();
$conexion->close();
?>
