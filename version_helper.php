<?php
/**
 * Helper para obtener la versión del sistema desde version.json
 */

function getSystemVersion() {
    $version_file = __DIR__ . '/version.json';
    
    if (file_exists($version_file)) {
        $version_data = json_decode(file_get_contents($version_file), true);
        if (isset($version_data['version'])) {
            return $version_data['version'];
        }
    }
    
    return '1.0.0'; // Fallback
}

function getSystemVersionFull() {
    $version_file = __DIR__ . '/version.json';
    
    if (file_exists($version_file)) {
        $version_data = json_decode(file_get_contents($version_file), true);
        return [
            'version' => $version_data['version'] ?? '1.0.0',
            'codename' => $version_data['codename'] ?? '',
            'build' => $version_data['build'] ?? '',
            'release_date' => $version_data['release_date'] ?? ''
        ];
    }
    
    return [
        'version' => '1.0.0',
        'codename' => '',
        'build' => '',
        'release_date' => ''
    ];
}
?>
