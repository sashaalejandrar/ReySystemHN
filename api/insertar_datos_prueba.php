<?php
/**
 * Script para insertar datos de prueba en el sistema de comparación de precios
 * Ejecutar una sola vez para poblar la base de datos con ejemplos
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Datos de prueba para precios de competencia
$datosPrueba = [
    [
        'codigo' => '7421001643011',
        'nombre' => 'Coca Cola 2L',
        'precios' => [
            ['fuente' => 'La Colonia', 'precio' => 32.00, 'url' => 'https://lacolonia.hn/producto/coca-cola-2l'],
            ['fuente' => 'Walmart', 'precio' => 33.50, 'url' => 'https://walmart.com.hn/producto/coca-cola-2l'],
        ]
    ],
    [
        'codigo' => '7501055363803',
        'nombre' => 'Sabritas Original 45g',
        'precios' => [
            ['fuente' => 'La Colonia', 'precio' => 12.00, 'url' => 'https://lacolonia.hn/producto/sabritas-original'],
            ['fuente' => 'Walmart', 'precio' => 11.50, 'url' => 'https://walmart.com.hn/producto/sabritas-original'],
        ]
    ],
    [
        'codigo' => '7506174500124',
        'nombre' => 'Aceite Capullo 1L',
        'precios' => [
            ['fuente' => 'La Colonia', 'precio' => 85.00, 'url' => 'https://lacolonia.hn/producto/aceite-capullo'],
            ['fuente' => 'Walmart', 'precio' => 87.00, 'url' => 'https://walmart.com.hn/producto/aceite-capullo'],
        ]
    ],
    [
        'codigo' => '7501000112524',
        'nombre' => 'Leche Lala Entera 1L',
        'precios' => [
            ['fuente' => 'La Colonia', 'precio' => 28.00, 'url' => 'https://lacolonia.hn/producto/leche-lala'],
            ['fuente' => 'Walmart', 'precio' => 27.50, 'url' => 'https://walmart.com.hn/producto/leche-lala'],
        ]
    ],
    [
        'codigo' => '7501055300013',
        'nombre' => 'Pan Bimbo Blanco Grande',
        'precios' => [
            ['fuente' => 'La Colonia', 'precio' => 45.00, 'url' => 'https://lacolonia.hn/producto/pan-bimbo'],
            ['fuente' => 'Walmart', 'precio' => 44.00, 'url' => 'https://walmart.com.hn/producto/pan-bimbo'],
        ]
    ]
];

$insertados = 0;

foreach ($datosPrueba as $producto) {
    foreach ($producto['precios'] as $precio) {
        $stmt = $conexion->prepare("INSERT INTO precios_competencia 
            (codigo_producto, nombre_producto, precio_competencia, fuente, url_producto, fecha_actualizacion, disponible) 
            VALUES (?, ?, ?, ?, ?, NOW(), 1)
            ON DUPLICATE KEY UPDATE 
            precio_competencia = VALUES(precio_competencia),
            fecha_actualizacion = NOW()");
        
        $stmt->bind_param("ssdss", 
            $producto['codigo'], 
            $producto['nombre'], 
            $precio['precio'], 
            $precio['fuente'], 
            $precio['url']
        );
        
        if ($stmt->execute()) {
            $insertados++;
        }
        $stmt->close();
    }
}

echo "✅ Datos de prueba insertados: {$insertados} registros\n";
echo "Ahora puedes acceder a comparador_precios.php para ver los resultados\n";

$conexion->close();
?>
