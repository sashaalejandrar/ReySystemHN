<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include '../funciones.php';

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}
$conexion->set_charset("utf8mb4");

// Obtener rol del usuario
$stmt = $conexion->prepare("SELECT Rol FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Solo admin puede obtener lista de usuarios
if ($user['Rol'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Obtener todos los usuarios
$query = "SELECT Usuario, Nombre, Apellido, Rol FROM usuarios ORDER BY Nombre, Apellido";
$result = $conexion->query($query);

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = [
        'usuario' => $row['Usuario'],
        'nombre_completo' => $row['Nombre'] . ' ' . $row['Apellido'],
        'rol' => $row['Rol']
    ];
}

$conexion->close();

echo json_encode([
    'success' => true,
    'usuarios' => $usuarios
]);
?>
