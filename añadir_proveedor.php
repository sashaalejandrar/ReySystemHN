<?php
ob_start();
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
header('Content-Type: application/json');

function sendResponse($success, $message) {
    ob_clean();
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

try {
    // Verificar que el Content-Type sea application/json
    if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        throw new Exception('Solo se aceptan peticiones con Content-Type: application/json');
    }

    // Leer y validar JSON del cuerpo
    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        throw new Exception('No se recibió ningún cuerpo en la petición.');
    }

    $input = json_decode($raw, true);
    if (!is_array($input)) {
        throw new Exception('El cuerpo de la petición no es un JSON válido.');
    }

    // Validar campos obligatorios
    $campos = ['nombre', 'rtn', 'contacto', 'direccion', 'celular', 'estado'];
    foreach ($campos as $campo) {
        if (empty($input[$campo])) {
            throw new Exception("El campo '$campo' es obligatorio.");
        }
    }

    // Conexión BD
    $mysqli = new mysqli('localhost', 'root', '', 'tiendasrey');
    if ($mysqli->connect_error) {
        throw new Exception('Error de conexión: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset("utf8mb4");

    // Insertar proveedor
    $stmt = $mysqli->prepare("INSERT INTO proveedores (Nombre, RTN, Contacto, Direccion, Celular, Estado) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Error al preparar la consulta: ' . $mysqli->error);
    }

    $stmt->bind_param(
        "ssssss",
        $input['nombre'],
        $input['rtn'],
        $input['contacto'],
        $input['direccion'],
        $input['celular'],
        $input['estado']
    );

    if (!$stmt->execute()) {
        throw new Exception('Error al insertar proveedor: ' . $stmt->error);
    }

    $stmt->close();
    $mysqli->close();

    sendResponse(true, 'Proveedor creado exitosamente.');

} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
}

ob_end_flush();
?>
