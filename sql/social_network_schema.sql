-- Script SQL para crear las tablas necesarias para la red social en tiempo real

-- Tabla de reacciones múltiples (like, love, wow, sad, angry)
CREATE TABLE IF NOT EXISTS reacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publicacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo_reaccion ENUM('like', 'love', 'wow', 'sad', 'angry') NOT NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (publicacion_id, usuario_id),
    FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(Id) ON DELETE CASCADE,
    INDEX idx_publicacion (publicacion_id),
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de stories (historias temporales de 24h)
CREATE TABLE IF NOT EXISTS stories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    tipo ENUM('imagen', 'video', 'texto') NOT NULL,
    contenido TEXT,
    archivo_url VARCHAR(500),
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    expira_en DATETIME NOT NULL,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(Id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_activo_expira (activo, expira_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de vistas de stories
CREATE TABLE IF NOT EXISTS story_vistas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    story_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha_vista DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vista (story_id, usuario_id),
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(Id) ON DELETE CASCADE,
    INDEX idx_story (story_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
