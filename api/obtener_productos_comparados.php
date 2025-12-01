<?php
header('Content-Type: application/json');
require_once '../config.php';

try {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    
    // Obtener productos recientemente actualizados con comparación
    $query = "SELECT 
                s.Codigo_Producto as codigo,
                s.Nombre_Producto as nombre,
                s.Precio_Venta as precio_propio,
                c.Precio_Competencia as precio_competencia,
                c.Diferencia_Porcentual as diferencia,
                c.Fuente_Competencia as fuente,
                c.URL_Producto as url,
                c.Fecha_Actualizacion as fecha_actualizacion
              FROM stock s
              LEFT JOIN comparacion_precios c ON s.Codigo_Producto = c.Codigo_Producto
              WHERE c.Fecha_Actualizacion IS NOT NULL
              ORDER BY c.Fecha_Actualizacion DESC
              LIMIT ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $productos = [];
    while ($row = $result->fetch_assoc()) {
        $productos[] = [
            'codigo' => $row['codigo'],
            'nombre' => $row['nombre'],
            'precio_propio' => $row['precio_propio'],
            'precio_competencia' => $row['precio_competencia'],
            'diferencia' => $row['diferencia'],
            'fuente' => $row['fuente'],
            'url' => $row['url'],
            'fecha_actualizacion' => $row['fecha_actualizacion']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'productos' => $productos,
        'total' => count($productos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
