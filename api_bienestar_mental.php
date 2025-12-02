<?php
session_start();
header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$usuario = $_SESSION['usuario'];
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

$conexion->set_charset("utf8mb4");

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ============================================
// FUNCIONES DE ÁNIMO
// ============================================
if ($action === 'save_mood') {
    $nivel = intval($_POST['nivel']);
    
    if ($nivel < 1 || $nivel > 5) {
        echo json_encode(['error' => 'Nivel inválido']);
        exit;
    }
    
    $stmt = $conexion->prepare("INSERT INTO bienestar_animo (usuario, nivel) VALUES (?, ?)");
    $stmt->bind_param("si", $usuario, $nivel);
    
    if ($stmt->execute()) {
        // Registrar actividad
        logActivity($conexion, $usuario, 'mood_entry', "Ánimo: $nivel/5");
        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['error' => 'Error al guardar']);
    }
    $stmt->close();
}

elseif ($action === 'get_moods') {
    $limit = intval($_GET['limit'] ?? 100);
    
    $stmt = $conexion->prepare("SELECT nivel, fecha FROM bienestar_animo WHERE usuario = ? ORDER BY fecha DESC LIMIT ?");
    $stmt->bind_param("si", $usuario, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $moods = [];
    while ($row = $result->fetch_assoc()) {
        $moods[] = [
            'level' => intval($row['nivel']),
            'date' => $row['fecha']
        ];
    }
    
    echo json_encode($moods);
    $stmt->close();
}

// ============================================
// FUNCIONES DE GRATITUD
// ============================================
elseif ($action === 'save_gratitude') {
    $item1 = $_POST['item1'] ?? '';
    $item2 = $_POST['item2'] ?? '';
    $item3 = $_POST['item3'] ?? '';
    
    $stmt = $conexion->prepare("INSERT INTO bienestar_gratitud (usuario, item1, item2, item3) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $usuario, $item1, $item2, $item3);
    
    if ($stmt->execute()) {
        $count = 0;
        if ($item1) $count++;
        if ($item2) $count++;
        if ($item3) $count++;
        logActivity($conexion, $usuario, 'gratitude_entry', "$count items");
        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['error' => 'Error al guardar']);
    }
    $stmt->close();
}

elseif ($action === 'get_gratitudes') {
    $limit = intval($_GET['limit'] ?? 100);
    
    $stmt = $conexion->prepare("SELECT item1, item2, item3, fecha FROM bienestar_gratitud WHERE usuario = ? ORDER BY fecha DESC LIMIT ?");
    $stmt->bind_param("si", $usuario, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $gratitudes = [];
    while ($row = $result->fetch_assoc()) {
        $items = [];
        if ($row['item1']) $items[] = $row['item1'];
        if ($row['item2']) $items[] = $row['item2'];
        if ($row['item3']) $items[] = $row['item3'];
        
        $gratitudes[] = [
            'items' => $items,
            'date' => $row['fecha']
        ];
    }
    
    echo json_encode($gratitudes);
    $stmt->close();
}

// ============================================
// FUNCIONES DE LOGROS
// ============================================
elseif ($action === 'save_win') {
    $texto = $_POST['texto'] ?? '';
    
    if (empty($texto)) {
        echo json_encode(['error' => 'Texto vacío']);
        exit;
    }
    
    $stmt = $conexion->prepare("INSERT INTO bienestar_logros (usuario, texto) VALUES (?, ?)");
    $stmt->bind_param("ss", $usuario, $texto);
    
    if ($stmt->execute()) {
        logActivity($conexion, $usuario, 'win_added', $texto);
        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['error' => 'Error al guardar']);
    }
    $stmt->close();
}

elseif ($action === 'get_wins') {
    $limit = intval($_GET['limit'] ?? 100);
    
    $stmt = $conexion->prepare("SELECT texto, fecha FROM bienestar_logros WHERE usuario = ? ORDER BY fecha DESC LIMIT ?");
    $stmt->bind_param("si", $usuario, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $wins = [];
    while ($row = $result->fetch_assoc()) {
        $wins[] = [
            'text' => $row['texto'],
            'date' => $row['fecha']
        ];
    }
    
    echo json_encode($wins);
    $stmt->close();
}

// ============================================
// FUNCIONES DE RUTINAS
// ============================================
elseif ($action === 'save_routine') {
    $texto = $_POST['texto'] ?? '';
    
    if (empty($texto)) {
        echo json_encode(['error' => 'Texto vacío']);
        exit;
    }
    
    $stmt = $conexion->prepare("INSERT INTO bienestar_rutinas (usuario, texto) VALUES (?, ?)");
    $stmt->bind_param("ss", $usuario, $texto);
    
    if ($stmt->execute()) {
        logActivity($conexion, $usuario, 'routine_added', $texto);
        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['error' => 'Error al guardar']);
    }
    $stmt->close();
}

elseif ($action === 'get_routines') {
    $stmt = $conexion->prepare("SELECT id, texto, completada, fecha_creacion FROM bienestar_rutinas WHERE usuario = ? ORDER BY fecha_creacion DESC");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $routines = [];
    while ($row = $result->fetch_assoc()) {
        $routines[] = [
            'id' => intval($row['id']),
            'text' => $row['texto'],
            'done' => (bool)$row['completada']
        ];
    }
    
    echo json_encode($routines);
    $stmt->close();
}

elseif ($action === 'toggle_routine') {
    $id = intval($_POST['id']);
    
    $stmt = $conexion->prepare("UPDATE bienestar_rutinas SET completada = NOT completada, fecha_completada = IF(completada, NULL, NOW()) WHERE id = ? AND usuario = ?");
    $stmt->bind_param("is", $id, $usuario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error al actualizar']);
    }
    $stmt->close();
}

elseif ($action === 'delete_routine') {
    $id = intval($_POST['id']);
    
    $stmt = $conexion->prepare("DELETE FROM bienestar_rutinas WHERE id = ? AND usuario = ?");
    $stmt->bind_param("is", $id, $usuario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error al eliminar']);
    }
    $stmt->close();
}

// ============================================
// FUNCIONES DE TAREAS RÁPIDAS
// ============================================
elseif ($action === 'save_quick_task') {
    $texto = $_POST['texto'] ?? '';
    
    if (empty($texto)) {
        echo json_encode(['error' => 'Texto vacío']);
        exit;
    }
    
    $stmt = $conexion->prepare("INSERT INTO bienestar_tareas_rapidas (usuario, texto) VALUES (?, ?)");
    $stmt->bind_param("ss", $usuario, $texto);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['error' => 'Error al guardar']);
    }
    $stmt->close();
}

elseif ($action === 'get_quick_tasks') {
    $stmt = $conexion->prepare("SELECT id, texto, completada FROM bienestar_tareas_rapidas WHERE usuario = ? ORDER BY fecha_creacion DESC");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = [
            'id' => intval($row['id']),
            'text' => $row['texto'],
            'done' => (bool)$row['completada']
        ];
    }
    
    echo json_encode($tasks);
    $stmt->close();
}

elseif ($action === 'toggle_quick_task') {
    $id = intval($_POST['id']);
    
    $stmt = $conexion->prepare("UPDATE bienestar_tareas_rapidas SET completada = NOT completada, fecha_completada = IF(completada, NULL, NOW()) WHERE id = ? AND usuario = ?");
    $stmt->bind_param("is", $id, $usuario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error al actualizar']);
    }
    $stmt->close();
}

elseif ($action === 'delete_quick_task') {
    $id = intval($_POST['id']);
    
    $stmt = $conexion->prepare("DELETE FROM bienestar_tareas_rapidas WHERE id = ? AND usuario = ?");
    $stmt->bind_param("is", $id, $usuario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error al eliminar']);
    }
    $stmt->close();
}

// ============================================
// FUNCIONES DE PREOCUPACIONES
// ============================================
elseif ($action === 'save_worry') {
    $texto = $_POST['texto'] ?? '';
    
    if (empty($texto)) {
        echo json_encode(['error' => 'Texto vacío']);
        exit;
    }
    
    $stmt = $conexion->prepare("INSERT INTO bienestar_preocupaciones (usuario, texto) VALUES (?, ?)");
    $stmt->bind_param("ss", $usuario, $texto);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['error' => 'Error al guardar']);
    }
    $stmt->close();
}

elseif ($action === 'get_worries') {
    $limit = intval($_GET['limit'] ?? 100);
    
    $stmt = $conexion->prepare("SELECT texto, fecha FROM bienestar_preocupaciones WHERE usuario = ? ORDER BY fecha DESC LIMIT ?");
    $stmt->bind_param("si", $usuario, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $worries = [];
    while ($row = $result->fetch_assoc()) {
        $worries[] = [
            'text' => $row['texto'],
            'date' => $row['fecha']
        ];
    }
    
    echo json_encode($worries);
    $stmt->close();
}

// ============================================
// FUNCIONES DE POMODORO
// ============================================
elseif ($action === 'increment_pomodoro') {
    $fecha = date('Y-m-d');
    
    $stmt = $conexion->prepare("INSERT INTO bienestar_pomodoro (usuario, fecha, cantidad) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE cantidad = cantidad + 1");
    $stmt->bind_param("ss", $usuario, $fecha);
    
    if ($stmt->execute()) {
        logActivity($conexion, $usuario, 'pomodoro_completed', '25 minutos');
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error al guardar']);
    }
    $stmt->close();
}

elseif ($action === 'get_pomodoro_data') {
    $stmt = $conexion->prepare("SELECT fecha, cantidad FROM bienestar_pomodoro WHERE usuario = ? ORDER BY fecha DESC LIMIT 30");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row['fecha']] = intval($row['cantidad']);
    }
    
    echo json_encode($data);
    $stmt->close();
}

// ============================================
// FUNCIONES DE ACHIEVEMENTS
// ============================================
elseif ($action === 'unlock_achievement') {
    $achievement_id = $_POST['achievement_id'] ?? '';
    
    if (empty($achievement_id)) {
        echo json_encode(['error' => 'ID vacío']);
        exit;
    }
    
    $stmt = $conexion->prepare("INSERT IGNORE INTO bienestar_achievements (usuario, achievement_id) VALUES (?, ?)");
    $stmt->bind_param("ss", $usuario, $achievement_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'new' => $stmt->affected_rows > 0]);
    } else {
        echo json_encode(['error' => 'Error al guardar']);
    }
    $stmt->close();
}

elseif ($action === 'get_achievements') {
    $stmt = $conexion->prepare("SELECT achievement_id, fecha_desbloqueo FROM bienestar_achievements WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $achievements = [];
    while ($row = $result->fetch_assoc()) {
        $achievements[] = $row['achievement_id'];
    }
    
    echo json_encode($achievements);
    $stmt->close();
}

// ============================================
// FUNCIONES DE CONTADORES
// ============================================
elseif ($action === 'increment_counter') {
    $tipo = $_POST['tipo'] ?? '';
    
    if (empty($tipo)) {
        echo json_encode(['error' => 'Tipo vacío']);
        exit;
    }
    
    $stmt = $conexion->prepare("INSERT INTO bienestar_contadores (usuario, tipo, valor) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE valor = valor + 1");
    $stmt->bind_param("ss", $usuario, $tipo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error al guardar']);
    }
    $stmt->close();
}

elseif ($action === 'get_counter') {
    $tipo = $_GET['tipo'] ?? '';
    
    if (empty($tipo)) {
        echo json_encode(['error' => 'Tipo vacío']);
        exit;
    }
    
    $stmt = $conexion->prepare("SELECT valor FROM bienestar_contadores WHERE usuario = ? AND tipo = ?");
    $stmt->bind_param("ss", $usuario, $tipo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['value' => intval($row['valor'])]);
    } else {
        echo json_encode(['value' => 0]);
    }
    $stmt->close();
}

// ============================================
// FUNCIONES DE ESTADÍSTICAS
// ============================================
elseif ($action === 'get_stats') {
    $stats = [];
    
    // Total de actividades
    $result = $conexion->query("SELECT COUNT(*) as total FROM bienestar_actividades WHERE usuario = '$usuario'");
    $stats['totalActivities'] = $result->fetch_assoc()['total'];
    
    // Actividades hoy
    $result = $conexion->query("SELECT COUNT(*) as total FROM bienestar_actividades WHERE usuario = '$usuario' AND DATE(fecha) = CURDATE()");
    $stats['todayActivities'] = $result->fetch_assoc()['total'];
    
    // Racha actual
    $stats['currentStreak'] = calculateStreak($conexion, $usuario);
    
    // Total pomodoros
    $result = $conexion->query("SELECT SUM(cantidad) as total FROM bienestar_pomodoro WHERE usuario = '$usuario'");
    $row = $result->fetch_assoc();
    $stats['totalPomodoros'] = intval($row['total'] ?? 0);
    
    // Entradas de ánimo
    $result = $conexion->query("SELECT COUNT(*) as total FROM bienestar_animo WHERE usuario = '$usuario'");
    $stats['moodEntries'] = $result->fetch_assoc()['total'];
    
    // Promedio de ánimo (últimos 7 días)
    $result = $conexion->query("SELECT AVG(nivel) as avg FROM bienestar_animo WHERE usuario = '$usuario' AND fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $row = $result->fetch_assoc();
    $stats['avgMood'] = round($row['avg'] ?? 0, 1);
    
    // Entradas de gratitud
    $result = $conexion->query("SELECT COUNT(*) as total FROM bienestar_gratitud WHERE usuario = '$usuario'");
    $stats['gratitudeEntries'] = $result->fetch_assoc()['total'];
    
    // Juegos de patrones
    $result = $conexion->query("SELECT valor FROM bienestar_contadores WHERE usuario = '$usuario' AND tipo = 'pattern_games'");
    $row = $result->fetch_assoc();
    $stats['patternGames'] = intval($row['valor'] ?? 0);
    
    // Sesiones de respiración
    $result = $conexion->query("SELECT valor FROM bienestar_contadores WHERE usuario = '$usuario' AND tipo = 'breathing_sessions'");
    $row = $result->fetch_assoc();
    $stats['breathingSessions'] = intval($row['valor'] ?? 0);
    
    echo json_encode($stats);
}

// ============================================
// REGISTRO DE ACTIVIDADES
// ============================================
elseif ($action === 'log_activity') {
    $tipo = $_POST['tipo'] ?? '';
    $detalles = $_POST['detalles'] ?? '';
    
    if (empty($tipo)) {
        echo json_encode(['error' => 'Tipo vacío']);
        exit;
    }
    
    if (logActivity($conexion, $usuario, $tipo, $detalles)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Error al guardar']);
    }
}

else {
    http_response_code(400);
    echo json_encode(['error' => 'Acción no válida']);
}

$conexion->close();

// ============================================
// FUNCIONES AUXILIARES
// ============================================
function logActivity($conexion, $usuario, $tipo, $detalles = '') {
    $stmt = $conexion->prepare("INSERT INTO bienestar_actividades (usuario, tipo, detalles) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $usuario, $tipo, $detalles);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

function calculateStreak($conexion, $usuario) {
    $result = $conexion->query("SELECT DISTINCT DATE(fecha) as fecha FROM bienestar_actividades WHERE usuario = '$usuario' ORDER BY fecha DESC LIMIT 365");
    
    $dates = [];
    while ($row = $result->fetch_assoc()) {
        $dates[] = $row['fecha'];
    }
    
    if (empty($dates)) return 0;
    
    $streak = 0;
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // Si no hay actividad hoy ni ayer, la racha es 0
    if ($dates[0] !== $today && $dates[0] !== $yesterday) {
        return 0;
    }
    
    $currentDate = new DateTime($dates[0]);
    $streak = 1;
    
    for ($i = 1; $i < count($dates); $i++) {
        $prevDate = new DateTime($dates[$i]);
        $diff = $currentDate->diff($prevDate)->days;
        
        if ($diff === 1) {
            $streak++;
            $currentDate = $prevDate;
        } else {
            break;
        }
    }
    
    return $streak;
}
?>
