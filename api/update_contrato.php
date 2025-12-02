<?php
/**
 * API: Update Contrato
 * Updates an existing contract
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
    
    if (!isset($data['id']) || intval($data['id']) <= 0) {
        throw new Exception("ID de contrato inválido");
    }
    
    // Validate identity format if provided
    if (isset($data['identidad']) && !preg_match('/^\d{4}-\d{4}-\d{5}$/', $data['identidad'])) {
        throw new Exception("Formato de identidad inválido. Use: 0000-0000-00000");
    }
    
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión");
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Update contract
    $sql = "
    UPDATE contratos SET
        tipo = ?,
        fecha_creacion = ?,
        lugar = ?,
        nombre_completo = ?,
        identidad = ?,
        servicios = ?,
        cargo = ?,
        nombre_empresa = ?,
        contenido_adicional = ?,
        firma_empleado = ?
    WHERE id = ?
    ";
    
    $stmt = $conexion->prepare($sql);
    
    $lugar = isset($data['lugar']) && $data['lugar'] !== '' ? $data['lugar'] : 'La Flecha, Macuelizo, Santa Bárbara';
    $contenido_adicional = isset($data['contenido_adicional']) ? $data['contenido_adicional'] : '';
    $firma_empleado = isset($data['firma_empleado']) ? $data['firma_empleado'] : null;
    
    $stmt->bind_param(
        "ssssssssssi",
        $data['tipo'],
        $data['fecha_creacion'],
        $lugar,
        $data['nombre_completo'],
        $data['identidad'],
        $data['servicios'],
        $data['cargo'],
        $data['nombre_empresa'],
        $contenido_adicional,
        $firma_empleado,
        $data['id']
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar el contrato");
    }
    
    $stmt->close();
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Contrato actualizado correctamente'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
