<?php
/**
 * API para enriquecer información de productos usando IA y web scraping
 * Acepta nombre del producto o código de barras y retorna información completa
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Recibir datos
$input = json_decode(file_get_contents('php://input'), true);
$nombre = $input['nombre'] ?? '';
$codigo = $input['codigo'] ?? '';

if (empty($nombre) && empty($codigo)) {
    echo json_encode(['success' => false, 'message' => 'Nombre o código requerido']);
    exit;
}

// Determinar qué buscar
$busqueda = !empty($codigo) ? $codigo : $nombre;

try {
    // Intentar primero con código de barras en APIs públicas
    if (!empty($codigo)) {
        $resultado = buscarPorCodigoBarras($codigo);
        if ($resultado['success']) {
            echo json_encode($resultado);
            exit;
        }
    }
    
    // Si no hay código o no se encontró, usar web scraping + IA
    $resultado = enriquecerConIA($busqueda);
    echo json_encode($resultado);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al enriquecer producto: ' . $e->getMessage()
    ]);
}

/**
 * Buscar producto por código de barras en APIs públicas
 */
function buscarPorCodigoBarras($codigo) {
    // Intentar con Open Food Facts (para alimentos)
    $url = "https://world.openfoodfacts.org/api/v0/product/{$codigo}.json";
    $response = @file_get_contents($url);
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['status']) && $data['status'] == 1) {
            $product = $data['product'];
            return [
                'success' => true,
                'source' => 'Open Food Facts',
                'data' => [
                    'nombre' => $product['product_name'] ?? '',
                    'marca' => $product['brands'] ?? '',
                    'descripcion' => $product['generic_name'] ?? $product['product_name'] ?? '',
                    'descripcionCorta' => substr($product['product_name'] ?? '', 0, 100),
                    'categoria' => $product['categories'] ?? 'Alimentos',
                    'imagen' => $product['image_url'] ?? ''
                ]
            ];
        }
    }
    
    // Intentar con UPC Database (requiere API key - versión gratuita limitada)
    // Por ahora, retornar false si no se encuentra
    return ['success' => false, 'message' => 'Código de barras no encontrado'];
}

/**
 * Enriquecer producto usando IA y web scraping
 */
function enriquecerConIA($busqueda) {
    // Usar Google Custom Search API o web scraping simple
    $infoProducto = buscarEnGoogle($busqueda);
    
    // Generar descripción con IA (usando un servicio gratuito o local)
    $descripcion = generarDescripcionIA($busqueda, $infoProducto);
    
    // Extraer categoría y marca del nombre
    $categoria = detectarCategoria($busqueda);
    $marca = detectarMarca($busqueda);
    
    return [
        'success' => true,
        'source' => 'IA + Web Scraping',
        'data' => [
            'nombre' => $busqueda,
            'marca' => $marca,
            'descripcion' => $descripcion,
            'descripcionCorta' => substr($descripcion, 0, 100),
            'categoria' => $categoria,
            'confianza' => 0.75 // Score de confianza
        ]
    ];
}

/**
 * Buscar información básica en Google (web scraping simple)
 */
function buscarEnGoogle($query) {
    // Sanitizar query
    $query = urlencode($query . ' producto características');
    
    // Usar DuckDuckGo Instant Answer API (gratuita, sin API key)
    $url = "https://api.duckduckgo.com/?q={$query}&format=json&no_html=1&skip_disambig=1";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        return [
            'abstract' => $data['Abstract'] ?? '',
            'url' => $data['AbstractURL'] ?? ''
        ];
    }
    
    return ['abstract' => '', 'url' => ''];
}

/**
 * Generar descripción usando IA (versión simple sin API externa)
 */
function generarDescripcionIA($nombre, $infoWeb) {
    // Si tenemos información de web scraping, usarla
    if (!empty($infoWeb['abstract'])) {
        return $infoWeb['abstract'];
    }
    
    // Generar descripción básica basada en el nombre
    $palabras = explode(' ', $nombre);
    $descripcion = "Producto: {$nombre}. ";
    
    // Detectar tipo de producto y agregar descripción genérica
    if (preg_match('/\d+\s*(kg|g|l|ml|oz|lb)/i', $nombre, $matches)) {
        $descripcion .= "Presentación de {$matches[0]}. ";
    }
    
    if (stripos($nombre, 'pack') !== false || stripos($nombre, 'paquete') !== false) {
        $descripcion .= "Empaque múltiple. ";
    }
    
    $descripcion .= "Producto de calidad para su negocio.";
    
    return $descripcion;
}

/**
 * Detectar categoría del producto basándose en palabras clave
 */
function detectarCategoria($nombre) {
    $categorias = [
        'Alimentos' => ['comida', 'alimento', 'cereal', 'pan', 'galleta', 'snack', 'dulce', 'chocolate'],
        'Bebidas' => ['bebida', 'refresco', 'jugo', 'agua', 'soda', 'té', 'café', 'cerveza', 'vino'],
        'Limpieza' => ['detergente', 'jabón', 'limpiador', 'cloro', 'desinfectante', 'suavizante'],
        'Higiene Personal' => ['shampoo', 'jabón', 'pasta', 'cepillo', 'desodorante', 'perfume'],
        'Mascotas' => ['perro', 'gato', 'mascota', 'pet', 'animal'],
        'Electrónica' => ['cable', 'cargador', 'audífono', 'batería', 'usb', 'electrónico'],
        'Hogar' => ['plato', 'vaso', 'taza', 'sartén', 'olla', 'cubierto'],
        'Papelería' => ['cuaderno', 'lápiz', 'pluma', 'papel', 'folder', 'marcador']
    ];
    
    $nombreLower = strtolower($nombre);
    
    foreach ($categorias as $categoria => $palabrasClave) {
        foreach ($palabrasClave as $palabra) {
            if (stripos($nombreLower, $palabra) !== false) {
                return $categoria;
            }
        }
    }
    
    return 'General';
}

/**
 * Detectar marca del producto
 */
function detectarMarca($nombre) {
    $marcasConocidas = [
        'Coca Cola', 'Pepsi', 'Nestlé', 'Bimbo', 'Sabritas', 'Gamesa', 
        'Colgate', 'Palmolive', 'Procter & Gamble', 'Unilever',
        'Samsung', 'Apple', 'Sony', 'LG', 'Huawei',
        'Nike', 'Adidas', 'Puma', 'Reebok',
        'Pet Master', 'Pedigree', 'Whiskas', 'Purina'
    ];
    
    $nombreLower = strtolower($nombre);
    
    foreach ($marcasConocidas as $marca) {
        if (stripos($nombreLower, strtolower($marca)) !== false) {
            return $marca;
        }
    }
    
    // Intentar extraer la primera palabra como marca
    $palabras = explode(' ', $nombre);
    if (count($palabras) > 0 && strlen($palabras[0]) > 2) {
        return ucfirst($palabras[0]);
    }
    
    return '';
}
?>
