<?php
/**
 * Migration: Create Forum Tables
 * Creates tables for Twitter-style forum: posts, likes, comments
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Table: foro_posts
$sql_posts = "CREATE TABLE IF NOT EXISTS `foro_posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `usuario_id` INT NOT NULL,
    `usuario_nombre` VARCHAR(255) NOT NULL,
    `usuario_avatar` VARCHAR(255) DEFAULT NULL,
    `contenido` TEXT NOT NULL,
    `imagen` VARCHAR(255) DEFAULT NULL,
    `likes_count` INT DEFAULT 0,
    `comments_count` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_usuario (usuario_id),
    INDEX idx_created (created_at),
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Posts del foro estilo Twitter'";

if ($conexion->query($sql_posts)) {
    echo "✅ Tabla 'foro_posts' creada correctamente<br>";
} else {
    echo "❌ Error creando 'foro_posts': " . $conexion->error . "<br>";
}

// Table: foro_likes
$sql_likes = "CREATE TABLE IF NOT EXISTS `foro_likes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `usuario_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_like (post_id, usuario_id),
    INDEX idx_post (post_id),
    INDEX idx_usuario (usuario_id),
    
    FOREIGN KEY (post_id) REFERENCES foro_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Likes de posts del foro'";

if ($conexion->query($sql_likes)) {
    echo "✅ Tabla 'foro_likes' creada correctamente<br>";
} else {
    echo "❌ Error creando 'foro_likes': " . $conexion->error . "<br>";
}

// Table: foro_comments
$sql_comments = "CREATE TABLE IF NOT EXISTS `foro_comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `usuario_id` INT NOT NULL,
    `usuario_nombre` VARCHAR(255) NOT NULL,
    `usuario_avatar` VARCHAR(255) DEFAULT NULL,
    `contenido` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_post (post_id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_created (created_at),
    
    FOREIGN KEY (post_id) REFERENCES foro_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Comentarios de posts del foro'";

if ($conexion->query($sql_comments)) {
    echo "✅ Tabla 'foro_comments' creada correctamente<br>";
} else {
    echo "❌ Error creando 'foro_comments': " . $conexion->error . "<br>";
}

echo "<br><strong>✅ Migración de Foro completada!</strong><br>";
echo "<a href='../foro.php'>Ir al Foro</a>";

$conexion->close();
?>
