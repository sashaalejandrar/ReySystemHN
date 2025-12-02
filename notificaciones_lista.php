<?php
session_start();
include 'funciones.php';
date_default_timezone_set('America/Tegucigalpa');

VerificarSiUsuarioYaInicioSesion();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener información del usuario
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
$rol_usuario = strtolower($Rol);
// --- FIN DE LA LÓGICA DE PERMISOS ---

// Obtener el ID del usuario actual
$usuario_id_result = $conexion->query("SELECT id FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
$usuario_id_row = $usuario_id_result->fetch_assoc();
$usuario_id = $usuario_id_row['id'];

// Marcar como leída
if (isset($_POST['marcar_leida'])) {
    $id = intval($_POST['id']);
    $conexion->query("UPDATE notificaciones SET leido = 1 WHERE id = $id");
    header("Location: notificaciones_lista.php");
    exit;
}

// Marcar todas como leídas
if (isset($_POST['marcar_todas_leidas'])) {
    $conexion->query("UPDATE notificaciones SET leido = 1 WHERE usuario_id = $usuario_id");
    header("Location: notificaciones_lista.php");
    exit;
}

// Eliminar notificación
if (isset($_POST['eliminar'])) {
    $id = intval($_POST['id']);
    $conexion->query("DELETE FROM notificaciones WHERE id = $id");
    header("Location: notificaciones_lista.php");
    exit;
}

// Eliminar todas las notificaciones leídas
if (isset($_POST['eliminar_leidas'])) {
    $conexion->query("DELETE FROM notificaciones WHERE leido = 1 AND usuario_id = $usuario_id");
    header("Location: notificaciones_lista.php");
    exit;
}

// Filtros
$tipo_filtro = $_GET['tipo'] ?? '';
$estado_filtro = $_GET['estado'] ?? '';

// Construir query
$where = ["usuario_id = $usuario_id"];
if ($tipo_filtro) $where[] = "tipo = '$tipo_filtro'";
if ($estado_filtro === 'leidas') $where[] = "leido = 1";
if ($estado_filtro === 'no_leidas') $where[] = "leido = 0";

$where_clause = implode(' AND ', $where);

// Obtener notificaciones
$notificaciones = $conexion->query("SELECT * FROM notificaciones WHERE $where_clause ORDER BY fecha_creacion DESC");

// Estadísticas
$total_notificaciones = $conexion->query("SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = $usuario_id")->fetch_assoc()['total'];
$no_leidas = $conexion->query("SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = $usuario_id AND leido = 0")->fetch_assoc()['total'];
$leidas = $conexion->query("SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = $usuario_id AND leido = 1")->fetch_assoc()['total'];



// DEBUG - Comentar después de verificar
echo "<!-- DEBUG: Usuario ID: $usuario_id -->";
echo "<!-- DEBUG: Where clause: $where_clause -->";
echo "<!-- DEBUG: Total notificaciones: $total_notificaciones -->";
echo "<!-- DEBUG: Notificaciones query rows: " . ($notificaciones ? $notificaciones->num_rows : 'NULL') . " -->";
// NO consumir filas con fetch_assoc en el debug


?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Lista de Notificaciones - Rey System APP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#1152d4",
                    "background-light": "#f6f6f8",
                    "background-dark": "#101622",
                },
                fontFamily: {
                    "display": ["Manrope", "sans-serif"]
                },
                borderRadius: {
                    "DEFAULT": "0.25rem",
                    "lg": "0.5rem",
                    "xl": "0.75rem",
                    "full": "9999px"
                },
            },
        },
    }
</script>
<style>
    .material-symbols-outlined {
        font-variation-settings:
        'FILL' 0,
        'wght' 400,
        'GRAD' 0,
        'opsz' 24
    }
    
    .notification-badge {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: .5;
        }
    }
</style>
<script src="nova_rey.js"></script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>
<!-- Main Content -->
<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">

<!-- PageHeading -->
<div class="flex flex-wrap justify-between gap-4 mb-8">
    <div class="flex flex-col gap-2">
        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">
            <span class="material-symbols-outlined text-primary inline-block align-middle mr-2">notifications</span>
            Centro de Notificaciones
        </h1>
        <p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">
            Gestiona todas las alertas y mensajes del sistema
        </p>
    </div>
    <div class="flex gap-3">
        <?php if ($no_leidas > 0): ?>
        <form method="POST" class="inline">
            <button type="submit" name="marcar_todas_leidas" 
                    class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition-all shadow-lg hover:shadow-xl">
                <span class="material-symbols-outlined text-sm">done_all</span>
                Marcar todas como leídas
            </button>
        </form>
        <?php endif; ?>
        <?php if ($leidas > 0): ?>
        <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar todas las notificaciones leídas?');">
            <button type="submit" name="eliminar_leidas" 
                    class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition-all shadow-lg hover:shadow-xl">
                <span class="material-symbols-outlined text-sm">delete_sweep</span>
                Limpiar leídas
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Estadísticas -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90 font-medium">Total Notificaciones</p>
                <p class="text-4xl font-black mt-2"><?php echo $total_notificaciones; ?></p>
            </div>
            <span class="material-symbols-outlined text-6xl opacity-30">notifications</span>
        </div>
    </div>

    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90 font-medium">No Leídas</p>
                <p class="text-4xl font-black mt-2"><?php echo $no_leidas; ?></p>
            </div>
            <span class="material-symbols-outlined text-6xl opacity-30">mark_email_unread</span>
        </div>
    </div>

    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm opacity-90 font-medium">Leídas</p>
                <p class="text-4xl font-black mt-2"><?php echo $leidas; ?></p>
            </div>
            <span class="material-symbols-outlined text-6xl opacity-30">mark_email_read</span>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6 shadow-lg">
    <div class="flex items-center gap-2 mb-4">
        <span class="material-symbols-outlined text-primary">filter_alt</span>
        <h3 class="text-gray-900 dark:text-white text-lg font-bold">Filtros de Búsqueda</h3>
    </div>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Tipo</label>
            <select name="tipo" class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                <option value="">Todos</option>
                <option value="stock_bajo" <?php echo $tipo_filtro == 'stock_bajo' ? 'selected' : ''; ?>>📦 Stock Bajo</option>
                <option value="sin_stock" <?php echo $tipo_filtro == 'sin_stock' ? 'selected' : ''; ?>>❌ Sin Stock</option>
                <option value="por_vencer" <?php echo $tipo_filtro == 'por_vencer' ? 'selected' : ''; ?>>⏰ Por Vencer</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Estado</label>
            <select name="estado" class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                <option value="">Todas</option>
                <option value="no_leidas" <?php echo $estado_filtro == 'no_leidas' ? 'selected' : ''; ?>>📬 No leídas</option>
                <option value="leidas" <?php echo $estado_filtro == 'leidas' ? 'selected' : ''; ?>>📭 Leídas</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 px-6 py-2 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition-all shadow-lg hover:shadow-xl">
                <span class="material-symbols-outlined text-sm inline-block align-middle mr-1">search</span>
                Filtrar
            </button>
            <a href="notificaciones_lista.php" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
                <span class="material-symbols-outlined text-sm">refresh</span>
            </a>
        </div>
    </form>
</div>

<!-- Tabla de Notificaciones -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-[#111722] border-b border-gray-200 dark:border-[#324467]">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Mensaje</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Producto</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-[#324467]">
                <?php if ($notificaciones && $notificaciones->num_rows > 0): ?>
                    <?php echo "<!-- DEBUG: Entrando al IF - num_rows = " . $notificaciones->num_rows . " -->"; ?>
                    <?php 
                    $tipo_config = [
                        'stock_bajo' => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/30', 'text' => 'text-yellow-800 dark:text-yellow-300', 'icon' => 'inventory_2', 'label' => 'Stock Bajo'],
                        'sin_stock' => ['bg' => 'bg-red-100 dark:bg-red-900/30', 'text' => 'text-red-800 dark:text-red-300', 'icon' => 'remove_shopping_cart', 'label' => 'Sin Stock'],
                        'por_vencer' => ['bg' => 'bg-orange-100 dark:bg-orange-900/30', 'text' => 'text-orange-800 dark:text-orange-300', 'icon' => 'schedule', 'label' => 'Por Vencer']
                    ];
                    
                    while($notif = $notificaciones->fetch_assoc()): 
                        $config = $tipo_config[$notif['tipo']] ?? ['bg' => 'bg-blue-100 dark:bg-blue-900/30', 'text' => 'text-blue-800 dark:text-blue-300', 'icon' => 'info', 'label' => 'Info'];
                        
                        // Obtener nombre del producto si existe
                        $producto_nombre = '-';
                        if ($notif['producto_id']) {
                            $prod_result = $conexion->query("SELECT Nombre_Producto FROM stock WHERE Id = " . intval($notif['producto_id']));
                            if ($prod_result && $prod_row = $prod_result->fetch_assoc()) {
                                $producto_nombre = $prod_row['Nombre_Producto'];
                            }
                        }
                    ?>
                        <?php if (!isset($row_count)) $row_count = 0; $row_count++; ?>
                        <?php if ($row_count <= 3) echo "<!-- DEBUG: Procesando fila $row_count - ID: " . $notif['id'] . " -->"; ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#1a2332] transition-colors <?php echo !$notif['leido'] ? 'bg-blue-50/30 dark:bg-blue-900/10' : ''; ?>">
                            <!-- Estado -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if (!$notif['leido']): ?>
                                    <span class="flex items-center gap-2">
                                        <span class="w-3 h-3 bg-primary rounded-full notification-badge"></span>
                                        <span class="text-xs font-bold text-primary">Nueva</span>
                                    </span>
                                <?php else: ?>
                                    <span class="flex items-center gap-2">
                                        <span class="w-3 h-3 bg-gray-300 dark:bg-gray-600 rounded-full"></span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">Leída</span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Tipo -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold <?php echo $config['bg'] . ' ' . $config['text']; ?>">
                                    <span class="material-symbols-outlined text-sm"><?php echo $config['icon']; ?></span>
                                    <?php echo $config['label']; ?>
                                </span>
                            </td>
                            
                            <!-- Mensaje -->
                            <td class="px-6 py-4">
                                <div class="max-w-md">
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        <?php echo htmlspecialchars($notif['mensaje']); ?>
                                    </p>
                                </div>
                            </td>
                            
                            <!-- Producto -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($notif['producto_id']): ?>
                                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-medium text-gray-700 dark:text-gray-300">
                                        <span class="material-symbols-outlined text-sm">inventory_2</span>
                                        <?php echo htmlspecialchars($producto_nombre); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-gray-400 dark:text-gray-600">-</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- Fecha -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <span class="text-xs font-medium text-gray-900 dark:text-white">
                                        <?php echo date('d/m/Y', strtotime($notif['fecha_creacion'])); ?>
                                    </span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        <?php echo date('H:i', strtotime($notif['fecha_creacion'])); ?>
                                    </span>
                                </div>
                            </td>
                            
                            <!-- Acciones -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <?php if (!$notif['leido']): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                            <button type="submit" name="marcar_leida" 
                                                    title="Marcar como leída"
                                                    class="p-2 hover:bg-green-100 dark:hover:bg-green-900/30 rounded-lg transition-all group">
                                                <span class="material-symbols-outlined text-gray-600 dark:text-gray-400 group-hover:text-green-600 dark:group-hover:text-green-400">done</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta notificación?');">
                                        <input type="hidden" name="id" value="<?php echo $notif['id']; ?>">
                                        <button type="submit" name="eliminar" 
                                                title="Eliminar"
                                                class="p-2 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-all group">
                                            <span class="material-symbols-outlined text-gray-600 dark:text-gray-400 group-hover:text-red-600 dark:group-hover:text-red-400">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-20 text-center">
                            <div class="flex flex-col items-center gap-6">
                                <!-- Rey con corona -->
                                <div class="relative">
                                    <div class="text-8xl animate-bounce">👑</div>
                                    <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 text-6xl">🤴</div>
                                </div>
                                <div class="space-y-2">
                                    <h3 class="text-gray-900 dark:text-white text-2xl font-black">¡Todo está tranquilo por aquí!</h3>
                                    <p class="text-gray-600 dark:text-[#92a4c9] text-base">
                                        El Rey System está en paz. No hay notificaciones pendientes.
                                    </p>
                                    <p class="text-gray-500 dark:text-gray-400 text-sm italic">
                                        "Un reino sin alertas es un reino próspero" 👑
                                    </p>
                                </div>
                                <!-- Decoración adicional -->
                                <div class="flex gap-4 text-4xl opacity-50">
                                    <span>⭐</span>
                                    <span>✨</span>
                                    <span>🌟</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Información adicional -->
<div class="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
    <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">info</span>
        <div>
            <h4 class="text-sm font-bold text-blue-900 dark:text-blue-300 mb-1">Información del Sistema</h4>
            <p class="text-xs text-blue-800 dark:text-blue-400">
                Las notificaciones se generan automáticamente para alertarte sobre eventos importantes del inventario como stock bajo, productos sin stock, y productos próximos a vencer.
            </p>
        </div>
    </div>
</div>

</div>

<!-- Footer -->
<footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
    <p class="text-gray-500 dark:text-[#92a4c9]">Versión 1.0.0</p>
    <a class="text-primary hover:underline" href="#">Ayuda y Soporte</a>
</footer>
</main>
</div>
</div>

<script>
// Auto-refresh cada 60 segundos si hay notificaciones no leídas
<?php if ($no_leidas > 0): ?>
setTimeout(function() {
    location.reload();
}, 60000);
<?php endif; ?>
</script>

</body></html>
<?php
$conexion->close();
?>
