<?php
/**
 * Script to automatically update all database connections
 * to use the centralized db_connect.php file
 */

// List of files to update
$files_to_update = [
    'marcar_leido.php',
    'procesar_egreso.php',
    'reporte_ventas.php',
    'obtener_estado_usuario.php',
    'obtener_producto.php',
    'pagar_deudas.php',
    'obtener_detalle_deuda.php',
    'verificar_estado_caja.php',
    'enviar_mensaje.php',
    'buscar_sugerencias.php',
    'obtener_mensajes_desde.php',
    'guardar_cierre_caja.php',
    'agregar_stock_simple.php',
    'buscar_sugerencias_api.php',
    'verificar_producto.php',
    'consulta_precios.php',
    'nueva_venta.php',
    'actualizar_producto.php',
    'cierre_caja.php',
    'obtener_clientes.php',
    'registrar_venta.php',
    'procesar_venta.php',
    'inventario.php',
    'configuracion.php',
    'actualizar_precio.php',
    'inventariotest.php',
    'crear_proveedor.php',
    'compra_desde_ventas.php',
    'agregar_reaccion.php',
    'consulta_edicion_precios.php',
    'chequeo_stock.php',
    'creacion_de_producto.php',
    'chat_interno.php',
    'obtener_mensajes.php',
    'get_foto_producto.php',
    'arqueo_caja.php',
    'buscar_producto_api.php',
    'marcar_notificacion_leida.php',
    'guardar_publicacion.php',
    'obtener_publicaciones.php',
    'usuario.php',
    'subir_archivo.php',
    'obtener_ventas_dia.php',
    'clientes.php',
    'lista_deudas.php',
    'lista_proveedores.php',
    'proveedores.php',
    'red_social.php',
    'guardar_usuario.php',
    'usuarios.php',
    'usuarios_ajax.php',
    'chat_list.php',
    'test_venta.php',
    'tickets_facturas.php',
    'generar_tickets_facturas.php'
];

// Patterns to search and replace
$old_patterns = [
    '/\$conexion\s*=\s*new\s+mysqli\s*\(\s*["\']localhost["\']\s*,\s*["\']root["\']\s*,\s*["\']["\']\s*,\s*["\']tiendasrey["\']\s*\)\s*;/i',
    '/\$conexion\s*=\s*@new\s+mysqli\s*\(\s*["\']localhost["\']\s*,\s*["\']root["\']\s*,\s*["\']["\']\s*,\s*["\']tiendasrey["\']\s*\)\s*;/i',
    '/\$conn\s*=\s*new\s+mysqli\s*\(\s*["\']localhost["\']\s*,\s*["\']root["\']\s*,\s*["\']["\']\s*,\s*["\']tiendasrey["\']\s*\)\s*;/i'
];

$errors_encountered = [];
$successfully_updated = [];

echo "=== DATABASE CONNECTION UPDATE SCRIPT ===" . PHP_EOL;
echo "Starting update process..." . PHP_EOL . PHP_EOL;

foreach ($files_to_update as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        $errors_encountered[] = "File not found: $file";
        echo "❌ SKIP: $file (file not found)" . PHP_EOL;
        continue;
    }
    
    $content = file_get_contents($filepath);
    $original_content = $content;
    
    // Check if already using db_connect.php or config.php
    if (stripos($content, "require_once 'db_connect.php'") !== false || 
        stripos($content, "require 'db_connect.php'") !== false ||
        stripos($content, "include 'db_connect.php'") !== false ||
        stripos($content, "require_once 'config.php'") !== false) {
        echo "⏭️  SKIP: $file (already updated)" . PHP_EOL;
        continue;
    }
    
    // Replace old connection patterns
    $was_modified = false;
    foreach ($old_patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, "require_once 'db_connect.php';", $content);
            $was_modified = true;
        }
    }
    
    // Remove error checking lines that follow the connection
    $content = preg_replace('/if\s*\(\s*\$conexion->connect_error\s*\)\s*\{[^}]*\}/s', '', $content);
    $content = preg_replace('/if\s*\(\s*\$conn->connect_error\s*\)\s*\{[^}]*\}/s', '', $content);
    
    if ($was_modified || $content !== $original_content) {
        if (file_put_contents($filepath, $content)) {
            $successfully_updated[] = $file;
            echo "✅ SUCCESS: $file" . PHP_EOL;
        } else {
            $errors_encountered[] = "Failed to write: $file";
            echo "❌ ERROR: $file (write failed)" . PHP_EOL;
        }
    } else {
        echo "⏭️  SKIP: $file (no hardcoded connection found)" . PHP_EOL;
}
}

echo PHP_EOL . "=== SUMMARY ===" . PHP_EOL;
echo "Successfully updated: " . count($successfully_updated) . " files" . PHP_EOL;
echo "Errors encountered: " . count($errors_encountered) . " files" . PHP_EOL;

if (count($errors_encountered) > 0) {
    echo PHP_EOL . "Errors:" . PHP_EOL;
    foreach ($errors_encountered as $error) {
        echo " - $error" . PHP_EOL;
    }
}

echo PHP_EOL . "Update process completed!" . PHP_EOL;
?>
