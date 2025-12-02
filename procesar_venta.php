<?php
// ============================================
// CONFIGURACIÓN INICIAL - ANTES DE CUALQUIER OUTPUT
// ============================================
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

if (ob_get_length()) ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// ============================================
// FUNCIÓN PARA RESPUESTAS JSON
// ============================================
function sendJSON($success, $message, $data = []) {
    if (ob_get_level()) ob_clean();
    
    $response = array_merge([
        'success' => $success,
        'message' => $message
    ], $data);
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// ============================================
// CAPTURAR ERRORES FATALES
// ============================================
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (ob_get_level()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error fatal del servidor',
            'error' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
});

// ============================================
// INICIAR SESIÓN
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// VERIFICAR DEPENDENCIAS
// ============================================
if (!file_exists('vendor/autoload.php')) {
    sendJSON(false, 'PHPMailer no instalado. Ejecuta: composer require phpmailer/phpmailer');
}
require 'vendor/autoload.php';

if (file_exists('generar_documentos.php')) {
    require_once 'generar_documentos.php';
}

if (file_exists('verificar_logros.php')) {
    require_once 'verificar_logros.php';
}

// ============================================
// CONEXIÓN A BASE DE DATOS
// ============================================
date_default_timezone_set('America/Tegucigalpa');
$conexion = @new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    error_log("Error de conexión: " . $conexion->connect_error);
    sendJSON(false, 'Error de conexión a la base de datos');
}

$conexion->set_charset("utf8mb4");

// ============================================
// VERIFICAR ESTRUCTURA DE TABLA
// ============================================
$result = $conexion->query("SHOW COLUMNS FROM ventas");
if (!$result) {
    sendJSON(false, 'Error al verificar estructura de tabla ventas');
}

$columnasVentas = [];
while ($row = $result->fetch_assoc()) {
    $columnasVentas[] = $row['Field'];
}

$tieneSubtotal = in_array('Subtotal', $columnasVentas);
$tieneImpuesto = in_array('Impuesto', $columnasVentas);

error_log("Columnas en ventas: " . implode(', ', $columnasVentas));

// ============================================
// OBTENER Y VALIDAR JSON
// ============================================
$json = file_get_contents('php://input');

if (empty($json)) {
    sendJSON(false, 'No se recibieron datos');
}

$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    sendJSON(false, 'JSON inválido: ' . json_last_error_msg());
}

// ============================================
// VALIDAR CAMPOS REQUERIDOS
// ============================================
$required = ['items', 'total', 'paymentMethod', 'vendedor'];
foreach ($required as $field) {
    if (!isset($data[$field])) {
        sendJSON(false, "Campo requerido faltante: {$field}");
    }
}

if (!is_array($data['items']) || empty($data['items'])) {
    sendJSON(false, 'Debe incluir al menos un producto');
}

if (!is_numeric($data['total']) || $data['total'] <= 0) {
    sendJSON(false, 'El total debe ser un número mayor a 0');
}

// ============================================
// PROCESAR TRANSACCIÓN
// ============================================
$conexion->begin_transaction();
$email_sent_status = 'not_attempted';

try {
    $items = $data['items'];
    $subtotal = isset($data['subtotal']) ? floatval($data['subtotal']) : 0;
    $tax = isset($data['tax']) ? floatval($data['tax']) : 0;
    $total = floatval($data['total']);
    $paymentMethod = $data['paymentMethod'];
    // Usar el usuario de la sesión en lugar del nombre completo del frontend
    $vendedor = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : $data['vendedor'];
    $fecha_venta = date('Y-m-d H:i:s');

    // ==================== GENERAR ID DE FACTURA ====================
    $nuevo_numero = 1;
    $query_last = "SELECT Factura_Id FROM ventas WHERE Factura_Id LIKE 'BS-%' ORDER BY Id DESC LIMIT 1";
    $result = $conexion->query($query_last);
    
    if (!$result) {
        throw new Exception("Error al consultar facturas: " . $conexion->error);
    }
    
    if ($result->num_rows > 0) {
        $last_factura = $result->fetch_assoc()['Factura_Id'];
        $numero_extraido = intval(substr($last_factura, 3));
        $nuevo_numero = $numero_extraido + 1;
    }
    $factura_id = 'BS-' . str_pad($nuevo_numero, 4, '0', STR_PAD_LEFT);

    // ==================== LÓGICA DE CRÉDITO ====================
    if ($paymentMethod === 'Credito') {
        
        if (empty($data['nombreCliente'])) {
            throw new Exception("Para crédito se requiere el nombre del cliente");
        }
        
        if (empty($data['idCliente'])) {
            throw new Exception("Para crédito se requiere el ID del cliente");
        }

        // Generar ID de deuda
        $nuevo_numero_deuda = 1;
        $query_deuda = "SELECT idDeuda FROM deudas WHERE idDeuda LIKE 'BS-D-%' ORDER BY idDeuda DESC LIMIT 1";
        $result_deuda = $conexion->query($query_deuda);
        
        if ($result_deuda && $result_deuda->num_rows > 0) {
            $last_deuda = $result_deuda->fetch_assoc()['idDeuda'];
            $numero_deuda = intval(substr($last_deuda, 5));
            $nuevo_numero_deuda = $numero_deuda + 1;
        }
        $idDeuda = 'BS-D-' . str_pad($nuevo_numero_deuda, 4, '0', STR_PAD_LEFT);

        $fechaRegistro = date('Y-m-d');
        $horaPago = date('H:i:s');
        $direccion = isset($data['direccion']) ? $data['direccion'] : 'N/A';

        $stmt_deuda = $conexion->prepare(
            "INSERT INTO deudas (idDeuda, idCliente, nombreCliente, monto, fechaRegistro, usuarioCajero, facturaID, estado, horaPago, direccion, notasPago) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, '')"
        );

        if (!$stmt_deuda) {
            throw new Exception("Error al preparar inserción de deuda: " . $conexion->error);
        }

        $stmt_deuda->bind_param(
            "sssdsssss",
            $idDeuda,
            $data['idCliente'],
            $data['nombreCliente'],
            $total,
            $fechaRegistro,
            $vendedor,
            $factura_id,
            $horaPago,
            $direccion
        );

        if (!$stmt_deuda->execute()) {
            throw new Exception("Error al registrar deuda: " . $stmt_deuda->error);
        }
        $stmt_deuda->close();

        // Insertar detalles de deuda y verificar/actualizar stock
        foreach ($items as $item) {
            
            $stmt_check = $conexion->prepare("SELECT Stock FROM stock WHERE Id = ?");
            if (!$stmt_check) {
                throw new Exception("Error al preparar verificación de stock");
            }
            
            $stmt_check->bind_param("i", $item['id']);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows === 0) {
                throw new Exception("Producto ID {$item['id']} no encontrado en inventario");
            }
            
            $stock_actual = $result_check->fetch_assoc()['Stock'];
            $stmt_check->close();
            
            if ($stock_actual < $item['cantidad']) {
                throw new Exception("Stock insuficiente para: {$item['nombre']}. Disponible: {$stock_actual}, solicitado: {$item['cantidad']}");
            }

            $stmt_det = $conexion->prepare(
                "INSERT INTO deudas_detalle (idDeuda, productoVendido, cantidad, precio) VALUES (?, ?, ?, ?)"
            );

            if (!$stmt_det) {
                throw new Exception("Error al preparar detalle de deuda");
            }

            $stmt_det->bind_param(
                "ssid",
                $idDeuda,
                $item['nombre'],
                $item['cantidad'],
                $item['precio']
            );

            if (!$stmt_det->execute()) {
                throw new Exception("Error en detalle de deuda: " . $stmt_det->error);
            }
            $stmt_det->close();

            $stmt_stock = $conexion->prepare("UPDATE stock SET Stock = Stock - ? WHERE Id = ?");
            if (!$stmt_stock) {
                throw new Exception("Error al preparar actualización de stock");
            }
            
            $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);
            
            if (!$stmt_stock->execute()) {
                throw new Exception("Error al actualizar stock: " . $stmt_stock->error);
            }
            $stmt_stock->close();
        }

        $conexion->commit();

        // ✅ ENVIAR EMAIL DE DEUDA
        try {
            enviarNotificacionDeuda($idDeuda, $data, $items);
            $email_sent_status = 'sent';
        } catch (Exception $e) {
            error_log("Error al enviar notificación de deuda: " . $e->getMessage());
            $email_sent_status = 'failed';
        }

        $message = 'Deuda registrada correctamente';
        if ($email_sent_status === 'sent') {
            $message .= '. Notificación enviada.';
        } elseif ($email_sent_status === 'failed') {
            $message .= '. Pero hubo un error al enviar la notificación por correo.';
        }

        sendJSON(true, $message, [
            'id_deuda' => $idDeuda,
            'factura_id' => $idDeuda,
            'tipo' => 'credito',
            'email_status' => $email_sent_status
        ]);

    } else {
        // ==================== VENTA NORMAL ====================
        
        // Verificar stock y obtener datos
        $productos_array = [];
        $marcas_array = [];
        
        foreach ($items as $item) {
            $stmt_check = $conexion->prepare("SELECT Stock, Marca FROM stock WHERE Id = ?");
            if (!$stmt_check) {
                throw new Exception("Error al preparar verificación de stock");
            }
            
            $stmt_check->bind_param("i", $item['id']);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            
            if ($result_check->num_rows === 0) {
                throw new Exception("Producto ID {$item['id']} no encontrado");
            }
            
            $row = $result_check->fetch_assoc();
            $stock_actual = $row['Stock'];
            $marca_db = $row['Marca'];
            $stmt_check->close();
            
            if ($stock_actual < $item['cantidad']) {
                throw new Exception("Stock insuficiente: {$item['nombre']}. Disponible: {$stock_actual}");
            }

            $productos_array[] = $item['nombre'] . " x" . $item['cantidad'];
            $marcas_array[] = $marca_db;
        }

        // Preparar datos de cliente
        // Para métodos no-crédito: usar datos del cliente si existen, sino "CONSUMIDOR FINAL"
        if ($paymentMethod === 'Efectivo' || $paymentMethod === 'Tarjeta' || $paymentMethod === 'Transferencia' || $paymentMethod === 'Mixto') {
            $cliente = isset($data['nombreCliente']) && !empty($data['nombreCliente']) ? $data['nombreCliente'] : 'CONSUMIDOR FINAL';
            $identidad = 'CF';
            $celular = isset($data['celular']) && !empty($data['celular']) ? $data['celular'] : 'NA';
            $direccion = isset($data['direccion']) && !empty($data['direccion']) ? $data['direccion'] : 'NA';
        } else {
            // Para otros métodos de pago, usar valores genéricos
            $cliente = isset($data['nombreCliente']) && !empty($data['nombreCliente']) ? $data['nombreCliente'] : 'Cliente General';
            $identidad = isset($data['identidad']) && !empty($data['identidad']) ? $data['identidad'] : 'CF';
            $celular = isset($data['celular']) && !empty($data['celular']) ? $data['celular'] : 'N/A';
            $direccion = isset($data['direccion']) && !empty($data['direccion']) ? $data['direccion'] : 'N/A';
        }
        $banco = 'N/A';
        
        // Si es transferencia, obtener el banco seleccionado
        if ($paymentMethod === 'Transferencia' && isset($data['banco']) && !empty($data['banco'])) {
            $banco = $data['banco'];
        }
        
        // Si es pago mixto, obtener el banco si hay transferencia
        if ($paymentMethod === 'Mixto' && isset($data['mixto_banco']) && !empty($data['mixto_banco'])) {
            $banco = $data['mixto_banco'];
        }
        
        $cambio = 0;
        if (isset($data['amountReceived']) && is_numeric($data['amountReceived'])) {
            $cambio = floatval($data['amountReceived']) - $total;
        }

        $efectivo = 0;
        $tarjeta = 0;
        $transferencia = 0;

        if ($paymentMethod === 'Efectivo' && isset($data['amountReceived'])) {
            $efectivo = floatval($data['amountReceived']);
        } elseif ($paymentMethod === 'Tarjeta') {
            $tarjeta = $total;
        } elseif ($paymentMethod === 'Transferencia') {
            $transferencia = $total;
        } elseif ($paymentMethod === 'Mixto') {
            // Pago mixto: obtener los montos individuales
            $efectivo = isset($data['mixto_efectivo']) ? floatval($data['mixto_efectivo']) : 0;
            $tarjeta = isset($data['mixto_tarjeta']) ? floatval($data['mixto_tarjeta']) : 0;
            $transferencia = isset($data['mixto_transferencia']) ? floatval($data['mixto_transferencia']) : 0;
            
            // Validar que la suma sea correcta
            $suma_pagos = $efectivo + $tarjeta + $transferencia;
            if (abs($suma_pagos - $total) > 0.01) {
                throw new Exception("La suma de pagos mixtos no coincide con el total");
            }
            
            // Validar banco si hay transferencia
            if ($transferencia > 0 && empty($banco)) {
                throw new Exception("Debe seleccionar un banco para la transferencia en pago mixto");
            }
        }

        // Preparar campos para INSERT
        $producto_vendido = implode(", ", $productos_array);
        $marca_vendida = implode(", ", array_unique($marcas_array));
        $cantidad_total = array_sum(array_column($items, 'cantidad'));
        $precio_promedio = $cantidad_total > 0 ? ($total / $cantidad_total) : 0;

        // Truncar si es necesario
        if (strlen($producto_vendido) > 500) {
            $producto_vendido = substr($producto_vendido, 0, 497) . "...";
        }
        if (strlen($marca_vendida) > 500) {
            $marca_vendida = substr($marca_vendida, 0, 497) . "...";
        }

        error_log("=== DATOS PARA INSERT ===");
        error_log("Factura_Id: " . $factura_id);
        error_log("Cliente: " . $cliente);
        error_log("Producto_Vendido: " . $producto_vendido);
        error_log("Marca: " . $marca_vendida);
        error_log("Cantidad: " . $cantidad_total);
        error_log("Precio: " . $precio_promedio);
        error_log("Total: " . $total);

        // ✅ INSERTAR VENTA según estructura real
        if ($tieneSubtotal && $tieneImpuesto) {
            // CON Subtotal e Impuesto
            $stmt_venta = $conexion->prepare(
                "INSERT INTO ventas (
                    Factura_Id, Cliente, Identidad, Celular, Direccion, 
                    Producto_Vendido, Marca, Cantidad, Precio,
                    Subtotal, Impuesto, Total, Fecha_Venta, 
                    MetodoPago, Vendedor, Efectivo, Tarjeta, Transferencia, Banco, Cambio
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt_venta) {
                throw new Exception("Error al preparar venta: " . $conexion->error);
            }

            $stmt_venta->bind_param(
                "sssssssssdddsssdddsd",
                $factura_id,
                $cliente,
                $identidad,
                $celular,
                $direccion,
                $producto_vendido,
                $marca_vendida,
                $cantidad_total,
                $precio_promedio,
                $subtotal,
                $tax,
                $total,
                $fecha_venta,
                $paymentMethod,
                $vendedor,
                $efectivo,
                $tarjeta,
                $transferencia,
                $banco,
                $cambio
            );

        } else {
            // SIN Subtotal e Impuesto
            $stmt_venta = $conexion->prepare(
                "INSERT INTO ventas (
                    Factura_Id, Cliente, Identidad, Celular, Direccion, 
                    Producto_Vendido, Marca, Cantidad, Precio,
                    Total, Fecha_Venta, MetodoPago, Vendedor, 
                    Efectivo, Tarjeta, Transferencia, Banco, Cambio
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt_venta) {
                throw new Exception("Error al preparar venta: " . $conexion->error);
            }

            $stmt_venta->bind_param(
                "ssssssssdssssdddsd",
                $factura_id,
                $cliente,
                $identidad,
                $celular,
                $direccion,
                $producto_vendido,
                $marca_vendida,
                $cantidad_total,
                $precio_promedio,
                $total,
                $fecha_venta,
                $paymentMethod,
                $vendedor,
                $efectivo,
                $tarjeta,
                $transferencia,
                $banco,
                $cambio
            );
        }

        if (!$stmt_venta->execute()) {
            throw new Exception("Error al insertar venta: " . $stmt_venta->error);
        }

        $venta_id = $conexion->insert_id;
        $stmt_venta->close();

        error_log("✅ Venta insertada con ID: " . $venta_id);

        // Insertar detalles y actualizar stock
        foreach ($items as $item) {
            
            $subtotal_item = floatval($item['precio']) * intval($item['cantidad']);
            $marca = isset($item['marca']) ? $item['marca'] : 'N/A';
            $tipo_precio_nombre = isset($item['tipoPrecioNombre']) ? $item['tipoPrecioNombre'] : 'Precio_Unitario';
            $tipo_precio_id = isset($item['tipoPrecioId']) ? intval($item['tipoPrecioId']) : 1;

            $stmt_det = $conexion->prepare(
                "INSERT INTO ventas_detalle (Id_Venta, Id_Producto, Producto_Vendido, Marca, Cantidad, Precio, tipo_precio_nombre, tipo_precio_id, Subtotal, Fecha) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt_det) {
                throw new Exception("Error al preparar detalle de venta: " . $conexion->error);
            }

            $stmt_det->bind_param(
                "iissidsids",
                $venta_id,
                $item['id'],
                $item['nombre'],
                $marca,
                $item['cantidad'],
                $item['precio'],
                $tipo_precio_nombre,
                $tipo_precio_id,
                $subtotal_item,
                $fecha_venta
            );

            if (!$stmt_det->execute()) {
                throw new Exception("Error en detalle de venta: " . $stmt_det->error);
            }
            $stmt_det->close();

            $stmt_stock = $conexion->prepare("UPDATE stock SET Stock = Stock - ? WHERE Id = ?");
            if (!$stmt_stock) {
                throw new Exception("Error al preparar actualización de stock: " . $conexion->error);
            }
            
            $stmt_stock->bind_param("ii", $item['cantidad'], $item['id']);

            if (!$stmt_stock->execute()) {
                throw new Exception("Error al actualizar stock: " . $stmt_stock->error);
            }
            $stmt_stock->close();
        }

        $conexion->commit();

        // ✅ SINCRONIZAR LOGROS AUTOMÁTICAMENTE
        $logros_desbloqueados = [];
        if (file_exists('auto_sincronizar_logros.php')) {
            require_once 'auto_sincronizar_logros.php';
            try {
                $logros_desbloqueados = autoSincronizarLogrosUsuario($vendedor);
            } catch (Exception $e) {
                error_log("Error al sincronizar logros: " . $e->getMessage());
            }
        }

        // ✅ ACUMULAR PUNTOS DE FIDELIDAD AUTOMÁTICAMENTE
        $puntos_info = [];
        if (file_exists('funciones_puntos.php')) {
            require_once 'funciones_puntos.php';
            try {
                $nombre_cliente = isset($data['nombreCliente']) && !empty($data['nombreCliente']) 
                    ? $data['nombreCliente'] 
                    : 'CONSUMIDOR FINAL';
                
                // Solo acumular puntos si NO es consumidor final
                if ($nombre_cliente !== 'CONSUMIDOR FINAL') {
                    error_log("=== ACUMULANDO PUNTOS ===");
                    error_log("Cliente: " . $nombre_cliente);
                    error_log("Total: " . $total);
                    error_log("Venta ID: " . $venta_id);
                    
                    $puntos_info = acumularPuntos($nombre_cliente, $total, $venta_id, $vendedor);
                    
                    error_log("Resultado puntos: " . json_encode($puntos_info));
                } else {
                    error_log("No se acumulan puntos para CONSUMIDOR FINAL");
                }
            } catch (Exception $e) {
                error_log("Error al acumular puntos: " . $e->getMessage());
            }
        } else {
            error_log("ERROR: funciones_puntos.php NO EXISTE");
        }

        // ✅ ENVIAR EMAIL DE VENTA
        try {
            enviarNotificacionVenta($factura_id, $data, $items);
            $email_sent_status = 'sent';
        } catch (Exception $e) {
            error_log("Error al enviar notificación de venta: " . $e->getMessage());
            $email_sent_status = 'failed';
        }

        $message = 'Venta procesada correctamente';
        if ($email_sent_status === 'sent') {
            $message .= '. Notificación enviada.';
        } elseif ($email_sent_status === 'failed') {
            $message .= '. Pero hubo un error al enviar la notificación por correo.';
        }

        sendJSON(true, $message, [
            'factura_id' => $factura_id,
            'venta_id' => $venta_id,
            'tipo' => 'venta',
            'email_status' => $email_sent_status,
            'should_print' => true,  // Trigger automatic printing for non-credit sales
            'logros_desbloqueados' => $logros_desbloqueados,  // Nuevos logros desbloqueados
            'puntos_ganados' => $puntos_info['puntos_ganados'] ?? 0,  // Puntos de fidelidad ganados
            'nivel_cliente' => $puntos_info['nivel_nuevo'] ?? 'Bronce',  // Nivel del cliente
            'subio_nivel' => $puntos_info['subio_nivel'] ?? false  // Si subió de nivel
        ]);
    }

} catch (Exception $e) {
    $conexion->rollback();
    error_log("ERROR TRANSACCIÓN: " . $e->getMessage());
    error_log("TRACE: " . $e->getTraceAsString());
    
    sendJSON(false, $e->getMessage());
}

$conexion->close();

// ============================================
// FUNCIONES DE NOTIFICACIÓN POR EMAIL
// ============================================

function enviarNotificacionVenta($factura_id, $data, $items) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'reysystemnotificaciones@gmail.com';
        $mail->Password   = 'sbzl symo xbpt atoq';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        // Remitentes y destinatarios
        $mail->setFrom('reysystemnotificaciones@gmail.com', 'ReySystemAPP - Notificación de Venta');
        $mail->addAddress('sashaalejandrar24@gmail.com', 'Sasha');
        $mail->addAddress('jesushernan.ordo@gmail.com', 'Jesús');
        $mail->addReplyTo('no-reply@tiendasrey.com', 'No Responder');

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = "✅ Nueva Venta Realizada - Factura: {$factura_id}";

        // Construir la tabla de productos dinámicamente
        $productos_html = '';
        foreach ($items as $item) {
            $subtotal_item = $item['precio'] * $item['cantidad'];
            $marca = isset($item['marca']) ? $item['marca'] : 'N/A';
            $tipo_precio = isset($item['tipoPrecioNombre']) ? $item['tipoPrecioNombre'] : 'Precio_Unitario';
            $productos_html .= '
            <tr>
                <td>' . htmlspecialchars($item['nombre']) . '</td>
                <td>' . htmlspecialchars($marca) . '</td>
                <td>' . htmlspecialchars($item['cantidad']) . '</td>
                <td>L. ' . number_format($item['precio'], 2) . '</td>
                <td><span style="font-size: 11px; color: #666; font-style: italic;">(' . htmlspecialchars($tipo_precio) . ')</span></td>
                <td>L. ' . number_format($subtotal_item, 2) . '</td>
            </tr>';
        }

        $cambio = isset($data['amountReceived']) ? $data['amountReceived'] - $data['total'] : 0;

        $mail->Body = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 20px; }
                .header { background-color: #1152d4; color: white; padding: 15px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { padding: 20px 0; }
                .info-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .info-table th { background-color: #f2f2f2; text-align: left; padding: 10px; font-weight: bold; }
                .info-table td { padding: 10px; border-bottom: 1px solid #ddd; }
                .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #777; }
                .total-row td { font-weight: bold; background-color: #f9f9f9; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 Nueva Venta Registrada</h1>
                    <p>Factura: <strong>' . htmlspecialchars($factura_id) . '</strong></p>
                </div>
                <div class="content">
                    <h2>Resumen de la Venta</h2>
                    <table class="info-table">
                        <tr><th><strong>Fecha y Hora</strong></th><td>' . date('Y-m-d H:i:s') . '</td></tr>
                        <tr><th><strong>Vendedor</strong></th><td>' . htmlspecialchars($data['vendedor']) . '</td></tr>
                        <tr><th><strong>Método de Pago</strong></th><td>' . htmlspecialchars($data['paymentMethod']) . '</td></tr>';
                        
        if ($data['paymentMethod'] === 'Efectivo' && isset($data['amountReceived'])) {
            $mail->Body .= '<tr><th><strong>Monto Recibido</strong></th><td>L. ' . number_format($data['amountReceived'], 2) . '</td></tr>
                            <tr><th><strong>Cambio</strong></th><td>L. ' . number_format($cambio, 2) . '</td></tr>';
        }
                        
        $mail->Body .= '</table>
                    
                    <h3 style="margin-top: 30px;">Datos del Cliente</h3>
                    <table class="info-table">
                        <tr>
                            <th><strong>Nombre</strong></th>
                            <td>' . htmlspecialchars(isset($data['nombreCliente']) && !empty($data['nombreCliente']) ? $data['nombreCliente'] : 'CONSUMIDOR FINAL') . '</td>
                        </tr>
                        <tr>
                            <th><strong>Celular</strong></th>
                            <td>' . htmlspecialchars(isset($data['celular']) && !empty($data['celular']) ? $data['celular'] : 'NA') . '</td>
                        </tr>
                        <tr>
                            <th><strong>Dirección</strong></th>
                            <td>' . htmlspecialchars(isset($data['direccion']) && !empty($data['direccion']) ? $data['direccion'] : 'NA') . '</td>
                        </tr>
                    </table>

                    <h3 style="margin-top: 30px;">Productos Vendidos</h3>
                    <table class="info-table">
                        <tr>
                            <th><strong>Producto</strong></th>
                            <th><strong>Marca</strong></th>
                            <th><strong>Cantidad</strong></th>
                            <th><strong>Precio Unit.</strong></th>
                            <th><strong>Tipo Precio</strong></th>
                            <th><strong>Subtotal</strong></th>
                        </tr>
                        ' . $productos_html . '
                        <tr class="total-row">
                            <td colspan="4" style="text-align:right;"><strong>Subtotal:</strong></td>
                            <td>L. ' . number_format($data['subtotal'], 2) . '</td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="4" style="text-align:right;"><strong>Impuesto (ISV 15%):</strong></td>
                            <td>L. ' . number_format($data['tax'], 2) . '</td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="4" style="text-align:right;"><strong>Total General:</strong></td>
                            <td>L. ' . number_format($data['total'], 2) . '</td>
                        </tr>
                    </table>';
                        
        if ($data['paymentMethod'] === 'Efectivo' && isset($data['amountReceived'])) {
            $mail->Body .= '<table class="info-table" style="margin-top: 20px;">
                            <tr><th><strong>Monto Recibido</strong></th><td>L. ' . number_format($data['amountReceived'], 2) . '</td></tr>
                            <tr><th><strong>Cambio</strong></th><td>L. ' . number_format($cambio, 2) . '</td></tr>
                            </table>';
        }
                        
        $mail->Body .= '</div>
                <div class="footer">
                    <p>Este es un mensaje automático generado por ReySystemAPP. Por favor, no responda a este correo.</p>
                </div>
            </div>
        </body>
        </html>';

        $mail->send();
        error_log("✅ Email de venta enviado correctamente - Factura: {$factura_id}");
        
    } catch (Exception $e) {
        throw new Exception("El mensaje de venta no pudo ser enviado. Error de PHPMailer: {$mail->ErrorInfo}");
    }
}

function enviarNotificacionDeuda($idDeuda, $data, $items) {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'reysystemnotificaciones@gmail.com';
        $mail->Password   = 'sbzl symo xbpt atoq';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('reysystemnotificaciones@gmail.com', 'ReySystemAPP - Notificación de Deuda');
        $mail->addAddress('sashaalejandrar24@gmail.com', 'Sasha');
        $mail->addAddress('jesushernan.ordo@gmail.com', 'Jesús');
        $mail->addReplyTo('no-reply@tiendasrey.com', 'No Responder');

        $mail->isHTML(true);
        $mail->Subject = "📌 Nueva Deuda Registrada - ID: {$idDeuda}";

        $productos_html = '';
        foreach ($items as $item) {
            $subtotal_item = $item['precio'] * $item['cantidad'];
            $marca = isset($item['marca']) ? $item['marca'] : 'N/A';
            $tipo_precio = isset($item['tipoPrecioNombre']) ? $item['tipoPrecioNombre'] : 'Precio_Unitario';
            $productos_html .= '
            <tr>
                <td>' . htmlspecialchars($item['nombre']) . '</td>
                <td>' . htmlspecialchars($marca) . '</td>
                <td>' . htmlspecialchars($item['cantidad']) . '</td>
                <td>L. ' . number_format($item['precio'], 2) . '</td>
                <td><span style="font-size: 11px; color: #666; font-style: italic;">(' . htmlspecialchars($tipo_precio) . ')</span></td>
                <td>L. ' . number_format($subtotal_item, 2) . '</td>
            </tr>';
            }

        $identidad = isset($data['identidad']) ? $data['identidad'] : 'N/A';
        $celular = isset($data['celular']) ? $data['celular'] : 'N/A';

        $mail->Body = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 20px; }
                .header { background-color: #d41111; color: white; padding: 15px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { padding: 20px 0; }
                .info-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .info-table th { background-color: #f2f2f2; text-align: left; padding: 10px; font-weight: bold; }
                .info-table td { padding: 10px; border-bottom: 1px solid #ddd; }
                .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #777; }
                .total-row td { font-weight: bold; background-color: #f9f9f9; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>📌 Nueva Deuda Registrada</h1>
                    <p>ID de Deuda: <strong>' . htmlspecialchars($idDeuda) . '</strong></p>
                </div>
                <div class="content">
                    <h2>Datos del Cliente</h2>
                    <table class="info-table">
                        <tr><th>Nombre</th><td>' . htmlspecialchars($data['nombreCliente']) . '</td></tr>
                        <tr><th>ID Cliente</th><td>' . htmlspecialchars($data['idCliente']) . '</td></tr>
                        <tr><th>Identidad</th><td>' . htmlspecialchars($identidad) . '</td></tr>
                        <tr><th>Celular</th><td>' . htmlspecialchars($celular) . '</td></tr>
                        <tr><th>Dirección</th><td>' . htmlspecialchars($data['direccion']) . '</td></tr>
                        <tr><th>Fecha de Registro</th><td>' . date('Y-m-d H:i:s') . '</td></tr>
                        <tr><th>Registrado por</th><td>' . htmlspecialchars($data['vendedor']) . '</td></tr>
                    </table>

                    <h3 style="margin-top: 30px;">Productos Asociados</h3>
                    <table class="info-table">
                        <tr>
                            <th>Producto</th>
                            <th>Marca</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Tipo Precio</th>
                            <th>Subtotal</th>
                        </tr>
                        ' . $productos_html . '
                        <tr class="total-row">
                            <td colspan="4" style="text-align:right;"><strong>Total Deuda:</strong></td>
                            <td><strong>L. ' . number_format($data['total'], 2) . '</strong></td>
                        </tr>
                    </table>
                </div>
                <div class="footer">
                    <p>Este es un mensaje automático generado por ReySystemAPP. Por favor, no responda a este correo.</p>
                </div>
            </div>
        </body>
        </html>';

        $mail->send();
        error_log("✅ Email de deuda enviado correctamente - ID: {$idDeuda}");

    } catch (Exception $e) {
        throw new Exception("No se pudo enviar la notificación de deuda. Error: {$mail->ErrorInfo}");
    }
}
?>