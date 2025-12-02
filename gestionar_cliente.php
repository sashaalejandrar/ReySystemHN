<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$conexion->set_charset("utf8mb4");

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

$action = isset($input['action']) ? $input['action'] : '';

if ($action === 'crear_cliente') {
    // Crear un nuevo cliente
    $nombre = isset($input['nombre']) ? trim($input['nombre']) : '';
    $celular = isset($input['celular']) ? trim($input['celular']) : 'NA';
    $direccion = isset($input['direccion']) ? trim($input['direccion']) : 'NA';
    
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'El nombre del cliente es requerido']);
        exit();
    }
    
    // Insertar cliente
    $stmt = $conexion->prepare("INSERT INTO clientes (Nombre, Celular, Direccion) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $celular, $direccion);
    
    if ($stmt->execute()) {
        $nuevo_id = $conexion->insert_id;
        echo json_encode([
            'success' => true,
            'message' => 'Cliente creado correctamente',
            'cliente' => [
                'Id' => $nuevo_id,
                'Nombre' => $nombre,
                'Celular' => $celular,
                'Direccion' => $direccion
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear cliente: ' . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

$conexion->close();
?>
