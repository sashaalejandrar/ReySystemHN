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
    <title>Pedidos Productos No Existentes - Rey System</title>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">📝 Pedidos Productos No Existentes</h1>
            <p class="text-gray-600 dark:text-gray-400">Registro rápido de clientes que buscan productos no disponibles</p>
        </div>

        <!-- Formulario Rápido -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <h3 class="text-2xl font-bold mb-6">➕ Registrar Pedido Producto No Existente</h3>
            <form id="formPedidoSimple" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-3">
                    <label class="block text-sm font-semibold mb-2">Producto Buscado *</label>
                    <input type="text" id="producto" required class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="Ej: Super Recargas, Producto X...">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Cliente *</label>
                    <input type="text" id="cliente" required class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="Nombre del cliente">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Teléfono</label>
                    <input type="tel" id="telefono" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="9999-9999">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Fecha de Visita *</label>
                    <input type="date" id="fecha_visita" required class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" value="<?=date('Y-m-d')?>">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Cantidad</label>
                    <input type="number" id="cantidad" min="1" value="1" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2">Notas</label>
                    <input type="text" id="notas" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="Observaciones adicionales...">
                </div>
                <div class="md:col-span-3 flex gap-4">
                    <button type="submit" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-blue-600 font-semibold">
                        <span class="material-symbols-outlined inline text-sm">add</span> Registrar Pedido
                    </button>
                    <button type="button" onclick="limpiarFormulario()" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold">
                        <span class="material-symbols-outlined inline text-sm">refresh</span> Limpiar
                    </button>
                </div>
            </form>
        </div>

        <!-- Pedidos de Hoy -->
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
            <div class="p-6">
                <h3 class="text-xl font-bold mb-4">📋 Pedidos de Hoy</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-[#0d1117]">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Hora</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Producto</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cliente</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Teléfono</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cant.</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="tablaPedidosHoy" class="divide-y divide-gray-200 dark:divide-[#324467]"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarPedidosHoy();
});

document.getElementById('formPedidoSimple').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const data = {
        cliente: document.getElementById('cliente').value,
        telefono: document.getElementById('telefono').value,
        producto: document.getElementById('producto').value,
        cantidad: document.getElementById('cantidad').value,
        fecha_visita: document.getElementById('fecha_visita').value,
        notas: document.getElementById('notas').value,
        tipo: 'simple'
    };
    
    const res = await fetch('api/pedidos.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    
    const result = await res.json();
    if (result.success) {
        alert('✅ Pedido registrado: ' + result.numero_pedido);
        limpiarFormulario();
        cargarPedidosHoy();
    } else {
        alert('❌ Error: ' + result.message);
    }
});

async function cargarPedidosHoy() {
    const hoy = new Date().toISOString().split('T')[0];
    const res = await fetch(`api/pedidos.php?fecha=${hoy}&limit=50`);
    const data = await res.json();
    
    if (data.success) {
        const tbody = document.getElementById('tablaPedidosHoy');
        tbody.innerHTML = data.pedidos.map(p => `
            <tr class="hover:bg-gray-50 dark:hover:bg-[#0d1117]">
                <td class="px-6 py-4">${formatHora(p.fecha_pedido)}</td>
                <td class="px-6 py-4 font-semibold">${p.producto_solicitado}</td>
                <td class="px-6 py-4">${p.cliente}</td>
                <td class="px-6 py-4">${p.telefono || '-'}</td>
                <td class="px-6 py-4">${p.cantidad}</td>
                <td class="px-6 py-4">${getEstadoBadge(p.estado)}</td>
            </tr>
        `).join('');
    }
}

function getEstadoBadge(estado) {
    const badges = {
        'Pendiente': '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">Pendiente</span>',
        'En Proceso': '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">En Proceso</span>',
        'Recibido': '<span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Recibido</span>'
    };
    return badges[estado] || estado;
}

function formatHora(fecha) {
    return new Date(fecha).toLocaleTimeString('es-HN', {hour: '2-digit', minute: '2-digit'});
}

function limpiarFormulario() {
    document.getElementById('formPedidoSimple').reset();
    document.getElementById('fecha_visita').value = new Date().toISOString().split('T')[0];
}
</script>
</body>
</html>
