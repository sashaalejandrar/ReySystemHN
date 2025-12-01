<?php
session_start();
date_default_timezone_set('America/Tegucigalpa');
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Incluye tus funciones si ahí está VerificarSiUsuarioYaInicioSesion()
 
// Cargar el autoloader de Composer para PHPMailer
require 'vendor/autoload.php';
// Verificar sesión
if (!function_exists('VerificarSiUsuarioYaInicioSesion')) {
    // Fallback si no existe la función
    if (empty($_SESSION['usuario'])) {
        header("Location: login.php");
        exit();
    }
} else {
    VerificarSiUsuarioYaInicioSesion();
}

// Conexión DB
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    http_response_code(500);
    die("Error de conexión: " . $conexion->connect_error);
}
 $conexion->set_charset("utf8mb4");

// Obtener info del usuario logueado
 $usuarioLog = $_SESSION['usuario'] ?? null;
 $Nombre_Completo = '';
 $Perfil = '';
 $Rol = '';
if ($usuarioLog) {
    $stmtUser = $conexion->prepare("SELECT Rol, Usuario, Nombre, Apellido, Email, Celular, Perfil FROM usuarios WHERE Usuario = ?");
    $stmtUser->bind_param("s", $usuarioLog);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    if ($row = $resUser->fetch_assoc()) {
        $Rol = $row['Rol'];
        $Nombre_Completo = trim(($row['Nombre'] ?? '') . ' ' . ($row['Apellido'] ?? ''));
        $Perfil = $row['Perfil'] ?? '';
    } else {
        header("Location: logout.php");
        exit();
    }
    $stmtUser->close();
} else {
    header("Location: login.php");
    exit();
}

// Procesar el formulario si se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_pago'])) {
    $idDeuda = $_POST['deuda'] ?? '';
    $monto = $_POST['amount'] ?? 0;
    $fecha = $_POST['date'] ?? date('Y-m-d');
    $notas = $_POST['notes'] ?? '';

    if (!empty($idDeuda) && $monto > 0) {
        // Iniciar transacción
        $conexion->begin_transaction();

        try {
            // --- PASO 1: Obtener información de la deuda para registrar la venta ---
            $stmtDeudaInfo = $conexion->prepare("SELECT nombreCliente, monto FROM deudas WHERE idDeuda = ?");
            $stmtDeudaInfo->bind_param("s", $idDeuda);
            $stmtDeudaInfo->execute();
            $deudaInfo = $stmtDeudaInfo->get_result()->fetch_assoc();
            $stmtDeudaInfo->close();

            if (!$deudaInfo) {
                throw new Exception("No se encontró la deuda especificada.");
            }
            $nombreCliente = $deudaInfo['nombreCliente'];
            $montoTotalDeuda = $deudaInfo['monto'];

            // --- PASO 2: Obtener la identidad del cliente desde la tabla 'clientes' ---
            // Asumimos que la tabla 'clientes' tiene una columna 'Nombre' y 'Identidad'.
            $identidadCliente = 'N/A'; // Valor por defecto si no se encuentra
            $stmtCliente = $conexion->prepare("SELECT Nombre FROM clientes WHERE Nombre = ?");
            $stmtCliente->bind_param("s", $nombreCliente);
            $stmtCliente->execute();
            $resultCliente = $stmtCliente->get_result();
            if ($rowCliente = $resultCliente->fetch_assoc()) {
                $nombreClienteD = $rowCliente['Nombre'];
            }
            $stmtCliente->close();

            // --- PASO 3: Obtener los detalles de la deuda y registrar cada producto como venta ---
$stmtDetalles = $conexion->prepare("SELECT productoVendido, cantidad, precio FROM deudas_detalle WHERE idDeuda = ?");
$stmtDetalles->bind_param("s", $idDeuda);
$stmtDetalles->execute();
$resDetalles = $stmtDetalles->get_result();

$stmtVenta = $conexion->prepare("INSERT INTO ventas (
    Cliente, Identidad, Celular, Direccion,
    Producto_Vendido, Marca, Cantidad, Precio, Total,
    Fecha_Venta, Transferencia, Efectivo, Tarjeta, Cambio,
    Banco, Factura_Id, MetodoPago, Vendedor
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmtVenta) {
    throw new Exception("Error al preparar el INSERT de ventas: " . $conexion->error);
}

// Valores por defecto
$identidadCliente = 'N/A';
$celularCliente = 'N/A';
$direccionCliente = 'N/A';
$transferencia = 0.00;
$tarjeta = 0.00;
$banco = 'N/A';
$factura_id = 'DEUDA-' . $idDeuda;
$metodoPago = 'Deuda Pagada';
$cambio = $montoTotalDeuda - $monto; // ✅ Diferencia entre deuda y pago
$efectivo = $monto; // ✅ Lo que realmente se pagó
$Vendedor = $usuarioLog;
$itemsEmail = [];
$subtotalTotal = 0;
$idVentaInsertada = null;

while ($detalle = $resDetalles->fetch_assoc()) {
    $producto = $detalle['productoVendido'];
    $cantidad = (float)$detalle['cantidad'];
    $precio = (float)$detalle['precio'];
    $subtotal = $cantidad * $precio;
    $subtotalTotal += $subtotal;

    // Buscar la marca desde la tabla stock
    $marcaProducto = 'N/A';
    $stmtMarca = $conexion->prepare("SELECT marca FROM stock WHERE nombre_producto = ? OR descripcion LIKE CONCAT('%', ?, '%') LIMIT 1");
    $stmtMarca->bind_param("ss", $producto, $producto);
    $stmtMarca->execute();
    $resMarca = $stmtMarca->get_result();
    if ($rowMarca = $resMarca->fetch_assoc()) {
        $marcaProducto = $rowMarca['marca'];
    }
    $stmtMarca->close();

    $stmtVenta->bind_param("ssssssdddsddddssss",
        $nombreCliente,        // Cliente
        $identidadCliente,     // Identidad
        $celularCliente,       // Celular
        $direccionCliente,     // Direccion
        $producto,             // Producto_Vendido
        $marcaProducto,        // Marca
        $cantidad,             // Cantidad
        $precio,               // Precio
        $montoTotalDeuda,      // Total
        $fecha,                // Fecha_Venta
        $transferencia,        // Transferencia
        $efectivo,             // Efectivo
        $tarjeta,              // Tarjeta
        $cambio,               // Cambio
        $banco,                // Banco
        $factura_id,           // Factura_Id
        $metodoPago,            // MetodoPago
        $Vendedor
    );

    if (!$stmtVenta->execute()) {
        throw new Exception("Error al registrar la venta: " . $stmtVenta->error);
    }

    if ($idVentaInsertada === null) {
        $idVentaInsertada = $conexion->insert_id;
    }

    $itemsEmail[] = [
        'nombre' => $producto,
        'marca' => $marcaProducto,
        'cantidad' => $cantidad,
        'precio' => $precio
    ];
}
$dataEmail = [
    'vendedor'   => $Nombre_Completo,
    'cliente'    => $nombreCliente,
    'metodoPago' => $metodoPago,
    'efectivo'   => $efectivo,
    'total'      => $montoTotalDeuda
];

enviarNotificacionVentaPorDeuda($factura_id, $dataEmail, $itemsEmail);


$stmtDetalles->close();
$stmtVenta->close();

            // --- PASO 4: Actualizar estado de la deuda a 'pagada' ---
            $stmtUpdate = $conexion->prepare("UPDATE deudas SET estado = 'pagada', fechaPago = ?, notasPago = ? WHERE idDeuda = ?");
            $stmtUpdate->bind_param("sss", $fecha, $notas, $idDeuda);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            // Confirmar transacción
            $conexion->commit();

            // Redirigir con mensaje de éxito
            $_SESSION['mensaje_exito'] = "¡Pago registrado y venta generada exitosamente!";
            header("Location: lista_deudas.php");
            exit();

        } catch (Exception $e) {
            // Revertir transacción en caso de error
            $conexion->rollback();
            $error_message = "Error al procesar el pago: " . $e->getMessage();
        }
    } else {
        $error_message = "Por favor, complete todos los campos obligatorios.";
    }
}

// Obtener el ID de deuda desde la URL si existe
 $idDeudaSeleccionado = $_GET['id'] ?? '';

// Obtener todas las deudas pendientes
 $deudasPendientes = [];
 $stmtDeudas = $conexion->prepare("SELECT idDeuda, nombreCliente, monto, fechaRegistro 
                                  FROM deudas 
                                  WHERE LOWER(estado) = 'pendiente' 
                                  ORDER BY fechaRegistro DESC");
 $stmtDeudas->execute();
 $resDeudas = $stmtDeudas->get_result();
while ($row = $resDeudas->fetch_assoc()) {
    $deudasPendientes[] = $row;
}
 $stmtDeudas->close();


// Permisos (si los usas)
 $rol_usuario = strtolower($Rol);

 function enviarNotificacionVentaPorDeuda($factura_id, $data, $items) {
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

        $mail->setFrom('reysystemnotificaciones@gmail.com', 'ReySystemAPP - Notificación de Venta');
        $mail->addAddress('sashaalejandrar24@gmail.com', 'Sasha');
        $mail->addAddress('jesushernan.ordo@gmail.com', 'Jesús');
        $mail->addReplyTo('no-reply@tiendasrey.com', 'No Responder');

        $mail->isHTML(true);
        $mail->Subject = "✅ Venta Registrada por Deuda Pagada - Factura: {$factura_id}";

        $productos_html = '';
        foreach ($items as $item) {
            $subtotal_item = $item['precio'] * $item['cantidad'];
            $productos_html .= '
            <tr>
                <td>' . htmlspecialchars($item['nombre']) . '</td>
                <td>' . htmlspecialchars($item['marca']) . '</td>
                <td>' . htmlspecialchars($item['cantidad']) . '</td>
                <td>L. ' . number_format($item['precio'], 2) . '</td>
                <td>L. ' . number_format($subtotal_item, 2) . '</td>
            </tr>';
        }

        $cambio = $data['total'] - $data['efectivo'];

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
                    <h1>✅ Venta Registrada</h1>
                    <p>Factura: <strong>' . htmlspecialchars($factura_id) . '</strong></p>
                    <p>Origen: <strong>Deuda Pagada</strong></p>
                </div>
                <div class="content">
                    <h2>Resumen de la Venta</h2>
                    <table class="info-table">
                        <tr><th>Fecha y Hora</th><td>' . date('Y-m-d H:i:s') . '</td></tr>
                        <tr><th>Vendedor</th><td>' . htmlspecialchars($data['vendedor']) . '</td></tr>
                        <tr><th>Cliente</th><td>' . htmlspecialchars($data['cliente']) . '</td></tr>
                        <tr><th>Método de Pago</th><td>' . htmlspecialchars($data['metodoPago']) . '</td></tr>
                        <tr><th>Monto Pagado</th><td>L. ' . number_format($data['efectivo'], 2) . '</td></tr>
                        <tr><th>Total Deuda</th><td>L. ' . number_format($data['total'], 2) . '</td></tr>
                        <tr><th>Cambio</th><td>L. ' . number_format($cambio, 2) . '</td></tr>
                    </table>

                    <h3 style="margin-top: 30px;">Productos Vendidos</h3>
                    <table class="info-table">
                        <tr>
                            <th>Producto</th>
                            <th>Marca</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                        ' . $productos_html . '
                    </table>
                </div>
                <div class="footer">
                    <p>Este es un mensaje automático generado por ReySystemAPP. Por favor, no responda a este correo.</p>
                </div>
            </div>
        </body>
        </html>';

        $mail->send();

    } catch (Exception $e) {
        throw new Exception("No se pudo enviar la notificación de venta. Error: {$mail->ErrorInfo}");
    }
}

?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Registrar Nuevo Pago</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#137fec",
            "background-light": "#f6f7f8",
            "background-dark": "#101922",
          },
          fontFamily: {
            "display": ["Inter", "sans-serif"]
          },
          borderRadius: {
            "DEFAULT": "0.25rem",
            "lg": "0.5rem",
            "xl": "0.75rem",
            "full": "9999px"
          },
        },
      },
    }
  </script>
<style>
    .material-symbols-outlined {
      font-variation-settings:
      'FILL' 0,
      'wght' 400,
      'GRAD' 0,
      'opsz' 24
    }
    .material-symbols-outlined.fill {
      font-variation-settings: 'FILL' 1;
    }
  </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="flex min-h-screen w-full">
  <!-- SideNavBar -->
 <?php include 'menu_lateral.php'; ?>

  <!-- Main Content -->
  <main class="flex-1 p-8">
    <div class="mx-auto max-w-4xl">
      <!-- PageHeading -->
      <div class="flex flex-wrap justify-between gap-3 mb-8">
        <div class="flex flex-col gap-2">
          <h1 class="text-white text-4xl font-black leading-tight tracking-[-0.033em]">Registrar Nuevo Pago</h1>
          <p class="text-[#92adc9] text-base font-normal leading-normal">Complete el formulario para registrar un pago a una deuda existente.</p>
        </div>
      </div>

      <!-- Mensaje de error si existe -->
      <?php if (isset($error_message)): ?>
      <div class="mt-8 flex items-center gap-4 rounded-lg bg-red-500/10 p-4 text-red-400">
        <span class="material-symbols-outlined">error</span>
        <p class="text-sm font-medium"><?php echo $error_message; ?></p>
      </div>
      <?php endif; ?>

      <!-- Form Card -->
      <div class="rounded-xl border border-[#324d67] bg-[#192633] p-8">
        <form method="POST" class="space-y-6">
          <input type="hidden" name="procesar_pago" value="1">
          
          <!-- Selector de Deuda -->
          <div class="flex flex-col">
            <label class="text-white text-base font-medium leading-normal pb-2" for="debt-select">Seleccionar Deuda</label>
            <select class="form-select w-full rounded-lg text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-[#324d67] bg-[#111a22] h-14 placeholder:text-[#92adc9] px-[15px] text-base font-normal leading-normal" id="debt-select" name="deuda" required>
              <option disabled value="">Seleccione una deuda pendiente</option>
              <?php foreach ($deudasPendientes as $deuda): ?>
              <option value="<?php echo $deuda['idDeuda']; ?>" <?php echo ($idDeudaSeleccionado == $deuda['idDeuda']) ? 'selected' : ''; ?>>
                ID Deuda: <?php echo $deuda['idDeuda']; ?> - Cliente: <?php echo htmlspecialchars($deuda['nombreCliente']); ?> - Monto: L <?php echo number_format((float)$deuda['monto'], 2); ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Monto del Pago -->
            <div class="flex flex-col">
              <label class="text-white text-base font-medium leading-normal pb-2" for="payment-amount">Monto del Pago</label>
              <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-[#92adc9]">L</span>
                <input class="form-input w-full rounded-lg text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-[#324d67] bg-[#111a22] h-14 placeholder:text-[#92adc9] pl-8 pr-[15px] text-base font-normal leading-normal" id="payment-amount" name="amount" placeholder="0.00" type="number" step="0.01" min="0.01" required/>
              </div>
            </div>

            <!-- Fecha de Pago -->
            <div class="flex flex-col">
              <label class="text-white text-base font-medium leading-normal pb-2" for="payment-date">Fecha de Pago</label>
              <div class="relative flex w-full items-stretch rounded-lg">
                <input class="form-input flex w-full min-w-0 flex-1 resize-none rounded-l-lg text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-[#324d67] bg-[#111a22] h-14 placeholder:text-[#92adc9] p-[15px] border-r-0 text-base font-normal leading-normal" id="payment-date" name="date" type="date" value="<?php echo date('Y-m-d'); ?>" required/>
                <div class="pointer-events-none text-[#92adc9] flex border border-[#324d67] bg-[#111a22] items-center justify-center pr-[15px] rounded-r-lg border-l-0">
                  <span class="material-symbols-outlined">calendar_month</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Notas -->
          <div class="flex flex-col">
            <label class="text-white text-base font-medium leading-normal pb-2" for="payment-notes">Notas (Opcional)</label>
            <textarea class="form-textarea w-full rounded-lg text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-[#324d67] bg-[#111a22] placeholder:text-[#92adc9] p-[15px] text-base font-normal leading-normal" id="payment-notes" name="notes" placeholder="Añada comentarios o referencias sobre el pago..." rows="4"></textarea>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center justify-end gap-4 pt-4">
            <a href="lista_deudas.php" class="rounded-lg px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#233648]">Cancelar</a>
            <button type="submit" class="rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary/80">Guardar Pago</button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>
</body>
</html>