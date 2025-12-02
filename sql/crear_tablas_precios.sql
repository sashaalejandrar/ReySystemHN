-- ============================================
-- SISTEMA DE PRECIOS PERSONALIZADOS
-- ============================================
-- Autor: ReySystem
-- Fecha: 2025-11-26
-- Descripción: Sistema flexible para gestionar múltiples tipos de precios por producto
--              Cada producto puede tener varios precios (Unitario, Mayoreo, Distribuidor, etc.)
--              Por defecto se crean 2 tipos: Precio_Unitario y Precio_Mayoreo
-- ============================================

-- Tabla 1: Tipos de Precios
-- Almacena los diferentes tipos de precios que pueden existir en el sistema
-- El usuario puede crear tipos personalizados (ej: Precio_Distribuidor, Precio_VIP, etc.)
CREATE TABLE IF NOT EXISTS tipos_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nombre del tipo de precio (ej: Precio_Mayorista)',
    descripcion VARCHAR(255) COMMENT 'Descripción opcional del tipo de precio',
    es_default BOOLEAN DEFAULT FALSE COMMENT 'Indica si es un precio por defecto del sistema (no se puede eliminar)',
    activo BOOLEAN DEFAULT TRUE COMMENT 'Indica si el tipo de precio está activo',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_activo (activo),
    INDEX idx_default (es_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tipos de precios disponibles en el sistema';

-- Insertar SOLO los 2 tipos de precios por defecto
-- Precio_Unitario: Precio de venta por unidad individual
-- Precio_Mayoreo: Precio de venta al mayoreo (corresponde a PrecioMayoreo en tabla stock)
INSERT INTO tipos_precios (nombre, descripcion, es_default, activo) VALUES
('Precio_Unitario', 'Precio por unidad individual del producto', TRUE, TRUE),
('Precio_Mayoreo', 'Precio de venta al mayoreo', TRUE, TRUE)
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

-- Tabla 2: Precios por Producto
-- Almacena MÚLTIPLES precios para cada producto según el tipo
-- Un producto puede tener N precios (uno por cada tipo de precio activo)
CREATE TABLE IF NOT EXISTS producto_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto_id INT NOT NULL COMMENT 'ID del producto en la tabla stock',
    tipo_precio_id INT NOT NULL COMMENT 'ID del tipo de precio',
    precio DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor del precio',
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES stock(Id) ON DELETE CASCADE,
    FOREIGN KEY (tipo_precio_id) REFERENCES tipos_precios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_producto_tipo (producto_id, tipo_precio_id) COMMENT 'Un producto solo puede tener un precio por tipo',
    INDEX idx_producto (producto_id),
    INDEX idx_tipo_precio (tipo_precio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Múltiples precios por producto según tipo';

-- ============================================
-- MIGRACIÓN DE DATOS EXISTENTES
-- ============================================

-- PASO 1: Migrar Precio_Unitario desde stock.Precio_Unitario
INSERT INTO producto_precios (producto_id, tipo_precio_id, precio)
SELECT 
    s.Id,
    (SELECT id FROM tipos_precios WHERE nombre = 'Precio_Unitario' LIMIT 1),
    COALESCE(s.Precio_Unitario, 0.00)
FROM stock s
WHERE NOT EXISTS (
    SELECT 1 FROM producto_precios pp 
    WHERE pp.producto_id = s.Id 
    AND pp.tipo_precio_id = (SELECT id FROM tipos_precios WHERE nombre = 'Precio_Unitario' LIMIT 1)
);

-- PASO 2: Migrar Precio_Mayoreo desde stock.PrecioMayoreo (si existe la columna)
INSERT INTO producto_precios (producto_id, tipo_precio_id, precio)
SELECT 
    s.Id,
    (SELECT id FROM tipos_precios WHERE nombre = 'Precio_Mayoreo' LIMIT 1),
    COALESCE(s.PrecioMayoreo, 0.00)
FROM stock s
WHERE EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'tiendasrey' 
    AND TABLE_NAME = 'stock' 
    AND COLUMN_NAME = 'PrecioMayoreo'
)
AND NOT EXISTS (
    SELECT 1 FROM producto_precios pp 
    WHERE pp.producto_id = s.Id 
    AND pp.tipo_precio_id = (SELECT id FROM tipos_precios WHERE nombre = 'Precio_Mayoreo' LIMIT 1)
);

-- ============================================
-- VERIFICACIÓN
-- ============================================
-- Consulta para verificar la migración
SELECT 
    'Tipos de Precios Creados' as Verificacion,
    COUNT(*) as Total
FROM tipos_precios
UNION ALL
SELECT 
    'Precios Migrados' as Verificacion,
    COUNT(*) as Total
FROM producto_precios;

-- ============================================
-- NOTAS IMPORTANTES
-- ============================================
-- 1. Cada producto puede tener MÚLTIPLES precios (uno por cada tipo)
-- 2. Los tipos por defecto (Precio_Unitario y Precio_Mayoreo) NO se pueden eliminar
-- 3. El usuario puede crear tipos adicionales: Precio_Distribuidor, Precio_VIP, etc.
-- 4. Al eliminar un tipo de precio, se eliminan automáticamente todos los precios asociados (CASCADE)
-- 5. El campo stock.Precio_Unitario se mantiene por compatibilidad y se sincroniza automáticamente

-- ============================================
-- FIN DEL SCRIPT
-- ============================================
