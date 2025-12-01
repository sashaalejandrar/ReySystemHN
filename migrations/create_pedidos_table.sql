-- Tabla para gestión de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Numero_Pedido VARCHAR(50) UNIQUE,
    Fecha_Pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    Cliente VARCHAR(200) NOT NULL,
    Telefono VARCHAR(20),
    Email VARCHAR(100),
    Producto_Solicitado VARCHAR(200) NOT NULL,
    Cantidad INT NOT NULL DEFAULT 1,
    Precio_Estimado DECIMAL(10,2),
    Total_Estimado DECIMAL(10,2),
    Notas TEXT,
    Estado ENUM('Pendiente', 'En Proceso', 'Recibido', 'Entregado', 'Cancelado') DEFAULT 'Pendiente',
    Fecha_Estimada_Entrega DATE,
    Usuario_Registro VARCHAR(50),
    id_negocio INT DEFAULT 1,
    id_sucursal INT DEFAULT 1,
    INDEX idx_estado (Estado),
    INDEX idx_cliente (Cliente),
    INDEX idx_fecha (Fecha_Pedido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
