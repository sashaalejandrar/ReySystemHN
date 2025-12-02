<?php
/**
 * Database Schema Verification Script
 * Verifies that all required multi-tenancy columns exist
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "=== VERIFICACIÓN DE ESQUEMA MULTI-TENANCY ===\n\n";

$tables = [
    'caja',
    'cierre_caja',
    'arqueo_caja',
    'ventas',
    'stock',
    'egresos_caja',
    'clientes',
    'proveedores',
    'deudas'
];

$all_ok = true;

foreach ($tables as $table) {
    echo "Tabla: $table\n";
    
    $result = $conexion->query("DESCRIBE $table");
    
    if (!$result) {
        echo "  ❌ Tabla no existe\n\n";
        continue;
    }
    
    $has_negocio = false;
    $has_sucursal = false;
    
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] === 'id_negocio') {
            $has_negocio = true;
        }
        if ($row['Field'] === 'id_sucursal') {
            $has_sucursal = true;
        }
    }
    
    if ($has_negocio && $has_sucursal) {
        echo "  ✅ id_negocio: OK\n";
        echo "  ✅ id_sucursal: OK\n";
    } else {
        $all_ok = false;
        if (!$has_negocio) echo "  ❌ id_negocio: FALTA\n";
        if (!$has_sucursal) echo "  ❌ id_sucursal: FALTA\n";
    }
    
    echo "\n";
}

echo "\n=== RESUMEN ===\n";
if ($all_ok) {
    echo "✅ TODAS LAS TABLAS TIENEN LAS COLUMNAS REQUERIDAS\n";
} else {
    echo "❌ ALGUNAS TABLAS TIENEN COLUMNAS FALTANTES\n";
}

$conexion->close();
?>
