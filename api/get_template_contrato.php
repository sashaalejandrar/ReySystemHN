<?php
/**
 * API: Get Contract Template
 * Returns the active contract template
 */

header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión");
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Get active template
    $sql = "SELECT id, nombre, contenido FROM plantillas_contrato WHERE activa = 1 ORDER BY id DESC LIMIT 1";
    $result = $conexion->query($sql);
    
    if ($result->num_rows === 0) {
        throw new Exception("No hay plantilla activa");
    }
    
    $template = $result->fetch_assoc();
    
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'data' => $template
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
