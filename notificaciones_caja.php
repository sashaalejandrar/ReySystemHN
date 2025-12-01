<?php
// notificaciones_caja.php
// Funciones helper para mostrar notificaciones sobre el estado de la caja

function obtenerEstadoCaja($conexion) {
    $fecha_hoy = date('Y-m-d');
    
    // Verificar si hay una caja abierta hoy
    $query = "SELECT * FROM caja WHERE DATE(Fecha) = ? AND Estado = 'Abierta' ORDER BY Id DESC LIMIT 1";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("s", $fecha_hoy);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $caja_abierta = $resultado->fetch_assoc();
    $stmt->close();
    
    $estado = [
        'hay_caja_abierta' => false,
        'caja_id' => null,
        'fecha_apertura' => null,
        'monto_inicial' => 0,
        'mensaje' => '',
        'tipo' => '', // 'success', 'warning', 'error'
        'icono' => '',
        'mostrar_boton_abrir' => false
    ];
    
    if ($caja_abierta) {
        $estado['hay_caja_abierta'] = true;
        $estado['caja_id'] = $caja_abierta['Id'];
        $estado['fecha_apertura'] = $caja_abierta['Fecha'];
        $estado['monto_inicial'] = $caja_abierta['monto_inicial'];
        $estado['mensaje'] = '✅ Caja abierta y lista para operar';
        $estado['tipo'] = 'success';
        $estado['icono'] = 'lock_open';
    } else {
        // Verificar si hubo una caja hoy que ya se cerró
        $query_cerrada = "SELECT * FROM caja WHERE DATE(Fecha) = ? AND Estado = 'Cerrada' ORDER BY Id DESC LIMIT 1";
        $stmt = $conexion->prepare($query_cerrada);
        $stmt->bind_param("s", $fecha_hoy);
        $stmt->execute();
        $resultado_cerrada = $stmt->get_result();
        $caja_cerrada = $resultado_cerrada->fetch_assoc();
        $stmt->close();
        
        if ($caja_cerrada) {
            $estado['mensaje'] = '🔒 La caja ya fue cerrada hoy. No se pueden realizar operaciones hasta mañana.';
            $estado['tipo'] = 'error';
            $estado['icono'] = 'lock';
            $estado['mostrar_boton_abrir'] = false;
        } else {
            $estado['mensaje'] = '⚠️ No hay caja abierta. Debes abrir la caja para realizar operaciones.';
            $estado['tipo'] = 'warning';
            $estado['icono'] = 'warning';
            $estado['mostrar_boton_abrir'] = true;
        }
    }
    
    return $estado;
}

function mostrarNotificacionCaja($estado, $mostrar_siempre = false) {
    // Si todo está bien y no se requiere mostrar siempre, no mostrar nada
    if ($estado['tipo'] === 'success' && !$mostrar_siempre) {
        return '';
    }
    
    $colores = [
        'success' => 'bg-green-50 dark:bg-green-900/20 border-green-400 dark:border-green-600 text-green-800 dark:text-green-200',
        'warning' => 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-400 dark:border-yellow-600 text-yellow-800 dark:text-yellow-200',
        'error' => 'bg-red-50 dark:bg-red-900/20 border-red-400 dark:border-red-600 text-red-800 dark:text-red-200'
    ];
    
    $color_class = $colores[$estado['tipo']] ?? $colores['warning'];
    $animate_class = $estado['tipo'] !== 'success' ? 'animate-pulse' : '';
    
    $html = '
    <div class="' . $color_class . ' border-2 rounded-xl p-5 mb-6 ' . $animate_class . '">
        <div class="flex items-center gap-4">
            <span class="material-symbols-outlined text-4xl">' . $estado['icono'] . '</span>
            <div class="flex-1">
                <h3 class="text-lg font-bold mb-1">Estado de Caja</h3>
                <p class="text-sm font-medium">' . $estado['mensaje'] . '</p>';
    
    if ($estado['mostrar_boton_abrir']) {
        $html .= '
                <div class="mt-3">
                    <a href="apertura_caja.php" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition shadow-md text-sm">
                        <span class="material-symbols-outlined text-lg">lock_open</span>
                        Abrir Caja Ahora
                    </a>
                </div>';
    }
    
    $html .= '
            </div>
        </div>
    </div>';
    
    return $html;
}
?>
