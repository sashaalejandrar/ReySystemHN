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

if (!in_array($rol_usuario, ['admin', 'cajero/gerente'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Ver Pedidos - Rey System</title>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">📋 Todos los Pedidos</h1>
            <p class="text-gray-600 dark:text-gray-400">Gestiona y consulta todos los pedidos registrados</p>
        </div>

        <!-- Filtros -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Buscar</label>
                    <input type="text" id="busqueda" placeholder="Cliente, producto, # pedido..." class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Estado</label>
                    <select id="filtro-estado" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600">
                        <option value="">Todos</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="En Proceso">En Proceso</option>
                        <option value="Recibido">Recibido</option>
                        <option value="Entregado">Entregado</option>
                        <option value="Cancelado">Cancelado</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button onclick="cargarPedidos()" class="w-full px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 font-semibold">
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

        <!-- Tabla de Pedidos -->
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Lista de Pedidos</h3>
                    <span id="total-pedidos" class="text-sm text-gray-600 dark:text-gray-400">0 pedidos</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-[#0d1117]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase"># Pedido</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Fecha</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cliente</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Teléfono</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Producto</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cant.</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Total Est.</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Estado</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tablaPedidos" class="divide-y divide-gray-200 dark:divide-[#324467]"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => cargarPedidos());

document.getElementById('busqueda').addEventListener('input', () => cargarPedidos());
document.getElementById('filtro-estado').addEventListener('change', () => cargarPedidos());

async function cargarPedidos() {
    const busqueda = document.getElementById('busqueda').value;
    const estado = document.getElementById('filtro-estado').value;
    
    const params = new URLSearchParams();
    if (busqueda) params.append('busqueda', busqueda);
    if (estado) params.append('estado', estado);
    params.append('limit', 1000);
    
    const res = await fetch(`api/pedidos.php?${params}`);
    const data = await res.json();
    
    if (data.success) {
        document.getElementById('total-pedidos').textContent = `${data.pedidos.length} pedidos`;
        actualizarTabla(data.pedidos);
    }
}

function actualizarTabla(pedidos) {
    const tbody = document.getElementById('tablaPedidos');
    tbody.innerHTML = pedidos.map(p => `
        <tr class="hover:bg-gray-50 dark:hover:bg-[#0d1117]">
            <td class="px-6 py-4 font-semibold">${p.numero_pedido}</td>
            <td class="px-6 py-4">${formatFecha(p.fecha_pedido)}</td>
            <td class="px-6 py-4">${p.cliente}</td>
            <td class="px-6 py-4">${p.telefono || '-'}</td>
            <td class="px-6 py-4">${p.producto_solicitado}</td>
            <td class="px-6 py-4">${p.cantidad}</td>
            <td class="px-6 py-4">L. ${formatNum(p.total_estimado)}</td>
            <td class="px-6 py-4">${getEstadoBadge(p.estado)}</td>
            <td class="px-6 py-4">
                <div class="flex gap-2">
                    <button onclick="imprimirPedido(${p.id})" class="text-blue-600 hover:text-blue-800" title="Imprimir">
                        <span class="material-symbols-outlined text-sm">print</span>
                    </button>
                    <button onclick="cambiarEstado(${p.id}, '${p.estado}')" class="text-green-600 hover:text-green-800" title="Cambiar Estado">
                        <span class="material-symbols-outlined text-sm">edit</span>
                    </button>
                    <button onclick="cancelarPedido(${p.id})" class="text-red-600 hover:text-red-800" title="Cancelar">
                        <span class="material-symbols-outlined text-sm">cancel</span>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function getEstadoBadge(estado) {
    const badges = {
        'Pendiente': '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">Pendiente</span>',
        'En Proceso': '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">En Proceso</span>',
        'Recibido': '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Recibido</span>',
        'Entregado': '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400">Entregado</span>',
        'Cancelado': '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Cancelado</span>'
    };
    return badges[estado] || estado;
}

function formatFecha(fecha) {
    return new Date(fecha).toLocaleDateString('es-HN', {year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'});
}

function formatNum(num) {
    return parseFloat(num).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function imprimirPedido(id) {
    window.open(`imprimir_pedido.php?id=${id}`, '_blank');
}

async function cambiarEstado(id, estadoActual) {
    const estados = ['Pendiente', 'En Proceso', 'Recibido', 'Entregado', 'Cancelado'];
    const opciones = estados.map((e, i) => `${i+1}. ${e} ${e === estadoActual ? '(actual)' : ''}`).join('\n');
    const seleccion = prompt(`Cambiar estado:\n${opciones}`);
    
    if (seleccion && estados[seleccion - 1]) {
        const res = await fetch('api/pedidos.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id, estado: estados[seleccion - 1]})
        });
        
        const result = await res.json();
        if (result.success) {
            alert('✅ Estado actualizado');
            cargarPedidos();
        }
    }
}

async function cancelarPedido(id) {
    if (confirm('¿Estás seguro de cancelar este pedido?')) {
        const res = await fetch('api/pedidos.php', {
            method: 'DELETE',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        
        const result = await res.json();
        if (result.success) {
            alert('✅ Pedido cancelado');
            cargarPedidos();
        }
    }
}

function exportarExcel() {
    alert('Función de exportación en desarrollo');
}
</script>
</body>
</html>
