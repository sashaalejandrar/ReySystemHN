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

// GET - Listar pedidos
if ($method === 'GET') {
    $limit = $_GET['limit'] ?? 100;
    $estado = $_GET['estado'] ?? '';
    $busqueda = $_GET['busqueda'] ?? '';
    
    $where = "1=1";
    if ($estado) $where .= " AND Estado = '$estado'";
    if ($busqueda) $where .= " AND (Cliente LIKE '%$busqueda%' OR Producto_Solicitado LIKE '%$busqueda%' OR Numero_Pedido LIKE '%$busqueda%')";
    
    $query = "SELECT * FROM pedidos WHERE $where ORDER BY Fecha_Pedido DESC LIMIT $limit";
    $result = $conexion->query($query);
    
    $pedidos = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pedidos[] = [
                'id' => $row['Id'],
                'numero_pedido' => $row['Numero_Pedido'],
                'fecha_pedido' => $row['Fecha_Pedido'],
                'cliente' => $row['Cliente'],
                'telefono' => $row['Telefono'],
                'email' => $row['Email'],
                'producto_solicitado' => $row['Producto_Solicitado'],
                'cantidad' => intval($row['Cantidad']),
                'precio_estimado' => floatval($row['Precio_Estimado']),
                'total_estimado' => floatval($row['Total_Estimado']),
                'notas' => $row['Notas'],
                'estado' => $row['Estado'],
                'fecha_estimada_entrega' => $row['Fecha_Estimada_Entrega'],
                'usuario_registro' => $row['Usuario_Registro']
            ];
        }
    }
    
    echo json_encode(['success' => true, 'pedidos' => $pedidos]);
}

// POST - Crear nuevo pedido
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $cliente = $conexion->real_escape_string($data['cliente']);
    $telefono = $conexion->real_escape_string($data['telefono'] ?? '');
    $email = $conexion->real_escape_string($data['email'] ?? '');
    $producto = $conexion->real_escape_string($data['producto']);
    $cantidad = intval($data['cantidad']);
    $precio = floatval($data['precio'] ?? 0);
    $total = $precio * $cantidad;
    $notas = $conexion->real_escape_string($data['notas'] ?? '');
    $estado = $conexion->real_escape_string($data['estado'] ?? 'Pendiente');
    $fecha_entrega = $data['fecha_entrega'] ?? null;
    $usuario = $_SESSION['usuario'];
    
    // Generar número de pedido único
    $numero_pedido = 'PED-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    $query = "INSERT INTO pedidos (
        Numero_Pedido, Cliente, Telefono, Email, Producto_Solicitado, 
        Cantidad, Precio_Estimado, Total_Estimado, Notas, Estado, 
        Fecha_Estimada_Entrega, Usuario_Registro
    ) VALUES (
        '$numero_pedido', '$cliente', '$telefono', '$email', '$producto',
        $cantidad, $precio, $total, '$notas', '$estado',
        " . ($fecha_entrega ? "'$fecha_entrega'" : "NULL") . ", '$usuario'
    )";
    
    if ($conexion->query($query)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Pedido creado exitosamente',
            'numero_pedido' => $numero_pedido,
            'id' => $conexion->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al crear pedido: ' . $conexion->error]);
    }
}

// PUT - Actualizar pedido
elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id']);
    $estado = $conexion->real_escape_string($data['estado']);
    
    $query = "UPDATE pedidos SET Estado = '$estado' WHERE Id = $id";
    
    if ($conexion->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Estado actualizado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al actualizar: ' . $conexion->error]);
    }
}

// DELETE - Cancelar pedido
elseif ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id']);
    
    $query = "UPDATE pedidos SET Estado = 'Cancelado' WHERE Id = $id";
    
    if ($conexion->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Pedido cancelado']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cancelar: ' . $conexion->error]);
    }
}

$conexion->close();
?>
