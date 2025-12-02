<?php
/**
 * Features Loader - Carga automática de features habilitadas
 * Incluir este archivo en el header de todas las páginas
 */

$features_config = require_once __DIR__ . '/config.php';

function loadFeatureScripts() {
    global $features_config;
    
    $scripts = [];
    
    // Command Palette
    if ($features_config['command_palette']) {
        $scripts[] = '<script src="' . getBaseUrl() . '/features/command_palette/palette.js"></script>';
    }
    
    // PWA
    if ($features_config['pwa']) {
        $scripts[] = '<script src="' . getBaseUrl() . '/features/pwa/pwa-install.js"></script>';
    }
    
    // AI Assistant
    if ($features_config['ai_assistant']) {
        $scripts[] = '<script src="' . getBaseUrl() . '/features/ai_assistant/widget.js"></script>';
    }
    
    // Onboarding
    if ($features_config['onboarding']) {
        $scripts[] = '<script src="' . getBaseUrl() . '/features/onboarding/tour.js"></script>';
    }
    
    return implode("\n", $scripts);
}

function loadFeatureStyles() {
    global $features_config;
    
    $styles = [];
    
    // Aquí se pueden agregar estilos específicos de features
    
    return implode("\n", $styles);
}

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    
    // Remover /features/ si existe en el path
    $path = str_replace('/features', '', $path);
    $path = str_replace('/api', '', $path);
    
    return $protocol . '://' . $host . rtrim($path, '/');
}

function renderFeatureScripts() {
    echo loadFeatureScripts();
}

function renderFeatureStyles() {
    echo loadFeatureStyles();
}

function isFeatureEnabled($feature) {
    global $features_config;
    return isset($features_config[$feature]) && $features_config[$feature] === true;
}
?>
