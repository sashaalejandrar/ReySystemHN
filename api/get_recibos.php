<?php
/**
 * API: Get Recibos (Receipts) for a specific expense
 * Returns list of receipt files for an expense
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
    // Get egreso_id from request
    $egreso_id = isset($_GET['egreso_id']) ? intval($_GET['egreso_id']) : 0;
    
    if ($egreso_id <= 0) {
        throw new Exception("ID de egreso inválido");
    }
    
    // Database connection
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión");
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Get receipts for this expense
    $sql = "
    SELECT 
        id,
        nombre_archivo,
        ruta_archivo,
        tipo_archivo,
        tamano,
        fecha_subida
    FROM egresos_archivos
    WHERE egreso_id = ?
    ORDER BY fecha_subida ASC
    ";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $egreso_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recibos = [];
    while ($row = $result->fetch_assoc()) {
        $recibos[] = [
            'id' => $row['id'],
            'nombre_archivo' => $row['nombre_archivo'],
            'ruta_archivo' => $row['ruta_archivo'],
            'tipo_archivo' => $row['tipo_archivo'],
            'tamano' => intval($row['tamano']),
            'fecha_subida' => $row['fecha_subida'],
            'es_imagen' => strpos($row['tipo_archivo'], 'image/') === 0,
            'es_pdf' => $row['tipo_archivo'] === 'application/pdf'
        ];
    }
    
    $stmt->close();
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'data' => $recibos,
        'total' => count($recibos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
