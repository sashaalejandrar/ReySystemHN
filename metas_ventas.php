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
    $Perfil = $row['Perfil'];   
}

$rol_usuario = strtolower($Rol);
$es_admin = ($rol_usuario === 'admin');
$es_cajero_gerente = ($rol_usuario === 'cajero/gerente');

// Verificar que el usuario tenga permisos para acceder a esta página
if (!$es_admin && !$es_cajero_gerente) {
    header('Location: index.php');
    exit();
}

// Crear tabla de metas si no existe
$conexion->query("CREATE TABLE IF NOT EXISTS metas_ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mes INT NOT NULL,
    anio INT NOT NULL,
    meta_monto DECIMAL(10,2) NOT NULL,
    meta_transacciones INT DEFAULT 0,
    descripcion TEXT,
    creado_por VARCHAR(100),
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_mes_anio (mes, anio)
)");

$mensaje = '';
$tipo_mensaje = '';

// Crear/Actualizar meta
if (isset($_POST['guardar_meta']) && $es_admin) {
    $mes = intval($_POST['mes']);
    $anio = intval($_POST['anio']);
    $meta_monto = floatval($_POST['meta_monto']);
    $meta_transacciones = intval($_POST['meta_transacciones']);
    $descripcion = $_POST['descripcion'];
    
    $stmt = $conexion->prepare("INSERT INTO metas_ventas (mes, anio, meta_monto, meta_transacciones, descripcion, creado_por) 
                                VALUES (?, ?, ?, ?, ?, ?) 
                                ON DUPLICATE KEY UPDATE meta_monto = ?, meta_transacciones = ?, descripcion = ?");
    $stmt->bind_param("iidissdis", $mes, $anio, $meta_monto, $meta_transacciones, $descripcion, $Usuario, $meta_monto, $meta_transacciones, $descripcion);
    
    if ($stmt->execute()) {
        $mensaje = 'Meta guardada exitosamente';
        $tipo_mensaje = 'success';
    }
}

// Mes y año actual
$mes_actual = date('n');
$anio_actual = date('Y');

// Obtener meta del mes actual
$meta_actual = $conexion->query("SELECT * FROM metas_ventas WHERE mes = $mes_actual AND anio = $anio_actual")->fetch_assoc();

// Ventas del mes actual
$ventas_mes = $conexion->query("SELECT SUM(Total) as total, COUNT(*) as transacciones FROM ventas WHERE MONTH(Fecha_Venta) = $mes_actual AND YEAR(Fecha_Venta) = $anio_actual")->fetch_assoc();
$total_ventas_mes = floatval($ventas_mes['total'] ?? 0);
$total_transacciones_mes = intval($ventas_mes['transacciones'] ?? 0);

// Calcular progreso
$meta_monto = floatval($meta_actual['meta_monto'] ?? 0);
$meta_transacciones = intval($meta_actual['meta_transacciones'] ?? 0);

$progreso_monto = $meta_monto > 0 ? ($total_ventas_mes / $meta_monto) * 100 : 0;
$progreso_transacciones = $meta_transacciones > 0 ? ($total_transacciones_mes / $meta_transacciones) * 100 : 0;

// Obtener historial de metas
$historial = $conexion->query("SELECT m.*, 
    COALESCE(SUM(v.Total), 0) as ventas_reales,
    COUNT(v.Id) as transacciones_reales
    FROM metas_ventas m
    LEFT JOIN ventas v ON MONTH(v.Fecha_Venta) = m.mes AND YEAR(v.Fecha_Venta) = m.anio
    GROUP BY m.id
    ORDER BY m.anio DESC, m.mes DESC
    LIMIT 12");

$meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Metas de Ventas - Rey System</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
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
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 16px 24px;
    border-radius: 12px;
    display: none;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.notification.show { display: block; }
.notification.success { background: #10b981; color: white; }
</style>
</head>
<body class="bg-[#f6f7f8] dark:bg-[#101922] font-display">

<?php if ($mensaje): ?>
<div class="notification <?php echo $tipo_mensaje; ?> show">
    <?php echo $mensaje; ?>
</div>
<script>
setTimeout(() => {
    document.querySelector('.notification').classList.remove('show');
}, 5000);
</script>
<?php endif; ?>

<div class="flex min-h-screen w-full">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 p-8">
<div class="mx-auto max-w-7xl">

<div class="mb-8">
    <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">Metas de Ventas</h1>
    <p class="text-gray-500 dark:text-[#92a4c9] text-base mt-2">
        <?php if ($es_admin): ?>
            Establece y monitorea objetivos de ventas mensuales
        <?php else: ?>
            Monitorea el progreso de los objetivos de ventas mensuales
        <?php endif; ?>
    </p>
</div>

<!-- Progreso del mes actual -->
<div class="bg-gradient-to-br from-primary to-blue-600 rounded-xl p-8 text-white shadow-lg mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-black"><?php echo $meses[$mes_actual - 1]; ?> <?php echo $anio_actual; ?></h2>
            <p class="text-sm opacity-90">Progreso del mes actual</p>
        </div>
        <span class="material-symbols-outlined text-5xl opacity-80">trending_up</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Progreso de monto -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm opacity-90">Meta de Ventas</span>
                <span class="font-bold">L<?php echo number_format($total_ventas_mes, 2); ?> / L<?php echo number_format($meta_monto, 2); ?></span>
            </div>
            <div class="w-full bg-white/20 rounded-full h-4 overflow-hidden">
                <div class="bg-white h-full rounded-full transition-all duration-500" style="width: <?php echo min($progreso_monto, 100); ?>%"></div>
            </div>
            <p class="text-sm mt-2 opacity-90">
                <?php echo number_format($progreso_monto, 1); ?>% completado
                <?php if ($progreso_monto >= 100): ?>
                    <span class="ml-2 font-bold animate-pulse">🎉 ¡Meta alcanzada!</span>
                <?php endif; ?>
            </p>
        </div>

        <!-- Progreso de transacciones -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm opacity-90">Meta de Transacciones</span>
                <span class="font-bold"><?php echo $total_transacciones_mes; ?> / <?php echo $meta_transacciones; ?></span>
            </div>
            <div class="w-full bg-white/20 rounded-full h-4 overflow-hidden">
                <div class="bg-white h-full rounded-full transition-all duration-500" style="width: <?php echo min($progreso_transacciones, 100); ?>%"></div>
            </div>
            <p class="text-sm mt-2 opacity-90">
                <?php echo number_format($progreso_transacciones, 1); ?>% completado
                <?php if ($progreso_transacciones >= 100): ?>
                    <span class="ml-2 font-bold animate-pulse">🎉 ¡Meta alcanzada!</span>
                <?php endif; ?>
            </p>
        </div>
    </div>
</div>

<!-- Formulario para crear/editar meta -->
<?php if ($es_admin): ?>
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-2xl">flag</span>
        </div>
        <div>
            <h2 class="text-gray-900 dark:text-white text-xl font-bold">Establecer Meta</h2>
            <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Define objetivos para un mes específico</p>
        </div>
    </div>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Mes</label>
            <select name="mes" required class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
                <?php for($i = 1; $i <= 12; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo $i == $mes_actual ? 'selected' : ''; ?>><?php echo $meses[$i-1]; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Año</label>
            <input type="number" name="anio" value="<?php echo $anio_actual; ?>" required min="2020" max="2100"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Meta de Ventas (L)</label>
            <input type="number" name="meta_monto" step="0.01" required placeholder="50000.00"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Meta de Transacciones</label>
            <input type="number" name="meta_transacciones" required placeholder="100"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Descripción (opcional)</label>
            <textarea name="descripcion" rows="2" placeholder="Notas sobre esta meta..."
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white"></textarea>
        </div>
        <div class="md:col-span-2">
            <button type="submit" name="guardar_meta" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90">
                <span class="material-symbols-outlined">save</span>
                Guardar Meta
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Historial de metas -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
    <div class="p-6 border-b border-gray-200 dark:border-[#324467]">
        <h3 class="text-gray-900 dark:text-white text-lg font-bold">Historial de Metas</h3>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Últimos 12 meses</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-[#324467]">
            <thead class="bg-gray-50 dark:bg-[#111a22]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Período</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Meta Ventas</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Ventas Reales</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Meta Trans.</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Trans. Reales</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Cumplimiento</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-[#324467]">
                <?php if ($historial && $historial->num_rows > 0): ?>
                    <?php while($meta = $historial->fetch_assoc()): 
                        $cumplimiento = $meta['meta_monto'] > 0 ? ($meta['ventas_reales'] / $meta['meta_monto']) * 100 : 0;
                        $cumplido = $cumplimiento >= 100;
                    ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#1a2332]">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo $meses[$meta['mes'] - 1]; ?> <?php echo $meta['anio']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                L<?php echo number_format($meta['meta_monto'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                L<?php echo number_format($meta['ventas_reales'], 2); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo number_format($meta['meta_transacciones']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                <?php echo number_format($meta['transacciones_reales']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2 max-w-[100px]">
                                        <div class="<?php echo $cumplido ? 'bg-green-500' : 'bg-blue-500'; ?> h-full rounded-full" 
                                             style="width: <?php echo min($cumplimiento, 100); ?>%"></div>
                                    </div>
                                    <span class="<?php echo $cumplido ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400'; ?> font-bold">
                                        <?php echo number_format($cumplimiento, 1); ?>%
                                    </span>
                                    <?php if ($cumplido): ?>
                                        <span class="material-symbols-outlined text-green-500 text-sm">check_circle</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <span class="material-symbols-outlined text-gray-400 dark:text-[#92a4c9] text-4xl">flag</span>
                            <p class="text-gray-500 dark:text-[#92a4c9] mt-2">No hay metas registradas</p>
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

<script>
// Función para lanzar confeti
function lanzarConfeti() {
    const duracion = 3 * 1000;
    const animacionFin = Date.now() + duracion;
    const colores = ['#137fec', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

    (function frame() {
        confetti({
            particleCount: 3,
            angle: 60,
            spread: 55,
            origin: { x: 0 },
            colors: colores
        });
        confetti({
            particleCount: 3,
            angle: 120,
            spread: 55,
            origin: { x: 1 },
            colors: colores
        });

        if (Date.now() < animacionFin) {
            requestAnimationFrame(frame);
        }
    }());
}

// Verificar si alguna meta fue alcanzada y lanzar confeti
window.addEventListener('load', function() {
    const progresoMonto = <?php echo $progreso_monto; ?>;
    const progresoTransacciones = <?php echo $progreso_transacciones; ?>;
    
    if (progresoMonto >= 100 || progresoTransacciones >= 100) {
        // Esperar un momento para que la página cargue completamente
        setTimeout(function() {
            lanzarConfeti();
        }, 500);
    }
});
</script>

</body></html>
<?php
$conexion->close();
?>
