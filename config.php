<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'tiendasrey');
define('DB_USER', 'root');
define('DB_PASS', ''); // Cambia esto según tu configuración

// Crear conexión con mysqli
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Verificar conexión
if (!$conn) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . mysqli_connect_error()
    ]));
}

// Configurar charset UTF-8
mysqli_set_charset($conn, 'utf8mb4');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para obtener el usuario actual usando $_SESSION['usuario']
function getUsuarioActual($conn) {
    // Verificar si existe la sesión del usuario
    if (!isset($_SESSION['usuario'])) {
        // Si no hay sesión, retornar null o un usuario por defecto
        return null;
    }
    
    // Obtener el nombre de usuario de la sesión
    $usuario_session = mysqli_real_escape_string($conn, $_SESSION['usuario']);
    
    // Buscar el usuario en la base de datos por el campo Nombre
    $query = "SELECT * FROM usuarios WHERE Usuario = '$usuario_session' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        // Si no se encuentra el usuario, retornar null
        return null;
    }
    
    return mysqli_fetch_assoc($result);
}

// Función alternativa si quieres buscar por ID guardado en sesión
function getUsuarioPorId($conn, $id) {
    $id = mysqli_real_escape_string($conn, $id);
    $query = "SELECT * FROM usuarios WHERE id = '$id' LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        return null;
    }
    
    return mysqli_fetch_assoc($result);
}

// Función para verificar si el usuario está logueado
function verificarLogin() {
    if (!isset($_SESSION['usuario'])) {
        // Redirigir al login si no está logueado
        header('Location: login.php');
        exit;
    }
}

// Función para obtener el tiempo relativo
function tiempoRelativo($fecha) {
    $ahora = new DateTime();
    $fecha_pub = new DateTime($fecha);
    $diferencia = $ahora->diff($fecha_pub);
    
    if ($diferencia->y > 0) {
        return $diferencia->y . ' año' . ($diferencia->y > 1 ? 's' : '');
    } elseif ($diferencia->m > 0) {
        return $diferencia->m . ' mes' . ($diferencia->m > 1 ? 'es' : '');
    } elseif ($diferencia->d > 0) {
        if ($diferencia->d == 1) return 'ayer';
        return $diferencia->d . ' días';
    } elseif ($diferencia->h > 0) {
        return $diferencia->h . ' hora' . ($diferencia->h > 1 ? 's' : '');
    } elseif ($diferencia->i > 0) {
        return $diferencia->i . ' minuto' . ($diferencia->i > 1 ? 's' : '');
    } else {
        return 'ahora mismo';
    }
}

// Función para escapar datos de forma segura
function escape($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data));
}
?>