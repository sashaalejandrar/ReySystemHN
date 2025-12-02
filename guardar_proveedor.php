<?php
// Forzar que cualquier error se muestre como una excepción
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Establecer el encabezado para devolver una respuesta JSON
header('Content-Type: application/json');

// --- FUNCIONES ---
function sendResponse($success, $message) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    echo json_encode($response);
    exit();
}

// --- LÓGICA PRINCIPAL DENTRO DE TRY-CATCH ---
try {
    // 1. Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no permitido.');
    }

    // 2. Obtener y validar JSON
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Datos JSON inválidos.');
    }

    // 3. Validar datos
    if (
        empty($data->id) ||
        empty($data->nombre) ||
        empty($data->rtn) ||
        empty($data->contacto) ||
        empty($data->direccion) ||
        empty($data->celular) ||
        empty($data->estado)
    ) {
        throw new Exception('Todos los campos son obligatorios.');
    }

    // 4. Conexión a la BD
    $db_host = 'localhost';
    $db_name = 'tiendasrey';
    $db_user = 'root';
    $db_pass = '';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) {
        throw new Exception('Error de conexión a la BD: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset("utf8mb4");

    // 5. Preparar actualización
    $id = (int)$data->id;
    $nombre = trim($data->nombre);
    $rtn = trim($data->rtn);
    $contacto = trim($data->contacto);
    $direccion = trim($data->direccion);
    $celular = trim($data->celular);
    $estado = $data->estado;

    // 6. Actualizar
    $sql = "UPDATE proveedores SET Nombre = ?, RTN = ?, Contacto = ?, Direccion = ?, Celular = ?, Estado = ? WHERE Id = ?";
    $stmt = $mysqli->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error al preparar actualización: ' . $mysqli->error);
    }
    $stmt->bind_param("ssssssi", $nombre, $rtn, $contacto, $direccion, $celular, $estado, $id);

    if ($stmt->execute()) {
        sendResponse(true, 'Proveedor actualizado exitosamente.');
    } else {
        throw new Exception('No se pudo actualizar el proveedor: ' . $stmt->error);
    }

    $stmt->close();
    $mysqli->close();

} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
}
?>