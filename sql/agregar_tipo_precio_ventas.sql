-- Agregar columna tipo_precio_nombre a ventas_detalle para almacenar el tipo de precio usado
ALTER TABLE ventas_detalle 
ADD COLUMN tipo_precio_nombre VARCHAR(100) DEFAULT 'Precio_Unitario' AFTER Precio;

-- Agregar columna tipo_precio_id para referencia
ALTER TABLE ventas_detalle 
ADD COLUMN tipo_precio_id INT DEFAULT 1 AFTER tipo_precio_nombre;
