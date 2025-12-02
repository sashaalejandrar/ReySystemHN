<?php
session_start();
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$chat_con = isset($_GET['chat']) ? intval($_GET['chat']) : 0;

if ($chat_con == 0) {
    echo json_encode(['success' => false, 'message' => 'Chat no especificado']);
    exit;
}

// Obtener ID del usuario actual
$resultado = $conexion->query("SELECT Id, Perfil FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
$usuario = $resultado->fetch_assoc();
$usuario_id = $usuario['Id'];
$perfil_usuario = $usuario['Perfil'];

// Obtener mensajes
$query_mensajes = "SELECT m.*, 
                          u1.Nombre as Emisor_Nombre, 
                          u1.Apellido as Emisor_Apellido, 
                          u1.Perfil as Emisor_Perfil
                   FROM mensajes_chat m
                   LEFT JOIN usuarios u1 ON m.Id_Emisor = u1.Id
                   WHERE (m.Id_Emisor = $usuario_id AND m.Id_Receptor = $chat_con)
                      OR (m.Id_Emisor = $chat_con AND m.Id_Receptor = $usuario_id)
                   ORDER BY m.Fecha_Mensaje ASC";

$resultado_mensajes = $conexion->query($query_mensajes);

$html = '';
while($mensaje = $resultado_mensajes->fetch_assoc()) {
    if ($mensaje['Id_Emisor'] == $usuario_id) {
        // Mensaje enviado
        $html .= '<div class="flex flex-row-reverse items-end gap-3">';
        $html .= '<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8" style="background-image: url(\'' . $perfil_usuario . '\');"></div>';
        $html .= '<div class="max-w-md space-y-1">';
        $html .= '<div class="rounded-lg rounded-br-none bg-primary p-3">';
        $html .= '<p class="text-sm text-white">' . htmlspecialchars($mensaje['Mensaje']) . '</p>';
        $html .= '</div>';
        $html .= '<p class="text-xs text-slate-500 dark:text-slate-400 text-right">' . date('g:i A', strtotime($mensaje['Fecha_Mensaje'])) . '</p>';
        $html .= '</div></div>';
    } else {
        // Mensaje recibido
        $html .= '<div class="flex items-end gap-3">';
        $html .= '<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8" style="background-image: url(\'' . $mensaje['Emisor_Perfil'] . '\');"></div>';
        $html .= '<div class="max-w-md space-y-1">';
        $html .= '<div class="rounded-lg rounded-bl-none bg-slate-200 dark:bg-slate-700 p-3">';
        $html .= '<p class="text-sm text-slate-800 dark:text-slate-200">' . htmlspecialchars($mensaje['Mensaje']) . '</p>';
        $html .= '</div>';
        $html .= '<p class="text-xs text-slate-500 dark:text-slate-400">' . date('g:i A', strtotime($mensaje['Fecha_Mensaje'])) . '</p>';
        $html .= '</div></div>';
    }
}

// Marcar mensajes como leídos
$conexion->query("UPDATE mensajes_chat SET leido = 1 WHERE Id_Emisor = $chat_con AND Id_Receptor = $usuario_id AND leido = 0");

echo json_encode([
    'success' => true,
    'html' => $html
]);

$conexion->close();
?>