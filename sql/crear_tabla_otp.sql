-- Tabla para almacenar códigos OTP de apertura y cierre de caja
-- Estos códigos se generan cuando el monto es menor al esperado

CREATE TABLE IF NOT EXISTS codigos_otp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    tipo ENUM('apertura', 'cierre') NOT NULL,
    fecha_generacion DATETIME NOT NULL,
    fecha_expiracion DATETIME NOT NULL,
    usado TINYINT(1) DEFAULT 0,
    email_enviado VARCHAR(255),
    monto_esperado DECIMAL(10,2),
    monto_real DECIMAL(10,2),
    usuario VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_usado_exp (usado, fecha_expiracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
