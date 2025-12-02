<?php
// Desactivar toda visualización de errores
error_reporting(0);
ini_set('display_errors', 0);
ini_set('html_errors', 0);
date_default_timezone_set('America/Tegucigalpa');

// Iniciar buffering de salida
ob_start();

// Establecer encabezado JSON
header('Content-Type: application/json');

// Función para enviar respuesta JSON y salir
function enviarRespuesta($datos) {
    // Limpiar cualquier salida anterior
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    // Enviar respuesta JSON
    echo json_encode($datos);
    exit;
}

// Función para manejar errores
function manejarError($mensaje) {
    error_log("[ProcesarEgreso] " . date('Y-m-d H:i:s') . " - " . $mensaje);
    enviarRespuesta(['success' => false, 'message' => $mensaje]);
}

try {
    // Iniciar sesión
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar autenticación
    if (!isset($_SESSION['usuario'])) {
        throw new Exception("Usuario no autenticado");
    }
    
    // Verificar que se hayan enviado los datos necesarios
    if (!isset($_POST['amount']) || !isset($_POST['date']) || !isset($_POST['description']) || !isset($_POST['expense_type'])) {
        throw new Exception("Faltan datos requeridos");
    }
    
    // Conexión a la base de datos
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    // Verificar conexión
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión a la base de datos: " . $conexion->connect_error);
    }
    
    // Establecer charset
    $conexion->set_charset("utf8mb4");
    
    // CAMBIO: Obtener el ID del usuario a partir del nombre de usuario
    $query_usuario = "SELECT Id FROM usuarios WHERE usuario = ?";
    $stmt_usuario = $conexion->prepare($query_usuario);
    $stmt_usuario->bind_param("s", $_SESSION['usuario']);
    $stmt_usuario->execute();
    $resultado_usuario = $stmt_usuario->get_result();
    
    if ($resultado_usuario->num_rows === 0) {
        throw new Exception("Usuario no encontrado en la base de datos");
    }
    
    $usuario_data = $resultado_usuario->fetch_assoc();
    $id_usuario = $usuario_data['Id'];
    $stmt_usuario->close();
    
    // Obtener ID de la caja actual
    $hoy = date("Y-m-d");
    $query_caja = "SELECT Id FROM caja WHERE DATE(Fecha) = ? ORDER BY Id DESC LIMIT 1";
    $stmt_caja = $conexion->prepare($query_caja);
    $stmt_caja->bind_param("s", $hoy);
    $stmt_caja->execute();
    $resultado_caja = $stmt_caja->get_result();
    
    if ($resultado_caja->num_rows === 0) {
        throw new Exception("No hay caja abierta para el día de hoy");
    }
    
    $caja = $resultado_caja->fetch_assoc();
    $id_caja = $caja['Id'];
    $stmt_caja->close();
    
    // Insertar el egreso usando el ID del usuario
    $query = "INSERT INTO egresos_caja (caja_id, monto, concepto, fecha_registro, tipo, usuario_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conexion->prepare($query);
    
    $monto = floatval($_POST['amount']);
    $descripcion = $_POST['description'];
    $fecha = $_POST['date'];
    $tipo = $_POST['expense_type'];
    
    // CAMBIO: Usar el ID del usuario en lugar del nombre
    $stmt->bind_param("idsssi", $id_caja, $monto, $descripcion, $fecha, $tipo, $id_usuario);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al guardar el egreso: " . $stmt->error);
    }
    
    $id_egreso = $conexion->insert_id;
    $stmt->close();
    
    // Procesar archivos adjuntos si existen
    if (isset($_FILES['recibos']) && !empty($_FILES['recibos']['name'][0])) {
        $directorio_destino = 'uploads/egresos/';
        
        // Crear directorio si no existe
        if (!file_exists($directorio_destino)) {
            mkdir($directorio_destino, 0777, true);
        }
        
        foreach ($_FILES['recibos']['name'] as $key => $nombre) {
            if ($_FILES['recibos']['error'][$key] === UPLOAD_ERR_OK) {
                $nombre_temporal = $_FILES['recibos']['tmp_name'][$key];
                $tipo_archivo = $_FILES['recibos']['type'][$key];
                $tamano = $_FILES['recibos']['size'][$key];
                
                // Validar tipo de archivo
                $tipos_permitidos = ['image/jpeg', 'image/png', 'application/pdf'];
                if (!in_array($tipo_archivo, $tipos_permitidos)) {
                    continue; // Omitir archivos no permitidos
                }
                
                // Generar nombre único
                $extension = pathinfo($nombre, PATHINFO_EXTENSION);
                $nombre_unico = uniqid() . '.' . $extension;
                $ruta_destino = $directorio_destino . $nombre_unico;
                
                // Mover archivo
                if (move_uploaded_file($nombre_temporal, $ruta_destino)) {
                    // Guardar referencia en la base de datos
                    $query_archivo = "INSERT INTO egresos_archivos (egreso_id, nombre_archivo, ruta_archivo, tipo_archivo, tamano) VALUES (?, ?, ?, ?, ?)";
                    $stmt_archivo = $conexion->prepare($query_archivo);
                    $stmt_archivo->bind_param("isssi", $id_egreso, $nombre, $ruta_destino, $tipo_archivo, $tamano);
                    $stmt_archivo->execute();
                    $stmt_archivo->close();
                }
            }
        }
    }
    
    // Cerrar conexión
    $conexion->close();
    
    enviarRespuesta(['success' => true, 'message' => 'Egreso guardado correctamente']);
    
} catch (Exception $e) {
    manejarError($e->getMessage());
}
?>