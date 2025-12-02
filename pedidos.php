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

// Obtener estadísticas rápidas
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN Estado = 'Pendiente' THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN Estado = 'En Proceso' THEN 1 ELSE 0 END) as en_proceso,
    SUM(CASE WHEN Estado = 'Recibido' THEN 1 ELSE 0 END) as recibidos
    FROM pedidos";
$stats = $conexion->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Pedidos - Rey System</title>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">📋 Gestión de Pedidos</h1>
            <p class="text-gray-600 dark:text-gray-400">Registra y gestiona pedidos de productos no disponibles</p>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Total Pedidos</p>
                <p class="text-3xl font-black"><?=$stats['total'] ?? 0?></p>
            </div>
            <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Pendientes</p>
                <p class="text-3xl font-black"><?=$stats['pendientes'] ?? 0?></p>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">En Proceso</p>
                <p class="text-3xl font-black"><?=$stats['en_proceso'] ?? 0?></p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                <p class="text-sm opacity-90">Recibidos</p>
                <p class="text-3xl font-black"><?=$stats['recibidos'] ?? 0?></p>
            </div>
        </div>

        <!-- Formulario Nuevo Pedido -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <h3 class="text-2xl font-bold mb-6">➕ Nuevo Pedido</h3>
            <form id="formPedido" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Cliente *</label>
                    <input type="text" id="cliente" required class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="Nombre del cliente">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Teléfono</label>
                    <input type="tel" id="telefono" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="9999-9999">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Email</label>
                    <input type="email" id="email" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="cliente@email.com">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Producto Solicitado *</label>
                    <input type="text" id="producto" required class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="Nombre del producto">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Cantidad *</label>
                    <input type="number" id="cantidad" required min="1" value="1" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Precio Estimado</label>
                    <input type="number" id="precio" step="0.01" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Fecha Estimada Entrega</label>
                    <input type="date" id="fecha_entrega" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Estado</label>
                    <select id="estado" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600">
                        <option value="Pendiente">Pendiente</option>
                        <option value="En Proceso">En Proceso</option>
                        <option value="Recibido">Recibido</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2">Notas</label>
                    <textarea id="notas" rows="3" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="Observaciones adicionales..."></textarea>
                </div>
                <div class="md:col-span-2 flex gap-4">
                    <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-blue-600 font-semibold">
                        <span class="material-symbols-outlined inline text-sm">add</span> Registrar Pedido
                    </button>
                    <button type="button" onclick="limpiarFormulario()" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                        <span class="material-symbols-outlined inline text-sm">refresh</span> Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- Pedidos Recientes -->
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold">Pedidos Recientes</h3>
                    <a href="ver_pedidos.php" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-600 font-semibold text-sm">
                        Ver Todos
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-[#0d1117]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase"># Pedido</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Fecha</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cliente</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Producto</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cantidad</th>
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
document.addEventListener('DOMContentLoaded', () => {
    cargarPedidosRecientes();
});

document.getElementById('formPedido').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const data = {
        cliente: document.getElementById('cliente').value,
        telefono: document.getElementById('telefono').value,
        email: document.getElementById('email').value,
        producto: document.getElementById('producto').value,
        cantidad: document.getElementById('cantidad').value,
        precio: document.getElementById('precio').value,
        fecha_entrega: document.getElementById('fecha_entrega').value,
        estado: document.getElementById('estado').value,
        notas: document.getElementById('notas').value
    };
    
    const res = await fetch('api/pedidos.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    
    const result = await res.json();
    if (result.success) {
        alert('✅ Pedido registrado exitosamente: ' + result.numero_pedido);
        limpiarFormulario();
        cargarPedidosRecientes();
        location.reload();
    } else {
        alert('❌ Error: ' + result.message);
    }
});

async function cargarPedidosRecientes() {
    const res = await fetch('api/pedidos.php?limit=10');
    const data = await res.json();
    
    if (data.success) {
        const tbody = document.getElementById('tablaPedidos');
        tbody.innerHTML = data.pedidos.map(p => `
            <tr class="hover:bg-gray-50 dark:hover:bg-[#0d1117]">
                <td class="px-6 py-4 font-semibold">${p.numero_pedido}</td>
                <td class="px-6 py-4">${formatFecha(p.fecha_pedido)}</td>
                <td class="px-6 py-4">${p.cliente}</td>
                <td class="px-6 py-4">${p.producto_solicitado}</td>
                <td class="px-6 py-4">${p.cantidad}</td>
                <td class="px-6 py-4">${getEstadoBadge(p.estado)}</td>
                <td class="px-6 py-4">
                    <button onclick="imprimirPedido(${p.id})" class="text-blue-600 hover:text-blue-800 mr-2" title="Imprimir">
                        <span class="material-symbols-outlined text-sm">print</span>
                    </button>
                    <button onclick="cambiarEstado(${p.id})" class="text-green-600 hover:text-green-800" title="Cambiar Estado">
                        <span class="material-symbols-outlined text-sm">edit</span>
                    </button>
                </td>
            </tr>
        `).join('');
    }
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
    return new Date(fecha).toLocaleDateString('es-HN', {year: 'numeric', month: 'short', day: 'numeric'});
}

function limpiarFormulario() {
    document.getElementById('formPedido').reset();
}

function imprimirPedido(id) {
    window.open(`imprimir_pedido.php?id=${id}`, '_blank');
}

async function cambiarEstado(id) {
    const nuevoEstado = prompt('Nuevo estado:\n1. Pendiente\n2. En Proceso\n3. Recibido\n4. Entregado\n5. Cancelado');
    const estados = ['', 'Pendiente', 'En Proceso', 'Recibido', 'Entregado', 'Cancelado'];
    
    if (nuevoEstado && estados[nuevoEstado]) {
        const res = await fetch('api/pedidos.php', {
            method: 'PUT',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id, estado: estados[nuevoEstado]})
        });
        
        const result = await res.json();
        if (result.success) {
            alert('✅ Estado actualizado');
            cargarPedidosRecientes();
        }
    }
}
</script>
</body>
</html>
