CREATE TABLE IF NOT EXISTS `diagnostico_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text,
  `archivo` varchar(255) NOT NULL,
  `nivel` enum('critico','advertencia','info') NOT NULL,
  `tipo` enum('compatibilidad','seguridad','sintaxis','logica') NOT NULL,
  `solucion` text,
  `proveedor` varchar(50) DEFAULT 'IA',
  `usuario` varchar(100) NOT NULL,
  `fecha_correccion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `backup_archivo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fecha` (`fecha_correccion`),
  KEY `idx_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
