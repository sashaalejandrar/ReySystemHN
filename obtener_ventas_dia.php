<?php
ob_start();
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
// Zona horaria correcta para Honduras
date_default_timezone_set('America/Tegucigalpa');
try {
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8");
    $fecha_hoy = date('Y-m-d');
    
    // Obtener el monto inicial de la caja del día
    $query_caja = "SELECT Total FROM caja 
                   WHERE DATE(Fecha) = ? 
                   ORDER BY Id DESC LIMIT 1";
    
    $stmt_caja = $conexion->prepare($query_caja);
    
    if (!$stmt_caja) {
        throw new Exception("Error en prepare caja: " . $conexion->error);
    }
    
    $stmt_caja->bind_param("s", $fecha_hoy);
    $stmt_caja->execute();
    $result_caja = $stmt_caja->get_result();
    
    $monto_inicial = 0;
    if ($result_caja->num_rows > 0) {
        $row_caja = $result_caja->fetch_assoc();
        $monto_inicial = $row_caja['Total'];
    }
    
    // Obtener el total de ventas del día
    $query_ventas = "SELECT COALESCE(SUM(Total), 0) as total_ventas 
                     FROM ventas 
                     WHERE DATE(Fecha_Venta) = ?";
    
    $stmt_ventas = $conexion->prepare($query_ventas);
    
    if (!$stmt_ventas) {
        throw new Exception("Error en prepare ventas: " . $conexion->error);
    }
    
    $stmt_ventas->bind_param("s", $fecha_hoy);
    $stmt_ventas->execute();
    $result_ventas = $stmt_ventas->get_result();
    $row_ventas = $result_ventas->fetch_assoc();
    
    $stmt_caja->close();
    $stmt_ventas->close();
    $conexion->close();
    
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'monto_inicial' => $monto_inicial,
        'total_ventas' => floatval($row_ventas['total_ventas'])
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>