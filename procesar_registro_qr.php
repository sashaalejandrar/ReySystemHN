<?php
/**
 * Procesar registro de cliente desde QR
 * Crea el cliente y otorga 10 puntos de bienvenida
 */

header('Content-Type: application/json');
require_once 'db_connect.php';
require_once 'funciones_puntos.php';

$token = $_POST['token'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$celular = trim($_POST['celular'] ?? '');
$direccion = trim($_POST['direccion'] ?? '');

// Validaciones
if (empty($token) || empty($nombre) || empty($celular) || empty($direccion)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
    exit;
}

// Validar formato de celular (8 dígitos)
if (!preg_match('/^[0-9]{8}$/', $celular)) {
    echo json_encode(['success' => false, 'message' => 'Celular debe tener 8 dígitos']);
    exit;
}

try {
    $conexion->begin_transaction();
    
    // Verificar que el token existe y no ha sido usado
    $stmt = $conexion->prepare("SELECT * FROM tokens_registro WHERE token = ? AND usado = 0 FOR UPDATE");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        throw new Exception('Token inválido o ya usado');
    }
    
    $stmt->close();
    
    // Verificar que el cliente no existe ya
    $stmt = $conexion->prepare("SELECT Id FROM clientes WHERE Celular = ?");
    $stmt->bind_param("s", $celular);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('Este número de celular ya está registrado');
    }
    $stmt->close();
    
    // Crear cliente en la tabla clientes
    $identidad = 'N/A'; // No se pide identidad
    $stmt = $conexion->prepare("
        INSERT INTO clientes (Nombre, Identidad, Celular, Direccion, Fecha_Registro) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssss", $nombre, $identidad, $celular, $direccion);
    $stmt->execute();
    $stmt->close();
    
    // Otorgar 10 puntos de bienvenida
    $puntos_bienvenida = 10;
    $resultado_puntos = acumularPuntos($nombre, 0, null, 'Sistema QR');
    
    // Si la función de puntos no funcionó, crear manualmente
    if (!$resultado_puntos['success']) {
        $stmt = $conexion->prepare("
            INSERT INTO puntos_clientes (cliente_nombre, puntos_disponibles, puntos_totales_ganados) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                puntos_disponibles = puntos_disponibles + ?,
                puntos_totales_ganados = puntos_totales_ganados + ?
        ");
        $stmt->bind_param("siiii", $nombre, $puntos_bienvenida, $puntos_bienvenida, $puntos_bienvenida, $puntos_bienvenida);
        $stmt->execute();
        $stmt->close();
        
        // Registrar en historial
        $stmt = $conexion->prepare("
            INSERT INTO historial_puntos (cliente_nombre, tipo, puntos, descripcion, usuario) 
            VALUES (?, 'ganado', ?, 'Puntos de bienvenida por registro QR', 'Sistema')
        ");
        $stmt->bind_param("si", $nombre, $puntos_bienvenida);
        $stmt->execute();
        $stmt->close();
    }
    
    // Marcar token como usado
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $conexion->prepare("
        UPDATE tokens_registro 
        SET usado = 1, fecha_uso = NOW(), cliente_registrado = ?, ip_registro = ? 
        WHERE token = ?
    ");
    $stmt->bind_param("sss", $nombre, $ip, $token);
    $stmt->execute();
    $stmt->close();
    
    $conexion->commit();
    
    echo json_encode([
        'success' => true,
        'cliente' => $nombre,
        'puntos' => $puntos_bienvenida,
        'message' => 'Registro exitoso'
    ]);
    
} catch (Exception $e) {
    $conexion->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conexion->close();
?>
