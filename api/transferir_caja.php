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
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);
$table = $data['table'] ?? '';
$id = $data['id'] ?? 0;
$nuevo_usuario = $data['nuevo_usuario'] ?? '';

// Validar tabla
$allowed_tables = ['caja', 'arqueo_caja', 'cierre_caja'];
if (!in_array($table, $allowed_tables)) {
    echo json_encode(['success' => false, 'message' => 'Tabla no válida']);
    exit;
}

// Validar ID y usuario
if (!is_numeric($id) || $id <= 0 || empty($nuevo_usuario)) {
    echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
    exit;
}

// Verificar que el nuevo usuario existe
$stmt = $conexion->prepare("SELECT Usuario FROM usuarios WHERE Usuario = ?");
$stmt->bind_param("s", $nuevo_usuario);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}
$stmt->close();

// Actualizar usuario responsable
try {
    $stmt = $conexion->prepare("UPDATE $table SET Usuario = ? WHERE Id = ?");
    $stmt->bind_param("si", $nuevo_usuario, $id);
    
    if ($stmt->execute()) {
        // Log de auditoría
        $log_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles, fecha) VALUES (?, 'TRANSFER', ?, ?, ?, NOW())");
        $detalles = "Transferido a: $nuevo_usuario";
        $log_stmt->bind_param("ssis", $_SESSION['usuario'], $table, $id, $detalles);
        $log_stmt->execute();
        $log_stmt->close();
        
        $stmt->close();
        $conexion->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Responsabilidad transferida correctamente'
        ]);
    } else {
        throw new Exception('Error al transferir');
    }
} catch (Exception $e) {
    $conexion->close();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
