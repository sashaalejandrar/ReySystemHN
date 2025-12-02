<?php
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$termino = $_GET['q'] ?? '';
$codigo = $_GET['codigo'] ?? '';

if (empty($termino) && empty($codigo)) {
    echo json_encode(['success' => false, 'message' => 'Término de búsqueda requerido']);
    exit;
}

$productos = [];

if (!empty($codigo)) {
    // Búsqueda exacta por código
    $stmt = $conexion->prepare("SELECT Id, Codigo_Producto, Nombre_Producto, Stock FROM stock WHERE Codigo_Producto = ?");
    $stmt->bind_param("s", $codigo);
} else {
    // Búsqueda por nombre o código
    $busqueda = "%{$termino}%";
    $stmt = $conexion->prepare("
        SELECT Id, Codigo_Producto, Nombre_Producto, Stock 
        FROM stock 
        WHERE Nombre_Producto LIKE ? OR Codigo_Producto LIKE ?
        LIMIT 20
    ");
    $stmt->bind_param("ss", $busqueda, $busqueda);
}

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $productos[] = [
        'id' => $row['Id'],
        'codigo' => $row['Codigo_Producto'],
        'nombre' => $row['Nombre_Producto'],
        'stock' => intval($row['Stock'])
    ];
}

$stmt->close();
$conexion->close();

echo json_encode([
    'success' => true,
    'productos' => $productos
]);
?>
