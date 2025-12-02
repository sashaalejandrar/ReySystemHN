<?php
/**
 * API para manejo de stories
 * GET: Obtener stories activas
 * POST: Crear nueva story
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
$stmt = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
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

// GET: Obtener stories activas (últimas 24h)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $query = "SELECT 
        s.*,
        u.Nombre,
        u.Apellido,
        u.Perfil,
        (SELECT COUNT(*) FROM story_vistas WHERE story_id = s.id) as total_vistas,
        (SELECT COUNT(*) FROM story_vistas WHERE story_id = s.id AND usuario_id = ?) as user_viewed
    FROM stories s
    INNER JOIN usuarios u ON s.usuario_id = u.Id
    WHERE s.activo = 1 AND s.expira_en > NOW()
    ORDER BY s.fecha_creacion DESC";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stories = [];
    while ($row = $result->fetch_assoc()) {
        $stories[] = $row;
    }
    
    echo json_encode(['success' => true, 'stories' => $stories]);
    $stmt->close();
}

// POST: Crear nueva story
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $tipo = $data['tipo'] ?? 'texto'; // imagen, video, texto
    $contenido = $conexion->real_escape_string($data['contenido'] ?? '');
    $archivo_url = $data['archivo_url'] ?? null;
    
    // Calcular fecha de expiración (24 horas)
    $expira_en = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $stmt = $conexion->prepare("INSERT INTO stories (usuario_id, tipo, contenido, archivo_url, expira_en) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $usuario_id, $tipo, $contenido, $archivo_url, $expira_en);
    
    if ($stmt->execute()) {
        $story_id = $stmt->insert_id;
        
        // Obtener datos completos de la story
        $stmt_get = $conexion->prepare("SELECT s.*, u.Nombre, u.Apellido, u.Perfil FROM stories s INNER JOIN usuarios u ON s.usuario_id = u.Id WHERE s.id = ?");
        $stmt_get->bind_param("i", $story_id);
        $stmt_get->execute();
        $result = $stmt_get->get_result();
        $story = $result->fetch_assoc();
        $stmt_get->close();
        
        echo json_encode(['success' => true, 'story' => $story]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear story']);
    }
    
    $stmt->close();
}

$conexion->close();
