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

if (!in_array($rol_usuario, ['admin', 'cajero/gerente'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Reportes de Pedidos - Rey System</title>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">📊 Reportes de Pedidos</h1>
            <p class="text-gray-600 dark:text-gray-400">Análisis y estadísticas de pedidos</p>
        </div>

        <!-- Estadísticas Generales -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Total Pedidos</p>
                <p class="text-3xl font-black" id="total-pedidos">0</p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Completados</p>
                <p class="text-3xl font-black" id="total-completados">0</p>
            </div>
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Pendientes</p>
                <p class="text-3xl font-black" id="total-pendientes">0</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Valor Total Est.</p>
                <p class="text-3xl font-black" id="valor-total">L. 0</p>
            </div>
        </div>

        <!-- Gráficas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Pedidos por Estado -->
            <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                <h3 class="text-xl font-bold mb-4">Pedidos por Estado</h3>
                <canvas id="chartEstados"></canvas>
            </div>

            <!-- Tendencia Mensual -->
            <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
                <h3 class="text-xl font-bold mb-4">Tendencia Mensual</h3>
                <canvas id="chartTendencia"></canvas>
            </div>
        </div>

        <!-- Productos Más Solicitados -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <h3 class="text-xl font-bold mb-4">Top 10 Productos Más Solicitados</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-[#0d1117]">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">#</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Producto</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cantidad Pedidos</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cantidad Total</th>
                        </tr>
                    </thead>
                    <tbody id="tablaProductos" class="divide-y divide-gray-200 dark:divide-[#324467]"></tbody>
                </table>
            </div>
        </div>

        <!-- Clientes Frecuentes -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
            <h3 class="text-xl font-bold mb-4">Top 10 Clientes Frecuentes</h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-[#0d1117]">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">#</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cliente</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Teléfono</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Total Pedidos</th>
                        </tr>
                    </thead>
                    <tbody id="tablaClientes" class="divide-y divide-gray-200 dark:divide-[#324467]"></tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script>
let chartEstados, chartTendencia;

document.addEventListener('DOMContentLoaded', () => {
    cargarEstadisticas();
});

async function cargarEstadisticas() {
    const res = await fetch('api/pedidos_stats.php');
    const data = await res.json();
    
    if (data.success) {
        // Estadísticas generales
        document.getElementById('total-pedidos').textContent = data.total_pedidos;
        document.getElementById('total-completados').textContent = data.total_completados;
        document.getElementById('total-pendientes').textContent = data.total_pendientes;
        document.getElementById('valor-total').textContent = 'L. ' + formatNum(data.valor_total);
        
        // Gráfica de estados
        crearGraficaEstados(data.por_estado);
        
        // Gráfica de tendencia
        crearGraficaTendencia(data.tendencia_mensual);
        
        // Productos más solicitados
        actualizarTablaProductos(data.productos_top);
        
        // Clientes frecuentes
        actualizarTablaClientes(data.clientes_top);
    }
}

function crearGraficaEstados(datos) {
    const ctx = document.getElementById('chartEstados');
    if (chartEstados) chartEstados.destroy();
    
    chartEstados = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: Object.keys(datos),
            datasets: [{
                data: Object.values(datos),
                backgroundColor: [
                    'rgba(249, 115, 22, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {color: '#9ca3af'}
                }
            }
        }
    });
}

function crearGraficaTendencia(datos) {
    const ctx = document.getElementById('chartTendencia');
    if (chartTendencia) chartTendencia.destroy();
    
    chartTendencia = new Chart(ctx, {
        type: 'line',
        data: {
            labels: datos.map(d => d.mes),
            datasets: [{
                label: 'Pedidos',
                data: datos.map(d => d.total),
                borderColor: 'rgba(59, 130, 246, 1)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    labels: {color: '#9ca3af'}
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {color: '#9ca3af'}
                },
                x: {
                    ticks: {color: '#9ca3af'}
                }
            }
        }
    });
}

function actualizarTablaProductos(productos) {
    const tbody = document.getElementById('tablaProductos');
    tbody.innerHTML = productos.map((p, i) => `
        <tr class="hover:bg-gray-50 dark:hover:bg-[#0d1117]">
            <td class="px-6 py-4 font-bold">${i + 1}</td>
            <td class="px-6 py-4">${p.producto}</td>
            <td class="px-6 py-4">${p.num_pedidos}</td>
            <td class="px-6 py-4 font-semibold">${p.cantidad_total}</td>
        </tr>
    `).join('');
}

function actualizarTablaClientes(clientes) {
    const tbody = document.getElementById('tablaClientes');
    tbody.innerHTML = clientes.map((c, i) => `
        <tr class="hover:bg-gray-50 dark:hover:bg-[#0d1117]">
            <td class="px-6 py-4 font-bold">${i + 1}</td>
            <td class="px-6 py-4">${c.cliente}</td>
            <td class="px-6 py-4">${c.telefono || '-'}</td>
            <td class="px-6 py-4 font-semibold">${c.total_pedidos}</td>
        </tr>
    `).join('');
}

function formatNum(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>
</body>
</html>
