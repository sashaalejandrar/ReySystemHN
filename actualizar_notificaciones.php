<?php
/**
 * Script para actualizar el sistema de notificaciones en todas las páginas
 * Reemplaza el código antiguo con el nuevo componente moderno
 */

$archivos_a_actualizar = [
    'arqueo_caja.php',
    'cierre_caja.php',
    'caja_al_dia.php',
    'inventario.php',
    'crear_usuarios.php',
    'configuracion.php',
    'consulta_precios.php',
    'perfil_usuario.php',
    'tickets_facturas.php',
    'proveedores.php',
    'consulta_edicion_precios.php'
];

$patron_buscar = '/<!-- SISTEMA DE NOTIFICACIONES -->.*?<\/div>\s*<\/div>/s';
$reemplazo = '<!-- SISTEMA DE NOTIFICACIONES REYSYSTEM -->
<?php include \'notificaciones_component.php\'; ?>

<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Avatar de usuario" style=\'background-image: url("<?php echo $Perfil;?>");\'></div>
</div>';

$actualizados = [];
$errores = [];

foreach ($archivos_a_actualizar as $archivo) {
    if (!file_exists($archivo)) {
        $errores[] = "$archivo - No existe";
        continue;
    }
    
    $contenido = file_get_contents($archivo);
    
    // Buscar el patrón
    if (preg_match($patron_buscar, $contenido)) {
        // Hacer backup
        $backup = $archivo . '.backup_' . date('Y-m-d_His');
        file_put_contents($backup, $contenido);
        
        // Reemplazar
        $nuevo_contenido = preg_replace($patron_buscar, $reemplazo, $contenido);
        
        if (file_put_contents($archivo, $nuevo_contenido)) {
            $actualizados[] = $archivo;
        } else {
            $errores[] = "$archivo - Error al escribir";
        }
    } else {
        $errores[] = "$archivo - Patrón no encontrado";
    }
}

// Mostrar resultados
echo "=== ACTUALIZACIÓN DE SISTEMA DE NOTIFICACIONES ===\n\n";

if (!empty($actualizados)) {
    echo "✅ Archivos actualizados (" . count($actualizados) . "):\n";
    foreach ($actualizados as $archivo) {
        echo "   - $archivo\n";
    }
    echo "\n";
}

if (!empty($errores)) {
    echo "❌ Errores (" . count($errores) . "):\n";
    foreach ($errores as $error) {
        echo "   - $error\n";
    }
    echo "\n";
}

echo "Proceso completado.\n";
?>
