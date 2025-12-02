<?php
/**
 * API: Get Chat Users
 * Returns all users with unread message count
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    $conexion->set_charset("utf8mb4");
    
    // Get current user ID
    $stmt_user = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $_SESSION['usuario']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    
    if ($result_user->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
        exit;
    }
    
    $current_user_id = $result_user->fetch_assoc()['Id'];
    
    // Get all users except current with unread count
    $sql = "SELECT u.Id, u.Nombre, u.Apellido, u.Perfil, u.Rol,
            (SELECT COUNT(*) FROM chat_messages 
             WHERE from_user_id = u.Id AND to_user_id = ? AND is_read = 0) as unread_count
        FROM usuarios u
        WHERE u.Id != ?
        ORDER BY u.Nombre ASC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $current_user_id, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = [
            'id' => $row['Id'],
            'nombre' => $row['Nombre'] . ' ' . $row['Apellido'],
            'avatar' => $row['Perfil'] ?: 'default-avatar.png',
            'rol' => $row['Rol'],
            'unread_count' => (int)$row['unread_count']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'users' => $users
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
