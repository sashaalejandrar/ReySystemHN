<?php
/**
 * Funciones del Sistema de Puntos de Fidelidad
 * Contiene todas las funciones para gestionar puntos de clientes
 */

if (!isset($conexion)) {
    require_once 'db_connect.php';
}

/**
 * Acumular puntos por una compra
 * @param string $cliente Nombre del cliente
 * @param float $monto_compra Monto total de la compra
 * @param int $venta_id ID de la venta
 * @param string $usuario Usuario que registra
 * @return array Resultado con puntos ganados y nuevo nivel
 */
function acumularPuntos($cliente, $monto_compra, $venta_id = null, $usuario = '') {
    global $conexion;
    
    try {
        // Calcular puntos base (1 punto por cada L10)
        $puntos_base = floor($monto_compra / 10);
        
        if ($puntos_base <= 0) {
            return ['success' => false, 'message' => 'Monto insuficiente para generar puntos'];
        }
        
        // Obtener o crear registro del cliente
        $stmt = $conexion->prepare("
            INSERT INTO puntos_clientes (cliente_nombre, puntos_disponibles, puntos_totales_ganados)
            VALUES (?, 0, 0)
            ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)
        ");
        $stmt->bind_param("s", $cliente);
        $stmt->execute();
        $stmt->close();
        
        // Obtener datos actuales del cliente
        $stmt = $conexion->prepare("SELECT puntos_totales_ganados, nivel_membresia FROM puntos_clientes WHERE cliente_nombre = ?");
        $stmt->bind_param("s", $cliente);
        $stmt->execute();
        $result = $stmt->get_result();
        $cliente_data = $result->fetch_assoc();
        $stmt->close();
        
        // Obtener multiplicador del nivel actual
        $stmt = $conexion->prepare("SELECT multiplicador_puntos FROM niveles_membresia WHERE nivel = ?");
        $stmt->bind_param("s", $cliente_data['nivel_membresia']);
        $stmt->execute();
        $result = $stmt->get_result();
        $nivel_data = $result->fetch_assoc();
        $multiplicador = $nivel_data['multiplicador_puntos'] ?? 1.00;
        $stmt->close();
        
        // Calcular puntos finales con multiplicador
        $puntos_ganados = floor($puntos_base * $multiplicador);
        
        // Actualizar puntos del cliente
        $stmt = $conexion->prepare("
            UPDATE puntos_clientes 
            SET puntos_disponibles = puntos_disponibles + ?,
                puntos_totales_ganados = puntos_totales_ganados + ?
            WHERE cliente_nombre = ?
        ");
        $stmt->bind_param("iis", $puntos_ganados, $puntos_ganados, $cliente);
        $stmt->execute();
        $stmt->close();
        
        // Registrar en historial
        $descripcion = "Compra de L" . number_format($monto_compra, 2) . " (x{$multiplicador})";
        $tipo = 'ganado';
        $stmt = $conexion->prepare("
            INSERT INTO historial_puntos (cliente_nombre, tipo, puntos, descripcion, venta_id, usuario)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssisss", $cliente, $tipo, $puntos_ganados, $descripcion, $venta_id, $usuario);
        $stmt->execute();
        $stmt->close();
        
        // Calcular y actualizar nivel
        $nuevo_nivel = calcularNivel($cliente_data['puntos_totales_ganados'] + $puntos_ganados);
        $nivel_anterior = $cliente_data['nivel_membresia'];
        
        if ($nuevo_nivel != $nivel_anterior) {
            $stmt = $conexion->prepare("UPDATE puntos_clientes SET nivel_membresia = ? WHERE cliente_nombre = ?");
            $stmt->bind_param("ss", $nuevo_nivel, $cliente);
            $stmt->execute();
            $stmt->close();
        }
        
        return [
            'success' => true,
            'puntos_ganados' => $puntos_ganados,
            'nivel_anterior' => $nivel_anterior,
            'nivel_nuevo' => $nuevo_nivel,
            'subio_nivel' => $nuevo_nivel != $nivel_anterior
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Canjear puntos por descuento
 * @param string $cliente Nombre del cliente
 * @param int $puntos Cantidad de puntos a canjear
 * @param string $descripcion Descripción del canje
 * @param string $usuario Usuario que registra
 * @return array Resultado con descuento aplicado
 */
function canjearPuntos($cliente, $puntos, $descripcion = 'Canje de puntos', $usuario = '') {
    global $conexion;
    
    try {
        // Validar que el cliente tenga suficientes puntos
        $stmt = $conexion->prepare("SELECT puntos_disponibles FROM puntos_clientes WHERE cliente_nombre = ?");
        $stmt->bind_param("s", $cliente);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            return ['success' => false, 'message' => 'Cliente no encontrado'];
        }
        
        $cliente_data = $result->fetch_assoc();
        $stmt->close();
        
        if ($cliente_data['puntos_disponibles'] < $puntos) {
            return ['success' => false, 'message' => 'Puntos insuficientes'];
        }
        
        if ($puntos < 100) {
            return ['success' => false, 'message' => 'Mínimo 100 puntos para canjear'];
        }
        
        // Calcular descuento (100 puntos = L10)
        $descuento = ($puntos / 100) * 10;
        
        // Actualizar puntos del cliente
        $stmt = $conexion->prepare("
            UPDATE puntos_clientes 
            SET puntos_disponibles = puntos_disponibles - ?,
                puntos_totales_canjeados = puntos_totales_canjeados + ?
            WHERE cliente_nombre = ?
        ");
        $stmt->bind_param("iis", $puntos, $puntos, $cliente);
        $stmt->execute();
        $stmt->close();
        
        // Registrar en historial
        $tipo = 'canjeado';
        $puntos_negativos = -$puntos;
        $stmt = $conexion->prepare("
            INSERT INTO historial_puntos (cliente_nombre, tipo, puntos, descripcion, usuario)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sisss", $cliente, $tipo, $puntos_negativos, $descripcion, $usuario);
        $stmt->execute();
        $stmt->close();
        
        return [
            'success' => true,
            'puntos_canjeados' => $puntos,
            'descuento' => $descuento,
            'puntos_restantes' => $cliente_data['puntos_disponibles'] - $puntos
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Obtener puntos de un cliente
 * @param string $cliente Nombre del cliente
 * @return array|null Datos del cliente o null
 */
function obtenerPuntosCliente($cliente) {
    global $conexion;
    
    $stmt = $conexion->prepare("
        SELECT pc.*, nm.multiplicador_puntos, nm.descuento_adicional, nm.color, nm.icono
        FROM puntos_clientes pc
        LEFT JOIN niveles_membresia nm ON pc.nivel_membresia = nm.nivel
        WHERE pc.cliente_nombre = ?
    ");
    $stmt->bind_param("s", $cliente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Calcular nivel según puntos totales
 * @param int $puntos_totales Total de puntos ganados
 * @return string Nivel de membresía
 */
function calcularNivel($puntos_totales) {
    global $conexion;
    
    $stmt = $conexion->query("
        SELECT nivel FROM niveles_membresia 
        WHERE puntos_minimos <= $puntos_totales 
        ORDER BY puntos_minimos DESC 
        LIMIT 1
    ");
    
    if ($stmt && $row = $stmt->fetch_assoc()) {
        return $row['nivel'];
    }
    
    return 'Bronce';
}

/**
 * Obtener historial de puntos de un cliente
 * @param string $cliente Nombre del cliente
 * @param int $limit Límite de registros
 * @return array Historial de transacciones
 */
function obtenerHistorial($cliente, $limit = 50) {
    global $conexion;
    
    $stmt = $conexion->prepare("
        SELECT * FROM historial_puntos 
        WHERE cliente_nombre = ? 
        ORDER BY fecha DESC 
        LIMIT ?
    ");
    $stmt->bind_param("si", $cliente, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $historial = [];
    while ($row = $result->fetch_assoc()) {
        $historial[] = $row;
    }
    
    return $historial;
}

/**
 * Obtener todas las recompensas activas
 * @return array Lista de recompensas
 */
function obtenerRecompensas() {
    global $conexion;
    
    $result = $conexion->query("
        SELECT * FROM recompensas 
        WHERE activo = 1 
        ORDER BY puntos_requeridos ASC
    ");
    
    $recompensas = [];
    while ($row = $result->fetch_assoc()) {
        $recompensas[] = $row;
    }
    
    return $recompensas;
}

/**
 * Obtener top clientes por puntos
 * @param int $limit Cantidad de clientes
 * @return array Top clientes
 */
function obtenerTopClientes($limit = 10) {
    global $conexion;
    
    $stmt = $conexion->prepare("
        SELECT cliente_nombre, puntos_disponibles, puntos_totales_ganados, nivel_membresia
        FROM puntos_clientes 
        ORDER BY puntos_totales_ganados DESC 
        LIMIT ?
    ");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $top = [];
    while ($row = $result->fetch_assoc()) {
        $top[] = $row;
    }
    
    return $top;
}

/**
 * Obtener estadísticas generales del sistema
 * @return array Estadísticas
 */
function obtenerEstadisticas() {
    global $conexion;
    
    $stats = [];
    
    // Total de clientes con puntos
    $result = $conexion->query("SELECT COUNT(*) as total FROM puntos_clientes");
    $stats['total_clientes'] = $result->fetch_assoc()['total'];
    
    // Total de puntos en circulación
    $result = $conexion->query("SELECT SUM(puntos_disponibles) as total FROM puntos_clientes");
    $stats['puntos_circulacion'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Puntos canjeados este mes
    $result = $conexion->query("
        SELECT SUM(ABS(puntos)) as total 
        FROM historial_puntos 
        WHERE tipo = 'canjeado' 
        AND MONTH(fecha) = MONTH(CURRENT_DATE())
        AND YEAR(fecha) = YEAR(CURRENT_DATE())
    ");
    $stats['canjes_mes'] = $result->fetch_assoc()['total'] ?? 0;
    
    // Distribución por niveles
    $result = $conexion->query("
        SELECT nivel_membresia, COUNT(*) as cantidad 
        FROM puntos_clientes 
        GROUP BY nivel_membresia
    ");
    $stats['por_nivel'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['por_nivel'][$row['nivel_membresia']] = $row['cantidad'];
    }
    
    return $stats;
}
?>
