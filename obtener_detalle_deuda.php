<?php
header('Content-Type: application/json');
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$conexion->set_charset("utf8mb4");

$id = $_GET['id'] ?? '';
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$deuda = $conexion->query("SELECT nombreCliente FROM deudas WHERE idDeuda = '$id'")->fetch_assoc();
$detalle = [];

$res = $conexion->query("SELECT productoVendido, cantidad, precio FROM deudas_detalle WHERE idDeuda = '$id'");
while ($row = $res->fetch_assoc()) {
    $detalle[] = $row;
}

echo json_encode([
    'success' => true,
    'nombreCliente' => $deuda['nombreCliente'] ?? 'Desconocido',
    'detalle' => $detalle
]);
