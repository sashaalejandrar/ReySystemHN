<?php
/**
 * Sistema de Detección Automática de Parámetros de Búsqueda
 * Usa DeepSeek AI para descubrir cómo buscar en cada sitio web
 */

header('Content-Type: application/json');
set_time_limit(300);

$DEEPSEEK_API_KEY = 'sk-686c295a9de14f3bac83cf30c4b84f47';
$DEEPSEEK_MODEL = 'deepseek-chat';

$sitios = [
    [
        'nombre' => 'La Colonia',
        'url_base' => 'https://www.lacolonia.com',
        'codigo_prueba' => '7501055363483'
    ],
    [
        'nombre' => 'Walmart Honduras',
        'url_base' => 'https://www.walmart.com.hn',
        'codigo_prueba' => '7501055363483'
    ],
    [
        'nombre' => 'Paiz',
        'url_base' => 'https://www.paiz.com.hn',
        'codigo_prueba' => '7501055363483'
    ],
    [
        'nombre' => 'Supermercado El Éxito',
        'url_base' => 'https://tiendaenlinea.supermercadoelexito.hn',
        'codigo_prueba' => '7501055363483'
    ],
    [
        'nombre' => 'La Antorcha',
        'url_base' => 'https://www.laantorcha.com',
        'codigo_prueba' => '7501055363483'
    ],
    [
        'nombre' => 'Maxi Despensa',
        'url_base' => 'https://maxidespensa.com.hn',
        'codigo_prueba' => '7501055363483'
    ]
];

$resultados = [];

foreach ($sitios as $sitio) {
    $parametros = detectarParametrosBusqueda($sitio, $DEEPSEEK_API_KEY, $DEEPSEEK_MODEL);
    $resultados[] = array_merge($sitio, $parametros);
    sleep(2); // Delay entre requests
}

echo json_encode([
    'success' => true,
    'sitios' => $resultados,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);

function detectarParametrosBusqueda($sitio, $apiKey, $model) {
    $prompt = "Necesito que analices cómo funciona la búsqueda en este sitio web de supermercado:

SITIO: {$sitio['nombre']}
URL BASE: {$sitio['url_base']}

TAREA:
1. Visita el sitio web {$sitio['url_base']}
2. Encuentra cómo funciona su buscador de productos
3. Identifica el parámetro de búsqueda exacto que usan

EJEMPLOS DE PARÁMETROS COMUNES:
- ?search=TERMINO
- ?q=TERMINO
- ?query=TERMINO
- /search?q=TERMINO
- /l?_q=TERMINO&map=ft
- /CODIGO_BARRAS?map=ft (para búsqueda por código)

INSTRUCCIONES:
- Analiza el HTML del sitio
- Busca formularios de búsqueda
- Identifica el parámetro GET que usan
- Si usan código de barras directo en URL, indícalo
- Dame la URL EXACTA de ejemplo

FORMATO DE RESPUESTA (JSON):
{
  \"parametro_nombre\": \"search\",
  \"parametro_codigo\": \"codigo_barras_en_url\",
  \"url_ejemplo_nombre\": \"https://sitio.com/search?q=coca+cola\",
  \"url_ejemplo_codigo\": \"https://sitio.com/7501055363483?map=ft\",
  \"metodo\": \"GET\",
  \"notas\": \"Explicación de cómo funciona\"
}

Si NO puedes determinar: {\"error\": \"No se pudo detectar\"}

RESPONDE SOLO JSON.";

    $data = [
        'model' => $model,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'temperature' => 0.1,
        'max_tokens' => 800
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
        CURLOPT_TIMEOUT => 120
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $result = json_decode($response, true);
        $contenido = trim($result['choices'][0]['message']['content'] ?? '{"error":"No response"}');
        $contenido = preg_replace('/```json\s*/', '', $contenido);
        $contenido = preg_replace('/```\s*$/', '', $contenido);
        
        $datos = json_decode($contenido, true);
        
        if (isset($datos['parametro_nombre'])) {
            return [
                'parametro_nombre' => $datos['parametro_nombre'],
                'parametro_codigo' => $datos['parametro_codigo'] ?? $datos['parametro_nombre'],
                'url_ejemplo_nombre' => $datos['url_ejemplo_nombre'] ?? '',
                'url_ejemplo_codigo' => $datos['url_ejemplo_codigo'] ?? '',
                'metodo' => $datos['metodo'] ?? 'GET',
                'notas' => $datos['notas'] ?? '',
                'detectado' => true
            ];
        }
    }
    
    return [
        'parametro_nombre' => 'search',
        'parametro_codigo' => 'search',
        'detectado' => false,
        'error' => 'No se pudo detectar automáticamente'
    ];
}
?>
