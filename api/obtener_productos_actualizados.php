<?php
/**
 * API para obtener productos actualizados en tiempo real
 * Devuelve productos con precios de competencia actualizados
 */

header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Obtener productos con precios actualizados recientemente (últimos 5 minutos)
$query = "SELECT 
    s.Codigo_Producto as codigo,
    s.Nombre_Producto as nombre,
    s.Precio_Unitario as mi_precio,
    AVG(pc.precio_competencia) as promedio_competencia,
    COUNT(DISTINCT pc.fuente) as num_fuentes
FROM stock s
LEFT JOIN precios_competencia pc ON s.Codigo_Producto = pc.codigo_producto
    AND pc.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
WHERE s.Stock > 0
GROUP BY s.Codigo_Producto
HAVING promedio_competencia IS NOT NULL
ORDER BY pc.fecha_actualizacion DESC
LIMIT 100";

$result = $conexion->query($query);

$productos = [];
while ($row = $result->fetch_assoc()) {
    $miPrecio = floatval($row['mi_precio']);
    $promedioComp = floatval($row['promedio_competencia']);
    $diferencia = $promedioComp > 0 ? $miPrecio - $promedioComp : 0;
    $porcentajeDif = $promedioComp > 0 ? ($diferencia / $promedioComp) * 100 : 0;
    
    $productos[] = [
        'codigo' => $row['codigo'],
        'nombre' => $row['nombre'],
        'mi_precio' => $miPrecio,
        'promedio_competencia' => $promedioComp,
        'diferencia_porcentual' => $porcentajeDif,
        'num_fuentes' => intval($row['num_fuentes'])
    ];
}

// Obtener estadísticas actualizadas
$statsQuery = "SELECT 
    COUNT(DISTINCT pc.codigo_producto) as productos_comparados,
    COUNT(DISTINCT pc.fuente) as fuentes_activas,
    AVG(CASE WHEN s.Precio_Unitario > pc.precio_competencia THEN 1 ELSE 0 END) * 100 as porcentaje_mas_caro
FROM precios_competencia pc
LEFT JOIN stock s ON pc.codigo_producto = s.Codigo_Producto
WHERE pc.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

$statsResult = $conexion->query($statsQuery);
$stats = $statsResult->fetch_assoc();

echo json_encode([
    'success' => true,
    'productos' => $productos,
    'stats' => [
        'productos_comparados' => intval($stats['productos_comparados'] ?? 0),
        'fuentes_activas' => intval($stats['fuentes_activas'] ?? 0),
        'porcentaje_mas_caro' => floatval($stats['porcentaje_mas_caro'] ?? 0)
    ],
    'timestamp' => date('Y-m-d H:i:s')
]);

$conexion->close();
?>
