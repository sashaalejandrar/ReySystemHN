<?php
header('Content-Type: application/json');

// Activar reporte de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    $conexion->set_charset("utf8mb4");

    $sql = "SELECT Id, Nombre AS nombreCliente, Celular AS celular, Direccion AS direccion FROM clientes ORDER BY Nombre ASC";
    $resultado = $conexion->query($sql);

    if (!$resultado) {
        throw new Exception("Error en la consulta: " . $conexion->error);
    }

    $clientes = [];
    while ($row = $resultado->fetch_assoc()) {
        $clientes[] = $row;
    }

    echo json_encode(['success' => true, 'clientes' => $clientes]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
