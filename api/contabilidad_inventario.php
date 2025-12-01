<?php
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$conexion->set_charset("utf8mb4");

// Obtener productos en stock
$query = "SELECT 
    s.nombre_producto as nombre,
    s.stock as cantidad,
    COALESCE(cp.CostoPorEmpaque, 0) as precio_compra,
    s.precio_unitario as precio_venta
    FROM stock s
    LEFT JOIN creacion_de_productos cp ON s.nombre_producto = cp.NombreProducto
    WHERE s.stock > 0
    ORDER BY (s.stock * COALESCE(cp.CostoPorEmpaque, 0)) DESC
    LIMIT 100";

$result = $conexion->query($query);

$productos = [];
$valor_total = 0;
$total_productos = 0;
$stock_bajo = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $cantidad = floatval($row['cantidad']);
        $precio_compra = floatval($row['precio_compra']);
        $precio_venta = floatval($row['precio_venta']);
        
        $productos[] = [
            'nombre' => $row['nombre'],
            'cantidad' => $cantidad,
            'precio_compra' => $precio_compra,
            'precio_venta' => $precio_venta
        ];
        
        $valor_total += $cantidad * $precio_compra;
        $total_productos++;
        
        // Considerar stock bajo si tiene menos de 10 unidades
        if ($cantidad < 10) {
            $stock_bajo++;
        }
    }
}

$conexion->close();

echo json_encode([
    'success' => true,
    'productos' => $productos,
    'valor_total' => $valor_total,
    'total_productos' => $total_productos,
    'stock_bajo' => $stock_bajo
]);
?>
