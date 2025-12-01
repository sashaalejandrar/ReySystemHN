<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'funciones.php';
date_default_timezone_set('America/Tegucigalpa');

VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Obtener información del usuario
$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre_Completo = $row['Nombre']." ".$row['Apellido'];
}

$rol_usuario = strtolower($Rol);

// Crear tabla de notificaciones si no existe
$conexion->query("CREATE TABLE IF NOT EXISTS notificaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('info', 'success', 'warning', 'error', 'alert') NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    mensaje TEXT NOT NULL,
    modulo VARCHAR(100),
    usuario_destino VARCHAR(100),
    leida BOOLEAN DEFAULT FALSE,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_usuario (usuario_destino),
    INDEX idx_leida (leida),
    INDEX idx_fecha (fecha)
)");

// Marcar como leída
if (isset($_POST['marcar_leida'])) {
    $id = intval($_POST['id']);
    $conexion->query("UPDATE notificaciones SET leida = TRUE WHERE id = $id");
}

// Marcar todas como leídas
if (isset($_POST['marcar_todas_leidas'])) {
    $conexion->query("UPDATE notificaciones SET leida = TRUE WHERE usuario_destino = '$Usuario' OR usuario_destino IS NULL");
}

// Eliminar notificación
if (isset($_POST['eliminar'])) {
    $id = intval($_POST['id']);
    $conexion->query("DELETE FROM notificaciones WHERE id = $id");
}

// Generar notificaciones automáticas del sistema
function generarNotificacionesAutomaticas($conexion, $usuario) {
    // Obtener el ID del usuario
    $usuario_result = $conexion->query("SELECT id FROM usuarios WHERE usuario = '$usuario'");
    if ($usuario_result && $usuario_row = $usuario_result->fetch_assoc()) {
        $usuario_id = $usuario_row['id'];
    } else {
        return; // Si no se encuentra el usuario, salir
    }
    
    // Stock bajo - Generar notificaciones para productos con stock < 10
    $productos_bajo_stock = $conexion->query("SELECT Id, Nombre_Producto, Stock FROM stock WHERE CAST(Stock AS UNSIGNED) < 10 AND CAST(Stock AS UNSIGNED) > 0");
    if ($productos_bajo_stock && $productos_bajo_stock->num_rows > 0) {
        while ($producto = $productos_bajo_stock->fetch_assoc()) {
            // Verificar si ya existe una notificación para este producto hoy
            $existe = $conexion->query("SELECT id FROM notificaciones WHERE tipo = 'stock_bajo' AND producto_id = " . $producto['Id'] . " AND usuario_id = $usuario_id AND DATE(fecha_creacion) = CURDATE() LIMIT 1")->num_rows;
            if (!$existe) {
                $mensaje = "Stock bajo: '" . $producto['Nombre_Producto'] . "' con " . $producto['Stock'] . " unidades.";
                $conexion->query("INSERT INTO notificaciones (usuario_id, tipo, mensaje, producto_id, leido) VALUES ($usuario_id, 'stock_bajo', '$mensaje', " . $producto['Id'] . ", 0)");
            }
        }
    }
    
    // Sin stock - Productos con stock = 0
    $productos_sin_stock = $conexion->query("SELECT Id, Nombre_Producto FROM stock WHERE CAST(Stock AS UNSIGNED) = 0");
    if ($productos_sin_stock && $productos_sin_stock->num_rows > 0) {
        while ($producto = $productos_sin_stock->fetch_assoc()) {
            // Verificar si ya existe una notificación para este producto hoy
            $existe = $conexion->query("SELECT id FROM notificaciones WHERE tipo = 'sin_stock' AND producto_id = " . $producto['Id'] . " AND usuario_id = $usuario_id AND DATE(fecha_creacion) = CURDATE() LIMIT 1")->num_rows;
            if (!$existe) {
                $mensaje = "Sin stock: '" . $producto['Nombre_Producto'] . "' agotado.";
                $conexion->query("INSERT INTO notificaciones (usuario_id, tipo, mensaje, producto_id, leido) VALUES ($usuario_id, 'sin_stock', '$mensaje', " . $producto['Id'] . ", 0)");
            }
        }
    }
    
    // Productos por vencer (próximos 30 días)
    $productos_por_vencer = $conexion->query("SELECT Id, Nombre_Producto, Fecha_Vencimiento FROM stock WHERE Fecha_Vencimiento != '' AND STR_TO_DATE(Fecha_Vencimiento, '%Y-%m-%d') BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)");
    if ($productos_por_vencer && $productos_por_vencer->num_rows > 0) {
        while ($producto = $productos_por_vencer->fetch_assoc()) {
            // Verificar si ya existe una notificación para este producto hoy
            $existe = $conexion->query("SELECT id FROM notificaciones WHERE tipo = 'por_vencer' AND producto_id = " . $producto['Id'] . " AND usuario_id = $usuario_id AND DATE(fecha_creacion) = CURDATE() LIMIT 1")->num_rows;
            if (!$existe) {
                $mensaje = "Producto por vencer: '" . $producto['Nombre_Producto'] . "' vence el " . $producto['Fecha_Vencimiento'] . ".";
                $conexion->query("INSERT INTO notificaciones (usuario_id, tipo, mensaje, producto_id, leido) VALUES ($usuario_id, 'por_vencer', '$mensaje', " . $producto['Id'] . ", 0)");
            }
        }
    }
}

generarNotificacionesAutomaticas($conexion, $Usuario);

// Filtros
$tipo_filtro = $_GET['tipo'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';

// Construir query
$where = ["(usuario_destino = '$Usuario' OR usuario_destino IS NULL)"];
if ($tipo_filtro) $where[] = "tipo = '$tipo_filtro'";
if ($estado_filtro === 'leidas') $where[] = "leida = TRUE";
if ($estado_filtro === 'no_leidas') $where[] = "leida = FALSE";

$where_clause = implode(' AND ', $where);

// Obtener notificaciones
$notificaciones = $conexion->query("SELECT * FROM notificaciones WHERE $where_clause ORDER BY fecha_creacion DESC LIMIT 100");

// Estadísticas
$total_notificaciones = $conexion->query("SELECT COUNT(*) as total FROM notificaciones WHERE $where_clause")->fetch_assoc()['total'];
$no_leidas = $conexion->query("SELECT COUNT(*) as total FROM notificaciones WHERE (usuario_destino = '$Usuario' OR usuario_destino IS NULL) AND leido = FALSE")->fetch_assoc()['total'];
$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Email = $row['Email'];
    $Celular = $row['Celular'];
    $Perfil = $row['Perfil'];
}

// --- INICIO DE LA LÓGICA DE PERMISOS ---
// Convertimos el rol a minúsculas para hacer la comparación insensible a mayúsculas/minúsculas.
$rol_usuario = strtolower($Rol);
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Notificaciones - Rey System</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: { "primary": "#137fec" },
            fontFamily: { "display": ["Inter", "sans-serif"] }
        }
    }
}
</script>
<style>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
</style>
</head>
<body class="bg-[#f6f7f8] dark:bg-[#101922] font-display">

<div class="flex min-h-screen w-full">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 p-8">
<div class="mx-auto max-w-5xl">

<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">Notificaciones</h1>
        <p class="text-gray-500 dark:text-[#92a4c9] text-base mt-2">Centro de alertas y mensajes del sistema</p>
    </div>
    <?php if ($no_leidas > 0): ?>
    <form method="POST">
        <button type="submit" name="marcar_todas_leidas" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-bold hover:bg-primary/90">
            <span class="material-symbols-outlined text-sm">done_all</span>
            Marcar todas como leídas
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- Estadísticas -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <span class="material-symbols-outlined text-4xl opacity-80">notifications</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Total</p>
                <p class="text-3xl font-bold"><?php echo $total_notificaciones; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <span class="material-symbols-outlined text-4xl opacity-80">mark_email_unread</span>
            <div class="text-right">
                <p class="text-sm opacity-90">No Leídas</p>
                <p class="text-3xl font-bold"><?php echo $no_leidas; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6">
    <h3 class="text-gray-900 dark:text-white text-lg font-bold mb-4">Filtros</h3>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Tipo</label>
            <select name="tipo" class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
                <option value="">Todos</option>
                <option value="info" <?php echo $tipo_filtro == 'info' ? 'selected' : ''; ?>>Info</option>
                <option value="success" <?php echo $tipo_filtro == 'success' ? 'selected' : ''; ?>>Éxito</option>
                <option value="warning" <?php echo $tipo_filtro == 'warning' ? 'selected' : ''; ?>>Advertencia</option>
                <option value="error" <?php echo $tipo_filtro == 'error' ? 'selected' : ''; ?>>Error</option>
                <option value="alert" <?php echo $tipo_filtro == 'alert' ? 'selected' : ''; ?>>Alerta</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Estado</label>
            <select name="estado" class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
                <option value="">Todas</option>
                <option value="no_leidas" <?php echo $estado_filtro == 'no_leidas' ? 'selected' : ''; ?>>No leídas</option>
                <option value="leidas" <?php echo $estado_filtro == 'leidas' ? 'selected' : ''; ?>>Leídas</option>
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full px-6 py-2 bg-primary text-white rounded-lg font-bold hover:bg-primary/90">
                Aplicar Filtros
            </button>
        </div>
    </form>
</div>

<!-- Lista de notificaciones -->
<div class="space-y-4">
    <?php if ($notificaciones && $notificaciones->num_rows > 0): ?>
        <?php while($notif = $notificaciones->fetch_assoc()): 
            $tipo_config = [
                'info' => ['bg' => 'bg-blue-50 dark:bg-blue-900/20', 'border' => 'border-blue-200 dark:border-blue-800', 'icon_bg' => 'bg-blue-500', 'icon' => 'info'],
                'success' => ['bg' => 'bg-green-50 dark:bg-green-900/20', 'border' => 'border-green-200 dark:border-green-800', 'icon_bg' => 'bg-green-500', 'icon' => 'check_circle'],
                'warning' => ['bg' => 'bg-yellow-50 dark:bg-yellow-900/20', 'border' => 'border-yellow-200 dark:border-yellow-800', 'icon_bg' => 'bg-yellow-500', 'icon' => 'warning'],
                'error' => ['bg' => 'bg-red-50 dark:bg-red-900/20', 'border' => 'border-red-200 dark:border-red-800', 'icon_bg' => 'bg-red-500', 'icon' => 'error'],
                'alert' => ['bg' => 'bg-purple-50 dark:bg-purple-900/20', 'border' => 'border-purple-200 dark:border-purple-800', 'icon_bg' => 'bg-purple-500', 'icon' => 'campaign']
            ][$notif['tipo']];
        ?>
            <div class="<?php echo $tipo_config['bg']; ?> rounded-xl border <?php echo $tipo_config['border']; ?> p-6 <?php echo !$notif['leida'] ? 'ring-2 ring-primary/20' : ''; ?>">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full <?php echo $tipo_config['icon_bg']; ?> flex items-center justify-center flex-shrink-0">
                        <span class="material-symbols-outlined text-white text-2xl"><?php echo $tipo_config['icon']; ?></span>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-gray-900 dark:text-white font-bold text-lg">
                                    <?php echo htmlspecialchars($notif['titulo']); ?>
                                    <?php if (!$notif['leida']): ?>
                                        <span class="ml-2 px-2 py-1 bg-primary text-white text-xs rounded-full">Nueva</span>
                                    <?php endif; ?>
                                </h3>
                                <p class="text-gray-700 dark:text-gray-300 text-sm mt-1">
                                    <?php echo htmlspecialchars($notif['mensaje']); ?>
                                </p>
                                <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-sm">schedule</span>
                                        <?php echo date('d/m/Y H:i', strtotime($notif['fecha'])); ?>
                                    </span>
                                    <?php if ($notif['modulo']): ?>
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-sm">folder</span>
                                            <?php echo htmlspecialchars($notif['modulo']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if (!$notif['leida']): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                        <button type="submit" name="marcar_leida" title="Marcar como leída"
                                                class="p-2 hover:bg-white dark:hover:bg-gray-800 rounded-lg transition">
                                            <span class="material-symbols-outlined text-gray-600 dark:text-gray-400">done</span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta notificación?');">
                                    <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                    <button type="submit" name="eliminar" title="Eliminar"
                                            class="p-2 hover:bg-white dark:hover:bg-gray-800 rounded-lg transition">
                                        <span class="material-symbols-outlined text-gray-600 dark:text-gray-400">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-16 text-center">
            <span class="material-symbols-outlined text-gray-400 dark:text-[#92a4c9] text-6xl">notifications_off</span>
            <h3 class="text-gray-900 dark:text-white text-xl font-bold mt-4">No hay notificaciones</h3>
            <p class="text-gray-500 dark:text-[#92a4c9] mt-2">Estás al día con todas tus notificaciones</p>
        </div>
    <?php endif; ?>
</div>

</div>
</main>
</div>

</body></html>
<?php
$conexion->close();
?>
