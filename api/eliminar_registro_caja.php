<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión y método POST
if (!isset($_SESSION['usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include '../funciones.php';

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}
$conexion->set_charset("utf8mb4");

// Verificar que el usuario es admin
$stmt = $conexion->prepare("SELECT Rol FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($user['Rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo administradores']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);
$table = $data['table'] ?? '';
$id = $data['id'] ?? 0;

// Validar tabla
$allowed_tables = ['caja', 'arqueo_caja', 'cierre_caja'];
if (!in_array($table, $allowed_tables)) {
    echo json_encode(['success' => false, 'message' => 'Tabla no válida']);
    exit;
}

// Validar ID
if (!is_numeric($id) || $id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID no válido']);
    exit;
}

// Eliminar registro
try {
    $stmt = $conexion->prepare("DELETE FROM $table WHERE Id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Log de auditoría (opcional)
        try {
            $log_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion, tabla, registro_id, fecha) VALUES (?, 'DELETE', ?, ?, NOW())");
            if ($log_stmt) {
                $log_stmt->bind_param("ssi", $_SESSION['usuario'], $table, $id);
                $log_stmt->execute();
                $log_stmt->close();
            }
        } catch (Exception $log_error) {
            // Ignorar errores de auditoría
        }
        
        $stmt->close();
        $conexion->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Registro eliminado correctamente'
        ]);
    } else {
        throw new Exception('Error al eliminar el registro');
    }
} catch (Exception $e) {
    $conexion->close();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
