<?php
/**
 * API para manejo de publicaciones
 * GET: Obtener publicaciones (con paginación para infinite scroll)
 * POST: Crear nueva publicación
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

// GET: Obtener publicaciones
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    $query = "SELECT 
        p.*,
        u.Nombre,
        u.Apellido,
        u.Perfil,
        u.Cargo,
        (SELECT COUNT(*) FROM reacciones WHERE publicacion_id = p.id) as total_reacciones,
        (SELECT COUNT(*) FROM comentarios WHERE publicacion_id = p.id) as total_comentarios,
        (SELECT tipo_reaccion FROM reacciones WHERE publicacion_id = p.id AND usuario_id = ?) as user_reaction
    FROM publicaciones p
    INNER JOIN usuarios u ON p.usuario_id = u.Id
    ORDER BY p.fecha_creacion DESC
    LIMIT ? OFFSET ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("iii", $usuario_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $publicaciones = [];
    while ($row = $result->fetch_assoc()) {
        // Obtener archivos multimedia
        $stmt_archivos = $conexion->prepare("SELECT * FROM archivos_multimedia WHERE publicacion_id = ?");
        $stmt_archivos->bind_param("i", $row['id']);
        $stmt_archivos->execute();
        $result_archivos = $stmt_archivos->get_result();
        $row['archivos'] = [];
        while ($archivo = $result_archivos->fetch_assoc()) {
            $row['archivos'][] = $archivo;
        }
        $stmt_archivos->close();
        
        $publicaciones[] = $row;
    }
    
    echo json_encode(['success' => true, 'publicaciones' => $publicaciones, 'page' => $page]);
    $stmt->close();
}

// POST: Crear nueva publicación
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $contenido = $conexion->real_escape_string($data['contenido'] ?? '');
    $audience = $data['audience'] ?? 'todos';
    
    $stmt = $conexion->prepare("INSERT INTO publicaciones (usuario_id, contenido, audience, fecha_creacion) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $usuario_id, $contenido, $audience);
    
    if ($stmt->execute()) {
        $publicacion_id = $stmt->insert_id;
        
        // Obtener datos completos de la publicación
        $stmt_get = $conexion->prepare("SELECT p.*, u.Nombre, u.Apellido, u.Perfil, u.Cargo FROM publicaciones p INNER JOIN usuarios u ON p.usuario_id = u.Id WHERE p.id = ?");
        $stmt_get->bind_param("i", $publicacion_id);
        $stmt_get->execute();
        $result = $stmt_get->get_result();
        $publicacion = $result->fetch_assoc();
        $stmt_get->close();
        
        echo json_encode(['success' => true, 'publicacion' => $publicacion]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear publicación']);
    }
    
    $stmt->close();
}

$conexion->close();
