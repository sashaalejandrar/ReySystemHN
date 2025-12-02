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

// ACTIVOS CORRIENTES

// Efectivo en Caja - Último cierre de caja
$query = "SELECT Total FROM cierre_caja WHERE DATE(Fecha) <= '$fecha' ORDER BY Fecha DESC LIMIT 1";
$result = $conexion->query($query);
$efectivo_caja = 0;
if ($result && $result->num_rows > 0) {
    $efectivo_caja = floatval($result->fetch_assoc()['Total'] ?? 0);
}

// Inventario - Valor total del stock
$query = "SELECT SUM(s.stock * COALESCE(cp.CostoPorEmpaque, 0)) as total
    FROM stock s
    LEFT JOIN creacion_de_productos cp ON s.nombre_producto = cp.NombreProducto
    WHERE s.stock > 0";
$result = $conexion->query($query);
$inventario = floatval($result->fetch_assoc()['total'] ?? 0);

// Cuentas por Cobrar - Deudas pendientes
$query = "SELECT SUM(Monto_Pendiente) as total FROM deudas WHERE Estado = 'Pendiente'";
$result = $conexion->query($query);
$cuentas_cobrar = floatval($result->fetch_assoc()['total'] ?? 0);

$total_activos_corrientes = $efectivo_caja + $inventario + $cuentas_cobrar;
$total_activos = $total_activos_corrientes; // Por ahora solo activos corrientes

// PASIVOS CORRIENTES

// Cuentas por Pagar - Por ahora en 0 (no hay tabla de proveedores)
$cuentas_pagar = 0;

$total_pasivos_corrientes = $cuentas_pagar;
$total_pasivos = $total_pasivos_corrientes;

// PATRIMONIO

// Capital - Calculado como diferencia (Activos - Pasivos)
$capital = $total_activos - $total_pasivos;

// Utilidades Retenidas - Por ahora en 0
$utilidades_retenidas = 0;

$total_patrimonio = $capital + $utilidades_retenidas;

$conexion->close();

echo json_encode([
    'success' => true,
    'efectivo_caja' => $efectivo_caja,
    'inventario' => $inventario,
    'cuentas_cobrar' => $cuentas_cobrar,
    'total_activos_corrientes' => $total_activos_corrientes,
    'total_activos' => $total_activos,
    'cuentas_pagar' => $cuentas_pagar,
    'total_pasivos_corrientes' => $total_pasivos_corrientes,
    'total_pasivos' => $total_pasivos,
    'capital' => $capital,
    'utilidades_retenidas' => $utilidades_retenidas,
    'total_patrimonio' => $total_patrimonio
]);
?>
