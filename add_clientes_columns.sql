-- Agregar columnas adicionales a la tabla clientes
ALTER TABLE clientes 
ADD COLUMN Email VARCHAR(255) NULL AFTER Direccion,
ADD COLUMN Identidad VARCHAR(50) NULL AFTER Email,
ADD COLUMN Notas TEXT NULL AFTER Identidad;
