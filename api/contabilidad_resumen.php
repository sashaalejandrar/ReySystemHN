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

// Calcular ingresos (ventas)
$query_ingresos = "SELECT 
    COALESCE(SUM(Total), 0) as total_ingresos,
    COUNT(*) as num_ventas
    FROM ventas 
    WHERE $where_fecha";

$result_ingresos = $conexion->query($query_ingresos);
$ingresos_data = $result_ingresos->fetch_assoc();
$total_ingresos = floatval($ingresos_data['total_ingresos']);
$num_ventas = intval($ingresos_data['num_ventas']);

// Calcular egresos
$query_egresos = "SELECT 
    COALESCE(SUM(monto), 0) as total_egresos,
    COUNT(*) as num_egresos
    FROM egresos_caja 
    WHERE $where_fecha_egresos";

$result_egresos = $conexion->query($query_egresos);
$egresos_data = $result_egresos->fetch_assoc();
$total_egresos = floatval($egresos_data['total_egresos']);
$num_egresos = intval($egresos_data['num_egresos']);

// Calcular utilidad y margen
$utilidad = $total_ingresos - $total_egresos;
$margen = $total_ingresos > 0 ? ($utilidad / $total_ingresos) * 100 : 0;

// Obtener datos para gráfica de tendencias
$tendencias = obtenerTendencias($conexion, $periodo, $fecha_inicio, $fecha_fin);

// Obtener distribución de ingresos por método de pago
$distribucion = obtenerDistribucion($conexion, $where_fecha);

$conexion->close();

echo json_encode([
    'success' => true,
    'ingresos' => $total_ingresos,
    'egresos' => $total_egresos,
    'utilidad' => $utilidad,
    'margen' => $margen,
    'num_ventas' => $num_ventas,
    'num_egresos' => $num_egresos,
    'tendencias' => $tendencias,
    'distribucion' => $distribucion
]);

function obtenerTendencias($conexion, $periodo, $fecha_inicio, $fecha_fin) {
    $labels = [];
    $ingresos = [];
    $egresos = [];
    
    switch($periodo) {
        case 'hoy':
            // Últimas 24 horas por hora
            for ($i = 23; $i >= 0; $i--) {
                $hora = date('H:00', strtotime("-$i hours"));
                $labels[] = $hora;
                
                $query_ing = "SELECT COALESCE(SUM(Total), 0) as total FROM ventas 
                              WHERE DATE(Fecha_Venta) = CURDATE() 
                              AND HOUR(Fecha_Venta) = " . date('H', strtotime("-$i hours"));
                $result = $conexion->query($query_ing);
                $ingresos[] = floatval($result->fetch_assoc()['total']);
                
                $query_egr = "SELECT COALESCE(SUM(monto), 0) as total FROM egresos_caja 
                              WHERE DATE(fecha_registro) = CURDATE() 
                              AND HOUR(fecha_registro) = " . date('H', strtotime("-$i hours"));
                $result = $conexion->query($query_egr);
                $egresos[] = floatval($result->fetch_assoc()['total']);
            }
            break;
            
        case 'semana':
            // Últimos 7 días
            for ($i = 6; $i >= 0; $i--) {
                $fecha = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('D d', strtotime($fecha));
                
                $query_ing = "SELECT COALESCE(SUM(Total), 0) as total FROM ventas WHERE DATE(Fecha_Venta) = '$fecha'";
                $result = $conexion->query($query_ing);
                $ingresos[] = floatval($result->fetch_assoc()['total']);
                
                $query_egr = "SELECT COALESCE(SUM(monto), 0) as total FROM egresos_caja WHERE DATE(fecha_registro) = '$fecha'";
                $result = $conexion->query($query_egr);
                $egresos[] = floatval($result->fetch_assoc()['total']);
            }
            break;
            
        case 'mes':
            // Últimos 30 días
            for ($i = 29; $i >= 0; $i--) {
                $fecha = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('d', strtotime($fecha));
                
                $query_ing = "SELECT COALESCE(SUM(Total), 0) as total FROM ventas WHERE DATE(Fecha_Venta) = '$fecha'";
                $result = $conexion->query($query_ing);
                $ingresos[] = floatval($result->fetch_assoc()['total']);
                
                $query_egr = "SELECT COALESCE(SUM(monto), 0) as total FROM egresos_caja WHERE DATE(fecha_registro) = '$fecha'";
                $result = $conexion->query($query_egr);
                $egresos[] = floatval($result->fetch_assoc()['total']);
            }
            break;
            
        case 'anio':
            // Últimos 12 meses
            for ($i = 11; $i >= 0; $i--) {
                $mes = date('Y-m', strtotime("-$i months"));
                $labels[] = date('M', strtotime($mes . '-01'));
                
                $query_ing = "SELECT COALESCE(SUM(Total), 0) as total FROM ventas WHERE DATE_FORMAT(Fecha_Venta, '%Y-%m') = '$mes'";
                $result = $conexion->query($query_ing);
                $ingresos[] = floatval($result->fetch_assoc()['total']);
                
                $query_egr = "SELECT COALESCE(SUM(monto), 0) as total FROM egresos_caja WHERE DATE_FORMAT(fecha_registro, '%Y-%m') = '$mes'";
                $result = $conexion->query($query_egr);
                $egresos[] = floatval($result->fetch_assoc()['total']);
            }
            break;
            
        default:
            // Por defecto, últimas 24 horas
            for ($i = 23; $i >= 0; $i--) {
                $hora = date('H:00', strtotime("-$i hours"));
                $labels[] = $hora;
                $ingresos[] = 0;
                $egresos[] = 0;
            }
    }
    
    return [
        'labels' => $labels,
        'ingresos' => $ingresos,
        'egresos' => $egresos
    ];
}

function obtenerDistribucion($conexion, $where_fecha) {
    $query = "SELECT 
        MetodoPago,
        COALESCE(SUM(Total), 0) as total
        FROM ventas 
        WHERE $where_fecha
        GROUP BY MetodoPago
        ORDER BY total DESC
        LIMIT 5";
    
    $result = $conexion->query($query);
    
    $labels = [];
    $valores = [];
    
    while ($row = $result->fetch_assoc()) {
        $labels[] = $row['MetodoPago'] ?: 'Sin especificar';
        $valores[] = floatval($row['total']);
    }
    
    return [
        'labels' => $labels,
        'valores' => $valores
    ];
}
?>
