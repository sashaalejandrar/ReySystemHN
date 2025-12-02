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

// Obtener productos pendientes (en creacion_de_productos pero no en stock)
$query = "SELECT cp.* 
          FROM creacion_de_productos cp 
          WHERE cp.CodigoProducto NOT IN (SELECT Codigo_Producto FROM stock)
          ORDER BY cp.Id DESC";

$productos_pendientes = $conexion->query($query);
$total_pendientes = $productos_pendientes->num_rows;

?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Productos Pendientes - Rey System APP</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
                        "display": ["Poppins", "sans-serif"]
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
        [x-cloak] { display: none !important; }
    </style>
    <?php include "pwa-head.php"; ?>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200" x-data="appPendientes()" x-init="init()">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>
<!-- Main Content -->
<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">
<!-- Header con Gradiente Mejorado -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-500 via-red-500 to-pink-600 p-8 mb-8 shadow-2xl">
    <div class="absolute inset-0 bg-black/10"></div>
    <div class="relative z-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-4xl">pending_actions</span>
                </div>
                <div>
                    <h1 class="text-white text-4xl font-black leading-tight tracking-tight">Productos Pendientes</h1>
                    <p class="text-orange-100 text-base font-medium">Productos creados que esperan ser ingresados al inventario</p>
                </div>
            </div>
            <div class="flex items-center gap-3 px-6 py-3 bg-white/20 backdrop-blur-md rounded-xl border-2 border-white/30 shadow-lg">
                <span class="material-symbols-outlined text-white text-2xl">inventory_2</span>
                <div class="text-left">
                    <p class="text-white/80 text-xs font-semibold uppercase">Total Pendientes</p>
                    <p class="text-white font-black text-3xl"><?php echo $total_pendientes; ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-32 -mt-32"></div>
    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -ml-24 -mb-24"></div>
</div>

<?php if ($total_pendientes > 0): ?>
<!-- Grid de Tarjetas Mejorado -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <?php while ($producto = $productos_pendientes->fetch_assoc()): ?>
    <div class="group relative flex flex-col rounded-2xl bg-white dark:bg-[#192233] overflow-hidden hover:shadow-2xl transition-all duration-300 border-2 border-transparent hover:border-primary">
        <!-- Sombra decorativa -->
        <div class="absolute inset-0 bg-gradient-to-br from-primary/0 via-blue-500/0 to-purple-500/0 group-hover:from-primary/5 group-hover:via-blue-500/5 group-hover:to-purple-500/5 rounded-2xl transition-all duration-300"></div>
        
        <!-- Imagen del Producto -->
        <div class="relative w-full h-52 bg-gradient-to-br from-gray-800 via-gray-900 to-black overflow-hidden">
            <?php if (!empty($producto['FotoProducto'])): ?>
                <img src="<?php echo htmlspecialchars($producto['FotoProducto']); ?>" 
                     alt="<?php echo htmlspecialchars($producto['NombreProducto']); ?>"
                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
            <?php else: ?>
                <div class="w-full h-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-gray-600 text-7xl">inventory_2</span>
                </div>
            <?php endif; ?>
            <!-- Badge Animado -->
            <div class="absolute top-3 right-3 px-3 py-1.5 bg-gradient-to-r from-yellow-400 to-orange-500 text-white text-xs font-black rounded-full shadow-lg animate-pulse">
                ⏳ PENDIENTE
            </div>
            <!-- Overlay con gradiente -->
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        </div>
        
        <!-- Información del Producto -->
        <div class="relative flex flex-col gap-3 p-5 flex-1">
            <div>
                <h3 class="text-gray-900 dark:text-white text-lg font-bold leading-tight line-clamp-2 mb-1 group-hover:text-primary transition-colors">
                    <?php echo htmlspecialchars($producto['NombreProducto']); ?>
                </h3>
                <div class="flex items-center gap-2 mt-2">
                    <span class="material-symbols-outlined text-gray-400 text-sm">tag</span>
                    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-mono">
                        <?php echo htmlspecialchars($producto['CodigoProducto']); ?>
                    </p>
                </div>
            </div>
            
            <?php if (!empty($producto['Marca'])): ?>
            <div class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-[#111722] rounded-lg">
                <span class="material-symbols-outlined text-primary text-sm">label</span>
                <span class="text-gray-700 dark:text-gray-300 text-sm font-semibold"><?php echo htmlspecialchars($producto['Marca']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($producto['DescripcionCorta'])): ?>
            <p class="text-gray-600 dark:text-[#92a4c9] text-sm line-clamp-2 leading-relaxed">
                <?php echo htmlspecialchars($producto['DescripcionCorta']); ?>
            </p>
            <?php endif; ?>
            
            <!-- Precio Sugerido -->
            <?php if ($producto['PrecioSugeridoUnidad'] > 0): ?>
            <div class="flex items-center justify-between mt-auto pt-3 border-t-2 border-gray-200 dark:border-[#324467]">
                <div class="flex items-center gap-2">
                    <span class="material-symbols-outlined text-green-600 dark:text-green-500 text-sm">sell</span>
                    <span class="text-gray-500 dark:text-[#92a4c9] text-xs font-semibold">Precio Sugerido</span>
                </div>
                <span class="text-green-600 dark:text-green-400 font-black text-xl">
                    L <?php echo number_format($producto['PrecioSugeridoUnidad'], 2); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Botones de Acción Mejorados -->
        <div class="relative flex flex-col gap-2 p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-[#111722] dark:to-[#0a0f1a] border-t-2 border-gray-200 dark:border-[#324467]">
            <!-- Fila 1: Ingresar y Ver Detalles -->
            <div class="flex gap-2">
                <button @click="abrirModalIngreso(<?php echo htmlspecialchars(json_encode($producto)); ?>)"
                        class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 bg-gradient-to-r from-primary to-blue-600 text-white rounded-lg font-bold hover:from-primary/90 hover:to-blue-600/90 transition-all shadow-md hover:shadow-xl hover:scale-105"
                        title="Ingresar al inventario">
                    <span class="material-symbols-outlined text-sm">add_circle</span>
                    <span class="text-xs">Ingresar</span>
                </button>
                <button @click="verDetalles(<?php echo htmlspecialchars(json_encode($producto)); ?>)"
                        class="flex-1 flex items-center justify-center gap-2 px-3 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-lg font-bold hover:from-blue-600/90 hover:to-cyan-600/90 transition-all hover:scale-105"
                        title="Ver información completa">
                    <span class="material-symbols-outlined text-sm">visibility</span>
                    <span class="text-xs">Detalles</span>
                </button>
            </div>
            <!-- Fila 2: Editar, Eliminar, Ir a Inventario -->
            <div class="flex gap-2">
                <button @click="editarProducto(<?php echo htmlspecialchars(json_encode($producto)); ?>)"
                        class="flex-1 flex items-center justify-center gap-1 px-2 py-2 bg-yellow-500 text-white rounded-lg font-bold hover:bg-yellow-600 transition-all hover:scale-105"
                        title="Editar producto">
                    <span class="material-symbols-outlined text-sm">edit</span>
                    <span class="text-xs">Editar</span>
                </button>
                <button @click="confirmarEliminar(<?php echo $producto['Id']; ?>, '<?php echo htmlspecialchars($producto['NombreProducto'], ENT_QUOTES); ?>')"
                        class="flex-1 flex items-center justify-center gap-1 px-2 py-2 bg-red-500 text-white rounded-lg font-bold hover:bg-red-600 transition-all hover:scale-105"
                        title="Eliminar producto">
                    <span class="material-symbols-outlined text-sm">delete</span>
                    <span class="text-xs">Eliminar</span>
                </button>
                <a href="inventario.php?codigo=<?php echo urlencode($producto['CodigoProducto']); ?>"
                   class="flex items-center justify-center px-3 py-2 bg-gray-700 dark:bg-[#324467] text-white rounded-lg font-bold hover:bg-gray-800 dark:hover:bg-[#3d5578] transition-all hover:scale-105"
                   title="Ir a Inventario">
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                </a>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php else: ?>
<!-- Estado Vacío Mejorado -->
<div class="flex flex-col items-center justify-center py-20">
    <div class="relative">
        <div class="w-32 h-32 rounded-full bg-gradient-to-br from-green-400 to-emerald-600 flex items-center justify-center shadow-2xl mb-6">
            <span class="material-symbols-outlined text-white text-7xl">check_circle</span>
        </div>
        <div class="absolute inset-0 bg-gradient-to-br from-green-400/20 to-emerald-600/20 rounded-full blur-2xl"></div>
    </div>
    <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-2">¡Todo al día!</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-lg mb-1">No hay productos pendientes de ingreso</p>
    <p class="text-gray-400 dark:text-gray-600 text-sm">Todos los productos creados ya están en el inventario</p>
</div>
<?php endif; ?>

</div>

<!-- Footer -->
<footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
<p class="text-gray-500 dark:text-[#92a4c9]">Versión 1.0.0</p>
<a class="text-primary hover:underline" href="#">Ayuda y Soporte</a>
</footer>
</main>
</div>
</div>

<!-- Modal de Ingreso Directo -->
<div x-show="modal.open" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @keydown.escape="modal.open = false" @click.self="modal.open = false">
    <div class="w-full max-w-lg bg-white dark:bg-[#192233] rounded-xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-[#324467]">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Ingresar Producto al Inventario</h3>
            <button @click="modal.open = false" class="text-gray-500 dark:text-[#92a4c9] hover:text-primary">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form @submit.prevent="ingresarProducto" class="p-6">
            <!-- Info del Producto -->
            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">info</span>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-900 dark:text-white" x-text="modal.producto.NombreProducto"></p>
                        <p class="text-sm text-gray-600 dark:text-[#92a4c9]">Código: <span x-text="modal.producto.CodigoProducto"></span></p>
                    </div>
                </div>
            </div>

            <!-- Cantidad -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Cantidad Inicial *</label>
                <input x-model.number="modal.cantidad" type="number" min="0" step="1" required
                       class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary"
                       placeholder="Ej: 100">
            </div>

            <!-- Fecha de Vencimiento -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Fecha de Vencimiento (Opcional)</label>
                <input x-model="modal.fecha_vencimiento" type="date"
                       class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
            </div>

            <!-- Botones -->
            <div class="flex gap-3">
                <button type="button" @click="modal.open = false"
                        class="flex-1 px-4 py-2.5 bg-gray-200 dark:bg-[#324467] text-gray-900 dark:text-white rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-[#3d5578] transition-all">
                    Cancelar
                </button>
                <button type="submit" :disabled="modal.loading"
                        class="flex-1 px-4 py-2.5 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!modal.loading">Ingresar al Inventario</span>
                    <span x-show="modal.loading" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        Procesando...
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Ver Detalles -->
<div x-show="modalDetalles.open" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @keydown.escape="modalDetalles.open = false" @click.self="modalDetalles.open = false">
    <div class="w-full max-w-3xl bg-white dark:bg-[#192233] rounded-xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-[#324467]">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Información Completa del Producto</h3>
            <button @click="modalDetalles.open = false" class="text-gray-500 dark:text-[#92a4c9] hover:text-primary">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            <!-- Imagen del Producto -->
            <div class="mb-6 flex justify-center">
                <template x-if="modalDetalles.producto.FotoProducto">
                    <img :src="modalDetalles.producto.FotoProducto" 
                         :alt="modalDetalles.producto.NombreProducto"
                         class="max-w-xs max-h-64 object-contain rounded-lg shadow-lg">
                </template>
                <template x-if="!modalDetalles.producto.FotoProducto">
                    <div class="w-64 h-64 bg-gradient-to-br from-[#0f172a] via-[#1e293b] to-[#334155] rounded-lg flex items-center justify-center">
                        <span class="material-symbols-outlined text-gray-400 text-8xl">inventory_2</span>
                    </div>
                </template>
            </div>

            <!-- Información en Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Información Básica -->
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-[#324467] pb-2">Información Básica</h4>
                    
                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Nombre del Producto</label>
                        <p class="text-gray-900 dark:text-white font-medium" x-text="modalDetalles.producto.NombreProducto || 'N/A'"></p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Código</label>
                        <p class="text-gray-900 dark:text-white font-medium" x-text="modalDetalles.producto.CodigoProducto || 'N/A'"></p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Marca</label>
                        <p class="text-gray-900 dark:text-white" x-text="modalDetalles.producto.Marca || 'N/A'"></p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Descripción Corta</label>
                        <p class="text-gray-900 dark:text-white" x-text="modalDetalles.producto.DescripcionCorta || 'N/A'"></p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Descripción</label>
                        <p class="text-gray-900 dark:text-white text-sm" x-text="modalDetalles.producto.Descripcion || 'N/A'"></p>
                    </div>
                </div>

                <!-- Precios y Empaque -->
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-[#324467] pb-2">Precios y Empaque</h4>
                    
                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Tipo de Empaque</label>
                        <p class="text-gray-900 dark:text-white" x-text="modalDetalles.producto.TipoEmpaque || 'N/A'"></p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Unidades por Empaque</label>
                        <p class="text-gray-900 dark:text-white" x-text="modalDetalles.producto.UnidadesPorEmpaque || '0'"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Costo/Empaque</label>
                            <p class="text-gray-900 dark:text-white font-medium">L <span x-text="parseFloat(modalDetalles.producto.CostoPorEmpaque || 0).toFixed(2)"></span></p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Costo/Unidad</label>
                            <p class="text-gray-900 dark:text-white font-medium">L <span x-text="parseFloat(modalDetalles.producto.CostoPorUnidad || 0).toFixed(2)"></span></p>
                        </div>
                    </div>

                    <div>
                        <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Margen Sugerido</label>
                        <p class="text-gray-900 dark:text-white font-medium"><span x-text="parseFloat(modalDetalles.producto.MargenSugerido || 0).toFixed(2)"></span>%</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase">Precio Sugerido/Empaque</label>
                            <p class="text-green-600 dark:text-green-400 font-bold text-lg">L <span x-text="parseFloat(modalDetalles.producto.PrecioSugeridoEmpaque || 0).toFixed(2)"></span></p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-green-600 dark:text-green-400 uppercase">Precio Sugerido/Unidad</label>
                            <p class="text-green-600 dark:text-green-400 font-bold text-lg">L <span x-text="parseFloat(modalDetalles.producto.PrecioSugeridoUnidad || 0).toFixed(2)"></span></p>
                        </div>
                    </div>
                </div>

                <!-- Información del Proveedor -->
                <div class="space-y-4 md:col-span-2">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-[#324467] pb-2">Información del Proveedor</h4>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Proveedor</label>
                            <p class="text-gray-900 dark:text-white" x-text="modalDetalles.producto.Proveedor || 'N/A'"></p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Dirección</label>
                            <p class="text-gray-900 dark:text-white text-sm" x-text="modalDetalles.producto.DireccionProveedor || 'N/A'"></p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Contacto</label>
                            <p class="text-gray-900 dark:text-white" x-text="modalDetalles.producto.ContactoProveedor || 'N/A'"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 border-t border-gray-200 dark:border-[#324467] bg-gray-50 dark:bg-[#111722]">
            <button @click="modalDetalles.open = false"
                    class="w-full px-4 py-2.5 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-all">
                Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Editar Producto -->
<div x-show="modalEditar.open" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @keydown.escape="modalEditar.open = false" @click.self="modalEditar.open = false">
    <div class="w-full max-w-4xl bg-white dark:bg-[#192233] rounded-xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-[#324467]">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Editar Producto</h3>
            <button @click="modalEditar.open = false" class="text-gray-500 dark:text-[#92a4c9] hover:text-primary">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form @submit.prevent="guardarEdicion" class="flex-1 overflow-y-auto p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Columna Izquierda -->
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-[#324467] pb-2">Información Básica</h4>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Nombre del Producto *</label>
                        <input x-model="modalEditar.form.NombreProducto" required
                               class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Código del Producto</label>
                        <input x-model="modalEditar.form.CodigoProducto"
                               class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Marca</label>
                        <input x-model="modalEditar.form.Marca"
                               class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Descripción Corta</label>
                        <textarea x-model="modalEditar.form.DescripcionCorta" rows="2"
                                  class="form-textarea w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Descripción</label>
                        <textarea x-model="modalEditar.form.Descripcion" rows="3"
                                  class="form-textarea w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary resize-none"></textarea>
                    </div>
                </div>

                <!-- Columna Derecha -->
                <div class="space-y-4">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-[#324467] pb-2">Empaque y Precios</h4>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Tipo de Empaque</label>
                            <input x-model="modalEditar.form.TipoEmpaque"
                                   class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Unidades/Empaque</label>
                            <input x-model.number="modalEditar.form.UnidadesPorEmpaque" type="number" min="0"
                                   class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Costo/Empaque</label>
                            <input x-model.number="modalEditar.form.CostoPorEmpaque" type="number" step="0.01" min="0"
                                   class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Costo/Unidad</label>
                            <input x-model.number="modalEditar.form.CostoPorUnidad" type="number" step="0.01" min="0"
                                   class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Margen Sugerido (%)</label>
                        <input x-model.number="modalEditar.form.MargenSugerido" type="number" step="0.01" min="0"
                               class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-green-600 dark:text-green-400 mb-2">Precio Sug./Empaque</label>
                            <input x-model.number="modalEditar.form.PrecioSugeridoEmpaque" type="number" step="0.01" min="0"
                                   class="form-input w-full rounded-lg border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20 text-gray-900 dark:text-white focus:border-green-500 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-green-600 dark:text-green-400 mb-2">Precio Sug./Unidad</label>
                            <input x-model.number="modalEditar.form.PrecioSugeridoUnidad" type="number" step="0.01" min="0"
                                   class="form-input w-full rounded-lg border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20 text-gray-900 dark:text-white focus:border-green-500 focus:ring-green-500">
                        </div>
                    </div>

                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-[#324467] pb-2 mt-4">Proveedor</h4>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Nombre del Proveedor</label>
                        <input x-model="modalEditar.form.Proveedor"
                               class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Dirección</label>
                        <input x-model="modalEditar.form.DireccionProveedor"
                               class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Contacto</label>
                        <input x-model="modalEditar.form.ContactoProveedor"
                               class="form-input w-full rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-primary">
                    </div>
                </div>
            </div>
        </form>

        <div class="p-6 border-t border-gray-200 dark:border-[#324467] bg-gray-50 dark:bg-[#111722]">
            <div class="flex gap-3">
                <button type="button" @click="modalEditar.open = false"
                        class="flex-1 px-4 py-2.5 bg-gray-200 dark:bg-[#324467] text-gray-900 dark:text-white rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-[#3d5578] transition-all">
                    Cancelar
                </button>
                <button @click="guardarEdicion" :disabled="modalEditar.loading"
                        class="flex-1 px-4 py-2.5 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!modalEditar.loading">Guardar Cambios</span>
                    <span x-show="modalEditar.loading" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        Guardando...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function appPendientes() {
    return {
        modal: {
            open: false,
            loading: false,
            producto: {},
            cantidad: 0,
            fecha_vencimiento: ''
        },

        modalDetalles: {
            open: false,
            producto: {}
        },

        modalEditar: {
            open: false,
            loading: false,
            form: {}
        },

        init() {
            console.log('App Productos Pendientes inicializada');
        },

        abrirModalIngreso(producto) {
            this.modal.producto = producto;
            this.modal.cantidad = 0;
            this.modal.fecha_vencimiento = '';
            this.modal.open = true;
        },

        verDetalles(producto) {
            this.modalDetalles.producto = producto;
            this.modalDetalles.open = true;
        },

        editarProducto(producto) {
            // Copiar todos los datos del producto al formulario
            this.modalEditar.form = JSON.parse(JSON.stringify(producto));
            this.modalEditar.open = true;
        },

        async guardarEdicion() {
            if (this.modalEditar.loading) return;

            if (!this.modalEditar.form.NombreProducto) {
                mostrarAdvertencia('El nombre del producto es requerido');
                return;
            }

            this.modalEditar.loading = true;

            try {
                const response = await fetch('api/editar_producto_catalogo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.modalEditar.form)
                });

                const data = await response.json();

                if (data.success) {
                    mostrarExito(data.message || 'Producto actualizado exitosamente');
                    this.modalEditar.open = false;
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    mostrarError(data.message || 'Error al actualizar el producto');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de comunicación con el servidor');
            } finally {
                this.modalEditar.loading = false;
            }
        },

        confirmarEliminar(id, nombre) {
            mostrarConfirmacion(
                `¿Estás seguro de eliminar el producto "${nombre}"? Esta acción no se puede deshacer.`,
                () => this.eliminarProducto(id, nombre),
                null
            );
        },

        async eliminarProducto(id, nombre) {
            try {
                const response = await fetch('api/eliminar_producto_catalogo.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });

                const data = await response.json();

                if (data.success) {
                    mostrarExito(data.message || 'Producto eliminado exitosamente');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    mostrarError(data.message || 'Error al eliminar el producto');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de comunicación con el servidor');
            }
        },

        async ingresarProducto() {
            if (this.modal.loading) return;

            if (!this.modal.cantidad || this.modal.cantidad <= 0) {
                mostrarAdvertencia('Por favor ingresa una cantidad válida');
                return;
            }

            this.modal.loading = true;

            try {
                const formData = new FormData();
                formData.append('codigo_producto', this.modal.producto.CodigoProducto);
                formData.append('cantidad', this.modal.cantidad);
                formData.append('fecha_vencimiento', this.modal.fecha_vencimiento);

                const response = await fetch('api/ingresar_producto_pendiente.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    mostrarExito(data.message || 'Producto ingresado exitosamente');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    mostrarError(data.message || 'Error al ingresar el producto');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarError('Error de comunicación con el servidor');
            } finally {
                this.modal.loading = false;
            }
        }
    };
}
</script>

<?php include 'modal_sistema.php'; ?>
</body>
</html>
