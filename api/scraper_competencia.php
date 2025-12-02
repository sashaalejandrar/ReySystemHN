<?php
/**
 * Scraper Ultra-Preciso con Google Search + Groq AI
 * Busca en Google y extrae precios reales de Honduras
 */

header('Content-Type: application/json');
set_time_limit(600);

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

$codigo = $_GET['codigo'] ?? '';
$nombre = $_GET['nombre'] ?? '';
$metodo = $_GET['metodo'] ?? 'google_ai'; // google_ai, scraping_directo, hibrido

if (empty($codigo) && empty($nombre)) {
    echo json_encode(['success' => false, 'message' => 'Código o nombre requerido']);
    exit;
}

// Obtener datos del producto
if (empty($nombre)) {
    $stmt = $conexion->prepare("SELECT Nombre_Producto FROM stock WHERE Codigo_Producto = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();
    $producto = $result->fetch_assoc();
    $nombre = $producto['Nombre_Producto'] ?? '';
    $stmt->close();
}

$GROQ_API_KEY = getenv('GROQ_API_KEY') ?: 'YOUR_GROQ_API_KEY_HERE';
$GROQ_MODEL = 'llama-3.3-70b-versatile';

/**
 * Método 1: Búsqueda en Google + IA (MÁS PRECISO) - MEJORADO
 * Simula búsqueda en Google y usa IA para extraer precios
 */
function busquedaGoogleConIA($codigo, $nombre, $apiKey, $model) {
    $query = "{$nombre} precio Honduras";
    
    $prompt = "Necesito que realices webscraping:
1. Busca en Google el nombre del producto seguido de \"precio Honduras\".
2. Toma el primer resultado y revisa si en el snippet aparece un precio en formato L.xx.xx (ejemplo: L.50.64). 
   - Si lo encuentras, devuélvelo directamente como precio.
3. Si no aparece en el snippet, entra al enlace del resultado.
   - Haz scraping del contenido de la página.
   - Extrae el dato que mejor coincida con el precio del producto en Honduras.
   - El precio debe estar en formato L.xx.xx (dos decimales, con la letra L antes).
4. Devuélveme únicamente el precio encontrado, sin texto adicional ni explicaciones.

Producto a buscar: {$nombre}
Código de barras: {$codigo}

FORMATO DE RESPUESTA (JSON):
{
  \"precio_encontrado\": true,
  \"precio\": 50.64,
  \"fuente\": \"La Colonia\",
  \"url\": \"https://www.lacolonia.com/producto/ejemplo\"
}

Si NO encuentras precio: {\"precio_encontrado\": false}

Responde SOLO con el JSON.";

    $data = [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.2,
        'max_tokens' => 500
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 60
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $resultados = [];
    
    if ($response) {
        $result = json_decode($response, true);
        $contenido = trim($result['choices'][0]['message']['content'] ?? '{"precio_encontrado":false}');
        $contenido = preg_replace('/```json\s*/', '', $contenido);
        $contenido = preg_replace('/```\s*$/', '', $contenido);
        
        $datos = json_decode($contenido, true);
        
        if (isset($datos['precio_encontrado']) && $datos['precio_encontrado'] === true && isset($datos['precio'])) {
            $resultados[] = [
                'fuente' => $datos['fuente'] ?? 'Google Search',
                'precio' => floatval($datos['precio']),
                'url' => $datos['url'] ?? ''
            ];
        }
    }
    
    // Si no encontró nada, intentar búsqueda con código
    if (empty($resultados) && !empty($codigo)) {
        $query2 = "{$codigo} precio Honduras";
        
        $prompt2 = "Necesito que realices webscraping:
1. Busca en Google: \"{$query2}\"
2. Revisa el snippet del primer resultado buscando precio en formato L.xx.xx
3. Si no está en el snippet, entra al enlace y extrae el precio de la página
4. Verifica que el código de barras {$codigo} coincida

Producto: {$nombre}

FORMATO (JSON):
{
  \"precio_encontrado\": true,
  \"precio\": 35.50,
  \"fuente\": \"Walmart Honduras\",
  \"url\": \"https://walmart.com.hn/producto\"
}

Si no encuentras: {\"precio_encontrado\": false}

Responde SOLO JSON.";

        $data2 = [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt2]],
            'temperature' => 0.2,
            'max_tokens' => 500
        ];

        $ch2 = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode($data2),
            CURLOPT_TIMEOUT => 60
        ]);

        $response2 = curl_exec($ch2);
        curl_close($ch2);

        if ($response2) {
            $result2 = json_decode($response2, true);
            $contenido2 = trim($result2['choices'][0]['message']['content'] ?? '{"precio_encontrado":false}');
            $contenido2 = preg_replace('/```json\s*/', '', $contenido2);
            $contenido2 = preg_replace('/```\s*$/', '', $contenido2);
            
            $datos2 = json_decode($contenido2, true);
            
            if (isset($datos2['precio_encontrado']) && $datos2['precio_encontrado'] === true && isset($datos2['precio'])) {
                $resultados[] = [
                    'fuente' => $datos2['fuente'] ?? 'Google Search',
                    'precio' => floatval($datos2['precio']),
                    'url' => $datos2['url'] ?? ''
                ];
            }
        }
    }
    
    return $resultados;
}

/**
 * Método 2: Scraping Directo Mejorado
 * Busca directamente en los sitios con verificación de código
 */
function scrapingDirectoMejorado($codigo, $nombre, $apiKey, $model) {
    $sitios = [
        [
            'nombre' => 'La Colonia',
            'urls' => [
                "https://www.lacolonia.com/shop?search={$codigo}",
                "https://www.lacolonia.com/shop?search=" . urlencode($nombre)
            ]
        ],
        [
            'nombre' => 'Walmart Honduras',
            'urls' => [
                "https://www.walmart.com.hn/search?q={$codigo}",
                "https://www.walmart.com.hn/search?q=" . urlencode($nombre)
            ]
        ],
        [
            'nombre' => 'Paiz',
            'urls' => [
                "https://www.paiz.com.hn/shop?search={$codigo}",
                "https://www.paiz.com.hn/shop?search=" . urlencode($nombre)
            ]
        ],
        [
            'nombre' => 'Supermercado El Éxito',
            'urls' => [
                "https://tiendaenlinea.supermercadoelexito.hn/shop?search={$codigo}",
                "https://tiendaenlinea.supermercadoelexito.hn/shop?search=" . urlencode($nombre)
            ]
        ]
    ];
    
    $resultados = [];
    
    foreach ($sitios as $sitio) {
        foreach ($sitio['urls'] as $url) {
            $html = @file_get_contents($url, false, stream_context_create([
                'http' => [
                    'timeout' => 15,
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                ]
            ]));
            
            if ($html) {
                // Usar IA para extraer precio del HTML
                $precio = extraerPrecioConIAMejorado($html, $codigo, $nombre, $apiKey, $model);
                
                if ($precio && $precio > 0) {
                    $resultados[] = [
                        'fuente' => $sitio['nombre'],
                        'precio' => $precio,
                        'url' => $url
                    ];
                    break; // Ya encontramos en este sitio
                }
            }
            
            sleep(1); // Delay entre requests
        }
    }
    
    return $resultados;
}

function extraerPrecioConIAMejorado($html, $codigo, $nombre, $apiKey, $model) {
    // Limpiar HTML
    $htmlLimpio = strip_tags($html, '<div><span><p><article><section><h1><h2><h3>');
    $htmlMuestra = substr($htmlLimpio, 0, 10000);
    
    $prompt = "Analiza este HTML de una página de supermercado y extrae el precio del producto.

Producto buscado:
- Código: {$codigo}
- Nombre: {$nombre}

HTML:
{$htmlMuestra}

INSTRUCCIONES:
1. Busca el producto que coincida con el código O nombre
2. El precio debe estar en Lempiras (L, Lps, HNL)
3. Si hay descuento, usa el precio con descuento
4. Extrae SOLO el número del precio

FORMATO DE RESPUESTA (solo el número):
35.00

Si no encuentras el producto o el precio, responde: NO_ENCONTRADO";

    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.1,
        'max_tokens' => 50
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $result = json_decode($response, true);
        $contenido = trim($result['choices'][0]['message']['content'] ?? 'NO_ENCONTRADO');
        
        if ($contenido !== 'NO_ENCONTRADO' && preg_match('/(\d+\.?\d*)/', $contenido, $matches)) {
            return floatval($matches[1]);
        }
    }
    
    return null;
}

// Ejecutar búsqueda según método
$resultados = [];
$resultadosGuardados = 0;

switch ($metodo) {
    case 'google_ai':
        $resultados = busquedaGoogleConIA($codigo, $nombre, $GROQ_API_KEY, $GROQ_MODEL);
        break;
        
    case 'scraping_directo':
        $resultados = scrapingDirectoMejorado($codigo, $nombre, $GROQ_API_KEY, $GROQ_MODEL);
        break;
        
    case 'hibrido':
        // Intentar primero con Google AI
        $resultados = busquedaGoogleConIA($codigo, $nombre, $GROQ_API_KEY, $GROQ_MODEL);
        
        // Si no encuentra suficientes, complementar con scraping directo
        if (count($resultados) < 2) {
            $resultadosScraping = scrapingDirectoMejorado($codigo, $nombre, $GROQ_API_KEY, $GROQ_MODEL);
            $resultados = array_merge($resultados, $resultadosScraping);
        }
        break;
}

// Guardar resultados en BD
foreach ($resultados as $resultado) {
    if (isset($resultado['precio']) && $resultado['precio'] > 0) {
        $stmt = $conexion->prepare("INSERT INTO precios_competencia 
            (codigo_producto, nombre_producto, precio_competencia, fuente, url_producto, fecha_actualizacion) 
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            precio_competencia = VALUES(precio_competencia),
            fecha_actualizacion = NOW()");
        
        $stmt->bind_param("ssdss", 
            $codigo, 
            $nombre, 
            $resultado['precio'], 
            $resultado['fuente'], 
            $resultado['url']
        );
        
        if ($stmt->execute()) {
            $resultadosGuardados++;
        }
        $stmt->close();
    }
}

echo json_encode([
    'success' => $resultadosGuardados > 0,
    'resultados' => $resultadosGuardados,
    'total_encontrados' => count($resultados),
    'message' => $resultadosGuardados > 0 ? "Se encontraron {$resultadosGuardados} precios usando {$metodo}" : "No se encontraron precios",
    'codigo' => $codigo,
    'nombre' => $nombre,
    'metodo' => $metodo,
    'detalles' => $resultados
]);

$conexion->close();
?>
