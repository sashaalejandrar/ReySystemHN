<?php
session_start();
header('Content-Type: application/json');

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No has iniciado sesión']);
    exit;
}

// Conexión a la base de datos
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener ID del usuario
 $usuario_id = isset($_GET['usuario']) ? intval($_GET['usuario']) : 0;

if ($usuario_id > 0) {
    // Obtener última actividad del usuario
    $stmt = $conexion->prepare("SELECT Ultima_Actividad FROM usuarios WHERE Id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $usuario = $resultado->fetch_assoc();
    
    if ($usuario) {
        $ultima_actividad = $usuario['Ultima_Actividad'];
        $ahora = new DateTime();
        $ultima = new DateTime($ultima_actividad);
        $diferencia = $ahora->diff($ultima);
        
        $total_segundos = $diferencia->days * 86400 + $diferencia->h * 3600 + $diferencia->i * 60 + $diferencia->s;
        
        if ($total_segundos < 30) {
            $estado_clase = 'status-online';
            $estado_texto = "En línea";
        } elseif ($total_segundos < 60) {
            $estado_clase = 'status-online';
            $estado_texto = "Hace un momento";
        } elseif ($total_segundos < 3600) {
            $minutos_ago = floor($total_segundos / 60);
            $estado_clase = 'status-away';
            $estado_texto = "Hace {$minutos_ago} minuto" . ($minutos_ago == 1 ? "" : "s");
        } elseif ($total_segundos < 86400) {
            $horas_ago = floor($total_segundos / 3600);
            $minutos_restantes = ($total_segundos % 3600) / 60;
            $estado_clase = 'status-away';
            if ($minutos_restantes > 0) {
                $estado_texto = "Hace {$horas_ago} hora" . ($horas_ago == 1 ? "" : "s") . " y " . floor($minutos_restantes) . " min";
            } else {
                $estado_texto = "Hace {$horas_ago} hora" . ($horas_ago == 1 ? "" : "s");
            }
        } else {
            $dias_ago = floor($total_segundos / 86400);
            $horas_restantes = ($total_segundos % 86400) / 3600;
            $estado_clase = 'status-offline';
            if ($horas_restantes > 0) {
                $estado_texto = "Hace {$dias_ago} día" . ($dias_ago == 1 ? "" : "s") . " y " . floor($horas_restantes) . " h";
            } else {
                $estado_texto = "Hace {$dias_ago} día" . ($dias_ago == 1 ? "" : "s");
            }
        }
        
        echo json_encode([
            'success' => true,
            'estado_clase' => $estado_clase,
            'estado_texto' => $estado_texto
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID de usuario no válido']);
}
?>