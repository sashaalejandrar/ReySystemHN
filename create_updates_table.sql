-- Tabla para gestionar actualizaciones del sistema
CREATE TABLE IF NOT EXISTS `updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL,
  `codename` varchar(100) DEFAULT NULL,
  `build` varchar(20) NOT NULL,
  `release_date` date NOT NULL,
  `release_type` enum('major','minor','patch') NOT NULL DEFAULT 'minor',
  `changelog` text NOT NULL,
  `changes_json` text NOT NULL COMMENT 'Array de cambios en JSON',
  `file_type` enum('zip','tar.gz','both') NOT NULL DEFAULT 'tar.gz',
  `file_path` varchar(255) DEFAULT NULL,
  `file_size` varchar(50) DEFAULT NULL,
  `github_tag` varchar(50) DEFAULT NULL,
  `github_release_url` varchar(255) DEFAULT NULL,
  `status` enum('draft','pending','published','failed') NOT NULL DEFAULT 'draft',
  `created_by` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `published_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `version` (`version`),
  KEY `status` (`status`),
  KEY `created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
