<?php
session_start();
header('Content-Type: application/json');
include 'funciones.php'; // Para verificar sesión
VerificarSiUsuarioYaInicioSesion();

// Conexión a la base de datos
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Obtener datos del usuario actual
 $resultado = $conexion->query("SELECT Id FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
 $usuario_actual = $resultado->fetch_assoc();
 $usuario_id = $usuario_actual['Id'];

// Leer JSON de la petición
 $data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['mensaje_id'], $data['emoji'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

 $mensaje_id = intval($data['mensaje_id']);
 $emoji = $conexion->real_escape_string($data['emoji']);

// Obtener reacciones actuales
 $stmt = $conexion->prepare("SELECT Reacciones FROM mensajes_chat WHERE Id = ?");
 $stmt->bind_param("i", $mensaje_id);
 $stmt->execute();
 $resultado = $stmt->get_result();
 $mensaje = $resultado->fetch_assoc();

if ($mensaje) {
    $reacciones = [];
    if (!empty($mensaje['Reacciones'])) {
        $reacciones = json_decode($mensaje['Reacciones'], true);
    }

    // Si el emoji no existe, crear el array
    if (!isset($reacciones[$emoji])) {
        $reacciones[$emoji] = [];
    }

    // Evitar que el mismo usuario reaccione dos veces con el mismo emoji
    if (!in_array($usuario_id, $reacciones[$emoji])) {
        $reacciones[$emoji][] = $usuario_id;
    }

    // Convertir de nuevo a JSON y actualizar la base de datos
    $reacciones_json = json_encode($reacciones);
    $stmt_update = $conexion->prepare("UPDATE mensajes_chat SET Reacciones = ? WHERE Id = ?");
    $stmt_update->bind_param("si", $reacciones_json, $mensaje_id);

    if ($stmt_update->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Reacción agregada',
            'reacciones' => $reacciones // Devolver el array completo de reacciones
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar la base de datos']);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Mensaje no encontrado']);
}
?>