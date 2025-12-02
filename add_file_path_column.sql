-- Script para agregar columna file_path a chat_messages
-- Ejecutar este script en phpMyAdmin o MySQL

USE tiendasrey;

-- Agregar columna file_path si no existe
ALTER TABLE chat_messages 
ADD COLUMN IF NOT EXISTS file_path VARCHAR(255) NULL AFTER mensaje;

-- Verificar que se agregó correctamente
DESCRIBE chat_messages;
