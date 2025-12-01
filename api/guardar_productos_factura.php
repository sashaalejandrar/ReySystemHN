<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuario no autenticado']);
    exit;
}

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener datos del usuario
$stmt_usuario = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = ?");
$stmt_usuario->bind_param("s", $_SESSION['usuario']);
$stmt_usuario->execute();
$resultado_usuario = $stmt_usuario->get_result();

if ($resultado_usuario->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

$usuario = $resultado_usuario->fetch_assoc();
$usuario_id = $usuario['id'] ?? $usuario['Id'] ?? null; // Intentar ambos
$usuario_nombre = $usuario['Usuario'] ?? $usuario['usuario'] ?? $_SESSION['usuario'];

// Obtener productos del request
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['productos']) || empty($input['productos'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron productos']);
    exit;
}

$productos = $input['productos'];

$productosNuevos = 0;
$productosActualizados = 0;
$errores = [];

// Iniciar transacción
$conexion->begin_transaction();

try {
    foreach ($productos as $producto) {
        $codigo = $producto['codigo'] ?? '';
        $nombre = $producto['nombre'] ?? '';
        $cantidad = intval($producto['cantidad'] ?? 0);
        $precio = floatval($producto['precio'] ?? 0);
        $marca = $producto['marca'] ?? '';
        $descripcion = $producto['descripcion'] ?? '';
        $existe = $producto['existe'] ?? false;
        
        // Validar datos
        if (empty($nombre) || $cantidad <= 0 || $precio <= 0) {
            $errores[] = "Producto inválido: $nombre";
            continue;
        }
        
        // DEBUG: Log para ver qué está llegando
        error_log("Producto: $nombre | Existe: " . ($existe ? 'SI' : 'NO') . " | Id: " . ($producto['Id'] ?? 'NO DEFINIDO'));
        
        if ($existe && isset($producto['Id']) && !empty($producto['Id'])) {
            // ACTUALIZAR PRODUCTO EXISTENTE EN STOCK
            $id_producto = $producto['Id'];
            $stock_actual = $producto['stockActual'] ?? 0;
            $nuevo_stock = $stock_actual + $cantidad;
            
            $stmt = $conexion->prepare("
                UPDATE stock 
                SET Stock = ?, 
                    Precio_Unitario = ?,
                    Fecha_Ultima_Actualizacion = NOW()
                WHERE Id = ?
            ");
            $stmt->bind_param("idi", $nuevo_stock, $precio, $id_producto);
            
            if ($stmt->execute()) {
                $productosActualizados++;
                
                // Registrar movimiento en historial de inventario
                registrarMovimientoInventario(
                    $conexion, 
                    $id_producto, 
                    $codigo, 
                    $nombre, 
                    $cantidad, 
                    'entrada', 
                    'Factura escaneada con IA',
                    $usuario_nombre
                );
            } else {
                $errores[] = "Error al actualizar: $nombre";
            }
            
            $stmt->close();
            
        } else {
            // CREAR NUEVO PRODUCTO
            $TipoEmpaque = $producto['tipoEmpaque'] ?? 'Unidad';
            $DescripcionCorta = substr($nombre, 0, 50); // Usar nombre como descripción corta
            $Proveedor = $producto['proveedor'] ?? 'Sin Proveedor';
            $CostoPorEmpaque = $precio * $cantidad; // Costo total del empaque
            $CostoPorUnidad = $precio; // Costo por unidad individual
            $MargenSugerido = 30.00; // Margen de ganancia sugerido por defecto (30%)
            $PrecioSugeridoEmpaque = $precio * $cantidad * 1.3; // Precio sugerido con 30% de margen
            
            // 1. Insertar en creacion_de_productos (todas las columnas requeridas)
            $stmt = $conexion->prepare("
                INSERT INTO creacion_de_productos 
                (CodigoProducto, NombreProducto, DescripcionCorta, Descripcion, Marca, Proveedor, 
                 PrecioSugeridoUnidad, PrecioSugeridoEmpaque, CostoPorUnidad, CostoPorEmpaque, UnidadesPorEmpaque, TipoEmpaque, MargenSugerido, Creado_Por, Fecha_Creacion)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            // Tipos: s s s s s s d d d d i s d s
            $stmt->bind_param("ssssssddddisds", 
                $codigo,                    // 1. CodigoProducto - string
                $nombre,                    // 2. NombreProducto - string
                $DescripcionCorta,          // 3. DescripcionCorta - string
                $descripcion,               // 4. Descripcion - string
                $marca,                     // 5. Marca - string
                $Proveedor,                 // 6. Proveedor - string
                $precio,                    // 7. PrecioSugeridoUnidad - double
                $PrecioSugeridoEmpaque,     // 8. PrecioSugeridoEmpaque - double
                $CostoPorUnidad,            // 9. CostoPorUnidad - double
                $CostoPorEmpaque,           // 10. CostoPorEmpaque - double
                $cantidad,                  // 11. UnidadesPorEmpaque - integer
                $TipoEmpaque,               // 12. TipoEmpaque - string
                $MargenSugerido,            // 13. MargenSugerido - double
                $usuario_nombre             // 14. Creado_Por - string
            );
            
            if ($stmt->execute()) {
                $producto_id = $conexion->insert_id;
                
                // 2. Insertar en stock - TODAS las columnas requeridas
                $Contacto = "";
                $Direccion = "";
                $Grupo = "General";
                $Sub_Grupo = "Sin clasificar";
                $Precio_Mayoreo = $precio;
                $Descuento = "0";
                $Sucursal = "Principal";
                $Fecha_Vencimiento = "";
                $id_negocio = 1;
                $id_sucursal = 1;
                $Fecha_Ultima_Actualizacion = date('Y-m-d');
                
                $stmt2 = $conexion->prepare("
                    INSERT INTO stock 
                    (Codigo_Producto, Nombre_Producto, Marca, Descripcion, TipoEmpaque, Proveedor, Contacto, Direccion, 
                     Grupo, Sub_Grupo, Precio_Unitario, Precio_Mayoreo, Stock, Descuento, Sucursal, Fecha_Ingreso, 
                     Fecha_Vencimiento, id_negocio, id_sucursal, Fecha_Ultima_Actualizacion)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)
                ");
                // 19 parámetros: 16 strings + 2 integers + 1 string = ssssssssssssssssiis
                $stmt2->bind_param("ssssssssssssssssiis", 
                    $codigo,                        // Codigo_Producto
                    $nombre,                        // Nombre_Producto
                    $marca,                         // Marca
                    $descripcion,                   // Descripcion
                    $TipoEmpaque,                   // TipoEmpaque
                    $Proveedor,                     // Proveedor
                    $Contacto,                      // Contacto
                    $Direccion,                     // Direccion
                    $Grupo,                         // Grupo
                    $Sub_Grupo,                     // Sub_Grupo
                    $precio,                        // Precio_Unitario
                    $Precio_Mayoreo,                // Precio_Mayoreo
                    $cantidad,                      // Stock
                    $Descuento,                     // Descuento
                    $Sucursal,                      // Sucursal
                    $Fecha_Vencimiento,             // Fecha_Vencimiento
                    $id_negocio,                    // id_negocio
                    $id_sucursal,                   // id_sucursal
                    $Fecha_Ultima_Actualizacion     // Fecha_Ultima_Actualizacion
                );
                
                if ($stmt2->execute()) {
                    $stock_id = $conexion->insert_id;
                    $productosNuevos++;
                    
                    // Registrar movimiento en historial
                    registrarMovimientoInventario(
                        $conexion, 
                        $stock_id, 
                        $codigo, 
                        $nombre, 
                        $cantidad, 
                        'entrada', 
                        'Producto nuevo desde factura escaneada',
                        $usuario_nombre
                    );
                } else {
                    $errores[] = "Error al crear stock para: $nombre";
                }
                
                $stmt2->close();
            } else {
                $errores[] = "Error al crear producto: $nombre";
            }
            
            $stmt->close();
        }
    }
    
    // Confirmar transacción
    $conexion->commit();
    
    echo json_encode([
        'success' => true,
        'nuevos' => $productosNuevos,
        'actualizados' => $productosActualizados,
        'errores' => $errores,
        'message' => "Procesamiento completado: $productosNuevos nuevos, $productosActualizados actualizados"
    ]);
    
} catch (Exception $e) {
    // Revertir transacción en caso de error
    $conexion->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar productos: ' . $e->getMessage(),
        'errores' => $errores
    ]);
}

$conexion->close();

/**
 * Registra un movimiento en el historial de inventario
 */
function registrarMovimientoInventario($conexion, $producto_id, $codigo, $nombre, $cantidad, $tipo, $motivo, $usuario) {
    // Verificar si existe la tabla historial_inventario
    $result = $conexion->query("SHOW TABLES LIKE 'historial_inventario'");
    
    if ($result->num_rows > 0) {
        $stmt = $conexion->prepare("
            INSERT INTO historial_inventario 
            (producto_id, codigo_producto, nombre_producto, cantidad, tipo_movimiento, motivo, usuario, fecha)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("issssss", $producto_id, $codigo, $nombre, $cantidad, $tipo, $motivo, $usuario);
        $stmt->execute();
        $stmt->close();
    }
}
?>
