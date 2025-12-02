<?php
session_start();
require_once '../../db_connect.php';

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

header('Content-Type: application/json');
$action = $_GET['action'] ?? '';

// Función helper para manejar errores SQL
function executeQuery($conexion, $sql) {
    $result = $conexion->query($sql);
    if (!$result) {
        error_log("SQL Error: " . $conexion->error . " | Query: " . $sql);
        return false;
    }
    return $result;
}

switch ($action) {
    case 'sales_by_day':
        // Ventas de los últimos 30 días
        $sql = "SELECT DATE(Fecha_Venta) as fecha, SUM(total) as total 
                FROM ventas 
                WHERE Fecha_Venta >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(Fecha_Venta)
                ORDER BY fecha_venta ASC";
        
        $result = executeQuery($conexion, $sql);
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'fecha' => $row['fecha_venta'],
                    'total' => floatval($row['total'] ?? 0)
                ];
            }
        }
        
        echo json_encode($data);
        break;
        
    case 'top_products':
        // Top 10 productos más vendidos (últimos 30 días)
        $sql = "SELECT p.nombre, SUM(dv.cantidad) as cantidad_vendida, SUM(dv.total) as total_vendido
                FROM ventas dv
                JOIN productos p ON dv.id_producto = p.Id
                WHERE dv.Fecha_Venta >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY dv.id_producto, p.nombre
                ORDER BY cantidad_vendida DESC
                LIMIT 10";
        
        $result = executeQuery($conexion, $sql);
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'nombre' => $row['nombre'],
                    'cantidad' => intval($row['cantidad_vendida'] ?? 0),
                    'total' => floatval($row['total_vendido'] ?? 0)
                ];
            }
        }
        
        echo json_encode($data);
        break;
        
    case 'income_vs_expenses':
        // Ingresos vs Gastos del mes actual
        $sql_ingresos = "SELECT COALESCE(SUM(total), 0) as total FROM ventas WHERE MONTH(Fecha_Venta) = MONTH(NOW()) AND YEAR(Fecha_Venta) = YEAR(NOW())";
        $sql_gastos = "SELECT COALESCE(SUM(monto), 0) as total FROM egresos WHERE MONTH(Fecha_Venta) = MONTH(NOW()) AND YEAR(Fecha_Venta) = YEAR(NOW())";
        
        $result_ingresos = executeQuery($conexion, $sql_ingresos);
        $result_gastos = executeQuery($conexion, $sql_gastos);
        
        $ingresos = 0;
        $gastos = 0;
        
        if ($result_ingresos) {
            $ingresos = floatval($result_ingresos->fetch_assoc()['total'] ?? 0);
        }
        if ($result_gastos) {
            $gastos = floatval($result_gastos->fetch_assoc()['total'] ?? 0);
        }
        
        echo json_encode([
            'ingresos' => $ingresos,
            'gastos' => $gastos,
            'ganancia' => $ingresos - $gastos
        ]);
        break;
        
    case 'low_stock':
        // Productos con stock bajo (menos de 10 unidades)
        $sql = "SELECT Nombre_Producto as nombre, Stock as stock 
                FROM stock 
                WHERE Stock < 10 AND Stock > 0 
                ORDER BY Stock ASC 
                LIMIT 10";
        
        $result = executeQuery($conexion, $sql);
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'nombre' => $row['nombre'],
                    'stock' => intval($row['stock'] ?? 0)
                ];
            }
        }
        
        echo json_encode($data);
        break;
        
    case 'sales_by_hour':
        // Ventas por hora del día actual
        $sql = "SELECT HOUR(Fecha_Venta) as hora, COUNT(*) as cantidad, SUM(total) as total
                FROM ventas
                WHERE DATE(Fecha_Venta) = CURDATE()
                GROUP BY HOUR(Fecha_Venta)
                ORDER BY hora ASC";
        
        $result = executeQuery($conexion, $sql);
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = [
                    'hora' => intval($row['hora'] ?? 0),
                    'cantidad' => intval($row['cantidad'] ?? 0),
                    'total' => floatval($row['total'] ?? 0)
                ];
            }
        }
        
        echo json_encode($data);
        break;
        
    case 'stats_summary':
        // Resumen de estadísticas
        $stats = [];
        
        // Total ventas hoy
        $sql = "SELECT COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total 
                FROM ventas 
                WHERE DATE(Fecha_Venta) = CURDATE()";
        $result = executeQuery($conexion, $sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['ventas_hoy'] = [
                'cantidad' => intval($row['cantidad'] ?? 0),
                'total' => floatval($row['total'] ?? 0)
            ];
        }
        
        // Total ventas mes
        $sql = "SELECT COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total 
                FROM ventas 
                WHERE MONTH(Fecha_Venta) = MONTH(NOW()) AND YEAR(Fecha_Venta) = YEAR(NOW())";
        $result = executeQuery($conexion, $sql);
        if ($result) {
            $row = $result->fetch_assoc();
            $stats['ventas_mes'] = [
                'cantidad' => intval($row['cantidad'] ?? 0),
                'total' => floatval($row['total'] ?? 0)
            ];
        }
        
        // Productos totales (de la tabla stock)
        $sql = "SELECT COUNT(*) as total FROM stock";
        $result = executeQuery($conexion, $sql);
        if ($result) {
            $stats['productos_total'] = intval($result->fetch_assoc()['total'] ?? 0);
        }
        
        // Productos bajo stock (de la tabla stock)
        $sql = "SELECT COUNT(*) as total FROM stock WHERE Stock < 10";
        $result = executeQuery($conexion, $sql);
        if ($result) {
            $stats['productos_bajo_stock'] = intval($result->fetch_assoc()['total'] ?? 0);
        }
        
        echo json_encode($stats);
        break;
        
    default:
        echo json_encode(['error' => 'Acción no válida']);
}
?>
