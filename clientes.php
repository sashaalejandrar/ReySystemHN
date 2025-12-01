<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'funciones.php';

VerificarSiUsuarioYaInicioSesion();
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Opcional: puedes consultar la tabla usuarios si necesitas validar algo más
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
// --- FIN DE LA LÓGICA DE PERMISOS ---

// Obtener clientes de la base de datos
$busqueda = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 15;
$offset = ($pagina - 1) * $limite;

// Construir consulta con búsqueda
if (!empty($busqueda)) {
    $sql = "SELECT c.*, pc.puntos_disponibles, pc.nivel_membresia 
            FROM clientes c 
            LEFT JOIN puntos_clientes pc ON c.Nombre = pc.cliente_nombre 
            WHERE c.Nombre LIKE ? OR c.Celular LIKE ? OR c.Direccion LIKE ? 
            ORDER BY c.Nombre ASC LIMIT ? OFFSET ?";
    $stmt = $conexion->prepare($sql);
    $busqueda_param = "%$busqueda%";
    $stmt->bind_param("sssii", $busqueda_param, $busqueda_param, $busqueda_param, $limite, $offset);
    $stmt->execute();
    $resultado_clientes = $stmt->get_result();
    
    // Contar total
    $sql_count = "SELECT COUNT(*) as total FROM clientes WHERE Nombre LIKE ? OR Celular LIKE ? OR Direccion LIKE ?";
    $stmt_count = $conexion->prepare($sql_count);
    $stmt_count->bind_param("sss", $busqueda_param, $busqueda_param, $busqueda_param);
    $stmt_count->execute();
    $total_clientes = $stmt_count->get_result()->fetch_assoc()['total'];
} else {
    $sql = "SELECT c.*, pc.puntos_disponibles, pc.nivel_membresia 
            FROM clientes c 
            LEFT JOIN puntos_clientes pc ON c.Nombre = pc.cliente_nombre 
            ORDER BY c.Nombre ASC LIMIT ? OFFSET ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $limite, $offset);
    $stmt->execute();
    $resultado_clientes = $stmt->get_result();
    
    // Contar total
    $total_clientes = $conexion->query("SELECT COUNT(*) as total FROM clientes")->fetch_assoc()['total'];
}

$total_paginas = ceil($total_clientes / $limite);

?>

<!DOCTYPE html>

<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Gestión de Clientes - Rey System APP</title>
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
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 1000;
        transform: translateX(120%);
        transition: transform 0.3s ease-out;
    }
    .notification.show {
        transform: translateX(0);
    }
    .notification.success {
        background-color: #10b981;
        color: white;
    }
    .notification.error {
        background-color: #ef4444;
        color: white;
    }
    .notification.info {
        background-color: #3b82f6;
        color: white;
    }
</style>
<link rel="stylesheet" href="clientes_premium.css">
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
<h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">Gestión de Clientes</h1>
<p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">Administra y visualiza todos tus clientes registrados.</p>
</div>
<div class="flex gap-3">
<button onclick="window.location.href='crear_cliente.php'" class="flex items-center gap-2 rounded-lg bg-primary px-6 py-3 text-white font-bold hover:bg-primary/90 transition-all duration-300">
<span class="material-symbols-outlined">add</span>
<span>Nuevo Cliente</span>
</button>
</div>
</div>

<!-- Barra de búsqueda y filtros -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6">
<form method="GET" action="clientes.php" class="flex flex-col sm:flex-row gap-4">
<div class="flex-1">
<div class="relative">
<span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-[#92a4c9]">search</span>
<input type="text" name="buscar" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar por nombre, celular o dirección..." class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"/>
</div>
</div>
<button type="submit" class="flex items-center justify-center gap-2 rounded-lg bg-primary px-6 py-3 text-white font-bold hover:bg-primary/90 transition-all duration-300">
<span class="material-symbols-outlined">search</span>
<span>Buscar</span>
</button>
<?php if (!empty($busqueda)): ?>
<button type="button" onclick="window.location.href='clientes.php'" class="flex items-center justify-center gap-2 rounded-lg border border-gray-200 dark:border-[#324467] px-6 py-3 text-gray-900 dark:text-white font-bold hover:bg-gray-100 dark:hover:bg-[#232f48] transition-all duration-300">
<span class="material-symbols-outlined">close</span>
<span>Limpiar</span>
</button>
<?php endif; ?>
</form>
</div>

<!-- Estadísticas rápidas -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
<div class="glass-card stat-card rounded-xl p-6 transition-all duration-300">
<div class="flex items-center justify-between">
<div>
<p class="text-gray-500 dark:text-[#92a4c9] text-sm font-medium">Total Clientes</p>
<p class="text-gray-900 dark:text-white text-2xl font-bold mt-1"><?php echo number_format($total_clientes); ?></p>
</div>
<span class="material-symbols-outlined text-primary text-4xl icon-bounce-hover">group</span>
</div>
</div>
<div class="glass-card stat-card rounded-xl p-6 transition-all duration-300">
<div class="flex items-center justify-between">
<div>
<p class="text-gray-500 dark:text-[#92a4c9] text-sm font-medium">Nuevos Este Mes</p>
<p class="text-gray-900 dark:text-white text-2xl font-bold mt-1">
<?php 
$nuevos_mes = $conexion->query("SELECT COUNT(*) as total FROM clientes WHERE MONTH(Fecha_Registro) = MONTH(CURDATE()) AND YEAR(Fecha_Registro) = YEAR(CURDATE())")->fetch_assoc()['total'];
echo number_format($nuevos_mes);
?>
</p>
</div>
<span class="material-symbols-outlined text-green-500 text-4xl icon-bounce-hover">trending_up</span>
</div>
</div>
</div>

<!-- Tabla de clientes -->
<div class="glass-card rounded-xl overflow-hidden glow-on-hover">
<div class="overflow-x-auto premium-scroll">
<table class="w-full premium-table">
<thead class="bg-gray-50 dark:bg-[#0d1420] border-b border-gray-200 dark:border-[#324467]">
            <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase tracking-wider">Nombre</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase tracking-wider">Celular</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase tracking-wider">Dirección</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase tracking-wider">Fecha Registro</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase tracking-wider">Puntos</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase tracking-wider">Estado</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase tracking-wider">Acciones</th>
            </tr>
</thead>
<tbody class="divide-y divide-gray-200 dark:divide-[#324467]">
<?php if ($resultado_clientes->num_rows > 0): ?>
    <?php while($cliente = $resultado_clientes->fetch_assoc()): ?>
        <?php
        // Verificar si tiene deudas pendientes
        $tiene_deudas = $conexion->query("SELECT COUNT(*) as total FROM deudas WHERE nombreCliente = '" . $cliente['Nombre'] . "' AND Estado = 'Pendiente'")->fetch_assoc()['total'] > 0;
        ?>
        <tr class="hover:bg-gray-50 dark:hover:bg-[#1a2332] transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary">person</span>
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($cliente['Nombre']); ?></div>
                        <div class="text-xs text-gray-500 dark:text-[#92a4c9]">ID: <?php echo $cliente['Id']; ?></div>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center gap-2 text-sm text-gray-900 dark:text-white">
                    <span class="material-symbols-outlined text-gray-400 dark:text-[#92a4c9] text-base">phone</span>
                    <?php echo htmlspecialchars($cliente['Celular']); ?>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2 text-sm text-gray-900 dark:text-white max-w-xs truncate">
                    <span class="material-symbols-outlined text-gray-400 dark:text-[#92a4c9] text-base">location_on</span>
                    <?php echo htmlspecialchars($cliente['Direccion']); ?>
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                <?php echo date('d/m/Y', strtotime($cliente['Fecha_Registro'])); ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <?php if ($cliente['puntos_disponibles'] !== null): 
                    $nivel_color = [
                        'Bronce' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300',
                        'Plata' => 'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300',
                        'Oro' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300',
                        'Platino' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300'
                    ];
                    $color_clase = $nivel_color[$cliente['nivel_membresia']] ?? 'bg-gray-100 dark:bg-gray-900/30 text-gray-800 dark:text-gray-300';
                ?>
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-bold text-blue-600 dark:text-blue-400"><?php echo number_format($cliente['puntos_disponibles']); ?></span>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold <?php echo $color_clase; ?>">
                            <span class="material-symbols-outlined text-xs">military_tech</span>
                            <?php echo $cliente['nivel_membresia']; ?>
                        </span>
                    </div>
                <?php else: ?>
                    <span class="text-xs text-gray-400 dark:text-gray-500">Sin puntos</span>
                <?php endif; ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <?php if ($tiene_deudas): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300">
                        <span class="material-symbols-outlined text-sm">warning</span>
                        Con Deuda
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                        <span class="material-symbols-outlined text-sm">check_circle</span>
                        Al Día
                    </span>
                <?php endif; ?>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end gap-2">
                    <button onclick="verDetalles(<?php echo $cliente['Id']; ?>)" class="action-btn p-2 rounded-lg text-primary hover:bg-primary/10 transition-colors" title="Ver detalles">
                        <span class="material-symbols-outlined">visibility</span>
                    </button>
                    <button onclick="editarCliente(<?php echo $cliente['Id']; ?>)" class="action-btn p-2 rounded-lg text-blue-600 dark:text-blue-400 hover:bg-blue-600/10 transition-colors" title="Editar">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button onclick="verHistorial(<?php echo $cliente['Id']; ?>, '<?php echo htmlspecialchars($cliente['Nombre']); ?>')" class="action-btn p-2 rounded-lg text-green-600 dark:text-green-400 hover:bg-green-600/10 transition-colors" title="Ver historial">
                        <span class="material-symbols-outlined icon-spin-hover">history</span>
                    </button>
                    <button onclick="abrirModalImpresion(<?php echo $cliente['Id']; ?>, '<?php echo htmlspecialchars($cliente['Nombre']); ?>')" class="action-btn p-2 rounded-lg text-purple-600 dark:text-purple-400 hover:bg-purple-600/10 transition-colors" title="Imprimir tickets">
                        <span class="material-symbols-outlined">print</span>
                    </button>
                    <?php if ($tiene_deudas): ?>
                        <button onclick="verDeudas('<?php echo htmlspecialchars($cliente['Nombre']); ?>')" class="action-btn p-2 rounded-lg text-red-600 dark:text-red-400 hover:bg-red-600/10 transition-colors badge-pulse" title="Ver deudas">
                            <span class="material-symbols-outlined">account_balance_wallet</span>
                        </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="6" class="px-6 py-12 text-center">
            <div class="flex flex-col items-center gap-3">
                <span class="material-symbols-outlined text-gray-400 dark:text-[#92a4c9] text-6xl">person_off</span>
                <p class="text-gray-500 dark:text-[#92a4c9] text-lg font-medium">No se encontraron clientes</p>
                <?php if (!empty($busqueda)): ?>
                    <p class="text-gray-400 dark:text-[#92a4c9] text-sm">Intenta con otra búsqueda</p>
                <?php endif; ?>
            </div>
        </td>
    </tr>
<?php endif; ?>
</tbody>
</table>
</div>

<!-- Paginación -->
<?php if ($total_paginas > 1): ?>
<div class="px-6 py-4 border-t border-gray-200 dark:border-[#324467] flex items-center justify-between">
<div class="text-sm text-gray-500 dark:text-[#92a4c9]">
    Mostrando <?php echo $offset + 1; ?> - <?php echo min($offset + $limite, $total_clientes); ?> de <?php echo $total_clientes; ?> clientes
</div>
<div class="flex gap-2">
    <?php if ($pagina > 1): ?>
        <a href="?pagina=<?php echo $pagina - 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" class="px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-[#232f48] transition-colors">
            Anterior
        </a>
    <?php endif; ?>
    
    <?php for ($i = max(1, $pagina - 2); $i <= min($total_paginas, $pagina + 2); $i++): ?>
        <a href="?pagina=<?php echo $i; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" class="px-4 py-2 rounded-lg <?php echo $i == $pagina ? 'bg-primary text-white' : 'border border-gray-200 dark:border-[#324467] text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-[#232f48]'; ?> transition-colors">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>
    
    <?php if ($pagina < $total_paginas): ?>
        <a href="?pagina=<?php echo $pagina + 1; ?><?php echo !empty($busqueda) ? '&buscar=' . urlencode($busqueda) : ''; ?>" class="px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-[#232f48] transition-colors">
            Siguiente
        </a>
    <?php endif; ?>
</div>
</div>
<?php endif; ?>
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

<!-- Notification container -->
<div id="notification" class="notification">
    <span id="notificationIcon" class="material-symbols-outlined"></span>
    <div>
        <p id="notificationTitle" class="font-semibold"></p>
        <p id="notificationMessage" class="text-sm"></p>
    </div>
</div>

<!-- Modal Ver Detalles -->
<div id="modalDetalles" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-[#192233] rounded-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 dark:border-[#324467] flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Detalles del Cliente</h3>
            <button onclick="cerrarModal('modalDetalles')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="contenidoDetalles" class="p-6">
            <!-- Se llenará dinámicamente -->
        </div>
    </div>
</div>

<!-- Modal Editar Cliente -->
<div id="modalEditar" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-[#192233] rounded-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 dark:border-[#324467] flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Editar Cliente</h3>
            <button onclick="cerrarModal('modalEditar')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <form id="formEditarCliente" class="p-6 space-y-4">
            <input type="hidden" id="editClienteId" name="id">
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Nombre</label>
                <input type="text" id="editNombre" name="nombre" required class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Celular</label>
                <input type="text" id="editCelular" name="celular" required class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Dirección</label>
                <textarea id="editDireccion" name="direccion" rows="3" class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"></textarea>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition-colors">
                    Guardar Cambios
                </button>
                <button type="button" onclick="cerrarModal('modalEditar')" class="flex-1 px-6 py-3 border border-gray-200 dark:border-[#324467] text-gray-900 dark:text-white rounded-lg font-bold hover:bg-gray-100 dark:hover:bg-[#232f48] transition-colors">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Historial -->
<div id="modalHistorial" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-[#192233] rounded-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 dark:border-[#324467] flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Historial de Compras</h3>
            <button onclick="cerrarModal('modalHistorial')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div id="contenidoHistorial" class="p-6">
            <!-- Se llenará dinámicamente -->
        </div>
    </div>
</div>

<!-- Modal Imprimir Tickets -->
<div id="modalImpresion" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-[#192233] rounded-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 dark:border-[#324467] flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Imprimir Tickets de Venta</h3>
                <p id="nombreClienteImpresion" class="text-sm text-gray-500 dark:text-[#92a4c9] mt-1"></p>
            </div>
            <button onclick="cerrarModal('modalImpresion')" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <!-- Botones de acción rápida -->
        <div class="p-6 border-b border-gray-200 dark:border-[#324467] flex flex-wrap gap-3">
            <button onclick="imprimirUltima()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                <span class="material-symbols-outlined">receipt_long</span>
                Imprimir Última Venta
            </button>
            <button onclick="imprimirTodas()" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors">
                <span class="material-symbols-outlined">print</span>
                Imprimir Todas
            </button>
            <button onclick="imprimirSeleccionadas()" class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                <span class="material-symbols-outlined">check_box</span>
                Imprimir Seleccionadas
            </button>
        </div>
        
        <div id="contenidoImpresion" class="p-6">
            <!-- Se llenará dinámicamente con la lista de ventas -->
        </div>
    </div>
</div>

<script>
// Función para mostrar notificaciones
function showNotification(type, title, message) {
    const notification = document.getElementById('notification');
    const icon = document.getElementById('notificationIcon');
    const titleEl = document.getElementById('notificationTitle');
    const messageEl = document.getElementById('notificationMessage');
    
    notification.className = `notification ${type}`;
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    if (type === 'success') {
        icon.textContent = 'check_circle';
    } else if (type === 'error') {
        icon.textContent = 'error';
    } else if (type === 'info') {
        icon.textContent = 'info';
    }
    
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 5000);
}

// Función para abrir modal
function abrirModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

// Función para cerrar modal
function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

// Ver detalles del cliente
async function verDetalles(clienteId) {
    try {
        const response = await fetch(`obtener_cliente.php?id=${clienteId}`);
        const data = await response.json();
        
        if (data.success) {
            const cliente = data.cliente;
            const contenido = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-[#92a4c9]">ID</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">${cliente.Id}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-[#92a4c9]">Fecha de Registro</p>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">${new Date(cliente.Fecha_Registro).toLocaleDateString('es-HN')}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-[#92a4c9]">Nombre</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">${cliente.Nombre}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-[#92a4c9]">Celular</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">${cliente.Celular}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-[#92a4c9]">Dirección</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">${cliente.Direccion}</p>
                    </div>
                </div>
            `;
            document.getElementById('contenidoDetalles').innerHTML = contenido;
            abrirModal('modalDetalles');
        } else {
            showNotification('error', 'Error', 'No se pudo cargar la información del cliente');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Error', 'Error al cargar los detalles del cliente');
    }
}

// Editar cliente
async function editarCliente(clienteId) {
    try {
        const response = await fetch(`obtener_cliente.php?id=${clienteId}`);
        const data = await response.json();
        
        if (data.success) {
            const cliente = data.cliente;
            document.getElementById('editClienteId').value = cliente.Id;
            document.getElementById('editNombre').value = cliente.Nombre;
            document.getElementById('editCelular').value = cliente.Celular;
            document.getElementById('editDireccion').value = cliente.Direccion;
            abrirModal('modalEditar');
        } else {
            showNotification('error', 'Error', 'No se pudo cargar la información del cliente');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Error', 'Error al cargar los datos del cliente');
    }
}

// Guardar cambios del cliente
document.getElementById('formEditarCliente').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('actualizar_cliente.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('success', 'Éxito', 'Cliente actualizado correctamente');
            cerrarModal('modalEditar');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification('error', 'Error', data.message || 'No se pudo actualizar el cliente');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Error', 'Error al actualizar el cliente');
    }
});

// Ver historial de compras
async function verHistorial(clienteId, nombreCliente) {
    try {
        const response = await fetch(`obtener_historial_cliente.php?nombre=${encodeURIComponent(nombreCliente)}`);
        const data = await response.json();
        
        if (data.success) {
            let contenido = `<h4 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Cliente: ${nombreCliente}</h4>`;
            
            if (data.ventas && data.ventas.length > 0) {
                contenido += `
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 dark:bg-[#0d1420] border-b border-gray-200 dark:border-[#324467]">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Fecha</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Total</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Método</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-[#324467]">
                `;
                
                data.ventas.forEach(venta => {
                    contenido += `
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#1a2332]">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">${new Date(venta.Fecha_Venta).toLocaleDateString('es-HN')}</td>
                            <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">L${parseFloat(venta.Total).toFixed(2)}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">${venta.MetodoPago || 'N/A'}</td>
                        </tr>
                    `;
                });
                
                contenido += `
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4 p-4 bg-primary/10 rounded-lg">
                        <p class="text-sm text-gray-900 dark:text-white">
                            <span class="font-semibold">Total de compras:</span> ${data.ventas.length}
                        </p>
                        <p class="text-sm text-gray-900 dark:text-white">
                            <span class="font-semibold">Monto total:</span> L${data.total.toFixed(2)}
                        </p>
                    </div>
                `;
            } else {
                contenido += `
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-gray-400 dark:text-[#92a4c9] text-6xl">receipt_long</span>
                        <p class="text-gray-500 dark:text-[#92a4c9] mt-4">Este cliente no tiene compras registradas</p>
                    </div>
                `;
            }
            
            document.getElementById('contenidoHistorial').innerHTML = contenido;
            abrirModal('modalHistorial');
        } else {
            showNotification('error', 'Error', 'No se pudo cargar el historial');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Error', 'Error al cargar el historial de compras');
    }
}

// Ver deudas del cliente
function verDeudas(nombreCliente) {
    window.location.href = `lista_deudas.php?cliente=${encodeURIComponent(nombreCliente)}`;
}

// Variables globales para impresión
let ventasClienteActual = [];
let nombreClienteActual = '';

// Abrir modal de impresión
async function abrirModalImpresion(clienteId, nombreCliente) {
    nombreClienteActual = nombreCliente;
    document.getElementById('nombreClienteImpresion').textContent = `Cliente: ${nombreCliente}`;
    
    try {
        const response = await fetch(`obtener_ventas_cliente.php?nombre=${encodeURIComponent(nombreCliente)}`);
        const data = await response.json();
        
        if (data.success && data.ventas && data.ventas.length > 0) {
            ventasClienteActual = data.ventas;
            
            let contenido = `
                <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <p class="text-sm text-gray-900 dark:text-white">
                        <span class="font-semibold">Total de ventas:</span> ${data.total_ventas}
                    </p>
                    <p class="text-sm text-gray-900 dark:text-white">
                        <span class="font-semibold">Monto total:</span> L${data.total_general.toFixed(2)}
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-[#0d1420] border-b border-gray-200 dark:border-[#324467]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">
                                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="rounded border-gray-300 dark:border-gray-600">
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Factura</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Fecha</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Total</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Método</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Productos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-[#324467]">
            `;
            
            data.ventas.forEach((venta, index) => {
                const fecha = new Date(venta.fecha).toLocaleDateString('es-HN', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                contenido += `
                    <tr class="hover:bg-gray-50 dark:hover:bg-[#1a2332]">
                        <td class="px-4 py-3">
                            <input type="checkbox" class="venta-checkbox rounded border-gray-300 dark:border-gray-600" value="${venta.id}" data-index="${index}">
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">${venta.factura_id || 'N/A'}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">${fecha}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-green-600 dark:text-green-400">L${venta.total.toFixed(2)}</td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">${venta.metodo_pago || 'N/A'}</td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-xs truncate">${venta.productos}</td>
                    </tr>
                `;
            });
            
            contenido += `
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('contenidoImpresion').innerHTML = contenido;
            abrirModal('modalImpresion');
        } else {
            showNotification('info', 'Sin ventas', 'Este cliente no tiene ventas registradas');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('error', 'Error', 'Error al cargar las ventas del cliente');
    }
}

// Toggle seleccionar todas
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.venta-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
}

// Imprimir última venta
function imprimirUltima() {
    if (ventasClienteActual.length === 0) {
        showNotification('warning', 'Sin ventas', 'No hay ventas para imprimir');
        return;
    }
    
    const ultimaVenta = ventasClienteActual[0]; // Ya están ordenadas por fecha DESC
    imprimirTickets([ultimaVenta.id]);
}

// Imprimir todas las ventas
function imprimirTodas() {
    if (ventasClienteActual.length === 0) {
        showNotification('warning', 'Sin ventas', 'No hay ventas para imprimir');
        return;
    }
    
    const ventasIds = ventasClienteActual.map(v => v.id);
    imprimirTickets(ventasIds);
}

// Imprimir ventas seleccionadas
function imprimirSeleccionadas() {
    const checkboxes = document.querySelectorAll('.venta-checkbox:checked');
    
    if (checkboxes.length === 0) {
        showNotification('warning', 'Sin selección', 'Por favor selecciona al menos una venta');
        return;
    }
    
    const ventasIds = Array.from(checkboxes).map(cb => cb.value);
    imprimirTickets(ventasIds);
}

// Función para imprimir tickets
function imprimirTickets(ventasIds) {
    // Crear formulario temporal para enviar POST
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'imprimir_ticket_cliente.php';
    form.target = '_blank'; // Abrir en nueva pestaña
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'ventas';
    input.value = ventasIds.join(',');
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
    
    showNotification('success', 'Generando tickets', `Generando ${ventasIds.length} ticket(s)...`);
}
</script>


</body></html>
<?php
$conexion->close();
?>