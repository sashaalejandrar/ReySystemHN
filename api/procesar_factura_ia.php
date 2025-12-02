<?php
session_start();
header('Content-Type: application/json');

// Incluir configuración de AI
require_once '../config_ai.php';

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener la imagen en base64
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['imagen'])) {
    echo json_encode(['success' => false, 'message' => 'No se recibió ninguna imagen']);
    exit;
}

$imagenBase64 = $input['imagen'];

// Verificar que tengamos la API key configurada
if (!defined('GROQ_API_KEY') || GROQ_API_KEY === 'gsk_TU_API_KEY_AQUI') {
    echo json_encode([
        'success' => false, 
        'message' => 'API Key de Groq no configurada. Por favor configura tu API key en config_ai.php',
        'instrucciones' => [
            '1. Obtén tu API key gratis en: https://console.groq.com/keys',
            '2. Edita el archivo config_ai.php',
            '3. Reemplaza "gsk_TU_API_KEY_AQUI" con tu API key real'
        ]
    ]);
    exit;
}

try {
    // Procesar imagen con Groq Vision API
    $prompt = "TAREA: Transcribe LITERALMENTE el contenido de esta factura.

PASO 1: Lee la imagen línea por línea
PASO 2: Copia EXACTAMENTE lo que ves, sin interpretar ni cambiar nada
PASO 3: Identifica las líneas que contienen productos (tienen: nombre, cantidad, precio)

FORMATO DE SALIDA - JSON ESTRICTO:
{
  \"productos\": [
    {
      \"nombre\": \"COPIA EXACTA del texto del producto\",
      \"cantidad\": número_que_ves,
      \"precio\": número_que_ves
    }
  ]
}

REGLAS ABSOLUTAS (INCUMPLIRLAS ES ERROR):
❌ NO inventes productos
❌ NO cambies nombres
❌ NO agregues información
❌ NO interpretes abreviaciones
❌ NO corrijas ortografía
✅ SOLO copia lo que VES en la imagen
✅ Si dice \"Shampoo Pantene 400ml\" → escribe \"Shampoo Pantene 400ml\"
✅ Si dice \"7501001234567 Shampoo Pantene 400ml\" → escribe \"7501001234567 Shampoo Pantene 400ml\"
✅ Mantén TODOS los números, letras y espacios EXACTOS

IDENTIFICAR PRODUCTOS:
- Busca líneas con formato: [texto] [número] [precio]
- Ignora: encabezados, totales, subtotales, ISV, pie de página
- Solo extrae líneas de productos individuales

EJEMPLO VISUAL:
Si ves esto en la imagen:
```
Shampoo Pantene 400ml    3    L85.50
Detergente Ariel 1kg     2    L120.00
```

Devuelve esto:
```json
{
  \"productos\": [
    {\"nombre\": \"Shampoo Pantene 400ml\", \"cantidad\": 3, \"precio\": 85.50},
    {\"nombre\": \"Detergente Ariel 1kg\", \"cantidad\": 2, \"precio\": 120.00}
  ]
}
```

IMPORTANTE: Los precios son números SIN \"L\", \"Lps\" o \"HNL\"

DEVUELVE SOLO EL JSON. NO agregues explicaciones.";

    // Llamar a la API según configuración (prioridad: OCR.space > Gemini > OpenAI > Groq)
    if (defined('USE_OCRSPACE') && USE_OCRSPACE) {
        $response = llamarOCRSpaceAPI($imagenBase64, $prompt);
    } else if (defined('USE_GEMINI') && USE_GEMINI) {
        $response = llamarGeminiVisionAPI($imagenBase64, $prompt);
    } else if (defined('USE_OPENAI') && USE_OPENAI) {
        $response = llamarOpenAIVisionAPI($imagenBase64, $prompt);
    } else {
        $response = llamarGroqVisionAPI($imagenBase64, $prompt);
    }
    
    if (!$response['success']) {
        echo json_encode([
            'success' => false,
            'message' => $response['error'] ?? 'Error al procesar con Groq Vision API'
        ]);
        exit;
    }
    
    $textoExtraido = $response['texto'];
    
    // Intentar parsear el JSON de la respuesta
    $productosData = extraerJSONDeRespuesta($textoExtraido);
    
    if (empty($productosData['productos'])) {
        echo json_encode([
            'success' => false,
            'message' => 'No se detectaron productos en la imagen',
            'texto_completo' => $textoExtraido
        ]);
        exit;
    }
    
    // Procesar productos y verificar si existen en stock
    $productos = [];
    foreach ($productosData['productos'] as $prod) {
        $nombreProducto = trim($prod['nombre'] ?? '');
        $cantidad = intval($prod['cantidad'] ?? 0);
        $precio = floatval($prod['precio'] ?? 0);
        
        if (empty($nombreProducto) || $cantidad <= 0 || $precio <= 0) {
            continue;
        }
        
        // Verificar si el nombre incluye un código de barras (13 dígitos al inicio)
        $tieneCodigoBarras = preg_match('/^(\d{13})\s+(.+)$/', $nombreProducto, $matchCodigo);
        
        if ($tieneCodigoBarras) {
            // Si tiene código de barras, buscar SOLO por código exacto
            $codigoBarras = $matchCodigo[1];
            $nombreSinCodigo = $matchCodigo[2];
            
            $stmt = $conexion->prepare("SELECT * FROM stock WHERE Codigo_Producto = ? LIMIT 1");
            $stmt->bind_param("s", $codigoBarras);
            $stmt->execute();
            $resultado = $stmt->get_result();
            $productoExistente = $resultado->num_rows > 0 ? $resultado->fetch_assoc() : null;
            $stmt->close();
            
            if ($productoExistente) {
                $productos[] = [
                    'codigo' => $productoExistente['Codigo_Producto'],
                    'nombre' => $productoExistente['Nombre_Producto'],
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'marca' => $productoExistente['Marca'] ?? '',
                    'descripcion' => $productoExistente['Descripcion'] ?? '',
                    'existe' => true,
                    'stockActual' => $productoExistente['Stock'],
                    'Id' => $productoExistente['Id']
                ];
            } else {
                // Producto nuevo con código de barras
                $productos[] = [
                    'codigo' => $codigoBarras,
                    'nombre' => $nombreSinCodigo,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'marca' => '',
                    'descripcion' => '',
                'existe' => false
                ];
            }
        } else {
            // Si NO tiene código de barras, buscar por nombre (menos común)
            $productoExistente = buscarProductoEnStock($nombreProducto, $conexion);
            
            if ($productoExistente) {
                $productos[] = [
                    'codigo' => $productoExistente['Codigo_Producto'],
                    'nombre' => $productoExistente['Nombre_Producto'],
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'marca' => $productoExistente['Marca'] ?? '',
                    'descripcion' => $productoExistente['Descripcion'] ?? '',
                    'existe' => true,
                    'stockActual' => $productoExistente['Stock'],
                    'Id' => $productoExistente['Id']
                ];
            } else {
                $productos[] = [
                    'codigo' => generarCodigoProducto($conexion),
                    'nombre' => $nombreProducto,
                    'cantidad' => $cantidad,
                    'precio' => $precio,
                    'marca' => '',
                    'descripcion' => '',
                    'existe' => false
                ];
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'productos' => $productos,
        'texto_extraido' => $textoExtraido
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar imagen: ' . $e->getMessage()
    ]);
}

$conexion->close();

/**
 * Llama a Groq Vision API para analizar la imagen
 */
function llamarGroqVisionAPI($imagenBase64, $prompt) {
    $apiKey = GROQ_API_KEY;
    $url = GROQ_API_URL;
    
    $data = [
        'model' => GROQ_VISION_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $imagenBase64
                        ]
                    ]
                ]
            ]
        ],
        'max_tokens' => GROQ_MAX_TOKENS,
        'temperature' => GROQ_TEMPERATURE
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Error de conexión: ' . $error];
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        return ['success' => false, 'error' => 'Error API: ' . ($errorData['error']['message'] ?? 'Código ' . $httpCode)];
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['choices'][0]['message']['content'])) {
        return ['success' => false, 'error' => 'Respuesta inválida de la API'];
    }
    
    return [
        'success' => true,
        'texto' => $result['choices'][0]['message']['content']
    ];
}

/**
 * Llama a OCR.space API para extraer texto (100% GRATIS - SIN TARJETA)
 * Luego usa Groq para parsear el texto a JSON
 */
function llamarOCRSpaceAPI($imagenBase64, $prompt) {
    $apiKey = OCRSPACE_API_KEY;
    $url = OCRSPACE_API_URL;
    
    // Paso 1: Extraer texto con OCR.space (GRATIS)
    $data = [
        'apikey' => $apiKey,
        'base64Image' => $imagenBase64,
        'language' => 'spa',
        'isOverlayRequired' => 'false',
        'detectOrientation' => 'true',
        'scale' => 'true',
        'OCREngine' => '2' // Motor 2 es más preciso
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Error de conexión OCR.space: ' . $error];
    }
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'Error OCR.space: Código ' . $httpCode];
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['ParsedResults'][0]['ParsedText'])) {
        // Mostrar el error completo para debug
        if (isset($result['ErrorMessage'])) {
            $errorMsg = is_array($result['ErrorMessage']) ? json_encode($result['ErrorMessage']) : $result['ErrorMessage'];
        } else {
            $errorMsg = 'Respuesta: ' . substr($response, 0, 200);
        }
        return ['success' => false, 'error' => 'Error OCR.space: ' . $errorMsg];
    }
    
    $textoExtraido = $result['ParsedResults'][0]['ParsedText'];
    
    if (empty(trim($textoExtraido))) {
        return ['success' => false, 'error' => 'No se detectó texto en la imagen'];
    }
    
    // Paso 2: Parsear texto manualmente
    $productos = [];
    
    // Limpiar y normalizar el texto
    $texto = str_replace(["\r\n", "\r"], "\n", $textoExtraido);
    
    // Primero intentar el patrón normal (línea por línea)
    preg_match_all('/(\d{13})\s+([A-Za-z].+?)\s+(?:CANT\s+)?(\d+)\s+(?:PRECIO\s+)?L\.?\s*(\d+\.?\d*)/i', $texto, $matches, PREG_SET_ORDER);
    
    if (!empty($matches)) {
        // Formato normal detectado
        foreach ($matches as $match) {
            $codigo = $match[1];
            $nombre = trim($match[2]);
            $cantidad = intval($match[3]);
            $precio = floatval($match[4]);
            
            // Limpiar el nombre
            $nombre = preg_replace('/\s+(CANT|PRECIO|L\.?)\s*$/i', '', $nombre);
            $nombre = trim($nombre);
            
            if (!empty($nombre) && $cantidad > 0 && $precio > 0) {
                $productos[] = [
                    'nombre' => $codigo . ' ' . $nombre,
                    'cantidad' => $cantidad,
                    'precio' => $precio
                ];
            }
        }
    } else {
        // Formato de tabla por columnas - intentar parsear diferente
        // Extraer códigos (13 dígitos)
        preg_match_all('/(\d{13})/', $texto, $codigos);
        
        // Extraer descripciones (texto entre códigos y CANT)
        preg_match('/DESCRIPCIÓN\s+(.+?)\s+CANT/s', $texto, $descripcionesMatch);
        if (isset($descripcionesMatch[1])) {
            $descripcionesTexto = $descripcionesMatch[1];
            // Separar por saltos de línea o múltiples espacios
            $descripciones = preg_split('/\s{2,}|\n/', $descripcionesTexto);
            $descripciones = array_filter(array_map('trim', $descripciones));
            $descripciones = array_values($descripciones);
        }
        
        // Extraer cantidades (números después de CANT)
        preg_match('/CANT\s+(.+?)\s+PRECIO/s', $texto, $cantidadesMatch);
        if (isset($cantidadesMatch[1])) {
            $cantidadesTexto = $cantidadesMatch[1];
            preg_match_all('/(\d+)/', $cantidadesTexto, $cantidadesNums);
            $cantidades = $cantidadesNums[1];
        }
        
        // Extraer precios (números después de PRECIO UNIT o L)
        preg_match_all('/L\s*(\d+\.?\d*)/', $texto, $preciosMatch);
        $precios = $preciosMatch[1];
        
        // Combinar los arrays
        $numProductos = min(
            count($codigos[1] ?? []),
            count($descripciones ?? []),
            count($cantidades ?? []),
            count($precios ?? [])
        );
        
        for ($i = 0; $i < $numProductos; $i++) {
            if (isset($codigos[1][$i], $descripciones[$i], $cantidades[$i], $precios[$i])) {
                $codigo = $codigos[1][$i];
                $nombre = trim($descripciones[$i]);
                $cantidad = intval($cantidades[$i]);
                $precio = floatval($precios[$i]);
                
                if (!empty($nombre) && $cantidad > 0 && $precio > 0) {
                    $productos[] = [
                        'nombre' => $codigo . ' ' . $nombre,
                        'cantidad' => $cantidad,
                        'precio' => $precio
                    ];
                }
            }
        }
    }
    
    // Debug: Si no se encontraron productos, devolver el texto para debug
    if (empty($productos)) {
        error_log("OCR.space - No se detectaron productos. Texto extraído: " . substr($textoExtraido, 0, 500));
        return [
            'success' => false, 
            'error' => 'No se detectaron productos. Texto extraído (primeros 500 caracteres): ' . substr($textoExtraido, 0, 500)
        ];
    }
    
    $json = json_encode(['productos' => $productos]);
    return ['success' => true, 'texto' => $json];
}

/**
 * Llama a OpenAI Vision API para analizar la imagen
 */
function llamarOpenAIVisionAPI($imagenBase64, $prompt) {
    $apiKey = OPENAI_API_KEY;
    $url = OPENAI_API_URL;
    
    // Verificar que la API key esté configurada
    if ($apiKey === 'TU_API_KEY_DE_OPENAI_AQUI') {
        return ['success' => false, 'error' => 'API Key de OpenAI no configurada. Configura OPENAI_API_KEY en config_ai.php'];
    }
    
    $data = [
        'model' => OPENAI_VISION_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $prompt
                    ],
                    [
                        'type' => 'image_url',
                        'image_url' => [
                            'url' => $imagenBase64
                        ]
                    ]
                ]
            ]
        ],
        'max_tokens' => GROQ_MAX_TOKENS
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Error de conexión: ' . $error];
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        return ['success' => false, 'error' => 'Error OpenAI: ' . ($errorData['error']['message'] ?? 'Código ' . $httpCode)];
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['choices'][0]['message']['content'])) {
        return ['success' => false, 'error' => 'Respuesta inválida de OpenAI'];
    }
    
    return [
        'success' => true,
        'texto' => $result['choices'][0]['message']['content']
    ];
}

/**
 * Llama a Google Gemini Vision API para analizar la imagen (GRATIS)
 */
function llamarGeminiVisionAPI($imagenBase64, $prompt) {
    $apiKey = GEMINI_API_KEY;
    $model = GEMINI_VISION_MODEL;
    
    // Verificar que la API key esté configurada
    if ($apiKey === 'TU_API_KEY_DE_GEMINI_AQUI') {
        return ['success' => false, 'error' => 'API Key de Gemini no configurada. Obtén una GRATIS en: https://aistudio.google.com/app/apikey'];
    }
    
    // Extraer solo los datos base64 (sin el prefijo data:image/...)
    $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imagenBase64);
    
    $url = GEMINI_API_URL . $model . ':generateContent?key=' . $apiKey;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    [
                        'text' => $prompt
                    ],
                    [
                        'inline_data' => [
                            'mime_type' => 'image/jpeg',
                            'data' => $imageData
                        ]
                    ]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.1,
            'maxOutputTokens' => 2000
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => 'Error de conexión: ' . $error];
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['error']['message'] ?? 'Código ' . $httpCode;
        return ['success' => false, 'error' => 'Error Gemini: ' . $errorMsg];
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        return ['success' => false, 'error' => 'Respuesta inválida de Gemini'];
    }
    
    return [
        'success' => true,
        'texto' => $result['candidates'][0]['content']['parts'][0]['text']
    ];
}

/**
 * Extrae JSON de la respuesta de la IA
 */
function extraerJSONDeRespuesta($texto) {
    // Intentar encontrar JSON en la respuesta
    if (preg_match('/\{[\s\S]*"productos"[\s\S]*\}/', $texto, $matches)) {
        $json = json_decode($matches[0], true);
        if ($json !== null) {
            return $json;
        }
    }
    
    // Intentar parsear directamente
    $json = json_decode($texto, true);
    if ($json !== null && isset($json['productos'])) {
        return $json;
    }
    
    return ['productos' => []];
}

/**
 * Busca un producto en la tabla stock por nombre similar
 */
function buscarProductoEnStock($nombre, $conexion) {
    // Limpiar el nombre para búsqueda
    $nombreLimpio = trim($nombre);
    
    // 1. Buscar por coincidencia exacta
    $stmt = $conexion->prepare("SELECT * FROM stock WHERE Nombre_Producto = ? LIMIT 1");
    $stmt->bind_param("s", $nombreLimpio);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        return $resultado->fetch_assoc();
    }
    
    // 2. Buscar por coincidencia parcial (LIKE completo)
    $nombreLike = '%' . $nombreLimpio . '%';
    $stmt = $conexion->prepare("SELECT * FROM stock WHERE Nombre_Producto LIKE ? LIMIT 1");
    $stmt->bind_param("s", $nombreLike);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        return $resultado->fetch_assoc();
    }
    
    // 3. Buscar por palabras clave (más flexible)
    // Extraer palabras significativas (más de 3 caracteres)
    $palabras = preg_split('/\s+/', $nombreLimpio);
    $palabrasSignificativas = array_filter($palabras, function($p) {
        return strlen($p) > 3;
    });
    
    foreach ($palabrasSignificativas as $palabra) {
        $palabraLike = '%' . $palabra . '%';
        $stmt = $conexion->prepare("SELECT * FROM stock WHERE Nombre_Producto LIKE ? LIMIT 1");
        $stmt->bind_param("s", $palabraLike);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            return $resultado->fetch_assoc();
        }
    }
    
    // 4. Búsqueda muy flexible - primeras 4 letras
    if (strlen($nombreLimpio) >= 4) {
        $inicioNombre = substr($nombreLimpio, 0, 4) . '%';
        $stmt = $conexion->prepare("SELECT * FROM stock WHERE Nombre_Producto LIKE ? LIMIT 1");
        $stmt->bind_param("s", $inicioNombre);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            return $resultado->fetch_assoc();
        }
    }
    
    return null;
}

/**
 * Genera un código de producto único
 */
function generarCodigoProducto($nombre) {
    $palabras = explode(' ', strtoupper($nombre));
    $codigo = '';
    
    foreach ($palabras as $palabra) {
        if (strlen($palabra) > 0) {
            $codigo .= substr($palabra, 0, 2);
        }
        if (strlen($codigo) >= 6) break;
    }
    
    $codigo .= rand(100, 999);
    
    return substr($codigo, 0, 15);
}
?>
