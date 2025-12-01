<?php
/**
 * API para notificaciones de red social en tiempo real
 * GET: Obtener notificaciones del usuario
 * POST: Marcar notificación como leída
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

// GET: Obtener notificaciones
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $solo_no_leidas = isset($_GET['unread']) && $_GET['unread'] === 'true';
    
    $query = "SELECT 
        n.*,
        u.Nombre as emisor_nombre,
        u.Perfil as emisor_perfil,
        p.contenido as publicacion_contenido
    FROM notificaciones_red n
    INNER JOIN usuarios u ON n.emisor_id = u.Id
    LEFT JOIN publicaciones p ON n.publicacion_id = p.id
    WHERE n.usuario_id = ?";
    
    if ($solo_no_leidas) {
        $query .= " AND n.leida = 0";
    }
    
    $query .= " ORDER BY n.fecha_creacion DESC LIMIT ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $usuario_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notificaciones = [];
    while ($row = $result->fetch_assoc()) {
        // Generar mensaje según tipo
        $mensaje = generarMensajeNotificacion($row);
        $row['mensaje_formateado'] = $mensaje;
        $notificaciones[] = $row;
    }
    
    echo json_encode(['success' => true, 'notificaciones' => $notificaciones]);
    $stmt->close();
}

// POST: Marcar como leída
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['notificacion_id'])) {
        // Marcar una notificación específica
        $notif_id = (int)$data['notificacion_id'];
        $stmt = $conexion->prepare("UPDATE notificaciones_red SET leida = 1, fecha_lectura = NOW() WHERE id = ? AND usuario_id = ?");
        $stmt->bind_param("ii", $notif_id, $usuario_id);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => 'Notificación marcada como leída']);
    } elseif (isset($data['marcar_todas'])) {
        // Marcar todas como leídas
        $stmt = $conexion->prepare("UPDATE notificaciones_red SET leida = 1, fecha_lectura = NOW() WHERE usuario_id = ? AND leida = 0");
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        echo json_encode(['success' => true, 'message' => "{$affected} notificaciones marcadas como leídas"]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    }
}

$conexion->close();

function generarMensajeNotificacion($notif) {
    $nombre = $notif['emisor_nombre'];
    
    switch ($notif['tipo']) {
        case 'like':
            return "{$nombre} le gustó tu publicación";
        case 'comment':
            return "{$nombre} comentó en tu publicación";
        case 'reaction':
            return "{$nombre} reaccionó a tu publicación";
        case 'mention':
            return "{$nombre} te mencionó en una publicación";
        case 'share':
            return "{$nombre} compartió tu publicación";
        case 'follow':
            return "{$nombre} comenzó a seguirte";
        case 'story_view':
            return "{$nombre} vio tu historia";
        default:
            return "{$nombre} interactuó con tu contenido";
    }
}
