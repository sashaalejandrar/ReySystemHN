<?php
session_start();
include 'funciones.php';

VerificarSiUsuarioYaInicioSesion();
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Verificar conexión
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

// Lógica de permisos
$rol_usuario = strtolower($Rol);
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Ver Egresos - Rey System APP</title>
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
    
    /* Responsive table */
    @media (max-width: 768px) {
        .table-responsive {
            display: block;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }
    
    /* Modal animations */
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
    
    /* Badge styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    
    .badge-success {
        background-color: rgba(34, 197, 94, 0.2);
        color: rgb(34, 197, 94);
    }
    
    .badge-warning {
        background-color: rgba(251, 191, 36, 0.2);
        color: rgb(251, 191, 36);
    }
    
    .badge-info {
        background-color: rgba(59, 130, 246, 0.2);
        color: rgb(59, 130, 246);
    }
    
    .badge-purple {
        background-color: rgba(168, 85, 247, 0.2);
        color: rgb(168, 85, 247);
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
<h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">Gestión de Egresos</h1>
<p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">Visualiza y administra todos los egresos registrados con sus recibos adjuntos.</p>
</div>
<div class="flex gap-3">
<a href="compra_desde_ventas.php" class="flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-sm">
<span class="material-symbols-outlined">add</span>
<span>Nuevo Egreso</span>
</a>
</div>
</div>

<!-- Filters Section -->
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm border border-gray-200 dark:border-[#324467] p-6 mb-6">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
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
<select id="tipo" class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
<option value="">Todos</option>
<option value="Compra">Compra</option>
<option value="Justificación">Justificación</option>
</select>
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estado</label>
<select id="confirmado" class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
<option value="">Todos</option>
<option value="0">Pendiente</option>
<option value="1">Confirmado</option>
</select>
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar</label>
<input type="text" id="search" placeholder="Buscar por concepto..." class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
</div>
</div>
<div class="mt-4 flex gap-3">
<button onclick="cargarEgresos()" class="flex items-center gap-2 bg-primary hover:bg-primary/90 text-white font-semibold py-2 px-6 rounded-lg transition-colors">
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
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
<div class="bg-gradient-to-br from-blue-500/10 to-blue-600/10 dark:from-blue-500/20 dark:to-blue-600/20 border border-blue-500/30 rounded-xl p-6">
<div class="flex items-center justify-between">
<div>
<p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Egresos</p>
<p class="text-3xl font-black text-gray-900 dark:text-white mt-1" id="total-egresos">0</p>
</div>
<div class="bg-blue-500/20 rounded-full p-3">
<span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-3xl">receipt_long</span>
</div>
</div>
</div>
<div class="bg-gradient-to-br from-green-500/10 to-green-600/10 dark:from-green-500/20 dark:to-green-600/20 border border-green-500/30 rounded-xl p-6">
<div class="flex items-center justify-between">
<div>
<p class="text-sm font-medium text-gray-600 dark:text-gray-400">Monto Total</p>
<p class="text-3xl font-black text-gray-900 dark:text-white mt-1" id="monto-total">L 0.00</p>
</div>
<div class="bg-green-500/20 rounded-full p-3">
<span class="material-symbols-outlined text-green-600 dark:text-green-400 text-3xl">payments</span>
</div>
</div>
</div>
<div class="bg-gradient-to-br from-purple-500/10 to-purple-600/10 dark:from-purple-500/20 dark:to-purple-600/20 border border-purple-500/30 rounded-xl p-6">
<div class="flex items-center justify-between">
<div>
<p class="text-sm font-medium text-gray-600 dark:text-gray-400">Confirmados</p>
<p class="text-3xl font-black text-gray-900 dark:text-white mt-1" id="total-confirmados">0</p>
</div>
<div class="bg-purple-500/20 rounded-full p-3">
<span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-3xl">check_circle</span>
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
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Tipo</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Monto</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Concepto</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Usuario</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Recibos</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Estado</th>
<th class="px-6 py-4 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
</tr>
</thead>
<tbody id="tabla-egresos" class="divide-y divide-gray-200 dark:divide-[#324467]">
<!-- Rows will be inserted here by JavaScript -->
</tbody>
</table>
</div>
<!-- Loading State -->
<div id="loading-state" class="flex items-center justify-center py-12">
<div class="text-center">
<div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-gray-300 border-t-primary mb-4"></div>
<p class="text-gray-500 dark:text-gray-400">Cargando egresos...</p>
</div>
</div>
<!-- Empty State -->
<div id="empty-state" class="hidden flex-col items-center justify-center py-12">
<span class="material-symbols-outlined text-gray-400 text-6xl mb-4">inbox</span>
<p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No se encontraron egresos</p>
<p class="text-gray-400 dark:text-gray-500 text-sm">Intenta ajustar los filtros de búsqueda</p>
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

<!-- Modal: Ver Recibos -->
<div id="modal-recibos" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4 modal-backdrop">
<div class="bg-white dark:bg-[#192233] rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden modal-content">
<div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-[#324467]">
<h2 class="text-2xl font-bold text-gray-900 dark:text-white">Recibos Adjuntos</h2>
<button onclick="cerrarModalRecibos()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
<span class="material-symbols-outlined text-3xl">close</span>
</button>
</div>
<div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
<div id="recibos-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
<!-- Receipts will be inserted here -->
</div>
</div>
</div>
</div>

<!-- Modal: Editar Egreso -->
<div id="modal-editar" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4 modal-backdrop">
<div class="bg-white dark:bg-[#192233] rounded-2xl shadow-2xl max-w-2xl w-full modal-content">
<div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-[#324467]">
<h2 class="text-2xl font-bold text-gray-900 dark:text-white">Editar Egreso</h2>
<button onclick="cerrarModalEditar()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
<span class="material-symbols-outlined text-3xl">close</span>
</button>
</div>
<form id="form-editar" class="p-6">
<input type="hidden" id="edit-id">
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Monto</label>
<input type="number" id="edit-monto" step="0.01" required class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha</label>
<input type="date" id="edit-fecha" required class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
</div>
</div>
<div class="mb-4">
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo</label>
<select id="edit-tipo" required class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
<option value="Compra">Compra</option>
<option value="Justificación">Justificación</option>
</select>
</div>
<div class="mb-6">
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Concepto</label>
<textarea id="edit-concepto" rows="3" required class="w-full px-3 py-2 bg-gray-50 dark:bg-[#111722] border border-gray-300 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
</div>
<div class="flex gap-3 justify-end">
<button type="button" onclick="cerrarModalEditar()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors">
Cancelar
</button>
<button type="submit" class="px-6 py-2 bg-primary hover:bg-primary/90 text-white font-semibold rounded-lg transition-colors">
Guardar Cambios
</button>
</div>
</form>
</div>
</div>

<script>
let egresosData = [];

// Load egresos on page load
document.addEventListener('DOMContentLoaded', function() {
    cargarEgresos();
});

// Load egresos function
async function cargarEgresos() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const tipo = document.getElementById('tipo').value;
    const search = document.getElementById('search').value;
    const confirmado = document.getElementById('confirmado').value;
    
    const params = new URLSearchParams({
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        tipo: tipo,
        search: search,
        confirmado: confirmado
    });
    
    try {
        document.getElementById('loading-state').style.display = 'flex';
        document.getElementById('empty-state').classList.add('hidden');
        document.getElementById('tabla-egresos').innerHTML = '';
        
        const response = await fetch(`api/get_egresos.php?${params}`);
        const data = await response.json();
        
        document.getElementById('loading-state').style.display = 'none';
        
        if (data.success && data.data.length > 0) {
            egresosData = data.data;
            renderEgresos(data.data);
            updateStats(data.data);
        } else {
            document.getElementById('empty-state').classList.remove('hidden');
            document.getElementById('empty-state').style.display = 'flex';
            updateStats([]);
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('loading-state').style.display = 'none';
        alert('Error al cargar los egresos');
    }
}

// Render egresos in table
function renderEgresos(egresos) {
    const tbody = document.getElementById('tabla-egresos');
    tbody.innerHTML = '';
    
    egresos.forEach(egreso => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-[#111722] transition-colors';
        
        const tipoBadge = egreso.tipo === 'Compra' 
            ? '<span class="badge badge-info">Compra</span>' 
            : '<span class="badge badge-purple">Justificación</span>';
        
        const estadoBadge = egreso.confirmado === 1
            ? '<span class="badge badge-success">Confirmado</span>'
            : '<span class="badge badge-warning">Pendiente</span>';
        
        const recibosBadge = egreso.num_recibos > 0
            ? `<span class="badge badge-info">${egreso.num_recibos} archivo(s)</span>`
            : '<span class="text-gray-400 text-sm">Sin recibos</span>';
        
        tr.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">#${egreso.id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${formatDate(egreso.fecha_registro)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">${tipoBadge}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">L ${parseFloat(egreso.monto).toFixed(2)}</td>
            <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-300 max-w-xs truncate" title="${egreso.concepto}">${egreso.concepto}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">${egreso.usuario}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">${recibosBadge}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">${estadoBadge}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <div class="flex gap-2">
                    ${egreso.num_recibos > 0 ? `
                    <button onclick="verRecibos(${egreso.id})" class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300" title="Ver recibos">
                        <span class="material-symbols-outlined text-xl">visibility</span>
                    </button>
                    ` : ''}
                    ${egreso.confirmado === 0 ? `
                    <button onclick="editarEgreso(${egreso.id})" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-300" title="Editar">
                        <span class="material-symbols-outlined text-xl">edit</span>
                    </button>
                    <button onclick="confirmarEgreso(${egreso.id})" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300" title="Confirmar">
                        <span class="material-symbols-outlined text-xl">check_circle</span>
                    </button>
                    ` : `
                    <span class="text-gray-400 text-xs" title="Confirmado por ${egreso.confirmado_por || 'N/A'}">
                        <span class="material-symbols-outlined text-xl">lock</span>
                    </span>
                    `}
                </div>
            </td>
        `;
        
        tbody.appendChild(tr);
    });
}

// Update stats
function updateStats(egresos) {
    const totalEgresos = egresos.length;
    const montoTotal = egresos.reduce((sum, e) => sum + parseFloat(e.monto), 0);
    const totalConfirmados = egresos.filter(e => e.confirmado === 1).length;
    
    document.getElementById('total-egresos').textContent = totalEgresos;
    document.getElementById('monto-total').textContent = `L ${montoTotal.toFixed(2)}`;
    document.getElementById('total-confirmados').textContent = totalConfirmados;
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
    document.getElementById('tipo').value = '';
    document.getElementById('search').value = '';
    document.getElementById('confirmado').value = '';
    cargarEgresos();
}

// Ver recibos
async function verRecibos(egresoId) {
    try {
        const response = await fetch(`api/get_recibos.php?egreso_id=${egresoId}`);
        const data = await response.json();
        
        if (data.success && data.data.length > 0) {
            const container = document.getElementById('recibos-container');
            container.innerHTML = '';
            
            data.data.forEach(recibo => {
                const div = document.createElement('div');
                div.className = 'bg-gray-50 dark:bg-[#111722] rounded-lg overflow-hidden border border-gray-200 dark:border-[#324467]';
                
                if (recibo.es_imagen) {
                    div.innerHTML = `
                        <img src="${recibo.ruta_archivo}" alt="${recibo.nombre_archivo}" class="w-full h-48 object-cover cursor-pointer" onclick="window.open('${recibo.ruta_archivo}', '_blank')">
                        <div class="p-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">${recibo.nombre_archivo}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">${formatFileSize(recibo.tamano)}</p>
                        </div>
                    `;
                } else if (recibo.es_pdf) {
                    div.innerHTML = `
                        <div class="flex items-center justify-center h-48 bg-red-100 dark:bg-red-900/20 cursor-pointer" onclick="window.open('${recibo.ruta_archivo}', '_blank')">
                            <span class="material-symbols-outlined text-red-600 text-6xl">picture_as_pdf</span>
                        </div>
                        <div class="p-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">${recibo.nombre_archivo}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">${formatFileSize(recibo.tamano)}</p>
                        </div>
                    `;
                }
                
                container.appendChild(div);
            });
            
            document.getElementById('modal-recibos').classList.remove('hidden');
        } else {
            alert('No hay recibos adjuntos para este egreso');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al cargar los recibos');
    }
}

// Format file size
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

// Close modal recibos
function cerrarModalRecibos() {
    document.getElementById('modal-recibos').classList.add('hidden');
}

// Editar egreso
function editarEgreso(egresoId) {
    const egreso = egresosData.find(e => e.id === egresoId);
    if (!egreso) return;
    
    document.getElementById('edit-id').value = egreso.id;
    document.getElementById('edit-monto').value = egreso.monto;
    document.getElementById('edit-fecha').value = egreso.fecha_registro.split(' ')[0];
    document.getElementById('edit-tipo').value = egreso.tipo;
    document.getElementById('edit-concepto').value = egreso.concepto;
    
    document.getElementById('modal-editar').classList.remove('hidden');
}

// Close modal editar
function cerrarModalEditar() {
    document.getElementById('modal-editar').classList.add('hidden');
}

// Form submit editar
document.getElementById('form-editar').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const id = document.getElementById('edit-id').value;
    const monto = document.getElementById('edit-monto').value;
    const fecha = document.getElementById('edit-fecha').value;
    const tipo = document.getElementById('edit-tipo').value;
    const concepto = document.getElementById('edit-concepto').value;
    
    try {
        const response = await fetch('api/update_egreso.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: id,
                monto: monto,
                fecha_registro: fecha,
                tipo: tipo,
                concepto: concepto
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Egreso actualizado correctamente');
            cerrarModalEditar();
            cargarEgresos();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al actualizar el egreso');
    }
});

// Confirmar egreso
async function confirmarEgreso(egresoId) {
    if (!confirm('¿Está seguro de confirmar este egreso? Esta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        const response = await fetch('api/confirm_egreso.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: egresoId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Egreso confirmado correctamente');
            cargarEgresos();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Error al confirmar el egreso');
    }
}
</script>

</body></html>
