<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}
$conexion->set_charset("utf8mb4");

$busqueda = trim($_GET['buscar'] ?? '');
$filtro = $_GET['filtro'] ?? 'Todos';

$query = "SELECT * FROM stock WHERE 1=1";
$params = [];
$types = "";

if (!empty($busqueda)) {
    $query .= " AND (Nombre_Producto LIKE ? OR Codigo_Producto LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

if ($filtro == 'Activo') {
    $query .= " AND Stock > 10";
} elseif ($filtro == 'Bajo Stock') {
    $query .= " AND Stock > 0 AND Stock <= 10";
} elseif ($filtro == 'Agotado') {
    $query .= " AND Stock = 0";
}

$query .= " ORDER BY Nombre_Producto ASC";

$stmt = $conexion->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$productos = [];
while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
}

$stmt->close();
$conexion->close();

echo json_encode([
    'success' => true,
    'productos' => $productos
]);
?>
