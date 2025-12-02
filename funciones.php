<?php
session_start();
function VerificarSiUsuarioYaInicioSesion(){
    // Verificar si el usuario ha iniciado sesión
    if (!isset($_SESSION['usuario'])) {
        header("Location: login.php");
        exit();
    }
    
    // Si no existe user_id en la sesión, obtenerlo de la base de datos
    if (!isset($_SESSION['user_id'])) {
        $conexion = new mysqli("localhost", "root", "", "tiendasrey");
        
        if (!$conexion->connect_error) {
            $usuario = $_SESSION['usuario'];
            $stmt = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $_SESSION['user_id'] = $row['Id'];
            }
            
            $stmt->close();
            $conexion->close();
        }
    }
}




































?>
