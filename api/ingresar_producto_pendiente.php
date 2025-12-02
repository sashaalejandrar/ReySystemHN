<?php
/**
 * API para ingresar productos pendientes directamente al inventario
 * Recibe: codigo_producto, cantidad, fecha_vencimiento (opcional)
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
include '../funciones.php';
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
    $codigo_producto = trim($_POST['codigo_producto'] ?? '');
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $fecha_vencimiento = trim($_POST['fecha_vencimiento'] ?? '');

    // Validaciones
    if (empty($codigo_producto)) {
        throw new Exception('Código de producto es requerido');
    }

    if ($cantidad <= 0) {
        throw new Exception('La cantidad debe ser mayor a 0');
    }

    // Buscar el producto en creacion_de_productos
    $stmt = $conexion->prepare("SELECT * FROM creacion_de_productos WHERE CodigoProducto = ? LIMIT 1");
    $stmt->bind_param("s", $codigo_producto);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Producto no encontrado en el catálogo');
    }
    
    $producto = $result->fetch_assoc();
    $stmt->close();

    // Verificar que no exista ya en stock
    $stmt = $conexion->prepare("SELECT Id FROM stock WHERE Codigo_Producto = ?");
    $stmt->bind_param("s", $codigo_producto);
    $stmt->execute();
    $result_check = $stmt->get_result();
    
    if ($result_check->num_rows > 0) {
        throw new Exception('Este producto ya existe en el inventario');
    }
    $stmt->close();

    // Preparar datos para insertar en stock
    $nombre_producto = $producto['NombreProducto'];
    $marca = $producto['Marca'] ?? 'N/A';
    $descripcion = $producto['Descripcion'] ?? '';
    $precio_unitario = floatval($producto['PrecioSugeridoUnidad'] ?? 0);
    $grupo = 'General'; // Categoría por defecto
    $foto_producto = $producto['FotoProducto'] ?? '';
    
    // Fecha de vencimiento (si no se proporciona, usar NULL)
    $fecha_venc = !empty($fecha_vencimiento) ? $fecha_vencimiento : null;

    // Insertar en la tabla stock
    $sql = "INSERT INTO stock (
        Nombre_Producto, 
        Codigo_Producto, 
        Marca, 
        Descripcion, 
        Precio_Unitario, 
        Stock, 
        Grupo, 
        FotoProducto,
        Fecha_Vencimiento
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . $conexion->error);
    }

    $stmt->bind_param(
        "ssssdisss",
        $nombre_producto,
        $codigo_producto,
        $marca,
        $descripcion,
        $precio_unitario,
        $cantidad,
        $grupo,
        $foto_producto,
        $fecha_venc
    );

    if (!$stmt->execute()) {
        throw new Exception('Error al insertar en stock: ' . $stmt->error);
    }

    $producto_id = $conexion->insert_id;
    $stmt->close();

    // Registrar en historial de inventario
    $accion = "Ingreso inicial desde productos pendientes";
    $usuario = $_SESSION['usuario'];
    
    $stmt_hist = $conexion->prepare("INSERT INTO historial_inventario (
        producto_id, 
        nombre_producto, 
        codigo_producto, 
        accion, 
        cantidad_anterior, 
        cantidad_nueva, 
        usuario, 
        fecha
    ) VALUES (?, ?, ?, ?, 0, ?, ?, NOW())");
    
    $stmt_hist->bind_param(
        "isssis",
        $producto_id,
        $nombre_producto,
        $codigo_producto,
        $accion,
        $cantidad,
        $usuario
    );
    
    $stmt_hist->execute();
    $stmt_hist->close();

    $conexion->close();

    echo json_encode([
        'success' => true,
        'message' => "Producto '{$nombre_producto}' ingresado exitosamente al inventario con {$cantidad} unidades",
        'producto_id' => $producto_id
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
