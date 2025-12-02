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
    <title>Estado de Resultados - Rey System</title>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">📈 Estado de Resultados (P&L)</h1>
            <p class="text-gray-600 dark:text-gray-400">Análisis de ingresos, costos y utilidad neta del período</p>
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
                        <span class="material-symbols-outlined inline text-sm">refresh</span> Actualizar
                    </button>
                </div>
            </div>
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Ingresos Totales</p>
                <p class="text-3xl font-black" id="ingresos">L. 0.00</p>
            </div>
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Gastos Totales</p>
                <p class="text-3xl font-black" id="gastos">L. 0.00</p>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Utilidad Neta</p>
                <p class="text-3xl font-black" id="utilidad">L. 0.00</p>
            </div>
        </div>

        <!-- Estado de Resultados -->
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
            <div class="p-6">
                <h3 class="text-2xl font-bold mb-6">Estado de Resultados</h3>
                
                <!-- Ingresos -->
                <div class="mb-6">
                    <h4 class="text-lg font-bold text-green-600 mb-3">INGRESOS</h4>
                    <div class="space-y-2 ml-4">
                        <div class="flex justify-between py-2 border-b dark:border-gray-700">
                            <span>Ventas</span>
                            <span class="font-semibold" id="ventas-total">L. 0.00</span>
                        </div>
                        <div class="flex justify-between py-2 font-bold text-lg">
                            <span>Total Ingresos</span>
                            <span class="text-green-600" id="total-ingresos">L. 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Costos -->
                <div class="mb-6">
                    <h4 class="text-lg font-bold text-orange-600 mb-3">COSTO DE MERCANCÍA VENDIDA</h4>
                    <div class="space-y-2 ml-4">
                        <div class="flex justify-between py-2 border-b dark:border-gray-700">
                            <span>Costo de Productos Vendidos</span>
                            <span class="font-semibold" id="costo-productos">L. 0.00</span>
                        </div>
                        <div class="flex justify-between py-2 font-bold">
                            <span>Utilidad Bruta</span>
                            <span class="text-blue-600" id="utilidad-bruta">L. 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Gastos Operativos -->
                <div class="mb-6">
                    <h4 class="text-lg font-bold text-red-600 mb-3">GASTOS OPERATIVOS</h4>
                    <div class="space-y-2 ml-4">
                        <div class="flex justify-between py-2 border-b dark:border-gray-700">
                            <span>Egresos de Caja</span>
                            <span class="font-semibold" id="egresos-total">L. 0.00</span>
                        </div>
                        <div class="flex justify-between py-2 font-bold text-lg">
                            <span>Total Gastos Operativos</span>
                            <span class="text-red-600" id="total-gastos">L. 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Utilidad Neta -->
                <div class="mt-8 pt-6 border-t-4 border-primary">
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-black">UTILIDAD NETA</span>
                        <span class="text-3xl font-black" id="utilidad-neta">L. 0.00</span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="text-sm text-gray-600">Margen de Utilidad</span>
                        <span class="text-xl font-bold text-primary" id="margen">0%</span>
                    </div>
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
    const res = await fetch(`api/estado_resultados.php?fecha_inicio=${inicio}&fecha_fin=${fin}`);
    const data = await res.json();
    
    if (data.success) {
        document.getElementById('ingresos').textContent = 'L. ' + formatNum(data.total_ingresos);
        document.getElementById('gastos').textContent = 'L. ' + formatNum(data.total_gastos);
        document.getElementById('utilidad').textContent = 'L. ' + formatNum(data.utilidad_neta);
        
        document.getElementById('ventas-total').textContent = 'L. ' + formatNum(data.ventas);
        document.getElementById('total-ingresos').textContent = 'L. ' + formatNum(data.total_ingresos);
        document.getElementById('costo-productos').textContent = 'L. ' + formatNum(data.costo_productos);
        document.getElementById('utilidad-bruta').textContent = 'L. ' + formatNum(data.utilidad_bruta);
        document.getElementById('egresos-total').textContent = 'L. ' + formatNum(data.egresos);
        document.getElementById('total-gastos').textContent = 'L. ' + formatNum(data.total_gastos);
        document.getElementById('utilidad-neta').textContent = 'L. ' + formatNum(data.utilidad_neta);
        document.getElementById('margen').textContent = data.margen_utilidad.toFixed(2) + '%';
        
        // Color según utilidad
        const utilidadEl = document.getElementById('utilidad-neta');
        utilidadEl.className = data.utilidad_neta >= 0 ? 'text-3xl font-black text-green-600' : 'text-3xl font-black text-red-600';
    }
}

function formatNum(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>
</body>
</html>
