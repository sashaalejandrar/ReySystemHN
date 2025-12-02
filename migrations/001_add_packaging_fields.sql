-- =====================================================
-- Migración: Sistema de Contenido/SubContenido
-- Fecha: 2025-11-30
-- Descripción: Agrega campos para manejar presentaciones
--              de productos con múltiples niveles
-- =====================================================

-- Casos de uso soportados:
-- 1. Simple: 1x24 (24 unidades)
-- 2. Doble nivel: 1x12x12 (144 unidades)
-- 3. Doble nivel: 1x6x24 (144 unidades)
-- 4. Triple nivel: 1x4x6x12 (288 unidades)
-- 5. Paquetes individuales: 1x1 (1 unidad)
-- 6. Cajas grandes: 1x100 (100 unidades)
-- 7. Display: 1x20x6 (120 unidades)
-- 8. Pallet: 1x48x12 (576 unidades)

USE tiendasrey;

-- Agregar columnas para el sistema de packaging
ALTER TABLE creacion_de_productos
ADD COLUMN IF NOT EXISTS TieneSubContenido TINYINT(1) DEFAULT 0 COMMENT 'Indica si usa sistema de subcontenido (0=No, 1=Sí)',
ADD COLUMN IF NOT EXISTS Contenido INT DEFAULT NULL COMMENT 'Cantidad de paquetes/cajas intermedias (ej: 12 en 1x12x12)',
ADD COLUMN IF NOT EXISTS SubContenido INT DEFAULT NULL COMMENT 'Unidades por paquete intermedio (ej: 12 en 1x12x12)',
ADD COLUMN IF NOT EXISTS UnidadesTotales INT DEFAULT NULL COMMENT 'Total de unidades calculadas automáticamente',
ADD COLUMN IF NOT EXISTS FormatoPresentacion VARCHAR(50) DEFAULT NULL COMMENT 'Formato visual (ej: 1x12x12, 1x24)';

-- Crear índice para búsquedas por tipo de presentación
CREATE INDEX IF NOT EXISTS idx_tiene_subcontenido ON creacion_de_productos(TieneSubContenido);

-- Actualizar productos existentes con valores por defecto
UPDATE creacion_de_productos 
SET 
    TieneSubContenido = 0,
    UnidadesTotales = UnidadesPorEmpaque,
    FormatoPresentacion = CONCAT('1x', COALESCE(UnidadesPorEmpaque, 1))
WHERE TieneSubContenido IS NULL;

-- Verificar cambios
SELECT 
    'Migración completada exitosamente' AS Status,
    COUNT(*) AS TotalProductos,
    SUM(CASE WHEN TieneSubContenido = 1 THEN 1 ELSE 0 END) AS ConSubContenido,
    SUM(CASE WHEN TieneSubContenido = 0 THEN 1 ELSE 0 END) AS SinSubContenido
FROM creacion_de_productos;
