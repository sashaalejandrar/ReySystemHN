<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codigo = $_POST['codigo'] ?? '';
    $nuevo_precio = $_POST['nuevo_precio'] ?? '';

    if (!empty($codigo) && is_numeric($nuevo_precio)) {
        $stmt = $conexion->prepare("UPDATE stock SET Precio_Unitario = ? WHERE Codigo_Producto = ?");
        $stmt->bind_param("ds", $nuevo_precio, $codigo);
        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "¡Precio actualizado correctamente!";
        } else {
            $_SESSION['mensaje_error'] = "Error al actualizar el precio.";
        }
        $stmt->close();
    }
}

header("Location: consulta_edicion_precios.php");
exit();
