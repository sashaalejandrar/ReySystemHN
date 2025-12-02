<?php
header('Content-Type: application/json');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';

if (!empty($nombre)) {
    $stmt = $conexion->prepare("SELECT * FROM ventas WHERE Cliente = ? ORDER BY Fecha_Venta DESC");
    $stmt->bind_param("s", $nombre);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ventas = [];
    $total = 0;
    
    while ($row = $result->fetch_assoc()) {
        $ventas[] = $row;
        $total += floatval($row['Total']);
    }
    
    echo json_encode([
        'success' => true,
        'ventas' => $ventas,
        'total' => $total
    ]);
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Nombre de cliente no proporcionado']);
}

$conexion->close();
?>
