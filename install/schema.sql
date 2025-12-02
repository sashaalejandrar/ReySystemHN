-- =====================================================
-- ReySystem Multi-Business Multi-Branch Schema
-- Complete database schema for multi-tenancy support
-- =====================================================

-- =====================================================
-- CORE MULTI-TENANCY TABLES
-- =====================================================

-- Table: negocios (businesses)
CREATE TABLE IF NOT EXISTS `negocios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(255) NOT NULL,
  `tipo_negocio` ENUM('abarrotes', 'ropa', 'ferreteria', 'farmacia', 'restaurante', 'otro') NOT NULL DEFAULT 'otro',
  `descripcion` TEXT,
  `direccion` TEXT,
  `telefono` VARCHAR(50),
  `email` VARCHAR(255),
  `rtn` VARCHAR(50),
  `logo` VARCHAR(255),
  `moneda` VARCHAR(10) DEFAULT 'L.',
  `impuesto_default` DECIMAL(5,2) DEFAULT 15.00,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_activo` (`activo`),
  INDEX `idx_tipo` (`tipo_negocio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: sucursales (branches)
CREATE TABLE IF NOT EXISTS `sucursales` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_negocio` INT NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `codigo` VARCHAR(50) UNIQUE,
  `direccion` TEXT,
  `telefono` VARCHAR(50),
  `email` VARCHAR(255),
  `responsable` VARCHAR(255),
  `horario` TEXT,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE CASCADE,
  INDEX `idx_negocio` (`id_negocio`),
  INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- USERS AND PERMISSIONS
-- =====================================================

-- Table: usuarios (users) - Modified for multi-tenancy
CREATE TABLE IF NOT EXISTS `usuarios` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `Usuario` VARCHAR(50) NOT NULL UNIQUE,
  `Password` VARCHAR(255) NOT NULL,
  `Nombre` VARCHAR(100) NOT NULL,
  `Apellido` VARCHAR(100),
  `Email` VARCHAR(255),
  `Celular` VARCHAR(50),
  `Rol` ENUM('Admin', 'Vendedor', 'Supervisor', 'Contador') NOT NULL DEFAULT 'Vendedor',
  `Perfil` VARCHAR(255) DEFAULT 'uploads/default-avatar.png',
  `id_negocio_default` INT,
  `id_sucursal_default` INT,
  `puede_cambiar_sucursal` TINYINT(1) DEFAULT 0,
  `super_admin` TINYINT(1) DEFAULT 0 COMMENT 'Can manage all businesses',
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_negocio_default`) REFERENCES `negocios`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`id_sucursal_default`) REFERENCES `sucursales`(`id`) ON DELETE SET NULL,
  INDEX `idx_usuario` (`Usuario`),
  INDEX `idx_negocio` (`id_negocio_default`),
  INDEX `idx_sucursal` (`id_sucursal_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: usuarios_negocios (user-business-branch assignments)
CREATE TABLE IF NOT EXISTS `usuarios_negocios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_usuario` INT NOT NULL,
  `id_negocio` INT NOT NULL,
  `id_sucursal` INT,
  `rol_en_negocio` VARCHAR(50),
  `permisos` TEXT COMMENT 'JSON array of permissions',
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`Id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_user_business_branch` (`id_usuario`, `id_negocio`, `id_sucursal`),
  INDEX `idx_usuario` (`id_usuario`),
  INDEX `idx_negocio` (`id_negocio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CONFIGURATION TABLES
-- =====================================================

-- Table: configuracion_app (application configuration per business)
CREATE TABLE IF NOT EXISTS `configuracion_app` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_negocio` INT NOT NULL,
  `nombre_empresa` VARCHAR(255) NOT NULL DEFAULT 'Tiendas Rey',
  `direccion_empresa` TEXT,
  `telefono_empresa` VARCHAR(50),
  `email_empresa` VARCHAR(255),
  `impuesto` DECIMAL(5,2) NOT NULL DEFAULT 15.00,
  `moneda` VARCHAR(10) DEFAULT 'L.',
  `formato_ticket` ENUM('ticket', 'factura', 'ambos') DEFAULT 'ticket',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_negocio` (`id_negocio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: configuracion_notificaciones
CREATE TABLE IF NOT EXISTS `configuracion_notificaciones` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `idUsuario` VARCHAR(50) NOT NULL,
  `email_ventas` TINYINT(1) NOT NULL DEFAULT 1,
  `email_deudas` TINYINT(1) NOT NULL DEFAULT 1,
  `email_productos` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idUsuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: configuracion_tema
CREATE TABLE IF NOT EXISTS `configuracion_tema` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `idUsuario` VARCHAR(50) NOT NULL,
  `tema` ENUM('light','dark','auto') NOT NULL DEFAULT 'dark',
  `color_primario` VARCHAR(7) NOT NULL DEFAULT '#1152d4',
  `tamano_fuente` ENUM('small','medium','large') NOT NULL DEFAULT 'medium',
  `alto_contraste` TINYINT(1) NOT NULL DEFAULT 0,
  `densidad_interfaz` ENUM('compact','normal','spacious') NOT NULL DEFAULT 'normal',
  `idioma` VARCHAR(5) NOT NULL DEFAULT 'es',
  `formato_fecha` VARCHAR(20) NOT NULL DEFAULT 'd/m/Y',
  `formato_hora` VARCHAR(20) NOT NULL DEFAULT 'H:i',
  `formato_moneda` VARCHAR(10) NOT NULL DEFAULT 'L.',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idUsuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: configuracion_privacidad
CREATE TABLE IF NOT EXISTS `configuracion_privacidad` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `idUsuario` VARCHAR(50) NOT NULL,
  `compartir_datos_analitica` TINYINT(1) NOT NULL DEFAULT 1,
  `retencion_logs_dias` INT NOT NULL DEFAULT 90,
  `permitir_cookies_terceros` TINYINT(1) NOT NULL DEFAULT 0,
  `mostrar_en_directorio` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idUsuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PRODUCTS AND INVENTORY
-- =====================================================

-- Table: productos (products) - Modified for multi-tenancy
CREATE TABLE IF NOT EXISTS `productos` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_negocio` INT NOT NULL,
  `id_sucursal` INT NOT NULL,
  `Codigo` VARCHAR(100) NOT NULL,
  `Nombre` VARCHAR(255) NOT NULL,
  `Descripcion` TEXT,
  `Categoria` VARCHAR(100),
  `Precio_Compra` DECIMAL(10,2) NOT NULL,
  `Precio_Venta` DECIMAL(10,2) NOT NULL,
  `Stock` INT NOT NULL DEFAULT 0,
  `Stock_Minimo` INT DEFAULT 5,
  `Proveedor` VARCHAR(255),
  `Foto` VARCHAR(255),
  `Activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_codigo_sucursal` (`Codigo`, `id_sucursal`),
  INDEX `idx_negocio` (`id_negocio`),
  INDEX `idx_sucursal` (`id_sucursal`),
  INDEX `idx_codigo` (`Codigo`),
  INDEX `idx_nombre` (`Nombre`),
  INDEX `idx_categoria` (`Categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: proveedores (suppliers) - Modified for multi-tenancy
CREATE TABLE IF NOT EXISTS `proveedores` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_negocio` INT NOT NULL,
  `Nombre` VARCHAR(255) NOT NULL,
  `Contacto` VARCHAR(255),
  `Telefono` VARCHAR(50),
  `Email` VARCHAR(255),
  `Direccion` TEXT,
  `RTN` VARCHAR(50),
  `Notas` TEXT,
  `Activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE CASCADE,
  INDEX `idx_negocio` (`id_negocio`),
  INDEX `idx_nombre` (`Nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SALES AND TRANSACTIONS
-- =====================================================

-- Table: clientes (customers) - Modified for multi-tenancy
CREATE TABLE IF NOT EXISTS `clientes` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_negocio` INT NOT NULL,
  `Nombre` VARCHAR(255) NOT NULL,
  `Apellido` VARCHAR(255),
  `Telefono` VARCHAR(50),
  `Email` VARCHAR(255),
  `Direccion` TEXT,
  `RTN` VARCHAR(50),
  `Limite_Credito` DECIMAL(10,2) DEFAULT 0,
  `Activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE CASCADE,
  INDEX `idx_negocio` (`id_negocio`),
  INDEX `idx_nombre` (`Nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: ventas (sales) - Modified for multi-tenancy
CREATE TABLE IF NOT EXISTS `ventas` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_negocio` INT NOT NULL,
  `id_sucursal` INT NOT NULL,
  `Fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `Usuario` VARCHAR(50) NOT NULL,
  `Cliente` VARCHAR(255),
  `Total` DECIMAL(10,2) NOT NULL,
  `Metodo_Pago` ENUM('Efectivo', 'Tarjeta', 'Transferencia', 'Credito') DEFAULT 'Efectivo',
  `Estado` ENUM('Completada', 'Pendiente', 'Cancelada') DEFAULT 'Completada',
  `Notas` TEXT,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales`(`id`) ON DELETE CASCADE,
  INDEX `idx_negocio` (`id_negocio`),
  INDEX `idx_sucursal` (`id_sucursal`),
  INDEX `idx_fecha` (`Fecha`),
  INDEX `idx_usuario` (`Usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: detalle_ventas (sale details)
CREATE TABLE IF NOT EXISTS `detalle_ventas` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_venta` INT NOT NULL,
  `id_producto` INT NOT NULL,
  `Cantidad` INT NOT NULL,
  `Precio_Unitario` DECIMAL(10,2) NOT NULL,
  `Subtotal` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`id_venta`) REFERENCES `ventas`(`Id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_producto`) REFERENCES `productos`(`Id`) ON DELETE CASCADE,
  INDEX `idx_venta` (`id_venta`),
  INDEX `idx_producto` (`id_producto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: deudas (debts) - Modified for multi-tenancy
CREATE TABLE IF NOT EXISTS `deudas` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_negocio` INT NOT NULL,
  `id_sucursal` INT NOT NULL,
  `id_venta` INT,
  `Cliente` VARCHAR(255) NOT NULL,
  `Monto_Total` DECIMAL(10,2) NOT NULL,
  `Monto_Pagado` DECIMAL(10,2) DEFAULT 0,
  `Saldo` DECIMAL(10,2) NOT NULL,
  `Fecha_Deuda` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `Fecha_Vencimiento` DATE,
  `Estado` ENUM('Pendiente', 'Pagada', 'Vencida') DEFAULT 'Pendiente',
  `Notas` TEXT,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_venta`) REFERENCES `ventas`(`Id`) ON DELETE SET NULL,
  INDEX `idx_negocio` (`id_negocio`),
  INDEX `idx_sucursal` (`id_sucursal`),
  INDEX `idx_cliente` (`Cliente`),
  INDEX `idx_estado` (`Estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: pagos_deudas (debt payments)
CREATE TABLE IF NOT EXISTS `pagos_deudas` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_deuda` INT NOT NULL,
  `Monto` DECIMAL(10,2) NOT NULL,
  `Fecha_Pago` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `Metodo_Pago` ENUM('Efectivo', 'Tarjeta', 'Transferencia') DEFAULT 'Efectivo',
  `Usuario` VARCHAR(50),
  `Notas` TEXT,
  FOREIGN KEY (`id_deuda`) REFERENCES `deudas`(`Id`) ON DELETE CASCADE,
  INDEX `idx_deuda` (`id_deuda`),
  INDEX `idx_fecha` (`Fecha_Pago`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CASH REGISTER OPERATIONS
-- =====================================================

-- Table: caja_operaciones (cash register operations) - Modified for multi-tenancy
CREATE TABLE IF NOT EXISTS `caja_operaciones` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_negocio` INT NOT NULL,
  `id_sucursal` INT NOT NULL,
  `Tipo` ENUM('apertura', 'arqueo', 'cierre', 'egreso') NOT NULL,
  `Usuario` VARCHAR(50) NOT NULL,
  `Fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `Monto_Inicial` DECIMAL(10,2),
  `Monto_Final` DECIMAL(10,2),
  `Monto_Sistema` DECIMAL(10,2),
  `Diferencia` DECIMAL(10,2),
  `Notas` TEXT,
  `Estado` ENUM('abierta', 'cerrada', 'verificada') DEFAULT 'abierta',
  `codigo_otp` VARCHAR(10),
  `otp_verificado` TINYINT(1) DEFAULT 0,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales`(`id`) ON DELETE CASCADE,
  INDEX `idx_negocio` (`id_negocio`),
  INDEX `idx_sucursal` (`id_sucursal`),
  INDEX `idx_tipo` (`Tipo`),
  INDEX `idx_fecha` (`Fecha`),
  INDEX `idx_usuario` (`Usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: egresos_caja (cash register expenses)
CREATE TABLE IF NOT EXISTS `egresos_caja` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_operacion` INT NOT NULL,
  `Concepto` VARCHAR(255) NOT NULL,
  `Monto` DECIMAL(10,2) NOT NULL,
  `Categoria` VARCHAR(100),
  `Fecha` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `Usuario` VARCHAR(50),
  `Notas` TEXT,
  FOREIGN KEY (`id_operacion`) REFERENCES `caja_operaciones`(`Id`) ON DELETE CASCADE,
  INDEX `idx_operacion` (`id_operacion`),
  INDEX `idx_fecha` (`Fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SECURITY AND AUDIT
-- =====================================================

-- Table: sesiones_activas (active sessions)
CREATE TABLE IF NOT EXISTS `sesiones_activas` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `idUsuario` VARCHAR(50) NOT NULL,
  `session_id` VARCHAR(255) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NOT NULL,
  `id_negocio` INT,
  `id_sucursal` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_current` TINYINT(1) NOT NULL DEFAULT 0,
  INDEX `idx_usuario` (`idUsuario`),
  INDEX `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: autenticacion_2fa (two-factor authentication)
CREATE TABLE IF NOT EXISTS `autenticacion_2fa` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `idUsuario` VARCHAR(50) NOT NULL,
  `secret` VARCHAR(255) NOT NULL,
  `backup_codes` TEXT,
  `enabled` TINYINT(1) NOT NULL DEFAULT 0,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idUsuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: api_keys
CREATE TABLE IF NOT EXISTS `api_keys` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `idUsuario` VARCHAR(50) NOT NULL,
  `api_key` VARCHAR(64) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `permisos` TEXT NOT NULL,
  `last_used` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `api_key` (`api_key`),
  INDEX `idx_usuario` (`idUsuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: audit_log (audit trail)
CREATE TABLE IF NOT EXISTS `audit_log` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `idUsuario` VARCHAR(50) NOT NULL,
  `id_negocio` INT,
  `id_sucursal` INT,
  `accion` VARCHAR(100) NOT NULL,
  `modulo` VARCHAR(50) NOT NULL,
  `detalles` TEXT,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_usuario` (`idUsuario`),
  INDEX `idx_negocio` (`id_negocio`),
  INDEX `idx_accion` (`accion`),
  INDEX `idx_fecha` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: backups
CREATE TABLE IF NOT EXISTS `backups` (
  `Id` INT AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255) NOT NULL,
  `filepath` VARCHAR(255) NOT NULL,
  `size` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `created_by` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ADDITIONAL FEATURES
-- =====================================================

-- Table: agenda (appointments/calendar)
CREATE TABLE IF NOT EXISTS `agenda` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_negocio` INT NOT NULL,
  `id_sucursal` INT,
  `titulo` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `fecha_inicio` DATETIME NOT NULL,
  `fecha_fin` DATETIME NOT NULL,
  `usuario` VARCHAR(50) NOT NULL,
  `cliente` VARCHAR(255),
  `tipo` ENUM('cita', 'evento', 'recordatorio', 'tarea') DEFAULT 'cita',
  `estado` ENUM('pendiente', 'confirmada', 'completada', 'cancelada') DEFAULT 'pendiente',
  `color` VARCHAR(7) DEFAULT '#1152d4',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_sucursal`) REFERENCES `sucursales`(`id`) ON DELETE SET NULL,
  INDEX `idx_negocio` (`id_negocio`),
  INDEX `idx_fecha` (`fecha_inicio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: notificaciones (notifications)
CREATE TABLE IF NOT EXISTS `notificaciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_usuario` INT NOT NULL,
  `id_negocio` INT,
  `tipo` VARCHAR(50) NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `leida` TINYINT(1) DEFAULT 0,
  `url` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios`(`Id`) ON DELETE CASCADE,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE CASCADE,
  INDEX `idx_usuario` (`id_usuario`),
  INDEX `idx_leida` (`leida`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: codigos_otp (one-time passwords)
CREATE TABLE IF NOT EXISTS `codigos_otp` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(10) NOT NULL,
  `tipo` ENUM('apertura', 'cierre', 'arqueo') NOT NULL,
  `usuario` VARCHAR(50) NOT NULL,
  `id_operacion` INT,
  `usado` TINYINT(1) DEFAULT 0,
  `fecha_creacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` TIMESTAMP NOT NULL,
  `fecha_uso` TIMESTAMP NULL,
  INDEX `idx_codigo` (`codigo`),
  INDEX `idx_operacion` (`id_operacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SOCIAL NETWORK FEATURES (Optional)
-- =====================================================

-- Table: publicaciones (social posts)
CREATE TABLE IF NOT EXISTS `publicaciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_negocio` INT,
  `usuario` VARCHAR(50) NOT NULL,
  `contenido` TEXT NOT NULL,
  `imagen` VARCHAR(255),
  `tipo` ENUM('texto', 'imagen', 'video', 'enlace') DEFAULT 'texto',
  `visibilidad` ENUM('publica', 'privada', 'negocio') DEFAULT 'negocio',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_negocio`) REFERENCES `negocios`(`id`) ON DELETE SET NULL,
  INDEX `idx_negocio` (`id_negocio`),
  INDEX `idx_usuario` (`usuario`),
  INDEX `idx_fecha` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: reacciones (reactions to posts)
CREATE TABLE IF NOT EXISTS `reacciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_publicacion` INT NOT NULL,
  `usuario` VARCHAR(50) NOT NULL,
  `tipo` ENUM('like', 'love', 'haha', 'wow', 'sad', 'angry') DEFAULT 'like',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_publicacion`) REFERENCES `publicaciones`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_user_post` (`id_publicacion`, `usuario`),
  INDEX `idx_publicacion` (`id_publicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: comentarios (comments on posts)
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_publicacion` INT NOT NULL,
  `usuario` VARCHAR(50) NOT NULL,
  `contenido` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`id_publicacion`) REFERENCES `publicaciones`(`id`) ON DELETE CASCADE,
  INDEX `idx_publicacion` (`id_publicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSTALLATION TRACKING
-- =====================================================

-- Table: instalacion (installation metadata)
CREATE TABLE IF NOT EXISTS `instalacion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `version` VARCHAR(20) NOT NULL,
  `fecha_instalacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `instalado_por` VARCHAR(50),
  `database_version` VARCHAR(20),
  `ultima_actualizacion` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
