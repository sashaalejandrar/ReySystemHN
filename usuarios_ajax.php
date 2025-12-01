<?php
// usuarios_ajax.php - Controlador para peticiones AJAX
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión no válida']);
    exit();
}

try {
    require_once 'config_users.php';
    require_once 'usuario.php';

    $database = new Database();
    $db = $database->getConnection();
    $usuario = new Usuario($db);

    header('Content-Type: application/json');

    $accion = isset($_POST['accion']) ? $_POST['accion'] : (isset($_GET['accion']) ? $_GET['accion'] : '');

    switch ($accion) {
        case 'listar':
            $busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
            $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
            $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 10;
            $estado = isset($_GET['estado']) ? $_GET['estado'] : '';
            
            $offset = ($pagina - 1) * $limite;
            
            $result = $usuario->listarUsuarios($busqueda, $limite, $offset, $estado);
            $total = $usuario->contarUsuarios($busqueda, $estado);
            
            $usuarios = array();
            while ($row = $result->fetch_assoc()) {
                $row['tiempo_actividad'] = $usuario->tiempoDesdeActividad($row['Ultima_Actividad']);
                $usuarios[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'usuarios' => $usuarios,
                'total' => $total,
                'pagina' => $pagina,
                'total_paginas' => ceil($total / $limite)
            ]);
            break;
        
    case 'obtener':
        $id = isset($_GET['id']) ? $_GET['id'] : '';
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'ID no proporcionado']);
            exit();
        }
        
        $datos = $usuario->obtenerPorId($id);
        
        if ($datos) {
            echo json_encode(['success' => true, 'usuario' => $datos]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        }
        break;
        
    case 'actualizar':
        $usuario->Id = $_POST['id'];
        $usuario->Nombre = $_POST['nombre'];
        $usuario->Apellido = $_POST['apellido'];
        $usuario->Email = $_POST['email'];
        $usuario->Celular = $_POST['celular'];
        $usuario->Usuario = $_POST['usuario'];
        $usuario->Rol = $_POST['rol'];
        $usuario->Perfil = isset($_POST['perfil']) ? $_POST['perfil'] : '';
        $usuario->Fecha_Nacimiento = $_POST['fecha_nacimiento'];
        $usuario->Cargo = $_POST['cargo'];
        $usuario->Estado_Online = $_POST['estado_online'];
        
        // Verificar si el email ya existe
        if ($usuario->emailExiste($usuario->Email, $usuario->Id)) {
            echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
            exit();
        }
        
        // Verificar si el usuario ya existe
        if ($usuario->usuarioExiste($usuario->Usuario, $usuario->Id)) {
            echo json_encode(['success' => false, 'message' => 'El nombre de usuario ya está en uso']);
            exit();
        }
        
        if ($usuario->actualizar()) {
            echo json_encode(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar usuario']);
        }
        break;
        
    case 'cambiar_clave':
        $usuario->Id = $_POST['id'];
        $usuario->Clave = $_POST['clave'];
        
        if ($usuario->actualizarClave()) {
            echo json_encode(['success' => true, 'message' => 'Contraseña actualizada correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar contraseña']);
        }
        break;
        
    case 'cambiar_estado':
        $id = $_POST['id'];
        $estado = $_POST['estado'];
        
        if ($usuario->cambiarEstado($id, $estado)) {
            echo json_encode(['success' => true, 'message' => 'Estado actualizado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
        }
        break;
        
    case 'eliminar':
        // Solo Admin puede eliminar
        if (isset($_SESSION['rol']) && $_SESSION['rol'] != 'Admin') {
            echo json_encode(['success' => false, 'message' => 'Solo administradores pueden eliminar usuarios']);
            exit();
        }
        
        $id = $_POST['id'];
        
        // No permitir eliminar el propio usuario
        if ($id == $_SESSION['usuario_id']) {
            echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propio usuario']);
            exit();
        }
        
        if ($usuario->eliminar($id)) {
            echo json_encode(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}

    $db->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>