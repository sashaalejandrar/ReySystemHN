<?php
/**
 * Descubridor Inteligente de Sitios Web - Honduras
 * Usa Groq AI para descubrir y validar sitios de supermercados
 */

header('Content-Type: application/json');

$GROQ_API_KEY = getenv('GROQ_API_KEY') ?: 'YOUR_GROQ_API_KEY_HERE';
$GROQ_MODEL = 'llama-3.3-70b-versatile';

/**
 * Descubre sitios de supermercados en Honduras usando IA
 */
function descubrirSitiosHonduras($apiKey, $model) {
    $prompt = "Lista TODOS los supermercados, tiendas en línea, bodegas y comercios de Honduras que venden productos de abarrotes/alimentos y tienen tienda en línea.

Para cada uno, proporciona:
1. Nombre del comercio
2. URL exacta del sitio web
3. Parámetro de búsqueda que usan (ejemplo: ?search=, ?q=, ?query=, etc.)

IMPORTANTE:
- Solo sitios que REALMENTE existen en Honduras
- Solo sitios con tienda en línea funcional
- Incluye: La Colonia, Walmart, Paiz, La Antorcha, Despensa Familiar, El Éxito, Maxi Despensa, etc.

FORMATO DE RESPUESTA (JSON):
{
  \"sitios\": [
    {
      \"nombre\": \"La Colonia\",
      \"url_base\": \"https://www.lacolonia.com\",
      \"parametro_busqueda\": \"search\",
      \"url_ejemplo\": \"https://www.lacolonia.com/shop?search=coca\"
    },
    {
      \"nombre\": \"Supermercado El Éxito\",
      \"url_base\": \"https://tiendaenlinea.supermercadoelexito.hn\",
      \"parametro_busqueda\": \"search\",
      \"url_ejemplo\": \"https://tiendaenlinea.supermercadoelexito.hn/shop?search=7428732011812\"
    }
  ]
}

Responde SOLO con el JSON, sin explicaciones.";

    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.3,
        'max_tokens' => 2000
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
        $contenido = $result['choices'][0]['message']['content'] ?? '{"sitios":[]}';
        
        // Limpiar respuesta
        $contenido = trim($contenido);
        $contenido = preg_replace('/```json\s*/', '', $contenido);
        $contenido = preg_replace('/```\s*$/', '', $contenido);
        
        $datos = json_decode($contenido, true);
        return $datos['sitios'] ?? [];
    }
    
    return [];
}

/**
 * Verifica si un sitio web existe y está accesible
 */
function verificarSitioExiste($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_NOBODY => true, // Solo headers
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode >= 200 && $httpCode < 400;
}

/**
 * Descubre el parámetro de búsqueda correcto de un sitio
 */
function descubrirParametroBusqueda($urlBase, $apiKey, $model) {
    // Intentar obtener la página principal
    $ch = curl_init($urlBase);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);
    
    $html = curl_exec($ch);
    curl_close($ch);
    
    if (!$html) {
        return null;
    }
    
    // Usar IA para analizar el HTML y encontrar el parámetro de búsqueda
    $htmlMuestra = substr(strip_tags($html, '<form><input>'), 0, 5000);
    
    $prompt = "Analiza este HTML de un sitio web de supermercado y encuentra el parámetro de búsqueda que usan.

HTML:
{$htmlMuestra}

Busca en formularios de búsqueda, inputs, o URLs el parámetro que usan para buscar productos.
Ejemplos comunes: search, q, query, s, buscar, producto, etc.

FORMATO DE RESPUESTA (solo el parámetro):
search

Si no encuentras nada, responde: NO_ENCONTRADO";

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
        $parametro = trim($result['choices'][0]['message']['content'] ?? 'NO_ENCONTRADO');
        
        if ($parametro !== 'NO_ENCONTRADO') {
            return $parametro;
        }
    }
    
    return null;
}

// Ejecutar descubrimiento
$sitiosDescubiertos = descubrirSitiosHonduras($GROQ_API_KEY, $GROQ_MODEL);

// Verificar cada sitio
$sitiosValidos = [];
foreach ($sitiosDescubiertos as $sitio) {
    $existe = verificarSitioExiste($sitio['url_base']);
    
    if ($existe) {
        // Intentar descubrir parámetro si no está definido
        if (empty($sitio['parametro_busqueda'])) {
            $sitio['parametro_busqueda'] = descubrirParametroBusqueda($sitio['url_base'], $GROQ_API_KEY, $GROQ_MODEL);
        }
        
        $sitio['verificado'] = true;
        $sitiosValidos[] = $sitio;
    }
}

echo json_encode([
    'success' => true,
    'total_descubiertos' => count($sitiosDescubiertos),
    'total_validos' => count($sitiosValidos),
    'sitios' => $sitiosValidos
], JSON_PRETTY_PRINT);
?>
