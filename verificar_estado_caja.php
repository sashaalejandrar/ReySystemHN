<?php
// Evitar cualquier salida antes del JSON
ob_start();
header('Content-Type: application/json; charset=utf-8');
// Zona horaria correcta para Honduras
date_default_timezone_set('America/Tegucigalpa');
try {
    // Conexión a la base de datos
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8");
    $fecha_hoy = date('Y-m-d');
    
    // Buscar registro de caja del día
    $query = "SELECT Estado FROM caja 
              WHERE DATE(Fecha) = ? 
              ORDER BY Id DESC LIMIT 1";
    
    $stmt = $conexion->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Error en prepare: " . $conexion->error);
    }
    
    $stmt->bind_param("s", $fecha_hoy);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // No existe registro para hoy
        $response = [
            'existe_registro' => false,
            'estado' => null,
            'mensaje' => 'Debe abrir la caja antes de realizar el cierre'
        ];
    } else {
        $row = $result->fetch_assoc();
        $estado = $row['Estado']; // Abierta o Cerrada
        
        $response = [
            'existe_registro' => true,
            'estado' => $estado,
            'mensaje' => $estado === 'Cerrada' ? 'La caja ya está cerrada' : 'Caja abierta'
        ];
    }
    
    $stmt->close();
    $conexion->close();
    
    // Limpiar cualquier salida previa
    ob_end_clean();
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Limpiar cualquier salida previa
    ob_end_clean();
    
    $response = [
        'existe_registro' => false,
        'estado' => null,
        'error' => $e->getMessage()
    ];
    
    echo json_encode($response);
}
?>