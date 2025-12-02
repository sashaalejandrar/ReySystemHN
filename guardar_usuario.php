<?php
// Forzar que cualquier error se muestre como una excepción
// Esto ayuda a que el catch() lo atrape todo.
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Establecer el encabezado para devolver una respuesta JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// --- FUNCIONES ---
function sendResponse($success, $message, $data = null) {
    // Limpiar cualquier salida que haya ocurrido antes
    ob_clean();
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
    exit();
}

// --- LÓGICA PRINCIPAL DENTRO DE TRY-CATCH ---
try {
    date_default_timezone_set('America/Tegucigalpa');

    // 1. Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitud no permitido.');
    }

    // 2. Obtener y validar datos (ahora desde $_POST y $_FILES en lugar de JSON)
    if (
        empty($_POST['nombre']) ||
        empty($_POST['apellido']) ||
        empty($_POST['celular']) ||
        empty($_POST['cargo']) || // CAMBIADO: Se añade la validación del nuevo campo
        empty($_POST['email']) ||
        empty($_POST['usuario']) ||
        empty($_POST['contraseña']) ||
        empty($_POST['rol']) ||
        empty($_POST['fecha_nacimiento'])
    ) {
        throw new Exception('Todos los campos obligatorios deben ser completados.');
    }
    if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El formato del correo electrónico no es válido.');
    }

    // 3. Procesar la imagen si se ha subido
    $foto_ruta = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        // Validar que sea una imagen
        $tipos_permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $tipo_mime = mime_content_type($_FILES['foto']['tmp_name']);
        
        if (!in_array($tipo_mime, $tipos_permitidos)) {
            throw new Exception('El archivo subido no es una imagen válida.');
        }
        
        // Crear directorio si no existe
        $directorio_destino = 'uploads/perfiles/';
        if (!file_exists($directorio_destino)) {
            mkdir($directorio_destino, 0755, true);
        }
        
        // Generar nombre único para el archivo
        $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
        $nombre_archivo = uniqid('perfil_') . '.' . $extension;
        $ruta_completa = $directorio_destino . $nombre_archivo;
        
        // Mover el archivo a su destino
        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_completa)) {
            throw new Exception('Error al guardar la imagen de perfil.');
        }
        
        $foto_ruta = $ruta_completa;
    }

    // 4. Conexión a la BD
    $db_host = 'localhost';
    $db_name = 'tiendasrey';
    $db_user = 'root';
    $db_pass = ''; // <-- ¡REVISAR ESTA CONTRASEÑA!

    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($mysqli->connect_error) {
        throw new Exception('Error de conexión a la BD: ' . $mysqli->connect_error);
    }
    $mysqli->set_charset("utf8mb4");

    // 5. Verificar si el email O el usuario ya existen
    $stmt = $mysqli->prepare("SELECT Id, Email, Usuario FROM usuarios WHERE Email = ? OR Usuario = ?");
    if ($stmt === false) {
        throw new Exception('Error al preparar consulta de verificación: ' . $mysqli->error);
    }
    $stmt->bind_param("ss", $_POST['email'], $_POST['usuario']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $stmt->close();
        if ($row['Email'] === $_POST['email']) {
            throw new Exception('El correo electrónico ya está en uso.');
        }
        if ($row['Usuario'] === $_POST['usuario']) {
            throw new Exception('El nombre de usuario ya está en uso.');
        }
    }
    $stmt->close();

    // 6. Preparar datos para la inserción
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $celular = trim($_POST['celular']);
    $cargo = trim($_POST['cargo']); // CAMBIADO: Se añade la nueva variable
    $email = trim($_POST['email']);
    $usuario = trim($_POST['usuario']);
    $rol = $_POST['rol'];
    $foto = $foto_ruta;
    $hashed_password = hash('sha256', $_POST['contraseña']);
    $fecha_ingreso = date("Y-m-d H:i:s");
    $fecha_nacimiento = trim($_POST['fecha_nacimiento']);
    $Estado = "Pendiente";
    // 7. Insertar (CAMBIADO: Se añade Cargo a la consulta)
    $sql = "INSERT INTO usuarios (Nombre, Apellido, Celular, Cargo, Email, Usuario, Clave, Rol, Perfil, Fecha_Ingreso, Fecha_Nacimiento, Estado_Online) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Error al preparar inserción: ' . $mysqli->error);
    }
    // 'sssssssssss' porque ahora son 11 parámetros de tipo string
    $stmt->bind_param("ssssssssssss", $nombre, $apellido, $celular, $cargo, $email, $usuario, $hashed_password, $rol, $foto, $fecha_ingreso, $fecha_nacimiento, $Estado);

    if ($stmt->execute()) {
        sendResponse(true, 'Usuario creado exitosamente.');
    } else {
        throw new Exception('No se pudo crear el usuario: ' . $stmt->error);
    }

    $stmt->close();
    $mysqli->close();

} catch (Exception $e) {
    // Cualquier error que ocurra en el bloque "try" será capturado aquí
    // y devuelto como un JSON limpio y válido.
    sendResponse(false, $e->getMessage());
}

?>