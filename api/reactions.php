<?php
/**
 * API para manejo de reacciones múltiples
 * POST: Agregar/cambiar reacción
 * DELETE: Eliminar reacción
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$conexion = new mysqli("127.0.0.1", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}
$conexion->set_charset("utf8mb4");

// Obtener ID del usuario actual
$stmt = $conexion->prepare("SELECT Id, Nombre FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$usuario_actual = $result->fetch_assoc();
$stmt->close();

if (!$usuario_actual) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

$usuario_id = $usuario_actual['Id'];
$usuario_nombre = $usuario_actual['Nombre'];

// POST: Agregar o cambiar reacción
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $publicacion_id = (int)$data['publicacionId'];
    $tipo_reaccion = $data['reactionType']; // like, love, wow, sad, angry
    
    // Validar tipo de reacción
    $tipos_validos = ['like', 'love', 'wow', 'sad', 'angry'];
    if (!in_array($tipo_reaccion, $tipos_validos)) {
        echo json_encode(['success' => false, 'message' => 'Tipo de reacción inválido']);
        exit;
    }
    
    // Insertar o actualizar reacción (UNIQUE KEY maneja duplicados)
    $stmt = $conexion->prepare("INSERT INTO reacciones (publicacion_id, usuario_id, tipo_reaccion) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE tipo_reaccion = ?");
    $stmt->bind_param("iiss", $publicacion_id, $usuario_id, $tipo_reaccion, $tipo_reaccion);
    
    if ($stmt->execute()) {
        // Obtener conteo de reacciones por tipo
        $stmt_count = $conexion->prepare("SELECT tipo_reaccion, COUNT(*) as count FROM reacciones WHERE publicacion_id = ? GROUP BY tipo_reaccion");
        $stmt_count->bind_param("i", $publicacion_id);
        $stmt_count->execute();
        $result = $stmt_count->get_result();
        
        $reacciones = [];
        while ($row = $result->fetch_assoc()) {
            $reacciones[$row['tipo_reaccion']] = (int)$row['count'];
        }
        $stmt_count->close();
        
        echo json_encode([
            'success' => true,
            'reacciones' => $reacciones,
            'userReaction' => $tipo_reaccion,
            'userName' => $usuario_nombre
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al guardar reacción']);
    }
    
    $stmt->close();
}

// DELETE: Eliminar reacción
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $publicacion_id = (int)$_GET['publicacionId'];
    
    $stmt = $conexion->prepare("DELETE FROM reacciones WHERE publicacion_id = ? AND usuario_id = ?");
    $stmt->bind_param("ii", $publicacion_id, $usuario_id);
    
    if ($stmt->execute()) {
        // Obtener conteo actualizado
        $stmt_count = $conexion->prepare("SELECT tipo_reaccion, COUNT(*) as count FROM reacciones WHERE publicacion_id = ? GROUP BY tipo_reaccion");
        $stmt_count->bind_param("i", $publicacion_id);
        $stmt_count->execute();
        $result = $stmt_count->get_result();
        
        $reacciones = [];
        while ($row = $result->fetch_assoc()) {
            $reacciones[$row['tipo_reaccion']] = (int)$row['count'];
        }
        $stmt_count->close();
        
        echo json_encode(['success' => true, 'reacciones' => $reacciones]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar reacción']);
    }
    
    $stmt->close();
}

$conexion->close();
