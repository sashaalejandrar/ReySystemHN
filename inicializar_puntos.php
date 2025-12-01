<?php
/**
 * Script de Inicialización del Sistema de Puntos de Fidelidad
 * Crea las tablas necesarias e inserta datos iniciales
 */

require_once 'db_connect.php';

echo "<!DOCTYPE html>
<html lang='es'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Inicializar Sistema de Puntos</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-radius: 5px; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #e3f2fd; border-radius: 5px; margin: 10px 0; }
        h1 { color: #1976d2; }
        h2 { color: #424242; margin-top: 30px; }
    </style>
</head>
<body>
    <h1>🎁 Inicialización del Sistema de Puntos de Fidelidad</h1>";

// ===== TABLA: puntos_clientes =====
echo "<h2>📊 Creando tabla: puntos_clientes</h2>";
$sql_puntos_clientes = "
CREATE TABLE IF NOT EXISTS puntos_clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_nombre VARCHAR(255) UNIQUE NOT NULL,
    puntos_disponibles INT DEFAULT 0,
    puntos_totales_ganados INT DEFAULT 0,
    puntos_totales_canjeados INT DEFAULT 0,
    nivel_membresia ENUM('Bronce', 'Plata', 'Oro', 'Platino') DEFAULT 'Bronce',
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cliente (cliente_nombre),
    INDEX idx_nivel (nivel_membresia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conexion->query($sql_puntos_clientes)) {
    echo "<div class='success'>✅ Tabla 'puntos_clientes' creada exitosamente</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conexion->error . "</div>";
}

// ===== TABLA: historial_puntos =====
echo "<h2>📜 Creando tabla: historial_puntos</h2>";
$sql_historial = "
CREATE TABLE IF NOT EXISTS historial_puntos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_nombre VARCHAR(255) NOT NULL,
    tipo ENUM('ganado', 'canjeado', 'expirado', 'ajuste') NOT NULL,
    puntos INT NOT NULL,
    descripcion TEXT,
    venta_id INT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    usuario VARCHAR(100),
    INDEX idx_cliente (cliente_nombre),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conexion->query($sql_historial)) {
    echo "<div class='success'>✅ Tabla 'historial_puntos' creada exitosamente</div>";
} else {
    echo "<div class='error'>❌ Error: " . $conexion->error . "</div>";
}

// ===== TABLA: niveles_membresia =====
echo "<h2>🏆 Creando tabla: niveles_membresia</h2>";
$sql_niveles = "
CREATE TABLE IF NOT EXISTS niveles_membresia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nivel VARCHAR(50) UNIQUE NOT NULL,
    puntos_minimos INT NOT NULL,
    multiplicador_puntos DECIMAL(3,2) DEFAULT 1.00,
    descuento_adicional DECIMAL(5,2) DEFAULT 0.00,
    color VARCHAR(20) DEFAULT '#gray',
    icono VARCHAR(50) DEFAULT 'star'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conexion->query($sql_niveles)) {
    echo "<div class='success'>✅ Tabla 'niveles_membresia' creada exitosamente</div>";
    
    // Insertar niveles predefinidos
    echo "<div class='info'>📝 Insertando niveles de membresía...</div>";
    
    $niveles_data = [
        ['Bronce', 0, 1.00, 0.00, '#CD7F32', 'military_tech'],
        ['Plata', 1000, 1.25, 2.00, '#C0C0C0', 'workspace_premium'],
        ['Oro', 5000, 1.50, 5.00, '#FFD700', 'emoji_events'],
        ['Platino', 10000, 2.00, 10.00, '#E5E4E2', 'diamond']
    ];
    
    foreach ($niveles_data as $nivel) {
        $stmt = $conexion->prepare("
            INSERT INTO niveles_membresia (nivel, puntos_minimos, multiplicador_puntos, descuento_adicional, color, icono)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                puntos_minimos = VALUES(puntos_minimos),
                multiplicador_puntos = VALUES(multiplicador_puntos),
                descuento_adicional = VALUES(descuento_adicional),
                color = VALUES(color),
                icono = VALUES(icono)
        ");
        $stmt->bind_param("siddss", $nivel[0], $nivel[1], $nivel[2], $nivel[3], $nivel[4], $nivel[5]);
        $stmt->execute();
        echo "<div class='success'>  ✓ Nivel '{$nivel[0]}' configurado</div>";
    }
} else {
    echo "<div class='error'>❌ Error: " . $conexion->error . "</div>";
}

// ===== TABLA: recompensas =====
echo "<h2>🎁 Creando tabla: recompensas</h2>";
$sql_recompensas = "
CREATE TABLE IF NOT EXISTS recompensas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    puntos_requeridos INT NOT NULL,
    tipo ENUM('descuento', 'producto', 'servicio') DEFAULT 'descuento',
    valor DECIMAL(10,2) DEFAULT 0.00,
    stock_disponible INT NULL,
    activo TINYINT(1) DEFAULT 1,
    imagen VARCHAR(255) NULL,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_puntos (puntos_requeridos),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($conexion->query($sql_recompensas)) {
    echo "<div class='success'>✅ Tabla 'recompensas' creada exitosamente</div>";
    
    // Insertar recompensas predefinidas
    echo "<div class='info'>📝 Insertando recompensas predefinidas...</div>";
    
    $recompensas_data = [
        ['Descuento L25', 'Descuento de L25 en tu próxima compra', 250, 'descuento', 25.00],
        ['Descuento L50', 'Descuento de L50 en tu próxima compra', 500, 'descuento', 50.00],
        ['Descuento L100', 'Descuento de L100 en tu próxima compra', 1000, 'descuento', 100.00],
        ['Descuento L200', 'Descuento de L200 en tu próxima compra', 2000, 'descuento', 200.00],
        ['Descuento 10%', 'Descuento del 10% en tu próxima compra', 1500, 'descuento', 10.00]
    ];
    
    foreach ($recompensas_data as $recompensa) {
        $stmt = $conexion->prepare("
            INSERT INTO recompensas (nombre, descripcion, puntos_requeridos, tipo, valor)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssisd", $recompensa[0], $recompensa[1], $recompensa[2], $recompensa[3], $recompensa[4]);
        if ($stmt->execute()) {
            echo "<div class='success'>  ✓ Recompensa '{$recompensa[0]}' agregada</div>";
        }
    }
} else {
    echo "<div class='error'>❌ Error: " . $conexion->error . "</div>";
}

echo "<h2>✅ Inicialización Completada</h2>";
echo "<div class='success'>
    <strong>¡Sistema de Puntos de Fidelidad inicializado correctamente!</strong><br><br>
    Tablas creadas:<br>
    ✓ puntos_clientes<br>
    ✓ historial_puntos<br>
    ✓ niveles_membresia (con 4 niveles)<br>
    ✓ recompensas (con 5 recompensas predefinidas)<br><br>
    <a href='puntos_fidelidad.php' style='color: #1976d2; text-decoration: none; font-weight: bold;'>
        → Ir al Panel de Puntos
    </a>
</div>";

echo "</body></html>";

$conexion->close();
?>
