<?php
/**
 * API: Create Contrato
 * Creates a new contract
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
    
    // Validate required fields
    $required = ['tipo', 'fecha_creacion', 'nombre_completo', 'identidad', 'servicios', 'cargo', 'nombre_empresa'];
    foreach ($required as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            throw new Exception("El campo $field es requerido");
        }
    }
    
    // Validate identity format (0000-0000-00000)
    if (!preg_match('/^\d{4}-\d{4}-\d{5}$/', $data['identidad'])) {
        throw new Exception("Formato de identidad inválido. Use: 0000-0000-00000");
    }
    
    // Validate tipo
    if (!in_array($data['tipo'], ['Contrato', 'Convenio'])) {
        throw new Exception("Tipo inválido. Use: Contrato o Convenio");
    }
    
    // Database connection
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión");
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Get user ID
    $stmt_user = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $_SESSION['usuario']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    
    if ($result_user->num_rows === 0) {
        throw new Exception("Usuario no encontrado");
    }
    
    $user_data = $result_user->fetch_assoc();
    $user_id = $user_data['Id'];
    $stmt_user->close();
    
    // Insert contract
    $sql = "
    INSERT INTO contratos (
        tipo, fecha_creacion, lugar, nombre_completo, identidad, 
        servicios, cargo, nombre_empresa, contenido_adicional, firma_empleado, creado_por
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
        $user_id
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar el contrato");
    }
    
    $contrato_id = $conexion->insert_id;
    
    $stmt->close();
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Contrato creado correctamente',
        'contrato_id' => $contrato_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
