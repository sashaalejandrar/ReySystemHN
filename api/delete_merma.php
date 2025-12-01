<?php
/**
 * API: Delete Merma
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    $conexion->set_charset("utf8mb4");
    
    // Get merma info before deleting
    $stmt_get = $conexion->prepare("SELECT producto_id, cantidad FROM mermas WHERE id = ?");
    $stmt_get->bind_param("i", $data['id']);
    $stmt_get->execute();
    $merma = $stmt_get->get_result()->fetch_assoc();
    
    // Delete merma
    $stmt = $conexion->prepare("DELETE FROM mermas WHERE id = ?");
    $stmt->bind_param("i", $data['id']);
    
    if ($stmt->execute()) {
        // Restore inventory
        $stmt_update = $conexion->prepare("UPDATE stock SET Stock = Stock + ? WHERE Id = ?");
        $stmt_update->bind_param("di", $merma['cantidad'], $merma['producto_id']);
        $stmt_update->execute();
        
        echo json_encode(['success' => true, 'message' => 'Merma eliminada']);
    } else {
        throw new Exception('Error al eliminar merma');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conexion->close();
?>
