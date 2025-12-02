<?php
// Test rápido para verificar el bind_param
$file = file_get_contents('/opt/lampp/htdocs/ReySystemDemo/api/procesar_creacion_lote.php');

// Buscar la línea del bind_param
if (preg_match('/bind_param\("([^"]+)"/', $file, $matches)) {
    $typeString = $matches[1];
    $length = strlen($typeString);
    
    echo "✅ Tipo string encontrado: $typeString\n";
    echo "📏 Longitud: $length caracteres\n";
    
    if ($length === 24) {
        echo "✅ CORRECTO: 24 caracteres como debe ser\n";
    } else {
        echo "❌ ERROR: Debería tener 24 caracteres\n";
    }
} else {
    echo "❌ No se encontró bind_param\n";
}

// Limpiar cache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "\n✅ OPcache limpiado\n";
}
?>
