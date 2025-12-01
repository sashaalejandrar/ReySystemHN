<?php
session_start();
header('Content-Type: application/json');
include 'funciones.php'; // Incluye tu archivo de verificación de sesión
VerificarSiUsuarioYaInicioSesion();

// Conexión a la base de datos
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Obtener el ID del usuario activo
 $id_usuario_actual = $_SESSION['user_id']; // Asegúrate que tengas el ID del usuario en la sesión

// Obtener el timestamp de la última petición
 $ultimo_timestamp = isset($_GET['ultimo_timestamp']) ? floatval($_GET['ultimo_timestamp']) : 0;

// Consultar mensajes nuevos desde el último timestamp
 $query = "SELECT * FROM mensajes_chat WHERE Id_Receptor = ? AND Fecha_Mensaje > ? ORDER BY Fecha_Mensaje ASC";
 $stmt = $conexion->prepare($query);
 $stmt->bind_param("ii", $id_usuario_actual, $ultimo_timestamp);
 $stmt->execute();

 $resultado = $stmt->get_result();
 $mensajes_nuevos = $resultado->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'mensajes' => $mensajes_nuevos,
    'ultimo_timestamp' => time() // Devuelve el timestamp actual para la próxima petición
]);
?>