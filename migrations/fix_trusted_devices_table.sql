-- Migración para agregar la columna device_token a la tabla trusted_devices
-- Fecha: 2025-12-01
-- Propósito: Arreglar el login con dispositivo de confianza

-- Paso 1: Agregar columna device_token como NULLABLE primero
ALTER TABLE `trusted_devices` 
ADD COLUMN IF NOT EXISTS `device_token` VARCHAR(255) NULL AFTER `user_id`;

-- Paso 2: Generar tokens únicos para las filas existentes que no tienen token
UPDATE `trusted_devices` 
SET `device_token` = CONCAT('legacy_', MD5(CONCAT(id, user_id, device_fingerprint, UNIX_TIMESTAMP(created_at))))
WHERE `device_token` IS NULL OR `device_token` = '';

-- Paso 3: Ahora hacer la columna NOT NULL y agregar constraint UNIQUE
ALTER TABLE `trusted_devices` 
MODIFY COLUMN `device_token` VARCHAR(255) NOT NULL;

-- Paso 4: Agregar índice único si no existe
-- Primero verificar si existe el índice, si no, agregarlo
SET @exist := (SELECT COUNT(*) FROM information_schema.statistics 
               WHERE table_schema = 'tiendasrey' 
               AND table_name = 'trusted_devices' 
               AND index_name = 'device_token');
SET @sqlstmt := IF(@exist > 0, 'SELECT ''Index already exists''', 
                   'ALTER TABLE `trusted_devices` ADD UNIQUE KEY `device_token` (`device_token`)');
PREPARE stmt FROM @sqlstmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Paso 5: Modificar el tipo de user_id para que sea consistente con el código
ALTER TABLE `trusted_devices` 
MODIFY COLUMN `user_id` INT(11) NOT NULL;
