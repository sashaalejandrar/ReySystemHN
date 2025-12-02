<?php
header('Content-Type: application/json');

try {
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión: " . $conexion->connect_error);
    }
    
    $conexion->set_charset("utf8mb4");
    
    // Deshabilitar foreign key checks temporalmente
    $conexion->query("SET FOREIGN_KEY_CHECKS=0");
    
    $resultados = [];
    
    // 1. Crear tabla unidades_medida
    $sql1 = "CREATE TABLE IF NOT EXISTS unidades_medida (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(50) NOT NULL UNIQUE,
        abreviatura VARCHAR(10) NOT NULL,
        tipo ENUM('peso', 'volumen', 'cantidad', 'empaque') NOT NULL,
        es_default BOOLEAN DEFAULT FALSE,
        activo BOOLEAN DEFAULT TRUE,
        fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tipo (tipo),
        INDEX idx_activo (activo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conexion->query($sql1)) {
        throw new Exception("Error creando tabla: " . $conexion->error);
    }
    $resultados[] = "Tabla unidades_medida creada";
    
    // 2. Insertar unidades predefinidas
    $unidades = [
        ['Unidad', 'Ud', 'cantidad', 1],
        ['Docena', 'Dz', 'cantidad', 0],
        ['Par', 'Par', 'cantidad', 0],
        ['Kilogramo', 'Kg', 'peso', 0],
        ['Gramo', 'g', 'peso', 0],
        ['Libra', 'Lb', 'peso', 0],
        ['Onza', 'Oz', 'peso', 0],
        ['Tonelada', 'Ton', 'peso', 0],
        ['Litro', 'L', 'volumen', 0],
        ['Mililitro', 'mL', 'volumen', 0],
        ['Galón', 'Gal', 'volumen', 0],
        ['Fardo', 'Fdo', 'empaque', 0],
        ['Caja', 'Cja', 'empaque', 0],
        ['Paquete', 'Pqt', 'empaque', 0],
        ['Bulto', 'Bto', 'empaque', 0],
        ['Saco', 'Sco', 'empaque', 0],
        ['Cartón', 'Ctn', 'empaque', 0]
    ];
    
    $stmt = $conexion->prepare("INSERT IGNORE INTO unidades_medida (nombre, abreviatura, tipo, es_default) VALUES (?, ?, ?, ?)");
    $insertadas = 0;
    foreach ($unidades as $u) {
        $stmt->bind_param("sssi", $u[0], $u[1], $u[2], $u[3]);
        if ($stmt->execute()) $insertadas++;
    }
    $stmt->close();
    $resultados[] = "$insertadas unidades insertadas";
    
    // 3. Modificar tipos_precios
    $conexion->query("ALTER TABLE tipos_precios ADD COLUMN IF NOT EXISTS unidad_medida_id INT NULL");
    $resultados[] = "Columna unidad_medida_id agregada a tipos_precios";
    
    // 4. Modificar stock
    $conexion->query("ALTER TABLE stock ADD COLUMN IF NOT EXISTS unidad_medida_id INT DEFAULT 1");
    $resultados[] = "Columna unidad_medida_id agregada a stock";
    
    // 5. Modificar creacion_de_productos
    $conexion->query("ALTER TABLE creacion_de_productos ADD COLUMN IF NOT EXISTS unidad_medida_id INT DEFAULT 1");
    $resultados[] = "Columna unidad_medida_id agregada a creacion_de_productos";
    
    // 6. Crear tipos de precios automáticos
    $unidades_precio = ['Kilogramo', 'Libra', 'Litro', 'Fardo', 'Caja', 'Paquete'];
    foreach ($unidades_precio as $unidad) {
        $stmt = $conexion->prepare("
            INSERT IGNORE INTO tipos_precios (nombre, descripcion, es_default, unidad_medida_id)
            SELECT CONCAT('Precio_', ?), CONCAT('Precio por ', ?), FALSE, id
            FROM unidades_medida WHERE nombre = ?
        ");
        $stmt->bind_param("sss", $unidad, $unidad, $unidad);
        $stmt->execute();
        $stmt->close();
    }
    $resultados[] = "Tipos de precios automáticos creados";
    
    // Rehabilitar foreign key checks
    $conexion->query("SET FOREIGN_KEY_CHECKS=1");
    
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'resultados' => $resultados,
        'unidades_insertadas' => $insertadas
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
