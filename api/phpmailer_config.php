<?php
// Archivo de configuración para PHPMailer
// Incluye este archivo antes de usar PHPMailer

// Cargar PHPMailer desde Composer
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Crea y configura una instancia de PHPMailer
 * @return PHPMailer
 */
function getMailerInstance() {
    $mail = new PHPMailer(true);
    
    // Configuración del servidor SMTP (Gmail)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'reysystemnotificaciones@gmail.com'; // Tu correo
    $mail->Password   = 'sbzl symo xbpt atoq'; // Contraseña de aplicación de Gmail
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    
    // Remitente por defecto
    $mail->setFrom('reysystemnotificaciones@gmail.com', 'Rey System');
    
    return $mail;
}
?>
