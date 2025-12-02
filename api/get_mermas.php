<?php
/**
 * API: Get Mermas (Waste/Loss Records)
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
    
    // Get filters
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
    $motivo = $_GET['motivo'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $sql = "SELECT * FROM mermas WHERE fecha BETWEEN ? AND ?";
    $params = [$fecha_inicio, $fecha_fin];
    $types = "ss";
    
    if ($motivo) {
        $sql .= " AND motivo = ?";
        $params[] = $motivo;
        $types .= "s";
    }
    
    if ($search) {
        $sql .= " AND (producto_nombre LIKE ? OR descripcion LIKE ?)";
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
    
    $mermas = [];
    while ($row = $result->fetch_assoc()) {
        $mermas[] = $row;
    }
    
    // Get stats
    $stats_sql = "SELECT 
        COUNT(*) as total,
        SUM(costo_total) as costo_total,
        SUM(CASE WHEN motivo = 'dañado' THEN 1 ELSE 0 END) as danados,
        SUM(CASE WHEN motivo = 'vencido' THEN 1 ELSE 0 END) as vencidos,
        SUM(CASE WHEN motivo = 'robo' THEN 1 ELSE 0 END) as robos,
        SUM(CASE WHEN motivo = 'otro' THEN 1 ELSE 0 END) as otros
    FROM mermas WHERE fecha BETWEEN ? AND ?";
    
    $stmt_stats = $conexion->prepare($stats_sql);
    $stmt_stats->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt_stats->execute();
    $stats = $stmt_stats->get_result()->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => $mermas,
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conexion->close();
?>
