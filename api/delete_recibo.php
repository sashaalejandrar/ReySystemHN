<?php
/**
 * API: Delete Recibo (Receipt)
 * Deletes a receipt file from disk and database
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
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    $recibo_id = isset($data['id']) ? intval($data['id']) : 0;
    
    if ($recibo_id <= 0) {
        throw new Exception("ID de recibo inválido");
    }
    
    // Database connection
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión");
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Get file path before deleting
    $sql = "SELECT ruta_archivo FROM egresos_archivos WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $recibo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Recibo no encontrado");
    }
    
    $recibo = $result->fetch_assoc();
    $ruta_archivo = $recibo['ruta_archivo'];
    $stmt->close();
    
    // Delete from database
    $delete_sql = "DELETE FROM egresos_archivos WHERE id = ?";
    $delete_stmt = $conexion->prepare($delete_sql);
    $delete_stmt->bind_param("i", $recibo_id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception("Error al eliminar el recibo de la base de datos");
    }
    
    $delete_stmt->close();
    $conexion->close();
    
    // Delete file from disk
    if (file_exists($ruta_archivo)) {
        unlink($ruta_archivo);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Recibo eliminado correctamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
