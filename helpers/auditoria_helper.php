<?php
/**
 * Helper: Audit Log Function
 * Use this function to log changes throughout the system
 */

/**
 * Log a change to the audit table
 * 
 * @param string $tabla Table name
 * @param int $registro_id Record ID
 * @param string $accion Action: 'crear', 'editar', 'eliminar'
 * @param string|null $campo_modificado Field name (optional)
 * @param mixed|null $valor_anterior Previous value (optional)
 * @param mixed|null $valor_nuevo New value (optional)
 * @return bool Success status
 */
function registrar_auditoria($tabla, $registro_id, $accion, $campo_modificado = null, $valor_anterior = null, $valor_nuevo = null) {
    global $conexion;
    
    // Get user info from session
    if (!isset($_SESSION['usuario'])) {
        return false;
    }
    
    // Get user ID and name
    $stmt_user = $conexion->prepare("SELECT Id, Nombre, Apellido FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $_SESSION['usuario']);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    
    if ($result_user->num_rows === 0) {
        return false;
    }
    
    $user_data = $result_user->fetch_assoc();
    $usuario_id = $user_data['Id'];
    $usuario_nombre = $user_data['Nombre'] . ' ' . $user_data['Apellido'];
    $stmt_user->close();
    
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Get user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Convert values to string for storage
    $valor_anterior_str = is_null($valor_anterior) ? null : (is_array($valor_anterior) ? json_encode($valor_anterior) : strval($valor_anterior));
    $valor_nuevo_str = is_null($valor_nuevo) ? null : (is_array($valor_nuevo) ? json_encode($valor_nuevo) : strval($valor_nuevo));
    
    // Insert audit log
    $sql = "
    INSERT INTO auditoria (
        tabla, registro_id, accion, campo_modificado, 
        valor_anterior, valor_nuevo, usuario_id, usuario_nombre,
        ip_address, user_agent
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param(
        "sissssssss",
        $tabla,
        $registro_id,
        $accion,
        $campo_modificado,
        $valor_anterior_str,
        $valor_nuevo_str,
        $usuario_id,
        $usuario_nombre,
        $ip_address,
        $user_agent
    );
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Log multiple field changes at once
 * 
 * @param string $tabla Table name
 * @param int $registro_id Record ID
 * @param array $cambios Array of changes: ['campo' => ['anterior' => val, 'nuevo' => val]]
 * @return bool Success status
 */
function registrar_auditoria_multiple($tabla, $registro_id, $cambios) {
    $success = true;
    
    foreach ($cambios as $campo => $valores) {
        $anterior = $valores['anterior'] ?? null;
        $nuevo = $valores['nuevo'] ?? null;
        
        if (!registrar_auditoria($tabla, $registro_id, 'editar', $campo, $anterior, $nuevo)) {
            $success = false;
        }
    }
    
    return $success;
}
?>
