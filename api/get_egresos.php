<?php
/**
 * API: Get Egresos (Expenses)
 * Returns list of expenses with filters and receipt information
 */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Check authentication
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    // Database connection
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión");
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Get filters from request
    $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
    $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $confirmado = isset($_GET['confirmado']) ? $_GET['confirmado'] : '';
    
    // Build query
    $sql = "
    SELECT 
        e.id,
        e.caja_id,
        e.monto,
        e.concepto,
        e.fecha_registro,
        e.tipo,
        e.confirmado,
        e.fecha_confirmacion,
        u.Nombre as usuario_nombre,
        u.Apellido as usuario_apellido,
        uc.Nombre as confirmado_nombre,
        uc.Apellido as confirmado_apellido,
        COUNT(a.id) as num_recibos
    FROM egresos_caja e
    LEFT JOIN usuarios u ON e.usuario_id = u.Id
    LEFT JOIN usuarios uc ON e.confirmado_por = uc.Id
    LEFT JOIN egresos_archivos a ON e.id = a.egreso_id
    WHERE e.fecha_registro BETWEEN ? AND ?
    ";
    
    $params = [$fecha_inicio, $fecha_fin];
    $types = "ss";
    
    // Add tipo filter
    if ($tipo !== '') {
        $sql .= " AND e.tipo = ?";
        $params[] = $tipo;
        $types .= "s";
    }
    
    // Add search filter
    if ($search !== '') {
        $sql .= " AND e.concepto LIKE ?";
        $params[] = "%$search%";
        $types .= "s";
    }
    
    // Add confirmado filter
    if ($confirmado !== '') {
        $sql .= " AND e.confirmado = ?";
        $params[] = intval($confirmado);
        $types .= "i";
    }
    
    $sql .= " GROUP BY e.id ORDER BY e.fecha_registro DESC, e.id DESC";
    
    $stmt = $conexion->prepare($sql);
    
    // Bind parameters dynamically
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $egresos = [];
    while ($row = $result->fetch_assoc()) {
        $egresos[] = [
            'id' => $row['id'],
            'caja_id' => $row['caja_id'],
            'monto' => floatval($row['monto']),
            'concepto' => $row['concepto'],
            'fecha_registro' => $row['fecha_registro'],
            'tipo' => $row['tipo'],
            'confirmado' => intval($row['confirmado']),
            'fecha_confirmacion' => $row['fecha_confirmacion'],
            'usuario' => trim($row['usuario_nombre'] . ' ' . $row['usuario_apellido']),
            'confirmado_por' => $row['confirmado_nombre'] ? trim($row['confirmado_nombre'] . ' ' . $row['confirmado_apellido']) : null,
            'num_recibos' => intval($row['num_recibos'])
        ];
    }
    
    $stmt->close();
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'data' => $egresos,
        'total' => count($egresos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
