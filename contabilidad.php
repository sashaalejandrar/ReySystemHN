<?php
session_start();
include 'funciones.php';

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

$rol_usuario = strtolower($Rol);

// Verificar permisos (solo admin y contador)
if (!in_array($rol_usuario, ['admin', 'contador'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Contabilidad - Rey System APP</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">💰 Contabilidad</h1>
                        <p class="text-gray-500 dark:text-[#92a4c9] text-base">Panel de control financiero y contable de la empresa</p>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="exportarExcel()" class="flex items-center gap-2 px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold shadow-lg">
                            <span class="material-symbols-outlined">download</span>
                            Exportar a Excel
                        </button>
                        <button onclick="actualizarDatos()" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-blue-600 transition font-semibold shadow-lg">
                            <span class="material-symbols-outlined">refresh</span>
                            Actualizar
                        </button>
                    </div>
                </div>

                <!-- Filtros de Período -->
                <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467] mb-8">
                    <div class="flex flex-wrap items-center gap-4">
                        <label class="text-sm font-semibold">📅 Período:</label>
                        <div class="flex gap-2">
                            <button onclick="cambiarPeriodo('hoy')" id="btn-hoy" class="px-4 py-2 rounded-lg bg-primary text-white font-semibold transition">Hoy</button>
                            <button onclick="cambiarPeriodo('semana')" id="btn-semana" class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition">Esta Semana</button>
                            <button onclick="cambiarPeriodo('mes')" id="btn-mes" class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition">Este Mes</button>
                            <button onclick="cambiarPeriodo('anio')" id="btn-anio" class="px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition">Este Año</button>
                        </div>
                        <div class="flex items-center gap-2 ml-auto">
                            <label class="text-sm">Desde:</label>
                            <input type="date" id="fecha-inicio" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white">
                            <label class="text-sm">Hasta:</label>
                            <input type="date" id="fecha-fin" class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-[#0d1117] text-gray-900 dark:text-white">
                            <button onclick="aplicarFechasPersonalizadas()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 transition font-semibold">Aplicar</button>
                        </div>
                    </div>
                </div>

                <!-- Tarjetas de Resumen -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Ingresos -->
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg fade-in">
                        <div class="flex items-center justify-between mb-4">
                            <span class="material-symbols-outlined text-4xl opacity-80">trending_up</span>
                            <div class="text-right">
                                <p class="text-sm opacity-90">Total Ingresos</p>
                                <p class="text-3xl font-black" id="total-ingresos">L. 0.00</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="material-symbols-outlined text-xs">info</span>
                            <span id="info-ingresos">Ventas del período</span>
                        </div>
                    </div>

                    <!-- Egresos -->
                    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white shadow-lg fade-in">
                        <div class="flex items-center justify-between mb-4">
                            <span class="material-symbols-outlined text-4xl opacity-80">trending_down</span>
                            <div class="text-right">
                                <p class="text-sm opacity-90">Total Egresos</p>
                                <p class="text-3xl font-black" id="total-egresos">L. 0.00</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="material-symbols-outlined text-xs">info</span>
                            <span id="info-egresos">Gastos del período</span>
                        </div>
                    </div>

                    <!-- Utilidad -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg fade-in">
                        <div class="flex items-center justify-between mb-4">
                            <span class="material-symbols-outlined text-4xl opacity-80">account_balance</span>
                            <div class="text-right">
                                <p class="text-sm opacity-90">Utilidad Neta</p>
                                <p class="text-3xl font-black" id="utilidad-neta">L. 0.00</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="material-symbols-outlined text-xs">info</span>
                            <span>Ingresos - Egresos</span>
                        </div>
                    </div>

                    <!-- Margen -->
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg fade-in">
                        <div class="flex items-center justify-between mb-4">
                            <span class="material-symbols-outlined text-4xl opacity-80">percent</span>
                            <div class="text-right">
                                <p class="text-sm opacity-90">Margen de Utilidad</p>
                                <p class="text-3xl font-black" id="margen-utilidad">0%</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                            <span class="material-symbols-outlined text-xs">info</span>
                            <span>Utilidad / Ingresos</span>
                        </div>
                    </div>
                </div>

                <!-- Gráficas -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Gráfica de Tendencias -->
                    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                        <h3 class="text-xl font-bold mb-4">📈 Tendencia Financiera</h3>
                        <canvas id="grafica-tendencias"></canvas>
                    </div>

                    <!-- Gráfica de Distribución -->
                    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                        <h3 class="text-xl font-bold mb-4">🥧 Distribución de Ingresos</h3>
                        <canvas id="grafica-distribucion"></canvas>
                    </div>
                </div>

                <!-- Tabs de Detalles -->
                <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
                    <div class="border-b border-gray-200 dark:border-[#324467]">
                        <div class="flex gap-2 p-4">
                            <button onclick="cambiarTab('ventas')" id="tab-ventas" class="px-6 py-3 rounded-lg bg-primary text-white font-semibold transition">Ventas</button>
                            <button onclick="cambiarTab('egresos')" id="tab-egresos" class="px-6 py-3 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition">Egresos</button>
                            <button onclick="cambiarTab('caja')" id="tab-caja" class="px-6 py-3 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition">Operaciones de Caja</button>
                            <button onclick="cambiarTab('inventario')" id="tab-inventario" class="px-6 py-3 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition">Inventario</button>
                        </div>
                    </div>

                    <!-- Contenido de Tabs -->
                    <div class="p-6">
                        <!-- Tab Ventas -->
                        <div id="content-ventas" class="tab-content">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 dark:bg-[#0d1117]">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Factura</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Cliente</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Fecha</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Método Pago</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Total</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Vendedor</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-ventas" class="divide-y divide-gray-200 dark:divide-[#324467]">
                                        <tr>
                                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">Cargando datos...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Egresos -->
                        <div id="content-egresos" class="tab-content hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 dark:bg-[#0d1117]">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Fecha</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Concepto</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Tipo</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Monto</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Usuario</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-egresos" class="divide-y divide-gray-200 dark:divide-[#324467]">
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">Cargando datos...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Caja -->
                        <div id="content-caja" class="tab-content hidden">
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 dark:bg-[#0d1117]">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Fecha</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Tipo</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Monto Inicial</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Monto Final</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Diferencia</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Usuario</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-caja" class="divide-y divide-gray-200 dark:divide-[#324467]">
                                        <tr>
                                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">Cargando datos...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tab Inventario -->
                        <div id="content-inventario" class="tab-content hidden">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
                                    <p class="text-sm text-blue-600 dark:text-blue-400 font-semibold">Valor Total Inventario</p>
                                    <p class="text-2xl font-black text-blue-700 dark:text-blue-300" id="valor-inventario">L. 0.00</p>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
                                    <p class="text-sm text-green-600 dark:text-green-400 font-semibold">Productos en Stock</p>
                                    <p class="text-2xl font-black text-green-700 dark:text-green-300" id="productos-stock">0</p>
                                </div>
                                <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-700">
                                    <p class="text-sm text-orange-600 dark:text-orange-400 font-semibold">Stock Bajo</p>
                                    <p class="text-2xl font-black text-orange-700 dark:text-orange-300" id="stock-bajo">0</p>
                                </div>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 dark:bg-[#0d1117]">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Producto</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Cantidad</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Precio Compra</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Precio Venta</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase">Valor Total</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla-inventario" class="divide-y divide-gray-200 dark:divide-[#324467]">
                                        <tr>
                                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">Cargando datos...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
let periodoActual = 'hoy';
let fechaInicio = null;
let fechaFin = null;
let graficaTendencias = null;
let graficaDistribucion = null;

// Inicializar fechas
document.getElementById('fecha-inicio').valueAsDate = new Date();
document.getElementById('fecha-fin').valueAsDate = new Date();

// Cargar datos al iniciar
document.addEventListener('DOMContentLoaded', function() {
    cargarDatos();
});

function cambiarPeriodo(periodo) {
    periodoActual = periodo;
    fechaInicio = null;
    fechaFin = null;
    
    // Actualizar botones
    ['hoy', 'semana', 'mes', 'anio'].forEach(p => {
        const btn = document.getElementById(`btn-${p}`);
        if (p === periodo) {
            btn.className = 'px-4 py-2 rounded-lg bg-primary text-white font-semibold transition';
        } else {
            btn.className = 'px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition';
        }
    });
    
    cargarDatos();
}

function aplicarFechasPersonalizadas() {
    fechaInicio = document.getElementById('fecha-inicio').value;
    fechaFin = document.getElementById('fecha-fin').value;
    periodoActual = 'personalizado';
    
    // Desactivar todos los botones de período
    ['hoy', 'semana', 'mes', 'anio'].forEach(p => {
        const btn = document.getElementById(`btn-${p}`);
        btn.className = 'px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition';
    });
    
    cargarDatos();
}

async function cargarDatos() {
    await cargarResumen();
    await cargarTabActual();
}

async function cargarResumen() {
    try {
        let url = `api/contabilidad_resumen.php?periodo=${periodoActual}`;
        if (fechaInicio && fechaFin) {
            url += `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        }
        
        const res = await fetch(url);
        const data = await res.json();
        
        if (data.success) {
            // Actualizar tarjetas
            document.getElementById('total-ingresos').textContent = 'L. ' + formatearNumero(data.ingresos);
            document.getElementById('total-egresos').textContent = 'L. ' + formatearNumero(data.egresos);
            document.getElementById('utilidad-neta').textContent = 'L. ' + formatearNumero(data.utilidad);
            document.getElementById('margen-utilidad').textContent = data.margen.toFixed(1) + '%';
            
            document.getElementById('info-ingresos').textContent = `${data.num_ventas} ventas`;
            document.getElementById('info-egresos').textContent = `${data.num_egresos} egresos`;
            
            // Actualizar gráficas
            actualizarGraficaTendencias(data.tendencias);
            actualizarGraficaDistribucion(data.distribucion);
        }
    } catch (e) {
        console.error('Error cargando resumen:', e);
    }
}

async function cargarTabActual() {
    const tabActivo = document.querySelector('.tab-content:not(.hidden)').id.replace('content-', '');
    
    switch(tabActivo) {
        case 'ventas':
            await cargarVentas();
            break;
        case 'egresos':
            await cargarEgresos();
            break;
        case 'caja':
            await cargarCaja();
            break;
        case 'inventario':
            await cargarInventario();
            break;
    }
}

async function cargarVentas() {
    try {
        let url = `api/contabilidad_ventas.php?periodo=${periodoActual}`;
        if (fechaInicio && fechaFin) {
            url += `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        }
        
        const res = await fetch(url);
        const data = await res.json();
        
        const tbody = document.getElementById('tabla-ventas');
        tbody.innerHTML = '';
        
        if (data.success && data.ventas.length > 0) {
            data.ventas.forEach(venta => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50 dark:hover:bg-[#0d1117] transition';
                tr.innerHTML = `
                    <td class="px-6 py-4 font-semibold text-blue-600">${venta.factura}</td>
                    <td class="px-6 py-4">${venta.cliente}</td>
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${formatearFecha(venta.fecha)}</td>
                    <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">${venta.metodo_pago}</span></td>
                    <td class="px-6 py-4 font-bold text-green-600">L. ${formatearNumero(venta.total)}</td>
                    <td class="px-6 py-4 text-sm">${venta.vendedor}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No hay ventas en este período</td></tr>';
        }
    } catch (e) {
        console.error('Error cargando ventas:', e);
    }
}

async function cargarEgresos() {
    try {
        let url = `api/contabilidad_egresos.php?periodo=${periodoActual}`;
        if (fechaInicio && fechaFin) {
            url += `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        }
        
        const res = await fetch(url);
        const data = await res.json();
        
        const tbody = document.getElementById('tabla-egresos');
        tbody.innerHTML = '';
        
        if (data.success && data.egresos.length > 0) {
            data.egresos.forEach(egreso => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50 dark:hover:bg-[#0d1117] transition';
                tr.innerHTML = `
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${formatearFecha(egreso.fecha)}</td>
                    <td class="px-6 py-4">${egreso.concepto}</td>
                    <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">${egreso.tipo}</span></td>
                    <td class="px-6 py-4 font-bold text-red-600">L. ${formatearNumero(egreso.monto)}</td>
                    <td class="px-6 py-4 text-sm">${egreso.usuario}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">No hay egresos en este período</td></tr>';
        }
    } catch (e) {
        console.error('Error cargando egresos:', e);
    }
}

async function cargarCaja() {
    try {
        let url = `api/contabilidad_caja.php?periodo=${periodoActual}`;
        if (fechaInicio && fechaFin) {
            url += `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        }
        
        console.log('Cargando caja desde:', url);
        const res = await fetch(url);
        const data = await res.json();
        
        console.log('Respuesta caja:', data);
        
        const tbody = document.getElementById('tabla-caja');
        tbody.innerHTML = '';
        
        if (data.success && data.operaciones && data.operaciones.length > 0) {
            data.operaciones.forEach(op => {
                const diferencia = parseFloat(op.monto_final || 0) - parseFloat(op.monto_inicial || 0);
                const colorDif = diferencia >= 0 ? 'text-green-600' : 'text-red-600';
                
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50 dark:hover:bg-[#0d1117] transition';
                tr.innerHTML = `
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">${formatearFecha(op.fecha)}</td>
                    <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-semibold ${op.tipo === 'Apertura' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'}">${op.tipo}</span></td>
                    <td class="px-6 py-4 font-semibold">L. ${formatearNumero(op.monto_inicial)}</td>
                    <td class="px-6 py-4 font-semibold">L. ${formatearNumero(op.monto_final)}</td>
                    <td class="px-6 py-4 font-bold ${colorDif}">L. ${formatearNumero(Math.abs(diferencia))}</td>
                    <td class="px-6 py-4 text-sm">${op.usuario}</td>
                `;
                tbody.appendChild(tr);
            });
        } else {
            tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-gray-500">No hay operaciones de caja en este período</td></tr>';
        }
    } catch (e) {
        console.error('Error cargando caja:', e);
        const tbody = document.getElementById('tabla-caja');
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-12 text-center text-red-500">Error al cargar datos. Revisa la consola.</td></tr>';
    }
}

async function cargarInventario() {
    try {
        console.log('Cargando inventario...');
        const res = await fetch('api/contabilidad_inventario.php');
        const data = await res.json();
        
        console.log('Respuesta inventario:', data);
        
        if (data.success) {
            document.getElementById('valor-inventario').textContent = 'L. ' + formatearNumero(data.valor_total);
            document.getElementById('productos-stock').textContent = data.total_productos;
            document.getElementById('stock-bajo').textContent = data.stock_bajo;
            
            const tbody = document.getElementById('tabla-inventario');
            tbody.innerHTML = '';
            
            if (data.productos && data.productos.length > 0) {
                data.productos.forEach(prod => {
                    const valorTotal = parseFloat(prod.cantidad) * parseFloat(prod.precio_compra);
                    const tr = document.createElement('tr');
                    tr.className = 'hover:bg-gray-50 dark:hover:bg-[#0d1117] transition';
                    tr.innerHTML = `
                        <td class="px-6 py-4 font-semibold">${prod.nombre}</td>
                        <td class="px-6 py-4">${prod.cantidad}</td>
                        <td class="px-6 py-4 text-sm">L. ${formatearNumero(prod.precio_compra)}</td>
                        <td class="px-6 py-4 text-sm">L. ${formatearNumero(prod.precio_venta)}</td>
                        <td class="px-6 py-4 font-bold text-blue-600">L. ${formatearNumero(valorTotal)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-12 text-center text-gray-500">No hay productos en inventario</td></tr>';
            }
        } else {
            console.error('Error en respuesta:', data.message);
            const tbody = document.getElementById('tabla-inventario');
            tbody.innerHTML = `<tr><td colspan="5" class="px-6 py-12 text-center text-red-500">Error: ${data.message || 'No se pudieron cargar los datos'}</td></tr>`;
        }
    } catch (e) {
        console.error('Error cargando inventario:', e);
        const tbody = document.getElementById('tabla-inventario');
        tbody.innerHTML = '<tr><td colspan="5" class="px-6 py-12 text-center text-red-500">Error al cargar datos. Revisa la consola.</td></tr>';
    }
}

function cambiarTab(tab) {
    // Ocultar todos los tabs
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Mostrar tab seleccionado
    document.getElementById(`content-${tab}`).classList.remove('hidden');
    
    // Actualizar botones
    ['ventas', 'egresos', 'caja', 'inventario'].forEach(t => {
        const btn = document.getElementById(`tab-${t}`);
        if (t === tab) {
            btn.className = 'px-6 py-3 rounded-lg bg-primary text-white font-semibold transition';
        } else {
            btn.className = 'px-6 py-3 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-600 font-semibold transition';
        }
    });
    
    // Cargar datos del tab
    cargarTabActual();
}

function actualizarGraficaTendencias(tendencias) {
    const ctx = document.getElementById('grafica-tendencias').getContext('2d');
    
    if (graficaTendencias) {
        graficaTendencias.destroy();
    }
    
    graficaTendencias = new Chart(ctx, {
        type: 'line',
        data: {
            labels: tendencias.labels,
            datasets: [
                {
                    label: 'Ingresos',
                    data: tendencias.ingresos,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Egresos',
                    data: tendencias.egresos,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    labels: {
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                    },
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                    }
                },
                x: {
                    ticks: {
                        color: document.documentElement.classList.contains('dark') ? '#9ca3af' : '#6b7280'
                    },
                    grid: {
                        color: document.documentElement.classList.contains('dark') ? '#374151' : '#e5e7eb'
                    }
                }
            }
        }
    });
}

function actualizarGraficaDistribucion(distribucion) {
    const ctx = document.getElementById('grafica-distribucion').getContext('2d');
    
    if (graficaDistribucion) {
        graficaDistribucion.destroy();
    }
    
    graficaDistribucion = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: distribucion.labels,
            datasets: [{
                data: distribucion.valores,
                backgroundColor: [
                    'rgb(59, 130, 246)',
                    'rgb(34, 197, 94)',
                    'rgb(251, 191, 36)',
                    'rgb(168, 85, 247)',
                    'rgb(236, 72, 153)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: document.documentElement.classList.contains('dark') ? '#fff' : '#000'
                    }
                }
            }
        }
    });
}

function formatearNumero(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatearFecha(fecha) {
    const d = new Date(fecha);
    return d.toLocaleDateString('es-HN', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function actualizarDatos() {
    cargarDatos();
}

async function exportarExcel() {
    try {
        let url = `api/contabilidad_exportar.php?periodo=${periodoActual}`;
        if (fechaInicio && fechaFin) {
            url += `&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`;
        }
        
        window.open(url, '_blank');
    } catch (e) {
        console.error('Error exportando:', e);
        alert('Error al exportar datos');
    }
}
</script>
</body>
</html>
