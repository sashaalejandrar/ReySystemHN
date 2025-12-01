<?php
// Limpiar caché de opcodes de PHP
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ Opcode cache cleared\n";
} else {
    echo "⚠️ OPcache not enabled\n";
}

// Limpiar caché de archivos
clearstatcache(true);
echo "✅ File stat cache cleared\n";

echo "\n🔄 Please refresh your browser and try again.\n";
?>
