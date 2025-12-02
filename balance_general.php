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
    <title>Balance General - Rey System</title>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">⚖️ Balance General</h1>
            <p class="text-gray-600 dark:text-gray-400">Estado de situación financiera: Activos, Pasivos y Patrimonio</p>
        </div>

        <!-- Filtro -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <div class="flex gap-4 items-center">
                <label class="font-semibold">Fecha de Corte:</label>
                <input type="date" id="fecha-corte" class="px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" value="<?=date('Y-m-d')?>">
                <button onclick="cargarDatos()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 font-semibold">
                    <span class="material-symbols-outlined inline text-sm">refresh</span> Actualizar
                </button>
            </div>
        </div>

        <!-- Ecuación Contable -->
        <div class="bg-gradient-to-r from-primary to-blue-600 rounded-xl p-6 mb-6 text-white">
            <div class="flex justify-between items-center">
                <div class="text-center flex-1">
                    <p class="text-sm opacity-90">ACTIVOS</p>
                    <p class="text-2xl font-black" id="total-activos-header">L. 0.00</p>
                </div>
                <div class="text-3xl font-black">=</div>
                <div class="text-center flex-1">
                    <p class="text-sm opacity-90">PASIVOS</p>
                    <p class="text-2xl font-black" id="total-pasivos-header">L. 0.00</p>
                </div>
                <div class="text-3xl font-black">+</div>
                <div class="text-center flex-1">
                    <p class="text-sm opacity-90">PATRIMONIO</p>
                    <p class="text-2xl font-black" id="total-patrimonio-header">L. 0.00</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- ACTIVOS -->
            <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
                <div class="p-6">
                    <h3 class="text-2xl font-bold text-green-600 mb-4">ACTIVOS</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-bold text-lg mb-2">Activos Corrientes</h4>
                            <div class="ml-4 space-y-2">
                                <div class="flex justify-between py-2 border-b dark:border-gray-700">
                                    <span>Efectivo en Caja</span>
                                    <span class="font-semibold" id="efectivo-caja">L. 0.00</span>
                                </div>
                                <div class="flex justify-between py-2 border-b dark:border-gray-700">
                                    <span>Inventario</span>
                                    <span class="font-semibold" id="inventario">L. 0.00</span>
                                </div>
                                <div class="flex justify-between py-2 border-b dark:border-gray-700">
                                    <span>Cuentas por Cobrar</span>
                                    <span class="font-semibold" id="cuentas-cobrar">L. 0.00</span>
                                </div>
                                <div class="flex justify-between py-2 font-bold">
                                    <span>Total Activos Corrientes</span>
                                    <span class="text-green-600" id="total-activos-corrientes">L. 0.00</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t-2 border-green-600">
                            <div class="flex justify-between text-xl font-black">
                                <span>TOTAL ACTIVOS</span>
                                <span class="text-green-600" id="total-activos">L. 0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASIVOS Y PATRIMONIO -->
            <div class="space-y-6">
                <!-- PASIVOS -->
                <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-red-600 mb-4">PASIVOS</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <h4 class="font-bold text-lg mb-2">Pasivos Corrientes</h4>
                                <div class="ml-4 space-y-2">
                                    <div class="flex justify-between py-2 border-b dark:border-gray-700">
                                        <span>Cuentas por Pagar</span>
                                        <span class="font-semibold" id="cuentas-pagar">L. 0.00</span>
                                    </div>
                                    <div class="flex justify-between py-2 font-bold">
                                        <span>Total Pasivos Corrientes</span>
                                        <span class="text-red-600" id="total-pasivos-corrientes">L. 0.00</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="pt-4 border-t-2 border-red-600">
                                <div class="flex justify-between text-xl font-black">
                                    <span>TOTAL PASIVOS</span>
                                    <span class="text-red-600" id="total-pasivos">L. 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PATRIMONIO -->
                <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-blue-600 mb-4">PATRIMONIO</h3>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between py-2 border-b dark:border-gray-700">
                                <span>Capital</span>
                                <span class="font-semibold" id="capital">L. 0.00</span>
                            </div>
                            <div class="flex justify-between py-2 border-b dark:border-gray-700">
                                <span>Utilidades Retenidas</span>
                                <span class="font-semibold" id="utilidades-retenidas">L. 0.00</span>
                            </div>
                            <div class="pt-4 border-t-2 border-blue-600">
                                <div class="flex justify-between text-xl font-black">
                                    <span>TOTAL PATRIMONIO</span>
                                    <span class="text-blue-600" id="total-patrimonio">L. 0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => cargarDatos());

async function cargarDatos() {
    const fecha = document.getElementById('fecha-corte').value;
    const res = await fetch(`api/balance_general.php?fecha=${fecha}`);
    const data = await res.json();
    
    if (data.success) {
        // Activos
        document.getElementById('efectivo-caja').textContent = 'L. ' + formatNum(data.efectivo_caja);
        document.getElementById('inventario').textContent = 'L. ' + formatNum(data.inventario);
        document.getElementById('cuentas-cobrar').textContent = 'L. ' + formatNum(data.cuentas_cobrar);
        document.getElementById('total-activos-corrientes').textContent = 'L. ' + formatNum(data.total_activos_corrientes);
        document.getElementById('total-activos').textContent = 'L. ' + formatNum(data.total_activos);
        document.getElementById('total-activos-header').textContent = 'L. ' + formatNum(data.total_activos);
        
        // Pasivos
        document.getElementById('cuentas-pagar').textContent = 'L. ' + formatNum(data.cuentas_pagar);
        document.getElementById('total-pasivos-corrientes').textContent = 'L. ' + formatNum(data.total_pasivos_corrientes);
        document.getElementById('total-pasivos').textContent = 'L. ' + formatNum(data.total_pasivos);
        document.getElementById('total-pasivos-header').textContent = 'L. ' + formatNum(data.total_pasivos);
        
        // Patrimonio
        document.getElementById('capital').textContent = 'L. ' + formatNum(data.capital);
        document.getElementById('utilidades-retenidas').textContent = 'L. ' + formatNum(data.utilidades_retenidas);
        document.getElementById('total-patrimonio').textContent = 'L. ' + formatNum(data.total_patrimonio);
        document.getElementById('total-patrimonio-header').textContent = 'L. ' + formatNum(data.total_patrimonio);
    }
}

function formatNum(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>
</body>
</html>
