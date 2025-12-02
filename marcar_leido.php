<?php
session_start();
header('Content-Type: application/json');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
    exit;
}

// Conexión a la base de datos
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener datos del usuario actual
 $resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
 $usuario_actual = $resultado->fetch_assoc();
 $Usuario_Id = $usuario_actual['Id'];

// Obtener ID del mensaje
 $data = json_decode(file_get_contents('php://input'), true);
 $mensaje_id = isset($data['mensaje_id']) ? intval($data['mensaje_id']) : 0;

if ($mensaje_id > 0) {
    // Marcar mensaje como leído
    $stmt = $conexion->prepare("UPDATE mensajes_chat SET Leido = 1 WHERE Id = ? AND Id_Receptor = ?");
    $stmt->bind_param("ii", $mensaje_id, $Usuario_Id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'ID de mensaje no válido']);
}
?>