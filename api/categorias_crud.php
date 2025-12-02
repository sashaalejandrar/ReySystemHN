<?php
session_start();
header('Content-Type: application/json');

// Verificar que el usuario esté autenticado y sea admin
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Verificar rol de admin
$usuario = $_SESSION['usuario'];
$stmt = $conexion->prepare("SELECT Rol FROM usuarios WHERE Usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || strtolower($user['Rol']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        // Listar todas las categorías
        $query = "SELECT * FROM Categorias ORDER BY nombre ASC";
        $result = $conexion->query($query);
        
        $categorias = [];
        while ($row = $result->fetch_assoc()) {
            $categorias[] = $row;
        }
        
        echo json_encode(['success' => true, 'data' => $categorias]);
        break;
        
    case 'POST':
        // Crear nueva categoría
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['nombre'])) {
            echo json_encode(['success' => false, 'message' => 'El nombre es requerido']);
            exit;
        }
        
        $nombre = trim($data['nombre']);
        $descripcion = trim($data['descripcion'] ?? '');
        $creado_por = $_SESSION['usuario'];
        
        // Verificar si ya existe
        $check = $conexion->prepare("SELECT id_categoria FROM Categorias WHERE nombre = ?");
        $check->bind_param("s", $nombre);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'La categoría ya existe']);
            exit;
        }
        
        $stmt = $conexion->prepare("INSERT INTO Categorias (nombre, descripcion, creado_por) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $descripcion, $creado_por);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Categoría creada exitosamente', 'id' => $conexion->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al crear categoría']);
        }
        break;
        
    case 'PUT':
        // Actualizar categoría
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id']) || empty($data['nombre'])) {
            echo json_encode(['success' => false, 'message' => 'ID y nombre son requeridos']);
            exit;
        }
        
        $id = intval($data['id']);
        $nombre = trim($data['nombre']);
        $descripcion = trim($data['descripcion'] ?? '');
        
        // Verificar si el nuevo nombre ya existe en otra categoría
        $check = $conexion->prepare("SELECT id_categoria FROM Categorias WHERE nombre = ? AND id_categoria != ?");
        $check->bind_param("si", $nombre, $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'Ya existe otra categoría con ese nombre']);
            exit;
        }
        
        $stmt = $conexion->prepare("UPDATE Categorias SET nombre = ?, descripcion = ? WHERE id_categoria = ?");
        $stmt->bind_param("ssi", $nombre, $descripcion, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Categoría actualizada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar categoría']);
        }
        break;
        
    case 'DELETE':
        // Eliminar categoría
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID es requerido']);
            exit;
        }
        
        $id = intval($data['id']);
        
        $stmt = $conexion->prepare("DELETE FROM Categorias WHERE id_categoria = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Categoría eliminada exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar categoría']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Método no permitido']);
        break;
}

$conexion->close();
?>
