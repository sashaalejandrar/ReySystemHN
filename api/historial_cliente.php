<?php
// API para obtener historial rápido de cliente por teléfono
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$telefono = $_GET['telefono'] ?? '';

if (empty($telefono)) {
    echo json_encode(['success' => false, 'message' => 'Teléfono requerido']);
    exit;
}

// Buscar cliente por teléfono
$query_cliente = "SELECT * FROM clientes WHERE Celular = ? LIMIT 1";
$stmt = $conexion->prepare($query_cliente);
$stmt->bind_param("s", $telefono);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Cliente no encontrado',
        'nuevo_cliente' => true
    ]);
    exit;
}

$cliente = $result->fetch_assoc();
$stmt->close();

// ===================================
// Obtener últimas 5 compras
// ===================================
$query_compras = "
    SELECT 
        v.Id,
        v.Fecha_Venta,
        v.Total,
        v.Metodo_Pago,
        COUNT(dv.id) as num_productos
    FROM ventas v
    LEFT JOIN detalle_ventas dv ON v.Id = dv.Id_Venta
    WHERE v.Cliente_Nombre = ? OR v.Cliente_Celular = ?
    GROUP BY v.Id
    ORDER BY v.Fecha_Venta DESC
    LIMIT 5
";

$stmt = $conexion->prepare($query_compras);
$stmt->bind_param("ss", $cliente['Nombre'], $telefono);
$stmt->execute();
$result_compras = $stmt->get_result();

$ultimas_compras = [];
while ($compra = $result_compras->fetch_assoc()) {
    $ultimas_compras[] = [
        'id' => $compra['Id'],
        'fecha' => $compra['Fecha_Venta'],
        'total' => floatval($compra['Total']),
        'metodo_pago' => $compra['Metodo_Pago'],
        'num_productos' => intval($compra['num_productos'])
    ];
}
$stmt->close();

// ===================================
// Calcular total gastado
// ===================================
$query_total = "
    SELECT SUM(Total) as total_gastado, COUNT(*) as num_compras
    FROM ventas
    WHERE Cliente_Nombre = ? OR Cliente_Celular = ?
";

$stmt = $conexion->prepare($query_total);
$stmt->bind_param("ss", $cliente['Nombre'], $telefono);
$stmt->execute();
$result_total = $stmt->get_result();
$stats = $result_total->fetch_assoc();
$stmt->close();

// ===================================
// Obtener productos favoritos (más comprados)
// ===================================
$query_favoritos = "
    SELECT 
        s.Nombre_Producto,
        COUNT(dv.id) as veces_comprado,
        SUM(dv.Cantidad) as total_unidades
    FROM detalle_ventas dv
    JOIN ventas v ON dv.Id_Venta = v.Id
    JOIN stock s ON dv.Id_Producto = s.Id
    WHERE v.Cliente_Nombre = ? OR v.Cliente_Celular = ?
    GROUP BY s.Id
    ORDER BY veces_comprado DESC
    LIMIT 5
";

$stmt = $conexion->prepare($query_favoritos);
$stmt->bind_param("ss", $cliente['Nombre'], $telefono);
$stmt->execute();
$result_favoritos = $stmt->get_result();

$productos_favoritos = [];
while ($fav = $result_favoritos->fetch_assoc()) {
    $productos_favoritos[] = [
        'nombre' => $fav['Nombre_Producto'],
        'veces' => intval($fav['veces_comprado']),
        'unidades' => intval($fav['total_unidades'])
    ];
}
$stmt->close();

// ===================================
// Verificar deuda pendiente
// ===================================
$query_deuda = "
    SELECT SUM(Monto_Pendiente) as deuda_total
    FROM deudas
    WHERE Cliente_Id = ? AND Estado = 'Pendiente'
";

$stmt = $conexion->prepare($query_deuda);
$stmt->bind_param("i", $cliente['Id']);
$stmt->execute();
$result_deuda = $stmt->get_result();
$deuda_row = $result_deuda->fetch_assoc();
$deuda_total = floatval($deuda_row['deuda_total'] ?? 0);
$stmt->close();

// ===================================
// Determinar si es cliente frecuente
// ===================================
$num_compras = intval($stats['num_compras']);
$es_frecuente = $num_compras >= 5; // 5 o más compras = frecuente

// Calcular última compra
$ultima_compra = count($ultimas_compras) > 0 ? $ultimas_compras[0]['fecha'] : null;
$dias_desde_ultima = null;
if ($ultima_compra) {
    $fecha_ultima = new DateTime($ultima_compra);
    $hoy = new DateTime();
    $dias_desde_ultima = $hoy->diff($fecha_ultima)->days;
}

$conexion->close();

echo json_encode([
    'success' => true,
    'cliente' => [
        'id' => $cliente['Id'],
        'nombre' => $cliente['Nombre'],
        'celular' => $cliente['Celular'],
        'direccion' => $cliente['Direccion'] ?? '',
        'email' => $cliente['Email'] ?? ''
    ],
    'estadisticas' => [
        'total_gastado' => floatval($stats['total_gastado']),
        'num_compras' => $num_compras,
        'es_frecuente' => $es_frecuente,
        'ultima_compra' => $ultima_compra,
        'dias_desde_ultima' => $dias_desde_ultima,
        'deuda_pendiente' => $deuda_total
    ],
    'ultimas_compras' => $ultimas_compras,
    'productos_favoritos' => $productos_favoritos
]);
?>
