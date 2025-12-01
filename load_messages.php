<?php
session_start();
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Obtener ID de usuario actual
$query_user = "SELECT Id FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'";
$result_user = $conexion->query($query_user);
$user_data = $result_user->fetch_assoc();
$currentUserId = $user_data['Id'];

$chatWithId = isset($_GET['chat_with']) ? intval($_GET['chat_with']) : 0;
$beforeId = isset($_GET['before_id']) ? intval($_GET['before_id']) : 0;

if ($chatWithId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de chat inválido']);
    exit;
}

// Construir consulta
$whereClause = "((Id_Emisor = $currentUserId AND Id_Receptor = $chatWithId) 
                OR (Id_Emisor = $chatWithId AND Id_Receptor = $currentUserId))";

if ($beforeId > 0) {
    $whereClause .= " AND Id < $beforeId";
}

$query = "SELECT m.*, 
          u1.Nombre as Emisor_Nombre, u1.Apellido as Emisor_Apellido, u1.Perfil as Emisor_Perfil
          FROM mensajes_chat m
          LEFT JOIN usuarios u1 ON m.Id_Emisor = u1.Id
          WHERE $whereClause
          ORDER BY m.Fecha_Mensaje DESC
          LIMIT 50";

$result = $conexion->query($query);
$messages = [];

while ($row = $result->fetch_assoc()) {
    // Formatear fecha
    $row['Fecha_Formateada'] = date('g:i A', strtotime($row['Fecha_Mensaje']));
    $row['Fecha_Completa'] = $row['Fecha_Mensaje'];
    $messages[] = $row;
}

// Reordenar cronológicamente para el frontend (aunque el frontend suele hacer prepend)
// $messages = array_reverse($messages);

echo json_encode([
    'success' => true,
    'messages' => $messages,
    'hasMore' => count($messages) === 50
]);

$conexion->close();
?>
