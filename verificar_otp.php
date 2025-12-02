<?php
/**
 * Verificador de códigos OTP para apertura y cierre de caja
 * Valida que el código sea correcto, no usado y no expirado
 */

header('Content-Type: application/json');
require_once 'db_connect.php';

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos inválidos']);
    exit;
}

$codigo = trim($input['codigo'] ?? '');
$tipo = $input['tipo'] ?? '';

// Validar campos requeridos
if (empty($codigo)) {
    echo json_encode([
        'success' => false,
        'valido' => false,
        'mensaje' => 'Código OTP requerido'
    ]);
    exit;
}

if (empty($tipo) || !in_array($tipo, ['apertura', 'cierre', 'arqueo'])) {
    echo json_encode([
        'success' => false,
        'valido' => false,
        'mensaje' => 'Tipo de operación inválido'
    ]);
    exit;
}

try {
    // Buscar el código en la base de datos
    $sql = "SELECT id, codigo, tipo, fecha_expiracion, usado 
            FROM codigos_otp 
            WHERE codigo = ? AND tipo = ?
            LIMIT 1";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $codigo, $tipo);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'valido' => false,
            'mensaje' => 'Código OTP no encontrado o tipo incorrecto'
        ]);
        exit;
    }
    
    $otp = $result->fetch_assoc();
    
    // Verificar si ya fue usado
    if ($otp['usado'] == 1) {
        echo json_encode([
            'success' => true,
            'valido' => false,
            'mensaje' => 'Este código OTP ya fue utilizado'
        ]);
        exit;
    }
    
    // Verificar si expiró
    $ahora = date('Y-m-d H:i:s');
    if ($ahora > $otp['fecha_expiracion']) {
        echo json_encode([
            'success' => true,
            'valido' => false,
            'mensaje' => 'Código OTP expirado. Debe solicitar uno nuevo.'
        ]);
        exit;
    }
    
    // Código válido - marcarlo como usado
    $update_sql = "UPDATE codigos_otp SET usado = 1 WHERE id = ?";
    $update_stmt = $conexion->prepare($update_sql);
    $update_stmt->bind_param("i", $otp['id']);
    
    if (!$update_stmt->execute()) {
        throw new Exception("Error al marcar código como usado");
    }
    
    // Calcular tiempo restante antes de expirar
    $expiracion = strtotime($otp['fecha_expiracion']);
    $actual = strtotime($ahora);
    $minutos_restantes = round(($expiracion - $actual) / 60);
    
    echo json_encode([
        'success' => true,
        'valido' => true,
        'mensaje' => 'Código OTP válido',
        'codigo' => $codigo,
        'tiempo_restante' => $minutos_restantes . ' minutos'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'valido' => false,
        'mensaje' => 'Error al verificar OTP: ' . $e->getMessage()
    ]);
}
?>
