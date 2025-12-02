<?php
/**
 * API: Gestión de Precios por Producto
 * Permite obtener y guardar los precios de un producto específico
 */

header('Content-Type: application/json');

try {
    $mysqli = new mysqli('localhost', 'root', '', 'tiendasrey');
    if ($mysqli->connect_error) {
        throw new Exception('Error de conexión a la BD');
    }
    $mysqli->set_charset("utf8mb4");

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // Obtener precios de un producto
            obtenerPreciosProducto($mysqli);
            break;

        case 'POST':
            // Guardar/actualizar precios de un producto
            guardarPreciosProducto($mysqli);
            break;

        default:
            throw new Exception('Método no permitido');
    }

    $mysqli->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// ============================================
// FUNCIONES
// ============================================

function obtenerPreciosProducto($mysqli) {
    if (empty($_GET['producto_id'])) {
        throw new Exception('ID del producto es requerido');
    }
    
    $producto_id = (int)$_GET['producto_id'];
    
    // Obtener todos los tipos de precios activos
    $stmt = $mysqli->prepare("
        SELECT id, nombre, descripcion, es_default
        FROM tipos_precios
        WHERE activo = TRUE
        ORDER BY es_default DESC, nombre ASC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $precios = [];
    while ($tipo = $result->fetch_assoc()) {
        // Obtener el precio del producto para este tipo
        $stmt_precio = $mysqli->prepare("
            SELECT precio
            FROM producto_precios
            WHERE producto_id = ? AND tipo_precio_id = ?
        ");
        $stmt_precio->bind_param("ii", $producto_id, $tipo['id']);
        $stmt_precio->execute();
        $result_precio = $stmt_precio->get_result();
        
        $precio_valor = 0.00;
        if ($row_precio = $result_precio->fetch_assoc()) {
            $precio_valor = (float)$row_precio['precio'];
        } else {
            // Si no existe precio en producto_precios, intentar obtener de stock
            if ($tipo['id'] == 1) { // Precio_Unitario
                $stmt_stock = $mysqli->prepare("SELECT Precio_Unitario FROM stock WHERE Id = ?");
                $stmt_stock->bind_param("i", $producto_id);
                $stmt_stock->execute();
                $result_stock = $stmt_stock->get_result();
                if ($row_stock = $result_stock->fetch_assoc()) {
                    $precio_valor = (float)$row_stock['Precio_Unitario'];
                }
                $stmt_stock->close();
            } elseif ($tipo['id'] == 2) { // Precio_Mayoreo
                $stmt_stock = $mysqli->prepare("SELECT Precio_Mayoreo FROM stock WHERE Id = ?");
                $stmt_stock->bind_param("i", $producto_id);
                $stmt_stock->execute();
                $result_stock = $stmt_stock->get_result();
                if ($row_stock = $result_stock->fetch_assoc()) {
                    $precio_valor = (float)$row_stock['Precio_Mayoreo'];
                }
                $stmt_stock->close();
            }
        }
        $stmt_precio->close();
        
        // Usar tipo_id como clave del array
        $precios[$tipo['id']] = $precio_valor;
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'producto_id' => $producto_id,
        'precios' => $precios
    ]);
}

function guardarPreciosProducto($mysqli) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['producto_id'])) {
        throw new Exception('ID del producto es requerido');
    }
    
    if (empty($data['precios']) || !is_array($data['precios'])) {
        throw new Exception('Datos de precios son requeridos');
    }
    
    $producto_id = (int)$data['producto_id'];
    $precios = $data['precios'];
    
    // Verificar que el producto existe
    $stmt = $mysqli->prepare("SELECT Id FROM stock WHERE Id = ?");
    $stmt->bind_param("i", $producto_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        throw new Exception('Producto no encontrado');
    }
    $stmt->close();
    
    // Iniciar transacción
    $mysqli->begin_transaction();
    
    try {
        $actualizados = 0;
        $insertados = 0;
        
        foreach ($precios as $tipo_id => $precio) {
            $tipo_id = (int)$tipo_id;
            $precio = (float)$precio;
            
            // Verificar si ya existe el precio
            $stmt = $mysqli->prepare("
                SELECT id FROM producto_precios
                WHERE producto_id = ? AND tipo_precio_id = ?
            ");
            $stmt->bind_param("ii", $producto_id, $tipo_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Actualizar precio existente
                $stmt->close();
                $stmt = $mysqli->prepare("
                    UPDATE producto_precios
                    SET precio = ?
                    WHERE producto_id = ? AND tipo_precio_id = ?
                ");
                $stmt->bind_param("dii", $precio, $producto_id, $tipo_id);
                $stmt->execute();
                $actualizados++;
            } else {
                // Insertar nuevo precio
                $stmt->close();
                $stmt = $mysqli->prepare("
                    INSERT INTO producto_precios (producto_id, tipo_precio_id, precio)
                    VALUES (?, ?, ?)
                ");
                $stmt->bind_param("iid", $producto_id, $tipo_id, $precio);
                $stmt->execute();
                $insertados++;
            }
            $stmt->close();
        }
        
        // Actualizar también el campo Precio_Unitario en la tabla stock (para compatibilidad)
        if (isset($precios[1])) { // Asumiendo que tipo_id 1 es Precio_Unitario
            $precio_unitario = (float)$precios[1];
            $stmt = $mysqli->prepare("UPDATE stock SET Precio_Unitario = ? WHERE Id = ?");
            $stmt->bind_param("di", $precio_unitario, $producto_id);
            $stmt->execute();
            $stmt->close();
        }
        
        $mysqli->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Precios guardados exitosamente',
            'actualizados' => $actualizados,
            'insertados' => $insertados
        ]);
        
    } catch (Exception $e) {
        $mysqli->rollback();
        throw $e;
    }
}
?>
