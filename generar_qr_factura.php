<?php
/**
 * Genera imagen QR para subir facturas desde móvil
 * Uso: generar_qr_factura.php
 */

require_once 'vendor/autoload.php';

// URL completa de la página de subir factura
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$url = $protocol . "://" . $host . "/ReySystemDemo/subir_factura_movil.php";

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
