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

$anio = $_GET['anio'] ?? date('Y');

// Obtener ventas por mes
$meses = [];
$meses_nombres = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

for ($i = 1; $i <= 12; $i++) {
    $mes_num = str_pad($i, 2, '0', STR_PAD_LEFT);
    
    // Total por método de pago
    $query = "SELECT 
        SUM(CASE WHEN MetodoPago = 'Efectivo' THEN Total ELSE 0 END) as efectivo,
        SUM(CASE WHEN MetodoPago = 'Tarjeta' THEN Total ELSE 0 END) as tarjeta,
        SUM(CASE WHEN MetodoPago = 'Transferencia' THEN Total ELSE 0 END) as transferencia,
        SUM(Total) as total,
        COUNT(*) as cantidad
        FROM ventas
        WHERE YEAR(Fecha_Venta) = $anio AND MONTH(Fecha_Venta) = $i";
    
    $result = $conexion->query($query);
    $row = $result->fetch_assoc();
    
    $meses[] = [
        'numero' => $i,
        'nombre' => $meses_nombres[$i-1],
        'efectivo' => floatval($row['efectivo'] ?? 0),
        'tarjeta' => floatval($row['tarjeta'] ?? 0),
        'transferencia' => floatval($row['transferencia'] ?? 0),
        'total' => floatval($row['total'] ?? 0),
        'cantidad' => intval($row['cantidad'] ?? 0)
    ];
}

// Calcular totales
$total_anual = array_sum(array_column($meses, 'total'));
$promedio_mensual = $total_anual / 12;
$total_ventas = array_sum(array_column($meses, 'cantidad'));

// Encontrar mejor mes
$mejor_mes = 'Ninguno';
$max_total = 0;
foreach ($meses as $mes) {
    if ($mes['total'] > $max_total) {
        $max_total = $mes['total'];
        $mejor_mes = $mes['nombre'];
    }
}

$conexion->close();

echo json_encode([
    'success' => true,
    'meses' => $meses,
    'total_anual' => $total_anual,
    'promedio_mensual' => $promedio_mensual,
    'mejor_mes' => $mejor_mes,
    'total_ventas' => $total_ventas
]);
?>
