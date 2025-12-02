<?php
/**
 * API para obtener las ventas de un cliente específico
 * Retorna JSON con la lista de ventas del cliente
 */

header('Content-Type: application/json');
require_once 'db_connect.php';

// Verificar que se recibió el nombre del cliente
if (!isset($_GET['nombre']) || empty($_GET['nombre'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Nombre de cliente requerido'
    ]);
    exit;
}

$nombre_cliente = $_GET['nombre'];

try {
    // Obtener ventas del cliente
    $stmt = $conexion->prepare("
        SELECT 
            Id,
            Factura_Id,
            Fecha_Venta,
            Total,
            MetodoPago,
            Producto_Vendido,
            Cantidad,
            Vendedor
        FROM ventas 
        WHERE Cliente = ? 
        ORDER BY Fecha_Venta DESC
    ");
    
    $stmt->bind_param("s", $nombre_cliente);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ventas = [];
    $total_general = 0;
    
    while ($venta = $result->fetch_assoc()) {
        $ventas[] = [
            'id' => $venta['Id'],
            'factura_id' => $venta['Factura_Id'],
            'fecha' => $venta['Fecha_Venta'],
            'total' => floatval($venta['Total']),
            'metodo_pago' => $venta['MetodoPago'],
            'productos' => $venta['Producto_Vendido'],
            'cantidad' => $venta['Cantidad'],
            'vendedor' => $venta['Vendedor']
        ];
        $total_general += floatval($venta['Total']);
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'ventas' => $ventas,
        'total_ventas' => count($ventas),
        'total_general' => $total_general
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener ventas: ' . $e->getMessage()
    ]);
}

$conexion->close();
?>
