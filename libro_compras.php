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
    <title>Libro de Compras - Rey System</title>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">🛒 Libro de Compras</h1>
            <p class="text-gray-600 dark:text-gray-400">Registro de compras y egresos para declaración al SAR</p>
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
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Total Compras/Egresos</p>
                <p class="text-3xl font-black" id="total-compras">L. 0.00</p>
            </div>
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Número de Egresos</p>
                <p class="text-3xl font-black" id="num-egresos">0</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Promedio por Egreso</p>
                <p class="text-3xl font-black" id="promedio-egreso">L. 0.00</p>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
            <div class="p-6">
                <h3 class="text-xl font-bold mb-4">Detalle de Compras y Egresos</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-[#0d1117]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Fecha</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Concepto</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Tipo</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Usuario</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Monto</th>
                            </tr>
                        </thead>
                        <tbody id="tabla-compras" class="divide-y divide-gray-200 dark:divide-[#324467]"></tbody>
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
    const res = await fetch(`api/libro_compras.php?fecha_inicio=${inicio}&fecha_fin=${fin}`);
    const data = await res.json();
    
    if (data.success) {
        document.getElementById('total-compras').textContent = 'L. ' + formatNum(data.total_compras);
        document.getElementById('num-egresos').textContent = data.num_egresos;
        document.getElementById('promedio-egreso').textContent = 'L. ' + formatNum(data.promedio_egreso);
        
        actualizarTabla(data.egresos);
    }
}

function actualizarTabla(egresos) {
    const tbody = document.getElementById('tabla-compras');
    tbody.innerHTML = egresos.map(e => `
        <tr class="hover:bg-gray-50 dark:hover:bg-[#0d1117]">
            <td class="px-6 py-4">${formatFecha(e.fecha)}</td>
            <td class="px-6 py-4 font-semibold">${e.concepto}</td>
            <td class="px-6 py-4"><span class="px-3 py-1 rounded-full text-xs font-semibold ${getTipoClass(e.tipo)}">${e.tipo}</span></td>
            <td class="px-6 py-4">${e.usuario}</td>
            <td class="px-6 py-4 font-bold text-red-600">L. ${formatNum(e.monto)}</td>
        </tr>
    `).join('');
}

function getTipoClass(tipo) {
    const classes = {
        'Compra': 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'Gasto': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        'Servicio': 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'
    };
    return classes[tipo] || 'bg-gray-100 text-gray-800';
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
    window.open(`api/libro_compras.php?fecha_inicio=${inicio}&fecha_fin=${fin}&export=excel`, '_blank');
}
</script>
</body>
</html>
