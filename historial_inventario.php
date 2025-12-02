<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'funciones.php';
date_default_timezone_set('America/Tegucigalpa');

VerificarSiUsuarioYaInicioSesion();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

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

$rol_usuario = strtolower($Rol);

// Crear tabla de movimientos si no existe
$conexion->query("CREATE TABLE IF NOT EXISTS movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto VARCHAR(255) NOT NULL,
    tipo ENUM('entrada', 'salida', 'ajuste') NOT NULL,
    cantidad INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    motivo VARCHAR(500),
    usuario VARCHAR(100),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha),
    INDEX idx_producto (producto)
)");

// Filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$tipo_filtro = $_GET['tipo'] ?? '';
$producto_filtro = $_GET['producto'] ?? '';

// Construir query
$where = ["DATE(fecha) BETWEEN '$fecha_desde' AND '$fecha_hasta'"];
if ($tipo_filtro) $where[] = "tipo = '$tipo_filtro'";
if ($producto_filtro) $where[] = "producto LIKE '%$producto_filtro%'";

$where_clause = implode(' AND ', $where);

// Obtener movimientos
$movimientos = $conexion->query("SELECT * FROM movimientos_inventario WHERE $where_clause ORDER BY fecha DESC LIMIT 100");

// Estadísticas
$total_entradas = $conexion->query("SELECT COUNT(*) as total FROM movimientos_inventario WHERE tipo = 'entrada' AND $where_clause")->fetch_assoc()['total'];
$total_salidas = $conexion->query("SELECT COUNT(*) as total FROM movimientos_inventario WHERE tipo = 'salida' AND $where_clause")->fetch_assoc()['total'];
$total_ajustes = $conexion->query("SELECT COUNT(*) as total FROM movimientos_inventario WHERE tipo = 'ajuste' AND $where_clause")->fetch_assoc()['total'];
$productos_bajo_stock = $conexion->query("SELECT COUNT(*) as total FROM stock WHERE Stock < 10")->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Historial de Inventario - Rey System</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "primary": "#137fec",
            },
            fontFamily: {
                "display": ["Inter", "sans-serif"]
            }
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
<div class="mx-auto max-w-7xl">

<!-- Header -->
<div class="mb-8">
    <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">Historial de Inventario</h1>
    <p class="text-gray-500 dark:text-[#92a4c9] text-base mt-2">Seguimiento de movimientos y cambios en el inventario</p>
</div>

<!-- Estadísticas -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <span class="material-symbols-outlined text-4xl opacity-80">arrow_downward</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Entradas</p>
                <p class="text-3xl font-bold"><?php echo $total_entradas; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <span class="material-symbols-outlined text-4xl opacity-80">arrow_upward</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Salidas</p>
                <p class="text-3xl font-bold"><?php echo $total_salidas; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <span class="material-symbols-outlined text-4xl opacity-80">tune</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Ajustes</p>
                <p class="text-3xl font-bold"><?php echo $total_ajustes; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <span class="material-symbols-outlined text-4xl opacity-80">warning</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Bajo Stock</p>
                <p class="text-3xl font-bold"><?php echo $productos_bajo_stock; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl rounded-2xl border border-slate-200/50 dark:border-slate-700/50 p-6 mb-6 shadow-xl">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center">
                <span class="material-symbols-outlined text-white text-xl">filter_alt</span>
            </div>
            <h3 class="text-gray-900 dark:text-white text-xl font-bold">Filtros de Búsqueda</h3>
        </div>
        <a href="?" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg font-medium transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">refresh</span>
            Limpiar
        </a>
    </div>
    
    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="space-y-2">
            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                <span class="material-symbols-outlined text-sm text-blue-500">calendar_today</span>
                Fecha Desde
            </label>
            <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>" 
                class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
        </div>
        
        <div class="space-y-2">
            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                <span class="material-symbols-outlined text-sm text-blue-500">event</span>
                Fecha Hasta
            </label>
            <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>" 
                class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all">
        </div>
        
        <div class="space-y-2">
            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                <span class="material-symbols-outlined text-sm text-purple-500">category</span>
                Tipo de Movimiento
            </label>
            <select name="tipo" class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all">
                <option value="">Todos los tipos</option>
                <option value="entrada" <?php echo $tipo_filtro == 'entrada' ? 'selected' : ''; ?>>📥 Entradas</option>
                <option value="salida" <?php echo $tipo_filtro == 'salida' ? 'selected' : ''; ?>>📤 Salidas</option>
                <option value="ajuste" <?php echo $tipo_filtro == 'ajuste' ? 'selected' : ''; ?>>⚙️ Ajustes</option>
            </select>
        </div>
        
        <div class="space-y-2">
            <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                <span class="material-symbols-outlined text-sm text-green-500">search</span>
                Buscar Producto
            </label>
            <input type="text" name="producto" value="<?php echo $producto_filtro; ?>" placeholder="Nombre del producto..."
                class="w-full px-4 py-2.5 rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white placeholder:text-slate-400 focus:border-green-500 focus:ring-2 focus:ring-green-500/20 transition-all">
        </div>
        
        <div class="md:col-span-2 lg:col-span-4 flex gap-3 pt-2">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-xl flex items-center gap-2">
                <span class="material-symbols-outlined">search</span>
                Aplicar Filtros
            </button>
            <button type="button" onclick="window.print()" class="px-6 py-3 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl font-bold transition-all flex items-center gap-2">
                <span class="material-symbols-outlined">print</span>
                Imprimir
            </button>
        </div>
    </form>
</div>

<!-- Tabla de movimientos -->
<div class="bg-white/80 dark:bg-slate-900/80 backdrop-blur-xl rounded-2xl border border-slate-200/50 dark:border-slate-700/50 overflow-hidden shadow-xl">
    <div class="p-6 border-b border-slate-200 dark:border-slate-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-xl">inventory</span>
                </div>
                <div>
                    <h3 class="text-gray-900 dark:text-white text-xl font-bold">Movimientos Recientes</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Últimos 100 registros</p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
            <thead class="bg-slate-50 dark:bg-slate-800/50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">schedule</span>
                            Fecha
                        </div>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">inventory_2</span>
                            Producto
                        </div>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">category</span>
                            Tipo
                        </div>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">numbers</span>
                            Cantidad
                        </div>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Stock Anterior</th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Stock Nuevo</th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        <div class="flex items-center gap-2">
                            <span class="material-symbols-outlined text-sm">person</span>
                            Usuario
                        </div>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Motivo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                <?php if ($movimientos && $movimientos->num_rows > 0): ?>
                    <?php while($mov = $movimientos->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-slate-400 text-sm">calendar_today</span>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            <?php echo date('d/m/Y', strtotime($mov['fecha'])); ?>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            <?php echo date('H:i', strtotime($mov['fecha'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    <?php echo htmlspecialchars($mov['producto']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $tipo_config = [
                                    'entrada' => [
                                        'class' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border border-green-200 dark:border-green-800',
                                        'icon' => 'arrow_downward',
                                        'label' => 'Entrada'
                                    ],
                                    'salida' => [
                                        'class' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border border-red-200 dark:border-red-800',
                                        'icon' => 'arrow_upward',
                                        'label' => 'Salida'
                                    ],
                                    'ajuste' => [
                                        'class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-200 dark:border-blue-800',
                                        'icon' => 'tune',
                                        'label' => 'Ajuste'
                                    ]
                                ];
                                $config = $tipo_config[$mov['tipo']];
                                ?>
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold <?php echo $config['class']; ?>">
                                    <span class="material-symbols-outlined text-sm"><?php echo $config['icon']; ?></span>
                                    <?php echo $config['label']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <?php
                                    $diff = $mov['stock_nuevo'] - $mov['stock_anterior'];
                                    if ($diff > 0) {
                                        echo '<span class="material-symbols-outlined text-green-500 text-sm">trending_up</span>';
                                        echo '<span class="text-sm font-bold text-green-600 dark:text-green-400">+' . $mov['cantidad'] . '</span>';
                                    } elseif ($diff < 0) {
                                        echo '<span class="material-symbols-outlined text-red-500 text-sm">trending_down</span>';
                                        echo '<span class="text-sm font-bold text-red-600 dark:text-red-400">-' . $mov['cantidad'] . '</span>';
                                    } else {
                                        echo '<span class="text-sm font-bold text-gray-600 dark:text-gray-400">' . $mov['cantidad'] . '</span>';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-500 dark:text-gray-400 font-mono">
                                    <?php echo $mov['stock_anterior']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 dark:text-white font-mono">
                                    <?php echo $mov['stock_nuevo']; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
                                        <span class="text-white text-xs font-bold">
                                            <?php echo strtoupper(substr($mov['usuario'], 0, 2)); ?>
                                        </span>
                                    </div>
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        <?php echo htmlspecialchars($mov['usuario']); ?>
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    <?php echo htmlspecialchars($mov['motivo'] ?: '-'); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-20 h-20 bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700 rounded-2xl flex items-center justify-center mb-4">
                                    <span class="material-symbols-outlined text-slate-400 dark:text-slate-500 text-4xl">inventory</span>
                                </div>
                                <h3 class="text-lg font-bold text-gray-700 dark:text-gray-300 mb-1">No hay movimientos</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">No se encontraron registros con los filtros aplicados</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
</main>
</div>

</body></html>
<?php
$conexion->close();
?>
