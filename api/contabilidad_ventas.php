<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$conexion->set_charset("utf8mb4");

// Obtener parámetros
$periodo = $_GET['periodo'] ?? 'hoy';
$fecha_inicio = $_GET['fecha_inicio'] ?? null;
$fecha_fin = $_GET['fecha_fin'] ?? null;

// Determinar rango de fechas
$where_fecha = "";
switch($periodo) {
    case 'hoy':
        $where_fecha = "DATE(Fecha_Venta) = CURDATE()";
        break;
    case 'semana':
        $where_fecha = "YEARWEEK(Fecha_Venta, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'mes':
        $where_fecha = "MONTH(Fecha_Venta) = MONTH(CURDATE()) AND YEAR(Fecha_Venta) = YEAR(CURDATE())";
        break;
    case 'anio':
        $where_fecha = "YEAR(Fecha_Venta) = YEAR(CURDATE())";
        break;
    case 'personalizado':
        if ($fecha_inicio && $fecha_fin) {
            $where_fecha = "DATE(Fecha_Venta) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        } else {
            $where_fecha = "DATE(Fecha_Venta) = CURDATE()";
        }
        break;
    default:
        $where_fecha = "DATE(Fecha_Venta) = CURDATE()";
}

// Obtener ventas
$query = "SELECT 
    Factura_Id as factura,
    Cliente as cliente,
    Fecha_Venta as fecha,
    MetodoPago as metodo_pago,
    Total as total,
    Vendedor as vendedor
    FROM ventas 
    WHERE $where_fecha
    ORDER BY Fecha_Venta DESC
    LIMIT 100";

$result = $conexion->query($query);

$ventas = [];
while ($row = $result->fetch_assoc()) {
    $ventas[] = [
        'factura' => $row['factura'],
        'cliente' => $row['cliente'],
        'fecha' => $row['fecha'],
        'metodo_pago' => $row['metodo_pago'],
        'total' => floatval($row['total']),
        'vendedor' => $row['vendedor']
    ];
}

$conexion->close();

echo json_encode([
    'success' => true,
    'ventas' => $ventas
]);
?>
