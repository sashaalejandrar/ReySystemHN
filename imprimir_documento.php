<?php
// Imprimir documento - Ticket o Factura
session_start();

// Conexión a base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset("utf8mb4");

// Obtener ID de factura
$factura_id = isset($_GET['factura_id']) ? $_GET['factura_id'] : '';

if (empty($factura_id)) {
    die("ID de factura no proporcionado");
}

// Obtener información de la empresa
$config_query = "SELECT * FROM configuracion_app WHERE Id = 1";
$config_result = $conexion->query($config_query);
$empresa = $config_result->fetch_assoc();

if (!$empresa) {
    $empresa = [
        'nombre_empresa' => 'Tiendas Rey',
        'direccion_empresa' => '',
        'telefono_empresa' => '',
        'email_empresa' => '',
        'impuesto' => 15.00
    ];
}

// Obtener información de la venta
$venta_query = "SELECT * FROM ventas WHERE Factura_Id = ?";
$stmt = $conexion->prepare($venta_query);
$stmt->bind_param("s", $factura_id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$venta) {
    die("Venta no encontrada");
}

// Obtener detalles de la venta
$detalles_query = "SELECT * FROM ventas_detalle WHERE Id_Venta = ?";
$stmt = $conexion->prepare($detalles_query);
$stmt->bind_param("i", $venta['Id']);
$stmt->execute();
$detalles_result = $stmt->get_result();
$detalles = [];
$cantidad_total = 0;

while ($row = $detalles_result->fetch_assoc()) {
    $detalles[] = $row;
    $cantidad_total += $row['Cantidad'];
}
$stmt->close();

// Determinar tipo de documento basado en cantidad
$es_factura = $cantidad_total >= 500;
$tipo_documento = $es_factura ? 'FACTURA' : 'TICKET';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $tipo_documento; ?> - <?php echo htmlspecialchars($factura_id); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
        }
        
        /* Estilos para TICKET (< 500 items) */
        <?php if (!$es_factura): ?>
        .documento {
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .company-info {
            font-size: 10px;
            margin-bottom: 2px;
        }
        
        .document-type {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0;
            text-align: center;
        }
        
        .info-section {
            margin-bottom: 10px;
            font-size: 11px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        
        .products {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 10px 0;
            margin: 10px 0;
        }
        
        .product-item {
            margin-bottom: 8px;
        }
        
        .product-name {
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .product-details {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }
        
        .totals {
            margin-top: 10px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .total-row.final {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px dashed #000;
            font-size: 10px;
        }
        
        <?php else: ?>
        /* Estilos para FACTURA (>= 500 items) */
        .documento {
            width: 210mm;
            margin: 0 auto;
            padding: 20mm;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .company-section {
            flex: 1;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .company-info {
            font-size: 12px;
            margin-bottom: 3px;
        }
        
        .document-info {
            text-align: right;
        }
        
        .document-type {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .info-row {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
        }
        
        .total-row.final {
            font-size: 16px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 10px;
            margin-top: 10px;
        }
        
        .footer {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #000;
            text-align: center;
            font-size: 11px;
        }
        <?php endif; ?>
        
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
            
            @page {
                <?php if (!$es_factura): ?>
                size: 80mm auto;
                margin: 0;
                <?php else: ?>
                size: A4;
                margin: 15mm;
                <?php endif; ?>
            }
        }
    </style>
</head>
<body>
    <div class="documento">
        <!-- HEADER -->
        <div class="header">
            <?php if (!$es_factura): ?>
                <!-- Ticket Header -->
                <div class="company-name"><?php echo htmlspecialchars($empresa['nombre_empresa']); ?></div>
                <?php if (!empty($empresa['direccion_empresa'])): ?>
                    <div class="company-info"><?php echo htmlspecialchars($empresa['direccion_empresa']); ?></div>
                <?php endif; ?>
                <?php if (!empty($empresa['telefono_empresa'])): ?>
                    <div class="company-info">Tel: <?php echo htmlspecialchars($empresa['telefono_empresa']); ?></div>
                <?php endif; ?>
                <?php if (!empty($empresa['email_empresa'])): ?>
                    <div class="company-info"><?php echo htmlspecialchars($empresa['email_empresa']); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Invoice Header -->
                <div class="company-section">
                    <div class="company-name"><?php echo htmlspecialchars($empresa['nombre_empresa']); ?></div>
                    <?php if (!empty($empresa['direccion_empresa'])): ?>
                        <div class="company-info"><?php echo htmlspecialchars($empresa['direccion_empresa']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($empresa['telefono_empresa'])): ?>
                        <div class="company-info">Teléfono: <?php echo htmlspecialchars($empresa['telefono_empresa']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($empresa['email_empresa'])): ?>
                        <div class="company-info">Email: <?php echo htmlspecialchars($empresa['email_empresa']); ?></div>
                    <?php endif; ?>
                </div>
                <div class="document-info">
                    <div class="document-type"><?php echo $tipo_documento; ?></div>
                    <div class="company-info">No.: <?php echo htmlspecialchars($factura_id); ?></div>
                    <div class="company-info">Fecha: <?php echo date('d/m/Y H:i', strtotime($venta['Fecha_Venta'])); ?></div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- DOCUMENT TYPE (Only for ticket) -->
        <?php if (!$es_factura): ?>
            <div class="document-type"><?php echo $tipo_documento; ?></div>
        <?php endif; ?>
        
        <!-- SALE INFO -->
        <div class="info-section">
            <?php if ($es_factura): ?>
                <div class="section-title">Información de la Venta</div>
                <div class="info-grid">
                    <div>
                        <div class="info-row">
                            <span class="info-label">Cliente:</span>
                            <?php echo htmlspecialchars($venta['Cliente']); ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Celular:</span>
                            <?php echo htmlspecialchars($venta['Celular']); ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Dirección:</span>
                            <?php echo htmlspecialchars($venta['Direccion']); ?>
                        </div>
                    </div>
                    <div>
                        <div class="info-row">
                            <span class="info-label">Vendedor:</span>
                            <?php echo htmlspecialchars($venta['Vendedor']); ?>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Método de Pago:</span>
                            <?php echo htmlspecialchars($venta['MetodoPago']); ?>
                        </div>
                        <?php if ($venta['MetodoPago'] === 'Transferencia' && !empty($venta['Banco']) && $venta['Banco'] !== 'N/A'): ?>
                        <div class="info-row">
                            <span class="info-label">Banco:</span>
                            <?php echo htmlspecialchars($venta['Banco']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="info-row">
                    <span>Factura:</span>
                    <span><?php echo htmlspecialchars($factura_id); ?></span>
                </div>
                <div class="info-row">
                    <span>Fecha:</span>
                    <span><?php echo date('d/m/Y H:i', strtotime($venta['Fecha_Venta'])); ?></span>
                </div>
                <div class="info-row">
                    <span>Cliente:</span>
                    <span><?php echo htmlspecialchars($venta['Cliente']); ?></span>
                </div>
                <div class="info-row">
                    <span>Vendedor:</span>
                    <span><?php echo htmlspecialchars($venta['Vendedor']); ?></span>
                </div>
                <div class="info-row">
                    <span>Pago:</span>
                    <span><?php echo htmlspecialchars($venta['MetodoPago']); ?></span>
                </div>
                <?php if ($venta['MetodoPago'] === 'Transferencia' && !empty($venta['Banco']) && $venta['Banco'] !== 'N/A'): ?>
                <div class="info-row">
                    <span>Banco:</span>
                    <span><?php echo htmlspecialchars($venta['Banco']); ?></span>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- PRODUCTS -->
        <?php if (!$es_factura): ?>
            <!-- Ticket Products -->
            <div class="products">
                <?php foreach ($detalles as $item): ?>
                    <div class="product-item">
                        <div class="product-name"><?php echo htmlspecialchars($item['Producto_Vendido']); ?></div>
                        <div class="product-details">
                            <span><?php echo $item['Cantidad']; ?> x L <?php echo number_format($item['Precio'], 2); ?></span>
                            <span>L <?php echo number_format($item['Subtotal'], 2); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Invoice Products Table -->
            <div class="section-title">Detalle de Productos</div>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-right">Precio Unit.</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalles as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['Producto_Vendido']); ?></td>
                            <td><?php echo htmlspecialchars($item['Marca']); ?></td>
                            <td class="text-center"><?php echo $item['Cantidad']; ?></td>
                            <td class="text-right">L <?php echo number_format($item['Precio'], 2); ?></td>
                            <td class="text-right">L <?php echo number_format($item['Subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <!-- TOTALS -->
        <div class="totals">
            <?php
            $subtotal = isset($venta['Subtotal']) ? $venta['Subtotal'] : $venta['Total'];
            $impuesto = isset($venta['Impuesto']) ? $venta['Impuesto'] : 0;
            $total = $venta['Total'];
            ?>
            <div class="total-row">
                <span>Subtotal:</span>
                <span>L <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php if ($impuesto > 0): ?>
                <div class="total-row">
                    <span>Impuesto (<?php echo $empresa['impuesto']; ?>%):</span>
                    <span>L <?php echo number_format($impuesto, 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="total-row final">
                <span>TOTAL:</span>
                <span>L <?php echo number_format($total, 2); ?></span>
            </div>
            
            <?php if ($venta['MetodoPago'] === 'Mixto'): ?>
                <div style="border-top: 1px dashed #000; margin-top: 10px; padding-top: 10px;">
                    <div style="font-weight: bold; margin-bottom: 5px;">Desglose de Pago:</div>
                    <?php if ($venta['Efectivo'] > 0): ?>
                        <div class="total-row">
                            <span>Efectivo:</span>
                            <span>L <?php echo number_format($venta['Efectivo'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($venta['Tarjeta'] > 0): ?>
                        <div class="total-row">
                            <span>Tarjeta:</span>
                            <span>L <?php echo number_format($venta['Tarjeta'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($venta['Transferencia'] > 0): ?>
                        <div class="total-row">
                            <span>Transferencia<?php echo (!empty($venta['Banco']) && $venta['Banco'] !== 'N/A') ? ' (' . htmlspecialchars($venta['Banco']) . ')' : ''; ?>:</span>
                            <span>L <?php echo number_format($venta['Transferencia'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($venta['MetodoPago'] === 'Efectivo' && $venta['Efectivo'] > 0): ?>
                <div class="total-row">
                    <span>Efectivo Recibido:</span>
                    <span>L <?php echo number_format($venta['Efectivo'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Cambio:</span>
                    <span>L <?php echo number_format($venta['Cambio'], 2); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- FOOTER -->
        <div class="footer">
            <div>¡Gracias por su compra!</div>
            <?php if (!empty($empresa['email_empresa']) || !empty($empresa['telefono_empresa'])): ?>
                <div style="margin-top: 10px;">
                    <?php if (!empty($empresa['telefono_empresa'])): ?>
                        <div>Tel: <?php echo htmlspecialchars($empresa['telefono_empresa']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($empresa['email_empresa'])): ?>
                        <div><?php echo htmlspecialchars($empresa['email_empresa']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div style="margin-top: 10px; font-size: 9px;">
                Documento generado automáticamente - <?php echo date('d/m/Y H:i:s'); ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-print cuando la página carga
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        }
        
        // Cerrar ventana después de imprimir (opcional)
        window.onafterprint = function() {
            // Dar tiempo al usuario para ver el documento antes de cerrar
            // setTimeout(function() { window.close(); }, 1000);
        }
    </script>
</body>
</html>
<?php
$conexion->close();
?>
