<?php
/**
 * Script para configurar permisos necesarios del sistema
 */

echo "=== Configuración de Permisos del Sistema ===\n\n";

$files_to_check = [
    'version.json' => 0666,
    'releases' => 0777,
    'backups' => 0777,
    'uploads' => 0777,
    'logs' => 0777,
    'temp_updates' => 0777
];

$fixed = 0;
$errors = 0;

foreach ($files_to_check as $path => $required_perms) {
    echo "Verificando: $path\n";
    
    if (!file_exists($path)) {
        // Si es un directorio y no existe, crearlo
        if (in_array($path, ['releases', 'backups', 'uploads', 'logs', 'temp_updates'])) {
            if (mkdir($path, $required_perms, true)) {
                echo "  ✅ Directorio creado con permisos " . decoct($required_perms) . "\n";
                $fixed++;
            } else {
                echo "  ❌ No se pudo crear el directorio\n";
                $errors++;
            }
        } else {
            echo "  ⚠️  Archivo no existe\n";
        }
        continue;
    }
    
    // Verificar permisos actuales
    $current_perms = fileperms($path);
    $is_writable = is_writable($path);
    
    if (!$is_writable) {
        echo "  ⚠️  No tiene permisos de escritura\n";
        
        // Intentar cambiar permisos
        if (@chmod($path, $required_perms)) {
            clearstatcache(true, $path);
            echo "  ✅ Permisos actualizados a " . decoct($required_perms) . "\n";
            $fixed++;
        } else {
            echo "  ❌ No se pudieron cambiar los permisos\n";
            echo "     Ejecuta manualmente: chmod " . decoct($required_perms) . " $path\n";
            $errors++;
        }
    } else {
        echo "  ✅ Permisos correctos\n";
    }
}

echo "\n=== Resumen ===\n";
echo "Archivos/directorios corregidos: $fixed\n";
echo "Errores: $errors\n";

if ($errors > 0) {
    echo "\n⚠️  Algunos permisos no se pudieron cambiar automáticamente.\n";
    echo "Ejecuta estos comandos manualmente:\n\n";
    echo "chmod 666 version.json\n";
    echo "chmod 777 releases backups uploads logs temp_updates\n";
    echo "\nO ejecuta este script con sudo:\n";
    echo "sudo php setup_permissions.php\n";
} else {
    echo "\n✅ Todos los permisos están configurados correctamente\n";
}

echo "\n=== Verificación Final ===\n";

// Test de escritura en version.json
if (is_writable('version.json')) {
    echo "✅ version.json es escribible\n";
} else {
    echo "❌ version.json NO es escribible\n";
}

// Test de escritura en releases
if (is_dir('releases') && is_writable('releases')) {
    echo "✅ releases/ es escribible\n";
    
    // Intentar crear un archivo de prueba
    $test_file = 'releases/.test_write';
    if (@file_put_contents($test_file, 'test')) {
        echo "✅ Se puede escribir en releases/\n";
        @unlink($test_file);
    } else {
        echo "❌ No se puede escribir en releases/\n";
    }
} else {
    echo "❌ releases/ NO es escribible\n";
}

echo "\n=== Fin ===\n";
?>
