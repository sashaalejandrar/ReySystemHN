<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    die('No autenticado');
}

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die('Error de conexión');
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
        $where_fecha_egresos = "DATE(fecha_registro) = CURDATE()";
        break;
    case 'semana':
        $where_fecha = "YEARWEEK(Fecha_Venta, 1) = YEARWEEK(CURDATE(), 1)";
        $where_fecha_egresos = "YEARWEEK(fecha_registro, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'mes':
        $where_fecha = "MONTH(Fecha_Venta) = MONTH(CURDATE()) AND YEAR(Fecha_Venta) = YEAR(CURDATE())";
        $where_fecha_egresos = "MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())";
        break;
    case 'anio':
        $where_fecha = "YEAR(Fecha_Venta) = YEAR(CURDATE())";
        $where_fecha_egresos = "YEAR(fecha_registro) = YEAR(CURDATE())";
        break;
    case 'personalizado':
        if ($fecha_inicio && $fecha_fin) {
            $where_fecha = "DATE(Fecha_Venta) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            $where_fecha_egresos = "DATE(fecha_registro) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        } else {
            $where_fecha = "DATE(Fecha_Venta) = CURDATE()";
            $where_fecha_egresos = "DATE(fecha_registro) = CURDATE()";
        }
        break;
    default:
        $where_fecha = "DATE(Fecha_Venta) = CURDATE()";
        $where_fecha_egresos = "DATE(fecha_registro) = CURDATE()";
}

// Configurar headers para descarga de Excel
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="reporte_contabilidad_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

// Obtener datos de ventas
$query_ventas = "SELECT 
    Factura_Id,
    Cliente,
    Fecha_Venta,
    MetodoPago,
    Total,
    Vendedor
    FROM ventas 
    WHERE $where_fecha
    ORDER BY Fecha_Venta DESC";

$ventas = $conexion->query($query_ventas);

// Obtener datos de egresos
$query_egresos = "SELECT 
    e.fecha_registro,
    e.concepto,
    e.tipo,
    e.monto,
    u.Usuario
    FROM egresos_caja e
    LEFT JOIN usuarios u ON e.usuario_id = u.Id
    WHERE $where_fecha_egresos
    ORDER BY e.fecha_registro DESC";

$egresos = $conexion->query($query_egresos);

// Calcular totales
$query_totales = "SELECT 
    COALESCE(SUM(Total), 0) as total_ingresos,
    COUNT(*) as num_ventas
    FROM ventas 
    WHERE $where_fecha";
$result_totales = $conexion->query($query_totales);
$totales = $result_totales->fetch_assoc();

$query_egresos_total = "SELECT 
    COALESCE(SUM(monto), 0) as total_egresos,
    COUNT(*) as num_egresos
    FROM egresos_caja 
    WHERE $where_fecha_egresos";
$result_egresos_total = $conexion->query($query_egresos_total);
$egresos_total = $result_egresos_total->fetch_assoc();

$utilidad = floatval($totales['total_ingresos']) - floatval($egresos_total['total_egresos']);

// Generar HTML para Excel
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Contabilidad</title>
    <style>
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #1152d4; color: white; font-weight: bold; }
        .total-row { background-color: #f0f0f0; font-weight: bold; }
        .header { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
        .summary { background-color: #e8f4f8; padding: 10px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">REPORTE DE CONTABILIDAD - REY SYSTEM</div>
    <div class="summary">
        <p><strong>Período:</strong> <?php echo ucfirst($periodo); ?></p>
        <p><strong>Fecha de Generación:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>Total Ingresos:</strong> L. <?php echo number_format($totales['total_ingresos'], 2); ?></p>
        <p><strong>Total Egresos:</strong> L. <?php echo number_format($egresos_total['total_egresos'], 2); ?></p>
        <p><strong>Utilidad Neta:</strong> L. <?php echo number_format($utilidad, 2); ?></p>
        <p><strong>Número de Ventas:</strong> <?php echo $totales['num_ventas']; ?></p>
        <p><strong>Número de Egresos:</strong> <?php echo $egresos_total['num_egresos']; ?></p>
    </div>

    <h3>VENTAS</h3>
    <table>
        <thead>
            <tr>
                <th>Factura</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>Método de Pago</th>
                <th>Total</th>
                <th>Vendedor</th>
            </tr>
        </thead>
        <tbody>
            <?php while($venta = $ventas->fetch_assoc()): ?>
            <tr>
                <td><?php echo $venta['Factura_Id']; ?></td>
                <td><?php echo $venta['Cliente']; ?></td>
                <td><?php echo $venta['Fecha_Venta']; ?></td>
                <td><?php echo $venta['MetodoPago']; ?></td>
                <td>L. <?php echo number_format($venta['Total'], 2); ?></td>
                <td><?php echo $venta['Vendedor']; ?></td>
            </tr>
            <?php endwhile; ?>
            <tr class="total-row">
                <td colspan="4">TOTAL</td>
                <td>L. <?php echo number_format($totales['total_ingresos'], 2); ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <h3>EGRESOS</h3>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Concepto</th>
                <th>Tipo</th>
                <th>Monto</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            <?php while($egreso = $egresos->fetch_assoc()): ?>
            <tr>
                <td><?php echo $egreso['fecha_registro']; ?></td>
                <td><?php echo $egreso['concepto']; ?></td>
                <td><?php echo $egreso['tipo'] ?: 'General'; ?></td>
                <td>L. <?php echo number_format($egreso['monto'], 2); ?></td>
                <td><?php echo $egreso['Usuario'] ?: 'Sistema'; ?></td>
            </tr>
            <?php endwhile; ?>
            <tr class="total-row">
                <td colspan="3">TOTAL</td>
                <td>L. <?php echo number_format($egresos_total['total_egresos'], 2); ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>
</html>
<?php
$conexion->close();
?>
