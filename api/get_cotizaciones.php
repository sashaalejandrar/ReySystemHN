<?php
/**
 * API: Get Cotizaciones (Quotations)
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    $conexion->set_charset("utf8mb4");
    
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $estado = $_GET['estado'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT * FROM cotizaciones WHERE fecha BETWEEN ? AND ?";
    $params = [$fecha_inicio, $fecha_fin];
    $types = "ss";
    
    if ($estado) {
        $sql .= " AND estado = ?";
        $params[] = $estado;
        $types .= "s";
    }
    
    if ($search) {
        $sql .= " AND (numero_cotizacion LIKE ? OR cliente_nombre LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    $sql .= " ORDER BY fecha DESC, id DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cotizaciones = [];
    while ($row = $result->fetch_assoc()) {
        $cotizaciones[] = $row;
    }
    
    // Get stats
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
        SUM(CASE WHEN estado = 'aprobada' THEN 1 ELSE 0 END) as aprobadas,
        SUM(CASE WHEN estado = 'convertida' THEN 1 ELSE 0 END) as convertidas,
        SUM(total) as monto_total
    FROM cotizaciones WHERE fecha BETWEEN ? AND ?";
    
    $stmt_stats = $conexion->prepare($stats_sql);
    $stmt_stats->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt_stats->execute();
    $stats = $stmt_stats->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => $cotizaciones,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conexion->close();
?>
