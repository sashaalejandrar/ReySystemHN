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
<title>Análisis ABC - Rey System APP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
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
                }
            }
        }
    }
</script>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
    }
</style>
<script src="nova_rey.js"></script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">
    
<!-- Page Heading -->
<div class="flex flex-wrap justify-between gap-4 mb-8">
    <div class="flex flex-col gap-2">
        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">📊 Análisis ABC de Productos</h1>
        <p class="text-gray-500 dark:text-[#92a4c9] text-base">Clasificación de productos por rentabilidad (Principio de Pareto 80/20)</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium mb-2">Fecha Inicio</label>
            <input type="date" id="fecha_inicio" value="<?php echo date('Y-m-01'); ?>" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467] focus:ring-2 focus:ring-primary">
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">Fecha Fin</label>
            <input type="date" id="fecha_fin" value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467] focus:ring-2 focus:ring-primary">
        </div>
        <div class="flex items-end">
            <button onclick="cargarAnalisis()" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                <span class="flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">analytics</span>
                    Generar Análisis
                </span>
            </button>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm">Categoría A</p>
                <p class="text-3xl font-bold" id="stat-a">0</p>
                <p class="text-green-100 text-xs mt-1">80% ingresos</p>
            </div>
            <span class="material-symbols-outlined text-5xl opacity-30">star</span>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm">Categoría B</p>
                <p class="text-3xl font-bold" id="stat-b">0</p>
                <p class="text-blue-100 text-xs mt-1">15% ingresos</p>
            </div>
            <span class="material-symbols-outlined text-5xl opacity-30">trending_up</span>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-orange-100 text-sm">Categoría C</p>
                <p class="text-3xl font-bold" id="stat-c">0</p>
                <p class="text-orange-100 text-xs mt-1">5% ingresos</p>
            </div>
            <span class="material-symbols-outlined text-5xl opacity-30">trending_down</span>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-purple-100 text-sm">Total Ingresos</p>
                <p class="text-2xl font-bold" id="stat-total">L. 0.00</p>
                <p class="text-purple-100 text-xs mt-1">Período seleccionado</p>
            </div>
            <span class="material-symbols-outlined text-5xl opacity-30">payments</span>
        </div>
    </div>
</div>

<!-- Charts -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-4">Distribución por Categoría</h3>
        <canvas id="chartPie"></canvas>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-bold mb-4">Curva de Pareto</h3>
        <canvas id="chartPareto"></canvas>
    </div>
</div>

<!-- Products Table -->
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-200 dark:border-[#324467]">
        <h3 class="text-lg font-bold">Productos Clasificados</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-[#0d1420]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Producto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Código</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Cantidad</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">Ingresos</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">% Ingresos</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider">Categoría</th>
                </tr>
            </thead>
            <tbody id="tabla-productos" class="divide-y divide-gray-200 dark:divide-[#324467]">
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        Selecciona un período y haz clic en "Generar Análisis"
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

</div>
</main>
</div>
</div>

<script>
let chartPie, chartPareto;

document.addEventListener('DOMContentLoaded', function() {
    cargarAnalisis();
});

async function cargarAnalisis() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    
    try {
        const response = await fetch(`api/get_analisis_abc.php?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}`);
        const result = await response.json();
        
        if (result.success) {
            mostrarEstadisticas(result.stats);
            mostrarTabla(result.data);
            mostrarGraficas(result.data, result.stats);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function mostrarEstadisticas(stats) {
    document.getElementById('stat-a').textContent = stats.categoria_a;
    document.getElementById('stat-b').textContent = stats.categoria_b;
    document.getElementById('stat-c').textContent = stats.categoria_c;
    document.getElementById('stat-total').textContent = 'L. ' + parseFloat(stats.total_ingresos).toFixed(2);
}

function mostrarTabla(productos) {
    const tbody = document.getElementById('tabla-productos');
    tbody.innerHTML = '';
    
    if (productos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay datos para el período seleccionado</td></tr>';
        return;
    }
    
    productos.forEach(p => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-[#0d1420]';
        
        let badgeClass = '';
        if (p.categoria_abc === 'A') badgeClass = 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400';
        else if (p.categoria_abc === 'B') badgeClass = 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400';
        else badgeClass = 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400';
        
        tr.innerHTML = `
            <td class="px-6 py-4">${p.Nombre}</td>
            <td class="px-6 py-4 text-gray-500">${p.Codigo}</td>
            <td class="px-6 py-4 text-right">${parseFloat(p.cantidad_vendida).toFixed(0)}</td>
            <td class="px-6 py-4 text-right font-semibold">L. ${parseFloat(p.ingresos_totales).toFixed(2)}</td>
            <td class="px-6 py-4 text-right">${p.porcentaje_ingresos}%</td>
            <td class="px-6 py-4 text-center">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${badgeClass}">
                    ${p.categoria_abc}
                </span>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function mostrarGraficas(productos, stats) {
    // Pie Chart
    const ctxPie = document.getElementById('chartPie').getContext('2d');
    if (chartPie) chartPie.destroy();
    
    chartPie = new Chart(ctxPie, {
        type: 'pie',
        data: {
            labels: ['Categoría A', 'Categoría B', 'Categoría C'],
            datasets: [{
                data: [stats.categoria_a, stats.categoria_b, stats.categoria_c],
                backgroundColor: ['#10b981', '#3b82f6', '#f97316']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
    
    // Pareto Chart
    const ctxPareto = document.getElementById('chartPareto').getContext('2d');
    if (chartPareto) chartPareto.destroy();
    
    const labels = productos.slice(0, 20).map(p => p.Nombre.substring(0, 15));
    const ingresos = productos.slice(0, 20).map(p => p.ingresos_totales);
    const acumulado = productos.slice(0, 20).map(p => p.porcentaje_acumulado);
    
    chartPareto = new Chart(ctxPareto, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                type: 'bar',
                label: 'Ingresos',
                data: ingresos,
                backgroundColor: '#3b82f6',
                yAxisID: 'y'
            }, {
                type: 'line',
                label: '% Acumulado',
                data: acumulado,
                borderColor: '#ef4444',
                backgroundColor: 'transparent',
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { type: 'linear', position: 'left' },
                y1: { type: 'linear', position: 'right', max: 100, grid: { drawOnChartArea: false } }
            }
        }
    });
}
</script>
</body>
</html>
