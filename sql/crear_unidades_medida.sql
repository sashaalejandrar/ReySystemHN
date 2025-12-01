-- =====================================================
-- SISTEMA DE UNIDADES DE MEDIDA Y PRESENTACIONES
-- =====================================================

-- 1. Crear tabla de unidades de medida
CREATE TABLE IF NOT EXISTS unidades_medida (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    abreviatura VARCHAR(10) NOT NULL,
    tipo ENUM('peso', 'volumen', 'cantidad', 'empaque') NOT NULL,
    es_default BOOLEAN DEFAULT FALSE,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insertar unidades predefinidas
INSERT INTO unidades_medida (nombre, abreviatura, tipo, es_default) VALUES
-- Cantidad (default)
('Unidad', 'Ud', 'cantidad', TRUE),
('Docena', 'Dz', 'cantidad', FALSE),
('Par', 'Par', 'cantidad', FALSE),

-- Peso
('Kilogramo', 'Kg', 'peso', FALSE),
('Gramo', 'g', 'peso', FALSE),
('Libra', 'Lb', 'peso', FALSE),
('Onza', 'Oz', 'peso', FALSE),
('Tonelada', 'Ton', 'peso', FALSE),

-- Volumen
('Litro', 'L', 'volumen', FALSE),
('Mililitro', 'mL', 'volumen', FALSE),
('Galón', 'Gal', 'volumen', FALSE),

-- Empaque
('Fardo', 'Fdo', 'empaque', FALSE),
('Caja', 'Cja', 'empaque', FALSE),
('Paquete', 'Pqt', 'empaque', FALSE),
('Bulto', 'Bto', 'empaque', FALSE),
('Saco', 'Sco', 'empaque', FALSE),
('Cartón', 'Ctn', 'empaque', FALSE)
ON DUPLICATE KEY UPDATE nombre=nombre;

-- 3. Modificar tabla tipos_precios para incluir unidad
ALTER TABLE tipos_precios 
ADD COLUMN IF NOT EXISTS unidad_medida_id INT NULL AFTER descripcion,
ADD CONSTRAINT fk_tipos_precios_unidad 
    FOREIGN KEY (unidad_medida_id) 
    REFERENCES unidades_medida(id) 
    ON DELETE SET NULL;

-- 4. Modificar tabla stock para incluir unidad
ALTER TABLE stock 
ADD COLUMN IF NOT EXISTS unidad_medida_id INT DEFAULT 1 AFTER Precio_Unitario,
ADD CONSTRAINT fk_stock_unidad 
    FOREIGN KEY (unidad_medida_id) 
    REFERENCES unidades_medida(id) 
    ON DELETE SET NULL;

-- 5. Modificar tabla creacion_de_productos para incluir unidad
ALTER TABLE creacion_de_productos 
ADD COLUMN IF NOT EXISTS unidad_medida_id INT DEFAULT 1 AFTER PrecioSugeridoUnidad,
ADD CONSTRAINT fk_creacion_productos_unidad 
    FOREIGN KEY (unidad_medida_id) 
    REFERENCES unidades_medida(id) 
    ON DELETE SET NULL;

-- 6. Crear tipos de precios automáticos para unidades comunes
INSERT INTO tipos_precios (nombre, descripcion, es_default, unidad_medida_id)
SELECT 
    CONCAT('Precio_', nombre),
    CONCAT('Precio por ', nombre),
    FALSE,
    id
FROM unidades_medida
WHERE nombre IN ('Kilogramo', 'Libra', 'Litro', 'Fardo', 'Caja', 'Paquete')
ON DUPLICATE KEY UPDATE nombre=nombre;

-- 7. Actualizar tipo de precio default para usar Unidad
UPDATE tipos_precios 
SET unidad_medida_id = (SELECT id FROM unidades_medida WHERE es_default = TRUE LIMIT 1)
WHERE es_default = TRUE AND unidad_medida_id IS NULL;
