<?php
/**
 * API para gestionar llaves de seguridad
 */
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

// Conectar a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$conexion->set_charset("utf8mb4");

// Obtener user_id desde el nombre de usuario
$stmt_user = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
$stmt_user->bind_param("s", $_SESSION['usuario']);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

$user_id = $result_user->fetch_assoc()['Id'];
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'save_pin':
        $pin = $_POST['pin'] ?? '';
        
        if (strlen($pin) !== 6 || !ctype_digit($pin)) {
            echo json_encode(['success' => false, 'message' => 'PIN inválido']);
            exit;
        }
        
        $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
        
        // Eliminar PIN anterior si existe
        $stmt = $conexion->prepare("DELETE FROM security_keys WHERE user_id = ? AND key_type = 'pin'");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        
        // Guardar nuevo PIN
        $stmt = $conexion->prepare("INSERT INTO security_keys (user_id, key_type, key_name, key_data) VALUES (?, 'pin', 'PIN de Seguridad', ?)");
        $stmt->bind_param("ss", $user_id, $hashed_pin);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'PIN configurado exitosamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar PIN']);
        }
        break;
        
    case 'save_trusted_device':
        $device_fingerprint = $_POST['fingerprint'] ?? '';
        $device_name = $_POST['device_name'] ?? 'Dispositivo';
        $browser = $_POST['browser'] ?? '';
        $os = $_POST['os'] ?? '';
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        // Expiración en 30 días
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $conexion->prepare("INSERT INTO trusted_devices (user_id, device_fingerprint, device_name, browser, os, ip_address, expires_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $user_id, $device_fingerprint, $device_name, $browser, $os, $ip_address, $expires_at);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Dispositivo registrado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar dispositivo']);
        }
        break;
        
    case 'save_webauthn':
        $key_name = $_POST['key_name'] ?? 'Llave de Seguridad';
        $key_data = $_POST['key_data'] ?? '';
        
        $stmt = $conexion->prepare("INSERT INTO security_keys (user_id, key_type, key_name, key_data) VALUES (?, 'webauthn', ?, ?)");
        $stmt->bind_param("sss", $user_id, $key_name, $key_data);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Llave registrada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar llave']);
        }
        break;
        
    case 'save_biometric':
        $key_data = $_POST['key_data'] ?? '';
        
        // Eliminar biométrica anterior si existe
        $stmt = $conexion->prepare("DELETE FROM security_keys WHERE user_id = ? AND key_type = 'biometric'");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        
        $stmt = $conexion->prepare("INSERT INTO security_keys (user_id, key_type, key_name, key_data) VALUES (?, 'biometric', 'Biometría', ?)");
        $stmt->bind_param("ss", $user_id, $key_data);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Biometría habilitada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al habilitar biometría']);
        }
        break;
        
    case 'get_keys':
        $stmt = $conexion->prepare("SELECT id, key_type, key_name, enabled, created_at FROM security_keys WHERE user_id = ? AND enabled = 1");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $keys = [];
        while ($row = $result->fetch_assoc()) {
            $keys[] = $row;
        }
        
        echo json_encode(['success' => true, 'keys' => $keys]);
        break;
        
    case 'delete_key':
        $key_id = $_POST['key_id'] ?? 0;
        
        $stmt = $conexion->prepare("DELETE FROM security_keys WHERE id = ? AND user_id = ?");
        $stmt->bind_param("is", $key_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Llave eliminada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar']);
        }
        break;
        
    case 'delete_trusted_device':
        $device_id = $_POST['device_id'] ?? 0;
        
        $stmt = $conexion->prepare("DELETE FROM trusted_devices WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $device_id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Dispositivo eliminado']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al eliminar dispositivo']);
        }
        break;
        
    case 'get_challenge':
        // Para login - obtener challenge sin autenticación de sesión
        if (isset($_GET['usuario'])) {
            $usuario = $_GET['usuario'];
            
            // Generar challenge
            $challenge = random_bytes(32);
            $challengeBase64 = base64_encode($challenge);
            
            // Guardar challenge en sesión temporal
            session_start();
            $_SESSION['webauthn_challenge'] = $challengeBase64;
            
            // Obtener credenciales del usuario
            $stmt = $conexion->prepare("SELECT credential_id FROM security_keys WHERE idUsuario = ? AND enabled = 1 AND key_type IN ('webauthn', 'biometric')");
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $credentials = [];
            while ($row = $result->fetch_assoc()) {
                $credentials[] = ['credential_id' => $row['credential_id']];
            }
            
            echo json_encode([
                'success' => true,
                'challenge' => $challengeBase64,
                'credentials' => $credentials
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuario no especificado']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>
