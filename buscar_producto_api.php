<?php
header('Content-Type: application/json'); // Indicamos que la respuesta será JSON

// Conexión a la base de datos
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
    exit;
}

// Obtener los datos enviados desde el frontend (JSON)
 $json = file_get_contents('php://input');
 $data = json_decode($json, true);

// Validar que se recibió el código
if (!isset($data['codigo']) || empty(trim($data['codigo']))) {
    echo json_encode(['success' => false, 'message' => 'Código de producto no proporcionado.']);
    exit;
}

 $codigo = trim($data['codigo']);

// Usar una consulta preparada para prevenir inyección SQL
 $stmt = $conexion->prepare("SELECT Nombre_Producto, Marca, Descripcion, Precio_Unitario FROM stock WHERE Codigo_Producto = ?");
if ($stmt === false) {
    echo json_encode(['success' => false, 'message' => 'Error en la preparación de la consulta.']);
    exit;
}

 $stmt->bind_param("s", $codigo);
 $stmt->execute();
 $resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    $producto = $resultado->fetch_assoc();
    
    // Formatear el precio a dos decimales
    $precio_formateado = number_format($producto['Precio_Unitario'], 2, '.', ',');

    echo json_encode([
        'success' => true,
        'product' => [
            'nombre' => htmlspecialchars($producto['Nombre_Producto']),
            'marca' => htmlspecialchars($producto['Marca']),
            'descripcion' => htmlspecialchars($producto['Descripcion']),
            'precio' => $precio_formateado
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
}

 $stmt->close();
 $conexion->close();
?>