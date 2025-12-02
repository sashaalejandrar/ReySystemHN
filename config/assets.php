<?php
/**
 * Configuración centralizada de assets
 * Permite cambiar entre CDN y local fácilmente
 */

// Modo de assets: 'local' o 'cdn'
define('ASSETS_MODE', 'local'); // Cambiar a 'cdn' si hay internet

// Base URL para assets locales
define('ASSETS_BASE_URL', '/ReySystemDemo/assets');

/**
 * Obtiene la URL de un asset según el modo configurado
 */
function getAssetUrl($name) {
    $urls = [
        'cdn' => [
            'tailwind' => 'https://cdn.tailwindcss.com?plugins=forms,container-queries',
            'alpine' => 'https://unpkg.com/alpinejs@3.12.0/dist/cdn.min.js',
            'chart' => 'https://cdn.jsdelivr.net/npm/chart.js',
            'tesseract' => 'https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js',
            'qrcode' => 'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js',
            'puter' => 'https://js.puter.com/v2/',
            'sweetalert2-js' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            'sweetalert2-css' => 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            'confetti' => 'https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js',
            'html5qrcode' => 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js',
            'font-inter' => 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap',
            'font-manrope' => 'https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap',
            'font-material' => 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined'
        ],
        'local' => [
            'tailwind' => 'https://cdn.tailwindcss.com?plugins=forms,container-queries', // Fallback a CDN
            'alpine' => ASSETS_BASE_URL . '/js/alpine.min.js',
            'chart' => ASSETS_BASE_URL . '/js/chart.min.js',
            'tesseract' => ASSETS_BASE_URL . '/js/tesseract.min.js',
            'qrcode' => ASSETS_BASE_URL . '/js/qrcode.min.js',
            'puter' => ASSETS_BASE_URL . '/js/puter.min.js',
            'sweetalert2-js' => ASSETS_BASE_URL . '/js/sweetalert2.min.js',
            'sweetalert2-css' => ASSETS_BASE_URL . '/css/sweetalert2.min.css',
            'confetti' => ASSETS_BASE_URL . '/js/confetti.min.js',
            'html5qrcode' => ASSETS_BASE_URL . '/js/html5-qrcode.min.js',
            'font-inter' => ASSETS_BASE_URL . '/fonts/inter/inter.css',
            'font-manrope' => ASSETS_BASE_URL . '/fonts/manrope/manrope.css',
            'font-material' => ASSETS_BASE_URL . '/fonts/material-icons/material-icons.css'
        ]
    ];
    
    $mode = ASSETS_MODE;
    return $urls[$mode][$name] ?? '';
}
?>
