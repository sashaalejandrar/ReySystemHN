-- Tablas para el módulo de Bienestar Mental
-- Ejecutar este script en la base de datos tiendasrey

-- Tabla para registro de ánimo
CREATE TABLE IF NOT EXISTS bienestar_animo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(600) NOT NULL,
    nivel INT NOT NULL CHECK (nivel BETWEEN 1 AND 5),
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_fecha (usuario, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para entradas de gratitud
CREATE TABLE IF NOT EXISTS bienestar_gratitud (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(600) NOT NULL,
    item1 TEXT,
    item2 TEXT,
    item3 TEXT,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_fecha (usuario, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para pequeños logros
CREATE TABLE IF NOT EXISTS bienestar_logros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(600) NOT NULL,
    texto TEXT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_fecha (usuario, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para rutinas (Asperger)
CREATE TABLE IF NOT EXISTS bienestar_rutinas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(600) NOT NULL,
    texto TEXT NOT NULL,
    completada BOOLEAN DEFAULT FALSE,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_completada DATETIME NULL,
    INDEX idx_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para tareas rápidas (TDAH)
CREATE TABLE IF NOT EXISTS bienestar_tareas_rapidas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(600) NOT NULL,
    texto TEXT NOT NULL,
    completada BOOLEAN DEFAULT FALSE,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_completada DATETIME NULL,
    INDEX idx_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para diario de preocupaciones (Ansiedad)
CREATE TABLE IF NOT EXISTS bienestar_preocupaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(600) NOT NULL,
    texto TEXT NOT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_fecha (usuario, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para registro de actividades (log general)
CREATE TABLE IF NOT EXISTS bienestar_actividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(600) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    detalles TEXT,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario_fecha (usuario, fecha),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para logros desbloqueados
CREATE TABLE IF NOT EXISTS bienestar_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(600) NOT NULL,
    achievement_id VARCHAR(50) NOT NULL,
    fecha_desbloqueo DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_achievement (usuario, achievement_id),
    INDEX idx_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para contadores de Pomodoro por día
CREATE TABLE IF NOT EXISTS bienestar_pomodoro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(600) NOT NULL,
    fecha DATE NOT NULL,
    cantidad INT DEFAULT 1,
    UNIQUE KEY unique_user_date (usuario, fecha),
    INDEX idx_usuario_fecha (usuario, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para contadores generales
CREATE TABLE IF NOT EXISTS bienestar_contadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(600) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    valor INT DEFAULT 0,
    UNIQUE KEY unique_user_type (usuario, tipo),
    INDEX idx_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
