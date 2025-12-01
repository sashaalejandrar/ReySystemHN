<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Obtener ID del usuario
$stmt_usuario = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
$stmt_usuario->bind_param("s", $_SESSION['usuario']);
$stmt_usuario->execute();
$resultado = $stmt_usuario->get_result();
$usuario = $resultado->fetch_assoc();
$stmt_usuario->close();

if (!$usuario) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

$usuario_id = $usuario['Id'];

// Obtener notificaciones pendientes
$query = "SELECT * FROM notificaciones 
          WHERE usuario_id = ? 
          ORDER BY fecha_creacion DESC 
          LIMIT 50";
          
$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$resultado = $stmt->get_result();

$notificaciones = [];
while ($row = $resultado->fetch_assoc()) {
    $notificaciones[] = $row;
}

$stmt->close();

// Contar no leídas
$query_count = "SELECT COUNT(*) as total FROM notificaciones 
                WHERE usuario_id = ? AND leida = 0";
$stmt_count = $conexion->prepare($query_count);
$stmt_count->bind_param("i", $usuario_id);
$stmt_count->execute();
$resultado_count = $stmt_count->get_result();
$count_row = $resultado_count->fetch_assoc();
$total_no_leidas = $count_row['total'];
$stmt_count->close();

$conexion->close();

echo json_encode([
    'success' => true,
    'notificaciones' => $notificaciones,
    'total_no_leidas' => $total_no_leidas
]);
?>
