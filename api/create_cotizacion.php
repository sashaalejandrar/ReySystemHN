<?php
/**
 * API: Create Cotizacion (Quotation)
 */

header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    $conexion->set_charset("utf8mb4");
    
    // Get user info
    $stmt_user = $conexion->prepare("SELECT Id, Nombre, Apellido FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $_SESSION['usuario']);
    $stmt_user->execute();
    $user = $stmt_user->get_result()->fetch_assoc();
    $usuario_nombre = $user['Nombre'] . ' ' . $user['Apellido'];
    
    // Generate quotation number
    $year = date('Y');
    $stmt_count = $conexion->prepare("SELECT COUNT(*) as total FROM cotizaciones WHERE YEAR(fecha) = ?");
    $stmt_count->bind_param("i", $year);
    $stmt_count->execute();
    $count = $stmt_count->get_result()->fetch_assoc()['total'] + 1;
    $numero_cotizacion = 'COT-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    
    // Calculate expiration date
    $fecha_vencimiento = date('Y-m-d', strtotime($data['fecha'] . ' + ' . $data['vigencia_dias'] . ' days'));
    
    // Insert quotation
    $sql = "INSERT INTO cotizaciones (
        numero_cotizacion, cliente_nombre, cliente_telefono, cliente_email,
        fecha, vigencia_dias, fecha_vencimiento, subtotal, descuento, total,
        estado, notas, usuario_id, usuario_nombre
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssisdddsss",
        $numero_cotizacion,
        $data['cliente_nombre'],
        $data['cliente_telefono'],
        $data['cliente_email'],
        $data['fecha'],
        $data['vigencia_dias'],
        $fecha_vencimiento,
        $data['subtotal'],
        $data['descuento'],
        $data['total'],
        $data['notas'],
        $user['Id'],
        $usuario_nombre
    );
    
    if ($stmt->execute()) {
        $cotizacion_id = $conexion->insert_id;
        
        // Insert items
        $sql_item = "INSERT INTO cotizaciones_items (
            cotizacion_id, producto_id, producto_nombre, cantidad, precio_unitario, subtotal
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt_item = $conexion->prepare($sql_item);
        
        foreach ($data['items'] as $item) {
            $stmt_item->bind_param("iisddd",
                $cotizacion_id,
                $item['producto_id'],
                $item['producto_nombre'],
                $item['cantidad'],
                $item['precio_unitario'],
                $item['subtotal']
            );
            $stmt_item->execute();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cotización creada exitosamente',
            'numero_cotizacion' => $numero_cotizacion
        ]);
    } else {
        throw new Exception('Error al crear cotización');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
?>
