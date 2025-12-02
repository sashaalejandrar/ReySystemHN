<?php
/**
 * Script de migración: Exportar datos de ventas a ventas_detalle
 * Este script toma las ventas existentes y crea registros en ventas_detalle
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "🔄 Iniciando migración de ventas a ventas_detalle...\n\n";

// Obtener todas las ventas que no tienen detalles
$sql_ventas = "SELECT v.* FROM ventas v 
               WHERE NOT EXISTS (
                   SELECT 1 FROM ventas_detalle vd WHERE vd.Id_Venta = v.Id
               )
               ORDER BY v.Id";

$result = $conexion->query($sql_ventas);

if (!$result) {
    die("Error al consultar ventas: " . $conexion->error);
}

$total_ventas = $result->num_rows;
echo "📊 Total de ventas a migrar: $total_ventas\n\n";

$migradas = 0;
$errores = 0;

while ($venta = $result->fetch_assoc()) {
    echo "Procesando venta ID: {$venta['Id']} - Factura: {$venta['Factura_Id']}\n";
    
    // Intentar obtener el ID del producto desde stock
    // Buscar por nombre del producto
    $productos_str = $venta['Producto_Vendido'];
    $marcas_str = $venta['Marca'];
    $cantidad_total = intval($venta['Cantidad']);
    $precio_promedio = floatval($venta['Precio']);
    $total_venta = floatval($venta['Total']);
    
    // Si el producto tiene "x" significa que tiene cantidad (ej: "Producto x2")
    // Intentamos separar por comas
    $productos_array = array_map('trim', explode(',', $productos_str));
    $marcas_array = array_map('trim', explode(',', $marcas_str));
    
    echo "  Productos: " . count($productos_array) . "\n";
    
    foreach ($productos_array as $index => $producto_info) {
        // Intentar extraer cantidad del formato "Producto xN"
        $cantidad = 1;
        $nombre_producto = $producto_info;
        
        if (preg_match('/(.+?)\s*x(\d+)$/i', $producto_info, $matches)) {
            $nombre_producto = trim($matches[1]);
            $cantidad = intval($matches[2]);
        }
        
        // Buscar el producto en stock
        $stmt_buscar = $conexion->prepare("SELECT Id, Precio_Unitario FROM stock WHERE Nombre_Producto LIKE ? LIMIT 1");
        $busqueda = "%{$nombre_producto}%";
        $stmt_buscar->bind_param("s", $busqueda);
        $stmt_buscar->execute();
        $result_producto = $stmt_buscar->get_result();
        
        if ($result_producto->num_rows > 0) {
            $producto = $result_producto->fetch_assoc();
            $id_producto = $producto['Id'];
            $precio_unitario = floatval($producto['Precio_Unitario']);
            
            // Si no pudimos extraer el precio de la venta, usar el precio del stock
            if ($precio_promedio == 0) {
                $precio_unitario = $precio_unitario;
            } else {
                $precio_unitario = $precio_promedio;
            }
            
            $subtotal = $precio_unitario * $cantidad;
            $marca = isset($marcas_array[$index]) ? $marcas_array[$index] : 'N/A';
            
            // Insertar en ventas_detalle
            $stmt_insert = $conexion->prepare(
                "INSERT INTO ventas_detalle 
                (Id_Venta, Id_Producto, Producto_Vendido, Marca, Cantidad, Precio, tipo_precio_nombre, tipo_precio_id, Subtotal, Fecha) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $tipo_precio = 'Precio_Unitario';
            $tipo_precio_id = 1;
            
            $stmt_insert->bind_param(
                "iissidsids",
                $venta['Id'],
                $id_producto,
                $nombre_producto,
                $marca,
                $cantidad,
                $precio_unitario,
                $tipo_precio,
                $tipo_precio_id,
                $subtotal,
                $venta['Fecha_Venta']
            );
            
            if ($stmt_insert->execute()) {
                echo "  ✅ Detalle insertado: $nombre_producto (ID: $id_producto) x$cantidad\n";
            } else {
                echo "  ❌ Error al insertar detalle: " . $stmt_insert->error . "\n";
                $errores++;
            }
            
            $stmt_insert->close();
        } else {
            echo "  ⚠️  Producto no encontrado en stock: $nombre_producto\n";
            
            // Insertar con ID_Producto = 0 (producto no encontrado)
            $stmt_insert = $conexion->prepare(
                "INSERT INTO ventas_detalle 
                (Id_Venta, Id_Producto, Producto_Vendido, Marca, Cantidad, Precio, tipo_precio_nombre, tipo_precio_id, Subtotal, Fecha) 
                VALUES (?, 0, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            
            $tipo_precio = 'Precio_Unitario';
            $tipo_precio_id = 1;
            $marca = isset($marcas_array[$index]) ? $marcas_array[$index] : 'N/A';
            $subtotal = $precio_promedio * $cantidad;
            
            $stmt_insert->bind_param(
                "issidsids",
                $venta['Id'],
                $nombre_producto,
                $marca,
                $cantidad,
                $precio_promedio,
                $tipo_precio,
                $tipo_precio_id,
                $subtotal,
                $venta['Fecha_Venta']
            );
            
            if ($stmt_insert->execute()) {
                echo "  ⚠️  Detalle insertado sin ID de producto\n";
            } else {
                echo "  ❌ Error: " . $stmt_insert->error . "\n";
                $errores++;
            }
            
            $stmt_insert->close();
        }
        
        $stmt_buscar->close();
    }
    
    $migradas++;
    echo "\n";
}

echo "\n✅ Migración completada!\n";
echo "📊 Ventas procesadas: $migradas\n";
echo "❌ Errores: $errores\n";

$conexion->close();
?>
