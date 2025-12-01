<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$conexion->set_charset("utf8mb4");

$mes = $_GET['mes'] ?? date('n');
$anio = $_GET['anio'] ?? date('Y');

// Total de ventas del mes
$query_ventas = "SELECT SUM(Total) as total FROM ventas 
    WHERE MONTH(Fecha_Venta) = $mes AND YEAR(Fecha_Venta) = $anio";
$result = $conexion->query($query_ventas);
$total_ventas = floatval($result->fetch_assoc()['total'] ?? 0);

// Total de compras/egresos del mes
$query_compras = "SELECT SUM(Monto) as total FROM egresos_caja 
    WHERE MONTH(fecha_registro) = $mes AND YEAR(fecha_registro) = $anio";
$result = $conexion->query($query_compras);
$total_compras = floatval($result->fetch_assoc()['total'] ?? 0);

// Cálculos de ISV (15%)
$base_imponible = $total_ventas / 1.15; // Asumiendo que ventas incluyen ISV
$isv_cobrado = $total_ventas - $base_imponible;
$isv_pagado = $total_compras * 0.15; // 15% de las compras
$isv_a_pagar = $isv_cobrado - $isv_pagado;

$conexion->close();

echo json_encode([
    'success' => true,
    'total_ventas' => $total_ventas,
    'base_imponible' => $base_imponible,
    'isv_cobrado' => $isv_cobrado,
    'total_compras' => $total_compras,
    'isv_pagado' => $isv_pagado,
    'isv_a_pagar' => $isv_a_pagar
]);
?>
