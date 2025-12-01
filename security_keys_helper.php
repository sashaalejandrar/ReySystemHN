<?php
/**
 * Helper para verificar llaves de seguridad durante el login
 */

/**
 * Verifica si el usuario tiene llaves de seguridad habilitadas
 */
function hasSecurityKeys($conexion, $user_id) {
    try {
        // Verificar si la tabla existe
        $result = $conexion->query("SHOW TABLES LIKE 'security_keys'");
        if ($result->num_rows == 0) {
            return false;
        }
        
        // Intentar con idUsuario primero
        $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM security_keys WHERE idUsuario = ? AND enabled = 1");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            return true;
        }
        
        // Si no encuentra, intentar con user_id
        $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM security_keys WHERE user_id = ? AND enabled = 1");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Verifica si el dispositivo actual es de confianza
 */
function isTrustedDevice($conexion, $user_id) {
    try {
        // Verificar si la tabla existe
        $result = $conexion->query("SHOW TABLES LIKE 'trusted_devices'");
        if ($result->num_rows == 0) {
            return false;
        }
        
        // Obtener el ID numérico del usuario si se pasó el username
        $numeric_user_id = $user_id;
        if (!is_numeric($user_id)) {
            // Es un username, obtener el ID
            $stmt = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
            $stmt->bind_param("s", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $numeric_user_id = $result->fetch_assoc()['Id'];
            } else {
                return false;
            }
        }
        
        // Verificar por token en cookie
        if (isset($_COOKIE['trusted_device_token'])) {
            $token = $_COOKIE['trusted_device_token'];
            
            $stmt = $conexion->prepare("
                SELECT id FROM trusted_devices 
                WHERE user_id = ? 
                AND device_token = ?
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->bind_param("is", $numeric_user_id, $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Actualizar last_used
                $device_id = $result->fetch_assoc()['id'];
                $stmt = $conexion->prepare("UPDATE trusted_devices SET last_used = NOW() WHERE id = ?");
                $stmt->bind_param("i", $device_id);
                $stmt->execute();
                return true;
            }
        }
        
        // Verificar por fingerprint
        $fingerprint = generateDeviceFingerprint();
        
        $stmt = $conexion->prepare("
            SELECT id FROM trusted_devices 
            WHERE user_id = ? 
            AND device_fingerprint = ? 
            AND (expires_at IS NULL OR expires_at > NOW())
        ");
        $stmt->bind_param("is", $numeric_user_id, $fingerprint);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Actualizar last_used
            $device_id = $result->fetch_assoc()['id'];
            $stmt = $conexion->prepare("UPDATE trusted_devices SET last_used = NOW() WHERE id = ?");
            $stmt->bind_param("i", $device_id);
            $stmt->execute();
            return true;
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Genera un fingerprint único del dispositivo
 */
function generateDeviceFingerprint() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $accept_encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    
    return base64_encode($user_agent . $accept_language . $accept_encoding);
}

/**
 * Verifica un PIN de seguridad
 */
function verifySecurityPIN($conexion, $user_id, $pin) {
    $stmt = $conexion->prepare("
        SELECT key_data FROM security_keys 
        WHERE user_id = ? 
        AND key_type = 'pin' 
        AND enabled = 1
    ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return password_verify($pin, $row['key_data']);
    }
    
    return false;
}

/**
 * Obtiene los tipos de llaves de seguridad habilitadas para un usuario
 */
function getEnabledSecurityKeyTypes($conexion, $user_id) {
    $stmt = $conexion->prepare("
        SELECT DISTINCT key_type FROM security_keys 
        WHERE user_id = ? 
        AND enabled = 1
    ");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $types = [];
    while ($row = $result->fetch_assoc()) {
        $types[] = $row['key_type'];
    }
    
    return $types;
}

/**
 * Verifica si el usuario tiene PIN habilitado
 */
function hasPinEnabled($conexion, $user_id) {
    try {
        // Verificar si la tabla existe primero
        $result = $conexion->query("SHOW TABLES LIKE 'pin_security'");
        if ($result->num_rows == 0) {
            return false; // Tabla no existe
        }
        
        $stmt = $conexion->prepare("SELECT COUNT(*) as count FROM pin_security WHERE idUsuario = ? AND enabled = 1");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    } catch (Exception $e) {
        return false; // En caso de error, asumir que no tiene PIN
    }
}
?>
