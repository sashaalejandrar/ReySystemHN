<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Actualización de Stock en Lote - Rey System APP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
            },
        },
    }
</script>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    
    /* Radio buttons modernos */
    .radio-card {
        transition: all 0.3s ease;
    }
    .radio-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .radio-card input:checked + .radio-content {
        border-color: #1152d4;
        background: linear-gradient(135deg, rgba(17, 82, 212, 0.1), rgba(17, 82, 212, 0.05));
    }
    
    /* Sugerencias modernas y hermosas */
    .suggestions-container {
        position: absolute;
        z-index: 1000;
        width: 100%;
        max-height: 350px;
        overflow-y: auto;
        background: linear-gradient(to bottom, #ffffff, #fafafa);
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 
                    0 8px 10px -6px rgba(0, 0, 0, 0.1),
                    0 0 0 1px rgba(0, 0, 0, 0.05);
        margin-top: 8px;
        animation: slideDownFade 0.2s ease-out;
        backdrop-filter: blur(10px);
    }
    
    .dark .suggestions-container {
        background: linear-gradient(to bottom, #1f2937, #111827);
        border-color: #374151;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 
                    0 8px 10px -6px rgba(0, 0, 0, 0.3),
                    0 0 0 1px rgba(255, 255, 255, 0.05);
    }
    
    @keyframes slideDownFade {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .suggestion-item-modern {
        border-bottom: 1px solid #f3f4f6;
    }
    
    .dark .suggestion-item-modern {
        border-bottom-color: #374151;
    }
    
    .suggestion-item-modern:last-child {
        border-bottom: none;
    }
    
    .suggestion-item-modern:first-child > div {
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }
    
    .suggestion-item-modern:last-child > div {
        border-bottom-left-radius: 12px;
        border-bottom-right-radius: 12px;
    }
    
    /* Scrollbar personalizado hermoso */
    .suggestions-container::-webkit-scrollbar {
        width: 6px;
    }
    
    .suggestions-container::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .suggestions-container::-webkit-scrollbar-thumb {
        background: linear-gradient(to bottom, #3b82f6, #8b5cf6);
        border-radius: 10px;
    }
    
    .suggestions-container::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(to bottom, #2563eb, #7c3aed);
    }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">

<!-- Page Heading -->
<div class="flex flex-wrap justify-between gap-4 mb-8">
    <div class="flex flex-col gap-2">
        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">
            🔄 Actualización de Stock en Lote
        </h1>
        <p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">
            Actualiza el inventario de múltiples productos a la vez
        </p>
    </div>
    <div class="flex gap-3">
        <a href="templates/plantilla_actualizacion_stock.csv" download class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
            <span class="material-symbols-outlined">download</span>
            Descargar Plantilla CSV
        </a>
    </div>
</div>

<!-- Tipo de Ajuste (Radio Buttons Modernos) -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Tipo de Operación</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <label class="radio-card cursor-pointer">
            <input type="radio" name="tipoAjuste" value="sumar" checked class="hidden">
            <div class="radio-content border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
                <div class="text-4xl mb-2">➕</div>
                <h4 class="font-bold text-gray-900 dark:text-white mb-1">Sumar</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Stock actual + Cantidad</p>
            </div>
        </label>
        <label class="radio-card cursor-pointer">
            <input type="radio" name="tipoAjuste" value="restar" class="hidden">
            <div class="radio-content border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
                <div class="text-4xl mb-2">➖</div>
                <h4 class="font-bold text-gray-900 dark:text-white mb-1">Restar</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Stock actual - Cantidad</p>
            </div>
        </label>
        <label class="radio-card cursor-pointer">
            <input type="radio" name="tipoAjuste" value="reemplazar" class="hidden">
            <div class="radio-content border-2 border-gray-200 dark:border-gray-700 rounded-xl p-4 text-center">
                <div class="text-4xl mb-2">🔄</div>
                <h4 class="font-bold text-gray-900 dark:text-white mb-1">Reemplazar</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">Nuevo stock = Cantidad</p>
            </div>
        </label>
    </div>
</div>

<!-- Importar CSV con Drag & Drop -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Importar desde CSV</h3>
    
    <div id="dropZoneActualizar" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl p-8 text-center cursor-pointer hover:border-primary hover:bg-primary/5 transition-all">
        <div class="flex flex-col items-center gap-2">
            <span class="material-symbols-outlined text-primary text-4xl">upload_file</span>
            <p class="text-sm text-gray-600 dark:text-gray-400">
                <span class="font-semibold text-primary">Click aquí</span> o arrastra el CSV
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-500">Solo archivos .csv</p>
        </div>
    </div>
    <input type="file" id="csvFileActualizar" accept=".csv" class="hidden">
</div>

<!-- Botón Agregar Fila Manual -->
<div class="flex justify-end mb-4">
    <button onclick="agregarFilaManual()" class="flex items-center gap-2 px-5 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition shadow-md">
        <span class="material-symbols-outlined text-lg">add</span>
        Agregar Producto Manualmente
    </button>
</div>

<!-- Contenedor de Productos (Cards Verticales) -->
<div id="contenedorProductosActualizar" class="space-y-6 mb-6">
    <!-- Aquí se agregarán las cards de productos dinámicamente -->
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-12 text-center">
        <img src="uploads/gatito.png" alt="Rey jugando con gato" class="w-64 h-64 mx-auto mb-6 animate-pulse">
        <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-2">Todo calmado por aquí</h3>
        <p class="text-gray-500 dark:text-gray-400 text-lg mb-4">Importa un CSV o añade una fila manualmente</p>
        <div class="flex gap-3 justify-center mt-6">
            <button onclick="document.getElementById('csvFileActualizar').click()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <span class="material-symbols-outlined">upload_file</span>
                Importar CSV
            </button>
            <button onclick="agregarFilaManual()" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <span class="material-symbols-outlined">add</span>
                Agregar Fila
            </button>
        </div>
    </div>
</div>

<!-- Resumen -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-4 mb-6">
    <div id="resumenActualizar" class="text-center text-sm text-gray-600 dark:text-gray-400">
        0 productos
    </div>
</div>

<!-- Botones de Acción -->
<div class="flex gap-3 justify-end">
    <button onclick="limpiarActualizacion()" class="flex items-center gap-2 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
        <span class="material-symbols-outlined">delete</span>
        Limpiar
    </button>
    <button onclick="aplicarActualizaciones()" id="btnAplicar" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors disabled:bg-gray-400">
        <span class="material-symbols-outlined">check_circle</span>
        Aplicar Cambios
    </button>
</div>

</div>
</main>
</div>
</div>

<!-- Modal de Notificaciones -->
<div id="modalNotificacion" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#192233] rounded-xl max-w-md w-full overflow-hidden shadow-2xl">
        <div id="notifHeader" class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <span id="notifIcon" class="material-symbols-outlined text-4xl"></span>
                <h3 id="notifTitulo" class="text-xl font-bold text-gray-900 dark:text-white"></h3>
            </div>
        </div>
        <div class="p-6">
            <p id="notifMensaje" class="text-gray-600 dark:text-gray-400"></p>
        </div>
        <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end">
            <button onclick="cerrarNotificacion()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- Modal de Confirmación -->
<div id="modalConfirmacion" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#192233] rounded-xl max-w-md w-full overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-yellow-50 dark:bg-yellow-900/20">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-4xl text-yellow-600">warning</span>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Confirmación</h3>
            </div>
        </div>
        <div class="p-6">
            <p id="confirmMensaje" class="text-gray-600 dark:text-gray-400"></p>
        </div>
        <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
            <button onclick="cerrarConfirmacion(false)" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                Cancelar
            </button>
            <button onclick="cerrarConfirmacion(true)" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                Confirmar
            </button>
        </div>
    </div>
</div>

<script src="js/tabla-editable-actualizar.js?v=<?= time() ?>"></script>
</body>
</html>
