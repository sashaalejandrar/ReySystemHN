<?php
/**
 * PWA Installation Script
 * Adds PWA support to all main pages
 */

echo "<h1>Instalador PWA - Rey System</h1>";
echo "<hr>";

$pages_to_update = [
    'index.php',
    'nueva_venta.php',
    'login.php',
    'inventario.php',
    'chat.php',
    'foro.php',
    'contratos.php',
    'cotizaciones.php',
    'mermas.php',
    'auditoria.php',
    'analisis_abc.php'
];

$pwa_include = '<?php include "pwa-head.php"; ?>';

$updated = 0;
$errors = 0;

foreach ($pages_to_update as $page) {
    if (!file_exists($page)) {
        echo "<p style='color: orange;'>⚠ $page no encontrado, saltando...</p>";
        continue;
    }

    $content = file_get_contents($page);
    
    // Check if already has PWA
    if (strpos($content, 'pwa-head.php') !== false) {
        echo "<p style='color: blue;'>ℹ $page ya tiene PWA instalado</p>";
        continue;
    }

    // Find </head> tag and insert before it
    if (strpos($content, '</head>') !== false) {
        $content = str_replace('</head>', $pwa_include . "\n</head>", $content);
        
        if (file_put_contents($page, $content)) {
            echo "<p style='color: green;'>✓ PWA agregado a $page</p>";
            $updated++;
        } else {
            echo "<p style='color: red;'>✗ Error escribiendo $page</p>";
            $errors++;
        }
    } else {
        echo "<p style='color: red;'>✗ No se encontró etiqueta &lt;/head&gt; en $page</p>";
        $errors++;
    }
}

echo "<hr>";
echo "<h2>Resumen:</h2>";
echo "<p><strong>Páginas actualizadas:</strong> $updated</p>";
echo "<p><strong>Errores:</strong> $errors</p>";

if ($updated > 0) {
    echo "<br><div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;'>";
    echo "<h3 style='color: #155724; margin-top: 0;'>✅ PWA Instalado Exitosamente!</h3>";
    echo "<p style='color: #155724;'>El sistema ahora es una Progressive Web App.</p>";
    echo "<p style='color: #155724;'><strong>Próximos pasos:</strong></p>";
    echo "<ol style='color: #155724;'>";
    echo "<li>Genera los iconos: <a href='generate-pwa-icons.html'>generate-pwa-icons.html</a></li>";
    echo "<li>Descarga los iconos y guárdalos en la carpeta <code>pwa-icons/</code></li>";
    echo "<li>Visita cualquier página del sistema</li>";
    echo "<li>Verás un botón 'Instalar App' en la esquina inferior derecha</li>";
    echo "<li>¡Instala la app en tu dispositivo!</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<br><p><a href='index.php' style='background: #1152d4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Ir al Sistema</a></p>";
?>
