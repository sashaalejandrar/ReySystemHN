<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$conexion->set_charset("utf8mb4");

// Estadísticas generales
$query = "SELECT 
    COUNT(*) as total_pedidos,
    SUM(CASE WHEN Estado IN ('Entregado', 'Recibido') THEN 1 ELSE 0 END) as total_completados,
    SUM(CASE WHEN Estado = 'Pendiente' THEN 1 ELSE 0 END) as total_pendientes,
    SUM(Total_Estimado) as valor_total
    FROM pedidos";
$result = $conexion->query($query);
$stats = $result->fetch_assoc();

// Pedidos por estado
$query = "SELECT Estado, COUNT(*) as total FROM pedidos GROUP BY Estado";
$result = $conexion->query($query);
$por_estado = [];
while ($row = $result->fetch_assoc()) {
    $por_estado[$row['Estado']] = intval($row['total']);
}

// Tendencia mensual (últimos 6 meses)
$query = "SELECT 
    DATE_FORMAT(Fecha_Pedido, '%Y-%m') as mes,
    COUNT(*) as total
    FROM pedidos
    WHERE Fecha_Pedido >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(Fecha_Pedido, '%Y-%m')
    ORDER BY mes ASC";
$result = $conexion->query($query);
$tendencia = [];
while ($row = $result->fetch_assoc()) {
    $tendencia[] = [
        'mes' => date('M Y', strtotime($row['mes'] . '-01')),
        'total' => intval($row['total'])
    ];
}

// Productos más solicitados
$query = "SELECT 
    Producto_Solicitado as producto,
    COUNT(*) as num_pedidos,
    SUM(Cantidad) as cantidad_total
    FROM pedidos
    GROUP BY Producto_Solicitado
    ORDER BY num_pedidos DESC
    LIMIT 10";
$result = $conexion->query($query);
$productos_top = [];
while ($row = $result->fetch_assoc()) {
    $productos_top[] = [
        'producto' => $row['producto'],
        'num_pedidos' => intval($row['num_pedidos']),
        'cantidad_total' => intval($row['cantidad_total'])
    ];
}

// Clientes frecuentes
$query = "SELECT 
    Cliente as cliente,
    Telefono as telefono,
    COUNT(*) as total_pedidos
    FROM pedidos
    GROUP BY Cliente, Telefono
    ORDER BY total_pedidos DESC
    LIMIT 10";
$result = $conexion->query($query);
$clientes_top = [];
while ($row = $result->fetch_assoc()) {
    $clientes_top[] = [
        'cliente' => $row['cliente'],
        'telefono' => $row['telefono'],
        'total_pedidos' => intval($row['total_pedidos'])
    ];
}

$conexion->close();

echo json_encode([
    'success' => true,
    'total_pedidos' => intval($stats['total_pedidos']),
    'total_completados' => intval($stats['total_completados']),
    'total_pendientes' => intval($stats['total_pendientes']),
    'valor_total' => floatval($stats['valor_total']),
    'por_estado' => $por_estado,
    'tendencia_mensual' => $tendencia,
    'productos_top' => $productos_top,
    'clientes_top' => $clientes_top
]);
?>
