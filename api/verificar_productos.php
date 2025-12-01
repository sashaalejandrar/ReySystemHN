<?php
header('Content-Type: application/json');

// Recibir productos del frontend
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['productos']) || !is_array($input['productos'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibieron productos para verificar'
    ]);
    exit;
}

$productos = $input['productos'];

try {
    // Conectar a la base de datos
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    $productosVerificados = [];
    
    foreach ($productos as $producto) {
        $codigo = $producto['codigo'] ?? '';
        $nombre = $producto['nombre'] ?? '';
        $cantidad = $producto['cantidad'] ?? 1;
        $precio = $producto['precio'] ?? 0;
        
        $existe = false;
        $stockActual = 0;
        $productoId = null;
        $marca = '';
        $descripcion = '';
        
        // Verificar si el producto existe en la BD
        if (!empty($codigo)) {
            // Búsqueda por código de barras
            $stmt = $conexion->prepare("SELECT Id, Stock, Nombre_Producto, Marca, Descripcion FROM stock WHERE Codigo_Producto = ?");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $existe = true;
                $row = $resultado->fetch_assoc();
                $stockActual = $row['Stock'];
                $nombre = $row['Nombre_Producto']; // Usar nombre de la BD
                $productoId = $row['Id'];
                $marca = $row['Marca'] ?? '';
                $descripcion = $row['Descripcion'] ?? '';
            }
            $stmt->close();
        }
        
        // Si no se encontró por código, intentar buscar por nombre similar
        if (!$existe && !empty($nombre)) {
            // Limpiar el nombre para búsqueda
            $nombreBusqueda = trim($nombre);
            
            // Búsqueda exacta primero
            $stmt = $conexion->prepare("SELECT Id, Stock, Nombre_Producto, Codigo_Producto, Marca, Descripcion FROM stock WHERE Nombre_Producto = ?");
            $stmt->bind_param("s", $nombreBusqueda);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $existe = true;
                $row = $resultado->fetch_assoc();
                $stockActual = $row['Stock'];
                $nombre = $row['Nombre_Producto'];
                $codigo = $row['Codigo_Producto']; // Usar código de la BD
                $productoId = $row['Id'];
                $marca = $row['Marca'] ?? '';
                $descripcion = $row['Descripcion'] ?? '';
            } else {
                // Búsqueda por similitud (LIKE)
                $nombreLike = '%' . $nombreBusqueda . '%';
                $stmt = $conexion->prepare("SELECT Id, Stock, Nombre_Producto, Codigo_Producto, Marca, Descripcion FROM stock WHERE Nombre_Producto LIKE ? LIMIT 1");
                $stmt->bind_param("s", $nombreLike);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
                if ($resultado->num_rows > 0) {
                    $existe = true;
                    $row = $resultado->fetch_assoc();
                    $stockActual = $row['Stock'];
                    $nombre = $row['Nombre_Producto'];
                    $codigo = $row['Codigo_Producto']; // Usar código de la BD
                    $productoId = $row['Id'];
                    $marca = $row['Marca'] ?? '';
                    $descripcion = $row['Descripcion'] ?? '';
                }
            }
            $stmt->close();
        }
        
        $productosVerificados[] = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'marca' => $marca,
            'descripcion' => $descripcion,
            'existe' => $existe,
            'stockActual' => $stockActual,
            'Id' => $productoId
        ];
    }
    
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'productos' => $productosVerificados,
        'message' => count($productosVerificados) . ' productos verificados en BD'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar productos: ' . $e->getMessage()
    ]);
}
?>
