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
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método de solicitud no permitido.');
    }

    // 2. Obtener ID
    if (empty($_GET['id'])) {
        throw new Exception('ID de proveedor no especificado.');
    }
    $id = (int)$_GET['id'];

    // 3. Conexión a la BD
    $db_host = 'localhost';
    $db_name = 'tiendasrey';
    $db_user = 'root';
    $db_pass = '';

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) {
        throw new Exception('Error de conexión a la BD: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset("utf8mb4");

    // 4. Eliminar
    $sql = "DELETE FROM proveedores WHERE Id = ?";
    $stmt = $mysqli->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error al preparar eliminación: ' . $mysqli->error);
    }
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            sendResponse(true, 'Proveedor eliminado exitosamente.');
        } else {
            throw new Exception('El proveedor no existe o ya fue eliminado.');
        }
    } else {
        throw new Exception('No se pudo eliminar el proveedor: ' . $stmt->error);
    }

    $stmt->close();
    $mysqli->close();

} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
}
?>