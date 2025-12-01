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
    <title>Declaración ISV - Rey System</title>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">📋 Declaración ISV (15%)</h1>
            <p class="text-gray-600 dark:text-gray-400">Cálculo mensual del Impuesto Sobre Ventas para declaración al SAR</p>
        </div>

        <!-- Filtro -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <div class="flex gap-4 items-center">
                <label class="font-semibold">Mes:</label>
                <select id="mes" class="px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600">
                    <?php 
                    $meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                    for($i=1; $i<=12; $i++): ?>
                    <option value="<?=$i?>" <?=$i==date('n')?'selected':''?>><?=$meses[$i-1]?></option>
                    <?php endfor; ?>
                </select>
                <label class="font-semibold">Año:</label>
                <select id="anio" class="px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600">
                    <?php for($i = date('Y'); $i >= 2020; $i--): ?>
                    <option value="<?=$i?>" <?=$i==date('Y')?'selected':''?>><?=$i?></option>
                    <?php endfor; ?>
                </select>
                <button onclick="cargarDatos()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 font-semibold">
                    <span class="material-symbols-outlined inline text-sm">calculate</span> Calcular
                </button>
            </div>
        </div>

        <!-- Resumen ISV -->
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden mb-6">
            <div class="p-6">
                <h3 class="text-2xl font-bold mb-6">Declaración Mensual de ISV</h3>
                
                <!-- ISV Cobrado -->
                <div class="mb-6">
                    <h4 class="text-lg font-bold text-green-600 mb-3">ISV COBRADO (Ventas)</h4>
                    <div class="space-y-2 ml-4">
                        <div class="flex justify-between py-2 border-b dark:border-gray-700">
                            <span>Total Ventas del Mes</span>
                            <span class="font-semibold" id="total-ventas">L. 0.00</span>
                        </div>
                        <div class="flex justify-between py-2 border-b dark:border-gray-700">
                            <span>Base Imponible (Ventas / 1.15)</span>
                            <span class="font-semibold" id="base-imponible">L. 0.00</span>
                        </div>
                        <div class="flex justify-between py-2 font-bold text-lg">
                            <span>ISV Cobrado (15%)</span>
                            <span class="text-green-600" id="isv-cobrado">L. 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- ISV Pagado -->
                <div class="mb-6">
                    <h4 class="text-lg font-bold text-orange-600 mb-3">ISV PAGADO (Compras)</h4>
                    <div class="space-y-2 ml-4">
                        <div class="flex justify-between py-2 border-b dark:border-gray-700">
                            <span>Total Compras del Mes</span>
                            <span class="font-semibold" id="total-compras">L. 0.00</span>
                        </div>
                        <div class="flex justify-between py-2 font-bold text-lg">
                            <span>ISV Pagado (15%)</span>
                            <span class="text-orange-600" id="isv-pagado">L. 0.00</span>
                        </div>
                    </div>
                </div>

                <!-- ISV a Pagar -->
                <div class="mt-8 pt-6 border-t-4 border-primary">
                    <div class="flex justify-between items-center">
                        <span class="text-2xl font-black">ISV A PAGAR AL SAR</span>
                        <span class="text-4xl font-black" id="isv-pagar">L. 0.00</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">* Nota: Las ventas actualmente no tienen ISV incluido. Este cálculo es estimado.</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => cargarDatos());

async function cargarDatos() {
    const mes = document.getElementById('mes').value;
    const anio = document.getElementById('anio').value;
    const res = await fetch(`api/declaracion_isv.php?mes=${mes}&anio=${anio}`);
    const data = await res.json();
    
    if (data.success) {
        document.getElementById('total-ventas').textContent = 'L. ' + formatNum(data.total_ventas);
        document.getElementById('base-imponible').textContent = 'L. ' + formatNum(data.base_imponible);
        document.getElementById('isv-cobrado').textContent = 'L. ' + formatNum(data.isv_cobrado);
        document.getElementById('total-compras').textContent = 'L. ' + formatNum(data.total_compras);
        document.getElementById('isv-pagado').textContent = 'L. ' + formatNum(data.isv_pagado);
        
        const isvPagar = document.getElementById('isv-pagar');
        isvPagar.textContent = 'L. ' + formatNum(data.isv_a_pagar);
        isvPagar.className = data.isv_a_pagar >= 0 ? 'text-4xl font-black text-red-600' : 'text-4xl font-black text-green-600';
    }
}

function formatNum(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}
</script>
</body>
</html>
