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

$fecha = $_GET['fecha'] ?? date('Y-m-d');

// Saldo inicial (apertura de caja)
$query = "SELECT Monto_Inicial FROM caja WHERE DATE(Fecha) = '$fecha' LIMIT 1";
$result = $conexion->query($query);
$saldo_inicial = 0;
if ($result && $result->num_rows > 0) {
    $saldo_inicial = floatval($result->fetch_assoc()['Monto_Inicial']);
}

// Ingresos (ventas del día)
$query = "SELECT SUM(Total) as total FROM ventas WHERE DATE(Fecha_Venta) = '$fecha'";
$result = $conexion->query($query);
$ingresos = floatval($result->fetch_assoc()['total'] ?? 0);

// Egresos (gastos del día)
$query = "SELECT SUM(Monto) as total FROM egresos_caja WHERE DATE(fecha_registro) = '$fecha'";
$result = $conexion->query($query);
$egresos = floatval($result->fetch_assoc()['total'] ?? 0);

// Saldo esperado
$saldo_esperado = $saldo_inicial + $ingresos - $egresos;

// Saldo real (cierre de caja)
$query = "SELECT Total FROM cierre_caja WHERE DATE(Fecha) = '$fecha' LIMIT 1";
$result = $conexion->query($query);
$saldo_real = 0;
if ($result && $result->num_rows > 0) {
    $saldo_real = floatval($result->fetch_assoc()['Total']);
}

// Diferencia
$diferencia = $saldo_real - $saldo_esperado;

$conexion->close();

echo json_encode([
    'success' => true,
    'saldo_caja' => $saldo_real,
    'total_ventas' => $ingresos,
    'total_egresos' => $egresos,
    'saldo_inicial' => $saldo_inicial,
    'ingresos' => $ingresos,
    'egresos' => $egresos,
    'saldo_esperado' => $saldo_esperado,
    'saldo_real' => $saldo_real,
    'diferencia' => $diferencia
]);
?>
