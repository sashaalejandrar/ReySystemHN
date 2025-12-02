<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Obtener productos del inventario
$productos_query = "SELECT DISTINCT Nombre_Producto, Precio_Unitario, Stock 
    FROM stock
    WHERE Stock > 0 
    ORDER BY Nombre_Producto";
$productos = $conexion->query($productos_query);

if (!$productos) {
    die("Error en consulta: " . $conexion->error);
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Pedidos por Mayoreo - Rey System</title>
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
            <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">🏪 Pedidos por Mayoreo</h1>
            <p class="text-gray-600 dark:text-gray-400">Registra pedidos de productos existentes para pulperías, bodegas y clientes mayoristas</p>
        </div>

        <!-- Información del Cliente -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <h3 class="text-xl font-bold mb-4">👤 Información del Cliente</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Cliente / Negocio *</label>
                    <input type="text" id="cliente" required class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="Pulpería Los Ángeles">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Teléfono *</label>
                    <input type="tel" id="telefono" required class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="9999-9999">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Email</label>
                    <input type="email" id="email" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="cliente@email.com">
                </div>
            </div>
        </div>

        <!-- Agregar Productos -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <h3 class="text-xl font-bold mb-4">🛒 Agregar Productos al Pedido</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold mb-2">Producto</label>
                    <select id="producto_select" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600">
                        <option value="">Seleccionar producto...</option>
                        <?php while($prod = $productos->fetch_assoc()): ?>
                        <option value="<?=$prod['Nombre_Producto']?>" data-precio="<?=$prod['Precio_Unitario']?>" data-stock="<?=$prod['Stock']?>">
                            <?=$prod['Nombre_Producto']?> (Stock: <?=$prod['Stock']?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Cantidad</label>
                    <input type="number" id="cantidad_prod" min="1" value="1" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2">Precio Unit.</label>
                    <input type="number" id="precio_prod" step="0.01" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" readonly>
                </div>
                <div class="flex items-end">
                    <button onclick="agregarProducto()" class="w-full px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                        <span class="material-symbols-outlined inline text-sm">add</span> Agregar
                    </button>
                </div>
            </div>

            <!-- Tabla de Productos Agregados -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-[#0d1117]">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Producto</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Cantidad</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Precio Unit.</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Subtotal</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tablaProductos" class="divide-y divide-gray-200 dark:divide-[#324467]"></tbody>
                    <tfoot class="bg-gray-50 dark:bg-[#0d1117]">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right font-bold text-lg">TOTAL:</td>
                            <td class="px-6 py-4 font-black text-2xl text-primary" id="total">L. 0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Detalles del Pedido -->
        <div class="bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 border border-gray-200 dark:border-[#324467]">
            <h3 class="text-xl font-bold mb-4">📋 Detalles del Pedido</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-2">Fecha Estimada de Entrega</label>
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
                    <label class="block text-sm font-semibold mb-2">Notas / Observaciones</label>
                    <textarea id="notas" rows="3" class="w-full px-4 py-2 rounded-lg border dark:bg-[#0d1117] dark:border-gray-600" placeholder="Información adicional del pedido..."></textarea>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex gap-4">
            <button onclick="guardarPedido()" class="px-8 py-4 bg-primary text-white rounded-lg hover:bg-blue-600 font-bold text-lg">
                <span class="material-symbols-outlined inline">save</span> Guardar Pedido
            </button>
            <button onclick="limpiarTodo()" class="px-8 py-4 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-bold text-lg">
                <span class="material-symbols-outlined inline">refresh</span> Limpiar Todo
            </button>
        </div>
    </main>
</div>

<script>
let productosAgregados = [];

document.getElementById('producto_select').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const precio = option.getAttribute('data-precio');
    document.getElementById('precio_prod').value = precio || '';
});

function agregarProducto() {
    const select = document.getElementById('producto_select');
    const producto = select.value;
    const cantidad = parseInt(document.getElementById('cantidad_prod').value);
    const precio = parseFloat(document.getElementById('precio_prod').value);
    
    if (!producto || !cantidad || !precio) {
        alert('⚠️ Completa todos los campos del producto');
        return;
    }
    
    const stock = parseInt(select.options[select.selectedIndex].getAttribute('data-stock'));
    if (cantidad > stock) {
        alert(`⚠️ Stock insuficiente. Disponible: ${stock}`);
        return;
    }
    
    const subtotal = cantidad * precio;
    
    productosAgregados.push({
        producto,
        cantidad,
        precio,
        subtotal
    });
    
    actualizarTabla();
    
    // Limpiar campos
    select.value = '';
    document.getElementById('cantidad_prod').value = 1;
    document.getElementById('precio_prod').value = '';
}

function actualizarTabla() {
    const tbody = document.getElementById('tablaProductos');
    let total = 0;
    
    tbody.innerHTML = productosAgregados.map((p, i) => {
        total += p.subtotal;
        return `
            <tr class="hover:bg-gray-50 dark:hover:bg-[#0d1117]">
                <td class="px-6 py-4 font-semibold">${p.producto}</td>
                <td class="px-6 py-4">${p.cantidad}</td>
                <td class="px-6 py-4">L. ${p.precio.toFixed(2)}</td>
                <td class="px-6 py-4 font-bold">L. ${p.subtotal.toFixed(2)}</td>
                <td class="px-6 py-4">
                    <button onclick="eliminarProducto(${i})" class="text-red-600 hover:text-red-800">
                        <span class="material-symbols-outlined text-sm">delete</span>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    document.getElementById('total').textContent = 'L. ' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function eliminarProducto(index) {
    productosAgregados.splice(index, 1);
    actualizarTabla();
}

async function guardarPedido() {
    const cliente = document.getElementById('cliente').value;
    const telefono = document.getElementById('telefono').value;
    const email = document.getElementById('email').value;
    const fecha_entrega = document.getElementById('fecha_entrega').value;
    const estado = document.getElementById('estado').value;
    const notas = document.getElementById('notas').value;
    
    if (!cliente || !telefono) {
        alert('⚠️ Completa los datos del cliente');
        return;
    }
    
    if (productosAgregados.length === 0) {
        alert('⚠️ Agrega al menos un producto');
        return;
    }
    
    const data = {
        cliente,
        telefono,
        email,
        fecha_entrega,
        estado,
        notas,
        productos: productosAgregados,
        tipo: 'mayoreo'
    };
    
    const res = await fetch('api/pedidos_mayoreo.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(data)
    });
    
    const result = await res.json();
    if (result.success) {
        alert('✅ Pedido por mayoreo registrado: ' + result.numero_pedido);
        limpiarTodo();
    } else {
        alert('❌ Error: ' + result.message);
    }
}

function limpiarTodo() {
    document.getElementById('cliente').value = '';
    document.getElementById('telefono').value = '';
    document.getElementById('email').value = '';
    document.getElementById('fecha_entrega').value = '';
    document.getElementById('estado').value = 'Pendiente';
    document.getElementById('notas').value = '';
    productosAgregados = [];
    actualizarTabla();
}
</script>
</body>
</html>
