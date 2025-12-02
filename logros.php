<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'funciones.php';
include 'verificar_logros.php';
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

// ✅ SINCRONIZAR LOGROS AUTOMÁTICAMENTE AL CARGAR LA PÁGINA
if (file_exists('auto_sincronizar_logros.php')) {
    require_once 'auto_sincronizar_logros.php';
    try {
        autoSincronizarLogrosUsuario($Usuario);
    } catch (Exception $e) {
        error_log("Error al sincronizar logros en logros.php: " . $e->getMessage());
    }
}

// Obtener todos los logros
$logros_query = "SELECT l.*, 
    COALESCE(ul.progreso_actual, 0) as progreso_actual,
    COALESCE(ul.completado, 0) as completado,
    ul.fecha_desbloqueo
    FROM logros l
    LEFT JOIN usuarios_logros ul ON l.id = ul.logro_id AND ul.usuario = ?
    WHERE l.activo = 1
    ORDER BY ul.completado DESC, l.puntos ASC";

$stmt = $conexion->prepare($logros_query);
$stmt->bind_param("s", $Usuario);
$stmt->execute();
$logros = $stmt->get_result();

// Calcular estadísticas
$stats_query = "SELECT 
    COUNT(*) as total_logros,
    SUM(CASE WHEN ul.completado = 1 THEN 1 ELSE 0 END) as completados,
    SUM(CASE WHEN ul.completado = 1 THEN l.puntos ELSE 0 END) as puntos_totales
    FROM logros l
    LEFT JOIN usuarios_logros ul ON l.id = ul.logro_id AND ul.usuario = ?
    WHERE l.activo = 1";

$stmt_stats = $conexion->prepare($stats_query);
$stmt_stats->bind_param("s", $Usuario);
$stmt_stats->execute();
$stats = $stmt_stats->get_result()->fetch_assoc();

$total_logros = $stats['total_logros'];
$completados = $stats['completados'] ?? 0;
$puntos_totales = $stats['puntos_totales'] ?? 0;
$porcentaje_completado = $total_logros > 0 ? ($completados / $total_logros) * 100 : 0;

?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Logros - Rey System</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: { "primary": "#1152d4" },
            fontFamily: { "display": ["Inter", "sans-serif"] }
        }
    }
}
</script>
<style>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
.logro-completado {
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%);
    border-color: #f59e0b;
}
.logro-bloqueado {
    opacity: 0.6;
    filter: grayscale(0.5);
}
@keyframes shine {
    0% { background-position: -200% center; }
    100% { background-position: 200% center; }
}
.shine-effect {
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    background-size: 200% 100%;
    animation: shine 2s infinite;
}
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.animate-shimmer {
    animation: shimmer 2s infinite;
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
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">Logros</h1>
            <p class="text-gray-500 dark:text-[#92a4c9] text-base mt-2">Completa desafíos y desbloquea recompensas</p>
        </div>
        <?php if ($rol_usuario === 'admin'): ?>
        <a href="gestionar_logros.php" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90">
            <span class="material-symbols-outlined">settings</span>
            Gestionar Logros
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- Estadísticas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-primary text-2xl">emoji_events</span>
            </div>
            <div>
                <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Logros Completados</p>
                <p class="text-gray-900 dark:text-white text-2xl font-black"><?php echo $completados; ?> / <?php echo $total_logros; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-yellow-500/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-yellow-500 text-2xl">stars</span>
            </div>
            <div>
                <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Puntos Totales</p>
                <p class="text-gray-900 dark:text-white text-2xl font-black"><?php echo number_format($puntos_totales); ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-green-500/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-green-500 text-2xl">trending_up</span>
            </div>
            <div>
                <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Progreso General</p>
                <p class="text-gray-900 dark:text-white text-2xl font-black"><?php echo number_format($porcentaje_completado, 1); ?>%</p>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="flex gap-2 mb-6">
    <button onclick="filtrarLogros('todos')" class="filtro-btn active px-4 py-2 rounded-lg font-semibold transition-all" data-filtro="todos">
        Todos
    </button>
    <button onclick="filtrarLogros('completados')" class="filtro-btn px-4 py-2 rounded-lg font-semibold transition-all" data-filtro="completados">
        Completados
    </button>
    <button onclick="filtrarLogros('pendientes')" class="filtro-btn px-4 py-2 rounded-lg font-semibold transition-all" data-filtro="pendientes">
        Pendientes
    </button>
</div>

<!-- Grid de Logros -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php while($logro = $logros->fetch_assoc()): 
        $es_completado = $logro['completado'] == 1;
        $progreso_porcentaje = ($logro['progreso_actual'] / $logro['valor_objetivo']) * 100;
    ?>
    <div class="logro-card <?php echo $es_completado ? 'logro-completado' : 'logro-bloqueado'; ?> bg-white dark:bg-[#192233] rounded-xl border-2 border-gray-200 dark:border-[#324467] p-6 transition-all hover:shadow-lg" 
         data-estado="<?php echo $es_completado ? 'completado' : 'pendiente'; ?>">
        
        <!-- Icono -->
        <div class="flex items-start justify-between mb-4">
            <div class="w-16 h-16 rounded-full flex items-center justify-center <?php echo $es_completado ? 'shine-effect' : ''; ?>" 
                 style="background-color: <?php echo $logro['color']; ?>20;">
                <span class="material-symbols-outlined text-4xl" style="color: <?php echo $logro['color']; ?>">
                    <?php echo $logro['icono']; ?>
                </span>
            </div>
            <?php if ($es_completado): ?>
            <span class="material-symbols-outlined text-yellow-500 text-2xl">check_circle</span>
            <?php endif; ?>
        </div>
        
        <!-- Información -->
        <h3 class="text-gray-900 dark:text-white text-lg font-bold mb-2"><?php echo $logro['nombre']; ?></h3>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm mb-4"><?php echo $logro['descripcion']; ?></p>
        
        <!-- Progreso Mejorado -->
        <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800/50 dark:to-slate-900/50 rounded-xl p-4 mb-3" data-logro-id="<?php echo $logro['id']; ?>">
            <!-- Números grandes -->
            <div class="flex items-baseline justify-center gap-2 mb-3">
                <span class="progreso-actual text-4xl font-black" style="color: <?php echo $logro['color']; ?>">
                    <?php echo $logro['progreso_actual']; ?>
                </span>
                <span class="text-2xl font-semibold text-gray-400 dark:text-gray-600">/</span>
                <span class="progreso-objetivo text-2xl font-bold text-gray-500 dark:text-gray-400">
                    <?php echo $logro['valor_objetivo']; ?>
                </span>
                <span class="progreso-unidad text-sm font-medium text-gray-400 dark:text-gray-500">
                    <?php 
                    $unidad = '';
                    switch ($logro['tipo_condicion']) {
                        case 'ventas_count': $unidad = $logro['progreso_actual'] == 1 ? 'venta' : 'ventas'; break;
                        case 'aperturas_count': $unidad = $logro['progreso_actual'] == 1 ? 'apertura' : 'aperturas'; break;
                        case 'arqueos_sin_error': $unidad = 'arqueos'; break;
                        case 'clientes_count': $unidad = $logro['progreso_actual'] == 1 ? 'cliente' : 'clientes'; break;
                        case 'dias_consecutivos': $unidad = 'días'; break;
                        case 'meta_alcanzada': $unidad = $logro['progreso_actual'] == 1 ? 'meta' : 'metas'; break;
                        default: $unidad = '';
                    }
                    echo $unidad;
                    ?>
                </span>
            </div>
            
            <!-- Barra de progreso -->
            <div class="relative">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden shadow-inner">
                    <div class="progreso-barra h-full rounded-full transition-all duration-700 ease-out relative overflow-hidden" 
                         style="width: <?php echo min($progreso_porcentaje, 100); ?>%; background: linear-gradient(90deg, <?php echo $logro['color']; ?>, <?php echo $logro['color']; ?>dd)">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                    </div>
                </div>
                <!-- Porcentaje -->
                <div class="progreso-porcentaje text-center mt-2">
                    <span class="text-lg font-bold" style="color: <?php echo $logro['color']; ?>">
                        <?php echo number_format($progreso_porcentaje, 1); ?>%
                    </span>
                </div>
            </div>
            
            <!-- Badge "Casi listo" -->
            <?php if ($progreso_porcentaje > 80 && !$es_completado): ?>
            <div class="mt-3 flex items-center justify-center">
                <span class="badge-casi-listo inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-amber-400 to-orange-500 text-white text-xs font-bold rounded-full shadow-lg animate-pulse">
                    <span class="material-symbols-outlined text-sm">bolt</span>
                    ¡Casi lo logras!
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Puntos -->
        <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-[#324467]">
            <span class="text-gray-500 dark:text-[#92a4c9] text-sm">Puntos</span>
            <span class="text-yellow-500 font-bold flex items-center gap-1">
                <span class="material-symbols-outlined text-sm">stars</span>
                <?php echo $logro['puntos']; ?>
            </span>
        </div>
        
        <?php if ($es_completado && $logro['fecha_desbloqueo']): ?>
        <p class="text-green-500 text-xs mt-2 flex items-center gap-1">
            <span class="material-symbols-outlined text-sm">check</span>
            Desbloqueado el <?php echo date('d/m/Y', strtotime($logro['fecha_desbloqueo'])); ?>
        </p>
        <?php endif; ?>
    </div>
    <?php endwhile; ?>
</div>

</div>
</main>
</div>

<!-- Modal de Logro Desbloqueado -->
<div id="modalLogro" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#192233] rounded-2xl max-w-md w-full p-8 text-center relative">
        <div id="logroIcono" class="w-24 h-24 rounded-full mx-auto mb-4 flex items-center justify-center shine-effect">
            <span id="logroIconoSymbol" class="material-symbols-outlined text-5xl"></span>
        </div>
        <h2 class="text-gray-900 dark:text-white text-2xl font-black mb-2">¡Logro Desbloqueado!</h2>
        <h3 id="logroNombre" class="text-primary text-xl font-bold mb-2"></h3>
        <p id="logroDescripcion" class="text-gray-500 dark:text-[#92a4c9] mb-4"></p>
        <div class="flex items-center justify-center gap-2 text-yellow-500 font-bold text-lg mb-6">
            <span class="material-symbols-outlined">stars</span>
            <span id="logroPuntos"></span> Puntos
        </div>
        <button onclick="cerrarModal()" class="w-full px-6 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90">
            ¡Genial!
        </button>
    </div>
</div>

<audio id="achievementSound" src="sounds/achievement_unlock.mp3" preload="auto"></audio>

<script>
// Filtrado de logros
function filtrarLogros(filtro) {
    const cards = document.querySelectorAll('.logro-card');
    const buttons = document.querySelectorAll('.filtro-btn');
    
    buttons.forEach(btn => {
        btn.classList.remove('active', 'bg-primary', 'text-white');
        btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
    });
    
    const activeBtn = document.querySelector(`[data-filtro="${filtro}"]`);
    activeBtn.classList.add('active', 'bg-primary', 'text-white');
    activeBtn.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
    
    cards.forEach(card => {
        const estado = card.dataset.estado;
        if (filtro === 'todos' || estado === filtro) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Inicializar filtros
document.addEventListener('DOMContentLoaded', function() {
    filtrarLogros('todos');
});

// Mostrar modal de logro desbloqueado
function mostrarLogroDesbloqueado(logro) {
    const modal = document.getElementById('modalLogro');
    const icono = document.getElementById('logroIcono');
    const iconoSymbol = document.getElementById('logroIconoSymbol');
    const nombre = document.getElementById('logroNombre');
    const descripcion = document.getElementById('logroDescripcion');
    const puntos = document.getElementById('logroPuntos');
    
    icono.style.backgroundColor = logro.color + '20';
    iconoSymbol.style.color = logro.color;
    iconoSymbol.textContent = logro.icono;
    nombre.textContent = logro.nombre;
    descripcion.textContent = logro.descripcion;
    puntos.textContent = logro.puntos;
    
    modal.classList.remove('hidden');
    
    // Confeti
    lanzarConfeti();
    
    // Sonido
    const sound = document.getElementById('achievementSound');
    sound.play().catch(e => console.log('No se pudo reproducir el sonido:', e));
}

function cerrarModal() {
    document.getElementById('modalLogro').classList.add('hidden');
}

function lanzarConfeti() {
    const duracion = 3 * 1000;
    const animacionFin = Date.now() + duracion;
    const colores = ['#fbbf24', '#f59e0b', '#1152d4', '#10b981', '#ef4444'];

    (function frame() {
        confetti({
            particleCount: 5,
            angle: 60,
            spread: 55,
            origin: { x: 0 },
            colors: colores
        });
        confetti({
            particleCount: 5,
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

// Verificar logros recién desbloqueados (se puede llamar desde otros archivos)
function verificarNuevosLogros() {
    fetch('api_logros.php?action=verificar_nuevos&usuario=<?php echo $Usuario; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.logros && data.logros.length > 0) {
                data.logros.forEach((logro, index) => {
                    setTimeout(() => {
                        mostrarLogroDesbloqueado(logro);
                    }, index * 4000);
                });
            }
        });
}

// ===== ACTUALIZACIÓN AUTOMÁTICA DE PROGRESO =====
function actualizarProgreso() {
    fetch('api_progreso_logros.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.logros) {
                data.logros.forEach(logro => {
                    actualizarCardLogro(logro);
                });
            }
        })
        .catch(error => console.error('Error al actualizar progreso:', error));
}

function actualizarCardLogro(logro) {
    const card = document.querySelector(`[data-logro-id="${logro.id}"]`);
    if (!card) return;
    
    // Actualizar número actual
    const actualElement = card.querySelector('.progreso-actual');
    if (actualElement && actualElement.textContent != logro.progreso_actual) {
        actualElement.textContent = logro.progreso_actual;
        // Animación de actualización
        actualElement.style.transform = 'scale(1.2)';
        setTimeout(() => {
            actualElement.style.transform = 'scale(1)';
        }, 300);
    }
    
    // Actualizar barra de progreso
    const barra = card.querySelector('.progreso-barra');
    if (barra) {
        barra.style.width = logro.porcentaje + '%';
    }
    
    // Actualizar porcentaje
    const porcentajeElement = card.querySelector('.progreso-porcentaje span');
    if (porcentajeElement) {
        porcentajeElement.textContent = logro.porcentaje + '%';
    }
    
    // Actualizar unidad
    const unidadElement = card.querySelector('.progreso-unidad');
    if (unidadElement) {
        unidadElement.textContent = logro.unidad;
    }
    
    // Mostrar/ocultar badge "casi listo"
    const parentCard = card.closest('.logro-card');
    let badge = card.querySelector('.badge-casi-listo');
    
    if (logro.casi_listo && !badge) {
        // Crear badge si no existe
        const badgeContainer = document.createElement('div');
        badgeContainer.className = 'mt-3 flex items-center justify-center';
        badgeContainer.innerHTML = `
            <span class="badge-casi-listo inline-flex items-center gap-1 px-3 py-1 bg-gradient-to-r from-amber-400 to-orange-500 text-white text-xs font-bold rounded-full shadow-lg animate-pulse">
                <span class="material-symbols-outlined text-sm">bolt</span>
                ¡Casi lo logras!
            </span>
        `;
        card.appendChild(badgeContainer);
    } else if (!logro.casi_listo && badge) {
        // Remover badge si ya no aplica
        badge.closest('div').remove();
    }
}

// Iniciar actualización automática cada 30 segundos
setInterval(actualizarProgreso, 30000);

// También actualizar cuando la página vuelve a estar visible
document.addEventListener('visibilitychange', () => {
    if (!document.hidden) {
        actualizarProgreso();
    }
});
</script>

</body></html>
<?php
$conexion->close();
?>
