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
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Auditoría de Cambios - Rey System APP</title>
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
        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">🔍 Auditoría de Cambios</h1>
        <p class="text-gray-500 dark:text-[#92a4c9] text-base">Registro de todas las modificaciones en el sistema</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm p-6 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
        <div>
            <label class="block text-sm font-medium mb-2">Fecha Inicio</label>
            <input type="date" id="fecha_inicio" value="<?php echo date('Y-m-01'); ?>" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">Fecha Fin</label>
            <input type="date" id="fecha_fin" value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">Tabla</label>
            <select id="filtro_tabla" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
                <option value="">Todas</option>
                <option value="productos">Productos</option>
                <option value="ventas">Ventas</option>
                <option value="usuarios">Usuarios</option>
                <option value="contratos">Contratos</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-2">Acción</label>
            <select id="filtro_accion" class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
                <option value="">Todas</option>
                <option value="crear">Crear</option>
                <option value="editar">Editar</option>
                <option value="eliminar">Eliminar</option>
            </select>
        </div>
        <div class="flex items-end">
            <button onclick="cargarAuditoria()" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                <span class="flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined">search</span>
                    Buscar
                </span>
            </button>
        </div>
    </div>
    <div class="mt-4">
        <input type="text" id="search" placeholder="Buscar por usuario, campo, valor..." class="w-full px-4 py-2 rounded-lg bg-gray-50 dark:bg-[#0d1420] border border-gray-300 dark:border-[#324467]">
    </div>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Total Registros</p>
                <p class="text-2xl font-bold" id="stat-total">0</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-gray-400">list_alt</span>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Creaciones</p>
                <p class="text-2xl font-bold text-green-600" id="stat-crear">0</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-green-400">add_circle</span>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Ediciones</p>
                <p class="text-2xl font-bold text-blue-600" id="stat-editar">0</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-blue-400">edit</span>
        </div>
    </div>
    
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 text-sm">Eliminaciones</p>
                <p class="text-2xl font-bold text-red-600" id="stat-eliminar">0</p>
            </div>
            <span class="material-symbols-outlined text-4xl text-red-400">delete</span>
        </div>
    </div>
</div>

<!-- Audit Log Table -->
<div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-200 dark:border-[#324467]">
        <h3 class="text-lg font-bold">Registro de Auditoría</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 dark:bg-[#0d1420]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Fecha/Hora</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Acción</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Tabla</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Campo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Valor Anterior</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">Valor Nuevo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase">IP</th>
                </tr>
            </thead>
            <tbody id="tabla-auditoria" class="divide-y divide-gray-200 dark:divide-[#324467]">
                <tr>
                    <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                        Cargando registros de auditoría...
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    cargarAuditoria();
    
    document.getElementById('search').addEventListener('input', function() {
        clearTimeout(this.searchTimeout);
        this.searchTimeout = setTimeout(() => cargarAuditoria(), 500);
    });
});

async function cargarAuditoria() {
    const fechaInicio = document.getElementById('fecha_inicio').value;
    const fechaFin = document.getElementById('fecha_fin').value;
    const tabla = document.getElementById('filtro_tabla').value;
    const accion = document.getElementById('filtro_accion').value;
    const search = document.getElementById('search').value;
    
    const params = new URLSearchParams({
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        tabla: tabla,
        accion: accion,
        search: search
    });
    
    try {
        const response = await fetch(`api/get_auditoria.php?${params}`);
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
    document.getElementById('stat-crear').textContent = stats.creaciones;
    document.getElementById('stat-editar').textContent = stats.ediciones;
    document.getElementById('stat-eliminar').textContent = stats.eliminaciones;
}

function mostrarTabla(logs) {
    const tbody = document.getElementById('tabla-auditoria');
    tbody.innerHTML = '';
    
    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-8 text-center text-gray-500">No hay registros para los filtros seleccionados</td></tr>';
        return;
    }
    
    logs.forEach(log => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-[#0d1420]';
        
        let accionBadge = '';
        if (log.accion === 'crear') {
            accionBadge = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">Crear</span>';
        } else if (log.accion === 'editar') {
            accionBadge = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">Editar</span>';
        } else {
            accionBadge = '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">Eliminar</span>';
        }
        
        const valorAnterior = log.valor_anterior ? (log.valor_anterior.length > 30 ? log.valor_anterior.substring(0, 30) + '...' : log.valor_anterior) : '-';
        const valorNuevo = log.valor_nuevo ? (log.valor_nuevo.length > 30 ? log.valor_nuevo.substring(0, 30) + '...' : log.valor_nuevo) : '-';
        
        tr.innerHTML = `
            <td class="px-6 py-4 text-sm">${log.fecha_formateada}</td>
            <td class="px-6 py-4 text-sm">${log.usuario_nombre}</td>
            <td class="px-6 py-4">${accionBadge}</td>
            <td class="px-6 py-4 text-sm font-mono">${log.tabla}</td>
            <td class="px-6 py-4 text-sm">${log.campo_modificado || '-'}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${valorAnterior}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${valorNuevo}</td>
            <td class="px-6 py-4 text-sm text-gray-500">${log.ip_address}</td>
        `;
        tbody.appendChild(tr);
    });
}
</script>
</body>
</html>
