<?php
/**
 * Función helper para registrar movimientos de inventario
 * 
 * @param mysqli $conexion Conexión a la base de datos
 * @param string $producto Nombre del producto
 * @param string $tipo Tipo de movimiento: 'entrada', 'salida', 'ajuste'
 * @param int $cantidad Cantidad del movimiento
 * @param int $stock_anterior Stock antes del movimiento
 * @param int $stock_nuevo Stock después del movimiento
 * @param string $motivo Motivo del movimiento
 * @param string $usuario Usuario que realiza el movimiento
 * @return bool True si se registró correctamente, false en caso contrario
 */
function registrarMovimientoInventario($conexion, $producto, $tipo, $cantidad, $stock_anterior, $stock_nuevo, $motivo = '', $usuario = '') {
    // Validar tipo de movimiento
    $tipos_validos = ['entrada', 'salida', 'ajuste'];
    if (!in_array($tipo, $tipos_validos)) {
        error_log("Tipo de movimiento inválido: $tipo");
        return false;
    }
    
    // Si no se proporciona usuario, intentar obtenerlo de la sesión
    if (empty($usuario) && isset($_SESSION['usuario'])) {
        $usuario = $_SESSION['usuario'];
    }
    
    // Preparar la consulta
    $stmt = $conexion->prepare("INSERT INTO movimientos_inventario 
        (producto, tipo, cantidad, stock_anterior, stock_nuevo, motivo, usuario, fecha) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    
    if (!$stmt) {
        error_log("Error al preparar consulta de movimiento: " . $conexion->error);
        return false;
    }
    
    $stmt->bind_param("ssiisss", $producto, $tipo, $cantidad, $stock_anterior, $stock_nuevo, $motivo, $usuario);
    
    $resultado = $stmt->execute();
    
    if (!$resultado) {
        error_log("Error al registrar movimiento de inventario: " . $stmt->error);
    }
    
    $stmt->close();
    
    return $resultado;
}
?>
