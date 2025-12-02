<?php
session_start();
header('Content-Type: application/json');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

include '../registrar_movimiento_inventario.php';

$input = json_decode(file_get_contents('php://input'), true);
$productos = $input['productos'] ?? [];
$tipoAjuste = $input['tipoAjuste'] ?? 'sumar';

if (empty($productos)) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron productos']);
    exit;
}

$actualizados = 0;
$errores = [];

foreach ($productos as $producto) {
    try {
        $codigo = $producto['codigo'];
        $stockNuevo = intval($producto['stockNuevo']);
        $precioNuevo = floatval($producto['precio'] ?? 0);
        
        // Buscar el producto por código
        $stmt = $conexion->prepare("SELECT Id, Nombre_Producto, Stock, Precio_Unitario FROM stock WHERE Codigo_Producto = ?");
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows === 0) {
            $errores[] = "Producto con código {$codigo} no encontrado";
            $stmt->close();
            continue;
        }
        
        $row = $resultado->fetch_assoc();
        $id = $row['Id'];
        $nombre = $row['Nombre_Producto'];
        $stockActual = intval($row['Stock']);
        $precioActual = floatval($row['Precio_Unitario']);
        $stmt->close();
        
        // Actualizar stock y precio
        $stmt = $conexion->prepare("UPDATE stock SET Stock = ?, Precio_Unitario = ? WHERE Id = ?");
        $stmt->bind_param("idi", $stockNuevo, $precioNuevo, $id);
        
        if ($stmt->execute()) {
            $actualizados++;
            
            // Registrar movimiento
            $diferencia = $stockNuevo - $stockActual;
            $tipoMovimiento = $diferencia > 0 ? 'entrada' : 'salida';
            $cantidad = abs($diferencia);
            
            $observacion = "Actualización masiva ({$tipoAjuste})";
            if ($precioNuevo != $precioActual) {
                $observacion .= " - Precio actualizado de L" . number_format($precioActual, 2) . " a L" . number_format($precioNuevo, 2);
            }
            
            if ($cantidad > 0) {
                registrarMovimientoInventario(
                    $conexion,
                    $nombre,
                    $tipoMovimiento,
                    $cantidad,
                    $stockActual,
                    $stockNuevo,
                    $observacion,
                    $_SESSION['usuario'] ?? 'Sistema'
                );
            }
        } else {
            $errores[] = "Producto {$codigo}: " . $stmt->error;
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $errores[] = "Producto {$producto['codigo']}: " . $e->getMessage();
    }
}

$conexion->close();

echo json_encode([
    'success' => count($errores) === 0,
    'actualizados' => $actualizados,
    'total' => count($productos),
    'errores' => $errores
]);
?>
