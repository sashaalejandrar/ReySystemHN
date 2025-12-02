<?php
/**
 * API para eliminar productos del catálogo (creacion_de_productos)
 * Solo elimina productos que NO están en stock
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea admin
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

$stmt = $conexion->prepare("SELECT Rol FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || strtolower($user['Rol']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado. Solo administradores pueden realizar esta acción.']);
    exit;
}

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);

    if ($id <= 0) {
        throw new Exception('ID de producto inválido');
    }

    // Verificar que el producto existe en creacion_de_productos
    $stmt = $conexion->prepare("SELECT CodigoProducto, NombreProducto FROM creacion_de_productos WHERE Id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Producto no encontrado');
    }
    
    $producto = $result->fetch_assoc();
    $codigo = $producto['CodigoProducto'];
    $nombre = $producto['NombreProducto'];
    $stmt->close();

    // Verificar que NO esté en stock (seguridad adicional)
    $stmt = $conexion->prepare("SELECT Id FROM stock WHERE Codigo_Producto = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result_stock = $stmt->get_result();
    
    if ($result_stock->num_rows > 0) {
        throw new Exception('No se puede eliminar. Este producto ya está en el inventario.');
    }
    $stmt->close();

    // Eliminar el producto de creacion_de_productos
    $stmt = $conexion->prepare("DELETE FROM creacion_de_productos WHERE Id = ?");
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al eliminar el producto: ' . $stmt->error);
    }
    
    $stmt->close();
    $conexion->close();

    echo json_encode([
        'success' => true,
        'message' => "Producto '{$nombre}' eliminado exitosamente del catálogo"
    ]);

} catch (Exception $e) {
    if (isset($conexion)) {
        $conexion->close();
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
