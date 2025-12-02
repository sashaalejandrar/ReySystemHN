<?php
/**
 * Sistema automático de sincronización de logros
 * Este archivo se incluye automáticamente en las acciones principales
 * para mantener los logros actualizados en tiempo real
 */

function autoSincronizarLogrosUsuario($usuario) {
    // Usar conexión global si existe, sino crear una nueva
    global $conexion;
    
    $conexion_local = null;
    if (!$conexion || !$conexion->ping()) {
        $conexion_local = new mysqli("localhost", "root", "", "tiendasrey");
        if ($conexion_local->connect_error) {
            return false; // Fallar silenciosamente para no interrumpir el flujo
        }
        $conn = $conexion_local;
    } else {
        $conn = $conexion;
    }
    
    // Obtener todos los logros activos
    $logros_query = "SELECT id, nombre, tipo_condicion, valor_objetivo FROM logros WHERE activo = 1";
    $logros_result = $conn->query($logros_query);
    
    if (!$logros_result) {
        if ($conexion_local) $conexion_local->close();
        return false;
    }
    
    $logros_desbloqueados = [];
    
    while ($logro = $logros_result->fetch_assoc()) {
        // Verificar si ya existe el registro
        $check_query = "SELECT * FROM usuarios_logros WHERE usuario = ? AND logro_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("si", $usuario, $logro['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $existe = $result->num_rows > 0;
        $registro_existente = $result->fetch_assoc();
        $stmt->close();
        
        // Calcular progreso actual
        require_once 'verificar_logros.php';
        $progreso_actual = calcularProgreso($usuario, $logro['tipo_condicion'], $conn);
        $completado = ($progreso_actual >= $logro['valor_objetivo']) ? 1 : 0;
        
        if (!$existe) {
            // Insertar registro nuevo
            $fecha_desbloqueo = $completado ? date('Y-m-d H:i:s') : null;
            $insert_query = "INSERT INTO usuarios_logros (usuario, logro_id, progreso_actual, completado, fecha_desbloqueo) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("siiis", $usuario, $logro['id'], $progreso_actual, $completado, $fecha_desbloqueo);
            $stmt->execute();
            $stmt->close();
            
            if ($completado) {
                $logros_desbloqueados[] = $logro;
            }
        } else {
            // Actualizar progreso existente solo si cambió
            if ($registro_existente['progreso_actual'] != $progreso_actual || $registro_existente['completado'] != $completado) {
                $fecha_desbloqueo = ($completado && !$registro_existente['completado']) ? date('Y-m-d H:i:s') : $registro_existente['fecha_desbloqueo'];
                $update_query = "UPDATE usuarios_logros SET progreso_actual = ?, completado = ?, fecha_desbloqueo = ? WHERE usuario = ? AND logro_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("iissi", $progreso_actual, $completado, $fecha_desbloqueo, $usuario, $logro['id']);
                $stmt->execute();
                $stmt->close();
                
                // Si se acaba de completar
                if ($completado && !$registro_existente['completado']) {
                    $logros_desbloqueados[] = $logro;
                }
            }
        }
    }
    
    if ($conexion_local) {
        $conexion_local->close();
    }
    
    return $logros_desbloqueados;
}

/**
 * Función para mostrar notificación de logros desbloqueados
 * Retorna un script JavaScript para mostrar las notificaciones
 */
function mostrarNotificacionesLogros($logros_desbloqueados) {
    if (empty($logros_desbloqueados)) {
        return '';
    }
    
    $script = "<script>\n";
    $script .= "document.addEventListener('DOMContentLoaded', function() {\n";
    
    foreach ($logros_desbloqueados as $logro) {
        $nombre = addslashes($logro['nombre']);
        $script .= "    setTimeout(function() {\n";
        $script .= "        if (typeof Swal !== 'undefined') {\n";
        $script .= "            Swal.fire({\n";
        $script .= "                icon: 'success',\n";
        $script .= "                title: '🏆 ¡Logro Desbloqueado!',\n";
        $script .= "                text: '{$nombre}',\n";
        $script .= "                timer: 3000,\n";
        $script .= "                showConfirmButton: false,\n";
        $script .= "                toast: true,\n";
        $script .= "                position: 'top-end'\n";
        $script .= "            });\n";
        $script .= "        } else {\n";
        $script .= "            alert('🏆 ¡Logro Desbloqueado! {$nombre}');\n";
        $script .= "        }\n";
        $script .= "    }, 500);\n";
    }
    
    $script .= "});\n";
    $script .= "</script>\n";
    
    return $script;
}
?>
