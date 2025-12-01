<?php
session_start();
require_once '../../db_connect.php';

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}

$usuario = $_SESSION['usuario'];
$rol = $_SESSION['rol'] ?? '';
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard Analítico - ReySystem</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-4px);
        }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900">

<!-- Header -->
<div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-6 py-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="../index.php" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">📊 Dashboard Analítico</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">Análisis en tiempo real de tu negocio</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="refreshAllCharts()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                <span class="material-symbols-outlined">refresh</span>
                Actualizar
            </button>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="p-6 space-y-6">

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Ventas Hoy -->
        <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="material-symbols-outlined text-4xl opacity-80">shopping_cart</span>
                <span class="text-sm opacity-80">Hoy</span>
            </div>
            <div class="text-3xl font-bold" id="ventas_hoy_total">L0</div>
            <div class="text-sm opacity-90" id="ventas_hoy_cantidad">0 ventas</div>
        </div>

        <!-- Ventas Mes -->
        <div class="stat-card bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="material-symbols-outlined text-4xl opacity-80">trending_up</span>
                <span class="text-sm opacity-80">Este Mes</span>
            </div>
            <div class="text-3xl font-bold" id="ventas_mes_total">L0</div>
            <div class="text-sm opacity-90" id="ventas_mes_cantidad">0 ventas</div>
        </div>

        <!-- Productos Total -->
        <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="material-symbols-outlined text-4xl opacity-80">inventory_2</span>
                <span class="text-sm opacity-80">Inventario</span>
            </div>
            <div class="text-3xl font-bold" id="productos_total">0</div>
            <div class="text-sm opacity-90">Productos</div>
        </div>

        <!-- Stock Bajo -->
        <div class="stat-card bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="material-symbols-outlined text-4xl opacity-80">warning</span>
                <span class="text-sm opacity-80">Alerta</span>
            </div>
            <div class="text-3xl font-bold" id="productos_bajo_stock">0</div>
            <div class="text-sm opacity-90">Stock Bajo</div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Ventas por Día -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">show_chart</span>
                Ventas Últimos 30 Días
            </h3>
            <canvas id="salesByDayChart"></canvas>
        </div>

        <!-- Ingresos vs Gastos -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-green-600">account_balance</span>
                Ingresos vs Gastos (Mes Actual)
            </h3>
            <canvas id="incomeExpensesChart"></canvas>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Productos -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-purple-600">star</span>
                Top 10 Productos Más Vendidos
            </h3>
            <canvas id="topProductsChart"></canvas>
        </div>

        <!-- Stock Bajo -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-orange-600">inventory</span>
                Productos con Stock Bajo
            </h3>
            <canvas id="lowStockChart"></canvas>
        </div>
    </div>

    <!-- Ventas por Hora -->
    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg">
        <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-blue-600">schedule</span>
            Ventas por Hora (Hoy)
        </h3>
        <canvas id="salesByHourChart"></canvas>
    </div>

</div>

<script>
// Configuración global de Chart.js
Chart.defaults.color = '#94a3b8';
Chart.defaults.borderColor = '#334155';

let charts = {};

// Cargar estadísticas resumen
async function loadStatsSummary() {
    try {
        const response = await fetch('../../api/analytics/data.php?action=stats_summary');
        const data = await response.json();
        
        document.getElementById('ventas_hoy_total').textContent = 'L ' + data.ventas_hoy.total.toLocaleString();
        document.getElementById('ventas_hoy_cantidad').textContent = data.ventas_hoy.cantidad + ' ventas';
        
        document.getElementById('ventas_mes_total').textContent = 'L ' + data.ventas_mes.total.toLocaleString();
        document.getElementById('ventas_mes_cantidad').textContent = data.ventas_mes.cantidad + ' ventas';
        
        document.getElementById('productos_total').textContent = data.productos_total;
        document.getElementById('productos_bajo_stock').textContent = data.productos_bajo_stock;
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

// Gráfico de Ventas por Día
async function loadSalesByDay() {
    try {
        const response = await fetch('../../api/analytics/data.php?action=sales_by_day');
        const data = await response.json();
        
        const ctx = document.getElementById('salesByDayChart').getContext('2d');
        
        if (charts.salesByDay) charts.salesByDay.destroy();
        
        charts.salesByDay = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.map(d => new Date(d.fecha).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' })),
                datasets: [{
                    label: 'Ventas',
                    data: data.map(d => d.total),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => 'L ' + value.toLocaleString()
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error loading sales by day:', error);
    }
}

// Gráfico de Ingresos vs Gastos
async function loadIncomeExpenses() {
    try {
        const response = await fetch('../../api/analytics/data.php?action=income_vs_expenses');
        const data = await response.json();
        
        const ctx = document.getElementById('incomeExpensesChart').getContext('2d');
        
        if (charts.incomeExpenses) charts.incomeExpenses.destroy();
        
        charts.incomeExpenses = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Ingresos', 'Gastos', 'Ganancia'],
                datasets: [{
                    data: [data.ingresos, data.gastos, data.ganancia],
                    backgroundColor: [
                        'rgba(34, 197, 94, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(59, 130, 246, 0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    } catch (error) {
        console.error('Error loading income/expenses:', error);
    }
}

// Gráfico de Top Productos
async function loadTopProducts() {
    try {
        const response = await fetch('../../api/analytics/data.php?action=top_products');
        const data = await response.json();
        
        const ctx = document.getElementById('topProductsChart').getContext('2d');
        
        if (charts.topProducts) charts.topProducts.destroy();
        
        charts.topProducts = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.nombre.substring(0, 20)),
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: data.map(d => d.cantidad),
                    backgroundColor: 'rgba(168, 85, 247, 0.8)',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    } catch (error) {
        console.error('Error loading top products:', error);
    }
}

// Gráfico de Stock Bajo
async function loadLowStock() {
    try {
        const response = await fetch('../../api/analytics/data.php?action=low_stock');
        const data = await response.json();
        
        const ctx = document.getElementById('lowStockChart').getContext('2d');
        
        if (charts.lowStock) charts.lowStock.destroy();
        
        charts.lowStock = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.nombre.substring(0, 20)),
                datasets: [{
                    label: 'Stock',
                    data: data.map(d => d.stock),
                    backgroundColor: 'rgba(249, 115, 22, 0.8)',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error loading low stock:', error);
    }
}

// Gráfico de Ventas por Hora
async function loadSalesByHour() {
    try {
        const response = await fetch('../../api/analytics/data.php?action=sales_by_hour');
        const data = await response.json();
        
        const ctx = document.getElementById('salesByHourChart').getContext('2d');
        
        if (charts.salesByHour) charts.salesByHour.destroy();
        
        charts.salesByHour = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.hora + ':00'),
                datasets: [{
                    label: 'Total Ventas',
                    data: data.map(d => d.total),
                    backgroundColor: 'rgba(59, 130, 246, 0.8)',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => 'L ' + value.toLocaleString()
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error loading sales by hour:', error);
    }
}

// Actualizar todos los gráficos
function refreshAllCharts() {
    loadStatsSummary();
    loadSalesByDay();
    loadIncomeExpenses();
    loadTopProducts();
    loadLowStock();
    loadSalesByHour();
}

// Cargar al inicio
document.addEventListener('DOMContentLoaded', refreshAllCharts);

// Auto-refresh cada 5 minutos
setInterval(refreshAllCharts, 300000);
</script>

</body>
</html>
