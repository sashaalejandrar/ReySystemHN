<?php
session_start();
include 'funciones.php';

VerificarSiUsuarioYaInicioSesion();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener datos del usuario
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

// Verificar que solo admin pueda acceder
if ($rol_usuario !== 'admin') {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Gestión de Contratos - Rey System APP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&amp;display=swap" rel="stylesheet"/>
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
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .modal-backdrop {
        animation: fadeIn 0.2s ease-out;
    }
    
    .modal-content {
        animation: slideUp 0.3s ease-out;
    }
    
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge-primary {
        background-color: rgba(17, 82, 212, 0.2);
        color: rgb(17, 82, 212);
    }
    
    .badge-purple {
        background-color: rgba(168, 85, 247, 0.2);
        color: rgb(168, 85, 247);
    }
    
    .contract-preview {
        white-space: pre-wrap;
        font-family: 'Times New Roman', serif;
        line-height: 1.8;
        text-align: justify;
    }
</style>
<script src="nova_rey.js"></script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>
<!-- Main Content -->
<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">
<!-- PageHeading -->
<div class="flex flex-wrap justify-between gap-4 mb-8">
<div class="flex flex-col gap-2">
<h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">📄 Gestión de Contratos</h1>
<p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">Crea, edita y genera contratos laborales en formato PDF y Word.</p>
</div>
<div class="flex gap-3">
<button onclick="abrirModalNuevo()" class="flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-sm">
<span class="material-symbols-outlined">add</span>
<span>Nuevo Contrato</span>
</button>
</div>
</div>

<!-- Filters Section -->
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm border border-gray-200 dark:border-[#324467] p-6 mb-6">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Inicio</label>
<input type="date" id="fecha_inicio" class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent" value="<?php echo date('Y-m-01'); ?>">
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Fin</label>
<input type="date" id="fecha_fin" class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent" value="<?php echo date('Y-m-d'); ?>">
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo</label>
<select id="tipo_filter" class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
<option value="">Todos</option>
<option value="Contrato">Contrato</option>
<option value="Convenio">Convenio</option>
</select>
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar</label>
<input type="text" id="search" placeholder="Nombre, empresa o identidad..." class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
</div>
</div>
<div class="mt-4 flex gap-3">
<button onclick="cargarContratos()" class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
<span class="material-symbols-outlined text-sm">search</span>
<span>Buscar</span>
</button>
<button onclick="limpiarFiltros()" class="flex items-center gap-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
<span class="material-symbols-outlined text-sm">refresh</span>
<span>Limpiar</span>
</button>
</div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
<div class="bg-gradient-to-br from-blue-500/10 to-blue-600/10 dark:from-blue-500/20 dark:to-blue-600/20 border border-blue-500/30 rounded-xl p-6">
<div class="flex items-center justify-between">
<div>
<p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Contratos</p>
<p class="text-3xl font-black text-gray-900 dark:text-white mt-1" id="total-contratos">0</p>
</div>
<div class="bg-blue-500/20 rounded-full p-3">
<span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-3xl">description</span>
</div>
</div>
</div>
<div class="bg-gradient-to-br from-purple-500/10 to-purple-600/10 dark:from-purple-500/20 dark:to-purple-600/20 border border-purple-500/30 rounded-xl p-6">
<div class="flex items-center justify-between">
<div>
<p class="text-sm font-medium text-gray-600 dark:text-gray-400">Este Mes</p>
<p class="text-3xl font-black text-gray-900 dark:text-white mt-1" id="total-mes">0</p>
</div>
<div class="bg-purple-500/20 rounded-full p-3">
<span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-3xl">calendar_month</span>
</div>
</div>
</div>
</div>

<!-- Table Section -->
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm border border-gray-200 dark:border-[#324467] overflow-hidden">
<div class="overflow-x-auto">
<table class="w-full">
<thead class="bg-gray-50 dark:bg-[#111722] border-b border-gray-200 dark:border-[#324467]">
<tr>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ID</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Identidad</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Empresa</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
</tr>
</thead>
<tbody id="tabla-contratos" class="divide-y divide-gray-200 dark:divide-[#324467]">
</tbody>
</table>
</div>
<!-- Loading State -->
<div id="loading-state" class="flex items-center justify-center py-12">
<div class="text-center">
<div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-gray-300 border-t-primary mb-4"></div>
<p class="text-gray-500 dark:text-gray-400">Cargando contratos...</p>
</div>
</div>
<!-- Empty State -->
<div id="empty-state" class="hidden flex-col items-center justify-center py-12">
<span class="material-symbols-outlined text-gray-400 text-6xl mb-4">inbox</span>
<p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No se encontraron contratos</p>
<p class="text-gray-400 dark:text-gray-500 text-sm">Crea tu primer contrato usando el botón "Nuevo Contrato"</p>
</div>
</div>

</div>
<!-- Footer -->
<footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
<p class="text-gray-500 dark:text-[#92a4c9]">Versión 1.0.0</p>
<a class="text-primary hover:underline" href="#">Ayuda y Soporte</a>
</footer>
</main>
</div>
</div>

<!-- Modal: Nuevo/Editar Contrato -->
<div id="modal-form" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4 modal-backdrop">
<div class="bg-white dark:bg-[#192233] rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden modal-content">
<div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-[#324467]">
<h2 class="text-2xl font-bold text-gray-900 dark:text-white" id="modal-title">Nuevo Contrato</h2>
<button onclick="cerrarModalForm()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
<span class="material-symbols-outlined text-3xl">close</span>
</button>
</div>
<form id="form-contrato" class="p-6 overflow-y-auto max-h-[calc(90vh-180px)]">
<input type="hidden" id="contrato-id">
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<!-- Tipo -->
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de Documento *</label>
<div class="flex gap-4">
<label class="flex items-center gap-2 cursor-pointer">
<input type="radio" name="tipo" value="Contrato" checked class="w-4 h-4 text-primary focus:ring-primary">
<span class="text-sm text-gray-700 dark:text-gray-300">Contrato</span>
</label>
<label class="flex items-center gap-2 cursor-pointer">
<input type="radio" name="tipo" value="Convenio" class="w-4 h-4 text-primary focus:ring-primary">
<span class="text-sm text-gray-700 dark:text-gray-300">Convenio</span>
</label>
</div>
</div>
<!-- Fecha -->
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha *</label>
<input type="date" id="fecha_creacion" required class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent" value="<?php echo date('Y-m-d'); ?>">
</div>
<!-- Lugar -->
<div class="md:col-span-2">
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lugar</label>
<input type="text" id="lugar" class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent" value="La Flecha, Macuelizo, Santa Bárbara">
</div>
<!-- Nombre Completo -->
<div class="md:col-span-2">
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre Completo del Empleado *</label>
<input type="text" id="nombre_completo" required class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Ej: Juan Carlos Pérez López">
</div>
<!-- Identidad -->
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Número de Identidad *</label>
<input type="text" id="identidad" required maxlength="15" placeholder="0000-0000-00000" class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Formato: 0000-0000-00000</p>
</div>
<!-- Cargo -->
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cargo *</label>
<input type="text" id="cargo" required class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Ej: Vendedor, Cajero, etc.">
</div>
<!-- Nombre Empresa -->
<div class="md:col-span-2">
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre de la Empresa *</label>
<input type="text" id="nombre_empresa" required class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Ej: Tiendas Rey">
</div>
<!-- Servicios -->
<div class="md:col-span-2">
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Servicios a Prestar *</label>
<textarea id="servicios" rows="3" required class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Ej: Atención al cliente, manejo de caja, control de inventario..."></textarea>
</div>
<!-- Contenido Adicional -->
<div class="md:col-span-2">
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cláusulas Adicionales (Opcional)</label>
<textarea id="contenido_adicional" rows="4" class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent" placeholder="Agregue cláusulas adicionales si es necesario..."></textarea>
</div>
<!-- Firma Electrónica -->
<div class="md:col-span-2">
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Firma del Empleado</label>
<div class="bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg p-4">
<!-- Selector de tipo de firma -->
<div class="flex gap-4 mb-4">
<label class="flex items-center gap-2 cursor-pointer">
<input type="radio" name="tipo_firma" value="dibujar" checked class="w-4 h-4 text-primary focus:ring-primary" onchange="cambiarTipoFirma()">
<span class="text-sm text-gray-700 dark:text-gray-300">✏️ Dibujar Firma</span>
</label>
<label class="flex items-center gap-2 cursor-pointer">
<input type="radio" name="tipo_firma" value="escribir" class="w-4 h-4 text-primary focus:ring-primary" onchange="cambiarTipoFirma()">
<span class="text-sm text-gray-700 dark:text-gray-300">📝 Escribir Nombre</span>
</label>
</div>
<!-- Canvas para dibujar -->
<div id="firma-canvas-container" class="mb-4">
<canvas id="firma-canvas" width="600" height="150" class="border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white cursor-crosshair w-full" style="max-width: 100%; height: 150px;"></canvas>
<div class="flex gap-2 mt-2">
<button type="button" onclick="limpiarFirma()" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm rounded-lg transition-colors">
Limpiar
</button>
</div>
</div>
<!-- Input para escribir nombre -->
<div id="firma-texto-container" class="hidden mb-4">
<input type="text" id="firma-texto" placeholder="Escriba su nombre completo..." class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent" style="font-family: 'Brush Script MT', cursive; font-size: 24px;">
<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Su nombre se convertirá en firma cursiva</p>
</div>
<!-- Preview de la firma -->
<div class="mt-4">
<p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Vista previa:</p>
<div id="firma-preview" class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 bg-white dark:bg-gray-800 min-h-[100px] flex items-center justify-center">
<span class="text-gray-400 text-sm">La firma aparecerá aquí</span>
</div>
</div>
</div>
</div>
</div>
<div class="flex gap-3 justify-end mt-6 pt-6 border-t border-gray-200 dark:border-[#324467]">
<button type="button" onclick="cerrarModalForm()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors">
Cancelar
</button>
<button type="button" onclick="previsualizarContrato()" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors">
Vista Previa
</button>
<button type="submit" class="px-6 py-2 bg-primary hover:bg-primary/90 text-white font-semibold rounded-lg transition-colors">
Guardar
</button>
</div>
</form>
</div>
</div>

<!-- Modal: Vista Previa -->
<div id="modal-preview" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4 modal-backdrop">
<div class="bg-white dark:bg-[#192233] rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden modal-content">
<div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-[#324467]">
<h2 class="text-2xl font-bold text-gray-900 dark:text-white">Vista Previa del Contrato</h2>
<button onclick="cerrarModalPreview()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
<span class="material-symbols-outlined text-3xl">close</span>
</button>
</div>
<div class="p-8 overflow-y-auto max-h-[calc(90vh-240px)] bg-white dark:bg-gray-900">
<div id="preview-content" class="contract-preview text-gray-900 dark:text-gray-100"></div>
</div>
<div class="flex gap-3 justify-end p-6 border-t border-gray-200 dark:border-[#324467]">
<button onclick="cerrarModalPreview()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors">
Cerrar
</button>
<button onclick="imprimirContrato()" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors flex items-center gap-2">
<span class="material-symbols-outlined text-sm">print</span>
Imprimir
</button>
</div>
</div>
</div>

<!-- Modal: Notificación -->
<div id="modal-notificacion" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-2xl max-w-md w-full modal-content">
<div class="p-6">
<div class="flex items-start gap-4">
<div id="notif-icon" class="flex-shrink-0"></div>
<div class="flex-1">
<h3 id="notif-title" class="text-lg font-bold text-gray-900 dark:text-white mb-2"></h3>
<p id="notif-message" class="text-gray-600 dark:text-gray-300"></p>
</div>
</div>
<div class="mt-6 flex justify-end">
<button onclick="cerrarNotificacion()" class="px-6 py-2 bg-primary hover:bg-primary/90 text-white font-semibold rounded-lg transition-colors">
Aceptar
</button>
</div>
</div>
</div>
</div>

<script>
let contratosData = [];
let contratoActual = null;
let firmaData = null;

// Signature canvas setup
let canvas, ctx, isDrawing = false;

// Load contracts on page load
document.addEventListener('DOMContentLoaded', function() {
    cargarContratos();
    
    // Setup signature canvas
    canvas = document.getElementById('firma-canvas');
    ctx = canvas.getContext('2d');
    ctx.strokeStyle = '#000';
    ctx.lineWidth = 2;
    ctx.lineCap = 'round';
    
    // Canvas drawing events
    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
    
    // Touch events for mobile
    canvas.addEventListener('touchstart', handleTouch);
    canvas.addEventListener('touchmove', handleTouch);
    canvas.addEventListener('touchend', stopDrawing);
    
    // Text signature input
    document.getElementById('firma-texto').addEventListener('input', function() {
        generarFirmaTexto();
    });
    
    // Auto-format identity input
    document.getElementById('identidad').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 13) value = value.substr(0, 13);
        
        let formatted = '';
        if (value.length > 0) formatted += value.substr(0, 4);
        if (value.length > 4) formatted += '-' + value.substr(4, 4);
        if (value.length > 8) formatted += '-' + value.substr(8, 5);
        
        e.target.value = formatted;
    });
});

// Signature drawing functions
function startDrawing(e) {
    isDrawing = true;
    const rect = canvas.getBoundingClientRect();
    ctx.beginPath();
    ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
}

function draw(e) {
    if (!isDrawing) return;
    const rect = canvas.getBoundingClientRect();
    ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
    ctx.stroke();
    actualizarPreviewFirma();
}

function stopDrawing() {
    isDrawing = false;
}

function handleTouch(e) {
    e.preventDefault();
    const touch = e.touches[0];
    const mouseEvent = new MouseEvent(e.type === 'touchstart' ? 'mousedown' : 'mousemove', {
        clientX: touch.clientX,
        clientY: touch.clientY
    });
    canvas.dispatchEvent(mouseEvent);
}

function limpiarFirma() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    document.getElementById('firma-preview').innerHTML = '<span class="text-gray-400 text-sm">La firma aparecerá aquí</span>';
    firmaData = null;
}

function cambiarTipoFirma() {
    const tipo = document.querySelector('input[name="tipo_firma"]:checked').value;
    if (tipo === 'dibujar') {
        document.getElementById('firma-canvas-container').classList.remove('hidden');
        document.getElementById('firma-texto-container').classList.add('hidden');
        limpiarFirma();
    } else {
        document.getElementById('firma-canvas-container').classList.add('hidden');
        document.getElementById('firma-texto-container').classList.remove('hidden');
        generarFirmaTexto();
    }
}

function generarFirmaTexto() {
    const texto = document.getElementById('firma-texto').value;
    if (!texto) {
        document.getElementById('firma-preview').innerHTML = '<span class="text-gray-400 text-sm">La firma aparecerá aquí</span>';
        firmaData = null;
        return;
    }
    
    // Create a temporary canvas to generate signature from text
    const tempCanvas = document.createElement('canvas');
    tempCanvas.width = 600;
    tempCanvas.height = 150;
    const tempCtx = tempCanvas.getContext('2d');
    
    // White background
    tempCtx.fillStyle = '#ffffff';
    tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);
    
    // Signature style - elegant handwriting
    tempCtx.fillStyle = '#000000';
    tempCtx.font = '48px "Segoe Script", "Lucida Handwriting", "Apple Chancery", cursive';
    tempCtx.textAlign = 'center';
    tempCtx.textBaseline = 'middle';
    
    // Draw text
    tempCtx.fillText(texto, tempCanvas.width / 2, tempCanvas.height / 2);
    
    firmaData = tempCanvas.toDataURL('image/png');
    actualizarPreviewFirma();
}

function actualizarPreviewFirma() {
    const tipo = document.querySelector('input[name="tipo_firma"]:checked').value;
    if (tipo === 'dibujar') {
        firmaData = canvas.toDataURL('image/png');
    }
    
    if (firmaData) {
        document.getElementById('firma-preview').innerHTML = `<img src="${firmaData}" alt="Firma" class="max-w-full h-auto">`;
    }
}

// Notification functions
function mostrarNotificacion(tipo, titulo, mensaje) {
    const modal = document.getElementById('modal-notificacion');
    const icon = document.getElementById('notif-icon');
    const titleEl = document.getElementById('notif-title');
    const messageEl = document.getElementById('notif-message');
    
    // Set icon based on type
    if (tipo === 'success') {
        icon.innerHTML = '<div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center"><span class="material-symbols-outlined text-green-600 dark:text-green-400 text-3xl">check_circle</span></div>';
    } else if (tipo === 'error') {
        icon.innerHTML = '<div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center"><span class="material-symbols-outlined text-red-600 dark:text-red-400 text-3xl">error</span></div>';
    } else if (tipo === 'warning') {
        icon.innerHTML = '<div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center"><span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 text-3xl">warning</span></div>';
    }
    
    titleEl.textContent = titulo;
    messageEl.textContent = mensaje;
    modal.classList.remove('hidden');
    
    // Auto close after 3 seconds for success messages
    if (tipo === 'success') {
        setTimeout(() => {
            cerrarNotificacion();
        }, 3000);
    }
}

function cerrarNotificacion() {
    document.getElementById('modal-notificacion').classList.add('hidden');
}

// Load contracts
async function cargarContratos() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const tipo = document.getElementById('tipo_filter').value;
    const search = document.getElementById('search').value;
    
    const params = new URLSearchParams({
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        tipo: tipo,
        search: search
    });
    
    try {
        document.getElementById('loading-state').style.display = 'flex';
        document.getElementById('empty-state').classList.add('hidden');
        document.getElementById('tabla-contratos').innerHTML = '';
        
        const response = await fetch(`api/get_contratos.php?${params}`);
        const data = await response.json();
        
        document.getElementById('loading-state').style.display = 'none';
        
        if (data.success && data.data.length > 0) {
            contratosData = data.data;
            renderContratos(data.data);
            updateStats(data.data);
        } else {
            document.getElementById('empty-state').classList.remove('hidden');
            document.getElementById('empty-state').style.display = 'flex';
            updateStats([]);
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('loading-state').style.display = 'none';
        mostrarNotificacion('error', 'Error', 'Error al cargar los contratos');
    }
}

// Render contracts in table
function renderContratos(contratos) {
    const tbody = document.getElementById('tabla-contratos');
    tbody.innerHTML = '';
    
    contratos.forEach(contrato => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-[#111722] transition-colors';
        
        const tipoBadge = contrato.tipo === 'Contrato' 
            ? '<span class="badge badge-primary">Contrato</span>' 
            : '<span class="badge badge-purple">Convenio</span>';
        
        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#${contrato.id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">${tipoBadge}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${contrato.nombre_completo}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${contrato.identidad}</td>
            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate">${contrato.nombre_empresa}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${formatDate(contrato.fecha_creacion)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div class="flex gap-2">
                    <button onclick="verContrato(${contrato.id})" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" title="Ver">
                        <span class="material-symbols-outlined text-xl">visibility</span>
                    </button>
                    <button onclick="editarContrato(${contrato.id})" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-300" title="Editar">
                        <span class="material-symbols-outlined text-xl">edit</span>
                    </button>
                    <button onclick="generarPDF(${contrato.id})" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300" title="PDF">
                        <span class="material-symbols-outlined text-xl">picture_as_pdf</span>
                    </button>
                    <button onclick="eliminarContrato(${contrato.id})" class="text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-300" title="Eliminar">
                        <span class="material-symbols-outlined text-xl">delete</span>
                    </button>
                </div>
            </td>
        `;
        
        tbody.appendChild(tr);
    });
}

// Update stats
function updateStats(contratos) {
    const total = contratos.length;
    const mesActual = new Date().getMonth();
    const totalMes = contratos.filter(c => {
        const fecha = new Date(c.fecha_creacion);
        return fecha.getMonth() === mesActual;
    }).length;
    
    document.getElementById('total-contratos').textContent = total;
    document.getElementById('total-mes').textContent = totalMes;
}

// Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
}

// Clear filters
function limpiarFiltros() {
    document.getElementById('fecha_inicio').value = '<?php echo date('Y-m-01'); ?>';
    document.getElementById('fecha_fin').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('tipo_filter').value = '';
    document.getElementById('search').value = '';
    cargarContratos();
}

// Open new contract modal
function abrirModalNuevo() {
    document.getElementById('modal-title').textContent = 'Nuevo Contrato';
    document.getElementById('form-contrato').reset();
    document.getElementById('contrato-id').value = '';
    document.getElementById('fecha_creacion').value = '<?php echo date('Y-m-d'); ?>';
    document.getElementById('lugar').value = 'La Flecha, Macuelizo, Santa Bárbara';
    document.getElementById('modal-form').classList.remove('hidden');
}

// Close form modal
function cerrarModalForm() {
    document.getElementById('modal-form').classList.add('hidden');
}

// Edit contract
function editarContrato(contratoId) {
    const contrato = contratosData.find(c => c.id === contratoId);
    if (!contrato) return;
    
    document.getElementById('modal-title').textContent = 'Editar Contrato';
    document.getElementById('contrato-id').value = contrato.id;
    document.querySelector(`input[name="tipo"][value="${contrato.tipo}"]`).checked = true;
    document.getElementById('fecha_creacion').value = contrato.fecha_creacion;
    document.getElementById('lugar').value = contrato.lugar;
    document.getElementById('nombre_completo').value = contrato.nombre_completo;
    document.getElementById('identidad').value = contrato.identidad;
    document.getElementById('cargo').value = contrato.cargo;
    document.getElementById('nombre_empresa').value = contrato.nombre_empresa;
    document.getElementById('servicios').value = contrato.servicios;
    document.getElementById('contenido_adicional').value = contrato.contenido_adicional || '';
    
    document.getElementById('modal-form').classList.remove('hidden');
}

// Form submit
document.getElementById('form-contrato').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const contratoId = document.getElementById('contrato-id').value;
    const tipo = document.querySelector('input[name="tipo"]:checked').value;
    const fecha = document.getElementById('fecha_creacion').value;
    const lugar = document.getElementById('lugar').value;
    const nombre = document.getElementById('nombre_completo').value;
    const identidad = document.getElementById('identidad').value;
    const cargo = document.getElementById('cargo').value;
    const empresa = document.getElementById('nombre_empresa').value;
    const servicios = document.getElementById('servicios').value;
    const contenidoAdicional = document.getElementById('contenido_adicional').value;
    
    const data = {
        tipo, fecha_creacion: fecha, lugar, nombre_completo: nombre,
        identidad, cargo, nombre_empresa: empresa, servicios, contenido_adicional: contenidoAdicional,
        firma_empleado: firmaData
    };
    
    if (contratoId) {
        data.id = contratoId;
    }
    
    try {
        const url = contratoId ? 'api/update_contrato.php' : 'api/create_contrato.php';
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarNotificacion('success', 'Éxito', result.message);
            cerrarModalForm();
            cargarContratos();
        } else {
            mostrarNotificacion('error', 'Error', result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error', 'Error al guardar el contrato');
    }
});

// Preview contract
function previsualizarContrato() {
    contratoActual = {
        tipo: document.querySelector('input[name="tipo"]:checked').value,
        fecha_creacion: document.getElementById('fecha_creacion').value,
        lugar: document.getElementById('lugar').value,
        nombre_completo: document.getElementById('nombre_completo').value,
        identidad: document.getElementById('identidad').value,
        cargo: document.getElementById('cargo').value,
        nombre_empresa: document.getElementById('nombre_empresa').value,
        servicios: document.getElementById('servicios').value,
        contenido_adicional: document.getElementById('contenido_adicional').value
    };
    
    mostrarPreview(contratoActual);
}

// View contract
function verContrato(contratoId) {
    const contrato = contratosData.find(c => c.id === contratoId);
    if (!contrato) return;
    contratoActual = contrato;
    mostrarPreview(contrato);
}

// Show preview
async function mostrarPreview(contrato) {
    const fecha = new Date(contrato.fecha_creacion);
    const meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    const fechaFormateada = fecha.getDate() + ' de ' + meses[fecha.getMonth()] + ' de ' + fecha.getFullYear();
    
    try {
        // Fetch template from database
        const response = await fetch('api/get_template_contrato.php');
        const result = await response.json();
        
        let contenido = '';
        
        if (result.success && result.template) {
            // Use database template
            contenido = result.template.contenido;
            
            // Replace placeholders with actual data
            contenido = contenido.replace(/\[TIPO\]/g, contrato.tipo.toUpperCase());
            contenido = contenido.replace(/\[FECHA\]/g, fechaFormateada);
            contenido = contenido.replace(/\[LUGAR\]/g, contrato.lugar);
            contenido = contenido.replace(/\[NOMBRE_COMPLETO\]/g, contrato.nombre_completo);
            contenido = contenido.replace(/\[IDENTIDAD\]/g, contrato.identidad);
            contenido = contenido.replace(/\[CARGO\]/g, contrato.cargo);
            contenido = contenido.replace(/\[NOMBRE_EMPRESA\]/g, contrato.nombre_empresa);
            contenido = contenido.replace(/\[SERVICIOS\]/g, contrato.servicios);
            contenido = contenido.replace(/\[CONTENIDO_ADICIONAL\]/g, contrato.contenido_adicional || '');
        } else {
            // Fallback to simple format if template not found
            contenido = `${contrato.tipo.toUpperCase()}

Fecha: ${fechaFormateada}
Lugar: ${contrato.lugar}

CONTRATO LABORAL

EMPLEADOR: Jesus Hernan Ordonez Reyes, Gerente de ${contrato.nombre_empresa}
EMPLEADO: ${contrato.nombre_completo}, Identidad: ${contrato.identidad}

PUESTO: ${contrato.cargo}
FUNCIONES: ${contrato.servicios}

${contrato.contenido_adicional || ''}

Por medio de la presente, ambas partes firman en conformidad:


_____________________________              _____________________________
Jesus Hernan Ordonez Reyes                ${contrato.nombre_completo}
Gerente / Propietario                      ${contrato.cargo}`;
        }
        
        // Display in modal
        document.getElementById('preview-content').innerHTML = '<pre class="whitespace-pre-wrap font-mono text-sm">' + contenido + '</pre>';
        document.getElementById('modal-preview').classList.remove('hidden');
        
    } catch (error) {
        console.error('Error loading template:', error);
        mostrarNotificacion('error', 'Error', 'No se pudo cargar la plantilla del contrato');
    }
}

// Close preview modal
function cerrarModalPreview() {
    document.getElementById('modal-preview').classList.add('hidden');
}

// Print contract
function imprimirContrato() {
    const contenido = document.getElementById('preview-content').textContent;
    const ventana = window.open('', '', 'width=800,height=600');
    ventana.document.write('<html><head><title>Contrato</title>');
    ventana.document.write('<style>body{font-family:Times New Roman,serif;line-height:1.8;white-space:pre-wrap;padding:2cm;}</style>');
    ventana.document.write('</head><body>');
    ventana.document.write(contenido);
    ventana.document.write('</body></html>');
    ventana.document.close();
    ventana.print();
}

// Generate PDF
function generarPDF(contratoId) {
    window.open(`api/generate_contrato_pdf.php?id=${contratoId}`, '_blank');
}

// Delete contract
async function eliminarContrato(contratoId) {
    if (!confirm('¿Está seguro de eliminar este contrato? Esta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        const response = await fetch('api/delete_contrato.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: contratoId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            mostrarNotificacion('success', 'Eliminado', 'Contrato eliminado correctamente');
            cargarContratos();
        } else {
            mostrarNotificacion('error', 'Error', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error', 'Error al eliminar el contrato');
    }
}
</script>

</body></html>
