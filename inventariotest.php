<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

function logError($message) {
    $logFile = 'api_errors.log';
    $timestamp = date("Y-m-d H:i:s");
    $logMessage = "[$timestamp] " . $message . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Incluir el archivo de lógica de notificaciones
include_once 'generar_notificaciones.php';



$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    logError("Error de conexión: " . $conexion->connect_error);
    die("Error de conexión");
}
$conexion->set_charset("utf8mb4");

$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$resultado = $stmt->get_result();
$row = $resultado->num_rows > 0 ? $resultado->fetch_assoc() : null;
$stmt->close();

$Nombre_Completo = $row ? ($row['Nombre'] . " " . $row['Apellido']) : 'Usuario';
$Perfil = $row['Perfil'] ?? 'uploads/default-avatar.png';
$id = $row['Id'];
$Grupos = "";

$stmt = $conexion->prepare("SELECT DISTINCT Grupo FROM stock ORDER BY Grupo ASC");
$stmt->execute();
$resultado = $stmt->get_result();
while ($row = $resultado->fetch_assoc()) {
    $Grupos .= "<option value='" . htmlspecialchars($row['Grupo']) . "'>" . htmlspecialchars($row['Grupo']) . "</option>";
}
$stmt->close();

$busqueda = trim($_GET['buscar'] ?? '');
$filtro = $_GET['filtro'] ?? 'Todos';

$query = "SELECT * FROM stock WHERE 1=1";
$params = [];
$types = "";

if (!empty($busqueda)) {
    $query .= " AND (Nombre_Producto LIKE ? OR Codigo_Producto LIKE ?)";
    $like = "%$busqueda%";
    $params[] = $like; 
    $params[] = $like;
    $types .= "ss";
}

if ($filtro == 'Activo') {
    $query .= " AND Stock > 10";
} elseif ($filtro == 'Bajo Stock') {
    $query .= " AND Stock > 0 AND Stock <= 10";
} elseif ($filtro == 'Agotado') {
    $query .= " AND Stock = 0";
}

$query .= " ORDER BY Nombre_Producto ASC";

$stmt = $conexion->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$productos = $stmt->get_result();
$total_productos = $productos->num_rows;
$stmt->close();

$ProductoParaEditar = null;
if(isset($_GET['codigo'])){
    $CodigoProducto = $_GET['codigo'];
    
    $stmt = $conexion->prepare("SELECT * FROM stock WHERE Codigo_Producto = ?");
    $stmt->bind_param("s", $CodigoProducto);
    $stmt->execute();
    $resultadocodigo = $stmt->get_result();
    
    if($row = $resultadocodigo->fetch_assoc()){
        $ProductoParaEditar = json_encode($row);
    }
    $stmt->close();
}
generarNotificacionesStock($conexion, $id);

// OBTENER NOTIFICACIONES PARA MOSTRAR
$notificaciones_pendientes = obtenerNotificacionesPendientes($conexion, $id);
$total_notificaciones = contarNotificacionesPendientes($conexion, $id);

 
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Inventario - ReySystemAPP</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#3182CE",
                        "background-light": "#F7FAFC",
                        "background-dark": "#111722",
                        "surface-light": "#FFFFFF",
                        "surface-dark": "#192233",
                        "border-light": "#E2E8F0",
                        "border-dark": "#2D3748",
                        "text-primary-light": "#1A202C",
                        "text-primary-dark": "#FFFFFF",
                        "text-secondary-light": "#4A5568",
                        "text-secondary-dark": "#92a4c9",
                    },
                    fontFamily: { "display": ["Manrope", "sans-serif"] },
                    borderRadius: {"DEFAULT": "0.5rem", "lg": "0.75rem", "xl": "1rem", "full": "9999px"},
                },
            },
        }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        [x-cloak] { display: none !important; }
        .suggestions-container {
            position: absolute;
            z-index: 50;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            margin-top: 0.25rem;
        }
        .dark .suggestions-container {
            background-color: #192233;
            border-color: #2D3748;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
        }
        .suggestion-item {
            padding: 0.75rem;
            cursor: pointer;
            color: #1A202C;
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s;
        }
        .dark .suggestion-item {
            color: #FFFFFF;
            border-bottom-color: #2D3748;
        }
        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-item:hover {
            background-color: #f7fafc;
        }
        .dark .suggestion-item:hover {
            background-color: #2D3748;
        }
        .foto-preview-hover {
            transition: all 0.3s ease;
        }
        .foto-preview-hover:hover {
            transform: scale(1.02);
        }
/* ========================================
   ESTILOS PERSONALIZADOS PARA NOTIFICACIONES
   ======================================== */

/* Scrollbar para navegadores webkit (Chrome, Safari, Edge) */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
    margin: 4px 0;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.3);
    border-radius: 10px;
    transition: background 0.3s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.5);
}

/* Tema oscuro */
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.2);
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.4);
}

/* Para Firefox */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.3) transparent;
    scroll-behavior: smooth;
}

.dark .custom-scrollbar {
    scrollbar-color: rgba(148, 163, 184, 0.2) transparent;
}

/* Ocultar scrollbar en estado inicial pero mantener funcionalidad */
.custom-scrollbar::-webkit-scrollbar-thumb {
    opacity: 0;
    transition: opacity 0.3s ease, background 0.3s ease;
}

.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    opacity: 1;
}

/* Animación para las notificaciones */
@keyframes slideInNotification {
    from {
        opacity: 0;
        transform: translateX(10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.notification-item {
    animation: slideInNotification 0.3s ease-out;
}

    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-text-primary-light dark:text-text-primary-dark" x-data="app()" x-init="init()" x-cloak>

<!-- NAVBAR -->
<header class="flex shrink-0 items-center justify-between whitespace-nowrap border-b border-border-light dark:border-border-dark px-6 py-3 bg-surface-light dark:bg-surface-dark">
    <div class="flex items-center gap-4">
        <div class="size-6 text-primary">
            <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 4H17.3334V17.3334H30.6666V30.6666H44V44H4V4Z" fill="currentColor"></path>
            </svg>
        </div>
        <h2 class="text-lg font-bold">ReySystemAPP</h2>
    </div>
    <div class="flex flex-1 justify-center gap-8">
        <nav class="flex items-center gap-9">
            <a class="text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors" href="index.php">Dashboard</a>
            <a class="text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors" href="nueva_venta.php">Ventas</a>
            <a class="text-sm font-bold text-primary dark:text-primary" href="inventario.php">Inventario</a>
            <a class="text-sm font-medium text-text-secondary-light dark:text-text-secondary-dark hover:text-primary dark:hover:text-primary transition-colors" href="reporte_ventas.php">Reportes</a>
        </nav>
    </div>
<!-- SISTEMA DE NOTIFICACIONES -->
<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" class="relative p-2 rounded-lg hover:bg-black/5 dark:hover:bg-white/5 transition-colors">
        <span class="material-symbols-outlined text-text-secondary-light dark:text-text-secondary-dark">notifications</span>
        <?php if ($total_notificaciones > 0): ?>
            <span class="absolute top-1 right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center animate-pulse">
                <?php echo $total_notificaciones; ?>
            </span>
        <?php endif; ?>
    </button>

    <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-80 bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark rounded-lg shadow-lg z-50 overflow-hidden">
        <div class="p-4 border-b border-border-light dark:border-border-dark flex items-center justify-between">
            <h3 class="font-semibold text-text-primary-light dark:text-text-primary-dark">Notificaciones</h3>
            <?php if ($total_notificaciones > 0): ?>
                <button @click="marcarTodasLeidas()" class="text-xs text-primary hover:underline">Marcar todas como leídas</button>
            <?php endif; ?>
        </div>
        <div class="max-h-96 overflow-y-auto custom-scrollbar">
            <?php if ($total_notificaciones > 0): ?>
                <?php foreach ($notificaciones_pendientes as $notificacion): ?>
                    <div class="p-4 hover:bg-background-light dark:hover:bg-background-dark border-b border-border-light dark:border-border-dark last:border-b-0 transition-colors duration-200 cursor-pointer">
                        <p class="text-sm text-text-primary-light dark:text-text-primary-dark leading-relaxed"><?php echo htmlspecialchars($notificacion['mensaje']); ?></p>
                        <div class="flex items-center justify-between mt-2.5">
                            <span class="text-xs text-text-secondary-light dark:text-text-secondary-dark flex items-center gap-1">
                                <?php 
                                $icono = 'info';
                                $color_icono = 'text-blue-500';
                                if ($notificacion['tipo'] == 'stock_bajo') {
                                    $icono = 'inventory';
                                    $color_icono = 'text-yellow-500';
                                } elseif ($notificacion['tipo'] == 'sin_stock') {
                                    $icono = 'remove_shopping_cart';
                                    $color_icono = 'text-red-500';
                                } elseif ($notificacion['tipo'] == 'por_vencer') {
                                    $icono = 'event_busy';
                                    $color_icono = 'text-orange-500';
                                }
                                ?>
                                <span class="material-symbols-outlined text-sm <?php echo $color_icono; ?>"><?php echo $icono; ?></span>
                                <span class="capitalize"><?php echo str_replace('_', ' ', $notificacion['tipo']); ?></span>
                            </span>
                            <?php if (!empty($notificacion['Codigo_Producto'])): ?>
                                <a href="inventario.php?buscar=<?php echo urlencode($notificacion['Codigo_Producto']); ?>" 
                                   class="text-xs text-primary font-medium hover:underline hover:text-primary/80 transition-colors flex items-center gap-1"
                                   @click="open = false">
                                    Ver Producto
                                    <span class="material-symbols-outlined text-xs">arrow_forward</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-8 text-center text-text-secondary-light dark:text-text-secondary-dark">
                    <span class="material-symbols-outlined text-3xl">notifications_off</span>
                    <p class="mt-2">No tienes notificaciones nuevas</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


    <div class="flex items-center gap-4">
        <button class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-slate-100 dark:bg-[#232f48] text-slate-900 dark:text-white text-sm font-bold leading-normal tracking-[0.015em]">
            <span class="truncate"><?php echo htmlspecialchars($Nombre_Completo); ?></span>
        </button>
        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo htmlspecialchars($Perfil); ?>");'></div>
    </div>
</header>

<main class="flex flex-1 overflow-hidden">
    <div class="flex flex-1 flex-col overflow-y-auto p-6 lg:p-8">

        <!-- HEADER -->
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div class="flex flex-col gap-1">
                <h1 class="text-3xl font-extrabold tracking-tight">Gestión de Inventario</h1>
                <p class="text-text-secondary-light dark:text-text-secondary-dark">Añade, visualiza y gestiona todos tus productos en un solo lugar.</p>
            </div>
        </div>

        <!-- FILTROS -->
        <form method="GET" class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <div class="flex-grow max-w-md">
                <label class="flex flex-col h-12 w-full">
                    <div class="flex w-full flex-1 items-stretch rounded-lg bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark focus-within:ring-2 focus-within:ring-primary">
                        <div class="flex items-center justify-center pl-4">
                            <span class="material-symbols-outlined text-text-secondary-light dark:text-text-secondary-dark">search</span>
                        </div>
                        <input name="buscar" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-r-lg text-text-primary-light dark:text-text-primary-dark focus:outline-0 focus:ring-0 border-none bg-transparent placeholder:text-text-secondary-light placeholder:dark:text-text-secondary-dark pl-2" placeholder="Buscar por nombre o código..." value="<?php echo htmlspecialchars($busqueda); ?>"/>
                    </div>
                </label>
            </div>
            <div class="flex items-center gap-3">
                <select name="filtro" onchange="this.form.submit()" class="flex h-10 items-center justify-center gap-x-2 rounded-lg bg-surface-light dark:bg-surface-dark border border-border-light dark:border-border-dark px-4 hover:border-primary transition-colors">
                    <option value="Todos" <?php echo $filtro == 'Todos' ? 'selected' : ''; ?>>Todos</option>
                    <option value="Activo" <?php echo $filtro == 'Activo' ? 'selected' : ''; ?>>Activo</option>
                    <option value="Bajo Stock" <?php echo $filtro == 'Bajo Stock' ? 'selected' : ''; ?>>Bajo Stock</option>
                    <option value="Agotado" <?php echo $filtro == 'Agotado' ? 'selected' : ''; ?>>Agotado</option>
                </select>
            </div>
        </form>

        <!-- BOTÓN PARA ABRIR MODAL -->
        <div class="flex justify-end mb-4">
            <button @click="abrirModal()" class="flex items-center gap-2 px-5 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition shadow-md">
                <span class="material-symbols-outlined text-lg">add</span>
                Ingresar Productos
            </button>
        </div>

        <!-- TABLA -->
        <div class="flex-1 overflow-hidden rounded-lg border border-border-light dark:border-border-dark bg-surface-light dark:bg-surface-dark">
            <div class="overflow-x-auto h-full">
                <table class="min-w-full text-left">
                    <thead class="border-b border-border-light dark:border-border-dark sticky top-0 bg-background-light dark:bg-background-dark z-10">
                        <tr>
                            <th class="px-6 py-4 text-sm font-semibold text-text-secondary-light dark:text-text-secondary-dark">Producto</th>
                            <th class="px-6 py-4 text-sm font-semibold text-text-secondary-light dark:text-text-secondary-dark">Código</th>
                            <th class="px-6 py-4 text-sm font-semibold text-text-secondary-light dark:text-text-secondary-dark">Marca</th>
                            <th class="px-6 py-4 text-sm font-semibold text-text-secondary-light dark:text-text-secondary-dark">Stock</th>
                            <th class="px-6 py-4 text-sm font-semibold text-text-secondary-light dark:text-text-secondary-dark">Precio</th>
                            <th class="px-6 py-4 text-sm font-semibold text-text-secondary-light dark:text-text-secondary-dark">Estado</th>
                            <th class="px-6 py-4 text-sm font-semibold text-text-secondary-light dark:text-text-secondary-dark text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_productos > 0): ?>
                            <?php while ($producto = $productos->fetch_assoc()): ?>
                                <?php
                                $stock = $producto['Stock'];
                                if ($stock == 0) {
                                    $estado_class = 'bg-red-500/10 text-red-500';
                                    $estado_dot = 'bg-red-500';
                                    $estado_text = 'Agotado';
                                } elseif ($stock <= 10) {
                                    $estado_class = 'bg-yellow-500/10 text-yellow-500';
                                    $estado_dot = 'bg-yellow-500';
                                    $estado_text = 'Bajo Stock';
                                } else {
                                    $estado_class = 'bg-green-500/10 text-green-500';
                                    $estado_dot = 'bg-green-500';
                                    $estado_text = 'Activo';
                                }
                                ?>
                                <tr class="border-b border-border-light dark:border-border-dark hover:bg-background-light dark:hover:bg-background-dark transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><?php echo htmlspecialchars($producto['Nombre_Producto']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php echo htmlspecialchars($producto['Codigo_Producto']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php echo htmlspecialchars($producto['Marca'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary-light dark:text-text-secondary-dark"><?php echo $producto['Stock']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary-light dark:text-text-secondary-dark">L <?php echo number_format($producto['Precio_Unitario'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <div class="inline-flex items-center gap-2 rounded-full <?php echo $estado_class; ?> px-3 py-1 text-sm font-medium">
                                            <div class="size-2 rounded-full <?php echo $estado_dot; ?>"></div>
                                            <?php echo $estado_text; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="editarProducto('<?php echo htmlspecialchars($producto['Codigo_Producto']); ?>')" class="flex h-8 w-8 items-center justify-center rounded-md hover:bg-black/5 dark:hover:bg-white/5 transition-colors" title="Editar">
                                                <span class="material-symbols-outlined text-lg text-text-secondary-light dark:text-text-secondary-dark">edit</span>
                                            </button>
                                            <button @click="eliminarProducto(<?php echo $producto['Id']; ?>, '<?php echo htmlspecialchars($producto['Nombre_Producto'], ENT_QUOTES); ?>')" class="flex h-8 w-8 items-center justify-center rounded-md hover:bg-black/5 dark:hover:bg-white/5 transition-colors" title="Eliminar">
                                                <span class="material-symbols-outlined text-lg text-red-500">delete</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-text-secondary-light dark:text-text-secondary-dark">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="material-symbols-outlined text-4xl">inventory_2</span>
                                        <p>No se encontraron productos</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex items-center justify-between mt-4 px-2">
            <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark">
                Mostrando <?php echo $total_productos; ?> resultado(s)
            </p>
        </div>
    </div>

    <!-- MODAL -->
    <div x-show="modal.open" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @keydown.escape="modal.open = false" @click.self="modal.open = false">
        <div class="w-full max-w-2xl bg-surface-light dark:bg-surface-dark rounded-xl shadow-2xl overflow-hidden">
            <div class="flex items-center justify-between p-6 border-b border-border-light dark:border-border-dark">
                <h3 class="text-xl font-bold" x-text="modal.editMode ? 'Editar Producto' : 'Nuevo Producto'"></h3>
                <button @click="modal.open = false" class="text-text-secondary-light dark:text-text-secondary-dark hover:text-primary">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="enviarFormulario" class="p-6 space-y-5" enctype="multipart/form-data">
                <input type="hidden" x-model="modal.product_id" name="product_id">
                <input type="hidden" :value="modal.editMode ? '1' : '0'" name="edit_mode">

                <!-- CHECK AUTOCOMPLETAR -->
                <div class="flex items-center gap-2 mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <input type="checkbox" x-model="modal.autocompletar" @change="toggleAutocompletar()" class="w-4 h-4 text-primary bg-gray-100 border-gray-300 rounded focus:ring-primary dark:focus:ring-primary dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                    <label class="text-sm font-medium text-gray-900 dark:text-gray-300 cursor-pointer">
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm">auto_awesome</span>
                            Buscar desde catálogo de productos (API)
                        </span>
                    </label>
                </div>

                <!-- CÓDIGO -->
                <div class="relative">
                    <label class="block text-sm font-medium mb-1.5">Código / SKU *</label>
                    <input x-model="modal.form.codigo" @input="buscarProducto()" @focus="modal.campoActivo = 'codigo'" required
                           class="form-input w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary"
                           placeholder="Ej. LP-GM-001">
                    <div x-show="modal.sugerencias.length > 0 && modal.campoActivo === 'codigo'" class="suggestions-container">
                        <template x-for="s in modal.sugerencias" :key="s.Codigo_Producto || s.CodigoProducto">
                            <div @click="seleccionarSugerencia(s)" class="suggestion-item">
                                <div class="font-medium" x-text="s.Nombre_Producto || s.NombreProducto"></div>
                                <div class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                                    SKU: <span x-text="s.Codigo_Producto || s.CodigoProducto"></span> | 
                                    Precio: L <span x-text="(s.Precio_Unitario || s.PrecioSugeridoUnidad || 0).toFixed(2)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- NOMBRE -->
                <div class="relative">
                    <label class="block text-sm font-medium mb-1.5">Nombre del Producto *</label>
                    <input x-model="modal.form.nombre" @input="buscarProducto()" @focus="modal.campoActivo = 'nombre'" required
                           class="form-input w-full rounded-md border-border-light dark:bg-background-dark focus:border-primary focus:ring-primary"
                           placeholder="Ej. Laptop Gamer">
                    <div x-show="modal.sugerencias.length > 0 && modal.campoActivo === 'nombre'" class="suggestions-container">
                        <template x-for="s in modal.sugerencias" :key="s.Codigo_Producto || s.CodigoProducto">
                            <div @click="seleccionarSugerencia(s)" class="suggestion-item">
                                <div class="font-medium" x-text="s.Nombre_Producto || s.NombreProducto"></div>
                                <div class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                                    SKU: <span x-text="s.Codigo_Producto || s.CodigoProducto"></span> | 
                                    Precio: L <span x-text="(s.Precio_Unitario || s.PrecioSugeridoUnidad || 0).toFixed(2)"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>


                <!-- FOTO -->
                <div>
                    <label class="block text-sm font-medium mb-1.5">Foto del Producto</label>
                    <div @click="$refs.photoInput.click()" class="relative w-full h-32 rounded-md overflow-hidden shadow border-2 border-dashed border-border-light dark:border-border-dark bg-gradient-to-br from-[#0f172a] via-[#1e293b] to-[#334155] flex items-center justify-center cursor-pointer hover:border-primary transition-all foto-preview-hover">
                        <template x-if="modal.form.foto">
                            <img :src="modal.form.foto" class="w-full h-full object-cover">
                        </template>
                        <template x-if="!modal.form.foto">
                            <div class="text-center pointer-events-none">
                                <span class="material-symbols-outlined text-3xl text-gray-400">add_photo_alternate</span>
                                <p class="text-gray-400 text-xs mt-1">Click para subir</p>
                            </div>
                        </template>
                    </div>
                    <input x-ref="photoInput" type="file" accept="image/*" @change="cargarFoto($event)" class="hidden">
                    <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">Máximo 3MB - JPG, PNG, GIF, WEBP</p>
                </div>

                <!-- DESCRIPCIÓN -->
                <div>
                    <label class="block text-sm font-medium mb-1.5">Descripción</label>
                    <textarea x-model="modal.form.descripcion" rows="3"
                              class="form-textarea w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary resize-none"
                              placeholder="Describe el producto..."></textarea>
                </div>

                <!-- PRECIO Y CANTIDAD -->
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-1.5">Precio (L) *</label>
                        <input x-model.number="modal.form.precio" type="number" step="0.01" min="0" required
                               class="form-input w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary">
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-1.5">Cantidad *</label>
                        <input x-model.number="modal.form.cantidad" type="number" min="0" required
                               class="form-input w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary">
                    </div>
                </div>

                <!-- CATEGORÍA Y VENCIMIENTO -->
                <div class="flex gap-4">
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-1.5">Categoría</label>
                        <select x-model="modal.form.categoria"
                                class="form-select w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary">
                            <option value="">Seleccionar categoría</option>
                            <?php echo $Grupos; ?>
                        </select>
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-medium mb-1.5">Fecha de Vencimiento</label>
                        <input x-model="modal.form.fecha_vencimiento" type="date"
                               class="form-input w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary">
                    </div>
                </div>

                <!-- BOTONES -->
                <div class="mt-6 pt-6 border-t border-border-light dark:border-border-dark">
                    <button type="submit" :disabled="modal.loading"
                            class="flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-md h-12 px-6 bg-primary text-white text-base font-bold hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!modal.loading" x-text="modal.editMode ? 'Actualizar Producto' : 'Añadir Producto al Inventario'"></span>
                        <span x-show="modal.loading" class="flex items-center gap-2">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            Guardando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
function app() {
    return {
        modal: {
            open: false,
            editMode: false,
            loading: false,
            autocompletar: true,
            product_id: '',
            sugerencias: [],
            campoActivo: '',
            form: {
                nombre: '', 
                codigo: '', 
                descripcion: '', 
                precio: 0, 
                cantidad: 0,
                categoria: '', 
                fecha_vencimiento: '', 
                foto: '', 
                fotoFile: null
            }
        },
        _searchTimeout: null,

        init() {
            <?php if ($ProductoParaEditar): ?>
            const producto = <?php echo $ProductoParaEditar; ?>;
            this.cargarProductoParaEditar(producto);
            <?php endif; ?>
            
        },
marcarTodasLeidas() {
    fetch('marcar_notificaciones_leidas.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            mostrarError('Error al marcar notificaciones: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        mostrarError('Error al procesar la solicitud');
    });
}
        cargarProductoParaEditar(p) {
            this.modal.editMode = true;
            this.modal.autocompletar = false;
            this.modal.open = true;
            this.modal.product_id = p.Id;
            this.modal.form = {
                nombre: p.Nombre_Producto,
                codigo: p.Codigo_Producto,
                descripcion: p.Descripcion || '',
                precio: parseFloat(p.Precio_Unitario),
                cantidad: 0,
                categoria: p.Grupo || '',
                fecha_vencimiento: p.Fecha_Vencimiento?.split(' ')[0] || '',
                foto: p.FotoProducto || '',
                fotoFile: null
            };
        },

        abrirModal() {
            this.limpiarFormulario();
            this.modal.open = true;
            this.modal.editMode = false;
        },

        limpiarFormulario() {
            this.modal.form = { 
                nombre: '', 
                codigo: '', 
                descripcion: '', 
                precio: 0, 
                cantidad: 0, 
                categoria: '', 
                fecha_vencimiento: '', 
                foto: '', 
                fotoFile: null 
            };
            this.modal.sugerencias = [];
            this.modal.product_id = '';
            this.modal.editMode = false;
            this.modal.autocompletar = true;
            this.modal.campoActivo = '';
            this.$nextTick(() => { 
                if (this.$refs.photoInput) this.$refs.photoInput.value = ''; 
            });
        },

        buscarProducto() {
            clearTimeout(this._searchTimeout);
            
            const codigo = this.modal.form.codigo?.trim() || '';
            const nombre = this.modal.form.nombre?.trim() || '';

            if (codigo.length < 2 && nombre.length < 3) {
                this.modal.sugerencias = [];
                return;
            }

            const q = codigo.length >= 2 ? codigo : nombre;
            const tipo = codigo.length >= 2 ? 'codigo' : 'nombre';

            this._searchTimeout = setTimeout(() => {
                this.cargarSugerencias(q, tipo);
            }, 300);
        },

        cargarSugerencias(q, tipo) {
            const params = new URLSearchParams({
                q: q,
                tipo: tipo,
                autocompletar: this.modal.autocompletar ? '1' : '0'
            });

            console.log('Buscando con autocompletar:', this.modal.autocompletar);

            fetch(`buscar_sugerencias.php?${params}`)
                .then(r => {
                    if (!r.ok) {
                        throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    }
                    return r.json();
                })
                .then(data => {
                    console.log('Sugerencias recibidas:', data);
                    if (data.success && Array.isArray(data.suggestions)) {
                        this.modal.sugerencias = data.suggestions;
                        console.log(`${data.count || data.suggestions.length} sugerencias de ${data.source}`);
                    } else {
                        console.warn('No hay sugerencias o formato incorrecto:', data);
                        this.modal.sugerencias = [];
                    }
                })
                .catch(err => {
                    console.error('Error al cargar sugerencias:', err);
                    this.modal.sugerencias = [];
                    mostrarError('Error al buscar productos: ' + err.message);
                });
        },

        seleccionarSugerencia(s) {
            const codigo = s.Codigo_Producto || s.CodigoProducto;
            
            if (!codigo) {
                console.error('Sugerencia sin código:', s);
                return;
            }
            
            this.cargarProductoCompleto(codigo);
            this.modal.sugerencias = [];
        },

        cargarProductoCompleto(codigo) {
            const params = new URLSearchParams({
                codigo: codigo,
                autocompletar: this.modal.autocompletar ? '1' : '0'
            });

            console.log('Cargando producto completo con autocompletar:', this.modal.autocompletar);

            fetch(`obtener_producto.php?${params}`)
                .then(r => {
                    if (!r.ok) {
                        throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    }
                    return r.json();
                })
                .then(data => {
                    console.log('Producto completo recibido:', data);
                    
                    if (!data.success || !data.producto) {
                        mostrarError('Producto no encontrado: ' + (data.message || 'Error desconocido'));
                        return;
                    }
                    
                    const p = data.producto;
                    
                    this.modal.form = {
                        nombre: p.Nombre_Producto || p.NombreProducto || '',
                        codigo: p.Codigo_Producto || p.CodigoProducto || '',
                        descripcion: p.Descripcion || '',
                        precio: parseFloat(p.Precio_Unitario || p.PrecioSugeridoUnidad || 0),
                        cantidad: 0,
                        categoria: p.Grupo || '',
                        fecha_vencimiento: p.Fecha_Vencimiento?.split(' ')[0] || '',
                        foto: p.FotoProducto || '',
                        fotoFile: null
                    };
                    
                    console.log('Producto cargado desde:', data.source);
                    
                    this.$nextTick(() => { 
                        if (this.$refs.photoInput) this.$refs.photoInput.value = ''; 
                    });
                })
                .catch(err => {
                    console.error('Error al cargar producto:', err);
                    mostrarError('Error al cargar el producto: ' + err.message);
                });
        },

        cargarFoto(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            if (file.size > 3*1024*1024) {
                mostrarInfo('La imagen es muy grande (máximo 3MB)');
                e.target.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = (ev) => {
                this.modal.form.foto = ev.target.result;
                this.modal.form.fotoFile = file;
            };
            reader.readAsDataURL(file);
        },

        async enviarFormulario() {
            if (this.modal.loading) return;
            
            const f = this.modal.form;
            if (!f.nombre || !f.codigo || f.precio <= 0 || f.cantidad < 0) {
                mostrarAdvertencia('Por favor completa todos los campos obligatorios correctamente');
                return;
            }
            
            this.modal.loading = true;
            
            const fd = new FormData();
            fd.append('nombre', f.nombre);
            fd.append('codigo', f.codigo);
            fd.append('descripcion', f.descripcion);
            fd.append('precio', f.precio);
            fd.append('cantidad', f.cantidad);
            fd.append('categoria', f.categoria);
            fd.append('fecha_vencimiento', f.fecha_vencimiento);
            fd.append('edit_mode', this.modal.editMode ? '1' : '0');
            
            if (this.modal.editMode) {
                fd.append('product_id', this.modal.product_id);
            }
            
            if (f.fotoFile) {
                fd.append('product_photo', f.fotoFile);
            } else if (f.foto && f.foto.startsWith('http')) {
                fd.append('foto_url', f.foto);
            }

            try {
                const url = this.modal.editMode ? 'actualizar_producto.php' : 'agregar_stock_simple.php';
                const res = await fetch(url, { method: 'POST', body: fd });
                const data = await res.json();
                
                if (data.success) {
                    mostrarExito('Producto guardado exitosamente');
                    location.reload();
                } else {
                    mostrarError('Error: ' + (data.message || 'No se pudo guardar el producto'));
                }
            } catch (err) {
                console.error('Error al enviar formulario:', err);
                mostrarError('Error al guardar el producto');
            } finally {
                this.modal.loading = false;
            }
        },

        editarProducto(codigo) {
            this.modal.editMode = true;
            this.modal.autocompletar = false;
            this.modal.open = true;
            this.modal.sugerencias = [];
            
            const params = new URLSearchParams({
                codigo: codigo,
                autocompletar: '0'
            });
            
            fetch(`obtener_producto.php?${params}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.success) { 
                        mostrarInfo('Producto no encontrado'); 
                        return; 
                    }
                    const p = data.producto;
                    this.modal.product_id = p.Id;
                    this.modal.form = {
                        nombre: p.Nombre_Producto,
                        codigo: p.Codigo_Producto,
                        descripcion: p.Descripcion || '',
                        precio: parseFloat(p.Precio_Unitario),
                        cantidad: 0,
                        categoria: p.Grupo || '',
                        fecha_vencimiento: p.Fecha_Vencimiento?.split(' ')[0] || '',
                        foto: p.FotoProducto || '',
                        fotoFile: null
                    };
                })
                .catch(err => {
                    console.error('Error al editar producto:', err);
                    mostrarError('Error al cargar el producto');
                });
        },

        eliminarProducto(id, nombre) {
            if (!confirm(`¿Estás seguro de eliminar "${nombre}"?`)) return;
            
            fetch('eliminar_producto.php', { 
                method: 'POST', 
                headers: {'Content-Type': 'application/json'}, 
                body: JSON.stringify({id}) 
            })
            .then(r => r.json())
            .then(d => { 
                if (d.success) { 
                    mostrarExito('Producto eliminado exitosamente'); 
                    location.reload(); 
                } else {
                    mostrarError('Error al eliminar: ' + (d.message || 'Error desconocido'));
                }
            })
            .catch(err => {
                console.error('Error al eliminar:', err);
                mostrarError('Error al eliminar el producto');
            });
        },

        toggleAutocompletar() {
            this.modal.sugerencias = [];
            
            console.log('Autocompletar cambiado a:', this.modal.autocompletar);
            
            const codigo = this.modal.form.codigo?.trim() || '';
            const nombre = this.modal.form.nombre?.trim() || '';
            
            if (codigo.length >= 2 || nombre.length >= 3) {
                this.buscarProducto();
            }
        }
    };
}
</script>
<?php include 'modal_sistema.php'; ?>
</body>
</html>