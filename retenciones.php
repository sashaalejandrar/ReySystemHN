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
    <title>Retenciones - Rey System</title>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">💰 Control de Retenciones</h1>
            <p class="text-gray-600 dark:text-gray-400">Registro de retenciones del 1% y 1.5% según normativa del SAR</p>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
            </div>
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Total Retenciones Calculadas</p>
                <p class="text-3xl font-black" id="total-retenciones">L. 0.00</p>
                <p class="text-xs opacity-75 mt-2">Basado en ventas del período</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Número de Transacciones</p>
                <p class="text-3xl font-black" id="num-transacciones">0</p>
            </div>
        </div>

        <!-- Información -->
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 mb-6">
            <h3 class="font-bold text-blue-900 dark:text-blue-300 mb-2">ℹ️ Información sobre Retenciones</h3>
            <ul class="text-sm text-blue-800 dark:text-blue-400 space-y-1">
                <li>• <strong>Retención 1%:</strong> Aplica a ventas mayores a L. 1,000</li>
                <li>• <strong>Retención 1.5%:</strong> Aplica a servicios profesionales</li>
                <li>• Este módulo calcula retenciones estimadas basadas en las ventas registradas</li>
                <li>• Consulte con su contador para determinar qué transacciones aplican retención</li>
            </ul>
        </div>

        <!-- Tabla -->
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
            <div class="p-6">
                <h3 class="text-xl font-bold mb-4">Detalle de Retenciones Estimadas</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-[#0d1117]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Fecha</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Factura</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cliente</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Monto Venta</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">% Retención</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Retención</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-retenciones" class="divide-y divide-gray-200 dark:divide-[#324467]"></tbody>
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
    const res = await fetch(`api/retenciones.php?fecha_inicio=${inicio}&fecha_fin=${fin}`);
    const data = await res.json();
    
    if (data.success) {
        document.getElementById('total-retenciones').textContent = 'L. ' + formatNum(data.total_retenciones);
        document.getElementById('num-transacciones').textContent = data.num_transacciones;
        
        actualizarTabla(data.retenciones);
    }
}

function actualizarTabla(retenciones) {
    const tbody = document.getElementById('tabla-retenciones');
    tbody.innerHTML = retenciones.map(r => `
        <tr class="hover:bg-gray-50 dark:hover:bg-[#0d1117]">
            <td class="px-6 py-4">${formatFecha(r.fecha)}</td>
            <td class="px-6 py-4 font-semibold">${r.factura}</td>
            <td class="px-6 py-4">${r.cliente}</td>
            <td class="px-6 py-4">L. ${formatNum(r.monto)}</td>
            <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">${r.porcentaje}%</span></td>
            <td class="px-6 py-4 font-bold text-orange-600">L. ${formatNum(r.retencion)}</td>
        </tr>
    `).join('');
}

function formatNum(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function formatFecha(fecha) {
    return new Date(fecha).toLocaleDateString('es-HN');
}
</script>
</body>
</html>
