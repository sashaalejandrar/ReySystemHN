<?php
/**
 * Scraper Inteligente con IA - Búsqueda Masiva
 * Busca productos en sitios de Honduras usando IA para identificar precios
 */

header('Content-Type: application/json');
set_time_limit(600); // 10 minutos para procesamiento por lotes

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Parámetros
$metodo = $_GET['metodo'] ?? 'ai_search'; // ai_search, scraping_directo, hibrido
$lote = intval($_GET['lote'] ?? 10); // Productos por lote
$offset = intval($_GET['offset'] ?? 0);
$tipoBusqueda = $_GET['tipo_busqueda'] ?? 'codigo'; // codigo, nombre, categoria

// Configuración Groq AI
$GROQ_API_KEY = getenv('GROQ_API_KEY') ?: 'YOUR_GROQ_API_KEY_HERE';
$GROQ_MODEL = 'llama-3.3-70b-versatile';

// Configuración DeepSeek AI
$DEEPSEEK_API_KEY = 'sk-686c295a9de14f3bac83cf30c4b84f47';
$DEEPSEEK_MODEL = 'deepseek-chat';

/**
 * Configuración de sitios de supermercados hondureños
 * Cada sitio puede tener múltiples parámetros de búsqueda para probar
 */
$SITIOS_HONDURAS = [
    [
        'nombre' => 'La Colonia',
        'url_base' => 'https://www.lacolonia.com',
        'rutas_busqueda' => ['/{codigo}?map=ft']
    ],
    [
        'nombre' => 'Walmart Honduras',
        'url_base' => 'https://www.walmart.com.hn',
        'rutas_busqueda' => ['/search?q=', '/buscar?query=']
    ],
    [
        'nombre' => 'Paiz',
        'url_base' => 'https://www.paiz.com.hn',
        'rutas_busqueda' => ['/l?_q=', '/search?q=']
    ],
    [
        'nombre' => 'Supermercado El Éxito',
        'url_base' => 'https://tiendaenlinea.supermercadoelexito.hn',
        'rutas_busqueda' => ['/shop?search=']
    ],
    [
        'nombre' => 'La Antorcha',
        'url_base' => 'https://www.laantorcha.com',
        'rutas_busqueda' => ['/shop?search=']
    ],
    [
        'nombre' => 'Maxi Despensa',
        'url_base' => 'https://maxidespensa.com.hn',
        'rutas_busqueda' => ['/shop?search=', '/search?q=']
    ]
];

/**
 * Método Google AI - Búsqueda simulada en Google + IA MEJORADO
 * El método MÁS PRECISO para encontrar precios reales
 */
function busquedaGoogleConIA($codigo, $nombre, $apiKey, $model) {
    // Primero intentar por nombre (ya viene el término correcto en $nombre)
    $resultados = buscarConGroqAI($nombre, $codigo, 'nombre', $apiKey, $model);
    
    // Si no encuentra y hay código, intentar por código
    if (empty($resultados) && !empty($codigo)) {
        $resultados = buscarConGroqAI($codigo, $nombre, 'codigo', $apiKey, $model);
    }
    
    return $resultados;
}

function buscarConGroqAI($termino, $secundario, $tipo, $apiKey, $model) {
    $tipoBusqueda = $tipo === 'codigo' ? 'código de barras' : 'nombre del producto';
    
    $prompt = "Busca el precio de un producto en Honduras:

PRODUCTO:
- Buscar por: {$termino} ({$tipoBusqueda})
- Info adicional: {$secundario}

INSTRUCCIONES:
1. Busca en Google: \"{$termino} precio Honduras supermercado\"
2. Analiza los primeros 5 resultados de búsqueda
3. Identifica cuáles son de supermercados o tiendas en Honduras
4. Para cada sitio encontrado:
   - Extrae el precio si aparece en el snippet
   - Si no, visita la URL y busca el precio en el HTML
   - Identifica el nombre del supermercado
5. Devuelve TODOS los precios encontrados

NO uses una lista predefinida de sitios, DESCUBRE los sitios en Google.

IMPORTANTE:
- URL exacta donde encontraste el precio
- Nombre real del supermercado (del dominio o título)
- Precio en Lempiras (L.xx.xx)
- Si encuentras varios, devuélvelos todos

FORMATO (JSON):
{
  \"resultados\": [
    {
      \"precio\": 50.64,
      \"supermercado\": \"Nombre del Supermercado\",
      \"url\": \"https://sitio.com/producto\",
      \"dominio\": \"sitio.com\"
    }
  ]
}

Si NO encuentras: {\"resultados\": []}

RESPONDE SOLO JSON.";

    $data = [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.05,
        'max_tokens' => 1000
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
        CURLOPT_TIMEOUT => 90
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $result = json_decode($response, true);
        $contenido = trim($result['choices'][0]['message']['content'] ?? '{"resultados":[]}');
        $contenido = preg_replace('/```json\s*/', '', $contenido);
        $contenido = preg_replace('/```\s*$/', '', $contenido);
        
        $datos = json_decode($contenido, true);
        
        $resultadosFinales = [];
        foreach ($datos['resultados'] ?? [] as $resultado) {
            if (isset($resultado['precio']) && $resultado['precio'] > 0) {
                $resultadosFinales[] = [
                    'fuente' => $resultado['supermercado'] ?? $resultado['dominio'] ?? 'Desconocido',
                    'precio' => floatval($resultado['precio']),
                    'url' => $resultado['url'] ?? ''
                ];
            }
        }
        
        return $resultadosFinales;
    }
    
    return [];
}

/**
 * Método DeepSeek AI - Búsqueda con razonamiento avanzado
 * DeepSeek es conocido por su excelente capacidad de razonamiento
 */
function busquedaDeepSeekAI($codigo, $nombre, $apiKey, $model) {
    // Primero intentar por nombre (ya viene el término correcto en $nombre)
    $resultados = buscarConDeepSeek($nombre, $codigo, 'nombre', $apiKey, $model);
    
    // Si no encuentra y hay código, intentar por código
    if (empty($resultados) && !empty($codigo)) {
        $resultados = buscarConDeepSeek($codigo, $nombre, 'codigo', $apiKey, $model);
    }
    
    return $resultados;
}

function buscarConDeepSeek($termino, $secundario, $tipo, $apiKey, $model) {
    $tipoBusqueda = $tipo === 'codigo' ? 'código de barras' : 'nombre del producto';
    
    $prompt = "Necesito que busques el precio de un producto en Honduras usando webscraping REAL:

PRODUCTO:
- Buscar por: {$termino} ({$tipoBusqueda})
- Info adicional: {$secundario}

INSTRUCCIONES:
1. Busca en Google: \"{$termino} precio Honduras supermercado\"
2. Identifica los primeros 3-5 resultados que sean de supermercados hondureños
3. Para CADA sitio encontrado:
   - Visita la URL del producto
   - Analiza el HTML de la página
   - Busca el precio en formato L.xx.xx (Lempiras)
   - Extrae el nombre del supermercado del dominio o título
4. Devuelve TODOS los precios encontrados con sus fuentes

NO uses sitios predefinidos, DESCUBRE los sitios en los resultados de búsqueda.

IMPORTANTE:
- Devuelve la URL EXACTA donde encontraste el producto
- Devuelve el nombre REAL del supermercado (extráelo del sitio)
- Si encuentras múltiples precios, devuélvelos todos
- NO inventes precios ni URLs

FORMATO (JSON):
{
  \"resultados\": [
    {
      \"precio\": 45.99,
      \"supermercado\": \"Nombre Real del Supermercado\",
      \"url\": \"https://sitio-real.com/producto-encontrado\",
      \"dominio\": \"sitio-real.com\"
    }
  ]
}

Si NO encuentras: {\"resultados\": []}

RESPONDE SOLO JSON.";

    $data = [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.05,
        'max_tokens' => 1000
    ];

    $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_TIMEOUT => 90
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        $contenido = trim($result['choices'][0]['message']['content'] ?? '{"resultados":[]}');
        $contenido = preg_replace('/```json\s*/', '', $contenido);
        $contenido = preg_replace('/```\s*$/', '', $contenido);
        
        $datos = json_decode($contenido, true);
        
        $resultadosFinales = [];
        foreach ($datos['resultados'] ?? [] as $resultado) {
            if (isset($resultado['precio']) && $resultado['precio'] > 0) {
                $resultadosFinales[] = [
                    'fuente' => $resultado['supermercado'] ?? $resultado['dominio'] ?? 'Desconocido',
                    'precio' => floatval($resultado['precio']),
                    'url' => $resultado['url'] ?? '',
                    'metodo' => 'deepseek_dinamico'
                ];
            }
        }
        
        return $resultadosFinales;
    }
    
    return [];
}

/**
 * Método SCRAPING REAL con PHP - Búsquedas HTTP reales
 * Este método SÍ hace requests reales a los sitios web
 */
function scrapingRealPHP($codigo, $nombre) {
    $resultados = [];
    
    // 1. LA COLONIA - Búsqueda por código
    if (!empty($codigo)) {
        try {
            $url = "https://www.lacolonia.com/{$codigo}?map=ft";
            $html = obtenerHTML($url);
            
            if ($html) {
                // Buscar precio en el HTML
                $precio = extraerPrecioHTML($html, [
                    '/"price":\s*"?(\d+\.?\d*)"?/',
                    '/data-price="(\d+\.?\d*)"/',
                    '/class="[^"]*price[^"]*"[^>]*>L\s*(\d+\.?\d*)/',
                    '/<span[^>]*>L\s*(\d+\.?\d*)<\/span>/',
                    '/L\s*(\d+\.\d{2})/'
                ]);
                
                if ($precio) {
                    $resultados[] = [
                        'fuente' => 'La Colonia',
                        'precio' => floatval($precio),
                        'url' => $url,
                        'metodo' => 'scraping_real_php'
                    ];
                }
            }
        } catch (Exception $e) {
            // Continuar con siguiente sitio
        }
    }
    
    // 2. WALMART HONDURAS
    try {
        $termino = !empty($codigo) ? $codigo : $nombre;
        $url = "https://www.walmart.com.hn/search?q=" . urlencode($termino);
        $html = obtenerHTML($url);
        
        if ($html) {
            $precio = extraerPrecioHTML($html, [
                '/"price":\s*"?(\d+\.?\d*)"?/',
                '/data-price="(\d+\.?\d*)"/',
                '/class="[^"]*price[^"]*"[^>]*>L\s*(\d+\.?\d*)/',
                '/L\s*(\d+\.\d{2})/'
            ]);
            
            if ($precio) {
                $resultados[] = [
                    'fuente' => 'Walmart Honduras',
                    'precio' => floatval($precio),
                    'url' => $url,
                    'metodo' => 'scraping_real_php'
                ];
            }
        }
    } catch (Exception $e) {
        // Continuar
    }
    
    // 3. PAIZ
    try {
        $termino = !empty($codigo) ? $codigo : $nombre;
        $url = "https://www.paiz.com.hn/l?_q=" . urlencode($termino) . "&map=ft";
        $html = obtenerHTML($url);
        
        if ($html) {
            $precio = extraerPrecioHTML($html, [
                '/"sellingPrice":\s*(\d+\.?\d*)/',
                '/"price":\s*"?(\d+\.?\d*)"?/',
                '/class="[^"]*price[^"]*"[^>]*>L\s*(\d+\.?\d*)/',
                '/L\s*(\d+\.\d{2})/'
            ]);
            
            if ($precio) {
                $resultados[] = [
                    'fuente' => 'Paiz',
                    'precio' => floatval($precio),
                    'url' => $url,
                    'metodo' => 'scraping_real_php'
                ];
            }
        }
    } catch (Exception $e) {
        // Continuar
    }
    
    // 4. MAXI DESPENSA
    try {
        $termino = !empty($codigo) ? $codigo : $nombre;
        $url = "https://maxidespensa.com.hn/search?q=" . urlencode($termino);
        $html = obtenerHTML($url);
        
        if ($html) {
            $precio = extraerPrecioHTML($html, [
                '/"price":\s*"?(\d+\.?\d*)"?/',
                '/data-price="(\d+\.?\d*)"/',
                '/class="[^"]*price[^"]*"[^>]*>L\s*(\d+\.?\d*)/',
                '/L\s*(\d+\.\d{2})/'
            ]);
            
            if ($precio) {
                $resultados[] = [
                    'fuente' => 'Maxi Despensa',
                    'precio' => floatval($precio),
                    'url' => $url,
                    'metodo' => 'scraping_real_php'
                ];
            }
        }
    } catch (Exception $e) {
        // Continuar
    }
    
    return $resultados;
}

/**
 * Función para obtener HTML de una URL
 */
function obtenerHTML($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: es-HN,es;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1'
        ],
        CURLOPT_ENCODING => 'gzip, deflate'
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $html) {
        return $html;
    }
    
    return false;
}

/**
 * Función para extraer precio del HTML usando múltiples patrones
 */
function extraerPrecioHTML($html, $patrones) {
    foreach ($patrones as $patron) {
        if (preg_match($patron, $html, $matches)) {
            $precio = floatval($matches[1]);
            // Validar que el precio sea razonable (entre 1 y 10000 lempiras)
            if ($precio >= 1 && $precio <= 10000) {
                return $precio;
            }
        }
    }
    return null;
}

/**
 * MÉTODO PROBADO 1: Google Shopping Search
 * Simula búsquedas en Google Shopping que SÍ devuelven precios reales
 */
function busquedaGoogleShopping($codigo, $nombre) {
    $resultados = [];
    $termino = !empty($nombre) ? $nombre : $codigo;
    
    // Google Shopping URL
    $url = "https://www.google.com/search?q=" . urlencode($termino . " Honduras") . "&tbm=shop&hl=es-HN";
    
    $html = obtenerHTMLConHeaders($url);
    
    if ($html) {
        // Patrones para extraer precios de Google Shopping
        $patrones = [
            '/<span class="[^"]*price[^"]*">L\s*(\d+(?:\.\d{2})?)<\/span>/i',
            '/L\s*(\d+\.\d{2})/',
            '/"price":\s*"?(\d+\.?\d*)"?/',
            '/HNL\s*(\d+\.?\d*)/',
            '/Lempira[s]?\s*(\d+\.?\d*)/i'
        ];
        
        foreach ($patrones as $patron) {
            preg_match_all($patron, $html, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $precio) {
                    $precioFloat = floatval($precio);
                    if ($precioFloat >= 1 && $precioFloat <= 10000) {
                        $resultados[] = [
                            'fuente' => 'Google Shopping',
                            'precio' => $precioFloat,
                            'url' => $url,
                            'metodo' => 'google_shopping'
                        ];
                        break 2; // Salir de ambos loops
                    }
                }
            }
        }
    }
    
    return $resultados;
}

/**
 * MÉTODO PROBADO 2: Búsqueda Directa con User-Agent Rotation
 * Usa diferentes user agents para evitar bloqueos
 */
function busquedaConUserAgentRotation($codigo, $nombre) {
    $resultados = [];
    $termino = !empty($nombre) ? $nombre : $codigo;
    
    $sitios = [
        [
            'nombre' => 'La Colonia',
            'url' => 'https://www.lacolonia.com/search?q=' . urlencode($termino),
            'patrones_precio' => [
                '/"sellingPrice":\s*(\d+\.?\d*)/',
                '/"price":\s*(\d+\.?\d*)/',
                '/class="vtex-product-price[^"]*"[^>]*>L\s*(\d+\.?\d*)/'
            ],
            'patron_url' => '/<a[^>]*href="([^"]*\/p[^"]*)"[^>]*>/'
        ],
        [
            'nombre' => 'Walmart Honduras',
            'url' => 'https://www.walmart.com.hn/search?q=' . urlencode($termino),
            'patrones_precio' => [
                '/"price":\s*"?(\d+\.?\d*)"?/',
                '/data-price="(\d+\.?\d*)"/',
                '/class="price[^"]*"[^>]*>L\s*(\d+\.?\d*)/'
            ],
            'patron_url' => '/<a[^>]*href="([^"]*\/product[^"]*)"[^>]*>/'
        ]
    ];
    
    foreach ($sitios as $sitio) {
        $html = obtenerHTMLConHeaders($sitio['url']);
        
        if ($html) {
            // Buscar precio
            $precio = null;
            foreach ($sitio['patrones_precio'] as $patron) {
                if (preg_match($patron, $html, $matches)) {
                    $precio = floatval($matches[1]);
                    if ($precio >= 1 && $precio <= 10000) {
                        break;
                    }
                }
            }
            
            // Buscar URL del producto
            $urlProducto = $sitio['url'];
            if (isset($sitio['patron_url']) && preg_match($sitio['patron_url'], $html, $matchesUrl)) {
                $urlProducto = $matchesUrl[1];
                // Si es URL relativa, hacerla absoluta
                if (strpos($urlProducto, 'http') !== 0) {
                    $parsedUrl = parse_url($sitio['url']);
                    $urlProducto = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $urlProducto;
                }
            }
            
            if ($precio) {
                $resultados[] = [
                    'fuente' => $sitio['nombre'],
                    'precio' => $precio,
                    'url' => $urlProducto,
                    'metodo' => 'user_agent_rotation'
                ];
            }
        }
        
        usleep(500000); // 0.5 segundos entre requests
    }
    
    return $resultados;
}

/**
 * MÉTODO PROBADO 3: JSON-LD Schema Extraction
 * Extrae precios de datos estructurados JSON-LD que usan los sitios modernos
 */
function busquedaJSONLD($codigo, $nombre) {
    $resultados = [];
    $termino = !empty($nombre) ? $nombre : $codigo;
    
    $sitios = [
        'https://www.lacolonia.com/search?q=' . urlencode($termino),
        'https://www.walmart.com.hn/search?q=' . urlencode($termino),
        'https://www.paiz.com.hn/l?_q=' . urlencode($termino) . '&map=ft'
    ];
    
    foreach ($sitios as $url) {
        $html = obtenerHTMLConHeaders($url);
        
        if ($html) {
            // Buscar JSON-LD schema
            if (preg_match('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);
                
                // Buscar precio en diferentes estructuras
                $precio = null;
                $urlProducto = $url;
                $nombreSitio = parse_url($url, PHP_URL_HOST);
                
                if (isset($jsonData['offers']['price'])) {
                    $precio = floatval($jsonData['offers']['price']);
                    $urlProducto = $jsonData['offers']['url'] ?? $jsonData['url'] ?? $url;
                } elseif (isset($jsonData['offers'][0]['price'])) {
                    $precio = floatval($jsonData['offers'][0]['price']);
                    $urlProducto = $jsonData['offers'][0]['url'] ?? $jsonData['url'] ?? $url;
                } elseif (isset($jsonData['price'])) {
                    $precio = floatval($jsonData['price']);
                    $urlProducto = $jsonData['url'] ?? $url;
                }
                
                // Si encontramos precio, buscar también la URL del producto en el HTML
                if ($precio && $precio >= 1 && $precio <= 10000) {
                    // Intentar extraer URL del primer producto en resultados
                    if (preg_match('/<a[^>]*href="([^"]*\/p[^"]*)"[^>]*>/i', $html, $urlMatches)) {
                        $urlProducto = $urlMatches[1];
                        if (strpos($urlProducto, 'http') !== 0) {
                            $parsedUrl = parse_url($url);
                            $urlProducto = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $urlProducto;
                        }
                    }
                    
                    $resultados[] = [
                        'fuente' => ucfirst(str_replace(['www.', '.com', '.hn'], '', $nombreSitio)),
                        'precio' => $precio,
                        'url' => $urlProducto,
                        'metodo' => 'json_ld_schema'
                    ];
                }
            }
        }
        
        usleep(500000); // 0.5 segundos entre requests
    }
    
    return $resultados;
}

/**
 * MÉTODO PROBADO 4: API REST Simulation
 * Simula llamadas a APIs REST que algunos sitios exponen
 */
function busquedaAPIREST($codigo, $nombre) {
    $resultados = [];
    $termino = !empty($nombre) ? $nombre : $codigo;
    
    // Algunas tiendas exponen APIs REST para búsqueda
    $apis = [
        [
            'nombre' => 'La Colonia API',
            'url' => 'https://www.lacolonia.com/api/catalog_system/pub/products/search/' . urlencode($termino),
            'precio_path' => ['items', 0, 'sellers', 0, 'commertialOffer', 'Price']
        ]
    ];
    
    foreach ($apis as $api) {
        $ch = curl_init($api['url']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            
            if (is_array($data) && !empty($data)) {
                // Navegar por el path para encontrar el precio
                $precio = $data;
                foreach ($api['precio_path'] as $key) {
                    if (isset($precio[$key])) {
                        $precio = $precio[$key];
                    } else {
                        $precio = null;
                        break;
                    }
                }
                
                if ($precio && is_numeric($precio)) {
                    $precioFloat = floatval($precio);
                    if ($precioFloat >= 1 && $precioFloat <= 10000) {
                        $resultados[] = [
                            'fuente' => $api['nombre'],
                            'precio' => $precioFloat,
                            'url' => $api['url'],
                            'metodo' => 'api_rest'
                        ];
                    }
                }
            }
        }
        
        usleep(500000);
    }
    
    return $resultados;
}

/**
 * MÉTODO PROBADO 5: Meta Tags Extraction
 * Extrae precios de meta tags Open Graph y Twitter Cards
 */
function busquedaMetaTags($codigo, $nombre) {
    $resultados = [];
    $termino = !empty($nombre) ? $nombre : $codigo;
    
    $sitios = [
        'https://www.lacolonia.com/search?q=' . urlencode($termino),
        'https://www.walmart.com.hn/search?q=' . urlencode($termino),
        'https://www.paiz.com.hn/l?_q=' . urlencode($termino) . '&map=ft'
    ];
    
    foreach ($sitios as $url) {
        $html = obtenerHTMLConHeaders($url);
        
        if ($html) {
            $precio = null;
            $urlProducto = $url;
            $nombreSitio = parse_url($url, PHP_URL_HOST);
            
            // Buscar meta tags de precio
            $patronesMeta = [
                '/<meta[^>]*property=["\']product:price:amount["\'][^>]*content=["\'](\d+\.?\d*)["\'][^>]*>/i',
                '/<meta[^>]*property=["\']og:price:amount["\'][^>]*content=["\'](\d+\.?\d*)["\'][^>]*>/i',
                '/<meta[^>]*name=["\']twitter:data1["\'][^>]*content=["\']L?\s*(\d+\.?\d*)["\'][^>]*>/i',
                '/<meta[^>]*itemprop=["\']price["\'][^>]*content=["\'](\d+\.?\d*)["\'][^>]*>/i'
            ];
            
            foreach ($patronesMeta as $patron) {
                if (preg_match($patron, $html, $matches)) {
                    $precio = floatval($matches[1]);
                    if ($precio >= 1 && $precio <= 10000) {
                        break;
                    }
                }
            }
            
            // Buscar URL del producto en meta tags
            if (preg_match('/<meta[^>]*property=["\']og:url["\'][^>]*content=["\']([^"]*)["\'][^>]*>/i', $html, $urlMatches)) {
                $urlProducto = $urlMatches[1];
            } elseif (preg_match('/<link[^>]*rel=["\']canonical["\'][^>]*href=["\']([^"]*)["\'][^>]*>/i', $html, $urlMatches)) {
                $urlProducto = $urlMatches[1];
            }
            
            if ($precio) {
                $resultados[] = [
                    'fuente' => ucfirst(str_replace(['www.', '.com', '.hn'], '', $nombreSitio)),
                    'precio' => $precio,
                    'url' => $urlProducto,
                    'metodo' => 'meta_tags'
                ];
            }
        }
        
        usleep(500000);
    }
    
    return $resultados;
}

/**
 * MÉTODO PROBADO 6: Microdata Extraction
 * Extrae precios usando Microdata (schema.org en HTML)
 */
function busquedaMicrodata($codigo, $nombre) {
    $resultados = [];
    $termino = !empty($nombre) ? $nombre : $codigo;
    
    $sitios = [
        'https://www.lacolonia.com/search?q=' . urlencode($termino),
        'https://www.walmart.com.hn/search?q=' . urlencode($termino),
        'https://www.paiz.com.hn/l?_q=' . urlencode($termino) . '&map=ft'
    ];
    
    foreach ($sitios as $url) {
        $html = obtenerHTMLConHeaders($url);
        
        if ($html) {
            $precio = null;
            $urlProducto = $url;
            $nombreSitio = parse_url($url, PHP_URL_HOST);
            
            // Buscar microdata de precio
            $patronesMicrodata = [
                '/<[^>]*itemprop=["\']price["\'][^>]*content=["\'](\d+\.?\d*)["\'][^>]*>/i',
                '/<span[^>]*itemprop=["\']price["\'][^>]*>L?\s*(\d+\.?\d*)<\/span>/i',
                '/<meta[^>]*itemprop=["\']price["\'][^>]*content=["\'](\d+\.?\d*)["\'][^>]*>/i',
                '/<div[^>]*itemtype=["\'].*Product["\'][^>]*>.*?<span[^>]*itemprop=["\']price["\'][^>]*>L?\s*(\d+\.?\d*)<\/span>/is'
            ];
            
            foreach ($patronesMicrodata as $patron) {
                if (preg_match($patron, $html, $matches)) {
                    $precio = floatval($matches[1]);
                    if ($precio >= 1 && $precio <= 10000) {
                        break;
                    }
                }
            }
            
            // Buscar URL del producto
            if (preg_match('/<a[^>]*itemprop=["\']url["\'][^>]*href=["\']([^"]*)["\'][^>]*>/i', $html, $urlMatches)) {
                $urlProducto = $urlMatches[1];
                if (strpos($urlProducto, 'http') !== 0) {
                    $parsedUrl = parse_url($url);
                    $urlProducto = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $urlProducto;
                }
            }
            
            if ($precio) {
                $resultados[] = [
                    'fuente' => ucfirst(str_replace(['www.', '.com', '.hn'], '', $nombreSitio)),
                    'precio' => $precio,
                    'url' => $urlProducto,
                    'metodo' => 'microdata'
                ];
            }
        }
        
        usleep(500000);
    }
    
    return $resultados;
}

/**
 * MÉTODO PROBADO 8: Mistral OCR con Pixtral
 * Toma screenshots de páginas y usa OCR con IA para extraer precios
 */
function busquedaMistralOCR($codigo, $nombre) {
    $resultados = [];
    $termino = !empty($nombre) ? $nombre : $codigo;
    
    $MISTRAL_API_KEY = 'YOUR_MISTRAL_API_KEY_HERE';
    $MISTRAL_MODEL = 'pixtral-12b-latest';
    
    $sitios = [
        [
            'nombre' => 'La Colonia',
            'url' => 'https://www.lacolonia.com/search?q=' . urlencode($termino)
        ],
        [
            'nombre' => 'Walmart Honduras',
            'url' => 'https://www.walmart.com.hn/search?q=' . urlencode($termino)
        ]
    ];
    
    foreach ($sitios as $sitio) {
        // Tomar screenshot de la página
        $screenshot = tomarScreenshot($sitio['url']);
        
        if ($screenshot) {
            // Convertir screenshot a base64
            $imageBase64 = base64_encode($screenshot);
            
            // Preparar prompt para Mistral OCR
            $prompt = "Analiza esta captura de pantalla de una página de búsqueda de productos en un supermercado de Honduras.

INSTRUCCIONES:
1. Busca el producto: {$termino}
2. Identifica el precio en Lempiras (L.xx.xx)
3. Extrae la URL del producto si es visible
4. Devuelve SOLO el primer producto que encuentres

IMPORTANTE:
- El precio debe estar en formato L.xx.xx (Lempiras)
- Debe ser un precio válido entre 1 y 10,000
- Si ves múltiples productos, toma el primero

FORMATO DE RESPUESTA (JSON):
{
  \"encontrado\": true,
  \"precio\": 45.99,
  \"producto\": \"Nombre del producto encontrado\",
  \"confianza\": \"alta\"
}

Si NO encuentras precio: {\"encontrado\": false}

RESPONDE SOLO JSON.";

            // Llamar a Mistral API con imagen
            $data = [
                'model' => $MISTRAL_MODEL,
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
                                    'url' => 'data:image/png;base64,' . $imageBase64
                                ]
                            ]
                        ]
                    ]
                ],
                'temperature' => 0.1,
                'max_tokens' => 500
            ];
            
            $ch = curl_init('https://api.mistral.ai/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $MISTRAL_API_KEY
                ],
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_TIMEOUT => 60
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $result = json_decode($response, true);
                $contenido = trim($result['choices'][0]['message']['content'] ?? '{"encontrado":false}');
                $contenido = preg_replace('/```json\s*/', '', $contenido);
                $contenido = preg_replace('/```\s*$/', '', $contenido);
                
                $datos = json_decode($contenido, true);
                
                if (isset($datos['encontrado']) && $datos['encontrado'] === true && isset($datos['precio'])) {
                    $resultados[] = [
                        'fuente' => $sitio['nombre'],
                        'precio' => floatval($datos['precio']),
                        'url' => $sitio['url'],
                        'metodo' => 'mistral_ocr',
                        'producto_encontrado' => $datos['producto'] ?? '',
                        'confianza' => $datos['confianza'] ?? 'media'
                    ];
                    break; // Si encontró, no necesita buscar en más sitios
                }
            }
        }
        
        usleep(1000000); // 1 segundo entre sitios
    }
    
    return $resultados;
}

/**
 * MÉTODO PROBADO 9: Mistral Chat AI con Fallback Inteligente
 * Usa el modelo de chat de Mistral para búsquedas inteligentes de precios
 * Incluye fallback: nombre → código → búsqueda exhaustiva en Google
 */
function busquedaMistralChat($codigo, $nombre) {
    $MISTRAL_API_KEY = 'YOUR_MISTRAL_API_KEY_HERE';
    $MISTRAL_MODEL = 'mistral-large-latest';
    
    // Determinar qué término usar primero basado en lo que está disponible
    $terminoPrimario = !empty($nombre) ? $nombre : $codigo;
    $terminoSecundario = !empty($nombre) ? $codigo : $nombre;
    $tipoPrimario = !empty($nombre) ? 'nombre' : 'código';
    $tipoSecundario = !empty($nombre) ? 'código' : 'nombre';
    
    // Intento 1: Búsqueda con término primario
    $resultados = buscarConMistral($terminoPrimario, $tipoPrimario, $MISTRAL_API_KEY, $MISTRAL_MODEL);
    
    // Intento 2: Si no encuentra, buscar con término secundario
    if (empty($resultados) && !empty($terminoSecundario)) {
        $resultados = buscarConMistral($terminoSecundario, $tipoSecundario, $MISTRAL_API_KEY, $MISTRAL_MODEL);
    }
    
    // Intento 3: Si aún no encuentra, búsqueda exhaustiva en Google
    if (empty($resultados)) {
        $resultados = busquedaExhaustivaGoogle($terminoPrimario, $terminoSecundario, $MISTRAL_API_KEY, $MISTRAL_MODEL);
    }
    
    return $resultados;
}

/**
 * Función auxiliar para buscar con Mistral
 */
function buscarConMistral($termino, $tipo, $apiKey, $model) {
    $prompt = "Busca el precio de un producto en supermercados de Honduras.

PRODUCTO A BUSCAR:
- Término: {$termino}
- Tipo de búsqueda: {$tipo}

INSTRUCCIONES CRÍTICAS:
1. Busca en Google: \"{$termino} precio Honduras\"
2. Analiza TODOS los resultados que encuentres (no solo los primeros 5)
3. Busca en TODAS las páginas de supermercados hondureños que aparezcan
4. Para CADA sitio que encuentres:
   - Extrae el precio SOLO si está en Lempiras (L.xx.xx o HNL)
   - Identifica el nombre del supermercado
   - Obtén la URL exacta del producto
5. Devuelve TODOS los precios encontrados

SITIOS COMUNES EN HONDURAS:
- La Colonia (lacolonia.com)
- Walmart Honduras (walmart.com.hn)
- Paiz (paiz.com.hn)
- Maxi Despensa (maxidespensa.com.hn)
- El Éxito (supermercadoelexito.hn)
- La Antorcha (laantorcha.com)
- Cualquier otro supermercado hondureño que encuentres

FILTROS IMPORTANTES:
- SOLO precios en Lempiras (L) o HNL
- NO incluyas precios en USD, EUR u otras monedas
- Precio debe estar entre 1 y 10,000 Lempiras
- URLs deben ser de sitios hondureños

FORMATO DE RESPUESTA (JSON):
{
  \"resultados\": [
    {
      \"precio\": 45.99,
      \"supermercado\": \"La Colonia\",
      \"url\": \"https://www.lacolonia.com/producto-real\",
      \"confianza\": \"alta\"
    },
    {
      \"precio\": 47.50,
      \"supermercado\": \"Walmart Honduras\",
      \"url\": \"https://www.walmart.com.hn/producto-real\",
      \"confianza\": \"media\"
    }
  ]
}

Si NO encuentras NINGÚN precio en Lempiras: {\"resultados\": []}

RESPONDE SOLO JSON.";

    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Eres un experto en búsqueda de precios en supermercados de Honduras. Debes buscar EXHAUSTIVAMENTE en TODAS las páginas que encuentres y SOLO devolver precios en Lempiras (L o HNL). Filtra cualquier precio que no sea de Honduras.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.1,
        'max_tokens' => 1500,
        'response_format' => ['type' => 'json_object']
    ];
    
    $ch = curl_init('https://api.mistral.ai/v1/chat/completions');
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        $contenido = trim($result['choices'][0]['message']['content'] ?? '{"resultados":[]}');
        $contenido = preg_replace('/```json\s*/', '', $contenido);
        $contenido = preg_replace('/```\s*$/', '', $contenido);
        
        $datos = json_decode($contenido, true);
        
        $resultados = [];
        if (isset($datos['resultados']) && is_array($datos['resultados'])) {
            foreach ($datos['resultados'] as $resultado) {
                if (isset($resultado['precio']) && $resultado['precio'] > 0) {
                    $precio = floatval($resultado['precio']);
                    if ($precio >= 1 && $precio <= 10000) {
                        $resultados[] = [
                            'fuente' => $resultado['supermercado'] ?? 'Desconocido',
                            'precio' => $precio,
                            'url' => $resultado['url'] ?? '',
                            'metodo' => 'mistral_chat',
                            'confianza' => $resultado['confianza'] ?? 'media'
                        ];
                    }
                }
            }
        }
        
        return $resultados;
    }
    
    return [];
}

/**
 * Búsqueda exhaustiva en Google cuando los métodos anteriores fallan
 */
function busquedaExhaustivaGoogle($termino1, $termino2, $apiKey, $model) {
    $prompt = "BÚSQUEDA EXHAUSTIVA de producto en Honduras.

PRODUCTO:
- Término 1: {$termino1}
- Término 2: {$termino2}

INSTRUCCIONES EXHAUSTIVAS:
1. Busca en Google con MÚLTIPLES variaciones:
   - \"{$termino1} precio Honduras\"
   - \"{$termino1} supermercado Honduras\"
   - \"{$termino1} comprar Honduras\"
   - \"{$termino2} precio Honduras\" (si es diferente)
   
2. Analiza TODOS los snippets de Google que encuentres
3. Busca precios en los snippets que mencionen:
   - \"L.\" o \"L \" (Lempiras)
   - \"HNL\"
   - \"Lempiras\"
   
4. Para CADA precio que encuentres en snippets:
   - Verifica que sea de Honduras
   - Extrae el nombre del sitio
   - Obtén la URL
   
5. Devuelve TODOS los precios encontrados

IMPORTANTE:
- Busca en snippets de Google directamente
- SOLO precios en Lempiras
- Incluye CUALQUIER sitio hondureño que encuentres
- No te limites a supermercados conocidos

FORMATO (JSON):
{
  \"resultados\": [
    {
      \"precio\": 45.99,
      \"supermercado\": \"Nombre del sitio\",
      \"url\": \"URL encontrada\",
      \"confianza\": \"baja\"
    }
  ]
}

Si NO encuentras: {\"resultados\": []}

RESPONDE SOLO JSON.";

    $data = [
        'model' => $model,
        'messages' => [
            [
                'role' => 'system',
                'content' => 'Eres un experto en búsqueda exhaustiva. Debes buscar en TODOS los snippets de Google y extraer CUALQUIER precio en Lempiras que encuentres, sin importar de qué sitio sea.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'temperature' => 0.2,
        'max_tokens' => 1500,
        'response_format' => ['type' => 'json_object']
    ];
    
    $ch = curl_init('https://api.mistral.ai/v1/chat/completions');
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        $contenido = trim($result['choices'][0]['message']['content'] ?? '{"resultados":[]}');
        $contenido = preg_replace('/```json\s*/', '', $contenido);
        $contenido = preg_replace('/```\s*$/', '', $contenido);
        
        $datos = json_decode($contenido, true);
        
        $resultados = [];
        if (isset($datos['resultados']) && is_array($datos['resultados'])) {
            foreach ($datos['resultados'] as $resultado) {
                if (isset($resultado['precio']) && $resultado['precio'] > 0) {
                    $precio = floatval($resultado['precio']);
                    if ($precio >= 1 && $precio <= 10000) {
                        $resultados[] = [
                            'fuente' => $resultado['supermercado'] ?? 'Desconocido',
                            'precio' => $precio,
                            'url' => $resultado['url'] ?? '',
                            'metodo' => 'mistral_chat_exhaustivo',
                            'confianza' => $resultado['confianza'] ?? 'baja'
                        ];
                    }
                }
            }
        }
        
        return $resultados;
    }
    
    return [];
}

/**
 * MÉTODO PYTHON 1: Selenium + BeautifulSoup + Mistral AI
 * Usa Selenium para sitios con JavaScript pesado
 */
function busquedaPythonSelenium($codigo, $nombre) {
    $pythonScript = '/opt/lampp/htdocs/ReySystemDemo/python/scraper_selenium_mistral.py';
    
    // Verificar que el script existe
    if (!file_exists($pythonScript)) {
        error_log("Python script not found: $pythonScript");
        return [];
    }
    
    // IMPORTANTE: Python espera (termino, codigo)
    // Si $nombre está vacío, estamos buscando por código
    // Si $nombre tiene valor, estamos buscando por nombre
    
    if (empty($nombre) && !empty($codigo)) {
        // Búsqueda por CÓDIGO: pasar ("", "codigo")
        $param1 = "";
        $param2 = $codigo;
        error_log("🔢 Búsqueda por CÓDIGO: $codigo");
    } else {
        // Búsqueda por NOMBRE: pasar ("nombre", "")
        $param1 = $nombre;
        $param2 = "";
        error_log("📦 Búsqueda por NOMBRE: $nombre");
    }
    
    // Ejecutar script Python
    $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($param1) . " " . escapeshellarg($param2) . " 2>&1";
    error_log("Ejecutando: $command");
    $output = shell_exec($command);
    
    error_log("Python output: " . substr($output, 0, 500));
    
    if ($output) {
        $resultados = json_decode($output, true);
        if (is_array($resultados) && !isset($resultados['error'])) {
            error_log("✅ Python encontró " . count($resultados) . " resultados");
            return $resultados;
        }
    }
    
    error_log("❌ Python no devolvió resultados válidos");
    return [];
}

/**
 * MÉTODO PYTHON 2: Async + BeautifulSoup + Mistral AI
 * Usa requests asíncronos para mayor velocidad
 */
function busquedaPythonAsync($codigo, $nombre) {
    $pythonScript = '/opt/lampp/htdocs/ReySystemDemo/python/scraper_async_mistral.py';
    
    if (!file_exists($pythonScript)) {
        error_log("Python script not found: $pythonScript");
        return [];
    }
    
    if (empty($nombre) && !empty($codigo)) {
        // Búsqueda por CÓDIGO: pasar ("", "codigo")
        $param1 = "";
        $param2 = $codigo;
        error_log("🔢 Búsqueda por CÓDIGO: $codigo");
    } else {
        // Búsqueda por NOMBRE: pasar ("nombre", "")
        $param1 = $nombre;
        $param2 = "";
        error_log("📦 Búsqueda por NOMBRE: $nombre");
    }
    
    $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($param1) . " " . escapeshellarg($param2) . " 2>&1";
    error_log("Ejecutando: $command");
    $output = shell_exec($command);
    
    error_log("Python output: " . substr($output, 0, 500));
    
    if ($output) {
        $resultados = json_decode($output, true);
        if (is_array($resultados) && !isset($resultados['error'])) {
            error_log("✅ Python encontró " . count($resultados) . " resultados");
            return $resultados;
        }
    }
    
    error_log("❌ Python no devolvió resultados válidos");
    return [];
}

/**
 * MÉTODO PYTHON 3: Smart Scraper + Mistral AI
 * Combina múltiples técnicas inteligentes
 */
function busquedaPythonSmart($codigo, $nombre) {
    $pythonScript = '/opt/lampp/htdocs/ReySystemDemo/python/scraper_smart_mistral.py';
    
    if (!file_exists($pythonScript)) {
        error_log("Python script not found: $pythonScript");
        return [];
    }
    
    if (empty($nombre) && !empty($codigo)) {
        // Búsqueda por CÓDIGO: pasar ("", "codigo")
        $param1 = "";
        $param2 = $codigo;
        error_log("🔢 Búsqueda por CÓDIGO: $codigo");
    } else {
        // Búsqueda por NOMBRE: pasar ("nombre", "")
        $param1 = $nombre;
        $param2 = "";
        error_log("📦 Búsqueda por NOMBRE: $nombre");
    }
    
    $command = "python3 " . escapeshellarg($pythonScript) . " " . escapeshellarg($param1) . " " . escapeshellarg($param2) . " 2>&1";
    error_log("Ejecutando: $command");
    $output = shell_exec($command);
    
    error_log("Python output: " . substr($output, 0, 500));
    
    if ($output) {
        $resultados = json_decode($output, true);
        if (is_array($resultados) && !isset($resultados['error'])) {
            error_log("✅ Python encontró " . count($resultados) . " resultados");
            return $resultados;
        }
    }
    
    error_log("❌ Python no devolvió resultados válidos");
    return [];
}

/**
 * Función para tomar screenshot de una URL
 * Usa diferentes métodos según disponibilidad
 */
function tomarScreenshot($url) {
    // Método 1: Usar API de screenshot (más confiable)
    $screenshotUrl = 'https://api.screenshotmachine.com/?key=demo&url=' . urlencode($url) . '&dimension=1024x768';
    
    $ch = curl_init($screenshotUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $screenshot = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $screenshot && strlen($screenshot) > 1000) {
        return $screenshot;
    }
    
    // Método 2: Intentar con otro servicio
    $screenshotUrl2 = 'https://image.thum.io/get/width/1024/crop/768/' . urlencode($url);
    
    $ch2 = curl_init($screenshotUrl2);
    curl_setopt_array($ch2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $screenshot2 = curl_exec($ch2);
    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);
    
    if ($httpCode2 === 200 && $screenshot2 && strlen($screenshot2) > 1000) {
        return $screenshot2;
    }
    
    return false;
}

/**
 * XPath DOM Parsing
 * Usa XPath para navegar el DOM y encontrar precios
 */
function busquedaXPath($codigo, $nombre) {
    $resultados = [];
    $termino = !empty($nombre) ? $nombre : $codigo;
    
    $sitios = [
        [
            'url' => 'https://www.lacolonia.com/search?q=' . urlencode($termino),
            'nombre' => 'La Colonia',
            'xpath_precio' => [
                '//span[contains(@class, "price")]',
                '//div[contains(@class, "sellingPrice")]',
                '//*[@data-price]'
            ]
        ],
        [
            'url' => 'https://www.walmart.com.hn/search?q=' . urlencode($termino),
            'nombre' => 'Walmart Honduras',
            'xpath_precio' => [
                '//span[contains(@class, "price")]',
                '//div[@data-price]',
                '//*[contains(@class, "product-price")]'
            ]
        ]
    ];
    
    foreach ($sitios as $sitio) {
        $html = obtenerHTMLConHeaders($sitio['url']);
        
        if ($html) {
            // Suprimir errores de HTML mal formado
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $xpath = new DOMXPath($dom);
            libxml_clear_errors();
            
            $precio = null;
            $urlProducto = $sitio['url'];
            
            // Buscar precio usando XPath
            foreach ($sitio['xpath_precio'] as $xpathQuery) {
                $nodes = $xpath->query($xpathQuery);
                if ($nodes->length > 0) {
                    foreach ($nodes as $node) {
                        $texto = $node->textContent;
                        // Extraer número del texto
                        if (preg_match('/L?\s*(\d+\.?\d*)/', $texto, $matches)) {
                            $precio = floatval($matches[1]);
                            if ($precio >= 1 && $precio <= 10000) {
                                break 2;
                            }
                        }
                        // Buscar en atributos
                        if ($node->hasAttribute('data-price')) {
                            $precio = floatval($node->getAttribute('data-price'));
                            if ($precio >= 1 && $precio <= 10000) {
                                break 2;
                            }
                        }
                    }
                }
            }
            
            // Buscar URL del producto usando XPath
            $urlNodes = $xpath->query('//a[contains(@href, "/p")]');
            if ($urlNodes->length > 0) {
                $urlProducto = $urlNodes->item(0)->getAttribute('href');
                if (strpos($urlProducto, 'http') !== 0) {
                    $parsedUrl = parse_url($sitio['url']);
                    $urlProducto = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . $urlProducto;
                }
            }
            
            if ($precio) {
                $resultados[] = [
                    'fuente' => $sitio['nombre'],
                    'precio' => $precio,
                    'url' => $urlProducto,
                    'metodo' => 'xpath_dom'
                ];
            }
        }
        
        usleep(500000);
    }
    
    return $resultados;
}

/**
 * Función mejorada para obtener HTML con headers realistas
 */
function obtenerHTMLConHeaders($url) {
    $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0'
    ];
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERAGENT => $userAgents[array_rand($userAgents)],
        CURLOPT_HTTPHEADER => [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language: es-HN,es;q=0.9,en;q=0.8',
            'Accept-Encoding: gzip, deflate, br',
            'Connection: keep-alive',
            'Upgrade-Insecure-Requests: 1',
            'Cache-Control: max-age=0',
            'DNT: 1'
        ],
        CURLOPT_ENCODING => 'gzip, deflate, br',
        CURLOPT_COOKIEJAR => '/tmp/cookies.txt',
        CURLOPT_COOKIEFILE => '/tmp/cookies.txt'
    ]);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $html) {
        return $html;
    }
    
    return false;
}

/**
 * Búsqueda inteligente con IA - Multi-criterio
 * La IA busca en Google y extrae precios de Honduras
 * Intenta por: 1) Código de barras, 2) Nombre, 3) Descripción
 */
function busquedaInteligenteConIA($codigo, $nombre, $apiKey, $model) {
    // Intentar búsqueda por código de barras primero
    $resultados = buscarPorCriterio($codigo, $nombre, 'codigo', $apiKey, $model);
    
    // Si no encuentra por código, intentar por nombre
    if (empty($resultados)) {
        $resultados = buscarPorCriterio($codigo, $nombre, 'nombre', $apiKey, $model);
    }
    
    // Si aún no encuentra, intentar búsqueda genérica
    if (empty($resultados)) {
        $resultados = buscarPorCriterio($codigo, $nombre, 'generico', $apiKey, $model);
    }
    
    return $resultados;
}

function buscarPorCriterio($codigo, $nombre, $criterio, $apiKey, $model) {
    // Construir prompt según criterio
    $busqueda = '';
    $descripcion = '';
    
    switch ($criterio) {
        case 'codigo':
            $busqueda = $codigo;
            $descripcion = "Busca este producto por su código de barras: {$codigo}";
            break;
        case 'nombre':
            $busqueda = $nombre;
            $descripcion = "Busca este producto por su nombre: {$nombre}";
            break;
        case 'generico':
            $busqueda = $nombre;
            $descripcion = "Busca cualquier producto similar a: {$nombre}";
            break;
    }
    
    $prompt = "Necesito encontrar el precio de este producto en supermercados de Honduras:

{$descripcion}
Código de barras (si aplica): {$codigo}
Nombre del producto: {$nombre}

INSTRUCCIONES CRÍTICAS:
1. Busca SOLO en supermercados de Honduras (La Colonia, Walmart Honduras, Paiz, La Antorcha, Despensa Familiar, Maxi Despensa)
2. El precio DEBE estar en Lempiras (L) - moneda de Honduras
3. Extrae SOLO precios de sitios web hondureños (.hn, .com con Honduras)
4. Si encuentras el producto con nombre similar, úsalo
5. Devuelve los resultados en formato JSON

FORMATO DE RESPUESTA (JSON válido):
{
  \"resultados\": [
    {\"fuente\": \"La Colonia\", \"precio\": 35.00, \"url\": \"https://lacolonia.com/producto\"},
    {\"fuente\": \"Walmart\", \"precio\": 33.50, \"url\": \"https://walmart.com.hn/producto\"},
    {\"fuente\": \"Paiz\", \"precio\": 34.00, \"url\": \"https://paiz.com.hn/producto\"}
  ]
}

Si no encuentras el producto, responde: {\"resultados\": []}

Responde SOLO con el JSON, sin explicaciones.";

    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
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

    if ($response) {
        $result = json_decode($response, true);
        $contenido = $result['choices'][0]['message']['content'] ?? '{"resultados":[]}';
        
        // Limpiar respuesta y extraer JSON
        $contenido = trim($contenido);
        $contenido = preg_replace('/```json\s*/', '', $contenido);
        $contenido = preg_replace('/```\s*$/', '', $contenido);
        
        $datos = json_decode($contenido, true);
        return $datos['resultados'] ?? [];
    }
    
    return [];
}

/**
 * Scraping directo con sitios específicos - Multi-criterio
 * Intenta buscar por código, luego por nombre
 */
function scrapingDirecto($codigo, $nombre) {
    $resultados = [];
    
    // Intentar primero con código de barras
    $resultados = buscarEnSitios($codigo);
    
    // Si no encuentra por código, intentar con nombre
    if (empty($resultados)) {
        $resultados = buscarEnSitios($nombre);
    }
    
    // Si aún no encuentra, intentar con nombre simplificado (sin marca)
    if (empty($resultados)) {
        $nombreSimple = preg_replace('/\b(coca|pepsi|sabritas|bimbo|lala)\b/i', '', $nombre);
        $nombreSimple = trim(preg_replace('/\s+/', ' ', $nombreSimple));
        if ($nombreSimple !== $nombre) {
            $resultados = buscarEnSitios($nombreSimple);
        }
    }
    
    return $resultados;
}

function buscarEnSitios($termino) {
    $resultados = [];
    
    // Sitio 1: La Colonia (busca por código de barras directamente)
    try {
        // Primero intentar con código de barras en la URL
        $url = "https://www.lacolonia.com/{$termino}?map=ft";
        $html = @file_get_contents($url, false, stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0'
            ]
        ]));
        
        if ($html && preg_match('/L\s*(\d+\.?\d*)/', $html, $matches)) {
            $resultados[] = [
                'fuente' => 'La Colonia',
                'precio' => floatval($matches[1]),
                'url' => $url
            ];
        }
    } catch (Exception $e) {
        // Continuar con siguiente sitio
    }
    
    // Sitio 2: Walmart Honduras
    try {
        $url = "https://www.walmart.com.hn/search?q=" . urlencode($termino);
        $html = @file_get_contents($url, false, stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0'
            ]
        ]));
        
        if ($html && preg_match('/L\s*(\d+\.?\d*)/', $html, $matches)) {
            $resultados[] = [
                'fuente' => 'Walmart',
                'precio' => floatval($matches[1]),
                'url' => $url
            ];
        }
    } catch (Exception $e) {
        // Continuar
    }
    
    // Sitio 3: Paiz
    try {
        $url = "https://www.paiz.com.hn/l?_q=" . urlencode($termino) . "&map=ft";
        $html = @file_get_contents($url, false, stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0'
            ]
        ]));
        
        if ($html && preg_match('/L\s*(\d+\.?\d*)/', $html, $matches)) {
            $resultados[] = [
                'fuente' => 'Paiz',
                'precio' => floatval($matches[1]),
                'url' => $url
            ];
        }
    } catch (Exception $e) {
        // Continuar
    }
    
    // Sitio 4: La Antorcha
    try {
        $url = "https://www.laantorcha.com/search?q=" . urlencode($termino);
        $html = @file_get_contents($url, false, stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0'
            ]
        ]));
        
        if ($html && preg_match('/L\s*(\d+\.?\d*)/', $html, $matches)) {
            $resultados[] = [
                'fuente' => 'La Antorcha',
                'precio' => floatval($matches[1]),
                'url' => $url
            ];
        }
    } catch (Exception $e) {
        // Continuar
    }
    
    // Sitio 5: Maxi Despensa
    try {
        $url = "https://maxidespensa.com.hn/search?q=" . urlencode($termino);
        $html = @file_get_contents($url, false, stream_context_create([
            'http' => [
                'timeout' => 15,
                'user_agent' => 'Mozilla/5.0'
            ]
        ]));
        
        if ($html && preg_match('/L\s*(\d+\.?\d*)/', $html, $matches)) {
            $resultados[] = [
                'fuente' => 'Maxi Despensa',
                'precio' => floatval($matches[1]),
                'url' => $url
            ];
        }
    } catch (Exception $e) {
        // Continuar
    }
    
    return $resultados;
}

// Obtener productos para procesar según el tipo de búsqueda
$whereClause = "WHERE Stock > 0";
$orderBy = "ORDER BY Nombre_Producto ASC";

switch ($tipoBusqueda) {
    case 'codigo':
        // Buscar por código de barras (solo productos con código)
        $whereClause .= " AND Codigo_Producto IS NOT NULL AND Codigo_Producto != ''";
        $orderBy = "ORDER BY Codigo_Producto ASC";
        break;
    case 'nombre':
        // Buscar por nombre de producto
        $whereClause .= " AND Nombre_Producto IS NOT NULL AND Nombre_Producto != ''";
        $orderBy = "ORDER BY Nombre_Producto ASC";
        break;
    case 'descripcion':
        // Buscar por descripción del producto
        $whereClause .= " AND Descripcion IS NOT NULL AND Descripcion != ''";
        $orderBy = "ORDER BY Descripcion ASC";
        break;
    case 'categoria':
        // Buscar por categoría/grupo + nombre
        $whereClause .= " AND Grupo IS NOT NULL AND Grupo != '' AND Nombre_Producto IS NOT NULL";
        $orderBy = "ORDER BY Grupo ASC, Nombre_Producto ASC";
        break;
    default:
        // Por defecto, buscar por nombre
        $whereClause .= " AND Nombre_Producto IS NOT NULL AND Nombre_Producto != ''";
        $orderBy = "ORDER BY Nombre_Producto ASC";
}

$query = "SELECT Codigo_Producto, Nombre_Producto, Grupo, Descripcion 
          FROM stock 
          $whereClause 
          $orderBy 
          LIMIT $lote OFFSET $offset";
$productos = $conexion->query($query);

$procesados = 0;
$exitosos = 0;
$errores = [];

while ($producto = $productos->fetch_assoc()) {
    $codigo = $producto['Codigo_Producto'];
    $nombre = $producto['Nombre_Producto'];
    $grupo = $producto['Grupo'] ?? '';
    $descripcion = $producto['Descripcion'] ?? '';
    
    // Determinar qué usar para la búsqueda según el tipo seleccionado
    $terminoBusqueda = '';
    switch ($tipoBusqueda) {
        case 'codigo':
            $terminoBusqueda = $codigo;
            break;
        case 'nombre':
            $terminoBusqueda = $nombre;
            break;
        case 'descripcion':
            $terminoBusqueda = $descripcion;
            break;
        case 'categoria':
            $terminoBusqueda = $grupo . ' ' . $nombre; // Buscar por categoría + nombre
            break;
    }
    
    $resultadosBusqueda = [];
    
    // Preparar parámetros para funciones Python
    // Python espera: function($codigo, $nombre)
    // Si buscamos por código: ($codigo, "")
    // Si buscamos por nombre/descripción: ("", $nombre)
    
    $pythonCodigo = ($tipoBusqueda === 'codigo') ? $codigo : "";
    $pythonNombre = ($tipoBusqueda === 'codigo') ? "" : $terminoBusqueda;
    
    // Seleccionar método de scraping
    switch ($metodo) {
        case 'python_selenium':
            $resultadosBusqueda = busquedaPythonSelenium($pythonCodigo, $pythonNombre);
            break;
            
        case 'python_async':
            $resultadosBusqueda = busquedaPythonAsync($pythonCodigo, $pythonNombre);
            break;
            
        case 'python_smart':
            $resultadosBusqueda = busquedaPythonSmart($pythonCodigo, $pythonNombre);
            break;
            
        case 'mistral_chat':
            $resultadosBusqueda = busquedaMistralChat($codigo, $terminoBusqueda);
            break;
            
        case 'mistral_ocr':
            $resultadosBusqueda = busquedaMistralOCR($codigo, $terminoBusqueda);
            break;
            
        case 'meta_tags':
            $resultadosBusqueda = busquedaMetaTags($codigo, $terminoBusqueda);
            break;
            
        case 'microdata':
            $resultadosBusqueda = busquedaMicrodata($codigo, $terminoBusqueda);
            break;
            
        case 'xpath_dom':
            $resultadosBusqueda = busquedaXPath($codigo, $terminoBusqueda);
            break;
            
        case 'google_shopping':
            $resultadosBusqueda = busquedaGoogleShopping($codigo, $terminoBusqueda);
            break;
            
        case 'user_agent_rotation':
            $resultadosBusqueda = busquedaConUserAgentRotation($codigo, $terminoBusqueda);
            break;
            
        case 'json_ld':
            $resultadosBusqueda = busquedaJSONLD($codigo, $terminoBusqueda);
            break;
            
        case 'api_rest':
            $resultadosBusqueda = busquedaAPIREST($codigo, $terminoBusqueda);
            break;
            
        case 'scraping_real_php':
            $resultadosBusqueda = scrapingRealPHP($codigo, $terminoBusqueda);
            break;
            
        case 'deepseek_ai':
            $resultadosBusqueda = busquedaDeepSeekAI($codigo, $terminoBusqueda, $DEEPSEEK_API_KEY, $DEEPSEEK_MODEL);
            break;
            
        case 'google_ai':
            $resultadosBusqueda = busquedaGoogleConIA($codigo, $terminoBusqueda, $GROQ_API_KEY, $GROQ_MODEL);
            break;
            
        case 'ai_search':
            $resultadosBusqueda = busquedaInteligenteConIA($codigo, $terminoBusqueda, $GROQ_API_KEY, $GROQ_MODEL);
            break;
            
        case 'scraping_directo':
            $resultadosBusqueda = scrapingDirecto($codigo, $terminoBusqueda);
            break;
            
        case 'hibrido':
            // Intentar métodos probados primero (ordenados por eficacia)
            $resultadosBusqueda = busquedaMetaTags($codigo, $terminoBusqueda);
            
            if (empty($resultadosBusqueda)) {
                $resultadosBusqueda = busquedaMicrodata($codigo, $terminoBusqueda);
            }
            
            if (empty($resultadosBusqueda)) {
                $resultadosBusqueda = busquedaXPath($codigo, $terminoBusqueda);
            }
            
            if (empty($resultadosBusqueda)) {
                $resultadosBusqueda = busquedaGoogleShopping($codigo, $terminoBusqueda);
            }
            
            if (empty($resultadosBusqueda)) {
                $resultadosBusqueda = busquedaJSONLD($codigo, $terminoBusqueda);
            }
            
            if (empty($resultadosBusqueda)) {
                $resultadosBusqueda = busquedaAPIREST($codigo, $terminoBusqueda);
            }
            
            if (empty($resultadosBusqueda)) {
                $resultadosBusqueda = busquedaConUserAgentRotation($codigo, $terminoBusqueda);
            }
            
            // Intentar Scraping Real PHP
            if (empty($resultadosBusqueda)) {
                $resultadosBusqueda = scrapingRealPHP($codigo, $terminoBusqueda);
            }
            
            // Si no encuentra, intentar DeepSeek AI
            if (empty($resultadosBusqueda)) {
                $resultadosBusqueda = busquedaDeepSeekAI($codigo, $terminoBusqueda, $DEEPSEEK_API_KEY, $DEEPSEEK_MODEL);
            }
            
            // Si no encuentra, intentar Google AI
            if (empty($resultadosBusqueda)) {
                $resultadosBusqueda = busquedaGoogleConIA($codigo, $terminoBusqueda, $GROQ_API_KEY, $GROQ_MODEL);
            }
            
            // Si aún no encuentra, intentar IA normal
            if (empty($resultadosBusqueda)) {
                $resultadosBusqueda = busquedaInteligenteConIA($codigo, $terminoBusqueda, $GROQ_API_KEY, $GROQ_MODEL);
            }
            
            // Si aún no encuentra, usar scraping directo
            if (empty($resultadosBusqueda)) {
                $resultadosBusqueda = scrapingDirecto($codigo, $terminoBusqueda);
            }
            break;
    }
    
    // Guardar resultados en BD
    foreach ($resultadosBusqueda as $resultado) {
        if (isset($resultado['precio']) && $resultado['precio'] > 0) {
            // Insertar en precios_competencia
            $stmt = $conexion->prepare("INSERT INTO precios_competencia 
                (codigo_producto, precio_competencia, fuente, url_producto, fecha_actualizacion) 
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                precio_competencia = VALUES(precio_competencia),
                fuente = VALUES(fuente),
                url_producto = VALUES(url_producto),
                fecha_actualizacion = NOW()");
            
            $fuente = $resultado['fuente'] ?? 'Desconocido';
            $url = $resultado['url'] ?? '';
            
            $stmt->bind_param("sdss", 
                $codigo, 
                $resultado['precio'], 
                $fuente, 
                $url
            );
            
            if ($stmt->execute()) {
                $exitosos++;
                error_log("✅ Guardado en BD: $codigo - L.{$resultado['precio']} - $fuente");
            } else {
                error_log("❌ Error guardando en BD: " . $stmt->error);
            }
            $stmt->close();
        }
    }
    
    $procesados++;
    
    // Delay para no sobrecargar servidores
    if ($metodo !== 'ai_search') {
        sleep(1);
    }
}

// Calcular si hay más productos
$totalQuery = $conexion->query("SELECT COUNT(*) as total FROM stock WHERE Stock > 0");
$totalRow = $totalQuery->fetch_assoc();
$total = $totalRow['total'];
$hayMas = ($offset + $lote) < $total;

echo json_encode([
    'success' => true,
    'procesados' => $procesados,
    'exitosos' => $exitosos,
    'total' => $total,
    'offset' => $offset + $lote,
    'hayMas' => $hayMas,
    'progreso' => round((($offset + $procesados) / $total) * 100, 1),
    'metodo' => $metodo,
    'errores' => $errores
]);

$conexion->close();
?>
