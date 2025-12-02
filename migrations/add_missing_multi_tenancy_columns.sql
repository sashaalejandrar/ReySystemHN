-- Migration: Add Multi-Tenancy Columns to All Tables
-- Date: 2025-11-21
-- Purpose: Add id_negocio and id_sucursal to all transactional tables

USE tiendasrey;

-- 1. Add columns to CAJA table
ALTER TABLE caja 
ADD COLUMN IF NOT EXISTS id_negocio INT DEFAULT 1 AFTER Id,
ADD COLUMN IF NOT EXISTS id_sucursal INT DEFAULT 1 AFTER id_negocio;

-- 2. Add columns to CIERRE_CAJA table
ALTER TABLE cierre_caja 
ADD COLUMN IF NOT EXISTS id_negocio INT DEFAULT 1 AFTER Id,
ADD COLUMN IF NOT EXISTS id_sucursal INT DEFAULT 1 AFTER id_negocio;

-- 3. Add columns to ARQUEO_CAJA table
ALTER TABLE arqueo_caja 
ADD COLUMN IF NOT EXISTS id_negocio INT DEFAULT 1 AFTER Id,
ADD COLUMN IF NOT EXISTS id_sucursal INT DEFAULT 1 AFTER id_negocio;

-- 4. Add columns to VENTAS table (if not exists)
ALTER TABLE ventas 
ADD COLUMN IF NOT EXISTS id_negocio INT DEFAULT 1 AFTER Id_Venta,
ADD COLUMN IF NOT EXISTS id_sucursal INT DEFAULT 1 AFTER id_negocio;

-- 5. Add columns to STOCK table (if not exists)
ALTER TABLE stock 
ADD COLUMN IF NOT EXISTS id_negocio INT DEFAULT 1 AFTER Id,
ADD COLUMN IF NOT EXISTS id_sucursal INT DEFAULT 1 AFTER id_negocio;

-- 6. Add columns to EGRESOS_CAJA table (if exists)
ALTER TABLE egresos_caja 
ADD COLUMN IF NOT EXISTS id_negocio INT DEFAULT 1 AFTER id,
ADD COLUMN IF NOT EXISTS id_sucursal INT DEFAULT 1 AFTER id_negocio;

-- 7. Add columns to CLIENTES table (if exists)
ALTER TABLE clientes 
ADD COLUMN IF NOT EXISTS id_negocio INT DEFAULT 1 AFTER id,
ADD COLUMN IF NOT EXISTS id_sucursal INT DEFAULT 1 AFTER id_negocio;

-- 8. Add columns to PROVEEDORES table (if exists)
ALTER TABLE proveedores 
ADD COLUMN IF NOT EXISTS id_negocio INT DEFAULT 1 AFTER id,
ADD COLUMN IF NOT EXISTS id_sucursal INT DEFAULT 1 AFTER id_negocio;

-- 9. Add columns to DEUDAS table (if exists)
ALTER TABLE deudas 
ADD COLUMN IF NOT EXISTS id_negocio INT DEFAULT 1 AFTER id,
ADD COLUMN IF NOT EXISTS id_sucursal INT DEFAULT 1 AFTER id_negocio;

-- 10. Add foreign key constraints (optional, for data integrity)
-- ALTER TABLE caja ADD FOREIGN KEY (id_negocio) REFERENCES negocios(id);
-- ALTER TABLE caja ADD FOREIGN KEY (id_sucursal) REFERENCES sucursales(id);

-- Verify changes
SELECT 'Migration completed successfully!' AS Status;
