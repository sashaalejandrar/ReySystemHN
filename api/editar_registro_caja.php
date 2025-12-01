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
$campos = $data['data'] ?? [];

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

try {
    // Construir query según la tabla
    if ($table === 'caja') {
        // Campos permitidos para tabla caja
        $monto_inicial = floatval($campos['monto_inicial'] ?? 0);
        $nota = $campos['nota'] ?? '';
        
        $stmt = $conexion->prepare("UPDATE caja SET monto_inicial = ?, Nota = ? WHERE Id = ?");
        $stmt->bind_param("dsi", $monto_inicial, $nota, $id);
        
    } elseif ($table === 'arqueo_caja') {
        // Campos permitidos para tabla arqueo_caja
        $efectivo = floatval($campos['efectivo'] ?? 0);
        $transferencia = floatval($campos['transferencia'] ?? 0);
        $tarjeta = floatval($campos['tarjeta'] ?? 0);
        $total = $efectivo + $transferencia + $tarjeta;
        $nota = $campos['nota'] ?? '';
        
        $stmt = $conexion->prepare("UPDATE arqueo_caja SET Efectivo = ?, Transferencia = ?, Tarjeta = ?, Total = ?, Nota_justi = ? WHERE Id = ?");
        $stmt->bind_param("ddddsi", $efectivo, $transferencia, $tarjeta, $total, $nota, $id);
        
    } elseif ($table === 'cierre_caja') {
        // Campos permitidos para tabla cierre_caja
        $efectivo = floatval($campos['efectivo'] ?? 0);
        $transferencia = floatval($campos['transferencia'] ?? 0);
        $tarjeta = floatval($campos['tarjeta'] ?? 0);
        $total = $efectivo + $transferencia + $tarjeta;
        $nota = $campos['nota'] ?? '';
        
        $stmt = $conexion->prepare("UPDATE cierre_caja SET Efectivo = ?, Transferencia = ?, Tarjeta = ?, Total = ?, Nota_Justifi = ? WHERE Id = ?");
        $stmt->bind_param("ddddsi", $efectivo, $transferencia, $tarjeta, $total, $nota, $id);
    }
    
    if ($stmt->execute()) {
        // Log de auditoría
        $log_stmt = $conexion->prepare("INSERT INTO auditoria (usuario, accion, tabla, registro_id, detalles, fecha) VALUES (?, 'UPDATE', ?, ?, ?, NOW())");
        $detalles = json_encode($campos);
        $log_stmt->bind_param("ssis", $_SESSION['usuario'], $table, $id, $detalles);
        $log_stmt->execute();
        $log_stmt->close();
        
        $stmt->close();
        $conexion->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Registro actualizado correctamente'
        ]);
    } else {
        throw new Exception('Error al actualizar el registro');
    }
} catch (Exception $e) {
    $conexion->close();
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
