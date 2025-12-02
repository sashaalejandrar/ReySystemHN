<?php
/**
 * API: Delete Contrato
 * Deletes a contract
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
    $data = json_decode(file_get_contents('php://input'), true);
    
    $contrato_id = isset($data['id']) ? intval($data['id']) : 0;
    
    if ($contrato_id <= 0) {
        throw new Exception("ID de contrato inválido");
    }
    
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión");
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Delete contract
    $stmt = $conexion->prepare("DELETE FROM contratos WHERE id = ?");
    $stmt->bind_param("i", $contrato_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al eliminar el contrato");
    }
    
    $stmt->close();
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Contrato eliminado correctamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
