<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['temp_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

// Completar login sin verificación adicional
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
unset($_SESSION['temp_2fa_secret']);

echo json_encode(['success' => true, 'message' => 'Login completado']);
?>
