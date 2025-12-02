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
    <title>Conciliación Bancaria - Rey System</title>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">🏦 Conciliación Bancaria</h1>
            <p class="text-gray-600 dark:text-gray-400">Comparación entre registros de caja y movimientos bancarios</p>
        </div>

        <!-- Filtro -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <div class="flex gap-4 items-center">
                <label class="font-semibold">Fecha:</label>
                <input type="date" id="fecha" class="px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" value="<?=date('Y-m-d')?>">
                <button onclick="cargarDatos()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 font-semibold">
                    <span class="material-symbols-outlined inline text-sm">sync</span> Conciliar
                </button>
            </div>
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Saldo en Caja</p>
                <p class="text-3xl font-black" id="saldo-caja">L. 0.00</p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Total Ventas</p>
                <p class="text-3xl font-black" id="total-ventas">L. 0.00</p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Total Egresos</p>
                <p class="text-3xl font-black" id="total-egresos">L. 0.00</p>
            </div>
        </div>

        <!-- Conciliación -->
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
            <div class="p-6">
                <h3 class="text-2xl font-bold mb-6">Conciliación del Día</h3>
                
                <div class="space-y-4">
                    <div class="flex justify-between py-3 border-b dark:border-gray-700">
                        <span class="font-semibold">Saldo Inicial (Apertura)</span>
                        <span class="text-lg" id="saldo-inicial">L. 0.00</span>
                    </div>
                    <div class="flex justify-between py-3 border-b dark:border-gray-700 text-green-600">
                        <span class="font-semibold">+ Ingresos (Ventas)</span>
                        <span class="text-lg" id="ingresos">L. 0.00</span>
                    </div>
                    <div class="flex justify-between py-3 border-b dark:border-gray-700 text-red-600">
                        <span class="font-semibold">- Egresos (Gastos)</span>
                        <span class="text-lg" id="egresos">L. 0.00</span>
                    </div>
                    <div class="flex justify-between py-3 font-bold text-xl pt-4 border-t-2 border-primary">
                        <span>Saldo Final Esperado</span>
                        <span id="saldo-esperado">L. 0.00</span>
                    </div>
                    <div class="flex justify-between py-3 font-bold text-xl">
                        <span>Saldo Final Real (Cierre)</span>
                        <span id="saldo-real">L. 0.00</span>
                    </div>
                    <div class="flex justify-between py-3 font-black text-2xl pt-4 border-t-4 border-primary">
                        <span>Diferencia</span>
                        <span id="diferencia">L. 0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => cargarDatos());

async function cargarDatos() {
    const fecha = document.getElementById('fecha').value;
    const res = await fetch(`api/conciliacion_bancaria.php?fecha=${fecha}`);
    const data = await res.json();
    
    if (data.success) {
        document.getElementById('saldo-caja').textContent = 'L. ' + formatNum(data.saldo_caja);
        document.getElementById('total-ventas').textContent = 'L. ' + formatNum(data.total_ventas);
        document.getElementById('total-egresos').textContent = 'L. ' + formatNum(data.total_egresos);
        
        document.getElementById('saldo-inicial').textContent = 'L. ' + formatNum(data.saldo_inicial);
        document.getElementById('ingresos').textContent = 'L. ' + formatNum(data.ingresos);
        document.getElementById('egresos').textContent = 'L. ' + formatNum(data.egresos);
        document.getElementById('saldo-esperado').textContent = 'L. ' + formatNum(data.saldo_esperado);
        document.getElementById('saldo-real').textContent = 'L. ' + formatNum(data.saldo_real);
        
        const difEl = document.getElementById('diferencia');
        difEl.textContent = 'L. ' + formatNum(Math.abs(data.diferencia));
        difEl.className = data.diferencia >= 0 ? 'text-2xl font-black text-green-600' : 'text-2xl font-black text-red-600';
    }
}

function formatNum(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>
</body>
</html>
