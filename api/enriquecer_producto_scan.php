<?php
session_start();
header('Content-Type: application/json');

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit;
}

$codigo_barras = trim($_POST['codigo_barras'] ?? '');
$nombre_producto = trim($_POST['nombre_producto'] ?? '');

if (empty($codigo_barras)) {
    echo json_encode(['success' => false, 'message' => 'Código de barras requerido']);
    exit;
}

// Reutilizar la API existente de enriquecimiento
include_once(__DIR__ . '/enriquecer_producto_ia.php');

try {
    // Llamar a la función de enriquecimiento existente
    $datos_enriquecidos = enriquecerProducto($codigo_barras, $nombre_producto);
    
    if ($datos_enriquecidos && isset($datos_enriquecidos['nombre'])) {
        echo json_encode([
            'success' => true,
            'codigo_barras' => $codigo_barras,
            'datos' => $datos_enriquecidos,
            'mensaje' => '✨ Datos enriquecidos con IA'
        ]);
    } else {
        // Si no se pudo enriquecer, devolver estructura básica
        echo json_encode([
            'success' => true,
            'codigo_barras' => $codigo_barras,
            'datos' => [
                'nombre' => $nombre_producto ?: 'Producto ' . $codigo_barras,
                'codigo' => $codigo_barras,
                'marca' => '',
                'categoria' => '',
                'descripcion' => '',
                'precio_sugerido' => 0,
                'foto_url' => ''
            ],
            'mensaje' => 'ℹ️ No se pudo enriquecer con IA. Datos básicos.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al enriquecer: ' . $e->getMessage()
    ]);
}

// Función auxiliar que llama a la lógica existente
function enriquecerProducto($codigo, $nombre = '') {
    // Primero intentar con Open Food Facts
    $url = "https://world.openfoodfacts.org/api/v0/product/{$codigo}.json";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ReySystem/1.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        
        if (isset($data['product']) && $data['status'] == 1) {
            $product = $data['product'];
            
            return [
                'nombre' => $product['product_name'] ?? $product['product_name_es'] ?? $nombre,
                'codigo' => $codigo,
                'marca' => $product['brands'] ?? '',
                'categoria' => $product['categories'] ?? '',
                'descripcion' => $product['ingredients_text'] ?? '',
                'precio_sugerido' => 0,
                'foto_url' => $product['image_url'] ?? $product['image_front_url'] ?? ''
            ];
        }
    }
    
    // Si Open Food Facts no tiene datos, usar solo el código
    return [
        'nombre' => $nombre ?: "Producto {$codigo}",
        'codigo' => $codigo,
        'marca' => '',
        'categoria' => '',
        'descripcion' => '',
        'precio_sugerido' => 0,
        'foto_url' => ''
    ];
}
?>
