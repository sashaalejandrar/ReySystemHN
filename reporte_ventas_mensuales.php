<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) die("Error de conexión");

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
    <title>Reporte Ventas Mensuales - Rey System</title>
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
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="flex h-screen">
    <?php include 'menu_lateral.php'; ?>
    <main class="flex-1 overflow-auto p-8">
        <div class="mb-8">
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">📊 Reporte de Ventas Mensuales</h1>
            <p class="text-gray-600 dark:text-gray-400">Análisis detallado de ventas por mes y método de pago</p>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <div class="flex gap-4 items-center">
                <label class="font-semibold">Año:</label>
                <select id="anio" class="px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600">
                    <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                    <option value="<?=$i?>" <?=$i==date('Y')?'selected':''?>><?=$i?></option>
                    <?php endfor; ?>
                </select>
                <button onclick="cargarDatos()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 font-semibold">Actualizar</button>
                <button onclick="exportarExcel()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold ml-auto">
                    <span class="material-symbols-outlined inline">download</span> Exportar
                </button>
            </div>
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Total Anual</p>
                <p class="text-3xl font-black" id="total-anual">L. 0.00</p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Promedio Mensual</p>
                <p class="text-3xl font-black" id="promedio-mensual">L. 0.00</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Mejor Mes</p>
                <p class="text-3xl font-black" id="mejor-mes">-</p>
            </div>
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Total Ventas</p>
                <p class="text-3xl font-black" id="total-ventas">0</p>
            </div>
        </div>

        <!-- Gráfica -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <h3 class="text-xl font-bold mb-4">Tendencia Mensual</h3>
            <canvas id="grafica-ventas"></canvas>
        </div>

        <!-- Tabla -->
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
            <div class="p-6">
                <h3 class="text-xl font-bold mb-4">Desglose por Mes</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-[#0d1117]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Mes</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Efectivo</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Tarjeta</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Transferencia</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Total</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase"># Ventas</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-ventas" class="divide-y divide-gray-200 dark:divide-[#324467]"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
let grafica = null;

document.addEventListener('DOMContentLoaded', () => cargarDatos());

async function cargarDatos() {
    const anio = document.getElementById('anio').value;
    const res = await fetch(`api/reporte_ventas_mensuales.php?anio=${anio}`);
    const data = await res.json();
    
    if (data.success) {
        document.getElementById('total-anual').textContent = 'L. ' + formatNum(data.total_anual);
        document.getElementById('promedio-mensual').textContent = 'L. ' + formatNum(data.promedio_mensual);
        document.getElementById('mejor-mes').textContent = data.mejor_mes;
        document.getElementById('total-ventas').textContent = data.total_ventas;
        
        actualizarGrafica(data.meses);
        actualizarTabla(data.meses);
    }
}

function actualizarGrafica(meses) {
    const ctx = document.getElementById('grafica-ventas').getContext('2d');
    if (grafica) grafica.destroy();
    
    grafica = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: meses.map(m => m.nombre),
            datasets: [{
                label: 'Ventas Mensuales',
                data: meses.map(m => m.total),
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: document.documentElement.classList.contains('dark') ? '#fff' : '#000' }}
            },
            scales: {
                y: { beginAtZero: true, ticks: { color: '#9ca3af' }, grid: { color: '#374151' }},
                x: { ticks: { color: '#9ca3af' }, grid: { color: '#374151' }}
            }
        }
    });
}

function actualizarTabla(meses) {
    const tbody = document.getElementById('tabla-ventas');
    tbody.innerHTML = meses.map(m => `
        <tr class="hover:bg-gray-50 dark:hover:bg-[#0d1117]">
            <td class="px-6 py-4 font-semibold">${m.nombre}</td>
            <td class="px-6 py-4">L. ${formatNum(m.efectivo)}</td>
            <td class="px-6 py-4">L. ${formatNum(m.tarjeta)}</td>
            <td class="px-6 py-4">L. ${formatNum(m.transferencia)}</td>
            <td class="px-6 py-4 font-bold text-green-600">L. ${formatNum(m.total)}</td>
            <td class="px-6 py-4">${m.cantidad}</td>
        </tr>
    `).join('');
}

function formatNum(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function exportarExcel() {
    const anio = document.getElementById('anio').value;
    window.open(`api/reporte_ventas_mensuales.php?anio=${anio}&export=excel`, '_blank');
}
</script>
</body>
</html>
