<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'funciones.php';

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
    $Nombre_Completo = $row['Nombre']." ".$row['Apellido'];
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);

// Obtener productos del inventario con sus precios
$query = "SELECT Codigo_Producto, Nombre_Producto, Precio_Unitario, Stock, Grupo 
          FROM stock 
          WHERE Stock > 0 
          ORDER BY Nombre_Producto ASC 
          LIMIT 900";
$productos = $conexion->query($query);

// Obtener estadísticas
$statsQuery = "SELECT 
    COUNT(DISTINCT pc.codigo_producto) as productos_comparados,
    COUNT(DISTINCT pc.fuente) as fuentes_activas,
    AVG(CASE WHEN s.Precio_Unitario > pc.precio_competencia THEN 1 ELSE 0 END) * 100 as porcentaje_mas_caro
FROM precios_competencia pc
LEFT JOIN stock s ON pc.codigo_producto = s.Codigo_Producto
WHERE pc.fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
$statsResult = $conexion->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Comparador de Precios - Rey System</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#1152d4",
                    "background-light": "#f6f7f8",
                    "background-dark": "#101622",
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
    
    @keyframes bounce-in {
        0% {
            transform: scale(0.3);
            opacity: 0;
        }
        50% {
            transform: scale(1.05);
        }
        70% {
            transform: scale(0.9);
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }
    
    .animate-bounce-in {
        animation: bounce-in 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="flex min-h-screen w-full">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 p-8">
<div class="mx-auto max-w-7xl">

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">Comparador de Precios</h1>
            <p class="text-gray-500 dark:text-[#92a4c9] text-base mt-2">Analiza precios de la competencia y optimiza tu estrategia</p>
        </div>
    </div>
    
    <!-- Panel de Control de Scraping -->
    <div class="bg-white dark:bg-[#1a2332] rounded-xl shadow-sm border border-gray-200 dark:border-[#324467] p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">🔍 Control de Actualización de Precios</h2>
        
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">
            
            <!-- Selector de Tipo de Búsqueda -->
            <div class="lg:col-span-7">
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Buscar Por</label>
                <select id="tipoBusqueda" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
                    <option value="codigo">🔢 Código de Barras</option>
                    <option value="nombre">📦 Nombre de Producto</option>
                    <option value="descripcion">📝 Descripción del Producto</option>
                    <option value="categoria">🏷️ Categoría + Nombre</option>
                </select>
            </div>

            <!-- Selector de Método -->
            <div class="lg:col-span-5">
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Método de Búsqueda</label>
                <select id="metodoScraping" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
                    <optgroup label="🐍 Métodos con Python + Mistral AI">
                        <option value="python_selenium">🚀 Python Selenium (JS Pesado + BeautifulSoup)</option>
                        <option value="python_async">⚡ Python Async (Rápido + Paralelo)</option>
                        <option value="python_smart">🧠 Python Smart (Multi-Técnica + IA)</option>
                    </optgroup>
                    <optgroup label="🤖 Métodos con IA de Mistral">
                        <option value="mistral_chat">💬 Mistral Chat AI (Large - Búsqueda Inteligente)</option>
                        <option value="mistral_ocr">📸 Mistral OCR (Pixtral - Screenshot + IA)</option>
                    </optgroup>
                    <optgroup label="✅ Métodos Probados (Recomendados)">
                        <option value="meta_tags">🏷️ Meta Tags (Probado - Alta Precisión)</option>
                        <option value="microdata">📊 Microdata Schema (Probado - Alta Precisión)</option>
                        <option value="xpath_dom">🎯 XPath DOM (Probado - Muy Preciso)</option>
                        <option value="google_shopping">🛒 Google Shopping (Probado)</option>
                        <option value="json_ld">📋 JSON-LD Schema (Probado)</option>
                        <option value="api_rest">🔌 API REST (Probado)</option>
                        <option value="user_agent_rotation">🔄 User-Agent Rotation (Probado)</option>
                    </optgroup>
                    <optgroup label="🔥 Métodos Avanzados">
                        <option value="scraping_real_php">🔥 Scraping Real PHP</option>
                        <option value="deepseek_ai">🧠 DeepSeek AI</option>
                        <option value="google_ai">🔍 Google + IA</option>
                        <option value="ai_search">🤖 IA Inteligente</option>
                        <option value="scraping_directo">🌐 Scraping Directo</option>
                    </optgroup>
                    <optgroup label="⚡ Combinados">
                        <option value="hibrido">⚡ Híbrido (Todos los Métodos - Máxima Cobertura)</option>
                    </optgroup>
                </select>
            </div>
            
            <!-- Tamaño de Lote -->
            <div class="lg:col-span-3">
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Productos por Lote</label>
                <select id="tamanoLote" class="w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
                    <option value="5">5 productos</option>
                    <option value="10" selected>10 productos</option>
                    <option value="20">20 productos</option>
                    <option value="50">50 productos</option>
                    <option value="100">100 productos</option>
                </select>
            </div>
            
            <!-- Botones de Acción -->
            <div class="lg:col-span-4 flex gap-2">
                <button onclick="iniciarActualizacionMasiva()" class="flex-1 flex items-center justify-center gap-2 px-6 py-2.5 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-colors">
                    <span class="material-symbols-outlined text-lg">play_arrow</span>
                    <span class="hidden sm:inline">Actualizar Todos</span>
                    <span class="sm:hidden">Actualizar</span>
                </button>
                <button onclick="detenerActualizacion()" id="btnDetener" class="hidden flex-1 flex items-center justify-center gap-2 px-6 py-2.5 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-colors">
                    <span class="material-symbols-outlined text-lg">stop</span>
                    <span class="hidden sm:inline">Detener</span>
                </button>
            </div>
        </div>
        
        <!-- Descripción del método -->
        <p class="text-xs text-gray-500 dark:text-[#92a4c9] mt-3">
            <span class="material-symbols-outlined text-xs align-middle">info</span>
            La IA busca automáticamente en sitios de Honduras con moneda Lempiras (L)
        </p>
        
        <!-- Barra de Progreso -->
        <div id="progresoContainer" class="hidden mt-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-900 dark:text-white">Progreso</span>
                <span id="progresoTexto" class="text-sm text-gray-500 dark:text-[#92a4c9]">0%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div id="barraProgreso" class="bg-primary h-3 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <p id="estadoActualizacion" class="text-xs text-gray-500 dark:text-[#92a4c9] mt-2">Preparando...</p>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-500 text-2xl">inventory</span>
            </div>
            <div>
                <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Productos Comparados</p>
                <p class="text-gray-900 dark:text-white text-2xl font-bold" data-stat="productos_comparados"><?php echo $stats['productos_comparados'] ?? 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-green-500/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-green-500 text-2xl">store</span>
            </div>
            <div>
                <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Fuentes Activas</p>
                <p class="text-gray-900 dark:text-white text-2xl font-bold" data-stat="fuentes_activas"><?php echo $stats['fuentes_activas'] ?? 0; ?></p>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-yellow-500/10 flex items-center justify-center">
                <span class="material-symbols-outlined text-yellow-500 text-2xl">trending_up</span>
            </div>
            <div>
                <p class="text-gray-500 dark:text-[#92a4c9] text-sm">% Más Caro</p>
                <p class="text-gray-900 dark:text-white text-2xl font-bold" data-stat="porcentaje_mas_caro"><?php echo number_format($stats['porcentaje_mas_caro'] ?? 0, 1); ?>%</p>
            </div>
        </div>
    </div>
</div>

<!-- Filtros y Búsqueda -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6">
    <div class="flex flex-wrap gap-4">
        <input type="text" id="buscarProducto" placeholder="Buscar producto..." 
               class="flex-1 px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white"
               onkeyup="filtrarProductos()">
        <select id="filtroCategoria" class="px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white"
                onchange="filtrarProductos()">
            <option value="">Todas las categorías</option>
            <option value="Bebidas">Bebidas</option>
            <option value="Abarrotes">Abarrotes</option>
            <option value="Limpieza">Limpieza</option>
            <option value="Lacteos">Lácteos</option>
        </select>
        <select id="filtroEstado" class="px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white"
                onchange="filtrarProductos()">
            <option value="">Todos</option>
            <option value="mas-caro">Más caro</option>
            <option value="mas-barato">Más barato</option>
            <option value="igual">Precio similar</option>
        </select>
    </div>
</div>

<!-- Lista de Productos -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
    <div class="p-6 border-b border-gray-200 dark:border-[#324467]">
        <h3 class="text-gray-900 dark:text-white text-lg font-bold">Comparación de Productos</h3>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Haz clic en un producto para ver detalles</p>
    </div>
    
    <div id="listaProductos" class="divide-y divide-gray-200 dark:divide-[#324467]">
        <?php while($producto = $productos->fetch_assoc()): 
            // Obtener precios de competencia
            $codigo = $producto['Codigo_Producto'];
            $precioPropio = floatval($producto['Precio_Unitario']);
            
            $queryComp = "SELECT fuente, precio_competencia, url_producto 
                         FROM precios_competencia 
                         WHERE codigo_producto = '$codigo' 
                         AND fecha_actualizacion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                         ORDER BY precio_competencia ASC";
            $preciosComp = $conexion->query($queryComp);
            
            $competidores = [];
            $totalComp = 0;
            $countComp = 0;
            
            while($comp = $preciosComp->fetch_assoc()) {
                $competidores[] = $comp;
                $totalComp += floatval($comp['precio_competencia']);
                $countComp++;
            }
            
            $promedioComp = $countComp > 0 ? $totalComp / $countComp : 0;
            $diferencia = $promedioComp > 0 ? $precioPropio - $promedioComp : 0;
            $porcentajeDif = $promedioComp > 0 ? ($diferencia / $promedioComp) * 100 : 0;
            
            $colorClass = '';
            $iconoEstado = '';
            if ($porcentajeDif > 5) {
                $colorClass = 'text-red-500';
                $iconoEstado = 'trending_up';
            } elseif ($porcentajeDif < -5) {
                $colorClass = 'text-green-500';
                $iconoEstado = 'trending_down';
            } else {
                $colorClass = 'text-yellow-500';
                $iconoEstado = 'trending_flat';
            }
        ?>
        <div class="p-6 hover:bg-gray-50 dark:hover:bg-[#1a2332] transition-colors cursor-pointer producto-item" 
             data-codigo="<?php echo $codigo; ?>"
             data-categoria="<?php echo $producto['Grupo']; ?>"
             data-diferencia="<?php echo $porcentajeDif; ?>"
             onclick="verDetalle('<?php echo $codigo; ?>')">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <h4 class="text-gray-900 dark:text-white font-semibold mb-1"><?php echo htmlspecialchars($producto['Nombre_Producto']); ?></h4>
                    <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Código: <?php echo $codigo; ?></p>
                </div>
                
                <div class="flex items-center gap-6">
                    <div class="text-right">
                        <p class="text-gray-500 dark:text-[#92a4c9] text-xs">Mi Precio</p>
                        <p class="text-gray-900 dark:text-white text-lg font-bold">L <?php echo number_format($precioPropio, 2); ?></p>
                    </div>
                    
                    <?php if ($countComp > 0): ?>
                    <div class="text-right">
                        <p class="text-gray-500 dark:text-[#92a4c9] text-xs">Promedio Competencia</p>
                        <p class="text-gray-900 dark:text-white text-lg font-bold" data-precio-promedio>L <?php echo number_format($promedioComp, 2); ?></p>
                    </div>
                    
                    <div class="text-right">
                        <p class="text-gray-500 dark:text-[#92a4c9] text-xs">Diferencia</p>
                        <div class="flex items-center gap-1 <?php echo $colorClass; ?>" data-diferencia-display>
                            <span class="material-symbols-outlined text-sm" data-icono-estado><?php echo $iconoEstado; ?></span>
                            <span class="font-bold"><?php echo $porcentajeDif > 0 ? '+' : ''; ?><?php echo number_format($porcentajeDif, 1); ?>%</span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="text-right">
                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs">
                            <span class="material-symbols-outlined text-sm">info</span>
                            Sin datos
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <button onclick="event.stopPropagation(); buscarEnCompetencia('<?php echo $codigo; ?>')" 
                            class="px-4 py-2 bg-primary/10 text-primary rounded-lg text-sm font-semibold hover:bg-primary/20 transition-colors">
                        <span class="material-symbols-outlined text-sm">search</span>
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

</div>
</main>
</div>

<!-- Modal de Notificación -->
<div id="modalNotificacion" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" onclick="if(event.target === this) cerrarNotificacion()">
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 max-w-md w-full mx-4 animate-bounce-in">
        <div class="flex items-start gap-4">
            <div id="iconoNotificacion" class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl"></span>
            </div>
            <div class="flex-1">
                <h3 id="tituloNotificacion" class="text-lg font-bold text-gray-900 dark:text-white mb-2"></h3>
                <p id="mensajeNotificacion" class="text-sm text-gray-600 dark:text-gray-300"></p>
            </div>
            <button onclick="cerrarNotificacion()" class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="mt-4 flex justify-end gap-2">
            <button id="btnCancelarNotif" onclick="cerrarNotificacion()" class="hidden px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white font-semibold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                Cancelar
            </button>
            <button id="btnAceptarNotif" onclick="cerrarNotificacion()" class="px-4 py-2 rounded-lg bg-primary text-white font-semibold hover:bg-primary/90 transition-colors">
                Aceptar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Detalle -->
<div id="modalDetalle" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" onclick="if(event.target === this) cerrarModal()">
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-gray-900 dark:text-white text-2xl font-bold">Detalle de Comparación</h2>
            <button onclick="cerrarModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="contenidoDetalle"></div>
    </div>
</div>

<script>
// ========================================
// Sistema de Notificaciones
// ========================================
let callbackNotificacion = null;

function mostrarNotificacion(tipo, titulo, mensaje, callback = null) {
    const modal = document.getElementById('modalNotificacion');
    const icono = document.getElementById('iconoNotificacion');
    const iconoSpan = icono.querySelector('.material-symbols-outlined');
    const tituloEl = document.getElementById('tituloNotificacion');
    const mensajeEl = document.getElementById('mensajeNotificacion');
    
    // Configurar según tipo
    const configs = {
        success: {
            bg: 'bg-green-500/10',
            text: 'text-green-500',
            icono: 'check_circle'
        },
        error: {
            bg: 'bg-red-500/10',
            text: 'text-red-500',
            icono: 'error'
        },
        warning: {
            bg: 'bg-yellow-500/10',
            text: 'text-yellow-500',
            icono: 'warning'
        },
        info: {
            bg: 'bg-blue-500/10',
            text: 'text-blue-500',
            icono: 'info'
        },
        loading: {
            bg: 'bg-primary/10',
            text: 'text-primary',
            icono: 'sync'
        }
    };
    
    const config = configs[tipo] || configs.info;
    
    // Aplicar estilos
    icono.className = `flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center ${config.bg}`;
    iconoSpan.className = `material-symbols-outlined text-2xl ${config.text}`;
    iconoSpan.textContent = config.icono;
    
    tituloEl.textContent = titulo;
    mensajeEl.textContent = mensaje;
    
    // Guardar callback
    callbackNotificacion = callback;
    
    // Mostrar modal
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function mostrarConfirmacion(titulo, mensaje, onConfirm) {
    mostrarNotificacion('warning', titulo, mensaje, onConfirm);
    document.getElementById('btnCancelarNotif').classList.remove('hidden');
    document.getElementById('btnAceptarNotif').onclick = () => {
        cerrarNotificacion();
        if (onConfirm) onConfirm();
    };
}

function cerrarNotificacion() {
    const modal = document.getElementById('modalNotificacion');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('btnCancelarNotif').classList.add('hidden');
    document.getElementById('btnAceptarNotif').onclick = cerrarNotificacion;
    callbackNotificacion = null;
}

// ========================================
// Funciones de Filtrado y Búsqueda
// ========================================
function filtrarProductos() {
    const busqueda = document.getElementById('buscarProducto').value.toLowerCase();
    const categoria = document.getElementById('filtroCategoria').value;
    const estado = document.getElementById('filtroEstado').value;
    
    document.querySelectorAll('.producto-item').forEach(item => {
        const texto = item.textContent.toLowerCase();
        const catItem = item.dataset.categoria;
        const dif = parseFloat(item.dataset.diferencia);
        
        let mostrar = true;
        
        if (busqueda && !texto.includes(busqueda)) mostrar = false;
        if (categoria && catItem !== categoria) mostrar = false;
        if (estado === 'mas-caro' && dif <= 5) mostrar = false;
        if (estado === 'mas-barato' && dif >= -5) mostrar = false;
        if (estado === 'igual' && (dif > 5 || dif < -5)) mostrar = false;
        
        item.style.display = mostrar ? 'block' : 'none';
    });
}

function verDetalle(codigo) {
    fetch(`api/comparador_detalle.php?codigo=${codigo}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarDetalle(data);
            }
        });
}

function mostrarDetalle(data) {
    const modal = document.getElementById('modalDetalle');
    const contenido = document.getElementById('contenidoDetalle');
    
    let html = `
        <div class="mb-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">${data.producto.nombre}</h3>
            <p class="text-gray-500 dark:text-[#92a4c9]">Código: ${data.producto.codigo}</p>
        </div>
        
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Mi Precio</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">L ${data.producto.precio}</p>
            </div>
            <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Promedio Competencia</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">L ${data.promedio}</p>
            </div>
        </div>
        
        <h4 class="font-bold text-gray-900 dark:text-white mb-4">Precios por Fuente</h4>
        <div class="space-y-3">
    `;
    
    data.competidores.forEach(comp => {
        html += `
            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-[#111a22] rounded-lg">
                <div>
                    <p class="font-semibold text-gray-900 dark:text-white">${comp.fuente}</p>
                    <a href="${comp.url}" target="_blank" class="text-xs text-primary hover:underline">Ver en sitio</a>
                </div>
                <p class="text-lg font-bold text-gray-900 dark:text-white">L ${comp.precio}</p>
            </div>
        `;
    });
    
    html += '</div>';
    contenido.innerHTML = html;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function cerrarModal() {
    document.getElementById('modalDetalle').classList.add('hidden');
    document.getElementById('modalDetalle').classList.remove('flex');
}

function buscarEnCompetencia(codigo) {
    mostrarNotificacion('loading', 'Buscando Precios', `Iniciando búsqueda en sitios de competencia para código: ${codigo}`);
    fetch(`api/scraper_competencia.php?codigo=${codigo}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                mostrarNotificacion('success', 'Búsqueda Completada', `Se encontraron ${data.resultados} coincidencias.`);
                setTimeout(() => location.reload(), 2000);
            } else {
                mostrarNotificacion('error', 'Error en Búsqueda', data.message);
            }
        });
}

// Variables globales para control de actualización masiva
let actualizacionEnCurso = false;
let offsetActual = 0;

function iniciarActualizacionMasiva() {
    if (actualizacionEnCurso) {
        mostrarNotificacion('warning', 'Actualización en Curso', 'Ya hay una actualización en curso. Por favor espera a que termine.');
        return;
    }
    
    const metodo = document.getElementById('metodoScraping').value;
    const lote = document.getElementById('tamanoLote').value;
    
    mostrarConfirmacion(
        'Actualización Masiva',
        `¿Iniciar actualización masiva con método "${metodo}"?\n\nEsto puede tardar varios minutos dependiendo del número de productos.`,
        () => {
            actualizacionEnCurso = true;
            offsetActual = 0;
            
            // Mostrar controles
            document.getElementById('progresoContainer').classList.remove('hidden');
            document.getElementById('btnDetener').classList.remove('hidden');
            
            // Iniciar proceso
            procesarLote(metodo, lote);
        }
    );
}

function procesarLote(metodo, lote) {
    if (!actualizacionEnCurso) {
        return;
    }
    
    const tipoBusqueda = document.getElementById('tipoBusqueda').value;
    const tiempoInicio = Date.now();
    
    // Mostrar estado inicial
    document.getElementById('estadoActualizacion').innerHTML = `
        <div class="flex items-center gap-2">
            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>🔍 Procesando productos ${offsetActual + 1} a ${offsetActual + parseInt(lote)}... (Buscando por: ${tipoBusqueda})</span>
        </div>
    `;
    
    fetch(`api/scraper_lotes.php?metodo=${metodo}&lote=${lote}&offset=${offsetActual}&tipo_busqueda=${tipoBusqueda}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const tiempoTranscurrido = ((Date.now() - tiempoInicio) / 1000).toFixed(1);
                
                // Actualizar barra de progreso con animación suave
                const barraProgreso = document.getElementById('barraProgreso');
                barraProgreso.style.transition = 'width 0.5s ease-in-out';
                barraProgreso.style.width = data.progreso + '%';
                
                // Actualizar texto de progreso
                document.getElementById('progresoTexto').textContent = Math.round(data.progreso) + '%';
                
                // Mostrar estadísticas detalladas
                document.getElementById('estadoActualizacion').innerHTML = `
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold">✅ Procesados: ${data.procesados}</span>
                            <span class="text-green-600 font-semibold">🎯 Exitosos: ${data.exitosos}</span>
                            <span class="text-blue-600">⏱️ ${tiempoTranscurrido}s</span>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            Método: ${metodo} | Tipo: ${tipoBusqueda} | Progreso: ${Math.round(data.progreso)}%
                        </div>
                    </div>
                `;
                
                // ACTUALIZAR TABLA EN TIEMPO REAL si hay productos exitosos
                if (data.exitosos > 0) {
                    actualizarListaProductosRapido();
                    
                    // Mostrar notificación de éxito
                    mostrarNotificacionTemporal(`✅ ${data.exitosos} productos actualizados`, 'success');
                }
                
                offsetActual = data.offset;
                
                // Continuar si hay más productos
                if (data.hayMas && actualizacionEnCurso) {
                    // Delay más corto para Python (500ms) vs otros métodos (1500ms)
                    const delay = metodo.includes('python') ? 500 : 1500;
                    setTimeout(() => procesarLote(metodo, lote), delay);
                } else {
                    finalizarActualizacion(data);
                }
            } else {
                mostrarNotificacion('error', 'Error en Actualización', data.message || 'Error desconocido');
                detenerActualizacion();
            }
        })
        .catch(err => {
            console.error(err);
            mostrarNotificacion('error', 'Error de Conexión', 'Error de conexión durante la actualización.');
            detenerActualizacion();
        });
}

// Función mejorada para actualizar lista de productos más rápido
function actualizarListaProductosRapido() {
    // Recargar solo la tabla sin toda la página
    const tabla = document.querySelector('#tablaProductos tbody');
    if (!tabla) return;
    
    fetch('api/obtener_productos_comparados.php?limit=50')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.productos) {
                // Actualizar productos en la tabla
                data.productos.forEach(producto => {
                    const fila = document.querySelector(`tr[data-codigo="${producto.codigo}"]`);
                    if (fila) {
                        // Actualizar celdas con efecto de resaltado
                        actualizarFilaProducto(fila, producto);
                    }
                });
            }
        })
        .catch(err => console.error('Error actualizando productos:', err));
}

// Función para actualizar una fila de producto con animación
function actualizarFilaProducto(fila, producto) {
    // Añadir clase de actualización
    fila.classList.add('bg-green-100', 'dark:bg-green-900');
    
    // Actualizar precios
    const celdas = fila.querySelectorAll('td');
    if (celdas.length >= 5) {
        // Precio propio
        if (producto.precio_propio) {
            celdas[2].textContent = `L. ${parseFloat(producto.precio_propio).toFixed(2)}`;
        }
        
        // Precio competencia
        if (producto.precio_competencia) {
            celdas[3].textContent = `L. ${parseFloat(producto.precio_competencia).toFixed(2)}`;
        }
        
        // Diferencia
        if (producto.diferencia) {
            const diff = parseFloat(producto.diferencia);
            const diffClass = diff > 0 ? 'text-red-600' : 'text-green-600';
            celdas[4].innerHTML = `<span class="${diffClass} font-semibold">${diff > 0 ? '+' : ''}${diff.toFixed(2)}%</span>`;
        }
    }
    
    // Remover resaltado después de 2 segundos
    setTimeout(() => {
        fila.classList.remove('bg-green-100', 'dark:bg-green-900');
    }, 2000);
}

// Función para mostrar notificaciones temporales pequeñas
function mostrarNotificacionTemporal(mensaje, tipo = 'info') {
    const notif = document.createElement('div');
    notif.className = `fixed bottom-4 right-4 px-4 py-2 rounded-lg shadow-lg z-50 transition-all transform translate-y-0 opacity-100 ${
        tipo === 'success' ? 'bg-green-500 text-white' : 'bg-blue-500 text-white'
    }`;
    notif.textContent = mensaje;
    
    document.body.appendChild(notif);
    
    // Animar entrada
    setTimeout(() => notif.classList.add('translate-y-2'), 10);
    
    // Remover después de 3 segundos
    setTimeout(() => {
        notif.classList.add('opacity-0', 'translate-y-4');
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Función para actualizar la lista de productos en tiempo real
function actualizarListaProductos() {
    fetch('api/obtener_productos_actualizados.php')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.productos) {
                const listaProductos = document.getElementById('listaProductos');
                
                // Actualizar cada producto en la lista
                data.productos.forEach(producto => {
                    const productoElement = document.querySelector(`[data-codigo="${producto.codigo}"]`);
                    
                    if (productoElement) {
                        // Actualizar precios y diferencias
                        actualizarProductoEnLista(productoElement, producto);
                        
                        // MOVER A LA PRIMERA POSICIÓN
                        listaProductos.insertBefore(productoElement, listaProductos.firstChild);
                        
                        // Efecto visual de actualización MÁS LLAMATIVO
                        productoElement.classList.add('bg-gradient-to-r', 'from-green-100', 'to-blue-100', 
                                                      'dark:from-green-900/30', 'dark:to-blue-900/30', 
                                                      'border-2', 'border-green-500', 'shadow-lg', 'scale-105');
                        productoElement.style.transition = 'all 0.5s ease';
                        
                        // Scroll suave al producto actualizado
                        productoElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        
                        // Añadir badge de "ACTUALIZADO"
                        const badge = document.createElement('div');
                        badge.className = 'absolute top-2 right-2 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-bold animate-pulse';
                        badge.textContent = '✓ ACTUALIZADO';
                        productoElement.style.position = 'relative';
                        productoElement.appendChild(badge);
                        
                        // Remover efectos después de 3 segundos
                        setTimeout(() => {
                            productoElement.classList.remove('bg-gradient-to-r', 'from-green-100', 'to-blue-100',
                                                            'dark:from-green-900/30', 'dark:to-blue-900/30',
                                                            'border-2', 'border-green-500', 'shadow-lg', 'scale-105');
                            if (badge.parentNode) {
                                badge.remove();
                            }
                        }, 3000);
                    }
                });
                
                // Actualizar estadísticas del dashboard
                if (data.stats) {
                    actualizarEstadisticasDashboard(data.stats);
                }
            }
        })
        .catch(err => console.error('Error actualizando lista:', err));
}

function actualizarEstadisticasDashboard(stats) {
    const elementos = {
        'productos_comparados': stats.productos_comparados || 0,
        'fuentes_activas': stats.fuentes_activas || 0,
        'porcentaje_mas_caro': (stats.porcentaje_mas_caro || 0).toFixed(1) + '%'
    };
    
    Object.keys(elementos).forEach(key => {
        const elemento = document.querySelector(`[data-stat="${key}"]`);
        if (elemento) {
            const valorAnterior = elemento.textContent;
            const valorNuevo = elementos[key];
            
            if (valorAnterior !== valorNuevo) {
                // Efecto de actualización en estadísticas
                elemento.classList.add('text-green-500', 'font-bold', 'scale-110');
                elemento.textContent = valorNuevo;
                
                setTimeout(() => {
                    elemento.classList.remove('text-green-500', 'font-bold', 'scale-110');
                }, 1000);
            }
        }
    });
}

function actualizarProductoEnLista(element, producto) {
    // Actualizar precio promedio competencia
    const promedioEl = element.querySelector('[data-precio-promedio]');
    if (promedioEl && producto.promedio_competencia) {
        const precioAnterior = promedioEl.textContent;
        const precioNuevo = 'L ' + parseFloat(producto.promedio_competencia).toFixed(2);
        
        if (precioAnterior !== precioNuevo) {
            promedioEl.textContent = precioNuevo;
            promedioEl.classList.add('text-green-600', 'font-bold');
            setTimeout(() => {
                promedioEl.classList.remove('text-green-600', 'font-bold');
            }, 2000);
        }
    }
    
    // Actualizar diferencia
    const diferenciaEl = element.querySelector('[data-diferencia-display]');
    if (diferenciaEl && producto.diferencia_porcentual !== undefined) {
        const porcentaje = parseFloat(producto.diferencia_porcentual);
        const signo = porcentaje > 0 ? '+' : '';
        
        // Actualizar texto
        const spanTexto = diferenciaEl.querySelector('span:last-child');
        if (spanTexto) {
            spanTexto.textContent = signo + porcentaje.toFixed(1) + '%';
        }
        
        // Actualizar color e icono
        const iconoEl = element.querySelector('[data-icono-estado]');
        if (iconoEl) {
            if (porcentaje > 5) {
                diferenciaEl.className = 'flex items-center gap-1 text-red-500';
                iconoEl.textContent = 'trending_up';
            } else if (porcentaje < -5) {
                diferenciaEl.className = 'flex items-center gap-1 text-green-500';
                iconoEl.textContent = 'trending_down';
            } else {
                diferenciaEl.className = 'flex items-center gap-1 text-yellow-500';
                iconoEl.textContent = 'trending_flat';
            }
        }
    }
    
    // Actualizar atributo data-diferencia para filtros
    element.setAttribute('data-diferencia', producto.diferencia_porcentual || 0);
}

function detenerActualizacion() {
    actualizacionEnCurso = false;
    document.getElementById('btnDetener').classList.add('hidden');
    document.getElementById('estadoActualizacion').textContent = 'Actualización detenida por el usuario';
}

function finalizarActualizacion(data) {
    actualizacionEnCurso = false;
    document.getElementById('btnDetener').classList.add('hidden');
    document.getElementById('barraProgreso').style.width = '100%';
    document.getElementById('progresoTexto').textContent = '100%';
    document.getElementById('estadoActualizacion').textContent = 
        `✅ Actualización completada! Procesados: ${data.total} | Exitosos: ${data.exitosos}`;
    
    // Mostrar notificación de éxito con opción de recargar
    mostrarConfirmacion(
        '✅ Actualización Completada',
        `Se han procesado ${data.total} productos exitosamente.\n\n¿Deseas recargar la página para ver los resultados actualizados?`,
        () => {
            location.reload();
        }
    );
}

function actualizarPrecios() {
    // Función legacy - redirigir a la nueva
    iniciarActualizacionMasiva();
}
</script>

</body>
</html>
<?php $conexion->close(); ?>