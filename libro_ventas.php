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
    <title>Libro de Ventas - Rey System</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">📖 Libro de Ventas</h1>
            <p class="text-gray-600 dark:text-gray-400">Registro completo de facturas emitidas para declaración al SAR</p>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Fecha Inicio</label>
                    <input type="date" id="fecha-inicio" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" value="<?=date('Y-m-01')?>">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Fecha Fin</label>
                    <input type="date" id="fecha-fin" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" value="<?=date('Y-m-d')?>">
                </div>
                <div class="flex items-end">
                    <button onclick="cargarDatos()" class="w-full px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 font-semibold">
                        <span class="material-symbols-outlined inline text-sm">search</span> Buscar
                    </button>
                </div>
                <div class="flex items-end">
                    <button onclick="exportarExcel()" class="w-full px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                        <span class="material-symbols-outlined inline text-sm">download</span> Exportar
                    </button>
                </div>
            </div>
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Total Ventas</p>
                <p class="text-3xl font-black" id="total-ventas">L. 0.00</p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Facturas Emitidas</p>
                <p class="text-3xl font-black" id="num-facturas">0</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Promedio por Factura</p>
                <p class="text-3xl font-black" id="promedio-factura">L. 0.00</p>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
            <div class="p-6">
                <h3 class="text-xl font-bold mb-4">Detalle de Facturas</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-[#0d1117]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Factura</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Fecha</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cliente</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Método Pago</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Subtotal</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">ISV (15%)</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Total</th>
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
document.addEventListener('DOMContentLoaded', () => cargarDatos());

async function cargarDatos() {
    const inicio = document.getElementById('fecha-inicio').value;
    const fin = document.getElementById('fecha-fin').value;
    const res = await fetch(`api/libro_ventas.php?fecha_inicio=${inicio}&fecha_fin=${fin}`);
    const data = await res.json();
    
    if (data.success) {
        document.getElementById('total-ventas').textContent = 'L. ' + formatNum(data.total_ventas);
        document.getElementById('num-facturas').textContent = data.num_facturas;
        document.getElementById('promedio-factura').textContent = 'L. ' + formatNum(data.promedio_factura);
        
        actualizarTabla(data.ventas);
    }
}

function actualizarTabla(ventas) {
    const tbody = document.getElementById('tabla-ventas');
    tbody.innerHTML = ventas.map(v => {
        const subtotal = v.total / 1.15; // Asumiendo ISV del 15%
        const isv = v.total - subtotal;
        return `
        <tr class="hover:bg-gray-50 dark:hover:bg-[#0d1117]">
            <td class="px-6 py-4 font-semibold">${v.factura}</td>
            <td class="px-6 py-4">${formatFecha(v.fecha)}</td>
            <td class="px-6 py-4">${v.cliente}</td>
            <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-semibold ${getMetodoPagoClass(v.metodo_pago)}">${v.metodo_pago}</span></td>
            <td class="px-6 py-4">L. ${formatNum(subtotal)}</td>
            <td class="px-6 py-4 text-orange-600">L. ${formatNum(isv)}</td>
            <td class="px-6 py-4 font-bold text-green-600">L. ${formatNum(v.total)}</td>
        </tr>
    `}).join('');
}

function getMetodoPagoClass(metodo) {
    const classes = {
        'Efectivo': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'Tarjeta': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'Transferencia': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'
    };
    return classes[metodo] || 'bg-gray-100 text-gray-800';
}

function formatNum(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatFecha(fecha) {
    return new Date(fecha).toLocaleDateString('es-HN');
}

function exportarExcel() {
    const inicio = document.getElementById('fecha-inicio').value;
    const fin = document.getElementById('fecha-fin').value;
    window.open(`api/libro_ventas.php?fecha_inicio=${inicio}&fecha_fin=${fin}&export=excel`, '_blank');
}
</script>
</body>
</html>
