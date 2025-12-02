<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

 
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

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

// --- INICIO DE LA LÓGICA DE PERMISOS ---
// Convertimos el rol a minúsculas para hacer la comparación insensible a mayúsculas/minúsculas.
 $rol_usuario = strtolower($Rol);

// Get products for dropdown
$productos = $conexion->query("SELECT Id, Nombre_Producto, Codigo_Producto, Precio_Unitario, Stock FROM stock ORDER BY Nombre_Producto");
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Mermas y Pérdidas - Rey System APP</title>
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
                }
            }
        }
    }
</script>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .modal-backdrop {
        animation: fadeIn 0.2s ease-out;
    }
    .modal-content {
        animation: slideUp 0.3s ease-out;
    }
</style>
<script src="nova_rey.js"></script>
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
        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">📦 Mermas y Pérdidas</h1>
        <p class="text-gray-500 dark:text-[#92a4c9] text-base">Registro de productos dañados, vencidos o perdidos</p>
    </div>
    <div class="flex gap-3">
        <button onclick="abrirModalNuevo()" class="flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-sm">
            <span class="material-symbols-outlined">add</span>
            <span>Registrar Merma</span>
        </button>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium mb-2">Fecha Inicio</label>
            <input type="date" id="fecha_inicio" value="<?php echo date('Y-m-01'); ?>" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">Fecha Fin</label>
            <input type="date" id="fecha_fin" value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">Motivo</label>
            <select id="filtro_motivo" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
                <option value="">Todos</option>
                <option value="dañado">Dañado</option>
                <option value="vencido">Vencido</option>
                <option value="robo">Robo</option>
                <option value="otro">Otro</option>
            </select>
        </div>
        <div class="flex items-end">
            <button onclick="cargarMermas()" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                <span class="flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">search</span>
                    Buscar
                </span>
            </button>
        </div>
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Mermas</p>
                <p class="text-2xl font-bold" id="stat-total">0</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-gray-400">inventory_2</span>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Costo Total</p>
                <p class="text-2xl font-bold text-red-600" id="stat-costo">L. 0.00</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-red-400">trending_down</span>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Dañados</p>
                <p class="text-2xl font-bold text-orange-600" id="stat-danados">0</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-orange-400">broken_image</span>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Vencidos</p>
                <p class="text-2xl font-bold text-purple-600" id="stat-vencidos">0</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-purple-400">event_busy</span>
        </div>
    </div>
</div>

<!-- Table -->
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-[#0d1420]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Producto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Cantidad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Motivo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Costo Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Usuario</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-mermas" class="divide-y divide-gray-200 dark:divide-[#324467]">
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        Cargando mermas...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

</div>
</main>
</div>
</div>

<!-- Modal: Nueva Merma -->
<div id="modal-form" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4 modal-backdrop">
<div class="bg-white dark:bg-[#192233] rounded-2xl shadow-2xl max-w-2xl w-full modal-content">
<div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-[#324467]">
<h2 class="text-2xl font-bold text-gray-900 dark:text-white">Registrar Merma</h2>
<button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
<span class="material-symbols-outlined text-3xl">close</span>
</button>
</div>

<form id="form-merma" class="p-6 space-y-4">
<div>
<label class="block text-sm font-medium mb-2">Producto *</label>
<select id="producto_id" required class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
<option value="">Seleccionar producto...</option>
<?php while($prod = $productos->fetch_assoc()): ?>
<option value="<?php echo $prod['Id']; ?>" data-precio="<?php echo $prod['Precio_Unitario']; ?>" data-stock="<?php echo $prod['Stock']; ?>">
<?php echo $prod['Nombre_Producto']; ?> (Stock: <?php echo $prod['Stock']; ?>)
</option>
<?php endwhile; ?>
</select>
</div>

<div class="grid grid-cols-2 gap-4">
<div>
<label class="block text-sm font-medium mb-2">Cantidad *</label>
<input type="number" id="cantidad" step="0.01" min="0.01" required class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
</div>
<div>
<label class="block text-sm font-medium mb-2">Fecha *</label>
<input type="date" id="fecha" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
</div>
</div>

<div>
<label class="block text-sm font-medium mb-2">Motivo *</label>
<select id="motivo" required class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
<option value="dañado">Dañado</option>
<option value="vencido">Vencido</option>
<option value="robo">Robo</option>
<option value="otro">Otro</option>
</select>
</div>

<div>
<label class="block text-sm font-medium mb-2">Descripción</label>
<textarea id="descripcion" rows="3" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]"></textarea>
</div>

<div class="flex gap-3 justify-end pt-4">
<button type="button" onclick="cerrarModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors">
Cancelar
</button>
<button type="submit" class="px-6 py-2 bg-primary hover:bg-primary/90 text-white font-semibold rounded-lg transition-colors">
Registrar Merma
</button>
</div>
</form>
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
document.addEventListener('DOMContentLoaded', function() {
    cargarMermas();
});

document.getElementById('form-merma').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const data = {
        producto_id: document.getElementById('producto_id').value,
        cantidad: document.getElementById('cantidad').value,
        fecha: document.getElementById('fecha').value,
        motivo: document.getElementById('motivo').value,
        descripcion: document.getElementById('descripcion').value
    };
    
    try {
        const response = await fetch('api/create_merma.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarNotificacion('success', 'Éxito', result.message);
            cerrarModal();
            cargarMermas();
        } else {
            mostrarNotificacion('error', 'Error', result.message);
        }
    } catch (error) {
        mostrarNotificacion('error', 'Error', 'Error al registrar merma');
    }
});

async function cargarMermas() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const motivo = document.getElementById('filtro_motivo').value;
    
    const params = new URLSearchParams({ fecha_inicio: fechaInicio, fecha_fin: fechaFin, motivo: motivo });
    
    try {
        const response = await fetch(`api/get_mermas.php?${params}`);
        const result = await response.json();
        
        if (result.success) {
            mostrarEstadisticas(result.stats);
            mostrarTabla(result.data);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function mostrarEstadisticas(stats) {
    document.getElementById('stat-total').textContent = stats.total;
    document.getElementById('stat-costo').textContent = 'L. ' + parseFloat(stats.costo_total || 0).toFixed(2);
    document.getElementById('stat-danados').textContent = stats.danados;
    document.getElementById('stat-vencidos').textContent = stats.vencidos;
}

function mostrarTabla(mermas) {
    const tbody = document.getElementById('tabla-mermas');
    tbody.innerHTML = '';
    
    if (mermas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center text-gray-500">No hay mermas registradas</td></tr>';
        return;
    }
    
    mermas.forEach(m => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-[#0d1420]';
        
        let motivoBadge = '';
        if (m.motivo === 'dañado') motivoBadge = 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400';
        else if (m.motivo === 'vencido') motivoBadge = 'bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400';
        else if (m.motivo === 'robo') motivoBadge = 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400';
        else motivoBadge = 'bg-gray-100 dark:bg-gray-900/30 text-gray-600 dark:text-gray-400';
        
        tr.innerHTML = `
            <td class="px-6 py-4">${m.fecha}</td>
            <td class="px-6 py-4">${m.producto_nombre}</td>
            <td class="px-6 py-4">${parseFloat(m.cantidad).toFixed(2)}</td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${motivoBadge}">
                    ${m.motivo}
                </span>
            </td>
            <td class="px-6 py-4 font-semibold text-red-600">L. ${parseFloat(m.costo_total).toFixed(2)}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${m.usuario_nombre}</td>
            <td class="px-6 py-4 text-center">
                <button onclick="eliminarMerma(${m.id})" class="text-red-600 hover:text-red-800">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

async function eliminarMerma(id) {
    if (!confirm('¿Eliminar esta merma? Esto restaurará el inventario.')) return;
    
    try {
        const response = await fetch('api/delete_merma.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarNotificacion('success', 'Eliminado', result.message);
            cargarMermas();
        } else {
            mostrarNotificacion('error', 'Error', result.message);
        }
    } catch (error) {
        mostrarNotificacion('error', 'Error', 'Error al eliminar merma');
    }
}

function abrirModalNuevo() {
    document.getElementById('form-merma').reset();
    document.getElementById('modal-form').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modal-form').classList.add('hidden');
}

function mostrarNotificacion(tipo, titulo, mensaje) {
    const modal = document.getElementById('modal-notificacion');
    const icon = document.getElementById('notif-icon');
    const titleEl = document.getElementById('notif-title');
    const messageEl = document.getElementById('notif-message');
    
    if (tipo === 'success') {
        icon.innerHTML = '<div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center"><span class="material-symbols-outlined text-green-600 dark:text-green-400 text-3xl">check_circle</span></div>';
    } else {
        icon.innerHTML = '<div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center"><span class="material-symbols-outlined text-red-600 dark:text-red-400 text-3xl">error</span></div>';
    }
    
    titleEl.textContent = titulo;
    messageEl.textContent = mensaje;
    modal.classList.remove('hidden');
    
    if (tipo === 'success') {
        setTimeout(() => cerrarNotificacion(), 3000);
    }
}

function cerrarNotificacion() {
    document.getElementById('modal-notificacion').classList.add('hidden');
}
</script>
</body>
</html>
