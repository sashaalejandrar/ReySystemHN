<?php
/**
 * API: Confirm Egreso (Expense)
 * Marks an expense as confirmed
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
    
    if ($egreso_id <= 0) {
        throw new Exception("ID de egreso inválido");
    }
    
    // Database connection
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión");
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Get user ID
    $query_usuario = "SELECT Id FROM usuarios WHERE usuario = ?";
    $stmt_usuario = $conexion->prepare($query_usuario);
    $stmt_usuario->bind_param("s", $_SESSION['usuario']);
    $stmt_usuario->execute();
    $resultado_usuario = $stmt_usuario->get_result();
    
    if ($resultado_usuario->num_rows === 0) {
        throw new Exception("Usuario no encontrado");
    }
    
    $usuario_data = $resultado_usuario->fetch_assoc();
    $id_usuario = $usuario_data['Id'];
    $stmt_usuario->close();
    
    // Check if already confirmed
    $check_sql = "SELECT confirmado FROM egresos_caja WHERE id = ?";
    $check_stmt = $conexion->prepare($check_sql);
    $check_stmt->bind_param("i", $egreso_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        throw new Exception("Egreso no encontrado");
    }
    
    $egreso = $check_result->fetch_assoc();
    if ($egreso['confirmado'] == 1) {
        throw new Exception("Este egreso ya está confirmado");
    }
    $check_stmt->close();
    
    // Confirm expense
    $sql = "
    UPDATE egresos_caja 
    SET confirmado = 1, 
        confirmado_por = ?, 
        fecha_confirmacion = NOW()
    WHERE id = ?
    ";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_usuario, $egreso_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al confirmar el egreso");
    }
    
    $stmt->close();
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Egreso confirmado correctamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
