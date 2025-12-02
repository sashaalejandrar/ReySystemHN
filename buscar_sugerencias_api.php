<?php
header('Content-Type: application/json');

// Conexión a la base de datos
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode([]); // Devuelve un array vacío en caso de error
    exit;
}

 $json = file_get_contents('php://input');
 $data = json_decode($json, true);

if (!isset($data['term']) || empty(trim($data['term']))) {
    echo json_encode([]);
    exit;
}

 $term = '%' . trim($data['term']) . '%';

// Buscar por nombre o código, limitando a 10 resultados para no sobrecargar
 $stmt = $conexion->prepare("SELECT Codigo_Producto, Nombre_Producto FROM stock WHERE Nombre_Producto LIKE ? OR Codigo_Producto LIKE ? ORDER BY Nombre_Producto ASC LIMIT 10");
if ($stmt === false) {
    echo json_encode([]);
    exit;
}

 $stmt->bind_param("ss", $term, $term);
 $stmt->execute();
 $resultado = $stmt->get_result();

 $suggestions = [];
while ($row = $resultado->fetch_assoc()) {
    $suggestions[] = [
        'codigo' => $row['Codigo_Producto'],
        'nombre' => $row['Nombre_Producto']
    ];
}

 $stmt->close();
 $conexion->close();

echo json_encode($suggestions);
?>