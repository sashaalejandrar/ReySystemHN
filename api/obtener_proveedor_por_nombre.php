<?php
header('Content-Type: application/json');

try {
    $mysqli = new mysqli('localhost', 'root', '', 'tiendasrey');
    if ($mysqli->connect_error) {
        throw new Exception('Error de conexión a la BD');
    }
    $mysqli->set_charset("utf8mb4");

    // Obtener nombre del proveedor
    $nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';
    
    if (empty($nombre)) {
        echo json_encode(['success' => false, 'message' => 'Nombre de proveedor requerido']);
        exit();
    }

    // Buscar proveedor por nombre exacto
    $stmt = $mysqli->prepare("SELECT Nombre, Direccion, Contacto, RTN FROM proveedores WHERE Nombre = ? LIMIT 1");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $proveedor = $resultado->fetch_assoc();
    $stmt->close();

    if ($proveedor) {
        echo json_encode([
            'success' => true,
            'proveedor' => $proveedor
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Proveedor no encontrado'
        ]);
    }

    $mysqli->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
