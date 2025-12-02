<?php
session_start();
require_once '../../db_connect.php';

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'generate_product_qr':
        $product_id = $_POST['product_id'] ?? '';
        
        if (empty($product_id)) {
            echo json_encode(['success' => false, 'error' => 'ID de producto requerido']);
            exit;
        }
        
        // Obtener datos del producto
        $stmt = $conexion->prepare("SELECT * FROM productos WHERE Id = ?");
        $stmt->bind_param("s", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        if (!$product) {
            echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
            exit;
        }
        
        // Generar datos del QR
        $qr_data = json_encode([
            'type' => 'product',
            'id' => $product['Id'],
            'nombre' => $product['nombre'],
            'precio' => $product['precio'],
            'stock' => $product['stock'],
            'codigo' => $product['codigo'] ?? ''
        ]);
        
        // URL del QR (usando API gratuita)
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);
        
        echo json_encode([
            'success' => true,
            'qr_url' => $qr_url,
            'product' => $product
        ]);
        break;
        
    case 'generate_payment_qr':
        $amount = $_POST['amount'] ?? 0;
        $description = $_POST['description'] ?? '';
        
        $qr_data = json_encode([
            'type' => 'payment',
            'amount' => $amount,
            'description' => $description,
            'timestamp' => time()
        ]);
        
        $qr_url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($qr_data);
        
        echo json_encode([
            'success' => true,
            'qr_url' => $qr_url
        ]);
        break;
        
    case 'scan_qr':
        $qr_data = $_POST['qr_data'] ?? '';
        
        if (empty($qr_data)) {
            echo json_encode(['success' => false, 'error' => 'Datos QR vacíos']);
            exit;
        }
        
        $data = json_decode($qr_data, true);
        
        if (!$data) {
            echo json_encode(['success' => false, 'error' => 'Datos QR inválidos']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        break;
        
    case 'add_to_sale':
        $product_id = $_POST['product_id'] ?? '';
        
        // Aquí se puede integrar con el sistema de ventas existente
        echo json_encode([
            'success' => true,
            'message' => 'Producto agregado a la venta'
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Acción no válida']);
}
?>
