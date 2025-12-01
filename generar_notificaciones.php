<?php
// No se necesita session_start() aquí, se llamará desde un script que ya lo tenga
function generarNotificacionesStock($conexion, $usuario_id) {
    // Evita generar notificaciones duplicadas en la misma carga de página
    static $notificaciones_generadas = false;
    if ($notificaciones_generadas) {
        return;
    }
    $notificaciones_generadas = true;

    // --- 1. Notificaciones de Stock Bajo (<= 10) ---
    $stmt = $conexion->prepare("
        SELECT Id, Nombre_Producto, Stock 
        FROM stock 
        WHERE Stock > 0 AND Stock <= 10
    ");
    
    if (!$stmt) {
        error_log("Error preparando query stock bajo: " . $conexion->error);
        return;
    }
    
    if ($stmt->execute()) {
        $productos_stock_bajo = $stmt->get_result();
        while ($producto = $productos_stock_bajo->fetch_assoc()) {
            $check_stmt = $conexion->prepare("
                SELECT 1 FROM notificaciones 
                WHERE usuario_id = ? AND producto_id = ? 
                  AND tipo = 'stock_bajo' AND leido = 0 
                  AND fecha_creacion > DATE_SUB(NOW(), INTERVAL 1 DAY)
            ");
            
            if (!$check_stmt) {
                error_log("Error preparando check stock bajo: " . $conexion->error);
                continue;
            }
            
            $check_stmt->bind_param("ii", $usuario_id, $producto['Id']);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows == 0) {
                $titulo = "Stock Bajo";
                $mensaje = "Stock bajo: '" . $producto['Nombre_Producto'] . "' con {$producto['Stock']} unidades.";
                $modulo = "Inventario";
                $usuario_destino = "";
                $insert_stmt = $conexion->prepare("
                    INSERT INTO notificaciones (usuario_id, titulo, tipo, mensaje, modulo, producto_id, usuario_destino, leido, fecha_creacion) 
                    VALUES (?, ?, 'stock_bajo', ?, ?, ?, ?, 0, NOW())
                ");
                
                if ($insert_stmt) {
                    $insert_stmt->bind_param("isssis", $usuario_id, $titulo, $mensaje, $modulo, $producto['Id'], $usuario_destino);
                    if (!$insert_stmt->execute()) {
                        error_log("Error insertando notificación stock bajo: " . $insert_stmt->error);
                    }
                    $insert_stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
    $stmt->close();

    // --- 2. Notificaciones de Sin Stock (0) ---
    $stmt = $conexion->prepare("SELECT Id, Nombre_Producto FROM stock WHERE Stock = 0");
    
    if (!$stmt) {
        error_log("Error preparando query sin stock: " . $conexion->error);
        return;
    }
    
    if ($stmt->execute()) {
        $productos_sin_stock = $stmt->get_result();
        while ($producto = $productos_sin_stock->fetch_assoc()) {
            $check_stmt = $conexion->prepare("
                SELECT 1 FROM notificaciones 
                WHERE usuario_id = ? AND producto_id = ? 
                  AND tipo = 'sin_stock' AND leido = 0 
                  AND fecha_creacion > DATE_SUB(NOW(), INTERVAL 1 DAY)
            ");
            
            if (!$check_stmt) {
                error_log("Error preparando check sin stock: " . $conexion->error);
                continue;
            }
            
            $check_stmt->bind_param("ii", $usuario_id, $producto['Id']);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows == 0) {
                $titulo = "Sin Stock";
                $mensaje = "Producto agotado: '" . $producto['Nombre_Producto'] . "'.";
                $modulo = "Inventario";
                $usuario_destino = "";
                $insert_stmt = $conexion->prepare("
                    INSERT INTO notificaciones (usuario_id, titulo, tipo, mensaje, modulo, producto_id, usuario_destino, leido, fecha_creacion) 
                    VALUES (?, ?, 'sin_stock', ?, ?, ?, ?, 0, NOW())
                ");
                
                if ($insert_stmt) {
                    $insert_stmt->bind_param("isssis", $usuario_id, $titulo, $mensaje, $modulo, $producto['Id'], $usuario_destino);
                    if (!$insert_stmt->execute()) {
                        error_log("Error insertando notificación sin stock: " . $insert_stmt->error);
                    }
                    $insert_stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
    $stmt->close();

    // --- 3. Notificaciones de Productos por Vencer (en los próximos 30 días) ---
    $stmt = $conexion->prepare("
        SELECT Id, Nombre_Producto, Fecha_Vencimiento 
        FROM stock 
        WHERE Fecha_Vencimiento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
          AND Fecha_Vencimiento IS NOT NULL 
          AND Fecha_Vencimiento != '0000-00-00'
    ");
    
    if (!$stmt) {
        error_log("Error preparando query por vencer: " . $conexion->error);
        return;
    }
    
    if ($stmt->execute()) {
        $productos_por_vencer = $stmt->get_result();
        while ($producto = $productos_por_vencer->fetch_assoc()) {
            $check_stmt = $conexion->prepare("
                SELECT 1 FROM notificaciones 
                WHERE usuario_id = ? AND producto_id = ? 
                  AND tipo = 'por_vencer' AND leido = 0 
                  AND fecha_creacion > DATE_SUB(NOW(), INTERVAL 1 DAY)
            ");
            
            if (!$check_stmt) {
                error_log("Error preparando check por vencer: " . $conexion->error);
                continue;
            }
            
            $check_stmt->bind_param("ii", $usuario_id, $producto['Id']);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows == 0) {
                $fecha_venc = date("d/m/Y", strtotime($producto['Fecha_Vencimiento']));
                $titulo = "Producto por Vencer";
                $mensaje = "Por vencer: '" . $producto['Nombre_Producto'] . "' vence el {$fecha_venc}.";
                $modulo = "Inventario";
                $usuario_destino = "";
                $insert_stmt = $conexion->prepare("
                    INSERT INTO notificaciones (usuario_id, titulo, tipo, mensaje, modulo, producto_id, usuario_destino, leido, fecha_creacion) 
                    VALUES (?, ?, 'por_vencer', ?, ?, ?, ?, 0, NOW())
                ");
                
                if ($insert_stmt) {
                    $insert_stmt->bind_param("isssis", $usuario_id, $titulo, $mensaje, $modulo, $producto['Id'], $usuario_destino);
                    if (!$insert_stmt->execute()) {
                        error_log("Error insertando notificación por vencer: " . $insert_stmt->error);
                    }
                    $insert_stmt->close();
                }
            }
            $check_stmt->close();
        }
    }
    $stmt->close();
}

// Función para obtener notificaciones pendientes
function obtenerNotificacionesPendientes($conexion, $usuario_id) {
    $stmt = $conexion->prepare("
        SELECT n.*, s.Nombre_Producto, s.Codigo_Producto 
        FROM notificaciones n
        LEFT JOIN stock s ON n.producto_id = s.Id
        WHERE n.usuario_id = ? AND n.leido = 0
        ORDER BY n.fecha_creacion DESC
        LIMIT 999
    ");
    
    if (!$stmt) {
        error_log("Error obteniendo notificaciones: " . $conexion->error);
        return [];
    }
    
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notificaciones = [];
    while ($row = $result->fetch_assoc()) {
        $notificaciones[] = $row;
    }
    
    $stmt->close();
    return $notificaciones;
}

// Función para contar notificaciones pendientes
function contarNotificacionesPendientes($conexion, $usuario_id) {
    $stmt = $conexion->prepare("
        SELECT COUNT(*) as total 
        FROM notificaciones 
        WHERE usuario_id = ? AND leido = 0
    ");
    
    if (!$stmt) {
        error_log("Error contando notificaciones: " . $conexion->error);
        return 0;
    }
    
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return $row['total'] ?? 0;
}
?>