<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
$where_fecha_caja = "";
$where_Fecha = "";
switch($periodo) {
    case 'hoy':
        $where_fecha_caja = "DATE(Fecha) = CURDATE()";
        $where_Fecha = "DATE(Fecha) = CURDATE()";
        break;
    case 'semana':
        $where_fecha_caja = "YEARWEEK(Fecha, 1) = YEARWEEK(CURDATE(), 1)";
        $where_Fecha = "YEARWEEK(Fecha, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'mes':
        $where_fecha_caja = "MONTH(Fecha) = MONTH(CURDATE()) AND YEAR(Fecha) = YEAR(CURDATE())";
        $where_Fecha = "MONTH(Fecha) = MONTH(CURDATE()) AND YEAR(Fecha) = YEAR(CURDATE())";
        break;
    case 'anio':
        $where_fecha_caja = "YEAR(Fecha) = YEAR(CURDATE())";
        $where_Fecha = "YEAR(Fecha) = YEAR(CURDATE())";
        break;
    case 'personalizado':
        if ($fecha_inicio && $fecha_fin) {
            $where_fecha_caja = "DATE(Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
            $where_Fecha = "DATE(Fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        } else {
            $where_fecha_caja = "DATE(Fecha) = CURDATE()";
            $where_Fecha = "DATE(Fecha) = CURDATE()";
        }
        break;
    default:
        $where_fecha_caja = "DATE(Fecha) = CURDATE()";
        $where_Fecha = "DATE(Fecha) = CURDATE()";
}

$operaciones = [];

// Obtener aperturas de caja
$query_aperturas = "SELECT 
    c.Fecha as fecha,
    'Apertura' as tipo,
    c.Monto_Inicial as monto_inicial,
    0 as monto_final,
    c.Usuario as usuario
    FROM caja c
    WHERE $where_fecha_caja
    ORDER BY c.Fecha DESC";

$result = $conexion->query($query_aperturas);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $operaciones[] = [
            'fecha' => $row['fecha'],
            'tipo' => $row['tipo'],
            'monto_inicial' => floatval($row['monto_inicial']),
            'monto_final' => floatval($row['monto_final']),
            'usuario' => $row['usuario']
        ];
    }
}

// Obtener arqueos de caja
$query_arqueos = "SELECT 
    ac.Fecha as fecha,
    'Arqueo' as tipo,
    ac.Efectivo as monto_inicial,
    ac.Total as monto_final,
    ac.usuario as usuario
    FROM arqueo_caja ac
    WHERE $where_Fecha
    ORDER BY ac.Fecha DESC";

$result = $conexion->query($query_arqueos);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $operaciones[] = [
            'fecha' => $row['fecha'],
            'tipo' => $row['tipo'],
            'monto_inicial' => floatval($row['monto_inicial']),
            'monto_final' => floatval($row['monto_final']),
            'usuario' => $row['usuario']
        ];
    }
}

// Obtener cierres de caja
$query_cierres = "SELECT 
    cc.Fecha as fecha,
    'Cierre' as tipo,
    cc.Efectivo as monto_inicial,
    cc.Total as monto_final,
    cc.usuario as usuario
    FROM cierre_caja cc
    WHERE $where_Fecha
    ORDER BY cc.Fecha DESC";

$result = $conexion->query($query_cierres);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $operaciones[] = [
            'fecha' => $row['fecha'],
            'tipo' => $row['tipo'],
            'monto_inicial' => floatval($row['monto_inicial']),
            'monto_final' => floatval($row['monto_final']),
            'usuario' => $row['usuario']
        ];
    }
}

// Ordenar por fecha descendente
if (count($operaciones) > 0) {
    usort($operaciones, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });
}

$conexion->close();

echo json_encode([
    'success' => true,
    'operaciones' => array_slice($operaciones, 0, 100)
]);
?>
