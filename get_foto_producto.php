<?php
header('Content-Type: application/json');
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$codigo = isset($_GET['codigo']) ? $conexion->real_escape_string($_GET['codigo']) : '';

if (empty($codigo)) {
    echo json_encode(['success' => false, 'message' => 'Código vacío']);
    exit;
}

$query = "SELECT FotoProducto FROM stock WHERE Codigo_Producto = '$codigo' LIMIT 1";
$resultado = $conexion->query($query);

if ($resultado && $resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    echo json_encode(['success' => true, 'foto' => $row['FotoProducto']]);
} else {
    echo json_encode(['success' => false, 'message' => 'No se encontró foto']);
}

$conexion->close();
?>
