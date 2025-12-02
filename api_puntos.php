<?php
/**
 * API REST para operaciones de puntos
 * Maneja consultas, canjes y historial
 */

header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'funciones_puntos.php';

session_start();
$usuario = $_SESSION['usuario'] ?? 'sistema';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'consultar':
        // Consultar puntos de un cliente
        $cliente = $_GET['cliente'] ?? '';
        
        if (empty($cliente)) {
            echo json_encode(['success' => false, 'message' => 'Cliente requerido']);
            exit;
        }
        
        $datos = obtenerPuntosCliente($cliente);
        
        if ($datos) {
            echo json_encode(['success' => true, 'datos' => $datos]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Cliente no encontrado']);
        }
        break;
        
    case 'canjear':
        // Canjear puntos
        $cliente = $_POST['cliente'] ?? '';
        $puntos = intval($_POST['puntos'] ?? 0);
        
        if (empty($cliente) || $puntos <= 0) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit;
        }
        
        $resultado = canjearPuntos($cliente, $puntos, 'Canje manual', $usuario);
        echo json_encode($resultado);
        break;
        
    case 'historial':
        // Obtener historial de un cliente
        $cliente = $_GET['cliente'] ?? '';
        
        if (empty($cliente)) {
            echo json_encode(['success' => false, 'message' => 'Cliente requerido']);
            exit;
        }
        
        $historial = obtenerHistorial($cliente, 50);
        echo json_encode(['success' => true, 'historial' => $historial]);
        break;
        
    case 'recompensas':
        // Listar recompensas disponibles
        $recompensas = obtenerRecompensas();
        echo json_encode(['success' => true, 'recompensas' => $recompensas]);
        break;
        
    case 'niveles':
        // Listar niveles de membresía
        $result = $conexion->query("SELECT * FROM niveles_membresia ORDER BY puntos_minimos ASC");
        $niveles = [];
        while ($row = $result->fetch_assoc()) {
            $niveles[] = $row;
        }
        echo json_encode(['success' => true, 'niveles' => $niveles]);
        break;
    
    case 'obtener_membresia':
        // Obtener una membresía específica
        $id = $_GET['id'] ?? 0;
        $stmt = $conexion->prepare("SELECT * FROM niveles_membresia WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo json_encode(['success' => true, 'membresia' => $row]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Membresía no encontrada']);
        }
        break;
        
    case 'crear_membresia':
        // Crear nueva membresía
        $nivel = $_POST['nivel'] ?? '';
        $puntos_minimos = intval($_POST['puntos_minimos'] ?? 0);
        $multiplicador = floatval($_POST['multiplicador'] ?? 1.0);
        $descuento = floatval($_POST['descuento'] ?? 0);
        $color = $_POST['color'] ?? '#6b7280';
        $icono = $_POST['icono'] ?? 'military_tech';
        $beneficios = $_POST['beneficios'] ?? '';
        
        if (empty($nivel) || $puntos_minimos < 0 || $multiplicador < 1) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit;
        }
        
        $stmt = $conexion->prepare("
            INSERT INTO niveles_membresia (nivel, puntos_minimos, multiplicador_puntos, descuento_adicional, color, icono, beneficios)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("siddsss", $nivel, $puntos_minimos, $multiplicador, $descuento, $color, $icono, $beneficios);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Membresía creada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear membresía: ' . $stmt->error]);
        }
        break;
        
    case 'actualizar_membresia':
        // Actualizar membresía existente
        $id = intval($_POST['id'] ?? 0);
        $nivel = $_POST['nivel'] ?? '';
        $puntos_minimos = intval($_POST['puntos_minimos'] ?? 0);
        $multiplicador = floatval($_POST['multiplicador'] ?? 1.0);
        $descuento = floatval($_POST['descuento'] ?? 0);
        $color = $_POST['color'] ?? '#6b7280';
        $icono = $_POST['icono'] ?? 'military_tech';
        $beneficios = $_POST['beneficios'] ?? '';
        
        if ($id <= 0 || empty($nivel)) {
            echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
            exit;
        }
        
        $stmt = $conexion->prepare("
            UPDATE niveles_membresia 
            SET nivel = ?, puntos_minimos = ?, multiplicador_puntos = ?, descuento_adicional = ?, 
                color = ?, icono = ?, beneficios = ?
            WHERE id = ?
        ");
        $stmt->bind_param("siddsssi", $nivel, $puntos_minimos, $multiplicador, $descuento, $color, $icono, $beneficios, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Membresía actualizada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $stmt->error]);
        }
        break;
        
    case 'eliminar_membresia':
        // Eliminar membresía
        $id = intval($_POST['id'] ?? 0);
        
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }
        
        // Verificar que no haya clientes con esta membresía
        $check = $conexion->query("SELECT COUNT(*) as total FROM puntos_clientes WHERE nivel_membresia = (SELECT nivel FROM niveles_membresia WHERE id = $id)");
        $count = $check->fetch_assoc()['total'];
        
        if ($count > 0) {
            echo json_encode(['success' => false, 'message' => "No se puede eliminar. Hay $count clientes con esta membresía"]);
            exit;
        }
        
        $stmt = $conexion->prepare("DELETE FROM niveles_membresia WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Membresía eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar: ' . $stmt->error]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>
