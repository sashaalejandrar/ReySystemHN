<?php
session_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
ob_start();

try {
    if (!isset($_SESSION['usuario'])) {
        throw new Exception('Usuario no autenticado');
    }

    $conexion = @new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }

    $conexion->set_charset("utf8");

    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!$data) {
        throw new Exception('No se recibieron datos válidos');
    }

    $total = floatval($data['total'] ?? 0);
    $nota_justificacion = $data['nota_justificacion'] ?? '';
    $conteo = $data['conteo'] ?? [];

    $x1 = intval($conteo['x1'] ?? 0);
    $x2 = intval($conteo['x2'] ?? 0);
    $x5 = intval($conteo['x5'] ?? 0);
    $x10 = intval($conteo['x10'] ?? 0);
    $x20 = intval($conteo['x20'] ?? 0);
    $x50 = intval($conteo['x50'] ?? 0);
    $x100 = intval($conteo['x100'] ?? 0);
    $x200 = intval($conteo['x200'] ?? 0);
    $x500 = intval($conteo['x500'] ?? 0);

    $fecha_actual = date('Y-m-d');
    $efectivo = $total;
    $transferencia = 0;
    $tarjeta = 0;

    $conexion->begin_transaction();

    $sql = "INSERT INTO cierre_caja 
            (X1, X2, X5, X10, X20, X50, X100, X200, X500, Total, Nota_Justificacion, Efectivo, Transferencia, Tarjeta, Fecha) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Error al preparar consulta: " . $conexion->error);
    }
    
    $stmt->bind_param("iiiiiiiiidsdddds", 
        $x1, $x2, $x5, $x10, $x20, $x50, $x100, $x200, $x500,
        $total,
        $nota_justificacion,
        $efectivo,
        $transferencia,
        $tarjeta,
        $fecha_actual
    );

    if (!$stmt->execute()) {
        throw new Exception("Error al guardar el cierre: " . $stmt->error);
    }
    
    $id_insertado = $stmt->insert_id;
    $stmt->close();

    $conexion->commit();
    $conexion->close();

    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Cierre de caja guardado exitosamente',
        'id' => $id_insertado,
        'total' => $total,
        'fecha' => $fecha_actual
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (isset($conexion) && $conexion->ping()) {
        $conexion->rollback();
        $conexion->close();
    }
    
    ob_end_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

exit;
?>