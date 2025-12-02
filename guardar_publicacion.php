<?php
// Desactivar visualización de errores y solo devolver JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validar datos
if (!isset($input['contenido']) || empty(trim($input['contenido']))) {
    echo json_encode(['success' => false, 'message' => 'El contenido no puede estar vacío']);
    exit;
}

try {
    // Obtener usuario actual
    $usuario = getUsuarioActual($conn);
    
    if (!$usuario) {
        throw new Exception('Usuario no encontrado');
    }
    
    $contenido = escape($conn, $input['contenido']);
    $tipo = isset($input['tipo']) ? escape($conn, $input['tipo']) : 'post';
    
    // Insertar publicación
    $query = "INSERT INTO publicaciones (usuario_id, contenido, tipo) VALUES ('{$usuario['Id']}', '$contenido', '$tipo')";
    
    if (!mysqli_query($conn, $query)) {
        throw new Exception('Error al crear publicación: ' . mysqli_error($conn));
    }
    
    // Obtener el ID de la publicación recién creada
    $publicacion_id = mysqli_insert_id($conn);
    
    // Si hay archivos multimedia, guardarlos
    if (isset($input['archivos']) && is_array($input['archivos'])) {
        foreach ($input['archivos'] as $archivo) {
            $nombre = escape($conn, $archivo['nombre_archivo']);
            $ruta = escape($conn, $archivo['ruta']);
            $tipo_archivo = escape($conn, $archivo['tipo']);
            $tipo_mime = escape($conn, $archivo['tipo_mime']);
            $tamano = (int)$archivo['tamano'];
            
            $query_archivo = "INSERT INTO archivos_multimedia 
                             (publicacion_id, nombre_archivo, ruta_archivo, tipo_archivo, tipo_mime, tamano) 
                             VALUES ('$publicacion_id', '$nombre', '$ruta', '$tipo_archivo', '$tipo_mime', '$tamano')";
            
            mysqli_query($conn, $query_archivo);
        }
    }
    
    // Obtener la publicación completa con archivos
    $query = "SELECT p.*, u.Nombre, u.Perfil, u.Id
              FROM publicaciones p
              INNER JOIN usuarios u ON p.usuario_id = u.Id
              WHERE p.id = '$publicacion_id'";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Error al obtener publicación: ' . mysqli_error($conn));
    }
    
    $publicacion = mysqli_fetch_assoc($result);
    
    // Obtener archivos multimedia
    $query_archivos = "SELECT * FROM archivos_multimedia WHERE publicacion_id = '$publicacion_id'";
    $result_archivos = mysqli_query($conn, $query_archivos);
    $archivos = [];
    while ($archivo = mysqli_fetch_assoc($result_archivos)) {
        $archivos[] = $archivo;
    }
    
    // Enviar respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => 'Publicación creada exitosamente',
        'publicacion' => [
            'id' => $publicacion['id'],
            'usuario' => $publicacion['Nombre'],
            'avatar' => $publicacion['Perfil'],
            'contenido' => $publicacion['contenido'],
            'fecha' => tiempoRelativo($publicacion['fecha_creacion']),
            'archivos' => $archivos
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

mysqli_close($conn);
?>