<?php
/**
 * Helper para 2FA usando la librería oficial de Google Authenticator
 * Librería: sonata-project/google-authenticator
 */

require_once __DIR__ . '/vendor/autoload.php';

use Sonata\GoogleAuthenticator\GoogleAuthenticator;
use Sonata\GoogleAuthenticator\GoogleQrUrl;

/**
 * Genera un secret aleatorio para 2FA
 */
function generate2FASecret() {
    $g = new GoogleAuthenticator();
    return $g->generateSecret();
}

/**
 * Obtiene la URL del código QR para Google Authenticator
 */
function get2FAQRCodeURL($secret, $username, $issuer = 'ReySystem') {
    // Generar la URL otpauth directamente (sin usar GoogleQrUrl que ya genera una URL de imagen)
    $otpauthUrl = sprintf(
        'otpauth://totp/%s:%s?secret=%s&issuer=%s',
        urlencode($issuer),
        urlencode($username),
        $secret,
        urlencode($issuer)
    );
    
    // Usar QR Server API para generar la imagen del QR
    return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($otpauthUrl);
}

/**
 * Verifica un código TOTP
 */
function verify2FACode($secret, $code) {
    $g = new GoogleAuthenticator();
    
    // La librería maneja automáticamente la ventana de tiempo
    return $g->checkCode($secret, $code);
}

/**
 * Genera códigos de respaldo
 */
function generarCodigosRespaldo($cantidad = 10) {
    $codigos = [];
    for ($i = 0; $i < $cantidad; $i++) {
        $codigos[] = strtoupper(bin2hex(random_bytes(4)));
    }
    return $codigos;
}
?>
