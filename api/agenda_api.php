<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$usuario = $_SESSION['usuario'];
$action = $_GET['action'] ?? '';

// ==================== TAREAS ====================

if ($action === 'get_tasks') {
    $estado = $_GET['estado'] ?? '';
    $prioridad = $_GET['prioridad'] ?? '';
    
    $sql = "SELECT * FROM agenda_tareas WHERE usuario = ? AND tipo = 'tarea'";
    $params = [$usuario];
    $types = "s";
    
    if ($estado) {
        $sql .= " AND estado = ?";
        $params[] = $estado;
        $types .= "s";
    }
    
    if ($prioridad) {
        $sql .= " AND prioridad = ?";
        $params[] = $prioridad;
        $types .= "s";
    }
    
    $sql .= " ORDER BY fecha_creacion DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tasks = [];
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    
    echo json_encode(['success' => true, 'tasks' => $tasks]);
}

elseif ($action === 'create_task') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $sql = "INSERT INTO agenda_tareas (usuario, titulo, descripcion, tipo, prioridad, estado, fecha_vencimiento, etiquetas) 
            VALUES (?, ?, ?, 'tarea', ?, 'pendiente', ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssss", 
        $usuario,
        $data['titulo'],
        $data['descripcion'],
        $data['prioridad'],
        $data['fecha_vencimiento'],
        $data['etiquetas']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tarea creada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear tarea']);
    }
}

elseif ($action === 'update_task') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $sql = "UPDATE agenda_tareas SET titulo = ?, descripcion = ?, prioridad = ?, estado = ?, fecha_vencimiento = ?, etiquetas = ? 
            WHERE id = ? AND usuario = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssssssis", 
        $data['titulo'],
        $data['descripcion'],
        $data['prioridad'],
        $data['estado'],
        $data['fecha_vencimiento'],
        $data['etiquetas'],
        $data['id'],
        $usuario
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tarea actualizada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar tarea']);
    }
}

elseif ($action === 'update_task_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $sql = "UPDATE agenda_tareas SET estado = ? WHERE id = ? AND usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sis", $data['estado'], $data['id'], $usuario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
    }
}

elseif ($action === 'delete_task') {
    $id = $_GET['id'] ?? 0;
    
    $sql = "DELETE FROM agenda_tareas WHERE id = ? AND usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("is", $id, $usuario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Tarea eliminada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar tarea']);
    }
}

// ==================== NOTAS ====================

elseif ($action === 'get_notes') {
    $sql = "SELECT * FROM agenda_tareas WHERE usuario = ? AND tipo = 'nota' ORDER BY fecha_creacion DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notes = [];
    while ($row = $result->fetch_assoc()) {
        $notes[] = $row;
    }
    
    echo json_encode(['success' => true, 'notes' => $notes]);
}

elseif ($action === 'create_note') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $sql = "INSERT INTO agenda_tareas (usuario, titulo, descripcion, tipo, etiquetas) 
            VALUES (?, ?, ?, 'nota', ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ssss", 
        $usuario,
        $data['titulo'],
        $data['descripcion'],
        $data['etiquetas']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Nota creada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear nota']);
    }
}

elseif ($action === 'update_note') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $sql = "UPDATE agenda_tareas SET titulo = ?, descripcion = ?, etiquetas = ? 
            WHERE id = ? AND usuario = ? AND tipo = 'nota'";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssis", 
        $data['titulo'],
        $data['descripcion'],
        $data['etiquetas'],
        $data['id'],
        $usuario
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Nota actualizada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar nota']);
    }
}

elseif ($action === 'delete_note') {
    $id = $_GET['id'] ?? 0;
    
    $sql = "DELETE FROM agenda_tareas WHERE id = ? AND usuario = ? AND tipo = 'nota'";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("is", $id, $usuario);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Nota eliminada']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar nota']);
    }
}

// ==================== PRODUCTOS BAJO STOCK ====================

elseif ($action === 'get_low_stock_products') {
    $sql = "SELECT Codigo_Producto, Nombre_Producto, Stock 
            FROM stock 
            WHERE Stock < 10 
            ORDER BY Stock ASC, Nombre_Producto ASC 
            LIMIT 100";
    
    $result = $conexion->query($sql);
    
    $products = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    echo json_encode(['success' => true, 'products' => $products]);
}

// ==================== CORREOS ====================

elseif ($action === 'send_email') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Separar destinatarios por comas
    $destinatarios = array_map('trim', explode(',', $data['destinatario']));
    
    // Verificar que todos los correos sean válidos
    foreach ($destinatarios as $email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => "Correo inválido: $email"]);
            exit();
        }
    }
    
    // Cargar configuración de PHPMailer
    require_once __DIR__ . '/phpmailer_config.php';
    
    try {
        $mail = getMailerInstance();
        
        // Destinatarios
        foreach ($destinatarios as $email) {
            $mail->addAddress($email);
        }
        
        // Contenido
        $mail->isHTML(false); // Enviar como texto plano
        $mail->Subject = $data['asunto'];
        $mail->Body    = $data['mensaje'];
        
        // Enviar
        $mail->send();
        
        // Guardar en historial
        foreach ($destinatarios as $email) {
            $sql = "INSERT INTO agenda_correos (usuario, destinatario, asunto, mensaje, tipo, estado) 
                    VALUES (?, ?, ?, ?, ?, 'enviado')";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssss", 
                $usuario,
                $email,
                $data['asunto'],
                $data['mensaje'],
                $data['tipo']
            );
            $stmt->execute();
        }
        
        $count = count($destinatarios);
        echo json_encode([
            'success' => true, 
            'message' => "✅ Correo enviado exitosamente a $count destinatario(s)"
        ]);
        
    } catch (Exception $e) {
        // Si falla el envío, guardar como fallido
        foreach ($destinatarios as $email) {
            $sql = "INSERT INTO agenda_correos (usuario, destinatario, asunto, mensaje, tipo, estado) 
                    VALUES (?, ?, ?, ?, ?, 'fallido')";
            
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssss", 
                $usuario,
                $email,
                $data['asunto'],
                $data['mensaje'],
                $data['tipo']
            );
            $stmt->execute();
        }
        
        echo json_encode([
            'success' => false, 
            'message' => "❌ Error al enviar: {$e->getMessage()}"
        ]);
    }
}

elseif ($action === 'get_email_history') {
    $sql = "SELECT * FROM agenda_correos WHERE usuario = ? ORDER BY fecha_envio DESC LIMIT 50";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $emails = [];
    while ($row = $result->fetch_assoc()) {
        $emails[] = $row;
    }
    
    echo json_encode(['success' => true, 'emails' => $emails]);
}

else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

$conexion->close();
?>
