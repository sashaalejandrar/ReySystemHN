<?php
ob_start();

// Convertir cualquier error en excepción
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

header('Content-Type: application/json');

function sendResponse($success, $message, $data = null) {
    ob_clean();
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        // Para listado
        if (isset($data['proveedores'])) {
            $response['proveedores'] = $data['proveedores'];
            $response['total_registros'] = $data['total_registros'] ?? 0;
            $response['total_paginas'] = $data['total_paginas'] ?? 0;
            $response['pagina_actual'] = $data['pagina_actual'] ?? 1;
        }
        // Para consulta individual
        if (isset($data['proveedor'])) {
            $response['proveedor'] = $data['proveedor'];
        }
    }
    echo json_encode($response);
    exit();
}

try {
    // Conexión BD
    $mysqli = new mysqli('localhost', 'root', '', 'tiendasrey');
    if ($mysqli->connect_error) {
        throw new Exception('Error de conexión a la BD: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset("utf8mb4");

    // --- CASO 1: Obtener un proveedor por ID ---
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $mysqli->prepare("SELECT * FROM proveedores WHERE Id = ?");
        if ($stmt === false) {
            throw new Exception('Error al preparar consulta de proveedor: ' . $mysqli->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $proveedor = $resultado->fetch_assoc();
        $stmt->close();

        if ($proveedor) {
            sendResponse(true, 'Proveedor obtenido correctamente.', ['proveedor' => $proveedor]);
        } else {
            sendResponse(false, 'Proveedor no encontrado.');
        }
    }

    // --- CASO 2: Listado con paginación ---
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina < 1) $pagina = 1;
    $limite = 50;
    $offset = ($pagina - 1) * $limite;
    $busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
    $estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

    $sql = "SELECT * FROM proveedores WHERE 1=1";
    $sql_count = "SELECT COUNT(*) as total FROM proveedores WHERE 1=1";
    $params = [];
    $types = '';

    if (!empty($busqueda)) {
        $sql .= " AND (Nombre LIKE ? OR RTN LIKE ? OR Contacto LIKE ?)";
        $sql_count .= " AND (Nombre LIKE ? OR RTN LIKE ? OR Contacto LIKE ?)";
        $searchTerm = '%' . $busqueda . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        $types .= 'sss';
    }

    if (!empty($estado) && $estado !== 'Todos') {
        $sql .= " AND Estado = ?";
        $sql_count .= " AND Estado = ?";
        $params[] = $estado;
        $types .= 's';
    }

    $sql .= " ORDER BY Nombre ASC LIMIT $offset, $limite";

    $stmt = $mysqli->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error al preparar consulta de proveedores: ' . $mysqli->error);
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();

    $proveedores = [];
    while ($fila = $resultado->fetch_assoc()) {
        $proveedores[] = $fila;
    }
    $stmt->close();

    $stmt_count = $mysqli->prepare($sql_count);
    if ($stmt_count === false) {
        throw new Exception('Error al preparar consulta de conteo: ' . $mysqli->error);
    }
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $resultado_count = $stmt_count->get_result();
    $total_registros = $resultado_count->fetch_assoc()['total'];
    $stmt_count->close();

    $total_paginas = ceil($total_registros / $limite);

    sendResponse(true, 'Proveedores cargados correctamente.', [
        'proveedores' => $proveedores,
        'total_registros' => $total_registros,
        'total_paginas' => $total_paginas,
        'pagina_actual' => $pagina
    ]);

    $mysqli->close();

} catch (Exception $e) {
    sendResponse(false, $e->getMessage());
}

ob_end_flush();
?>
