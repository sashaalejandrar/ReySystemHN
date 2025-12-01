<?php
session_start();
header('Content-Type: application/json');

function logError($message) {
    $logFile = 'api_errors.log';
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[$timestamp] " . $message . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

try {
    $q = trim($_GET['q'] ?? '');
    $tipo = $_GET['tipo'] ?? 'nombre'; // 'nombre' o 'codigo'
    $autocompletar = ($_GET['autocompletar'] ?? '0') === '1';

    if (empty($q)) {
        echo json_encode(['success' => true, 'message' => 'Query vacío', 'suggestions' => []]);
        exit;
    }

    // Conectar a la base de datos usando db_connect
    require_once 'db_connect.php';
    
    if ($conexion->connect_error) {
        logError("Error de conexión BD: " . $conexion->connect_error);
        echo json_encode(['success' => false, 'message' => 'Error de conexión', 'suggestions' => []]);
        exit;
    }
    
    $conexion->set_charset("utf8mb4");

    if ($autocompletar) {
        // BUSCAR EN TABLA creacion_de_productos (catálogo completo)
        logError("Buscando en creacion_de_productos: $q ($tipo)");
        
        if ($tipo === 'codigo') {
            $query = "SELECT 
                Id,
                NombreProducto,
                CodigoProducto,
                Marca,
                CostoPorUnidad,
                PrecioSugeridoUnidad,
                FotoProducto
            FROM creacion_de_productos 
            WHERE CodigoProducto LIKE ? 
            ORDER BY NombreProducto ASC
            LIMIT 10";
            $param = '%' . $q . '%'; // Cambiado para buscar en cualquier parte del código
        } else {
            $query = "SELECT 
                Id,
                NombreProducto,
                CodigoProducto,
                Marca,
                CostoPorUnidad,
                PrecioSugeridoUnidad,
                FotoProducto
            FROM creacion_de_productos 
            WHERE NombreProducto LIKE ? 
            ORDER BY NombreProducto ASC
            LIMIT 10";
            $param = '%' . $q . '%';
        }
        
        $stmt = $conexion->prepare($query);
        if (!$stmt) {
            logError("Error en prepare (creacion_de_productos): " . $conexion->error);
            echo json_encode(['success' => false, 'message' => 'Error en consulta', 'suggestions' => []]);
            exit;
        }
        
        $stmt->bind_param("s", $param);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $suggestions = [];
        while ($row = $resultado->fetch_assoc()) {
            $suggestions[] = $row;
        }
        
        $stmt->close();
        
        logError("Sugerencias encontradas en creacion_de_productos: " . count($suggestions));
        
        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions,
            'source' => 'catalogo',
            'count' => count($suggestions)
        ]);
        
    } else {
        // BUSCAR EN TABLA stock (inventario local)
        logError("Buscando en stock: $q ($tipo)");
        
        if ($tipo === 'codigo') {
            $query = "SELECT 
                Id,
                Nombre_Producto,
                Codigo_Producto,
                Marca,
                Precio_Compra,
                Precio_Venta,
                Cantidad_Disponible,
                Foto
            FROM stock 
            WHERE Codigo_Producto LIKE ? 
            ORDER BY Nombre_Producto ASC
            LIMIT 10";
            $param = '%' . $q . '%'; // Cambiado para buscar en cualquier parte del código
        } else {
            $query = "SELECT 
                Id,
                Nombre_Producto,
                Codigo_Producto,
                Marca,
                Precio_Compra,
                Precio_Venta,
                Cantidad_Disponible,
                Foto
            FROM stock 
            WHERE Nombre_Producto LIKE ? 
            ORDER BY Nombre_Producto ASC
            LIMIT 10";
            $param = '%' . $q . '%';
        }
        
        $stmt = $conexion->prepare($query);
        if (!$stmt) {
            logError("Error en prepare (stock): " . $conexion->error);
            echo json_encode(['success' => false, 'message' => 'Error en consulta', 'suggestions' => []]);
            exit;
        }
        
        $stmt->bind_param("s", $param);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        $suggestions = [];
        while ($row = $resultado->fetch_assoc()) {
            $suggestions[] = $row;
        }
        
        $stmt->close();
        
        logError("Sugerencias encontradas en stock: " . count($suggestions));
        
        echo json_encode([
            'success' => true,
            'suggestions' => $suggestions,
            'source' => 'local',
            'count' => count($suggestions)
        ]);
    }
    
    $conexion->close();

} catch (Exception $e) {
    logError("Exception en buscar_sugerencias: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'suggestions' => []]);
}
?>