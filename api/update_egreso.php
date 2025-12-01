<?php
/**
 * API: Update Egreso (Expense)
 * Updates expense information
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
    
    $egreso_id = isset($data['id']) ? intval($data['id']) : 0;
    $monto = isset($data['monto']) ? floatval($data['monto']) : 0;
    $concepto = isset($data['concepto']) ? trim($data['concepto']) : '';
    $fecha_registro = isset($data['fecha_registro']) ? $data['fecha_registro'] : '';
    $tipo = isset($data['tipo']) ? $data['tipo'] : '';
    
    // Validate
    if ($egreso_id <= 0) {
        throw new Exception("ID de egreso inválido");
    }
    
    if ($monto <= 0) {
        throw new Exception("El monto debe ser mayor a 0");
    }
    
    if (empty($concepto)) {
        throw new Exception("El concepto es requerido");
    }
    
    if (!in_array($tipo, ['Compra', 'Justificación'])) {
        throw new Exception("Tipo de egreso inválido");
    }
    
    // Database connection
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión");
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Update expense
    $sql = "
    UPDATE egresos_caja 
    SET monto = ?, 
        concepto = ?, 
        fecha_registro = ?, 
        tipo = ?
    WHERE id = ?
    ";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("dsssi", $monto, $concepto, $fecha_registro, $tipo, $egreso_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar el egreso");
    }
    
    $stmt->close();
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Egreso actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
