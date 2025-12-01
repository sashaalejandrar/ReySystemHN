<?php
/**
 * Generador de códigos OTP para apertura y cierre de caja
 * Se activa cuando el monto es menor al esperado
 */

header('Content-Type: application/json');
require_once 'db_connect.php';

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

$tipo = $input['tipo'] ?? '';
$monto_esperado = floatval($input['monto_esperado'] ?? 0);
$monto_real = floatval($input['monto_real'] ?? 0);
$usuario = $input['usuario'] ?? '';
$email = $input['email'] ?? '';

// Validar campos requeridos
if (empty($tipo) || !in_array($tipo, ['apertura', 'cierre', 'arqueo'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Tipo inválido']);
    exit;
}

if (empty($usuario) || empty($email)) {
    echo json_encode(['success' => false, 'mensaje' => 'Usuario y email son requeridos']);
    exit;
}

try {
    // Generar código OTP con formato: [DÍA][BS][AP/CR][XXX]
    $dia = date('d'); // Día del mes (01-31)
    $bodega = 'BS'; // Bodega Siloe
    $tipo_codigo = ($tipo === 'apertura') ? 'AP' : (($tipo === 'cierre') ? 'CR' : 'AR');
    $random = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT); // 3 dígitos aleatorios
    
    $codigo = $dia . $bodega . $tipo_codigo . $random;
    
    // Fechas de generación y expiración (20 minutos)
    $fecha_generacion = date('Y-m-d H:i:s');
    $fecha_expiracion = date('Y-m-d H:i:s', strtotime('+20 minutes'));
    
    // Insertar en base de datos
    $sql = "INSERT INTO codigos_otp (codigo, tipo, fecha_generacion, fecha_expiracion, email_enviado, monto_esperado, monto_real, usuario, usado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssdds", $codigo, $tipo, $fecha_generacion, $fecha_expiracion, $email, $monto_esperado, $monto_real, $usuario);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar código OTP: " . $stmt->error);
    }
    
    // Enviar correo con el código OTP
    $email_enviado = enviarEmailOTP($codigo, $tipo, $monto_esperado, $monto_real, $usuario, $email);
    
    echo json_encode([
        'success' => true,
        'codigo' => $codigo,
        'mensaje' => $email_enviado ? 'Código OTP generado y enviado al correo' : 'Código generado pero el correo no pudo enviarse',
        'expira_en' => '20 minutos',
        'fecha_expiracion' => $fecha_expiracion
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al generar OTP: ' . $e->getMessage()
    ]);
}

/**
 * Enviar email con código OTP
 */
function enviarEmailOTP($codigo, $tipo, $monto_esperado, $monto_real, $usuario, $email_destino) {
    require 'vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'reysystemnotificaciones@gmail.com';
        $mail->Password   = 'sbzl symo xbpt atoq';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        
        // Remitente y destinatarios
        $mail->setFrom('reysystemnotificaciones@gmail.com', 'ReySystemAPP - Código OTP');
        $mail->addAddress('sashaalejandrar24@gmail.com', 'Sasha');
        $mail->addAddress('jesushernan.ordo@gmail.com', 'Jesús');
        
        // Determinar título y datos según tipo
        $titulo_operacion = '';
        $icono = '';
        switch($tipo) {
            case 'apertura':
                $titulo_operacion = 'Apertura de Caja';
                $icono = '🔓';
                break;
            case 'cierre':
                $titulo_operacion = 'Cierre de Caja';
                $icono = '🔒';
                break;
            case 'arqueo':
                $titulo_operacion = 'Arqueo de Caja';
                $icono = '🔍';
                break;
            default:
                $titulo_operacion = 'Operación de Caja';
                $icono = '💰';
        }
        
        $diferencia = $monto_real - $monto_esperado;
        
        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = '🔐 Código OTP - ' . $titulo_operacion;
        
        $mail->Body = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 20px; }
                .header { background-color: #1152d4; color: white; padding: 15px; border-radius: 8px 8px 0 0; text-align: center; }
                .alert-box { background-color: #fee; border: 2px solid #f00; padding: 20px; margin: 20px 0; border-radius: 8px; }
                .otp-box { background: #fff; border: 3px dashed #1152d4; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .otp-code { font-size: 36px; font-weight: bold; letter-spacing: 5px; color: #1152d4; margin: 10px 0; }
                .info-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .info-table th { background-color: #f2f2f2; text-align: left; padding: 10px; font-weight: bold; }
                .info-table td { padding: 10px; border-bottom: 1px solid #ddd; }
                .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #777; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . $icono . ' Código OTP Requerido</h1>
                    <p>' . $titulo_operacion . '</p>
                </div>
                
                <div class="alert-box">
                    <h2 style="color: #c00; margin-top: 0;">⚠️ AUTORIZACIÓN NECESARIA</h2>
                    <p style="font-size: 16px;"><strong>El monto registrado es MENOR al esperado.</strong></p>
                    <p>Se requiere código de autorización para continuar con la operación.</p>
                </div>
                
                <div class="otp-box">
                    <p style="font-size: 14px; color: #666; margin: 0;">Código de Autorización:</p>
                    <div class="otp-code">' . htmlspecialchars($codigo) . '</div>
                    <p style="font-size: 12px; color: #f00; font-weight: bold;">⏰ Expira en 20 minutos</p>
                </div>
                
                <h3>Detalles de la Operación</h3>
                <table class="info-table">
                    <tr>
                        <th>Usuario</th>
                        <td>' . htmlspecialchars($usuario) . '</td>
                    </tr>
                    <tr>
                        <th>Tipo</th>
                        <td>' . ucfirst($tipo) . '</td>
                    </tr>
                    <tr>
                        <th>Monto Esperado</th>
                        <td>L.' . number_format($monto_esperado, 2) . '</td>
                    </tr>
                    <tr>
                        <th>Monto Real</th>
                        <td>L.' . number_format($monto_real, 2) . '</td>
                    </tr>
                    <tr style="background-color: #fee;">
                        <th>Faltante</th>
                        <td style="color: #c00; font-weight: bold;">L.' . number_format(abs($diferencia), 2) . '</td>
                    </tr>
                    <tr>
                        <th>Generado</th>
                        <td>' . date('d/m/Y H:i:s') . '</td>
                    </tr>
                </table>
                
                <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0;"><strong>⚠️ Instrucciones:</strong></p>
                    <ol style="margin: 10px 0;">
                        <li>Ingresa este código en la pantalla de ' . strtolower($titulo_operacion) . '</li>
                        <li>Selecciona el motivo del faltante</li>
                        <li>El código expirará en 20 minutos</li>
                        <li>Solo puede usarse una vez</li>
                    </ol>
                </div>
                
                <div class="footer">
                    <p>Este es un mensaje automático generado por ReySystemAPP.</p>
                    <p>Por favor, no responda a este correo.</p>
                </div>
            </div>
        </body>
        </html>';
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Error al enviar email OTP: " . $e->getMessage());
        return false;
    }
}
?>
