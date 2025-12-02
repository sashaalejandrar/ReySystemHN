<?php
header('Content-Type: application/json');

try {
    $mysqli = new mysqli('localhost', 'root', '', 'tiendasrey');
    if ($mysqli->connect_error) {
        throw new Exception('Error de conexión a la BD');
    }
    $mysqli->set_charset("utf8mb4");

    // Obtener categorías de la tabla Categorias
    $stmt = $mysqli->prepare("SELECT nombre FROM Categorias ORDER BY nombre ASC");
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $categorias = [];
    while ($fila = $resultado->fetch_assoc()) {
        $categorias[] = $fila['nombre'];
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'categorias' => $categorias
    ]);

    $mysqli->close();

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
