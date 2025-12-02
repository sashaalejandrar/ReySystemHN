-- Script SQL para crear tablas del sistema de comparación de precios

-- Tabla para almacenar precios de la competencia
CREATE TABLE IF NOT EXISTS precios_competencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_producto VARCHAR(50),
    nombre_producto VARCHAR(255),
    precio_competencia DECIMAL(10,2),
    fuente VARCHAR(100),
    url_producto TEXT,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    disponible BOOLEAN DEFAULT 1,
    INDEX idx_codigo (codigo_producto),
    INDEX idx_fuente (fuente),
    INDEX idx_fecha (fecha_actualizacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para registrar fuentes de competencia
CREATE TABLE IF NOT EXISTS fuentes_competencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE,
    url_base VARCHAR(255),
    activo BOOLEAN DEFAULT 1,
    ultima_actualizacion DATETIME,
    total_productos INT DEFAULT 0,
    estado VARCHAR(50) DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para historial de precios (opcional, para análisis de tendencias)
CREATE TABLE IF NOT EXISTS historial_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo_producto VARCHAR(50),
    precio_propio DECIMAL(10,2),
    precio_promedio_competencia DECIMAL(10,2),
    fecha_registro DATE,
    INDEX idx_codigo_fecha (codigo_producto, fecha_registro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertar fuentes iniciales
INSERT INTO fuentes_competencia (nombre, url_base, activo) VALUES
('La Colonia', 'https://www.lacolonia.hn', 1),
('Walmart Honduras', 'https://www.walmart.com.hn', 1),
('La Antorcha', 'https://www.laantorcha.hn', 0),
('Paiz', 'https://www.paiz.com.hn', 0),
('Despensa Familiar', 'https://www.despensafamiliar.com', 0)
ON DUPLICATE KEY UPDATE url_base = VALUES(url_base);
