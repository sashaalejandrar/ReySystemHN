<?php
session_start();

if (!isset($_SESSION['temp_usuario'])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    die("Error de conexión");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    $usuario = $_SESSION['temp_usuario'];
    $pin = $_POST['pin'];
    $pinHash = hash('sha256', $pin);
    
    // Verificar PIN
    $stmt = $conexion->prepare("SELECT id FROM pin_security WHERE idUsuario = ? AND pin_hash = ? AND enabled = 1");
    $stmt->bind_param("ss", $usuario, $pinHash);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // PIN correcto - completar login
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
        
        // Actualizar último uso
        $stmt_update = $conexion->prepare("UPDATE pin_security SET last_used = NOW() WHERE idUsuario = ?");
        $stmt_update->bind_param("s", $usuario);
        $stmt_update->execute();
        
        header("Location: index.php");
        exit();
    } else {
        // PIN incorrecto
        $_SESSION['pin_error'] = 'PIN incorrecto';
        header("Location: verify_login.php");
        exit();
    }
}

$conexion->close();
header("Location: verify_login.php");
exit();
?>
