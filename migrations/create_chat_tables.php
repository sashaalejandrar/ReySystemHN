<?php
/**
 * Migration: Create Chat Tables
 * Creates tables for real-time internal chat
 */

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Table: chat_messages
$sql_messages = "CREATE TABLE IF NOT EXISTS `chat_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `from_user_id` INT NOT NULL,
    `to_user_id` INT NOT NULL,
    `mensaje` TEXT NOT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_from_to (from_user_id, to_user_id, created_at),
    INDEX idx_to_unread (to_user_id, is_read),
    INDEX idx_created (created_at),
    
    FOREIGN KEY (from_user_id) REFERENCES usuarios(Id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id) REFERENCES usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Mensajes del chat interno'";

if ($conexion->query($sql_messages)) {
    echo "✅ Tabla 'chat_messages' creada correctamente<br>";
} else {
    echo "❌ Error creando 'chat_messages': " . $conexion->error . "<br>";
}

// Table: chat_typing (for typing indicator)
$sql_typing = "CREATE TABLE IF NOT EXISTS `chat_typing` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `typing_to` INT NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_typing (user_id, typing_to),
    INDEX idx_typing_to (typing_to),
    
    FOREIGN KEY (user_id) REFERENCES usuarios(Id) ON DELETE CASCADE,
    FOREIGN KEY (typing_to) REFERENCES usuarios(Id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Indicador de escritura en tiempo real'";

if ($conexion->query($sql_typing)) {
    echo "✅ Tabla 'chat_typing' creada correctamente<br>";
} else {
    echo "❌ Error creando 'chat_typing': " . $conexion->error . "<br>";
}

echo "<br><strong>✅ Migración de Chat completada!</strong><br>";
echo "<a href='../chat.php'>Ir al Chat</a>";

$conexion->close();
?>
