<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexión
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

// Validar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no iniciada']);
    exit;
}

// Leer JSON
 $json = file_get_contents('php://input');
 $data = json_decode($json, true);
if (!$data || !isset($data['destinatario_id'], $data['mensaje'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Obtener datos del usuario actual
 $usuario = $conexion->real_escape_string($_SESSION['usuario']);
 $res = $conexion->query("SELECT Id, Nombre, Apellido, Perfil FROM usuarios WHERE usuario = '$usuario'");
if (!$res || $res->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}
 $usuario_data = $res->fetch_assoc();
 $usuario_id = $usuario_data['Id'];
 $nombre = $usuario_data['Nombre'];
 $apellido = $usuario_data['Apellido'];
 $perfil = $usuario_data['Perfil'];

// Preparar datos del mensaje
 $destinatario_id = intval($data['destinatario_id']);
 $mensaje = $conexion->real_escape_string($data['mensaje']);

// Insertar mensaje
 $stmt = $conexion->prepare("INSERT INTO mensajes_chat (Id_Emisor, Id_Receptor, Usuario, Mensaje, Fecha_Mensaje, leido, Nombre, Apellido, Perfil) VALUES (?, ?, ?, ?, NOW(), 0, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar consulta: ' . $conexion->error]);
    exit;
}
 $stmt->bind_param("iisssss", $usuario_id, $destinatario_id, $usuario, $mensaje, $nombre, $apellido, $perfil);

if ($stmt->execute()) {
    // --- CORRECCIÓN AQUÍ ---
    // Devuelve el ID del emisor y su perfil para que el frontend sepa dónde dibujar el mensaje.
    echo json_encode([
        'success' => true,
        'message' => 'Mensaje enviado',
        'id' => $conexion->insert_id, // ID del nuevo mensaje
        'id_emisor' => $usuario_id,     // <-- ¡CLAVE! ID del que envía
        'emisor_perfil' => $perfil,    // <-- ¡CLAVE! Perfil del que envía
        'mensaje' => $mensaje,
        'fecha' => date('g:i A'),
        'nombre' => $nombre,
        'apellido' => $apellido,
        'usuario'=> $usuario
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al ejecutar: ' . $stmt->error]);
}
?>