<?php
/**
 * API: Get Contratos
 * Returns list of contracts with filters
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

// Check admin role
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$conexion->set_charset("utf8mb4");

// Get user role
$stmt = $conexion->prepare("SELECT Rol FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (strtolower($user['Rol']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    // Get filters
    $fecha_inicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01');
    $fecha_fin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-d');
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Build query
    $sql = "
    SELECT 
        c.id,
        c.tipo,
        c.fecha_creacion,
        c.lugar,
        c.nombre_completo,
        c.identidad,
        c.servicios,
        c.cargo,
        c.nombre_empresa,
        c.contenido_adicional,
        c.fecha_registro,
        u.Nombre as creado_nombre,
        u.Apellido as creado_apellido
    FROM contratos c
    LEFT JOIN usuarios u ON c.creado_por = u.Id
    WHERE c.fecha_creacion BETWEEN ? AND ?
    ";
    
    $params = [$fecha_inicio, $fecha_fin];
    $types = "ss";
    
    // Add tipo filter
    if ($tipo !== '') {
        $sql .= " AND c.tipo = ?";
        $params[] = $tipo;
        $types .= "s";
    }
    
    // Add search filter
    if ($search !== '') {
        $sql .= " AND (c.nombre_completo LIKE ? OR c.nombre_empresa LIKE ? OR c.identidad LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "sss";
    }
    
    $sql .= " ORDER BY c.fecha_creacion DESC, c.id DESC";
    
    $stmt = $conexion->prepare($sql);
    
    if (count($params) > 0) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $contratos = [];
    while ($row = $result->fetch_assoc()) {
        $contratos[] = [
            'id' => $row['id'],
            'tipo' => $row['tipo'],
            'fecha_creacion' => $row['fecha_creacion'],
            'lugar' => $row['lugar'],
            'nombre_completo' => $row['nombre_completo'],
            'identidad' => $row['identidad'],
            'servicios' => $row['servicios'],
            'cargo' => $row['cargo'],
            'nombre_empresa' => $row['nombre_empresa'],
            'contenido_adicional' => $row['contenido_adicional'],
            'fecha_registro' => $row['fecha_registro'],
            'creado_por' => trim($row['creado_nombre'] . ' ' . $row['creado_apellido'])
        ];
    }
    
    $stmt->close();
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'data' => $contratos,
        'total' => count($contratos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
