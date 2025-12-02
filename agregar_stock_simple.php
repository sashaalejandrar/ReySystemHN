<?php
session_start();
include 'funciones.php';
include 'registrar_movimiento_inventario.php';
VerificarSiUsuarioYaInicioSesion();

header('Content-Type: application/json');

/*
 * ---------------------------------------------------------------
 *  AGREGAR O ACTUALIZAR PRODUCTO EN STOCK
 *  - Valida si el producto ya existe en stock
 *  - Si existe: actualiza stock y datos
 *  - Si no existe: crea nuevo producto
 *  - Completa datos desde creacion_de_productos
 *  - INSERTA FotoProducto correctamente
 *  - Soporta .webp, .jpg, .png, .gif
 *  - Crea carpeta uploads/ si no existe
 *  - Logs detallados con IP, usuario y hora
 * ---------------------------------------------------------------
 */

$response = ['success' => false, 'message' => ''];

try {
    // === 1. RECIBIR Y VALIDAR DATOS DEL FORMULARIO ===
    $nombre           = trim($_POST['nombre'] ?? '');
    $codigo           = trim($_POST['codigo'] ?? '');
    $descripcion      = $_POST['descripcion'] ?? '';
    $precio           = floatval($_POST['precio'] ?? 0);
    $cantidad         = intval($_POST['cantidad'] ?? 0);
    $categoria        = $_POST['categoria'] ?? '';
    $fecha_vencimiento = $_POST['fecha_vencimiento'] ?? null;
    if ($fecha_vencimiento === '' || $fecha_vencimiento === 'null') {
        $fecha_vencimiento = null;
    }

    // Validaciones
    if (empty($nombre)) {
        throw new Exception("El nombre del producto es obligatorio.");
    }
    if (empty($codigo)) {
        throw new Exception("El código del producto es obligatorio.");
    }
    if ($precio <= 0) {
        throw new Exception("El precio debe ser mayor a 0.");
    }
    if ($cantidad < 0) {
        throw new Exception("La cantidad no puede ser negativa.");
    }

    // === 2. CONEXIÓN A LA BASE DE DATOS ===
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    $conexion->set_charset("utf8mb4");

    // === 3. VERIFICAR SI EL PRODUCTO YA EXISTE EN STOCK ===
    $producto_existente = null;
    $stmt = $conexion->prepare("SELECT * FROM stock WHERE Codigo_Producto = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Error preparando consulta: " . $conexion->error);
    }
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $producto_existente = $result->fetch_assoc();
    }
    $stmt->close();

    // === 4. BUSCAR EN creacion_de_productos ===
    $producto_base = null;
    $stmt = $conexion->prepare("SELECT * FROM creacion_de_productos WHERE CodigoProducto = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception("Error preparando consulta: " . $conexion->error);
    }
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $producto_base = $result->fetch_assoc();
    }
    $stmt->close();

    // === 5. SI EL PRODUCTO EXISTE EN STOCK: ACTUALIZAR ===
    if ($producto_existente) {
        // Producto ya existe, solo actualizar stock y datos
        $nuevo_stock = $producto_existente['Stock'] + $cantidad;
        
        // Manejo de foto (si se sube una nueva)
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception("No se pudo crear la carpeta 'uploads/'.");
            }
        }

        $foto_path = $producto_existente['FotoProducto']; // Mantener foto actual por defecto

        if (isset($_FILES['product_photo']) && $_FILES['product_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['product_photo'];
            $file_name = $file['name'];
            $file_tmp  = $file['tmp_name'];
            $file_size = $file['size'];

            // Validar tamaño
            if ($file_size > 3 * 1024 * 1024) {
                throw new Exception("La imagen no puede exceder 3MB.");
            }

            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowed)) {
                throw new Exception("Formato no permitido. Usa: JPG, PNG, GIF, WEBP.");
            }

            $foto_name = $codigo . '.' . $ext;
            $foto_path = $upload_dir . $foto_name;

            if (!move_uploaded_file($file_tmp, $foto_path)) {
                throw new Exception("Error al guardar la imagen.");
            }
        } elseif (isset($_POST['foto_url']) && !empty($_POST['foto_url'])) {
            // Si viene una URL de foto (del catálogo)
            $foto_path = $_POST['foto_url'];
        }

        // Actualizar producto existente
        $sql_update = "
            UPDATE stock SET
                Nombre_Producto = ?,
                Descripcion = ?,
                Precio_Unitario = ?,
                Stock = ?,
                Grupo = ?,
                Fecha_Vencimiento = ?,
                FotoProducto = ?
            WHERE Codigo_Producto = ?
        ";

        $stmt = $conexion->prepare($sql_update);
        if (!$stmt) {
            throw new Exception("Error SQL: " . $conexion->error);
        }

        $stmt->bind_param(
            "ssdisiss",
            $nombre,
            $descripcion,
            $precio,
            $nuevo_stock,
            $categoria,
            $fecha_vencimiento,
            $foto_path,
            $codigo
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar: " . $stmt->error);
        }

        $stmt->close();
        
        // Registrar movimiento de inventario (entrada)
        registrarMovimientoInventario(
            $conexion,
            $nombre,
            'entrada',
            $cantidad,
            $producto_existente['Stock'],
            $nuevo_stock,
            'Actualización de stock desde inventario',
            $_SESSION['usuario'] ?? 'Sistema'
        );
        
        $conexion->close();

        $response['success'] = true;
        $response['message'] = "Producto actualizado exitosamente. Stock anterior: {$producto_existente['Stock']}, Stock nuevo: {$nuevo_stock}";
        
    } else {
        // === 6. SI NO EXISTE: VERIFICAR QUE EXISTA EN CATÁLOGO ===
        if (!$producto_base) {
            // No existe ni en stock ni en catálogo
            $response['success'] = false;
            $response['message'] = "⚠️ El producto con código '{$codigo}' no existe en el catálogo. Primero debes crear el producto en 'Creación de Productos'.";
            $response['create_required'] = true;
            
            $conexion->close();
            echo json_encode($response);
            exit;
        }

        // === 7. COMPLETAR CAMPOS DESDE CATÁLOGO ===
        $marca          = $producto_base['Marca'] ?? 'N/A';
        $tipo_empaque   = $producto_base['TipoEmpaque'] ?? 'N/A';
        $proveedor      = $producto_base['Proveedor'] ?? 'N/A';
        $contacto       = $producto_base['Contacto'] ?? 'N/A';
        $direccion      = $producto_base['Direccion'] ?? 'N/A';
        $sub_grupo      = $producto_base['Sub_Grupo'] ?? 'N/A';
        $precio_mayoreo = floatval($producto_base['Precio_Mayoreo'] ?? 0);
        $descuento      = floatval($producto_base['Descuento'] ?? 0);
        $sucursal       = $producto_base['Sucursal'] ?? 'N/A';
        $fecha_ingreso  = $producto_base['Fecha_Ingreso'] ?? date('Y-m-d');
        $foto_base      = $producto_base['FotoProducto'] ?? null;

        // === 8. MANEJO DE FOTO ===
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception("No se pudo crear la carpeta 'uploads/'.");
            }
        }

        $foto_path = $foto_base; // Por defecto: foto del catálogo

        if (isset($_FILES['product_photo']) && $_FILES['product_photo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['product_photo'];
            $file_name = $file['name'];
            $file_tmp  = $file['tmp_name'];
            $file_size = $file['size'];

            // Validar tamaño
            if ($file_size > 3 * 1024 * 1024) {
                throw new Exception("La imagen no puede exceder 3MB.");
            }

            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowed)) {
                throw new Exception("Formato no permitido. Usa: JPG, PNG, GIF, WEBP.");
            }

            $foto_name = $codigo . '.' . $ext;
            $foto_path = $upload_dir . $foto_name;

            if (!move_uploaded_file($file_tmp, $foto_path)) {
                throw new Exception("Error al guardar la imagen.");
            }
        } elseif (isset($_POST['foto_url']) && !empty($_POST['foto_url'])) {
            // Si viene una URL de foto (del catálogo)
            $foto_path = $_POST['foto_url'];
        }

        // === 9. INSERTAR NUEVO PRODUCTO EN STOCK ===
        $sql_insert = "
            INSERT INTO stock 
            (
                Codigo_Producto, Nombre_Producto, Descripcion, Precio_Unitario, Stock, Grupo,
                Fecha_Vencimiento, FotoProducto, Marca, TipoEmpaque, Proveedor, Contacto,
                Direccion, Sub_Grupo, Precio_Mayoreo, Descuento, Sucursal, Fecha_Ingreso
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ";

        $stmt = $conexion->prepare($sql_insert);
        if (!$stmt) {
            throw new Exception("Error SQL: " . $conexion->error);
        }

        $stmt->bind_param(
            "sssdississssssdsss",
            $codigo,
            $nombre,
            $descripcion,
            $precio,
            $cantidad,
            $categoria,
            $fecha_vencimiento,
            $foto_path,
            $marca,
            $tipo_empaque,
            $proveedor,
            $contacto,
            $direccion,
            $sub_grupo,
            $precio_mayoreo,
            $descuento,
            $sucursal,
            $fecha_ingreso
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al guardar: " . $stmt->error);
        }

        $stmt->close();
        
        // Registrar movimiento de inventario (entrada inicial)
        registrarMovimientoInventario(
            $conexion,
            $nombre,
            'entrada',
            $cantidad,
            0,
            $cantidad,
            'Ingreso inicial de producto al inventario',
            $_SESSION['usuario'] ?? 'Sistema'
        );
        
        $conexion->close();

        $response['success'] = true;
        $response['message'] = "✅ Producto creado exitosamente en el inventario con {$cantidad} unidades.";
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log(
        "[" . date('Y-m-d H:i:s') . " HN] ERROR en agregar_stock_simple.php | " .
        "Usuario: " . ($_SESSION['usuario'] ?? 'N/A') . " | " .
        "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . " | " .
        "Error: " . $e->getMessage() . PHP_EOL,
        3,
        "api_errors.log"
    );
}

echo json_encode($response);
exit;
?>