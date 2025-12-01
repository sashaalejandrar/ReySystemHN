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
        $where_fecha = "DATE(fecha_registro) = CURDATE()";
        break;
    case 'semana':
        $where_fecha = "YEARWEEK(fecha_registro, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'mes':
        $where_fecha = "MONTH(fecha_registro) = MONTH(CURDATE()) AND YEAR(fecha_registro) = YEAR(CURDATE())";
        break;
    case 'anio':
        $where_fecha = "YEAR(fecha_registro) = YEAR(CURDATE())";
        break;
    case 'personalizado':
        if ($fecha_inicio && $fecha_fin) {
            $where_fecha = "DATE(fecha_registro) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
        } else {
            $where_fecha = "DATE(fecha_registro) = CURDATE()";
        }
        break;
    default:
        $where_fecha = "DATE(fecha_registro) = CURDATE()";
}

// Obtener egresos con información del usuario
$query = "SELECT 
    e.fecha_registro as fecha,
    e.concepto,
    e.tipo,
    e.monto,
    u.Usuario as usuario
    FROM egresos_caja e
    LEFT JOIN usuarios u ON e.usuario_id = u.Id
    WHERE $where_fecha
    ORDER BY e.fecha_registro DESC
    LIMIT 100";

$result = $conexion->query($query);

$egresos = [];
while ($row = $result->fetch_assoc()) {
    $egresos[] = [
        'fecha' => $row['fecha'],
        'concepto' => $row['concepto'],
        'tipo' => $row['tipo'] ?: 'General',
        'monto' => floatval($row['monto']),
        'usuario' => $row['usuario'] ?: 'Sistema'
    ];
}

$conexion->close();

echo json_encode([
    'success' => true,
    'egresos' => $egresos
]);
?>
