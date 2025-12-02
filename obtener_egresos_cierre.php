<?php
// Activa el reporte de todos los errores
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
date_default_timezone_set('America/Tegucigalpa');
// --- INICIO: CONFIGURACIÓN DE CABECERAS CORS ---
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=UTF-8');
// --- FIN DE LA CONFIGURACIÓN ---

require_once 'config.php';
session_start();

// Función para enviar una respuesta JSON segura
function send_json_response($data) {
    $error = error_get_last();
    if ($error) {
        $data['php_error'] = $error['message'];
    }
    echo json_encode($data);
    exit;
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION['usuario'])) {
    send_json_response(['success' => false, 'error' => 'No autorizado.']);
}

try {
    $hoy = date("Y-m-d");

    // 1. Obtener el ID de la caja abierta de hoy
    $stmt_caja = $conn->prepare("SELECT id FROM caja WHERE Fecha = ? AND Estado = 'Abierta'");
    $stmt_caja->bind_param("s", $hoy);
    $stmt_caja->execute();
    $resultado_caja = $stmt_caja->get_result();

    if ($resultado_caja->num_rows === 0) {
        send_json_response(['success' => false, 'error' => 'No hay una caja abierta para hoy.']);
    }
    $caja = $resultado_caja->fetch_assoc();
    $id_caja = $caja['id'];

    // 2. Calcular el total de egresos del día
    $stmt_egresos = $conn->prepare("SELECT SUM(monto) as total_egresos FROM egresos_caja WHERE caja_id = ?");
    $stmt_egresos->bind_param("i", $id_caja);
    $stmt_egresos->execute();
    $resultado_egresos = $stmt_egresos->get_result();
    $total_egresos = 0.00;
    if ($resultado_egresos->num_rows > 0) {
        $egresos_data = $resultado_egresos->fetch_assoc();
        $total_egresos = floatval($egresos_data['total_egresos']);
    }

    // 3. Enviar la respuesta exitosa
    send_json_response([
        'success' => true,
        'total_egresos' => $total_egresos
    ]);

} catch (Exception $e) {
    send_json_response(['success' => false, 'error' => 'Excepción capturada: ' . $e->getMessage()]);
}
?>