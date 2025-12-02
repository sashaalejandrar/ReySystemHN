<?php
// API para obtener productos más vendidos (favoritos)
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Obtener los 12 productos más vendidos de los últimos 30 días
$query = "
    SELECT 
        s.Id,
        s.Codigo_Producto,
        s.Nombre_Producto,
        s.Marca,
        s.Precio_Unitario,
        s.Stock,
        s.FotoProducto,
        COUNT(dv.id) as total_ventas,
        SUM(dv.Cantidad) as unidades_vendidas
    FROM stock s
    LEFT JOIN detalle_ventas dv ON s.Id = dv.Id_Producto
    LEFT JOIN ventas v ON dv.Id_Venta = v.Id
    WHERE v.Fecha_Venta >= DATE_SUB(NOW(), INTERVAL 30 DAY)
      AND s.Stock > 0
    GROUP BY s.Id
    ORDER BY unidades_vendidas DESC
    LIMIT 12
";

$result = $conexion->query($query);

$productos = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $productos[] = [
            'id' => $row['Id'],
            'codigo' => $row['Codigo_Producto'],
            'nombre' => $row['Nombre_Producto'],
            'marca' => $row['Marca'],
            'precio' => floatval($row['Precio_Unitario']),
            'stock' => intval($row['Stock']),
            'foto' => $row['FotoProducto'] ?: '',
            'ventas' => intval($row['total_ventas']),
            'unidades' => intval($row['unidades_vendidas'])
        ];
    }
}

// Si no hay suficientes productos vendidos, completar con productos con más stock
if (count($productos) < 12) {
    $ids_existentes = array_map(function($p) { return $p['id']; }, $productos);
    $ids_str = count($ids_existentes) > 0 ? implode(',', $ids_existentes) : '0';
    
    $query_complemento = "
        SELECT 
            Id,
            Codigo_Producto,
            Nombre_Producto,
            Marca,
            Precio_Unitario,
            Stock,
            FotoProducto
        FROM stock
        WHERE Stock > 0
          AND Id NOT IN ($ids_str)
        ORDER BY Stock DESC
        LIMIT " . (12 - count($productos));
    
    $result_complemento = $conexion->query($query_complemento);
    
    if ($result_complemento) {
        while ($row = $result_complemento->fetch_assoc()) {
            $productos[] = [
                'id' => $row['Id'],
                'codigo' => $row['Codigo_Producto'],
                'nombre' => $row['Nombre_Producto'],
                'marca' => $row['Marca'],
                'precio' => floatval($row['Precio_Unitario']),
                'stock' => intval($row['Stock']),
                'foto' => $row['FotoProducto'] ?: '',
                'ventas' => 0,
                'unidades' => 0
            ];
        }
    }
}

$conexion->close();

echo json_encode([
    'success' => true,
    'productos' => $productos
]);
?>
