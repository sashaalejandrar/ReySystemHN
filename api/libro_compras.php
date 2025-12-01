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

// Obtener egresos (compras y gastos)
$query = "SELECT 
    e.fecha_registro as fecha,
    e.Concepto as concepto,
    e.Tipo as tipo,
    e.Monto as monto,
    e.Usuario as usuario
    FROM egresos_caja e
    WHERE DATE(e.fecha_registro) BETWEEN '$fecha_inicio' AND '$fecha_fin'
    ORDER BY e.fecha_registro DESC";

$result = $conexion->query($query);

$egresos = [];
$total_compras = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $egresos[] = [
            'fecha' => $row['fecha'],
            'concepto' => $row['concepto'],
            'tipo' => $row['tipo'],
            'usuario' => $row['usuario'],
            'monto' => floatval($row['monto'])
        ];
        $total_compras += floatval($row['monto']);
    }
}

$num_egresos = count($egresos);
$promedio_egreso = $num_egresos > 0 ? $total_compras / $num_egresos : 0;

$conexion->close();

echo json_encode([
    'success' => true,
    'egresos' => $egresos,
    'total_compras' => $total_compras,
    'num_egresos' => $num_egresos,
    'promedio_egreso' => $promedio_egreso
]);
?>
