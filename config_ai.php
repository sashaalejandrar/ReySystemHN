<?php
/**
 * Configuración de APIs de Inteligencia Artificial
 * Mistral AI y Groq para diagnóstico y corrección automática
 */

// APIs de IA
define('MISTRAL_API_KEY', getenv('MISTRAL_API_KEY') ?: 'YOUR_MISTRAL_API_KEY_HERE');
define('MISTRAL_API_URL', 'https://api.mistral.ai/v1/chat/completions');
define('MISTRAL_MODEL', 'mistral-large-latest');

define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: 'YOUR_GROQ_API_KEY_HERE');
define('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
define('GROQ_MODEL', 'llama-3.1-70b-versatile');

// Configuración del sistema de diagnóstico
define('DIAGNOSTIC_BACKUP_DIR', __DIR__ . '/backups/diagnostic/');
define('DIAGNOSTIC_LOG_FILE', __DIR__ . '/logs/diagnostic.log');
define('MAX_FILES_PER_SCAN', 50); // Límite de archivos por escaneo
define('SCAN_EXTENSIONS', ['php']); // Extensiones a escanear

// Crear directorios si no existen
if (!is_dir(DIAGNOSTIC_BACKUP_DIR)) {
    mkdir(DIAGNOSTIC_BACKUP_DIR, 0755, true);
}

if (!is_dir(dirname(DIAGNOSTIC_LOG_FILE))) {
    mkdir(dirname(DIAGNOSTIC_LOG_FILE), 0755, true);
}

/**
 * Función para hacer llamadas a la API de Mistral
 */
function callMistralAPI($messages, $temperature = 0.3) {
    $data = [
        'model' => MISTRAL_MODEL,
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => 4000
    ];

    $ch = curl_init(MISTRAL_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . MISTRAL_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'Mistral API error: ' . $httpCode];
    }

    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        return ['success' => true, 'content' => $result['choices'][0]['message']['content']];
    }

    return ['success' => false, 'error' => 'Invalid response from Mistral'];
}

/**
 * Función para hacer llamadas a la API de Groq
 */
function callGroqAPI($messages, $temperature = 0.3) {
    $data = [
        'model' => GROQ_MODEL,
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => 4000
    ];

    $ch = curl_init(GROQ_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'Groq API error: ' . $httpCode];
    }

    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        return ['success' => true, 'content' => $result['choices'][0]['message']['content']];
    }

    return ['success' => false, 'error' => 'Invalid response from Groq'];
}

/**
 * Función para llamar a IA con fallback automático
 */
function callAIWithFallback($messages, $temperature = 0.3) {
    // Intentar primero con Mistral
    $result = callMistralAPI($messages, $temperature);
    
    if ($result['success']) {
        return ['success' => true, 'content' => $result['content'], 'provider' => 'Mistral'];
    }

    // Si falla, intentar con Groq
    $result = callGroqAPI($messages, $temperature);
    
    if ($result['success']) {
        return ['success' => true, 'content' => $result['content'], 'provider' => 'Groq'];
    }

    return ['success' => false, 'error' => 'Ambas APIs fallaron'];
}

/**
 * Función para registrar en el log
 */
function logDiagnostic($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    file_put_contents(DIAGNOSTIC_LOG_FILE, $logMessage, FILE_APPEND);
}
