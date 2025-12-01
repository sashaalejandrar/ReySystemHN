<?php
// Script para verificar y actualizar el progreso de logros
// Se incluye desde otros archivos cuando ocurren acciones relevantes

function verificarLogros($usuario, $tipo_accion, $datos = []) {
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        return [];
    }
    
    $logros_desbloqueados = [];
    
    // Mapeo de tipos de acción a tipos de condición
    $mapeo_acciones = [
        'venta_completada' => 'ventas_count',
        'apertura_caja' => 'aperturas_count',
        'arqueo_perfecto' => 'arqueos_sin_error',
        'cliente_registrado' => 'clientes_count',
        'inventario_actualizado' => 'inventario_updates',
        'meta_alcanzada' => 'meta_alcanzada'
    ];
    
    if (!isset($mapeo_acciones[$tipo_accion])) {
        $conexion->close();
        return [];
    }
    
    $tipo_condicion = $mapeo_acciones[$tipo_accion];
    
    // Obtener logros activos de este tipo
    $stmt = $conexion->prepare("SELECT * FROM logros WHERE tipo_condicion = ? AND activo = 1");
    $stmt->bind_param("s", $tipo_condicion);
    $stmt->execute();
    $logros = $stmt->get_result();
    
    while ($logro = $logros->fetch_assoc()) {
        // Verificar si el usuario ya tiene este logro
        $stmt_check = $conexion->prepare("SELECT * FROM usuarios_logros WHERE usuario = ? AND logro_id = ?");
        $stmt_check->bind_param("si", $usuario, $logro['id']);
        $stmt_check->execute();
        $resultado = $stmt_check->get_result();
        
        if ($resultado->num_rows == 0) {
            // Crear registro de progreso
            $stmt_insert = $conexion->prepare("INSERT INTO usuarios_logros (usuario, logro_id, progreso_actual, completado) VALUES (?, ?, 0, 0)");
            $stmt_insert->bind_param("si", $usuario, $logro['id']);
            $stmt_insert->execute();
            $stmt_insert->close();
        }
        
        $usuario_logro = $resultado->fetch_assoc();
        $stmt_check->close();
        
        // Si ya está completado, saltar
        if ($usuario_logro && $usuario_logro['completado'] == 1) {
            continue;
        }
        
        // Calcular nuevo progreso
        $nuevo_progreso = calcularProgreso($usuario, $tipo_condicion, $conexion);
        
        // Actualizar progreso
        $completado = ($nuevo_progreso >= $logro['valor_objetivo']) ? 1 : 0;
        $fecha_desbloqueo = $completado ? date('Y-m-d H:i:s') : null;
        
        $stmt_update = $conexion->prepare("UPDATE usuarios_logros SET progreso_actual = ?, completado = ?, fecha_desbloqueo = ? WHERE usuario = ? AND logro_id = ?");
        $stmt_update->bind_param("iissi", $nuevo_progreso, $completado, $fecha_desbloqueo, $usuario, $logro['id']);
        $stmt_update->execute();
        $stmt_update->close();
        
        // Si se acaba de desbloquear, agregarlo al array de retorno
        if ($completado && (!$usuario_logro || $usuario_logro['completado'] == 0)) {
            $logros_desbloqueados[] = [
                'id' => $logro['id'],
                'nombre' => $logro['nombre'],
                'descripcion' => $logro['descripcion'],
                'icono' => $logro['icono'],
                'puntos' => $logro['puntos'],
                'color' => $logro['color']
            ];
        }
    }
    
    $stmt->close();
    $conexion->close();
    
    return $logros_desbloqueados;
}

function calcularProgreso($usuario, $tipo_condicion, $conexion) {
    $progreso = 0;
    
    switch ($tipo_condicion) {
        case 'ventas_count':
            $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM ventas WHERE Vendedor = ?");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $progreso = $result['count'];
            $stmt->close();
            break;
            
        case 'aperturas_count':
            $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM aperturas_caja WHERE usuario = ?");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $progreso = $result['count'];
            $stmt->close();
            break;
            
        case 'arqueos_sin_error':
            // Arqueos donde sobrante = 0 y faltante = 0
            $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM arqueos_caja WHERE usuario = ? AND sobrante = 0 AND faltante = 0");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $progreso = $result['count'];
            $stmt->close();
            break;
            
        case 'clientes_count':
            $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM clientes WHERE creado_por = ?");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $progreso = $result['count'];
            $stmt->close();
            break;
            
        case 'inventario_updates':
            // Contar actualizaciones en historial_inventario
            $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM historial_inventario WHERE usuario = ?");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $progreso = $result['count'];
            $stmt->close();
            break;
            
        case 'meta_alcanzada':
            // Verificar si alguna vez alcanzó una meta
            $mes_actual = date('n');
            $anio_actual = date('Y');
            
            $stmt = $conexion->prepare("
                SELECT COUNT(*) as count 
                FROM metas_ventas m
                LEFT JOIN (
                    SELECT SUM(Total) as total_ventas, MONTH(Fecha_Venta) as mes, YEAR(Fecha_Venta) as anio
                    FROM ventas 
                    WHERE Vendedor = ?
                    GROUP BY YEAR(Fecha_Venta), MONTH(Fecha_Venta)
                ) v ON m.mes = v.mes AND m.anio = v.anio
                WHERE v.total_ventas >= m.meta_monto
            ");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $progreso = $result['count'];
            $stmt->close();
            break;
            
        case 'dias_consecutivos':
            // Calcular días consecutivos con ventas
            $stmt = $conexion->prepare("
                SELECT DISTINCT DATE(Fecha_Venta) as fecha 
                FROM ventas 
                WHERE Vendedor = ? 
                ORDER BY fecha DESC
            ");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $dias_consecutivos = 0;
            $fecha_anterior = null;
            
            while ($row = $result->fetch_assoc()) {
                $fecha_actual = new DateTime($row['fecha']);
                
                if ($fecha_anterior === null) {
                    $dias_consecutivos = 1;
                } else {
                    $diff = $fecha_anterior->diff($fecha_actual)->days;
                    if ($diff == 1) {
                        $dias_consecutivos++;
                    } else {
                        break;
                    }
                }
                
                $fecha_anterior = $fecha_actual;
            }
            
            $progreso = $dias_consecutivos;
            $stmt->close();
            break;
    }
    
    return $progreso;
}

// Función para obtener logros recién desbloqueados (para mostrar notificación)
function obtenerLogrosRecientes($usuario, $limite = 5) {
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        return [];
    }
    
    $stmt = $conexion->prepare("
        SELECT l.*, ul.fecha_desbloqueo 
        FROM usuarios_logros ul
        JOIN logros l ON ul.logro_id = l.id
        WHERE ul.usuario = ? AND ul.completado = 1
        ORDER BY ul.fecha_desbloqueo DESC
        LIMIT ?
    ");
    $stmt->bind_param("si", $usuario, $limite);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $logros = [];
    while ($row = $result->fetch_assoc()) {
        $logros[] = $row;
    }
    
    $stmt->close();
    $conexion->close();
    
    return $logros;
}
?>
