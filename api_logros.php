<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit();
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['error' => 'Error de conexión']);
    exit();
}

$action = $_GET['action'] ?? '';
$usuario = $_SESSION['usuario'];

switch ($action) {
    case 'obtener_logros':
        $usuario_param = $_GET['usuario'] ?? $usuario;
        
        $stmt = $conexion->prepare("
            SELECT l.*, 
                COALESCE(ul.progreso_actual, 0) as progreso_actual,
                COALESCE(ul.completado, 0) as completado,
                ul.fecha_desbloqueo
            FROM logros l
            LEFT JOIN usuarios_logros ul ON l.id = ul.logro_id AND ul.usuario = ?
            WHERE l.activo = 1
            ORDER BY ul.completado DESC, l.puntos ASC
        ");
        $stmt->bind_param("s", $usuario_param);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logros = [];
        while ($row = $result->fetch_assoc()) {
            $logros[] = $row;
        }
        
        echo json_encode(['logros' => $logros]);
        $stmt->close();
        break;
        
    case 'verificar_nuevos':
        // Obtener logros desbloqueados en las últimas 24 horas
        $stmt = $conexion->prepare("
            SELECT l.*, ul.fecha_desbloqueo 
            FROM usuarios_logros ul
            JOIN logros l ON ul.logro_id = l.id
            WHERE ul.usuario = ? 
            AND ul.completado = 1 
            AND ul.fecha_desbloqueo >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY ul.fecha_desbloqueo DESC
        ");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logros = [];
        while ($row = $result->fetch_assoc()) {
            $logros[] = [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'descripcion' => $row['descripcion'],
                'icono' => $row['icono'],
                'puntos' => $row['puntos'],
                'color' => $row['color'],
                'fecha_desbloqueo' => $row['fecha_desbloqueo']
            ];
        }
        
        echo json_encode(['logros' => $logros]);
        $stmt->close();
        break;
        
    case 'crear_logro':
        // Solo Admin
        $resultado = $conexion->query("SELECT Rol FROM usuarios WHERE usuario = '$usuario'");
        $user = $resultado->fetch_assoc();
        
        if (strtolower($user['Rol']) !== 'admin') {
            echo json_encode(['error' => 'No autorizado']);
            exit();
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $stmt = $conexion->prepare("INSERT INTO logros (nombre, descripcion, icono, tipo_condicion, valor_objetivo, puntos, color, es_predefinido, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");
        $stmt->bind_param("ssssiiis", 
            $data['nombre'],
            $data['descripcion'],
            $data['icono'],
            $data['tipo_condicion'],
            $data['valor_objetivo'],
            $data['puntos'],
            $data['color'],
            $usuario
        );
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $conexion->insert_id]);
        } else {
            echo json_encode(['error' => $stmt->error]);
        }
        $stmt->close();
        break;
        
    case 'eliminar_logro':
        // Solo Admin
        $resultado = $conexion->query("SELECT Rol FROM usuarios WHERE usuario = '$usuario'");
        $user = $resultado->fetch_assoc();
        
        if (strtolower($user['Rol']) !== 'admin') {
            echo json_encode(['error' => 'No autorizado']);
            exit();
        }
        
        $logro_id = intval($_GET['id'] ?? 0);
        
        // Verificar que no sea predefinido
        $check = $conexion->query("SELECT es_predefinido FROM logros WHERE id = $logro_id");
        $logro = $check->fetch_assoc();
        
        if ($logro && $logro['es_predefinido'] == 0) {
            $conexion->query("DELETE FROM logros WHERE id = $logro_id");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'No se pueden eliminar logros predefinidos']);
        }
        break;
        
    case 'estadisticas':
        $stmt = $conexion->prepare("
            SELECT 
                COUNT(*) as total_logros,
                SUM(CASE WHEN ul.completado = 1 THEN 1 ELSE 0 END) as completados,
                SUM(CASE WHEN ul.completado = 1 THEN l.puntos ELSE 0 END) as puntos_totales
            FROM logros l
            LEFT JOIN usuarios_logros ul ON l.id = ul.logro_id AND ul.usuario = ?
            WHERE l.activo = 1
        ");
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $stats = $stmt->get_result()->fetch_assoc();
        
        echo json_encode($stats);
        $stmt->close();
        break;
        
    default:
        echo json_encode(['error' => 'Acción no válida']);
        break;
}

$conexion->close();
?>
