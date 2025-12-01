<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Conexión a la base de datos
require_once 'db_connect.php';

$usuario = $_SESSION['usuario'];

// Validar datos recibidos
if (isset($_POST['actual'], $_POST['nueva'], $_POST['confirmar'])) {
    $actualHash = hash("sha256", $_POST['actual']);
    $nueva = $_POST['nueva'];
    $confirmar = $_POST['confirmar'];

    if ($nueva !== $confirmar) {
        die("❌ Las contraseñas nuevas no coinciden.");
    }

    $nuevaHash = hash("sha256", $nueva);

    // Verificar contraseña actual
    $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND clave = '$actualHash'";
    $resultado = $conexion->query($sql);

    if ($resultado->num_rows === 1) {
        // Actualizar contraseña
        $update = "UPDATE usuarios SET clave = '$nuevaHash' WHERE usuario = '$usuario'";
        if ($conexion->query($update)) {
            echo "✅ Contraseña actualizada correctamente.";
            header("location:configuracion.php");
        } else {
            echo "❌ Error al actualizar la contraseña.";
        }
    } else {
        echo "❌ La contraseña actual es incorrecta.";
    }
} else {
    echo "❌ Faltan datos del formulario.";
}
?>
