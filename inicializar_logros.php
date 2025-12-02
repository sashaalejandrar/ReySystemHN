<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Script para inicializar el sistema de logros
// Crea las tablas necesarias e inserta los 10 logros predefinidos

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

echo "<h2>Inicializando Sistema de Logros...</h2>";

// 1. Crear tabla de logros
echo "<p>Creando tabla 'logros'...</p>";
$sql_logros = "CREATE TABLE IF NOT EXISTS logros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    icono VARCHAR(50) DEFAULT 'emoji_events',
    tipo_condicion ENUM('ventas_count', 'ventas_monto', 'clientes_count', 'aperturas_count', 'arqueos_sin_error', 'meta_alcanzada', 'dias_consecutivos', 'inventario_updates', 'custom') NOT NULL,
    valor_objetivo INT NOT NULL,
    puntos INT DEFAULT 10,
    color VARCHAR(20) DEFAULT '#1152d4',
    activo TINYINT(1) DEFAULT 1,
    es_predefinido TINYINT(1) DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    creado_por VARCHAR(100)
)";

if ($conexion->query($sql_logros)) {
    echo "<p style='color: green;'>✓ Tabla 'logros' creada exitosamente</p>";
} else {
    echo "<p style='color: red;'>✗ Error al crear tabla 'logros': " . $conexion->error . "</p>";
}

// 2. Crear tabla de usuarios_logros
echo "<p>Creando tabla 'usuarios_logros'...</p>";
$sql_usuarios_logros = "CREATE TABLE IF NOT EXISTS usuarios_logros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(100) NOT NULL,
    logro_id INT NOT NULL,
    progreso_actual INT DEFAULT 0,
    completado TINYINT(1) DEFAULT 0,
    fecha_desbloqueo DATETIME,
    FOREIGN KEY (logro_id) REFERENCES logros(id) ON DELETE CASCADE,
    UNIQUE KEY unique_usuario_logro (usuario, logro_id)
)";

if ($conexion->query($sql_usuarios_logros)) {
    echo "<p style='color: green;'>✓ Tabla 'usuarios_logros' creada exitosamente</p>";
} else {
    echo "<p style='color: red;'>✗ Error al crear tabla 'usuarios_logros': " . $conexion->error . "</p>";
}

// 3. Verificar si ya existen logros predefinidos
$check = $conexion->query("SELECT COUNT(*) as count FROM logros WHERE es_predefinido = 1");
$row = $check->fetch_assoc();

if ($row['count'] > 0) {
    echo "<p style='color: orange;'>⚠ Ya existen logros predefinidos. Saltando inserción...</p>";
} else {
    echo "<p>Insertando 10 logros predefinidos...</p>";
    
    // Array de logros predefinidos
    $logros_predefinidos = [
        [
            'nombre' => 'Primera Venta',
            'descripcion' => 'Realiza tu primera venta en el sistema',
            'icono' => 'shopping_bag',
            'tipo_condicion' => 'ventas_count',
            'valor_objetivo' => 1,
            'puntos' => 10,
            'color' => '#10b981'
        ],
        [
            'nombre' => 'Vendedor Novato',
            'descripcion' => 'Completa 10 ventas exitosas',
            'icono' => 'store',
            'tipo_condicion' => 'ventas_count',
            'valor_objetivo' => 10,
            'puntos' => 25,
            'color' => '#3b82f6'
        ],
        [
            'nombre' => 'Vendedor Experto',
            'descripcion' => 'Alcanza 100 ventas completadas',
            'icono' => 'workspace_premium',
            'tipo_condicion' => 'ventas_count',
            'valor_objetivo' => 100,
            'puntos' => 100,
            'color' => '#8b5cf6'
        ],
        [
            'nombre' => 'Maestro de Ventas',
            'descripcion' => 'Logra 500 ventas en total',
            'icono' => 'military_tech',
            'tipo_condicion' => 'ventas_count',
            'valor_objetivo' => 500,
            'puntos' => 250,
            'color' => '#f59e0b'
        ],
        [
            'nombre' => 'Apertura Perfecta',
            'descripcion' => 'Realiza 5 aperturas de caja sin errores',
            'icono' => 'lock_open',
            'tipo_condicion' => 'aperturas_count',
            'valor_objetivo' => 5,
            'puntos' => 30,
            'color' => '#06b6d4'
        ],
        [
            'nombre' => 'Arqueo Preciso',
            'descripcion' => 'Completa 10 arqueos de caja sin discrepancias',
            'icono' => 'calculate',
            'tipo_condicion' => 'arqueos_sin_error',
            'valor_objetivo' => 10,
            'puntos' => 50,
            'color' => '#14b8a6'
        ],
        [
            'nombre' => 'Meta Alcanzada',
            'descripcion' => 'Cumple una meta mensual de ventas',
            'icono' => 'flag',
            'tipo_condicion' => 'meta_alcanzada',
            'valor_objetivo' => 1,
            'puntos' => 75,
            'color' => '#ef4444'
        ],
        [
            'nombre' => 'Racha de Éxito',
            'descripcion' => 'Realiza ventas durante 7 días consecutivos',
            'icono' => 'local_fire_department',
            'tipo_condicion' => 'dias_consecutivos',
            'valor_objetivo' => 7,
            'puntos' => 60,
            'color' => '#f97316'
        ],
        [
            'nombre' => 'Cliente VIP',
            'descripcion' => 'Registra 50 clientes nuevos en el sistema',
            'icono' => 'person_add',
            'tipo_condicion' => 'clientes_count',
            'valor_objetivo' => 50,
            'puntos' => 80,
            'color' => '#ec4899'
        ],
        [
            'nombre' => 'Inventario Maestro',
            'descripcion' => 'Actualiza el inventario 100 veces',
            'icono' => 'inventory',
            'tipo_condicion' => 'inventario_updates',
            'valor_objetivo' => 100,
            'puntos' => 90,
            'color' => '#6366f1'
        ]
    ];
    
    $stmt = $conexion->prepare("INSERT INTO logros (nombre, descripcion, icono, tipo_condicion, valor_objetivo, puntos, color, es_predefinido, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'SYSTEM')");
    
    $insertados = 0;
    foreach ($logros_predefinidos as $logro) {
        $stmt->bind_param("ssssiss", 
            $logro['nombre'],
            $logro['descripcion'],
            $logro['icono'],
            $logro['tipo_condicion'],
            $logro['valor_objetivo'],
            $logro['puntos'],
            $logro['color']
        );
        
        if ($stmt->execute()) {
            $insertados++;
            echo "<p style='color: green;'>✓ Logro '{$logro['nombre']}' creado</p>";
        } else {
            echo "<p style='color: red;'>✗ Error al crear logro '{$logro['nombre']}': " . $stmt->error . "</p>";
        }
    }
    
    $stmt->close();
    echo "<p style='color: green; font-weight: bold;'>✓ {$insertados} logros predefinidos insertados exitosamente</p>";
}

echo "<h3 style='color: green;'>¡Sistema de Logros Inicializado Correctamente!</h3>";
echo "<p><a href='logros.php'>Ir a Logros</a> | <a href='gestionar_logros.php'>Gestionar Logros (Admin)</a></p>";

$conexion->close();
?>
