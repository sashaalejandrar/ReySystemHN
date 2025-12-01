<?php
/**
 * Generar e imprimir tickets de ventas para un cliente
 * Versión simplificada que usa TCPDF directamente
 */

require_once 'db_connect.php';
require_once 'vendor/autoload.php';

// Verificar que se recibieron IDs de ventas
if (!isset($_POST['ventas']) || empty($_POST['ventas'])) {
    die('No se especificaron ventas para imprimir');
}

// Convertir string de IDs a array
$ventas_ids = explode(',', $_POST['ventas']);

if (empty($ventas_ids)) {
    die('No se especificaron ventas válidas');
}

try {
    // Obtener información de la empresa desde configuracion_app
    $stmt_config = $conexion->prepare("SELECT nombre_empresa, direccion_empresa, telefono_empresa FROM configuracion_app LIMIT 1");
    $stmt_config->execute();
    $result_config = $stmt_config->get_result();
    
    $empresa = [
        'nombre' => 'BODEGA SILOE',
        'direccion' => 'La Flecha, Macuelizo',
        'ciudad' => 'Santa Barbara, Honduras',
        'telefono' => '+504 9770-2487'
    ];
    
    if ($result_config->num_rows > 0) {
        $config = $result_config->fetch_assoc();
        $empresa['nombre'] = $config['nombre_empresa'] ?: $empresa['nombre'];
        $empresa['direccion'] = $config['direccion_empresa'] ?: $empresa['direccion'];
        $empresa['telefono'] = $config['telefono_empresa'] ?: $empresa['telefono'];
    }
    $stmt_config->close();
    
    // Crear PDF estilo ticket térmico (80mm de ancho, altura automática)
    $pdf = new TCPDF('P', 'mm', array(80, 297), true, 'UTF-8', false);
    $pdf->SetMargins(3, 3, 3);
    $pdf->SetAutoPageBreak(true, 5);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetFont('helvetica', '', 9);
    
    // Procesar cada venta
    foreach ($ventas_ids as $venta_id) {
        $venta_id = intval(trim($venta_id));
        
        if ($venta_id <= 0) continue;
        
        // Obtener datos de la venta
        $stmt = $conexion->prepare("
            SELECT 
                Id, Factura_Id, Cliente, Producto_Vendido, Cantidad, Precio,
                Total, Fecha_Venta, MetodoPago, Vendedor
            FROM ventas 
            WHERE Id = ?
        ");
        
        $stmt->bind_param("i", $venta_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            continue;
        }
        
        $venta = $result->fetch_assoc();
        $stmt->close();
        
        // Obtener detalle de productos desde ventas_detalle
        $stmt_detalle = $conexion->prepare("
            SELECT 
                Producto_Vendido as nombre,
                Cantidad as cantidad,
                Precio as precio,
                Subtotal as subtotal,
                tipo_precio_nombre
            FROM ventas_detalle 
            WHERE Id_Venta = ?
            ORDER BY Id
        ");
        
        $stmt_detalle->bind_param("i", $venta_id);
        $stmt_detalle->execute();
        $result_detalle = $stmt_detalle->get_result();
        
        $detalle_productos = [];
        while ($producto = $result_detalle->fetch_assoc()) {
            $detalle_productos[] = [
                'nombre' => $producto['nombre'],
                'cantidad' => floatval($producto['cantidad']),
                'precio' => floatval($producto['precio']),
                'subtotal' => floatval($producto['subtotal']),
                'tipo_precio' => $producto['tipo_precio_nombre'] ?: 'Precio_Unitario'
            ];
        }
        $stmt_detalle->close();
        
        // Si no hay detalle en ventas_detalle, usar el método antiguo (fallback)
        if (empty($detalle_productos)) {
            $productos_array = explode(", ", $venta['Producto_Vendido']);
            $cantidades_array = explode(", ", $venta['Cantidad']);
            $precios_array = explode(", ", $venta['Precio']);
            
            for ($i = 0; $i < count($productos_array); $i++) {
                $cantidad = isset($cantidades_array[$i]) ? floatval($cantidades_array[$i]) : 1;
                $precio = isset($precios_array[$i]) ? floatval($precios_array[$i]) : 0;
                
                $detalle_productos[] = [
                    'nombre' => $productos_array[$i],
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'subtotal' => $cantidad * $precio
                ];
            }
        }
        
        // Agregar página
        $pdf->AddPage();
        
        // === ENCABEZADO ===
        $pdf->SetFont('', 'B', 13);
        $pdf->Cell(0, 7, strtoupper($empresa['nombre']), 0, 1, 'C');
        $pdf->SetFont('', '', 8);
        $pdf->Cell(0, 4, $empresa['direccion'], 0, 1, 'C');
        $pdf->Cell(0, 4, $empresa['ciudad'], 0, 1, 'C');
        $pdf->Cell(0, 4, 'Tel: ' . $empresa['telefono'], 0, 1, 'C');
        $pdf->Ln(5);
        
        // === NÚMERO DE TICKET ===
        $pdf->SetFont('', 'B', 11);
        $pdf->Cell(0, 6, 'TICKET DE VENTA', 0, 1, 'C');
        $pdf->SetFont('', '', 9);
        $pdf->Cell(0, 5, 'No. ' . ($venta['Factura_Id'] ?: str_pad($venta['Id'], 6, '0', STR_PAD_LEFT)), 0, 1, 'C');
        $pdf->Ln(6);
        
        // === INFORMACIÓN DE VENTA ===
        $pdf->SetFont('', '', 8);
        $pdf->Cell(22, 5, 'Fecha:', 0, 0, 'L');
        $pdf->SetFont('', 'B', 8);
        $pdf->Cell(0, 5, date('d/m/Y H:i', strtotime($venta['Fecha_Venta'])), 0, 1, 'L');
        
        $pdf->SetFont('', '', 8);
        $pdf->Cell(22, 5, 'Cliente:', 0, 0, 'L');
        $pdf->SetFont('', 'B', 8);
        $nombre_cliente = $venta['Cliente'] ?: 'CONSUMIDOR FINAL';
        $pdf->Cell(0, 5, substr($nombre_cliente, 0, 30), 0, 1, 'L');
        
        $pdf->SetFont('', '', 8);
        $pdf->Cell(22, 5, 'Vendedor:', 0, 0, 'L');
        $pdf->SetFont('', 'B', 8);
        $pdf->Cell(0, 5, $venta['Vendedor'], 0, 1, 'L');
        
        $pdf->SetFont('', '', 8);
        $pdf->Cell(22, 5, 'Pago:', 0, 0, 'L');
        $pdf->SetFont('', 'B', 8);
        $pdf->Cell(0, 5, ucfirst(strtolower($venta['MetodoPago'])), 0, 1, 'L');
        
        // Calcular total de items primero
        $total_items = 0;
        foreach ($detalle_productos as $producto) {
            $total_items += $producto['cantidad'];
        }
        
        // Mostrar total de artículos
        $pdf->SetFont('', '', 8);
        $pdf->Cell(22, 5, 'Artículos:', 0, 0, 'L');
        $pdf->SetFont('', 'B', 8);
        $pdf->Cell(0, 5, $total_items, 0, 1, 'L');
        
        $pdf->Ln(7);
        
        // === PRODUCTOS ===
        $pdf->SetFont('', 'B', 9);
        $pdf->Cell(0, 6, 'PRODUCTOS', 0, 1, 'L');
        $pdf->Ln(2);
        
        $pdf->SetFont('', '', 8);
        
        foreach ($detalle_productos as $producto) {
            $cantidad = $producto['cantidad'];
            $precio = $producto['precio'];
            $subtotal = $producto['subtotal'];
            $tipo_precio = isset($producto['tipo_precio']) ? $producto['tipo_precio'] : 'Precio_Unitario';
            
            // Nombre del producto
            $pdf->SetFont('', 'B', 9);
            $pdf->Cell(0, 5, substr($producto['nombre'], 0, 35), 0, 1, 'L');
            
            // Tipo de precio (en gris claro)
            $pdf->SetFont('', 'I', 7);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 3, '  (' . $tipo_precio . ')', 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
            
            // Cantidad y precio
            $pdf->SetFont('', '', 8);
            $pdf->Cell(45, 4, '  ' . $cantidad . ' x L' . number_format($precio, 2), 0, 0, 'L');
            $pdf->SetFont('', 'B', 9);
            $pdf->Cell(29, 4, 'L' . number_format($subtotal, 2), 0, 1, 'R');
            
            $pdf->Ln(2);
        }
        
        $pdf->Ln(5);
        
        // === TOTAL ===
        $pdf->SetFont('', 'B', 12);
        $pdf->Cell(45, 7, 'TOTAL:', 0, 0, 'L');
        $pdf->Cell(29, 7, 'L' . number_format($venta['Total'], 2), 0, 1, 'R');
        
        $pdf->Ln(8);
        
        // === PIE DE TICKET ===
        $pdf->SetFont('', 'BI', 10);
        $pdf->Cell(0, 5, '¡Gracias por su preferencia!', 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('', '', 7);
        $pdf->Cell(0, 3, 'Conserve este ticket como comprobante', 0, 1, 'C');
        $pdf->Ln(3);
        $pdf->SetFont('', 'I', 7);
        $pdf->Cell(0, 3, 'Sistema Rey', 0, 1, 'C');
        
        $pdf->Ln(10);
    }
    
    // Mostrar el PDF directamente en el navegador
    $pdf->Output('tickets_cliente_' . date('YmdHis') . '.pdf', 'I');
    
} catch (Exception $e) {
    die('Error al generar tickets: ' . $e->getMessage());
}

$conexion->close();
?>
