-- Tabla de notificaciones para la red social
CREATE TABLE IF NOT EXISTS notificaciones_red (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL COMMENT 'Usuario que recibe la notificación',
    tipo ENUM('like', 'comment', 'reaction', 'mention', 'share', 'follow', 'story_view') NOT NULL COMMENT 'Tipo de notificación',
    emisor_id INT NOT NULL COMMENT 'Usuario que generó la notificación',
    publicacion_id INT DEFAULT NULL COMMENT 'ID de la publicación relacionada',
    story_id INT DEFAULT NULL COMMENT 'ID de la story relacionada',
    comentario_id INT DEFAULT NULL COMMENT 'ID del comentario relacionado',
    mensaje TEXT COMMENT 'Mensaje de la notificación',
    leida TINYINT(1) DEFAULT 0 COMMENT '0 = no leída, 1 = leída',
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura DATETIME DEFAULT NULL,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(Id) ON DELETE CASCADE,
    FOREIGN KEY (emisor_id) REFERENCES usuarios(Id) ON DELETE CASCADE,
    FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (story_id) REFERENCES stories(id) ON DELETE CASCADE,
    
    INDEX idx_usuario_leida (usuario_id, leida),
    INDEX idx_fecha (fecha_creacion DESC),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Notificaciones de la red social (likes, comentarios, menciones, etc.)';
