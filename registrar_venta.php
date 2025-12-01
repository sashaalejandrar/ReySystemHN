<?php
session_start();
include 'funciones.php';

// Importar las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

VerificarSiUsuarioYaInicioSesion();
// Conexión a la base de datos
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Verificar conexión
if ($conexion->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// Obtener los datos del formulario
 $data = json_decode(file_get_contents('php://input'), true);

// Validar campos requeridos
 $campos = [
    'Cliente', 'Identidad', 'Celular', 'Direccion', 
    'Producto_Vendido', 'Marca', 'Cantidad', 'Precios', 'Total',
    'Transferencia', 'Efectivo', 'Tarjeta', 'Cambio', 'Banco', 'Tarjeta_Id', 'MetodoPago'
];

foreach ($campos as $campo) {
    if (!isset($data[$campo])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Falta el campo: $campo"]);
        exit;
    }
}

// Escapar y validar datos
 $cliente       = $conexion->real_escape_string($data['Cliente']);
 $identidad     = $conexion->real_escape_string($data['Identidad']);
 $celular       = $conexion->real_escape_string($data['Celular']);
 $direccion     = $conexion->real_escape_string($data['Direccion']);
 $producto      = $conexion->real_escape_string($data['Producto_Vendido']);
 $marca         = $conexion->real_escape_string($data['Marca']);
 $cantidad      = intval($data['Cantidad']);
 $precios       = floatval($data['Precios']);
 $total         = floatval($data['Total']);
 $transferencia = floatval($data['Transferencia']);
 $efectivo      = floatval($data['Efectivo']);
 $tarjeta       = floatval($data['Tarjeta']);
 $cambio        = floatval($data['Cambio']);
 $banco         = $conexion->real_escape_string($data['Banco']);
 $tarjeta_id    = $conexion->real_escape_string($data['Tarjeta_Id']);
 $metodo_pago   = $conexion->real_escape_string($data['MetodoPago']);
 $vendedor      = $conexion->real_escape_string($_SESSION['usuario']);

// Generar fecha y hora exacta
 $fecha_venta = date('Y-m-d H:i:s');

// Insertar en la base de datos
 $sql = "INSERT INTO ventas (
    Cliente, Identidad, Celular, Direccion,
    Producto_Vendido, Marca, Cantidad, Precios, Total,
    Transferencia, Efectivo, Tarjeta, Cambio, Banco, Tarjeta_Id, MetodoPago, Vendedor, Fecha_Venta
) VALUES (
    '$cliente', '$identidad', '$celular', '$direccion',
    '$producto', '$marca', $cantidad, $precios, '$total',
    '$transferencia', '$efectivo', '$tarjeta', '$cambio', '$banco', '$tarjeta_id', '$metodo_pago', '$vendedor', '$fecha_venta'
)";

if ($conexion->query($sql)) {
    // Si la inserción fue exitosa, enviar correo de notificación
    $correoEnviado = enviarNotificacionVenta($data);
    
    if ($correoEnviado) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Venta registrada y notificación enviada.']);
    } else {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Venta registrada, pero hubo un error al enviar la notificación.']);
    }
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error al registrar la venta en la base de datos.']);
}

 $conexion->close();

// Función para enviar el correo de notificación
function enviarNotificacionVenta($data) {
    try {
        // Cargar el autoloader de Composer
        require_once 'vendor/autoload.php';

        // Crear una instancia de PHPMailer
        $mail = new PHPMailer(true);

        // --- CONFIGURACIÓN SMTP (GMAIL) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'reysystemnotificaciones@gmail.com';
        $mail->Password   = 'sbzl symo xbpt atoq'; // Contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Para SSL
        $mail->Port       = 465; // Puerto para SSL
        $mail->CharSet    = 'UTF-8';

        // --- CONFIGURACIÓN DEL CORREO ---
        $mail->setFrom('reysystemnotificaciones@gmail.com', 'ReySystemAPP - Notificación de Venta');
        $mail->addAddress('sashaalejandrar24@gmail.com', 'Sasha Alejadrrar');
        $mail->addAddress('jesushernan.ordo@gmail.com', 'Jesús Hernán Ordó');
        $mail->addReplyTo('no-reply@tiendasrey.com', 'Información de Contacto');

        // --- CONTENIDO DEL CORREO ---
        $mail->isHTML(true);
        $mail->Subject = '✅ Nueva Venta Realizada en Rey System APP';

        // Construir el cuerpo del correo en HTML
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
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🎉 Nueva Venta Registrada</h1>
                    <p>Una nueva venta ha sido procesada exitosamente en el sistema.</p>
                </div>
                <div class="content">
                    <h2>Detalles de la Venta</h2>
                    <table class="info-table">
                        <tr>
                            <th><strong>Cliente</strong></th>
                            <td>' . htmlspecialchars($data['Cliente']) . '</td>
                        </tr>
                        <tr>
                            <th><strong>Identidad</strong></th>
                            <td>' . htmlspecialchars($data['Identidad']) . '</td>
                        </tr>
                        <tr>
                            <th><strong>Celular</strong></th>
                            <td>' . htmlspecialchars($data['Celular']) . '</td>
                        </tr>
                        <tr>
                            <th><strong>Dirección</strong></th>
                            <td>' . htmlspecialchars($data['Direccion']) . '</td>
                        </tr>
                    </table>

                    <h3 style="margin-top: 30px;">Productos Vendidos</h3>
                    <table class="info-table">
                        <tr>
                            <th><strong>Producto</strong></th>
                            <th><strong>Marca</strong></th>
                            <th><strong>Cantidad</strong></th>
                            <th><strong>Precio</strong></th>
                            <th><strong>Total</strong></th>
                        </tr>
                        <tr>
                            <td>' . htmlspecialchars($data['Producto_Vendido']) . '</td>
                            <td>' . htmlspecialchars($data['Marca']) . '</td>
                            <td>' . htmlspecialchars($data['Cantidad']) . '</td>
                            <td>L. ' . number_format($data['Precios'], 2) . '</td>
                            <td>L. ' . number_format($data['Total'], 2) . '</td>
                        </tr>
                    </table>

                    <h3 style="margin-top: 30px;">Resumen del Pago</h3>
                    <table class="info-table">
                        <tr>
                            <th><strong>Total de la Venta</strong></th>
                            <td>L. ' . number_format($data['Total'], 2) . '</td>
                        </tr>
                        <tr>
                            <th><strong>Método de Pago</strong></th>
                            <td>' . htmlspecialchars($data['MetodoPago']) . '</td>
                        </tr>
                        <tr>
                            <th><strong>Fecha de la Venta</strong></th>
                            <td>' . htmlspecialchars($data['Fecha_Venta']) . '</td>
                        </tr>
                        <tr>
                            <th><strong>Vendedor</strong></th>
                            <td>' . htmlspecialchars($data['Vendedor']) . '</td>
                        </tr>
                    </table>
                </div>
                <div class="footer">
                    <p>Este es un mensaje automático generado por ReySystemAPP. Por favor, no responda a este correo.</p>
                </div>
            </div>
        </body>
        </html>';

        // Enviar el correo
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Registrar error para depuración
        error_log("Error al enviar correo: " . $e->getMessage());
        return false;
    }
}
?>