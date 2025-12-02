-- Agregar columna Fecha_Registro a la tabla clientes
ALTER TABLE clientes 
ADD COLUMN Fecha_Registro DATETIME DEFAULT CURRENT_TIMESTAMP;

-- Actualizar registros existentes con la fecha actual
UPDATE clientes 
SET Fecha_Registro = CURRENT_TIMESTAMP 
WHERE Fecha_Registro IS NULL;
