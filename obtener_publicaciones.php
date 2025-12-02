<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    // Obtener publicaciones con información del usuario y conteo de likes y comentarios
    $query = "SELECT 
        p.*,
        u.nombre,
        u.avatar,
        u.puesto,
        COUNT(DISTINCT l.id) as total_likes,
        COUNT(DISTINCT c.id) as total_comentarios
    FROM publicaciones p
    INNER JOIN usuarios u ON p.usuario_id = u.id
    LEFT JOIN likes l ON p.id = l.publicacion_id
    LEFT JOIN comentarios c ON p.id = c.publicacion_id
    GROUP BY p.id
    ORDER BY p.fecha_creacion DESC
    LIMIT 20";
    
    $result = mysqli_query($conn, $query);
    
    if (!$result) {
        throw new Exception('Error en la consulta: ' . mysqli_error($conn));
    }
    
    $publicaciones = [];
    
    while ($pub = mysqli_fetch_assoc($result)) {
        // Obtener archivos multimedia para cada publicación
        $pub_id = mysqli_real_escape_string($conn, $pub['id']);
        $query_archivos = "SELECT * FROM archivos_multimedia WHERE publicacion_id = '$pub_id'";
        $result_archivos = mysqli_query($conn, $query_archivos);
        
        $archivos = [];
        while ($archivo = mysqli_fetch_assoc($result_archivos)) {
            $archivos[] = [
                'id' => $archivo['id'],
                'nombre_archivo' => $archivo['nombre_archivo'],
                'ruta_archivo' => $archivo['ruta_archivo'],
                'tipo_archivo' => $archivo['tipo_archivo'],
                'tipo_mime' => $archivo['tipo_mime']
            ];
        }
        
        $publicaciones[] = [
            'id' => $pub['id'],
            'usuario' => $pub['nombre'],
            'avatar' => $pub['avatar'],
            'puesto' => $pub['puesto'],
            'contenido' => $pub['contenido'],
            'tipo' => $pub['tipo'],
            'fecha' => tiempoRelativo($pub['fecha_creacion']),
            'likes' => (int)$pub['total_likes'],
            'comentarios' => (int)$pub['total_comentarios'],
            'archivos' => $archivos
        ];
    }
    
    echo json_encode([
        'success' => true,
        'publicaciones' => $publicaciones
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>