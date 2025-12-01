<?php
/**
 * API para obtener el historial de correcciones del diagnóstico
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea admin
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$stmt = $conexion->prepare("SELECT Rol FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || strtolower($user['Rol']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    $conexion->close();
    exit;
}

try {
    // Obtener parámetros
    $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

    // Consultar historial
    $query = "SELECT * FROM diagnostico_historial ORDER BY fecha_correccion DESC LIMIT ? OFFSET ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ii", $limite, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $historial = [];
    while ($row = $result->fetch_assoc()) {
        $historial[] = [
            'id' => $row['id'],
            'titulo' => $row['titulo'],
            'descripcion' => $row['descripcion'],
            'archivo' => $row['archivo'],
            'nivel' => $row['nivel'],
            'tipo' => $row['tipo'],
            'solucion' => $row['solucion'],
            'proveedor' => $row['proveedor'],
            'usuario' => $row['usuario'],
            'fecha' => date('d/m/Y H:i', strtotime($row['fecha_correccion'])),
            'backup' => $row['backup_archivo']
        ];
    }

    $stmt->close();

    // Contar total
    $countStmt = $conexion->query("SELECT COUNT(*) as total FROM diagnostico_historial");
    $total = $countStmt->fetch_assoc()['total'];

    echo json_encode([
        'success' => true,
        'historial' => $historial,
        'total' => $total
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener historial: ' . $e->getMessage()
    ]);
} finally {
    $conexion->close();
}
