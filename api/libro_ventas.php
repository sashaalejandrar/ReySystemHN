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

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Obtener ventas
$query = "SELECT 
    v.Id as factura,
    v.Fecha_Venta as fecha,
    v.Cliente as cliente,
    v.MetodoPago as metodo_pago,
    v.Total as total
    FROM ventas v
    WHERE DATE(v.Fecha_Venta) BETWEEN '$fecha_inicio' AND '$fecha_fin'
    ORDER BY v.Fecha_Venta DESC";

$result = $conexion->query($query);

$ventas = [];
$total_ventas = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $ventas[] = [
            'factura' => $row['factura'],
            'fecha' => $row['fecha'],
            'cliente' => $row['cliente'],
            'metodo_pago' => $row['metodo_pago'],
            'total' => floatval($row['total'])
        ];
        $total_ventas += floatval($row['total']);
    }
}

$num_facturas = count($ventas);
$promedio_factura = $num_facturas > 0 ? $total_ventas / $num_facturas : 0;

$conexion->close();

echo json_encode([
    'success' => true,
    'ventas' => $ventas,
    'total_ventas' => $total_ventas,
    'num_facturas' => $num_facturas,
    'promedio_factura' => $promedio_factura
]);
?>
