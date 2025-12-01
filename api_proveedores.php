<?php
// API para buscar proveedores
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "tiendasrey");

if ($conn->connect_error) {
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'buscar') {
    $termino = $_GET['termino'] ?? '';
    
    if (strlen($termino) < 2) {
        echo json_encode([]);
        exit;
    }
    
    $termino = '%' . $conn->real_escape_string($termino) . '%';
    
    $stmt = $conn->prepare("SELECT * FROM proveedores WHERE Nombre LIKE ? OR Direccion LIKE ? OR Contacto LIKE ? LIMIT 10");
    $stmt->bind_param("sss", $termino, $termino, $termino);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $proveedores = [];
    while ($row = $result->fetch_assoc()) {
        $proveedores[] = [
            'id' => $row['Id'],
            'nombre' => $row['Nombre'],
            'direccion' => $row['Direccion'],
            'contacto' => $row['Contacto']
        ];
    }
    
    echo json_encode($proveedores);
    $stmt->close();
}

$conn->close();
?>
