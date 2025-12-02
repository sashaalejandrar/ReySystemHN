<?php
/**
 * Configuración de Features Modernas
 * El usuario puede activar/desactivar cada funcionalidad
 */

return [
    // FASE 1 - Fundación
    'dashboard_analytics' => true,      // Dashboard con gráficos
    'command_palette' => true,          // Ctrl+K para acciones rápidas
    'pwa' => true,                      // Progressive Web App
    'qr_system' => true,                // Sistema de QR avanzado
    
    // FASE 2 - Engagement
    'gamification' => false,            // Logros y recompensas
    'ai_assistant' => false,            // Asistente virtual con IA
    'onboarding' => true,               // Tour interactivo
    
    // FASE 3 - Integraciones
    'whatsapp' => false,                // WhatsApp Business
    'payments' => false,                // Pasarelas de pago
    'theme_editor' => false,            // Editor visual de temas
    
    // FASE 4 - Análisis
    'ml_predictions' => false,          // Predicciones con ML
    'pdf_reports' => true,              // Reportes PDF avanzados
    'audit_system' => true,             // Sistema de auditoría
    
    // FASE 5 - Colaboración
    'realtime_collab' => false,         // Colaboración en tiempo real
    'task_manager' => true,             // Tareas y recordatorios
    'smart_notifications' => true,      // Notificaciones inteligentes
    
    // FASE 6 - UX y Extras
    'image_recognition' => false,       // Reconocimiento de productos por IA
    'video_calls' => false,             // Videollamadas integradas
    'mobile_app' => false,              // App móvil nativa
    'kiosk_mode' => false,              // Modo presentación/kiosko
    'accessibility' => true,            // Modo de accesibilidad
    'workflows' => false,               // Workflows automatizados
    'etl_pipelines' => false,           // ETL y Data Pipelines
];
