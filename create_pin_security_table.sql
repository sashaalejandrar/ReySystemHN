-- Tabla para almacenar PINs de seguridad
CREATE TABLE IF NOT EXISTS `pin_security` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idUsuario` varchar(50) NOT NULL,
  `pin_hash` varchar(255) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_used` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idUsuario` (`idUsuario`),
  KEY `idx_usuario_enabled` (`idUsuario`, `enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
