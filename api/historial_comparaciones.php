<?php
header('Content-Type: application/json');
date_default_timezone_set('America/Tegucigalpa');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

try {
    // Obtener todas las comparaciones con información del producto
    $query = "SELECT 
                pc.codigo_producto,
                s.Nombre_Producto as nombre_producto,
                pc.precio_competencia,
                pc.fuente,
                pc.url_producto,
                pc.fecha_actualizacion,
                s.Precio_Unitario as mi_precio
              FROM precios_competencia pc
              LEFT JOIN stock s ON pc.codigo_producto = s.Codigo_Producto
              ORDER BY pc.fecha_actualizacion DESC";
    
    $result = $conexion->query($query);
    $comparaciones = [];
    
    while ($row = $result->fetch_assoc()) {
        $comparaciones[] = $row;
    }
    
    // Calcular estadísticas
    $totalComparados = count($comparaciones);
    $masBaratos = 0;
    $masCaros = 0;
    
    foreach ($comparaciones as $comp) {
        if ($comp['mi_precio'] && $comp['precio_competencia']) {
            if ($comp['precio_competencia'] < $comp['mi_precio']) {
                $masBaratos++;
            } else if ($comp['precio_competencia'] > $comp['mi_precio']) {
                $masCaros++;
            }
        }
    }
    
    // Última actualización - obtener la más reciente de la base de datos
    $ultimaActualizacion = '-';
    $queryUltima = "SELECT MAX(fecha_actualizacion) as ultima FROM precios_competencia";
    $resultUltima = $conexion->query($queryUltima);
    
    if ($resultUltima && $rowUltima = $resultUltima->fetch_assoc()) {
        if ($rowUltima['ultima']) {
            $fecha = new DateTime($rowUltima['ultima']);
            $ahora = new DateTime();
            $diff = $ahora->getTimestamp() - $fecha->getTimestamp();
            
            if ($diff < 60) {
                $ultimaActualizacion = "Hace " . $diff . "s";
            } elseif ($diff < 3600) {
                $ultimaActualizacion = "Hace " . floor($diff / 60) . "m";
            } elseif ($diff < 86400) {
                $ultimaActualizacion = "Hace " . floor($diff / 3600) . "h";
            } else {
                $ultimaActualizacion = "Hace " . floor($diff / 86400) . "d";
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'comparaciones' => $comparaciones,
        'estadisticas' => [
            'total' => $totalComparados,
            'mas_baratos' => $masBaratos,
            'mas_caros' => $masCaros,
            'ultima_actualizacion' => $ultimaActualizacion
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
