<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_connect.php';

$usuario = $_SESSION['usuario'];

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $archivoTmp = $_FILES['avatar']['tmp_name'];
    $nombreOriginal = basename($_FILES['avatar']['name']);
    $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

    $permitidas = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($extension, $permitidas)) {
        die("Formato de imagen no permitido.");
    }

    $nombreFinal = "avatar_" . $usuario . "_" . time() . "." . $extension;
    $rutaDestino = "uploads/" . $nombreFinal;

    if (!is_dir("uploads")) {
        mkdir("uploads", 0755, true);
    }

    if (move_uploaded_file($archivoTmp, $rutaDestino)) {
        $sql = "UPDATE usuarios SET Perfil = '$rutaDestino' WHERE usuario = '$usuario'";
        if ($conexion->query($sql)) {
            header("Location: configuracion.php");
            exit();
        } else {
            echo "Error al actualizar la base de datos.";
        }
    } else {
        echo "Error al mover el archivo.";
    }
} else {
    echo "No se recibió ninguna imagen.";
}
?>
