<?php
session_start();
header('Content-Type: application/json');

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

require_once 'db_connect.php';
require_once 'verificar_logros.php';

$usuario = $_SESSION['usuario'];

try {
    // Obtener todos los logros activos con progreso del usuario
    $query = "
        SELECT 
            l.id,
            l.nombre,
            l.descripcion,
            l.icono,
            l.tipo_condicion,
            l.valor_objetivo,
            l.puntos,
            COALESCE(ul.progreso_actual, 0) as progreso_actual,
            COALESCE(ul.completado, 0) as completado,
            ul.fecha_desbloqueo
        FROM logros l
        LEFT JOIN usuarios_logros ul ON l.id = ul.logro_id AND ul.usuario = ?
        WHERE l.activo = 1
        ORDER BY l.orden ASC, l.id ASC
    ";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logros = [];
    
    while ($row = $result->fetch_assoc()) {
        // Calcular progreso actual en tiempo real
        $progreso_actual = calcularProgreso($usuario, $row['tipo_condicion'], $conexion);
        
        // Calcular porcentaje
        $porcentaje = 0;
        if ($row['valor_objetivo'] > 0) {
            $porcentaje = min(100, ($progreso_actual / $row['valor_objetivo']) * 100);
        }
        
        // Determinar unidad según tipo
        $unidad = '';
        switch ($row['tipo_condicion']) {
            case 'ventas_count':
                $unidad = $progreso_actual == 1 ? 'venta' : 'ventas';
                break;
            case 'aperturas_count':
                $unidad = $progreso_actual == 1 ? 'apertura' : 'aperturas';
                break;
            case 'arqueos_sin_error':
                $unidad = $progreso_actual == 1 ? 'arqueo perfecto' : 'arqueos perfectos';
                break;
            case 'clientes_count':
                $unidad = $progreso_actual == 1 ? 'cliente' : 'clientes';
                break;
            case 'dias_consecutivos':
                $unidad = $progreso_actual == 1 ? 'día' : 'días';
                break;
            case 'meta_alcanzada':
                $unidad = $progreso_actual == 1 ? 'meta' : 'metas';
                break;
            case 'inventario_updates':
                $unidad = $progreso_actual == 1 ? 'actualización' : 'actualizaciones';
                break;
            default:
                $unidad = '';
        }
        
        $logros[] = [
            'id' => $row['id'],
            'nombre' => $row['nombre'],
            'descripcion' => $row['descripcion'],
            'icono' => $row['icono'],
            'progreso_actual' => $progreso_actual,
            'valor_objetivo' => $row['valor_objetivo'],
            'porcentaje' => round($porcentaje, 1),
            'completado' => $row['completado'] == 1,
            'puntos' => $row['puntos'],
            'fecha_desbloqueo' => $row['fecha_desbloqueo'],
            'unidad' => $unidad,
            'casi_listo' => $porcentaje > 80 && $row['completado'] == 0
        ];
    }
    
    echo json_encode([
        'success' => true,
        'logros' => $logros
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conexion->close();
?>
