-- Tabla para tareas y notas
CREATE TABLE IF NOT EXISTS `agenda_tareas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario` VARCHAR(50) NOT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `descripcion` TEXT,
  `tipo` ENUM('tarea', 'nota', 'recordatorio') DEFAULT 'tarea',
  `prioridad` ENUM('baja', 'media', 'alta', 'urgente') DEFAULT 'media',
  `estado` ENUM('pendiente', 'en_progreso', 'completada') DEFAULT 'pendiente',
  `fecha_creacion` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `fecha_vencimiento` DATE NULL,
  `etiquetas` VARCHAR(255),
  INDEX `idx_usuario` (`usuario`),
  INDEX `idx_estado` (`estado`),
  INDEX `idx_tipo` (`tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para historial de correos
CREATE TABLE IF NOT EXISTS `agenda_correos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario` VARCHAR(50) NOT NULL,
  `destinatario` VARCHAR(255) NOT NULL,
  `asunto` VARCHAR(255) NOT NULL,
  `mensaje` TEXT NOT NULL,
  `tipo` ENUM('pedido', 'nota', 'recordatorio', 'otro') DEFAULT 'otro',
  `fecha_envio` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `estado` ENUM('enviado', 'fallido') DEFAULT 'enviado',
  INDEX `idx_usuario` (`usuario`),
  INDEX `idx_fecha` (`fecha_envio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
