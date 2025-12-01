<?php
/**
 * API de Gamificación
 * Maneja logros, puntos, y leaderboard
 */
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = $_SESSION['usuario'];

switch ($action) {
    case 'get_user_stats':
        // Obtener estadísticas del usuario
        $stats = [
            'points' => 0,
            'level' => 1,
            'achievements' => [],
            'rank' => 0
        ];
        
        // Calcular puntos basados en ventas
        $sql = "SELECT COUNT(*) as ventas, SUM(total) as total_vendido 
                FROM ventas 
                WHERE usuario = ? AND MONTH(fecha) = MONTH(NOW())";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $stats['points'] = ($result['ventas'] * 10) + (floor($result['total_vendido'] / 100) * 5);
        $stats['level'] = floor($stats['points'] / 100) + 1;
        $stats['ventas_mes'] = $result['ventas'];
        $stats['total_vendido'] = $result['total_vendido'];
        
        // Obtener logros
        $achievements = [];
        
        if ($result['ventas'] >= 10) {
            $achievements[] = ['name' => 'Vendedor Novato', 'icon' => 'star', 'color' => 'blue'];
        }
        if ($result['ventas'] >= 50) {
            $achievements[] = ['name' => 'Vendedor Experto', 'icon' => 'workspace_premium', 'color' => 'purple'];
        }
        if ($result['ventas'] >= 100) {
            $achievements[] = ['name' => 'Maestro de Ventas', 'icon' => 'emoji_events', 'color' => 'yellow'];
        }
        
        $stats['achievements'] = $achievements;
        
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    case 'get_leaderboard':
        // Obtener ranking de vendedores
        $sql = "SELECT 
                    u.usuario,
                    u.nombre,
                    COUNT(v.Id) as ventas,
                    SUM(v.total) as total_vendido
                FROM usuarios u
                LEFT JOIN ventas v ON u.usuario = v.usuario AND MONTH(v.fecha) = MONTH(NOW())
                WHERE u.rol = 'vendedor'
                GROUP BY u.usuario
                ORDER BY total_vendido DESC
                LIMIT 10";
        
        $result = $conexion->query($sql);
        $leaderboard = [];
        $rank = 1;
        
        while ($row = $result->fetch_assoc()) {
            $points = ($row['ventas'] * 10) + (floor($row['total_vendido'] / 100) * 5);
            $leaderboard[] = [
                'rank' => $rank++,
                'usuario' => $row['usuario'],
                'nombre' => $row['nombre'],
                'ventas' => $row['ventas'],
                'total' => $row['total_vendido'],
                'points' => $points
            ];
        }
        
        echo json_encode(['success' => true, 'leaderboard' => $leaderboard]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>
