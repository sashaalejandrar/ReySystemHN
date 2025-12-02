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

// Obtener ventas mayores a L. 1,000 (aplica retención del 1%)
$query = "SELECT 
    v.Id as factura,
    v.Fecha_Venta as fecha,
    v.Cliente as cliente,
    v.Total as monto
    FROM ventas v
    WHERE DATE(v.Fecha_Venta) BETWEEN '$fecha_inicio' AND '$fecha_fin'
    AND v.Total >= 1000
    ORDER BY v.Fecha_Venta DESC";

$result = $conexion->query($query);

$retenciones = [];
$total_retenciones = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $monto = floatval($row['monto']);
        $porcentaje = 1; // 1% de retención
        $retencion = $monto * ($porcentaje / 100);
        
        $retenciones[] = [
            'fecha' => $row['fecha'],
            'factura' => $row['factura'],
            'cliente' => $row['cliente'],
            'monto' => $monto,
            'porcentaje' => $porcentaje,
            'retencion' => $retencion
        ];
        
        $total_retenciones += $retencion;
    }
}

$num_transacciones = count($retenciones);

$conexion->close();

echo json_encode([
    'success' => true,
    'retenciones' => $retenciones,
    'total_retenciones' => $total_retenciones,
    'num_transacciones' => $num_transacciones
]);
?>
