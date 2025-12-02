<?php
/**
 * Genera imagen QR para un token
 * Uso: generar_qr_imagen.php?token=ABC123
 */

require_once 'vendor/autoload.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    // Generar imagen de error
    $img = imagecreate(200, 200);
    $bg = imagecolorallocate($img, 255, 255, 255);
    $text_color = imagecolorallocate($img, 255, 0, 0);
    imagestring($img, 5, 50, 90, 'Token requerido', $text_color);
    header('Content-Type: image/png');
    imagepng($img);
    imagedestroy($img);
    exit;
}

// URL completa del registro
$url = "http://localhost/ReySystemDemo/registro_cliente.php?token=" . $token;

// Usar la clase QRcode de TCPDF
require_once('vendor/tecnick.com/tcpdf/include/barcodes/qrcode.php');

$qrcode = new QRcode($url, 'H');
$barcode_array = $qrcode->getBarcodeArray();

$size = 8; // Tamaño de cada módulo en píxeles
$margin = 4; // Margen en módulos

// Obtener dimensiones
$width = $barcode_array['num_cols'];
$height = $barcode_array['num_rows'];
$imgSize = ($width + $margin * 2) * $size;

// Crear imagen
$img = imagecreate($imgSize, $imgSize);

// Colores
$bg_color = imagecolorallocate($img, 255, 255, 255); // Blanco
$fg_color = imagecolorallocate($img, 17, 82, 212);   // Azul primary

// Dibujar el QR
for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        if ($barcode_array['bcode'][$y][$x] == 1) {
            imagefilledrectangle(
                $img,
                ($x + $margin) * $size,
                ($y + $margin) * $size,
                ($x + $margin + 1) * $size - 1,
                ($y + $margin + 1) * $size - 1,
                $fg_color
            );
        }
    }
}

// Salida
header('Content-Type: image/png');
header('Cache-Control: no-cache, must-revalidate');
imagepng($img);
imagedestroy($img);
?>
