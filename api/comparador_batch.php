<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('America/Tegucigalpa');

try {
    // Conexión directa a la base de datos
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception('Error de conexión: ' . $conexion->connect_error);
    }
    
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $lote = isset($_GET['lote']) ? intval($_GET['lote']) : 5;
    
    // Obtener productos
    $query = "SELECT Codigo_Producto, Nombre_Producto, Precio_Unitario 
              FROM stock 
              WHERE Stock > 0 
              ORDER BY Codigo_Producto ASC 
              LIMIT $lote OFFSET $offset";
    
    $productos = $conexion->query($query);
    
    if (!$productos) {
        throw new Exception('Error en consulta: ' . $conexion->error);
    }
    
    $procesados = 0;
    $encontrados = 0;
    $resultados = [];
    
    while ($producto = $productos->fetch_assoc()) {
        $codigo = $producto['Codigo_Producto'];
        $nombre = $producto['Nombre_Producto'];
        $miPrecio = floatval($producto['Precio_Unitario']);
        
        // Buscar precio con Python Smart
        $preciosCompetencia = buscarPreciosPython($codigo, $nombre);
        
        if (!empty($preciosCompetencia)) {
            foreach ($preciosCompetencia as $pc) {
                $precioComp = floatval($pc['precio']);
                $fuente = $pc['fuente'];
                $url = $pc['url'];
                
                // Calcular diferencia
                $diferencia = 0;
                if ($miPrecio > 0) {
                    $diferencia = (($precioComp - $miPrecio) / $miPrecio) * 100;
                }
                
                // Verificar si ya existe un precio anterior
                $checkStmt = $conexion->prepare("SELECT precio_competencia FROM precios_competencia WHERE codigo_producto = ? AND fuente = ?");
                $checkStmt->bind_param("ss", $codigo, $fuente);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                $precioAnterior = null;
                
                if ($row = $result->fetch_assoc()) {
                    $precioAnterior = floatval($row['precio_competencia']);
                }
                $checkStmt->close();
                
                // Guardar/Actualizar en BD
                $stmt = $conexion->prepare("INSERT INTO precios_competencia 
                    (codigo_producto, precio_competencia, fuente, url_producto, fecha_actualizacion) 
                    VALUES (?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                    precio_competencia = VALUES(precio_competencia),
                    fuente = VALUES(fuente),
                    url_producto = VALUES(url_producto),
                    fecha_actualizacion = NOW()");
                
                $stmt->bind_param("sdss", $codigo, $precioComp, $fuente, $url);
                $stmt->execute();
                $stmt->close();
                
                $encontrados++;
                
                // Determinar si el precio cambió
                $cambio = null;
                if ($precioAnterior !== null) {
                    if ($precioComp > $precioAnterior) {
                        $cambio = 'subio';
                    } elseif ($precioComp < $precioAnterior) {
                        $cambio = 'bajo';
                    } else {
                        $cambio = 'igual';
                    }
                }
                
                // Agregar a resultados con información de cambio
                $resultados[] = [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'mi_precio' => $miPrecio,
                    'precio_competencia' => $precioComp,
                    'precio_anterior' => $precioAnterior,
                    'cambio' => $cambio,
                    'diferencia_porcentual' => $diferencia,
                    'fuente' => $fuente,
                    'url' => $url
                ];
            }
        }
        
        $procesados++;
    }
    
    // Calcular totales
    $totalQuery = $conexion->query("SELECT COUNT(*) as total FROM stock WHERE Stock > 0");
    $totalRow = $totalQuery->fetch_assoc();
    $total = $totalRow['total'];
    
    $hayMas = ($offset + $lote) < $total;
    $progreso = (($offset + $procesados) / $total) * 100;
    
    echo json_encode([
        'success' => true,
        'procesados' => $procesados,
        'encontrados' => $encontrados,
        'total_procesados' => $offset + $procesados,
        'total_encontrados' => $encontrados,
        'total' => $total,
        'offset' => $offset + $lote,
        'hay_mas' => $hayMas,
        'progreso' => $progreso,
        'mensaje' => "Procesados $procesados productos, encontrados $encontrados precios",
        'resultados' => $resultados
    ]);
    
    $conexion->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Función para buscar precios con Python
function buscarPreciosPython($codigo, $nombre) {
    $pythonScript = '/opt/lampp/htdocs/ReySystemDemo/python/scraper_smart_mistral.py';
    
    if (!file_exists($pythonScript)) {
        return [];
    }
    
    // Buscar por código si existe, sino por nombre
    if (!empty($codigo)) {
        $param1 = "";
        $param2 = $codigo;
    } else {
        $param1 = $nombre;
        $param2 = "";
    }
    
    $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($param1) . " " . escapeshellarg($param2) . " 2>&1";
    $output = shell_exec($command);
    
    if ($output) {
        $resultados = json_decode($output, true);
        if (is_array($resultados) && !isset($resultados['error'])) {
            return $resultados;
        }
    }
    
    return [];
}
?>
