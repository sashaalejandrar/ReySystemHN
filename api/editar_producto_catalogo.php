<?php
/**
 * API para editar productos del catálogo (creacion_de_productos)
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
    
    $id = intval($input['Id'] ?? 0);
    $nombreProducto = trim($input['NombreProducto'] ?? '');
    $codigoProducto = trim($input['CodigoProducto'] ?? '');
    $marca = trim($input['Marca'] ?? '');
    $descripcionCorta = trim($input['DescripcionCorta'] ?? '');
    $descripcion = trim($input['Descripcion'] ?? '');
    $tipoEmpaque = trim($input['TipoEmpaque'] ?? '');
    $unidadesPorEmpaque = intval($input['UnidadesPorEmpaque'] ?? 0);
    $costoPorEmpaque = floatval($input['CostoPorEmpaque'] ?? 0);
    $costoPorUnidad = floatval($input['CostoPorUnidad'] ?? 0);
    $margenSugerido = floatval($input['MargenSugerido'] ?? 0);
    $precioSugeridoEmpaque = floatval($input['PrecioSugeridoEmpaque'] ?? 0);
    $precioSugeridoUnidad = floatval($input['PrecioSugeridoUnidad'] ?? 0);
    $proveedor = trim($input['Proveedor'] ?? '');
    $direccionProveedor = trim($input['DireccionProveedor'] ?? '');
    $contactoProveedor = trim($input['ContactoProveedor'] ?? '');

    // Validaciones
    if ($id <= 0) {
        throw new Exception('ID de producto inválido');
    }

    if (empty($nombreProducto)) {
        throw new Exception('El nombre del producto es requerido');
    }

    // Verificar que el producto existe
    $stmt = $conexion->prepare("SELECT Id FROM creacion_de_productos WHERE Id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Producto no encontrado');
    }
    $stmt->close();

    // Actualizar el producto
    $sql = "UPDATE creacion_de_productos SET 
            NombreProducto = ?,
            CodigoProducto = ?,
            Marca = ?,
            DescripcionCorta = ?,
            Descripcion = ?,
            TipoEmpaque = ?,
            UnidadesPorEmpaque = ?,
            CostoPorEmpaque = ?,
            CostoPorUnidad = ?,
            MargenSugerido = ?,
            PrecioSugeridoEmpaque = ?,
            PrecioSugeridoUnidad = ?,
            Proveedor = ?,
            DireccionProveedor = ?,
            ContactoProveedor = ?
            WHERE Id = ?";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . $conexion->error);
    }

    $stmt->bind_param(
        "ssssssissdddsssi",
        $nombreProducto,
        $codigoProducto,
        $marca,
        $descripcionCorta,
        $descripcion,
        $tipoEmpaque,
        $unidadesPorEmpaque,
        $costoPorEmpaque,
        $costoPorUnidad,
        $margenSugerido,
        $precioSugeridoEmpaque,
        $precioSugeridoUnidad,
        $proveedor,
        $direccionProveedor,
        $contactoProveedor,
        $id
    );

    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar el producto: ' . $stmt->error);
    }

    $stmt->close();
    $conexion->close();

    echo json_encode([
        'success' => true,
        'message' => "Producto '{$nombreProducto}' actualizado exitosamente"
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
