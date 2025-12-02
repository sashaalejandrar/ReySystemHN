<?php
/**
 * API: Gestión de Tipos de Precios
 * Permite crear, leer, actualizar y eliminar tipos de precios personalizados
 */

header('Content-Type: application/json');
session_start();

// Verificar autenticación (opcional, ajustar según necesidad)
// include '../funciones.php';
// VerificarSiUsuarioYaInicioSesion();

try {
    $mysqli = new mysqli('localhost', 'root', '', 'tiendasrey');
    if ($mysqli->connect_error) {
        throw new Exception('Error de conexión a la BD');
    }
    $mysqli->set_charset("utf8mb4");

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // Listar todos los tipos de precios
            listarTiposPrecios($mysqli);
            break;

        case 'POST':
            // Crear nuevo tipo de precio
            crearTipoPrecio($mysqli);
            break;

        case 'PUT':
            // Actualizar tipo de precio
            actualizarTipoPrecio($mysqli);
            break;

        case 'DELETE':
            // Eliminar tipo de precio
            eliminarTipoPrecio($mysqli);
            break;

        default:
            throw new Exception('Método no permitido');
    }

    $mysqli->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// ============================================
// FUNCIONES
// ============================================

function listarTiposPrecios($mysqli) {
    $stmt = $mysqli->prepare("
        SELECT id, nombre, descripcion, es_default, activo, fecha_creacion
        FROM tipos_precios
        WHERE activo = TRUE
        ORDER BY es_default DESC, nombre ASC
    ");
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tipos = [];
    while ($row = $result->fetch_assoc()) {
        $tipos[] = [
            'id' => (int)$row['id'],
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion'],
            'es_default' => (bool)$row['es_default'],
            'activo' => (bool)$row['activo'],
            'fecha_creacion' => $row['fecha_creacion']
        ];
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'tipos' => $tipos,
        'total' => count($tipos)
    ]);
}

function crearTipoPrecio($mysqli) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['nombre'])) {
        throw new Exception('El nombre del tipo de precio es requerido');
    }
    
    $nombre = trim($data['nombre']);
    $descripcion = trim($data['descripcion'] ?? '');
    
    // Validar que el nombre no exista
    $stmt = $mysqli->prepare("SELECT id FROM tipos_precios WHERE nombre = ?");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $stmt->close();
        throw new Exception('Ya existe un tipo de precio con ese nombre');
    }
    $stmt->close();
    
    // Insertar nuevo tipo
    $stmt = $mysqli->prepare("
        INSERT INTO tipos_precios (nombre, descripcion, es_default, activo)
        VALUES (?, ?, FALSE, TRUE)
    ");
    $stmt->bind_param("ss", $nombre, $descripcion);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al crear el tipo de precio');
    }
    
    $nuevo_id = $mysqli->insert_id;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tipo de precio creado exitosamente',
        'id' => $nuevo_id
    ]);
}

function actualizarTipoPrecio($mysqli) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('ID del tipo de precio es requerido');
    }
    
    $id = (int)$data['id'];
    
    // Verificar que no sea un tipo por defecto
    $stmt = $mysqli->prepare("SELECT es_default FROM tipos_precios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        throw new Exception('Tipo de precio no encontrado');
    }
    
    $row = $result->fetch_assoc();
    if ($row['es_default']) {
        $stmt->close();
        throw new Exception('No se pueden editar los tipos de precio por defecto');
    }
    $stmt->close();
    
    // Actualizar
    $nombre = trim($data['nombre'] ?? '');
    $descripcion = trim($data['descripcion'] ?? '');
    
    if (empty($nombre)) {
        throw new Exception('El nombre es requerido');
    }
    
    $stmt = $mysqli->prepare("
        UPDATE tipos_precios
        SET nombre = ?, descripcion = ?
        WHERE id = ?
    ");
    $stmt->bind_param("ssi", $nombre, $descripcion, $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar el tipo de precio');
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tipo de precio actualizado exitosamente'
    ]);
}

function eliminarTipoPrecio($mysqli) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        throw new Exception('ID del tipo de precio es requerido');
    }
    
    $id = (int)$data['id'];
    
    // Verificar que no sea un tipo por defecto
    $stmt = $mysqli->prepare("SELECT es_default FROM tipos_precios WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        throw new Exception('Tipo de precio no encontrado');
    }
    
    $row = $result->fetch_assoc();
    if ($row['es_default']) {
        $stmt->close();
        throw new Exception('No se pueden eliminar los tipos de precio por defecto');
    }
    $stmt->close();
    
    // Eliminar (CASCADE eliminará los precios asociados)
    $stmt = $mysqli->prepare("DELETE FROM tipos_precios WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if (!$stmt->execute()) {
        throw new Exception('Error al eliminar el tipo de precio');
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tipo de precio eliminado exitosamente'
    ]);
}
?>
