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

$rol_usuario = strtolower($Rol);

// Get products for dropdown
$productos = $conexion->query("SELECT Id, Nombre_Producto, Codigo_Producto, Precio_Unitario, Stock FROM stock ORDER BY Nombre_Producto");
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Cotizaciones - Rey System APP</title>
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
        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">💼 Cotizaciones</h1>
        <p class="text-gray-500 dark:text-[#92a4c9] text-base">Gestión de cotizaciones y presupuestos</p>
    </div>
    <div class="flex gap-3">
        <button onclick="abrirModalNuevo()" class="flex items-center justify-center gap-2 bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-lg transition-colors shadow-sm">
            <span class="material-symbols-outlined">add</span>
            <span>Nueva Cotización</span>
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
            <label class="block text-sm font-medium mb-2">Estado</label>
            <select id="filtro_estado" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
                <option value="">Todos</option>
                <option value="pendiente">Pendiente</option>
                <option value="aprobada">Aprobada</option>
                <option value="rechazada">Rechazada</option>
                <option value="convertida">Convertida</option>
            </select>
        </div>
        <div class="flex items-end">
            <button onclick="cargarCotizaciones()" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-2 px-6 rounded-lg transition-colors">
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
                <p class="text-gray-500 text-sm">Total</p>
                <p class="text-2xl font-bold" id="stat-total">0</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-gray-400">request_quote</span>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Pendientes</p>
                <p class="text-2xl font-bold text-orange-600" id="stat-pendientes">0</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-orange-400">pending</span>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Convertidas</p>
                <p class="text-2xl font-bold text-green-600" id="stat-convertidas">0</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-green-400">check_circle</span>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Monto Total</p>
                <p class="text-xl font-bold text-blue-600" id="stat-monto">L. 0.00</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-blue-400">payments</span>
        </div>
    </div>
</div>

<!-- Table -->
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-[#0d1420]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Número</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Total</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Estado</th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-cotizaciones" class="divide-y divide-gray-200 dark:divide-[#324467]">
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        Cargando cotizaciones...
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

<!-- Modal: Nueva Cotización -->
<div id="modal-cotizacion" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
<div class="bg-white dark:bg-[#192233] rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
<div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-[#324467] sticky top-0 bg-white dark:bg-[#192233] z-10">
<h2 class="text-2xl font-bold text-gray-900 dark:text-white">Nueva Cotización</h2>
<button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
<span class="material-symbols-outlined text-3xl">close</span>
</button>
</div>

<form id="form-cotizacion" class="p-6 space-y-6">
<!-- Información del Cliente -->
<div class="bg-gray-50 dark:bg-[#0d1420] rounded-lg p-4">
<h3 class="text-lg font-bold mb-4">Información del Cliente</h3>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<div>
<label class="block text-sm font-medium mb-2">Cliente *</label>
<input type="text" id="cliente_nombre" required class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#192233] border border-gray-300 dark:border-[#324467]">
</div>
<div>
<label class="block text-sm font-medium mb-2">Teléfono</label>
<input type="tel" id="cliente_telefono" class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#192233] border border-gray-300 dark:border-[#324467]">
</div>
<div>
<label class="block text-sm font-medium mb-2">Email</label>
<input type="email" id="cliente_email" class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#192233] border border-gray-300 dark:border-[#324467]">
</div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
<div>
<label class="block text-sm font-medium mb-2">Fecha *</label>
<input type="date" id="fecha" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#192233] border border-gray-300 dark:border-[#324467]">
</div>
<div>
<label class="block text-sm font-medium mb-2">Vigencia (días)</label>
<input type="number" id="vigencia_dias" value="15" min="1" class="w-full px-4 py-2 rounded-lg bg-white dark:bg-[#192233] border border-gray-300 dark:border-[#324467]">
</div>
</div>
</div>

<!-- Productos -->
<div>
<div class="flex items-center justify-between mb-4">
<h3 class="text-lg font-bold">Productos</h3>
<button type="button" onclick="agregarItem()" class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
<span class="material-symbols-outlined text-sm">add</span>
Agregar Producto
</button>
</div>
<div id="items-container" class="space-y-3">
<!-- Items se agregan dinámicamente -->
</div>
</div>

<!-- Totales -->
<div class="bg-gray-50 dark:bg-[#0d1420] rounded-lg p-4">
<div class="space-y-2">
<div class="flex justify-between text-sm">
<span>Subtotal:</span>
<span id="subtotal-display" class="font-semibold">L. 0.00</span>
</div>
<div class="flex justify-between text-sm">
<span>Descuento:</span>
<div class="flex items-center gap-2">
<input type="number" id="descuento" value="0" min="0" step="0.01" onchange="calcularTotales()" class="w-24 px-2 py-1 rounded border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#192233] text-right">
<span class="font-semibold" id="descuento-display">L. 0.00</span>
</div>
</div>
<div class="flex justify-between text-lg font-bold border-t pt-2">
<span>Total:</span>
<span id="total-display" class="text-primary">L. 0.00</span>
</div>
</div>
</div>

<!-- Notas -->
<div>
<label class="block text-sm font-medium mb-2">Notas / Observaciones</label>
<textarea id="notas" rows="3" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]"></textarea>
</div>

<div class="flex gap-3 justify-end pt-4 border-t">
<button type="button" onclick="cerrarModal()" class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition-colors">
Cancelar
</button>
<button type="submit" class="px-6 py-2 bg-primary hover:bg-primary/90 text-white font-semibold rounded-lg transition-colors">
Guardar Cotización
</button>
</div>
</form>
</div>
</div>

<!-- Modal: Notificación -->
<div id="modal-notificacion" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-2xl max-w-md w-full">
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
    cargarCotizaciones();
});

async function cargarCotizaciones() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const estado = document.getElementById('filtro_estado').value;
    
    const params = new URLSearchParams({ fecha_inicio: fechaInicio, fecha_fin: fechaFin, estado: estado });
    
    try {
        const response = await fetch(`api/get_cotizaciones.php?${params}`);
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
    document.getElementById('stat-pendientes').textContent = stats.pendientes;
    document.getElementById('stat-convertidas').textContent = stats.convertidas;
    document.getElementById('stat-monto').textContent = 'L. ' + parseFloat(stats.monto_total || 0).toFixed(2);
}

function mostrarTabla(cotizaciones) {
    const tbody = document.getElementById('tabla-cotizaciones');
    tbody.innerHTML = '';
    
    if (cotizaciones.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No hay cotizaciones registradas</td></tr>';
        return;
    }
    
    cotizaciones.forEach(c => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-[#0d1420]';
        
        let estadoBadge = '';
        if (c.estado === 'pendiente') estadoBadge = 'bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400';
        else if (c.estado === 'aprobada') estadoBadge = 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400';
        else if (c.estado === 'convertida') estadoBadge = 'bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400';
        else estadoBadge = 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400';
        
        tr.innerHTML = `
            <td class="px-6 py-4 font-mono">${c.numero_cotizacion}</td>
            <td class="px-6 py-4">${c.cliente_nombre}</td>
            <td class="px-6 py-4">${c.fecha}</td>
            <td class="px-6 py-4 font-semibold">L. ${parseFloat(c.total).toFixed(2)}</td>
            <td class="px-6 py-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${estadoBadge}">
                    ${c.estado}
                </span>
            </td>
            <td class="px-6 py-4 text-center">
                <button onclick="verDetalle(${c.id})" class="text-blue-600 hover:text-blue-800 mr-2">
                    <span class="material-symbols-outlined">visibility</span>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

let itemCounter = 0;
const productos = <?php 
$productos->data_seek(0); // Reset pointer
$prods = [];
while($p = $productos->fetch_assoc()) {
    $prods[] = $p;
}
echo json_encode($prods);
?>;

function abrirModalNuevo() {
    document.getElementById('form-cotizacion').reset();
    document.getElementById('items-container').innerHTML = '';
    itemCounter = 0;
    agregarItem(); // Add first item by default
    document.getElementById('modal-cotizacion').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modal-cotizacion').classList.add('hidden');
}

function agregarItem() {
    const container = document.getElementById('items-container');
    const itemId = itemCounter++;
    
    const itemDiv = document.createElement('div');
    itemDiv.className = 'bg-white dark:bg-[#192233] rounded-lg p-4 border border-gray-200 dark:border-[#324467]';
    itemDiv.id = `item-${itemId}`;
    
    let productosOptions = '<option value="">Seleccionar producto...</option>';
    productos.forEach(p => {
        productosOptions += `<option value="${p.Id}" data-precio="${p.Precio_Unitario}" data-nombre="${p.Nombre_Producto}">${p.Nombre_Producto} - L. ${parseFloat(p.Precio_Unitario).toFixed(2)}</option>`;
    });
    
    itemDiv.innerHTML = `
        <div class="flex items-start gap-4">
            <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-3">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium mb-1">Producto *</label>
                    <select class="item-producto w-full px-3 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467] text-sm" onchange="actualizarPrecio(${itemId})" required>
                        ${productosOptions}
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Cantidad *</label>
                    <input type="number" class="item-cantidad w-full px-3 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467] text-sm" min="1" step="0.01" value="1" onchange="calcularTotales()" required>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Precio Unit.</label>
                    <input type="number" class="item-precio w-full px-3 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467] text-sm" step="0.01" readonly>
                </div>
            </div>
            <button type="button" onclick="eliminarItem(${itemId})" class="mt-6 text-red-600 hover:text-red-800">
                <span class="material-symbols-outlined">delete</span>
            </button>
        </div>
    `;
    
    container.appendChild(itemDiv);
}

function actualizarPrecio(itemId) {
    const itemDiv = document.getElementById(`item-${itemId}`);
    const select = itemDiv.querySelector('.item-producto');
    const precioInput = itemDiv.querySelector('.item-precio');
    
    const selectedOption = select.options[select.selectedIndex];
    const precio = selectedOption.getAttribute('data-precio') || 0;
    
    precioInput.value = parseFloat(precio).toFixed(2);
    calcularTotales();
}

function eliminarItem(itemId) {
    const itemDiv = document.getElementById(`item-${itemId}`);
    if (itemDiv) {
        itemDiv.remove();
        calcularTotales();
    }
}

function calcularTotales() {
    const items = document.querySelectorAll('#items-container > div');
    let subtotal = 0;
    
    items.forEach(item => {
        const cantidad = parseFloat(item.querySelector('.item-cantidad').value) || 0;
        const precio = parseFloat(item.querySelector('.item-precio').value) || 0;
        subtotal += cantidad * precio;
    });
    
    const descuento = parseFloat(document.getElementById('descuento').value) || 0;
    const total = subtotal - descuento;
    
    document.getElementById('subtotal-display').textContent = 'L. ' + subtotal.toFixed(2);
    document.getElementById('descuento-display').textContent = 'L. ' + descuento.toFixed(2);
    document.getElementById('total-display').textContent = 'L. ' + total.toFixed(2);
}

document.getElementById('form-cotizacion').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Recopilar items
    const items = [];
    const itemDivs = document.querySelectorAll('#items-container > div');
    
    if (itemDivs.length === 0) {
        mostrarNotificacion('error', 'Error', 'Debes agregar al menos un producto');
        return;
    }
    
    itemDivs.forEach(itemDiv => {
        const select = itemDiv.querySelector('.item-producto');
        const cantidad = parseFloat(itemDiv.querySelector('.item-cantidad').value);
        const precio = parseFloat(itemDiv.querySelector('.item-precio').value);
        
        if (select.value && cantidad > 0) {
            const selectedOption = select.options[select.selectedIndex];
            items.push({
                producto_id: select.value,
                producto_nombre: selectedOption.getAttribute('data-nombre'),
                cantidad: cantidad,
                precio_unitario: precio,
                subtotal: cantidad * precio
            });
        }
    });
    
    if (items.length === 0) {
        mostrarNotificacion('error', 'Error', 'Debes seleccionar al menos un producto válido');
        return;
    }
    
    const subtotal = items.reduce((sum, item) => sum + item.subtotal, 0);
    const descuento = parseFloat(document.getElementById('descuento').value) || 0;
    const total = subtotal - descuento;
    
    const data = {
        cliente_nombre: document.getElementById('cliente_nombre').value,
        cliente_telefono: document.getElementById('cliente_telefono').value,
        cliente_email: document.getElementById('cliente_email').value,
        fecha: document.getElementById('fecha').value,
        vigencia_dias: document.getElementById('vigencia_dias').value,
        subtotal: subtotal,
        descuento: descuento,
        total: total,
        notas: document.getElementById('notas').value,
        items: items
    };
    
    try {
        const response = await fetch('api/create_cotizacion.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            mostrarNotificacion('success', 'Éxito', result.message);
            cerrarModal();
            cargarCotizaciones();
        } else {
            mostrarNotificacion('error', 'Error', result.message);
        }
    } catch (error) {
        mostrarNotificacion('error', 'Error', 'Error al guardar cotización');
    }
});

function verDetalle(id) {
    alert('Detalle de cotización #' + id);
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
