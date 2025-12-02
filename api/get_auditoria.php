<?php
/**
 * API: Get Audit Logs
 * Returns audit log entries with filters
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Check if user is admin
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$stmt = $conexion->prepare("SELECT Rol FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (strtolower($user['Rol']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    $conexion->set_charset("utf8mb4");
    
    // Get filters
    $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
    $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
    $tabla = isset($_GET['tabla']) && $_GET['tabla'] !== '' ? $_GET['tabla'] : null;
    $accion = isset($_GET['accion']) && $_GET['accion'] !== '' ? $_GET['accion'] : null;
    $usuario_id = isset($_GET['usuario_id']) && $_GET['usuario_id'] !== '' ? intval($_GET['usuario_id']) : null;
    $search = isset($_GET['search']) && $_GET['search'] !== '' ? $_GET['search'] : null;
    
    // Build query
    $sql = "
    SELECT 
        a.*,
        DATE_FORMAT(a.fecha, '%d/%m/%Y %H:%i:%s') as fecha_formateada
    FROM auditoria a
    WHERE DATE(a.fecha) BETWEEN ? AND ?
    ";
    
    $params = [$fecha_inicio, $fecha_fin];
    $types = "ss";
    
    if ($tabla) {
        $sql .= " AND a.tabla = ?";
        $params[] = $tabla;
        $types .= "s";
    }
    
    if ($accion) {
        $sql .= " AND a.accion = ?";
        $params[] = $accion;
        $types .= "s";
    }
    
    if ($usuario_id) {
        $sql .= " AND a.usuario_id = ?";
        $params[] = $usuario_id;
        $types .= "i";
    }
    
    if ($search) {
        $sql .= " AND (a.usuario_nombre LIKE ? OR a.campo_modificado LIKE ? OR a.valor_anterior LIKE ? OR a.valor_nuevo LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ssss";
    }
    
    $sql .= " ORDER BY a.fecha DESC LIMIT 500";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    
    // Get statistics
    $stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN accion = 'crear' THEN 1 ELSE 0 END) as creaciones,
        SUM(CASE WHEN accion = 'editar' THEN 1 ELSE 0 END) as ediciones,
        SUM(CASE WHEN accion = 'eliminar' THEN 1 ELSE 0 END) as eliminaciones
    FROM auditoria
    WHERE DATE(fecha) BETWEEN ? AND ?
    ";
    
    $stmt_stats = $conexion->prepare($stats_sql);
    $stmt_stats->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt_stats->execute();
    $stats = $stmt_stats->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => $logs,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
