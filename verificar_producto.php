<?php
session_start();
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['existe' => false, 'message' => 'Error de conexión']);
    exit;
}

// Obtener datos JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['codigo'])) {
    echo json_encode(['existe' => false, 'message' => 'Código no proporcionado']);
    exit;
}

$codigo = $conexion->real_escape_string($data['codigo']);

// Buscar en creacion_de_productos
$query = "SELECT * FROM creacion_de_productos WHERE Codigo = '$codigo' LIMIT 1";
$resultado = $conexion->query($query);

if ($resultado && $resultado->num_rows > 0) {
    $producto = $resultado->fetch_assoc();
    
    echo json_encode([
        'existe' => true,
        'producto' => [
            'id' => $producto['Id'],
            'codigo' => $producto['Codigo'],
            'nombre' => $producto['Nombre'],
            'marca' => $producto['Marca']
        ]
    ]);
} else {
    echo json_encode([
        'existe' => false,
        'message' => 'Producto no encontrado en creacion_de_productos'
    ]);
}

$conexion->close();
?>