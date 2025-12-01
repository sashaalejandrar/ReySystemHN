<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit();
}

$conexion->set_charset("utf8mb4");
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $cliente = $conexion->real_escape_string($data['cliente']);
    $telefono = $conexion->real_escape_string($data['telefono']);
    $email = $conexion->real_escape_string($data['email'] ?? '');
    $notas = $conexion->real_escape_string($data['notas'] ?? '');
    $estado = $conexion->real_escape_string($data['estado'] ?? 'Pendiente');
    $fecha_entrega = $data['fecha_entrega'] ?? null;
    $usuario = $_SESSION['usuario'];
    $productos = $data['productos'];
    
    // Calcular total
    $total = 0;
    foreach ($productos as $prod) {
        $total += $prod['subtotal'];
    }
    
    // Generar número de pedido único
    $numero_pedido = 'MAY-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Crear resumen de productos para la columna Producto_Solicitado
    $resumen_productos = implode(', ', array_map(function($p) {
        return $p['producto'] . ' x' . $p['cantidad'];
    }, $productos));
    
    // Insertar pedido principal
    $query = "INSERT INTO pedidos (
        Numero_Pedido, Cliente, Telefono, Email, Producto_Solicitado, 
        Cantidad, Precio_Estimado, Total_Estimado, Notas, Estado, 
        Fecha_Estimada_Entrega, Usuario_Registro
    ) VALUES (
        '$numero_pedido', '$cliente', '$telefono', '$email', '$resumen_productos',
        1, $total, $total, '$notas', '$estado',
        " . ($fecha_entrega ? "'$fecha_entrega'" : "NULL") . ", '$usuario'
    )";
    
    if ($conexion->query($query)) {
        $pedido_id = $conexion->insert_id;
        
        // Insertar detalles de productos (opcional: crear tabla pedidos_detalle)
        // Por ahora guardamos en notas el detalle completo
        $detalle_completo = "PEDIDO POR MAYOREO\n\n";
        foreach ($productos as $prod) {
            $detalle_completo .= "- {$prod['producto']}: {$prod['cantidad']} x L.{$prod['precio']} = L.{$prod['subtotal']}\n";
        }
        $detalle_completo .= "\nTOTAL: L.$total\n\n" . $notas;
        
        $update = "UPDATE pedidos SET Notas = '$detalle_completo' WHERE Id = $pedido_id";
        $conexion->query($update);
        
        echo json_encode([
            'success' => true,
            'message' => 'Pedido por mayoreo creado exitosamente',
            'numero_pedido' => $numero_pedido,
            'id' => $pedido_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear pedido: ' . $conexion->error]);
    }
}

$conexion->close();
?>
