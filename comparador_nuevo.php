<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Nombre_Completo = $row['Nombre']." ".$row['Apellido'];
    $Perfil = $row['Perfil'];
}
 $rol_usuario = strtolower($Rol);
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Comparador de Precios - Rey System</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-pulse-slow {
            animation: pulse-slow 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        #contadorItems {
            transition: transform 0.2s ease-in-out;
        }
        #contadorItems.scale-125 {
            transform: scale(1.25);
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
    <div class="flex flex-1">
        <?php include 'menu_lateral.php'; ?>
        
        <main class="flex-1 flex flex-col">
            <div class="flex-1 p-6 lg:p-10">
                <!-- Header -->
                <div class="flex flex-wrap justify-between gap-4 mb-8">
                    <div class="flex flex-col gap-2">
                        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">🔍 Comparador de Precios</h1>
                        <p class="text-gray-500 dark:text-[#92a4c9] text-base">Compara automáticamente todos tus productos con la competencia</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="iniciarComparacion()" id="btnIniciar" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-blue-600 transition font-semibold shadow-lg">
                            <span class="material-symbols-outlined">play_arrow</span>
                            Iniciar Comparación
                        </button>
                        <button onclick="detenerComparacion()" id="btnDetener" class="hidden flex items-center gap-2 px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition font-semibold">
                            <span class="material-symbols-outlined">stop</span>
                            Detener
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Total Productos</p>
                                <p class="text-3xl font-bold text-gray-900 dark:text-white" id="totalProductos">-</p>
                            </div>
                            <span class="material-symbols-outlined text-4xl text-blue-500">inventory_2</span>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Procesados</p>
                                <p class="text-3xl font-bold text-green-600" id="procesados">0</p>
                            </div>
                            <span class="material-symbols-outlined text-4xl text-green-500">check_circle</span>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Encontrados</p>
                                <p class="text-3xl font-bold text-purple-600" id="encontrados">0</p>
                            </div>
                            <span class="material-symbols-outlined text-4xl text-purple-500">search</span>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Progreso</p>
                                <p class="text-3xl font-bold text-orange-600" id="progreso">0%</p>
                            </div>
                            <span class="material-symbols-outlined text-4xl text-orange-500">trending_up</span>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div id="progressContainer" class="hidden mb-8">
                    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold">Procesando...</h3>
                            <span id="tiempoTranscurrido" class="text-sm text-gray-500">0s</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                            <div id="barraProgreso" class="bg-gradient-to-r from-blue-500 to-purple-600 h-4 rounded-full transition-all duration-500" style="width: 0%"></div>
                        </div>
                        <p id="estadoActual" class="text-sm text-gray-600 dark:text-gray-400 mt-3">Esperando...</p>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between flex-wrap gap-4">
                            <div>
                                <h2 class="text-xl font-bold">📊 Resultados de Comparación</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Productos comparados con precios de la competencia</p>
                            </div>
                            
                            <!-- Counter Card -->
                            <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl px-6 py-4 border-2 border-blue-200 dark:border-blue-700 shadow-lg">
                                <div class="flex items-center gap-3">
                                    <div class="bg-blue-500 rounded-full p-2">
                                        <span class="material-symbols-outlined text-white text-2xl">shopping_cart</span>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-semibold uppercase">Items en Tabla</p>
                                        <p class="text-3xl font-black text-blue-600 dark:text-blue-400" id="contadorItems">0</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-[#0d1117]">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Producto</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Mi Precio</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Competencia</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Diferencia</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Fuente</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tablaResultados" class="divide-y divide-gray-200 dark:divide-[#324467]">
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <span class="material-symbols-outlined text-6xl mb-4 opacity-50">search_off</span>
                                        <p>No hay resultados aún. Inicia la comparación para ver los precios.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div id="paginacionComparador" class="hidden p-6 border-t border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Mostrando <span id="rangoInicioComp">1</span> a <span id="rangoFinComp">25</span> de <span id="totalItemsComp">0</span> productos
                            </div>
                            <div class="flex gap-2">
                                <button onclick="cambiarPaginaComp('anterior')" id="btnAnteriorComp" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                                    ← Anterior
                                </button>
                                <div id="numeroPaginasComp" class="flex gap-2"></div>
                                <button onclick="cambiarPaginaComp('siguiente')" id="btnSiguienteComp" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                                    Siguiente →
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal de Notificaciones -->
<div id="modalNotificacion" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-opacity">
    <div class="bg-white dark:bg-[#192233] rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
        <div class="p-6">
            <div class="flex items-start gap-4">
                <div id="modalIcon" class="flex-shrink-0">
                    <span class="material-symbols-outlined text-4xl"></span>
                </div>
                <div class="flex-1">
                    <h3 id="modalTitulo" class="text-xl font-bold text-gray-900 dark:text-white mb-2"></h3>
                    <p id="modalMensaje" class="text-gray-600 dark:text-gray-300"></p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <button onclick="cerrarModal()" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let comparacionActiva = false;
let offsetActual = 0;
let tiempoInicio = 0;
let intervaloTiempo = null;
let todosLosResultados = [];
let paginaActualComp = 1;
const itemsPorPaginaComp = 25;

// Sistema de notificaciones modal
function mostrarNotificacion(titulo, mensaje, tipo = 'info') {
    const modal = document.getElementById('modalNotificacion');
    const icono = document.getElementById('modalIcon');
    const tituloEl = document.getElementById('modalTitulo');
    const mensajeEl = document.getElementById('modalMensaje');
    
    // Configurar según tipo
    const configs = {
        error: { icon: 'error', color: 'text-red-500' },
        success: { icon: 'check_circle', color: 'text-green-500' },
        warning: { icon: 'warning', color: 'text-yellow-500' },
        info: { icon: 'info', color: 'text-blue-500' }
    };
    
    const config = configs[tipo] || configs.info;
    icono.innerHTML = `<span class="material-symbols-outlined text-4xl ${config.color}">${config.icon}</span>`;
    tituloEl.textContent = titulo;
    mensajeEl.textContent = mensaje;
    
    modal.classList.remove('hidden');
    setTimeout(() => modal.classList.add('opacity-100'), 10);
}

function cerrarModal() {
    const modal = document.getElementById('modalNotificacion');
    modal.classList.remove('opacity-100');
    setTimeout(() => modal.classList.add('hidden'), 200);
}

// Cargar estadísticas iniciales
async function cargarEstadisticas() {
    try {
        const res = await fetch('api/comparador_stats.php');
        const data = await res.json();
        document.getElementById('totalProductos').textContent = data.total || '-';
    } catch (e) {
        console.error('Error cargando stats:', e);
        mostrarNotificacion('Error de Conexión', 'No se pudieron cargar las estadísticas. Verifica que el servidor esté funcionando.', 'error');
    }
}

function iniciarComparacion() {
    if (comparacionActiva) return;
    
    comparacionActiva = true;
    offsetActual = 0;
    tiempoInicio = Date.now();
    todosLosResultados = []; // Reset results
    paginaActualComp = 1; // Reset to first page
    
    document.getElementById('btnIniciar').classList.add('hidden');
    document.getElementById('btnDetener').classList.remove('hidden');
    document.getElementById('progressContainer').classList.remove('hidden');
    
    // Limpiar tabla
    const tbody = document.getElementById('tablaResultados');
    tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">Iniciando comparación...</td></tr>';
    
    // Iniciar contador de tiempo
    intervaloTiempo = setInterval(() => {
        const segundos = Math.floor((Date.now() - tiempoInicio) / 1000);
        const minutos = Math.floor(segundos / 60);
        const segs = segundos % 60;
        document.getElementById('tiempoTranscurrido').textContent = `${minutos}m ${segs}s`;
    }, 1000);
    
    procesarLote();
}

function detenerComparacion() {
    comparacionActiva = false;
    document.getElementById('btnIniciar').classList.remove('hidden');
    document.getElementById('btnDetener').classList.add('hidden');
    clearInterval(intervaloTiempo);
    document.getElementById('estadoActual').textContent = 'Detenido por el usuario';
}

async function procesarLote() {
    if (!comparacionActiva) return;
    
    try {
        const res = await fetch(`api/comparador_batch.php?offset=${offsetActual}&lote=5`);
        const data = await res.json();
        
        if (data.success) {
            // Actualizar estadísticas
            document.getElementById('procesados').textContent = data.total_procesados;
            document.getElementById('encontrados').textContent = data.total_encontrados;
            document.getElementById('progreso').textContent = Math.round(data.progreso) + '%';
            document.getElementById('barraProgreso').style.width = data.progreso + '%';
            document.getElementById('estadoActual').textContent = data.mensaje;
            
            // Actualizar tabla si hay resultados nuevos
            if (data.resultados && data.resultados.length > 0) {
                actualizarTabla(data.resultados);
            }
            
            offsetActual = data.offset;
            
            // Continuar si hay más
            if (data.hay_mas && comparacionActiva) {
                setTimeout(() => procesarLote(), 1000);
            } else {
                finalizarComparacion(data);
            }
        } else {
            mostrarNotificacion('Error en Comparación', data.message || 'Error desconocido al procesar productos', 'error');
            detenerComparacion();
        }
    } catch (e) {
        console.error('Error:', e);
        mostrarNotificacion('Error de Conexión', 'No se pudo conectar con el servidor. Verifica tu conexión e intenta nuevamente.', 'error');
        detenerComparacion();
    }
}

function actualizarTabla(resultados) {
    // Agregar nuevos resultados al array global
    todosLosResultados = todosLosResultados.concat(resultados);
    
    // Mostrar página actual
    mostrarPaginaActualComp();
}

function mostrarPaginaActualComp() {
    const tbody = document.getElementById('tablaResultados');
    
    // Limpiar mensaje inicial
    if (tbody.querySelector('td[colspan="6"]')) {
        tbody.innerHTML = '';
    }
    
    // Calcular rango de la página actual
    const inicio = (paginaActualComp - 1) * itemsPorPaginaComp;
    const fin = inicio + itemsPorPaginaComp;
    const resultadosPagina = todosLosResultados.slice(inicio, fin);
    
    // Limpiar tbody solo si estamos en la primera página
    if (paginaActualComp === 1 && inicio === 0) {
        tbody.innerHTML = '';
    }
    
    // Renderizar solo los items de esta página
    tbody.innerHTML = '';
    resultadosPagina.forEach(r => {
        const diferencia = r.diferencia_porcentual;
        const colorDif = diferencia > 0 ? 'text-red-600' : 'text-green-600';
        const iconoDif = diferencia > 0 ? 'arrow_upward' : 'arrow_downward';
        
        // Indicador de cambio de precio
        let cambioPrecio = '';
        if (r.cambio === 'subio') {
            cambioPrecio = '<span class="text-red-500 text-xs flex items-center gap-1 mt-1"><span class="material-symbols-outlined text-sm">trending_up</span>Subió</span>';
        } else if (r.cambio === 'bajo') {
            cambioPrecio = '<span class="text-green-500 text-xs flex items-center gap-1 mt-1"><span class="material-symbols-outlined text-sm">trending_down</span>Bajó</span>';
        } else if (r.cambio === 'igual') {
            cambioPrecio = '<span class="text-gray-500 text-xs flex items-center gap-1 mt-1"><span class="material-symbols-outlined text-sm">remove</span>Sin cambio</span>';
        } else {
            cambioPrecio = '<span class="text-blue-500 text-xs flex items-center gap-1 mt-1"><span class="material-symbols-outlined text-sm">fiber_new</span>Nuevo</span>';
        }
        
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-[#0d1117] transition';
        tr.innerHTML = `
            <td class="px-6 py-4">
                <div class="font-semibold text-gray-900 dark:text-white">${r.nombre}</div>
                <div class="text-sm text-gray-500">${r.codigo}</div>
            </td>
            <td class="px-6 py-4 font-semibold text-blue-600">L. ${parseFloat(r.mi_precio).toFixed(2)}</td>
            <td class="px-6 py-4">
                <div class="font-semibold text-purple-600">L. ${parseFloat(r.precio_competencia).toFixed(2)}</div>
                ${cambioPrecio}
            </td>
            <td class="px-6 py-4">
                <span class="${colorDif} font-bold flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">${iconoDif}</span>
                    ${Math.abs(diferencia).toFixed(1)}%
                </span>
            </td>
            <td class="px-6 py-4 text-sm">${r.fuente}</td>
            <td class="px-6 py-4">
                <a href="${r.url}" target="_blank" class="text-primary hover:underline text-sm flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                    Ver
                </a>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    // Actualizar contador de items
    const totalItems = todosLosResultados.length;
    document.getElementById('contadorItems').textContent = totalItems;
    
    // Animar el contador
    const contador = document.getElementById('contadorItems');
    contador.classList.add('scale-125');
    setTimeout(() => contador.classList.remove('scale-125'), 200);
    
    // Actualizar controles de paginación
    actualizarControlesPaginacionComp();
}

function actualizarControlesPaginacionComp() {
    const totalPaginas = Math.ceil(todosLosResultados.length / itemsPorPaginaComp);
    const paginacion = document.getElementById('paginacionComparador');
    
    if (totalPaginas <= 1) {
        paginacion.classList.add('hidden');
        return;
    }
    
    paginacion.classList.remove('hidden');
    
    // Actualizar rango
    const inicio = (paginaActualComp - 1) * itemsPorPaginaComp + 1;
    const fin = Math.min(paginaActualComp * itemsPorPaginaComp, todosLosResultados.length);
    document.getElementById('rangoInicioComp').textContent = inicio;
    document.getElementById('rangoFinComp').textContent = fin;
    document.getElementById('totalItemsComp').textContent = todosLosResultados.length;
    
    // Botones anterior/siguiente
    document.getElementById('btnAnteriorComp').disabled = paginaActualComp === 1;
    document.getElementById('btnSiguienteComp').disabled = paginaActualComp === totalPaginas;
    
    // Números de página
    const numeroPaginas = document.getElementById('numeroPaginasComp');
    numeroPaginas.innerHTML = '';
    
    for (let i = 1; i <= totalPaginas; i++) {
        if (i === 1 || i === totalPaginas || (i >= paginaActualComp - 2 && i <= paginaActualComp + 2)) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = `px-4 py-2 rounded-lg font-semibold transition ${
                i === paginaActualComp 
                    ? 'bg-primary text-white' 
                    : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600'
            }`;
            btn.onclick = () => irAPaginaComp(i);
            numeroPaginas.appendChild(btn);
        } else if (i === paginaActualComp - 3 || i === paginaActualComp + 3) {
            const span = document.createElement('span');
            span.textContent = '...';
            span.className = 'px-2 text-gray-500';
            numeroPaginas.appendChild(span);
        }
    }
}

function cambiarPaginaComp(direccion) {
    if (direccion === 'anterior' && paginaActualComp > 1) {
        paginaActualComp--;
    } else if (direccion === 'siguiente') {
        const totalPaginas = Math.ceil(todosLosResultados.length / itemsPorPaginaComp);
        if (paginaActualComp < totalPaginas) {
            paginaActualComp++;
        }
    }
    mostrarPaginaActualComp();
}

function irAPaginaComp(pagina) {
    paginaActualComp = pagina;
    mostrarPaginaActualComp();
}

function finalizarComparacion(data) {
    comparacionActiva = false;
    clearInterval(intervaloTiempo);
    document.getElementById('btnIniciar').classList.remove('hidden');
    document.getElementById('btnDetener').classList.add('hidden');
    document.getElementById('estadoActual').textContent = `✅ Completado! ${data.total_encontrados} precios encontrados de ${data.total_procesados} productos`;
    
    // Mostrar notificación de éxito
    mostrarNotificacion(
        '¡Comparación Completada!', 
        `Se procesaron ${data.total_procesados} productos y se encontraron ${data.total_encontrados} precios de la competencia.`, 
        'success'
    );
}

// Cargar al iniciar
cargarEstadisticas();
</script>
</body>
</html>
