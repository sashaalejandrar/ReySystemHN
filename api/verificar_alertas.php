<?php
// API para verificar alertas en tiempo real
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$alertas = [];

// Obtener ID del usuario
$usuario_result = $conexion->query("SELECT id FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
if ($usuario_result && $usuario_row = $usuario_result->fetch_assoc()) {
    $usuario_id = $usuario_row['id'];
} else {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

// ===================================
// 1. ALERTA: Producto sin stock al intentar vender
// ===================================
// Esta alerta se dispara desde el frontend cuando se intenta agregar un producto sin stock
// No necesita verificación aquí

// ===================================
// 2. ALERTA: Productos próximos a vencer (próximos 7 días)
// ===================================
$query_vencer = "
    SELECT 
        Nombre_Producto,
        Fecha_Vencimiento,
        Stock,
        DATEDIFF(STR_TO_DATE(Fecha_Vencimiento, '%Y-%m-%d'), CURDATE()) as dias_restantes
    FROM stock
    WHERE Fecha_Vencimiento IS NOT NULL
      AND Fecha_Vencimiento != ''
      AND Fecha_Vencimiento != '0000-00-00'
      AND STR_TO_DATE(Fecha_Vencimiento, '%Y-%m-%d') BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
      AND Stock > 0
    ORDER BY Fecha_Vencimiento ASC
    LIMIT 3
";

$result_vencer = $conexion->query($query_vencer);
if ($result_vencer && $result_vencer->num_rows > 0) {
    while ($producto = $result_vencer->fetch_assoc()) {
        $dias = $producto['dias_restantes'];
        $mensaje = $dias == 0 
            ? "'{$producto['Nombre_Producto']}' vence HOY. Stock: {$producto['Stock']}" 
            : "'{$producto['Nombre_Producto']}' vence en {$dias} día(s). Stock: {$producto['Stock']}";
        
        $alertas[] = [
            'tipo' => 'por_vencer',
            'titulo' => 'Producto por Vencer',
            'mensaje' => $mensaje,
            'id' => 'vencer-' . md5($producto['Nombre_Producto']),
            'accion' => "window.location.href='inventario.php'",
            'accion_texto' => 'Ver inventario'
        ];
    }
}

// ===================================
// 3. ALERTA: Cliente frecuente detectado
// ===================================
// Esta alerta se dispara desde el frontend cuando se ingresa teléfono de cliente
// No necesita verificación aquí

// ===================================
// 4. ALERTA: Poco efectivo en caja
// ===================================
$hoy = date('Y-m-d');
$query_caja = "
    SELECT Monto_Inicial, Monto_Actual
    FROM caja
    WHERE DATE(Fecha) = ? AND Estado = 'Abierta'
    ORDER BY Id DESC
    LIMIT 1
";

$stmt = $conexion->prepare($query_caja);
$stmt->bind_param("s", $hoy);
$stmt->execute();
$result_caja = $stmt->get_result();

if ($result_caja && $row_caja = $result_caja->fetch_assoc()) {
    $monto_actual = floatval($row_caja['Monto_Actual']);
    
    // Alerta si hay menos de L 500 en efectivo
    if ($monto_actual < 500) {
        $alertas[] = [
            'tipo' => 'poco_efectivo',
            'titulo' => 'Poco Efectivo en Caja',
            'mensaje' => "Solo quedan L " . number_format($monto_actual, 2) . " en caja. Considera solicitar más efectivo para dar cambio.",
            'id' => 'efectivo-' . date('YmdH')
        ];
    }
}
$stmt->close();

// ===================================
// 5. ALERTA: Meta de ventas alcanzada
// ===================================
$query_ventas_hoy = "
    SELECT SUM(Total) as total_ventas
    FROM ventas
    WHERE DATE(Fecha_Venta) = CURDATE()
";

$result_ventas = $conexion->query($query_ventas_hoy);
if ($result_ventas && $row_ventas = $result_ventas->fetch_assoc()) {
    $total_ventas = floatval($row_ventas['total_ventas']);
    
    // Meta diaria: L 10,000 (ajustar según necesidad)
    $meta_diaria = 10000;
    
    // Alerta cuando se alcanza el 100% de la meta (solo una vez al día)
    if ($total_ventas >= $meta_diaria) {
        // Verificar si ya se mostró hoy
        $cache_key = 'meta_alcanzada_' . date('Ymd');
        if (!isset($_SESSION[$cache_key])) {
            $_SESSION[$cache_key] = true;
            
            $alertas[] = [
                'tipo' => 'meta_alcanzada',
                'titulo' => '🎉 ¡Meta Alcanzada!',
                'mensaje' => "¡Felicidades! Se alcanzó la meta diaria de L " . number_format($meta_diaria, 2) . ". Total vendido: L " . number_format($total_ventas, 2),
                'id' => 'meta-' . date('Ymd'),
                'sonido' => true
            ];
        }
    }
}

$conexion->close();

echo json_encode([
    'success' => true,
    'alertas' => $alertas,
    'timestamp' => time()
]);
?>
