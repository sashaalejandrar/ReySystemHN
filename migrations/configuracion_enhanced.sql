-- Script de migración para nuevas funcionalidades de configuracion.php
-- ReySystem - Enhanced Configuration System

-- 1. Tabla de configuración de tema
CREATE TABLE IF NOT EXISTS `configuracion_tema` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` varchar(50) NOT NULL,
  `tema` enum('light','dark','auto') NOT NULL DEFAULT 'dark',
  `color_primario` varchar(7) NOT NULL DEFAULT '#1152d4',
  `tamano_fuente` enum('small','medium','large') NOT NULL DEFAULT 'medium',
  `alto_contraste` tinyint(1) NOT NULL DEFAULT 0,
  `densidad_interfaz` enum('compact','normal','spacious') NOT NULL DEFAULT 'normal',
  `idioma` varchar(5) NOT NULL DEFAULT 'es',
  `formato_fecha` varchar(20) NOT NULL DEFAULT 'd/m/Y',
  `formato_hora` varchar(20) NOT NULL DEFAULT 'H:i',
  `formato_moneda` varchar(10) NOT NULL DEFAULT 'L.',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `idUsuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabla de autenticación 2FA
CREATE TABLE IF NOT EXISTS `autenticacion_2fa` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` varchar(50) NOT NULL,
  `secret` varchar(255) NOT NULL,
  `backup_codes` text DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `idUsuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabla de API Keys
CREATE TABLE IF NOT EXISTS `api_keys` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` varchar(50) NOT NULL,
  `api_key` varchar(64) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `permisos` text NOT NULL,
  `last_used` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `api_key` (`api_key`),
  KEY `idUsuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tabla de registro de auditoría
CREATE TABLE IF NOT EXISTS `audit_log` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` varchar(50) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `detalles` text DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `idUsuario` (`idUsuario`),
  KEY `accion` (`accion`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Tabla de configuración de notificaciones avanzada
CREATE TABLE IF NOT EXISTS `configuracion_notificaciones_avanzada` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` varchar(50) NOT NULL,
  `canal_email` tinyint(1) NOT NULL DEFAULT 1,
  `canal_sms` tinyint(1) NOT NULL DEFAULT 0,
  `canal_push` tinyint(1) NOT NULL DEFAULT 0,
  `canal_webhook` tinyint(1) NOT NULL DEFAULT 0,
  `webhook_url` varchar(255) DEFAULT NULL,
  `telefono_sms` varchar(20) DEFAULT NULL,
  `horario_inicio` time DEFAULT '08:00:00',
  `horario_fin` time DEFAULT '20:00:00',
  `dias_activos` varchar(50) DEFAULT 'L,M,X,J,V,S,D',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `idUsuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Tabla de reglas de notificación personalizadas
CREATE TABLE IF NOT EXISTS `reglas_notificacion` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo_evento` varchar(50) NOT NULL,
  `condicion` text NOT NULL,
  `umbral` decimal(10,2) DEFAULT NULL,
  `canales` varchar(50) NOT NULL,
  `mensaje_personalizado` text DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `idUsuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Tabla de integraciones
CREATE TABLE IF NOT EXISTS `integraciones` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `configuracion` text NOT NULL,
  `credenciales` text DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 0,
  `last_sync` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Tabla de tareas programadas
CREATE TABLE IF NOT EXISTS `tareas_programadas` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `frecuencia` enum('hourly','daily','weekly','monthly') NOT NULL,
  `dia_semana` tinyint(1) DEFAULT NULL,
  `dia_mes` tinyint(2) DEFAULT NULL,
  `hora` time NOT NULL,
  `parametros` text DEFAULT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `last_run` timestamp NULL DEFAULT NULL,
  `next_run` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `tipo` (`tipo`),
  KEY `next_run` (`next_run`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Tabla de configuración de privacidad
CREATE TABLE IF NOT EXISTS `configuracion_privacidad` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` varchar(50) NOT NULL,
  `compartir_datos_analitica` tinyint(1) NOT NULL DEFAULT 1,
  `retencion_logs_dias` int(11) NOT NULL DEFAULT 90,
  `permitir_cookies_terceros` tinyint(1) NOT NULL DEFAULT 0,
  `mostrar_en_directorio` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  UNIQUE KEY `idUsuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Tabla de métricas del sistema
CREATE TABLE IF NOT EXISTS `metricas_sistema` (
  `Id` int(11) NOT NULL AUTO_INCREMENT,
  `cpu_usage` decimal(5,2) DEFAULT NULL,
  `memory_usage` decimal(5,2) DEFAULT NULL,
  `disk_usage` decimal(5,2) DEFAULT NULL,
  `active_connections` int(11) DEFAULT NULL,
  `queries_per_second` decimal(10,2) DEFAULT NULL,
  `cache_size_mb` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`Id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar integraciones predefinidas
INSERT IGNORE INTO `integraciones` (`tipo`, `nombre`, `configuracion`, `enabled`) VALUES
('whatsapp', 'WhatsApp Business API', '{"api_url":"","token":"","phone_number":""}', 0),
('payment_stripe', 'Stripe Payments', '{"api_key":"","webhook_secret":""}', 0),
('payment_paypal', 'PayPal', '{"client_id":"","client_secret":"","mode":"sandbox"}', 0),
('shipping_fedex', 'FedEx Shipping', '{"account_number":"","meter_number":"","api_key":""}', 0),
('accounting', 'Sistema Contable', '{"api_url":"","api_key":""}', 0);

-- Insertar tarea programada de backup automático
INSERT IGNORE INTO `tareas_programadas` (`tipo`, `nombre`, `frecuencia`, `hora`, `enabled`) VALUES
('backup', 'Backup Automático Diario', 'daily', '02:00:00', 0),
('cleanup', 'Limpieza de Logs Antiguos', 'weekly', '03:00:00', 1),
('optimize', 'Optimización de Base de Datos', 'weekly', '04:00:00', 1);
