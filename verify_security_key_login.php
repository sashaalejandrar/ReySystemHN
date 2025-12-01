<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['temp_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['credentialId'])) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit();
}

$usuario = $_SESSION['temp_usuario'];
$credentialId = $input['credentialId'];

// Verificar que la credencial pertenece al usuario
$stmt = $conexion->prepare("SELECT id FROM security_keys WHERE idUsuario = ? AND credential_id = ? AND enabled = 1");
$stmt->bind_param("ss", $usuario, $credentialId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Verificación exitosa - completar login
    $_SESSION["usuario"] = $_SESSION['temp_usuario'];
    $_SESSION["user_id"] = $_SESSION['temp_user_id'];
    $_SESSION['usuario_id'] = $_SESSION['temp_user_id'];
    $_SESSION['rol'] = $_SESSION['temp_rol'];
    $_SESSION['perfil'] = $_SESSION['temp_perfil'];
    $_SESSION['nombre'] = $_SESSION['temp_nombre'];
    
    // Limpiar sesión temporal
    unset($_SESSION['temp_usuario']);
    unset($_SESSION['temp_user_id']);
    unset($_SESSION['temp_rol']);
    unset($_SESSION['temp_perfil']);
    unset($_SESSION['temp_nombre']);
    unset($_SESSION['temp_security_check']);
    
    // Actualizar último uso de la llave
    $stmt_update = $conexion->prepare("UPDATE security_keys SET last_used = NOW() WHERE credential_id = ?");
    $stmt_update->bind_param("s", $credentialId);
    $stmt_update->execute();
    
    echo json_encode(['success' => true, 'message' => 'Verificación exitosa']);
} else {
    echo json_encode(['success' => false, 'message' => 'Llave de seguridad no válida']);
}

$conexion->close();
?>
