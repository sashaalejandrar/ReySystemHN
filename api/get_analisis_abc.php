<?php
/**
 * API: Calculate ABC Analysis
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    $conexion->set_charset("utf8mb4");
    
    $fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
    $fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');
    
    // Get product sales data
    $sql = "SELECT 
        p.Id,
        p.Nombre_Producto as Nombre,
        p.Codigo_Producto as Codigo,
        p.Precio_Unitario as Precio,
        COALESCE(SUM(dv.Cantidad), 0) as cantidad_vendida,
        COALESCE(SUM(dv.Cantidad * dv.Precio), 0) as ingresos_totales
    FROM stock p
    LEFT JOIN detalle_venta dv ON p.Id = dv.Id_Producto
    LEFT JOIN ventas v ON dv.Id_Venta = v.Id
    WHERE v.Fecha BETWEEN ? AND ?
    GROUP BY p.Id
    HAVING cantidad_vendida > 0
    ORDER BY ingresos_totales DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $fecha_inicio, $fecha_fin);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    $total_ingresos = 0;
    
    while ($row = $result->fetch_assoc()) {
        $productos[] = $row;
        $total_ingresos += $row['ingresos_totales'];
    }
    
    // Calculate ABC classification
    $acumulado = 0;
    $clasificados = [];
    
    foreach ($productos as $producto) {
        $porcentaje = ($producto['ingresos_totales'] / $total_ingresos) * 100;
        $acumulado += $porcentaje;
        
        if ($acumulado <= 80) {
            $categoria = 'A';
        } elseif ($acumulado <= 95) {
            $categoria = 'B';
        } else {
            $categoria = 'C';
        }
        
        $clasificados[] = array_merge($producto, [
            'porcentaje_ingresos' => round($porcentaje, 2),
            'porcentaje_acumulado' => round($acumulado, 2),
            'categoria_abc' => $categoria
        ]);
    }
    
    // Count by category
    $count_a = count(array_filter($clasificados, fn($p) => $p['categoria_abc'] === 'A'));
    $count_b = count(array_filter($clasificados, fn($p) => $p['categoria_abc'] === 'B'));
    $count_c = count(array_filter($clasificados, fn($p) => $p['categoria_abc'] === 'C'));
    
    echo json_encode([
        'success' => true,
        'data' => $clasificados,
        'stats' => [
            'total_productos' => count($clasificados),
            'total_ingresos' => $total_ingresos,
            'categoria_a' => $count_a,
            'categoria_b' => $count_b,
            'categoria_c' => $count_c
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conexion->close();
?>
