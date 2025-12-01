-- =====================================================
-- Migration: Add Multi-Business Multi-Branch Support
-- Adds necessary columns to existing tables
-- =====================================================

-- Ensure negocios table exists
CREATE TABLE IF NOT EXISTS `negocios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `tipo_negocio` ENUM('abarrotes', 'ropa', 'ferreteria', 'farmacia', 'restaurante', 'otro') NOT NULL DEFAULT 'abarrotes',
  `direccion` TEXT,
  `telefono` VARCHAR(50),
  `email` VARCHAR(255),
  `rtn` VARCHAR(50),
  `impuesto_default` DECIMAL(5,2) DEFAULT 15.00,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure sucursales table exists
CREATE TABLE IF NOT EXISTS `sucursales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_negocio` INT NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `codigo` VARCHAR(50) UNIQUE,
  `direccion` TEXT,
  `telefono` VARCHAR(50),
  `responsable` VARCHAR(255),
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add columns to usuarios table (if not exist)
SET @query1 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'id_negocio_default') = 0,
    'ALTER TABLE usuarios ADD COLUMN id_negocio_default INT DEFAULT 1',
    'SELECT "Column id_negocio_default already exists"'
);
PREPARE stmt1 FROM @query1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

SET @query2 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'id_sucursal_default') = 0,
    'ALTER TABLE usuarios ADD COLUMN id_sucursal_default INT DEFAULT 1',
    'SELECT "Column id_sucursal_default already exists"'
);
PREPARE stmt2 FROM @query2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @query3 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'puede_cambiar_sucursal') = 0,
    'ALTER TABLE usuarios ADD COLUMN puede_cambiar_sucursal TINYINT(1) DEFAULT 1',
    'SELECT "Column puede_cambiar_sucursal already exists"'
);
PREPARE stmt3 FROM @query3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

SET @query4 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'usuarios' AND column_name = 'super_admin') = 0,
    'ALTER TABLE usuarios ADD COLUMN super_admin TINYINT(1) DEFAULT 0',
    'SELECT "Column super_admin already exists"'
);
PREPARE stmt4 FROM @query4;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

-- Add columns to creacion_de_productos table
SET @query5 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'creacion_de_productos' AND column_name = 'id_negocio') = 0,
    'ALTER TABLE creacion_de_productos ADD COLUMN id_negocio INT DEFAULT 1',
    'SELECT "Column id_negocio already exists in creacion_de_productos"'
);
PREPARE stmt5 FROM @query5;
EXECUTE stmt5;
DEALLOCATE PREPARE stmt5;

SET @query6 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'creacion_de_productos' AND column_name = 'id_sucursal') = 0,
    'ALTER TABLE creacion_de_productos ADD COLUMN id_sucursal INT DEFAULT 1',
    'SELECT "Column id_sucursal already exists in creacion_de_productos"'
);
PREPARE stmt6 FROM @query6;
EXECUTE stmt6;
DEALLOCATE PREPARE stmt6;

-- Add columns to ventas table
SET @query7 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'ventas' AND column_name = 'id_negocio') = 0,
    'ALTER TABLE ventas ADD COLUMN id_negocio INT DEFAULT 1',
    'SELECT "Column id_negocio already exists in ventas"'
);
PREPARE stmt7 FROM @query7;
EXECUTE stmt7;
DEALLOCATE PREPARE stmt7;

SET @query8 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'ventas' AND column_name = 'id_sucursal') = 0,
    'ALTER TABLE ventas ADD COLUMN id_sucursal INT DEFAULT 1',
    'SELECT "Column id_sucursal already exists in ventas"'
);
PREPARE stmt8 FROM @query8;
EXECUTE stmt8;
DEALLOCATE PREPARE stmt8;

-- Add columns to clientes table
SET @query9 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'clientes' AND column_name = 'id_negocio') = 0,
    'ALTER TABLE clientes ADD COLUMN id_negocio INT DEFAULT 1',
    'SELECT "Column id_negocio already exists in clientes"'
);
PREPARE stmt9 FROM @query9;
EXECUTE stmt9;
DEALLOCATE PREPARE stmt9;

-- Add columns to proveedores table
SET @query10 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'proveedores' AND column_name = 'id_negocio') = 0,
    'ALTER TABLE proveedores ADD COLUMN id_negocio INT DEFAULT 1',
    'SELECT "Column id_negocio already exists in proveedores"'
);
PREPARE stmt10 FROM @query10;
EXECUTE stmt10;
DEALLOCATE PREPARE stmt10;

-- Add columns to deudas table
SET @query11 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'deudas' AND column_name = 'id_negocio') = 0,
    'ALTER TABLE deudas ADD COLUMN id_negocio INT DEFAULT 1',
    'SELECT "Column id_negocio already exists in deudas"'
);
PREPARE stmt11 FROM @query11;
EXECUTE stmt11;
DEALLOCATE PREPARE stmt11;

SET @query12 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'deudas' AND column_name = 'id_sucursal') = 0,
    'ALTER TABLE deudas ADD COLUMN id_sucursal INT DEFAULT 1',
    'SELECT "Column id_sucursal already exists in deudas"'
);
PREPARE stmt12 FROM @query12;
EXECUTE stmt12;
DEALLOCATE PREPARE stmt12;

-- Add columns to stock table
SET @query13 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'stock' AND column_name = 'id_negocio') = 0,
    'ALTER TABLE stock ADD COLUMN id_negocio INT DEFAULT 1',
    'SELECT "Column id_negocio already exists in stock"'
);
PREPARE stmt13 FROM @query13;
EXECUTE stmt13;
DEALLOCATE PREPARE stmt13;

SET @query14 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'stock' AND column_name = 'id_sucursal') = 0,
    'ALTER TABLE stock ADD COLUMN id_sucursal INT DEFAULT 1',
    'SELECT "Column id_sucursal already exists in stock"'
);
PREPARE stmt14 FROM @query14;
EXECUTE stmt14;
DEALLOCATE PREPARE stmt14;

-- Add column to configuracion_app
SET @query15 = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_schema = DATABASE() AND table_name = 'configuracion_app' AND column_name = 'id_negocio') = 0,
    'ALTER TABLE configuracion_app ADD COLUMN id_negocio INT DEFAULT 1',
    'SELECT "Column id_negocio already exists in configuracion_app"'
);
PREPARE stmt15 FROM @query15;
EXECUTE stmt15;
DEALLOCATE PREPARE stmt15;

-- Insert default business if not exists
INSERT INTO `negocios` (`id`, `nombre`, `tipo_negocio`, `impuesto_default`, `activo`)
SELECT 1, 'Tienda Rey', 'abarrotes', 15.00, 1
WHERE NOT EXISTS (SELECT 1 FROM `negocios` WHERE `id` = 1);

-- Insert default branch if not exists
INSERT INTO `sucursales` (`id`, `id_negocio`, `nombre`, `codigo`, `activo`)
SELECT 1, 1, 'Sucursal Principal', 'SUC001', 1
WHERE NOT EXISTS (SELECT 1 FROM `sucursales` WHERE `id` = 1);

-- Update existing users to have default business/branch
UPDATE `usuarios` 
SET `id_negocio_default` = 1, `id_sucursal_default` = 1
WHERE `id_negocio_default` IS NULL OR `id_negocio_default` = 0;

-- Update admin users to be super_admin
UPDATE `usuarios` 
SET `super_admin` = 1
WHERE `Rol` = 'Admin';
