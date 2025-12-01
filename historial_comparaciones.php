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
    <title>Historial de Comparaciones - Rey System</title>
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.3s ease-out;
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
                        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">📋 Historial de Comparaciones</h1>
                        <p class="text-gray-500 dark:text-[#92a4c9] text-base">Todos los precios comparados con la competencia</p>
                    </div>
                    <button onclick="actualizarDatos()" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-blue-600 transition font-semibold shadow-lg">
                        <span class="material-symbols-outlined">refresh</span>
                        Actualizar
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Total Comparados</p>
                                <p class="text-3xl font-bold text-blue-600" id="totalComparados">-</p>
                            </div>
                            <span class="material-symbols-outlined text-4xl text-blue-500">inventory</span>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Más Baratos</p>
                                <p class="text-3xl font-bold text-green-600" id="masBaratos">-</p>
                            </div>
                            <span class="material-symbols-outlined text-4xl text-green-500">trending_down</span>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Más Caros</p>
                                <p class="text-3xl font-bold text-red-600" id="masCaros">-</p>
                            </div>
                            <span class="material-symbols-outlined text-4xl text-red-500">trending_up</span>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-500 dark:text-gray-400 text-sm">Última Actualización</p>
                                <p class="text-lg font-bold text-purple-600" id="ultimaActualizacion">-</p>
                            </div>
                            <span class="material-symbols-outlined text-4xl text-purple-500">schedule</span>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467] mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-semibold mb-2">🔍 Buscar Producto</label>
                            <input type="text" id="filtroNombre" placeholder="Nombre o código..." 
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary"
                                   oninput="filtrarTabla()">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">🏪 Filtrar por Fuente</label>
                            <select id="filtroFuente" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary"
                                    onchange="filtrarTabla()">
                                <option value="">Todas las fuentes</option>
                                <option value="La Colonia">La Colonia</option>
                                <option value="Walmart">Walmart Honduras</option>
                                <option value="Paiz">Paiz</option>
                                <option value="Maxi Despensa">Maxi Despensa</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">📊 Ordenar por</label>
                            <select id="ordenar" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary"
                                    onchange="ordenarTabla()">
                                <option value="fecha_desc">Más recientes</option>
                                <option value="fecha_asc">Más antiguos</option>
                                <option value="precio_asc">Precio menor</option>
                                <option value="precio_desc">Precio mayor</option>
                                <option value="nombre_asc">Nombre A-Z</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Results Table -->
                <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
                    <div class="p-6 border-b border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-bold">📊 Precios Comparados</h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Mostrando <span id="contadorVisible">0</span> de <span id="contadorTotal">0</span> productos</p>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-[#0d1117]">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Producto</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Mi Precio</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Precio Competencia</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Diferencia</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Margen</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Ganancia/Pérdida</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Fuente</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Última Actualización</th>
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tablaResultados" class="divide-y divide-gray-200 dark:divide-[#324467]">
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center gap-4">
                                            <span class="material-symbols-outlined text-6xl text-gray-400 animate-pulse">hourglass_empty</span>
                                            <p class="text-gray-500 dark:text-gray-400">Cargando datos...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div id="paginacion" class="hidden p-6 border-t border-gray-200 dark:border-[#324467]">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                Mostrando <span id="rangoInicio">1</span> a <span id="rangoFin">25</span> de <span id="totalItems">0</span> productos
                            </div>
                            <div class="flex gap-2">
                                <button onclick="cambiarPagina('anterior')" id="btnAnterior" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                                    ← Anterior
                                </button>
                                <div id="numeroPaginas" class="flex gap-2"></div>
                                <button onclick="cambiarPagina('siguiente')" id="btnSiguiente" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
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

<script>
let todosLosDatos = [];
let datosFiltrados = [];
let intervaloActualizacion = null;
let paginaActual = 1;
const itemsPorPagina = 25;

// Cargar datos al iniciar
async function cargarDatos() {
    try {
        const res = await fetch('api/historial_comparaciones.php');
        const data = await res.json();
        
        if (data.success) {
            todosLosDatos = data.comparaciones;
            datosFiltrados = todosLosDatos;
            actualizarEstadisticas(data.estadisticas);
            paginaActual = 1;
            mostrarPaginaActual();
        }
    } catch (e) {
        console.error('Error cargando datos:', e);
    }
}

function actualizarEstadisticas(stats) {
    document.getElementById('totalComparados').textContent = stats.total || 0;
    document.getElementById('masBaratos').textContent = stats.mas_baratos || 0;
    document.getElementById('masCaros').textContent = stats.mas_caros || 0;
    document.getElementById('ultimaActualizacion').textContent = stats.ultima_actualizacion || '-';
}

function mostrarDatos(datos) {
    const tbody = document.getElementById('tablaResultados');
    tbody.innerHTML = '';
    
    if (datos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                    <span class="material-symbols-outlined text-6xl mb-4 opacity-50">search_off</span>
                    <p>No hay datos para mostrar</p>
                </td>
            </tr>
        `;
        return;
    }
    
    datos.forEach((item, index) => {
        const miPrecio = parseFloat(item.mi_precio || 0);
        const precioComp = parseFloat(item.precio_competencia);
        
        // Calcular diferencia porcentual
        let diferencia = 0;
        let colorDif = 'text-gray-500';
        let iconoDif = 'remove';
        
        if (miPrecio > 0) {
            diferencia = ((precioComp - miPrecio) / miPrecio) * 100;
            if (diferencia > 0) {
                colorDif = 'text-red-600';
                iconoDif = 'arrow_upward';
            } else if (diferencia < 0) {
                colorDif = 'text-green-600';
                iconoDif = 'arrow_downward';
            }
        }
        
        // Calcular margen de ganancia/pérdida
        let margen = 0;
        let margenColor = 'text-gray-500';
        let margenIcon = 'remove';
        
        if (miPrecio > 0) {
            // Margen = (Mi Precio - Precio Competencia) / Mi Precio * 100
            margen = ((miPrecio - precioComp) / miPrecio) * 100;
            if (margen > 0) {
                margenColor = 'text-green-600 bg-green-50 dark:bg-green-900/20';
                margenIcon = 'trending_up';
            } else if (margen < 0) {
                margenColor = 'text-red-600 bg-red-50 dark:bg-red-900/20';
                margenIcon = 'trending_down';
            } else {
                margenColor = 'text-gray-600 bg-gray-50 dark:bg-gray-900/20';
            }
        }
        
        // Calcular ganancia/pérdida en Lempiras
        const gananciaLempiras = miPrecio - precioComp;
        let gananciaColor = 'text-gray-500';
        let gananciaIcon = 'remove';
        
        if (gananciaLempiras > 0) {
            gananciaColor = 'text-green-600 font-bold';
            gananciaIcon = 'add_circle';
        } else if (gananciaLempiras < 0) {
            gananciaColor = 'text-red-600 font-bold';
            gananciaIcon = 'remove_circle';
        }
        
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-[#0d1117] transition fade-in';
        tr.style.animationDelay = `${index * 0.02}s`;
        
        tr.innerHTML = `
            <td class="px-6 py-4">
                <div class="font-semibold text-gray-900 dark:text-white">${item.nombre_producto || 'Sin nombre'}</div>
                <div class="text-sm text-gray-500">${item.codigo_producto}</div>
            </td>
            <td class="px-6 py-4">
                <span class="text-lg font-bold text-blue-600">${miPrecio > 0 ? 'L. ' + miPrecio.toFixed(2) : '-'}</span>
            </td>
            <td class="px-6 py-4">
                <span class="text-lg font-bold text-purple-600">L. ${precioComp.toFixed(2)}</span>
            </td>
            <td class="px-6 py-4">
                ${miPrecio > 0 ? `
                    <span class="${colorDif} font-bold flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">${iconoDif}</span>
                        ${Math.abs(diferencia).toFixed(1)}%
                    </span>
                ` : '<span class="text-gray-400">-</span>'}
            </td>
            <td class="px-6 py-4">
                ${miPrecio > 0 ? `
                    <div class="inline-flex items-center gap-2 px-3 py-2 rounded-lg ${margenColor}">
                        <span class="material-symbols-outlined text-sm">${margenIcon}</span>
                        <span class="font-bold">${margen > 0 ? '+' : ''}${margen.toFixed(1)}%</span>
                    </div>
                ` : '<span class="text-gray-400">-</span>'}
            </td>
            <td class="px-6 py-4">
                ${miPrecio > 0 ? `
                    <div class="flex items-center gap-2 ${gananciaColor}">
                        <span class="material-symbols-outlined text-sm">${gananciaIcon}</span>
                        <span class="text-lg font-bold">L. ${gananciaLempiras > 0 ? '+' : ''}${gananciaLempiras.toFixed(2)}</span>
                    </div>
                ` : '<span class="text-gray-400">-</span>'}
            </td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold ${getFuenteColor(item.fuente)}">
                    <span class="material-symbols-outlined text-sm">store</span>
                    ${item.fuente}
                </span>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                ${formatearFecha(item.fecha_actualizacion)}
            </td>
            <td class="px-6 py-4">
                <a href="${item.url_producto}" target="_blank" class="text-primary hover:underline text-sm flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">open_in_new</span>
                    Ver
                </a>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function getFuenteColor(fuente) {
    const colores = {
        'La Colonia': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'Walmart': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'Paiz': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'Maxi Despensa': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'
    };
    return colores[fuente] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400';
}

function formatearFecha(fecha) {
    if (!fecha) return '-';
    const d = new Date(fecha);
    const ahora = new Date();
    const diff = Math.floor((ahora - d) / 1000); // segundos
    
    if (diff < 60) return 'Hace un momento';
    if (diff < 3600) return `Hace ${Math.floor(diff / 60)} minutos`;
    if (diff < 86400) return `Hace ${Math.floor(diff / 3600)} horas`;
    if (diff < 604800) return `Hace ${Math.floor(diff / 86400)} días`;
    
    return d.toLocaleDateString('es-HN', { year: 'numeric', month: 'short', day: 'numeric' });
}

function filtrarTabla() {
    const filtroNombre = document.getElementById('filtroNombre').value.toLowerCase();
    const filtroFuente = document.getElementById('filtroFuente').value;
    
    datosFiltrados = todosLosDatos.filter(item => {
        const coincideNombre = !filtroNombre || 
            (item.nombre_producto && item.nombre_producto.toLowerCase().includes(filtroNombre)) ||
            (item.codigo_producto && item.codigo_producto.toLowerCase().includes(filtroNombre));
        
        const coincideFuente = !filtroFuente || item.fuente === filtroFuente;
        
        return coincideNombre && coincideFuente;
    });
    
    paginaActual = 1;
    mostrarPaginaActual();
}

function ordenarTabla() {
    const orden = document.getElementById('ordenar').value;
    
    switch(orden) {
        case 'fecha_desc':
            datosFiltrados.sort((a, b) => new Date(b.fecha_actualizacion) - new Date(a.fecha_actualizacion));
            break;
        case 'fecha_asc':
            datosFiltrados.sort((a, b) => new Date(a.fecha_actualizacion) - new Date(b.fecha_actualizacion));
            break;
        case 'precio_asc':
            datosFiltrados.sort((a, b) => parseFloat(a.precio_competencia) - parseFloat(b.precio_competencia));
            break;
        case 'precio_desc':
            datosFiltrados.sort((a, b) => parseFloat(b.precio_competencia) - parseFloat(a.precio_competencia));
            break;
        case 'nombre_asc':
            datosFiltrados.sort((a, b) => (a.nombre_producto || '').localeCompare(b.nombre_producto || ''));
            break;
    }
    
    paginaActual = 1;
    mostrarPaginaActual();
}

function actualizarDatos() {
    cargarDatos();
}

// Funciones de paginación
function mostrarPaginaActual() {
    const inicio = (paginaActual - 1) * itemsPorPagina;
    const fin = inicio + itemsPorPagina;
    const datosPagina = datosFiltrados.slice(inicio, fin);
    
    mostrarDatos(datosPagina);
    actualizarControlesPaginacion();
    
    // Actualizar contadores
    document.getElementById('contadorVisible').textContent = datosFiltrados.length;
    document.getElementById('contadorTotal').textContent = todosLosDatos.length;
}

function actualizarControlesPaginacion() {
    const totalPaginas = Math.ceil(datosFiltrados.length / itemsPorPagina);
    const paginacion = document.getElementById('paginacion');
    
    if (totalPaginas <= 1) {
        paginacion.classList.add('hidden');
        return;
    }
    
    paginacion.classList.remove('hidden');
    
    // Actualizar rango
    const inicio = (paginaActual - 1) * itemsPorPagina + 1;
    const fin = Math.min(paginaActual * itemsPorPagina, datosFiltrados.length);
    document.getElementById('rangoInicio').textContent = inicio;
    document.getElementById('rangoFin').textContent = fin;
    document.getElementById('totalItems').textContent = datosFiltrados.length;
    
    // Botones anterior/siguiente
    document.getElementById('btnAnterior').disabled = paginaActual === 1;
    document.getElementById('btnSiguiente').disabled = paginaActual === totalPaginas;
    
    // Números de página
    const numeroPaginas = document.getElementById('numeroPaginas');
    numeroPaginas.innerHTML = '';
    
    for (let i = 1; i <= totalPaginas; i++) {
        if (i === 1 || i === totalPaginas || (i >= paginaActual - 2 && i <= paginaActual + 2)) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = `px-4 py-2 rounded-lg font-semibold transition ${
                i === paginaActual 
                    ? 'bg-primary text-white' 
                    : 'bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600'
            }`;
            btn.onclick = () => irAPagina(i);
            numeroPaginas.appendChild(btn);
        } else if (i === paginaActual - 3 || i === paginaActual + 3) {
            const span = document.createElement('span');
            span.textContent = '...';
            span.className = 'px-2 text-gray-500';
            numeroPaginas.appendChild(span);
        }
    }
}

function cambiarPagina(direccion) {
    if (direccion === 'anterior' && paginaActual > 1) {
        paginaActual--;
    } else if (direccion === 'siguiente') {
        const totalPaginas = Math.ceil(datosFiltrados.length / itemsPorPagina);
        if (paginaActual < totalPaginas) {
            paginaActual++;
        }
    }
    mostrarPaginaActual();
}

function irAPagina(pagina) {
    paginaActual = pagina;
    mostrarPaginaActual();
}

// Auto-actualizar cada 10 segundos
intervaloActualizacion = setInterval(() => {
    cargarDatos();
}, 1000000000);

// Cargar al iniciar
cargarDatos();
</script>
</body>
</html>
