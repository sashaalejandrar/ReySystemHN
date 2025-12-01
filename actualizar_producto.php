<?php
session_start();
include 'funciones.php';
include 'registrar_movimiento_inventario.php';
VerificarSiUsuarioYaInicioSesion();

header('Content-Type: application/json; charset=utf-8');

// Registro de errores a archivo propio (asegúrate permisos de escritura)
$log_file = __DIR__ . '/api_errors.log';
function log_error($msg) {
    global $log_file;
    error_log("[" . date('Y-m-d H:i:s') . "] " . $msg . PHP_EOL, 3, $log_file);
}

$response = ['success' => false, 'message' => ''];

try {
    // --- Recibir y normalizar datos
    $product_id = intval($_POST['product_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $codigo = trim($_POST['codigo'] ?? '');
    $marca = trim($_POST['marca'] ?? ''); // Recibir marca del formulario
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio = floatval($_POST['precio'] ?? 0);
    $cantidad = intval($_POST['cantidad'] ?? 0);
    $categoria = trim($_POST['categoria'] ?? '');
    $fecha_vencimiento = trim($_POST['fecha_vencimiento'] ?? '');
    $unidad_medida_id = intval($_POST['unidad_medida_id'] ?? 1); // Recibir unidad de medida
    if ($fecha_vencimiento === '') $fecha_vencimiento = null;

    // Validaciones básicas
    // Permitir product_id = 0 para productos nuevos de creacion_de_productos
    if ($nombre === '' || $codigo === '') throw new Exception("Faltan datos obligatorios: nombre o código.");
    if ($precio <= 0) throw new Exception("El precio debe ser mayor a 0.");
    if ($cantidad < 0) throw new Exception("La cantidad no puede ser negativa.");

    // === CONEXIÓN ===
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . $conexion->connect_error);
    }
    $conexion->set_charset("utf8mb4");

    // Determinar si es INSERT o UPDATE
    $es_producto_nuevo = ($product_id <= 0);
    
    $foto_actual = null;
    $stock_anterior = 0;
    $nombre_anterior = $nombre;

    // === SI ES ACTUALIZACIÓN, OBTENER DATOS ACTUALES ===
    if (!$es_producto_nuevo) {
        $stmt = $conexion->prepare("SELECT FotoProducto, Stock, Nombre_Producto FROM stock WHERE Id = ?");
        if (!$stmt) throw new Exception("Prepare SELECT FotoProducto falló: " . $conexion->error);
        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Error al ejecutar SELECT FotoProducto: " . $err);
        }
        $result = $stmt->get_result();
        $row_actual = $result ? $result->fetch_assoc() : null;
        $foto_actual = $row_actual['FotoProducto'] ?? null;
        $stock_anterior = intval($row_actual['Stock'] ?? 0);
        $nombre_anterior = $row_actual['Nombre_Producto'] ?? $nombre;
        $stmt->close();
    }

    // === BUSCAR EN creacion_de_productos POR CODIGO ===
    $stmt = $conexion->prepare("SELECT * FROM creacion_de_productos WHERE CodigoProducto = ?");
    if (!$stmt) throw new Exception("Prepare SELECT creacion_de_productos falló: " . $conexion->error);
    $stmt->bind_param("s", $codigo);
    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();
        throw new Exception("Error al ejecutar SELECT creacion_de_productos: " . $err);
    }
    $result = $stmt->get_result();
    $producto_base = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
    $stmt->close();

    // === COMPLETAR CAMPOS CON creacion_de_productos O VALORES POR DEFECTO ===
    // Si marca viene vacía del formulario, usar la de creacion_de_productos o 'N/A'
    if (empty($marca)) {
        $marca = $producto_base['Marca'] ?? 'N/A';
    }
    $tipo_empaque = $producto_base['TipoEmpaque'] ?? 'N/A';
    $proveedor = $producto_base['Proveedor'] ?? 'N/A';
    $contacto = $producto_base['Contacto'] ?? 'N/A';
    $direccion = $producto_base['Direccion'] ?? 'N/A';
    $sub_grupo = $producto_base['Sub_Grupo'] ?? 'N/A';
    $precio_mayoreo = floatval($producto_base['Precio_Mayoreo'] ?? 0);
    $descuento = floatval($producto_base['Descuento'] ?? 0);
    $sucursal = $producto_base['Sucursal'] ?? 'N/A';
    $fecha_ingreso = $producto_base['Fecha_Ingreso'] ?? date('Y-m-d');

    // --- Preparar ruta de foto actual por defecto (se guarda relativa)
    $foto_path = $foto_actual;

    // === SUBIR NUEVA FOTO (opcional) ===
    $upload_dir_abs = __DIR__ . '/uploads/';
    $upload_dir_rel = 'uploads/';
    if (!is_dir($upload_dir_abs)) {
        if (!mkdir($upload_dir_abs, 0755, true)) {
            throw new Exception("No se pudo crear carpeta de uploads: $upload_dir_abs");
        }
    }

    if (isset($_FILES['product_photo']) && $_FILES['product_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['product_photo'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            throw new Exception("Formato no permitido. Usa: JPG, PNG, GIF, WEBP.");
        }

        // Nombre y rutas
        $foto_name = $codigo . '.' . $ext;
        $foto_path_abs = $upload_dir_abs . $foto_name; // en filesystem
        $foto_path_rel = $upload_dir_rel . $foto_name; // lo guardamos en la DB

        if (!move_uploaded_file($file['tmp_name'], $foto_path_abs)) {
            throw new Exception("Error al mover el archivo a $foto_path_abs");
        }

        // Asignar nueva ruta relativa para DB
        $foto_path = $foto_path_rel;

        // Eliminar foto anterior si existe y es distinta
        if ($foto_actual && $foto_actual !== $foto_path && file_exists(__DIR__ . '/' . $foto_actual)) {
            @unlink(__DIR__ . '/' . $foto_actual);
        }
    }

    // === INSERTAR O ACTUALIZAR stock ===
    if ($es_producto_nuevo) {
        // INSERTAR nuevo producto
        $insert_sql = "
            INSERT INTO stock (
                Codigo_Producto, Nombre_Producto, Descripcion, Precio_Unitario,
                Stock, Grupo, Fecha_Vencimiento, FotoProducto,
                Marca, TipoEmpaque, Proveedor, Contacto, Direccion,
                Sub_Grupo, Precio_Mayoreo, Descuento, Sucursal, Fecha_Ingreso,
                Fecha_Ultima_Actualizacion, unidad_medida_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ";
        
        $stmt = $conexion->prepare($insert_sql);
        if (!$stmt) throw new Exception("Prepare INSERT falló: " . $conexion->error);
        
        // Asegurar tipos correctos y variables para bind
        $fecha_vencimiento_bind = $fecha_vencimiento ?: null;
        $foto_bind = $foto_path ?? null;
        $precio_mayoreo = floatval($precio_mayoreo);
        $descuento = floatval($descuento);
        $fecha_ingreso = $fecha_ingreso ?: date('Y-m-d');
        
        // Tipos para 19 parámetros: s s s d i s s s s s s s s s d d s s i
        $types = "sssdisssssssssddssi";
        
        $bind_result = $stmt->bind_param(
            $types,
            $codigo, $nombre, $descripcion, $precio, $cantidad, $categoria,
            $fecha_vencimiento_bind, $foto_bind,
            $marca, $tipo_empaque, $proveedor, $contacto, $direccion,
            $sub_grupo, $precio_mayoreo, $descuento, $sucursal, $fecha_ingreso,
            $unidad_medida_id
        );
        
        if ($bind_result === false) {
            throw new Exception("bind_param falló: " . $stmt->error);
        }
        
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Error al ejecutar INSERT: " . $err);
        }
        
        $product_id = $conexion->insert_id; // Obtener el ID del nuevo producto
        $stmt->close();
        
    } else {
        // ACTUALIZAR producto existente
        $update_sql = "
            UPDATE stock SET
                Codigo_Producto = ?, Nombre_Producto = ?, Descripcion = ?, Precio_Unitario = ?,
                Stock = ?, Grupo = ?, Fecha_Vencimiento = ?, FotoProducto = COALESCE(?, FotoProducto),
                Marca = ?, TipoEmpaque = ?, Proveedor = ?, Contacto = ?, Direccion = ?,
                Sub_Grupo = ?, Precio_Mayoreo = ?, Descuento = ?, Sucursal = ?, Fecha_Ingreso = ?,
                unidad_medida_id = ?
            WHERE Id = ?
        ";

        $stmt = $conexion->prepare($update_sql);
        if (!$stmt) throw new Exception("Prepare UPDATE falló: " . $conexion->error);

        // Asegurar tipos correctos y variables para bind
        $fecha_vencimiento_bind = $fecha_vencimiento ?: null;
        $foto_bind = $foto_path ?? null;
        $precio_mayoreo = floatval($precio_mayoreo);
        $descuento = floatval($descuento);
        $fecha_ingreso = $fecha_ingreso ?: date('Y-m-d');

        // Tipos exactos para 20 parámetros: s s s d i s s s s s s s s s d d s s i i
        $types = "sssdisssssssssddssii";

        $bind_result = $stmt->bind_param(
            $types,
            $codigo, $nombre, $descripcion, $precio, $cantidad, $categoria,
            $fecha_vencimiento_bind, $foto_bind,
            $marca, $tipo_empaque, $proveedor, $contacto, $direccion,
            $sub_grupo, $precio_mayoreo, $descuento, $sucursal, $fecha_ingreso,
            $unidad_medida_id, $product_id
        );

        if ($bind_result === false) {
            throw new Exception("bind_param falló: " . $stmt->error);
        }

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            throw new Exception("Error al ejecutar UPDATE: " . $err);
        }

        $stmt->close();
    }
    
    // Registrar movimiento de inventario si cambió el stock
    if ($cantidad != $stock_anterior) {
        $diferencia = $cantidad - $stock_anterior;
        $tipo_movimiento = $diferencia > 0 ? 'entrada' : 'salida';
        $cantidad_movimiento = abs($diferencia);
        
        registrarMovimientoInventario(
            $conexion,
            $nombre,
            $tipo_movimiento,
            $cantidad_movimiento,
            $stock_anterior,
            $cantidad,
            'Actualización manual desde inventario',
            $_SESSION['usuario'] ?? 'Sistema'
        );
    }
    
    $conexion->close();

    $response['success'] = true;
    $response['message'] = 'Producto actualizado correctamente.';
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    log_error("actualizar_producto.php: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>