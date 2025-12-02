-- Tabla para almacenar llaves de seguridad de los usuarios
CREATE TABLE IF NOT EXISTS `security_keys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` VARCHAR(50) NOT NULL,
  `key_type` ENUM('webauthn', 'pin', 'trusted_device', 'biometric') NOT NULL,
  `key_name` VARCHAR(100) NOT NULL,
  `key_data` TEXT NOT NULL,
  `enabled` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_used` TIMESTAMP NULL,
  `expires_at` TIMESTAMP NULL,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_key_type` (`key_type`),
  INDEX `idx_enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para dispositivos de confianza
CREATE TABLE IF NOT EXISTS `trusted_devices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT(11) NOT NULL,
  `device_token` VARCHAR(255) NOT NULL,
  `device_fingerprint` VARCHAR(255) NOT NULL,
  `device_name` VARCHAR(100) NOT NULL,
  `browser` VARCHAR(50),
  `os` VARCHAR(50),
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  `last_used` TIMESTAMP NULL,
  UNIQUE KEY `device_token` (`device_token`),
  INDEX `idx_user_device` (`user_id`, `device_fingerprint`),
  INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
