<?php
/**
 * Configuración del Sistema de Actualizaciones
 * 
 * INSTRUCCIONES:
 * 1. Cambia GITHUB_USER por tu usuario de GitHub
 * 2. Cambia GITHUB_REPO por el nombre de tu repositorio
 * 3. Asegúrate de crear releases en GitHub con tags de versión (ej: v2.5.0)
 */

return [
    // Configuración de GitHub
    'github' => [
        'user' => 'sashaalejandrar',        // Tu usuario de GitHub
        'repo' => 'ReySystemHN',         // Nombre de tu repositorio
        'branch' => 'main',            // Rama principal
    ],
    
    // Configuración de actualizaciones
    'updates' => [
        'auto_check' => false,         // Verificar automáticamente al iniciar sesión
        'check_interval' => 86400,     // Intervalo de verificación (24 horas)
        'require_backup' => true,      // Crear backup antes de actualizar
        'allow_beta' => false,         // Permitir versiones beta/pre-release
    ],
    
    // Directorios
    'paths' => [
        'temp' => __DIR__ . '/temp_updates',
        'backup' => __DIR__ . '/backups',
        'logs' => __DIR__ . '/logs',
    ],
    
    // Archivos a excluir del backup
    'exclude_from_backup' => [
        'temp_updates',
        'backups',
        'logs',
        'uploads',
        '.git',
        'node_modules',
        'vendor',
    ],
];
?>
