<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// INGRESOS - Total de ventas
$query_ventas = "SELECT SUM(Total) as total FROM ventas WHERE DATE(Fecha_Venta) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
$result = $conexion->query($query_ventas);
$ventas = floatval($result->fetch_assoc()['total'] ?? 0);

// COSTO DE PRODUCTOS VENDIDOS - Calcular desde ventas_detalle
$query_costo = "SELECT SUM(vd.Cantidad * COALESCE(cp.CostoPorEmpaque, 0)) as costo
    FROM ventas_detalle vd
    INNER JOIN ventas v ON vd.Id_Venta = v.Id
    LEFT JOIN creacion_de_productos cp ON vd.Producto_Vendido = cp.NombreProducto
    WHERE DATE(v.Fecha_Venta) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
$result = $conexion->query($query_costo);
$costo_productos = floatval($result->fetch_assoc()['costo'] ?? 0);

// GASTOS OPERATIVOS - Egresos de caja
$query_egresos = "SELECT SUM(Monto) as total FROM egresos_caja WHERE DATE(fecha_registro) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
$result = $conexion->query($query_egresos);
$egresos = floatval($result->fetch_assoc()['total'] ?? 0);

// CÁLCULOS
$total_ingresos = $ventas;
$utilidad_bruta = $ventas - $costo_productos;
$total_gastos = $egresos;
$utilidad_neta = $utilidad_bruta - $total_gastos;
$margen_utilidad = $ventas > 0 ? ($utilidad_neta / $ventas) * 100 : 0;

$conexion->close();

echo json_encode([
    'success' => true,
    'ventas' => $ventas,
    'total_ingresos' => $total_ingresos,
    'costo_productos' => $costo_productos,
    'utilidad_bruta' => $utilidad_bruta,
    'egresos' => $egresos,
    'total_gastos' => $total_gastos,
    'utilidad_neta' => $utilidad_neta,
    'margen_utilidad' => $margen_utilidad
]);
?>
