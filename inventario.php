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

$stmt = $conexion->prepare("SELECT nombre FROM Categorias ORDER BY nombre ASC");
$stmt->execute();
$resultado = $stmt->get_result();
while ($row = $resultado->fetch_assoc()) {
    $Grupos .= "<option value='" . htmlspecialchars($row['nombre']) . "'>" . htmlspecialchars($row['nombre']) . "</option>";
}
$stmt->close();

$busqueda = trim($_GET['buscar'] ?? '');
$filtro = $_GET['filtro'] ?? 'Todos';
$pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
$productos_por_pagina = 25;
$offset = ($pagina_actual - 1) * $productos_por_pagina;

// Primero contar total de productos
$query_count = "SELECT COUNT(*) as total FROM stock WHERE 1=1";
$params_count = [];
$types_count = "";

if (!empty($busqueda)) {
    $query_count .= " AND (Nombre_Producto LIKE ? OR Codigo_Producto LIKE ?)";
    $like = "%$busqueda%";
    $params_count[] = $like; 
    $params_count[] = $like;
    $types_count .= "ss";
}

if ($filtro == 'Activo') {
    $query_count .= " AND Stock > 10";
} elseif ($filtro == 'Bajo Stock') {
    $query_count .= " AND Stock > 0 AND Stock <= 10";
} elseif ($filtro == 'Agotado') {
    $query_count .= " AND Stock = 0";
}

$stmt_count = $conexion->prepare($query_count);
if (!empty($params_count)) {
    $stmt_count->bind_param($types_count, ...$params_count);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_productos = $result_count->fetch_assoc()['total'];
$total_paginas = ceil($total_productos / $productos_por_pagina);
$stmt_count->close();

// Ahora obtener productos de la página actual
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

$query .= " ORDER BY Nombre_Producto ASC LIMIT ? OFFSET ?";
$params[] = $productos_por_pagina;
$params[] = $offset;
$types .= "ii";

$stmt = $conexion->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$productos = $stmt->get_result();
$productos_en_pagina = $productos->num_rows;
$stmt->close();

$ProductoParaEditar = null;
if(isset($_GET['codigo'])){
    $CodigoProducto = $_GET['codigo'];
    
    // Primero buscar en stock
    $stmt = $conexion->prepare("SELECT * FROM stock WHERE Codigo_Producto = ?");
    $stmt->bind_param("s", $CodigoProducto);
    $stmt->execute();
    $resultadocodigo = $stmt->get_result();
    
    if($row = $resultadocodigo->fetch_assoc()){
        // Producto encontrado en stock
        $ProductoParaEditar = json_encode($row);
    } else {
        // Si no está en stock, buscar en creacion_de_productos
        $stmt->close();
        $stmt = $conexion->prepare("SELECT * FROM creacion_de_productos WHERE CodigoProducto = ?");
        $stmt->bind_param("s", $CodigoProducto);
        $stmt->execute();
        $resultadoPendiente = $stmt->get_result();
        
        if($rowPendiente = $resultadoPendiente->fetch_assoc()){
            // Adaptar estructura de creacion_de_productos a stock
            $productoAdaptado = [
                'Nombre_Producto' => $rowPendiente['NombreProducto'],
                'Codigo_Producto' => $rowPendiente['CodigoProducto'],
                'Marca' => $rowPendiente['Marca'] ?? '',
                'Descripcion' => $rowPendiente['Descripcion'] ?? '',
                'Precio_Unitario' => $rowPendiente['PrecioSugeridoUnidad'] ?? 0,
                'Stock' => 0,
                'Grupo' => 'General',
                'FotoProducto' => $rowPendiente['FotoProducto'] ?? '',
                'Fecha_Vencimiento' => '',
                'es_producto_pendiente' => true // Flag para saber que viene de pendientes
            ];
            $ProductoParaEditar = json_encode($productoAdaptado);
        }
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
                    "primary": "#1152d4",
                    "background-light": "#f6f6f8",
                    "background-dark": "#101622",
                    "surface-light": "#ffffff",
                    "surface-dark": "#192233",
                    "border-light": "#e2e8f0",
                    "border-dark": "#2D3748",
                    "text-primary-light": "#1A202C",
                    "text-primary-dark": "#FFFFFF",
                    "text-secondary-light": "#718096",
                    "text-secondary-dark": "#92a4c9",
                    },
                    fontFamily: { "display": ["Poppins", "sans-serif"] },
                    borderRadius: {"DEFAULT": "0.5rem", "lg": "0.75rem", "xl": "1rem", "full": "9999px"},
                },
            },
        }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <style>
        [x-cloak] { display: none !important; }
        
        /* Skeleton Loader Animation */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }
        
        .skeleton {
            animation: shimmer 2s infinite linear;
            background: linear-gradient(to right, #f0f0f0 4%, #e0e0e0 25%, #f0f0f0 36%);
            background-size: 1000px 100%;
        }
        
        .dark .skeleton {
            background: linear-gradient(to right, #1a2332 4%, #111722 25%, #1a2332 36%);
            background-size: 1000px 100%;
        }
        
        /* Smooth page transitions */
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Bounce-in animation for checkmark */
        @keyframes bounceIn {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .animate-bounce-in {
            animation: bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
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
<?php include "pwa-head.php"; ?>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-text-primary-light dark:text-text-primary-dark" x-data="app()" x-init="init()">

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
    <div class="flex items-center gap-4">
        <!-- SISTEMA DE NOTIFICACIONES REYSYSTEM -->
        <?php include 'notificaciones_component.php'; ?>
        
        <button class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-slate-100 dark:bg-[#232f48] text-slate-900 dark:text-white text-sm font-bold leading-normal tracking-[0.015em]">
            <span class="truncate"><?php echo htmlspecialchars($Nombre_Completo); ?></span>
        </button>
        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo htmlspecialchars($Perfil); ?>");'></div>
    </div>
</header>

<main class="flex flex-1 overflow-hidden">
    <div class="flex flex-1 flex-col overflow-y-auto p-6 lg:p-8">

        <!-- HEADER CON GRADIENTE -->
        <div class="fade-in relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary via-blue-600 to-indigo-700 p-8 mb-8 shadow-xl">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <span class="material-symbols-outlined text-white text-5xl">inventory_2</span>
                    <h1 class="text-4xl font-black text-white tracking-tight">Gestión de Inventario</h1>
                </div>
                <p class="text-blue-100 text-lg">Controla y administra tu stock de productos en tiempo real</p>
            </div>
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-32 -mt-32"></div>
            <div class="absolute bottom-0 right-0 w-48 h-48 bg-white/5 rounded-full -mr-24 -mb-24"></div>
        </div>

        <!-- STATS CARDS -->
        <div class="fade-in grid grid-cols-1 md:grid-cols-3 gap-4 mb-6" style="animation-delay: 0.1s">
            <div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-100 text-sm font-medium">Total Productos</p>
                        <p class="text-3xl font-bold mt-1"><?php echo $total_productos; ?></p>
                    </div>
                    <span class="material-symbols-outlined text-5xl opacity-20">inventory</span>
                </div>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-100 text-sm font-medium">Filtro Activo</p>
                        <p class="text-3xl font-bold mt-1"><?php echo $filtro; ?></p>
                    </div>
                    <span class="material-symbols-outlined text-5xl opacity-20">filter_alt</span>
                </div>
            </div>
            <div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-100 text-sm font-medium">Gestión Rápida</p>
                        <button @click="abrirModal()" class="mt-2 px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-semibold transition-colors backdrop-blur-sm">
                            + Agregar Producto
                        </button>
                    </div>
                    <span class="material-symbols-outlined text-5xl opacity-20">add_circle</span>
                </div>
            </div>
        </div>

        <!-- FILTROS MEJORADOS -->
        <form method="GET" class="fade-in bg-white dark:bg-[#192233] rounded-xl p-6 mb-6 shadow-sm border border-gray-200 dark:border-gray-700" style="animation-delay: 0.2s">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex-grow max-w-md">
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Buscar Producto</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                        <input id="inputBusqueda" name="buscar" 
                               class="w-full pl-10 pr-4 py-3 rounded-lg border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all" 
                               placeholder="Buscar por nombre o código..." 
                               value="<?php echo htmlspecialchars($busqueda); ?>"/>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Filtrar por Estado</label>
                    <select name="filtro" onchange="this.form.submit()" 
                            class="px-4 py-3 rounded-lg border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white hover:border-primary focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer">
                        <option value="Todos" <?php echo $filtro == 'Todos' ? 'selected' : ''; ?>>📦 Todos</option>
                        <option value="Activo" <?php echo $filtro == 'Activo' ? 'selected' : ''; ?>>✅ Activo</option>
                        <option value="Bajo Stock" <?php echo $filtro == 'Bajo Stock' ? 'selected' : ''; ?>>⚠️ Bajo Stock</option>
                        <option value="Agotado" <?php echo $filtro == 'Agotado' ? 'selected' : ''; ?>>🚫 Agotado</option>
                    </select>
                </div>
            </div>
        </form>

        <!-- TABLA MEJORADA CON MÁS DESTAQUE VISUAL -->
        <div class="fade-in relative" style="animation-delay: 0.3s">
            <!-- Sombra decorativa -->
            <div class="absolute inset-0 bg-gradient-to-r from-primary/5 via-blue-500/5 to-purple-500/5 rounded-xl blur-xl"></div>
        <!-- TABLA MEJORADA -->
        <div class="relative bg-white dark:bg-[#192233] rounded-xl shadow-2xl border-2 border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-[#111722] dark:to-[#1a2332] border-b-2 border-primary/20">
                            <th class="px-6 py-4 text-left">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-sm">inventory</span>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Producto</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-sm">tag</span>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Código</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-sm">label</span>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Marca</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-sm">warehouse</span>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Stock</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-sm">payments</span>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Precio</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-left">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-sm">check_circle</span>
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Estado</span>
                                </div>
                            </th>
                            <th class="px-6 py-4 text-right">
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Acciones</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <?php if ($total_productos > 0): ?>
                            <?php while ($producto = $productos->fetch_assoc()): ?>
                                <?php
                                $stock = $producto['Stock'];
                                if ($stock == 0) {
                                    $estado_class = 'bg-gradient-to-r from-red-500/10 to-red-600/10 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800';
                                    $estado_dot = 'bg-red-500 shadow-lg shadow-red-500/50';
                                    $estado_text = 'Agotado';
                                    $estado_icon = 'cancel';
                                } elseif ($stock <= 10) {
                                    $estado_class = 'bg-gradient-to-r from-yellow-500/10 to-amber-600/10 text-yellow-600 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-800';
                                    $estado_dot = 'bg-yellow-500 shadow-lg shadow-yellow-500/50';
                                    $estado_text = 'Bajo Stock';
                                    $estado_icon = 'warning';
                                } else {
                                    $estado_class = 'bg-gradient-to-r from-green-500/10 to-emerald-600/10 text-green-600 dark:text-green-400 border border-green-200 dark:border-green-800';
                                    $estado_dot = 'bg-green-500 shadow-lg shadow-green-500/50';
                                    $estado_text = 'Activo';
                                    $estado_icon = 'check_circle';
                                }
                                ?>
                                <tr class="hover:bg-gradient-to-r hover:from-primary/5 hover:to-transparent transition-all duration-200 group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-primary/10 to-blue-600/10 flex items-center justify-center">
                                                <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($producto['Nombre_Producto']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-gray-100 dark:bg-gray-800 text-xs font-mono font-medium text-gray-700 dark:text-gray-300">
                                            <?php echo htmlspecialchars($producto['Codigo_Producto']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($producto['Marca'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-sm font-semibold">
                                            <span class="material-symbols-outlined text-xs">inventory</span>
                                            <?php echo $producto['Stock']; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-bold text-gray-900 dark:text-white">L <?php echo number_format($producto['Precio_Unitario'], 2); ?></span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="inline-flex items-center gap-2 rounded-full <?php echo $estado_class; ?> px-3 py-1.5 text-xs font-bold">
                                            <div class="size-2 rounded-full <?php echo $estado_dot; ?> animate-pulse"></div>
                                            <span class="material-symbols-outlined text-sm"><?php echo $estado_icon; ?></span>
                                            <?php echo $estado_text; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="editarProducto('<?php echo htmlspecialchars($producto['Codigo_Producto']); ?>')" 
                                                    class="flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 dark:text-blue-400 transition-all hover:scale-105" 
                                                    title="Editar">
                                                <span class="material-symbols-outlined text-sm">edit</span>
                                                <span class="text-xs font-semibold">Editar</span>
                                            </button>
                                            <button @click="eliminarProducto(<?php echo $producto['Id']; ?>, '<?php echo addslashes($producto['Nombre_Producto']); ?>')" 
                                                    class="flex items-center gap-1 px-3 py-1.5 rounded-lg bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 transition-all hover:scale-105" 
                                                    title="Eliminar">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                                <span class="text-xs font-semibold">Eliminar</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center gap-4">
                                        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-5xl text-gray-400">inventory_2</span>
                                        </div>
                                        <div>
                                            <p class="text-lg font-semibold text-gray-900 dark:text-white">No se encontraron productos</p>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Intenta ajustar los filtros o agregar nuevos productos</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div> <!-- Cierre del contenedor con sombra -->

        <!-- PAGINACIÓN ELEGANTE -->
        <div class="fade-in mt-6 flex flex-col sm:flex-row items-center justify-between gap-4 bg-white dark:bg-[#192233] rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700" style="animation-delay: 0.4s">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Mostrando <span class="font-bold text-gray-900 dark:text-white"><?php echo (($pagina_actual - 1) * $productos_por_pagina) + 1; ?></span> - 
                <span class="font-bold text-gray-900 dark:text-white"><?php echo min($pagina_actual * $productos_por_pagina, $total_productos); ?></span> 
                de <span class="font-bold text-gray-900 dark:text-white"><?php echo $total_productos; ?></span> productos
            </div>
            
            <?php if ($total_paginas > 1): ?>
            <div class="flex items-center gap-2">
                <!-- Botón Primera -->
                <?php if ($pagina_actual > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => 1])); ?>" 
                   class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-primary hover:text-white transition-all">
                    <span class="material-symbols-outlined text-sm">first_page</span>
                </a>
                <?php endif; ?>
                
                <!-- Botón Anterior -->
                <?php if ($pagina_actual > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual - 1])); ?>" 
                   class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-primary hover:text-white transition-all font-semibold">
                    Anterior
                </a>
                <?php endif; ?>
                
                <!-- Números de página -->
                <div class="flex items-center gap-1">
                    <?php
                    $rango = 2;
                    $inicio = max(1, $pagina_actual - $rango);
                    $fin = min($total_paginas, $pagina_actual + $rango);
                    
                    for ($i = $inicio; $i <= $fin; $i++):
                        if ($i == $pagina_actual):
                    ?>
                        <span class="px-4 py-2 rounded-lg bg-gradient-to-r from-primary to-blue-600 text-white font-bold shadow-lg">
                            <?php echo $i; ?>
                        </span>
                    <?php else: ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>" 
                           class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition-all font-semibold">
                            <?php echo $i; ?>
                        </a>
                    <?php 
                        endif;
                    endfor; 
                    ?>
                </div>
                
                <!-- Botón Siguiente -->
                <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina_actual + 1])); ?>" 
                   class="px-4 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-primary hover:text-white transition-all font-semibold">
                    Siguiente
                </a>
                <?php endif; ?>
                
                <!-- Botón Última -->
                <?php if ($pagina_actual < $total_paginas): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $total_paginas])); ?>" 
                   class="px-3 py-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-primary hover:text-white transition-all">
                    <span class="material-symbols-outlined text-sm">last_page</span>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- MODAL -->
    <div x-show="modal.open" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @keydown.escape="modal.open = false" @click.self="modal.open = false">
        <div class="w-full max-w-6xl bg-surface-light dark:bg-surface-dark rounded-xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh]">
            <div class="flex items-center justify-between p-6 border-b border-border-light dark:border-border-dark">
                <h3 class="text-xl font-bold" x-text="modal.editMode ? 'Editar Producto' : 'Nuevo Producto'"></h3>
                <button @click="modal.open = false" class="text-text-secondary-light dark:text-text-secondary-dark hover:text-primary">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form @submit.prevent="enviarFormulario" class="flex-1 overflow-y-auto p-6" enctype="multipart/form-data">
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

                <!-- LAYOUT DE DOS COLUMNAS -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- COLUMNA IZQUIERDA: Información Básica -->
                    <div class="space-y-5">
                        <!-- CÓDIGO -->
                        <div class="relative">
                            <label class="block text-sm font-medium mb-1.5">Código / SKU *</label>
                            <input x-model="modal.form.codigo" @input="buscarProducto()" @focus="modal.campoActivo = 'codigo'" required
                                   class="form-input w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary"
                                   placeholder="Ej. LP-GM-001">
                            <div x-show="modal.sugerencias.length > 0 && (modal.campoActivo === 'codigo' || modal.campoActivo === 'nombre')" class="suggestions-container" @click.outside="modal.sugerencias = []">
                                <template x-for="(s, index) in modal.sugerencias" :key="s.Id || index">
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
                        </div>

                        <!-- MARCA -->
                        <div>
                            <label class="block text-sm font-medium mb-1.5">Marca</label>
                            <input x-model="modal.form.marca" type="text"
                                   class="form-input w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary"
                                   placeholder="Ej. HP, Dell, Samsung">
                        </div>

                        <!-- UNIDAD DE MEDIDA -->
                        <div>
                            <label class="block text-sm font-medium mb-1.5 flex items-center gap-2">
                                <span class="material-symbols-outlined text-sm text-primary">straighten</span>
                                Unidad de Medida *
                            </label>
                            <select x-model="modal.form.unidad_medida_id" @change="cambiarUnidadMedida()"
                                    class="form-select w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary">
                                <template x-for="grupo in Object.keys(unidadesMedida)" :key="grupo">
                                    <optgroup :label="grupo.charAt(0).toUpperCase() + grupo.slice(1)">
                                        <template x-for="unidad in unidadesMedida[grupo]" :key="unidad.id">
                                            <option :value="unidad.id" x-text="`${unidad.nombre} (${unidad.abreviatura})`"></option>
                                        </template>
                                    </optgroup>
                                </template>
                            </select>
                            <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-1">
                                Define cómo se vende este producto
                            </p>
                        </div>

                        <!-- DESCRIPCIÓN -->
                        <div>
                            <label class="block text-sm font-medium mb-1.5">Descripción</label>
                            <textarea x-model="modal.form.descripcion" rows="3"
                                      class="form-textarea w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary resize-none"
                                      placeholder="Describe el producto..."></textarea>
                        </div>

                        <!-- PRECIO Y CANTIDAD -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium mb-1.5">
                                    <span x-text="'Precio por ' + (modal.unidadSeleccionada?.nombre || 'Unidad')"></span> (L) *
                                </label>
                                <input x-model.number="modal.form.precio" type="number" step="0.01" min="0" required
                                       class="form-input w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5">Cantidad *</label>
                                <input x-model.number="modal.form.cantidad" type="number" min="0" required
                                       class="form-input w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary">
                            </div>
                        </div>

                        <!-- CATEGORÍA Y VENCIMIENTO -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium mb-1.5">Categoría</label>
                                <select x-model="modal.form.categoria"
                                        class="form-select w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary">
                                    <option value="">Seleccionar categoría</option>
                                    <template x-for="cat in categorias" :key="cat">
                                        <option :value="cat" x-text="cat"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1.5">Fecha de Vencimiento</label>
                                <input x-model="modal.form.fecha_vencimiento" type="date"
                                       class="form-input w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary">
                            </div>
                        </div>
                    </div>

                    <!-- COLUMNA DERECHA: Foto y Precios -->
                    <div class="space-y-5">
                        <!-- FOTO CON DRAG & DROP -->
                        <div x-data="{ 
                            isDragging: false, 
                            isUploading: false, 
                            uploadSuccess: false,
                            uploadProgress: 0
                        }">
                            <label class="block text-sm font-medium mb-1.5">Foto del Producto</label>
                            
                            <!-- Área de Drag & Drop / Preview -->
                            <div 
                                x-ref="dropZone"
                                @drop.prevent="isDragging = false; handleDrop($event, $el)"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @click="!modal.form.foto && $refs.photoInput.click()"
                                :class="isDragging ? 'border-primary bg-primary/5 scale-105' : 'border-border-light dark:border-border-dark'"
                                class="relative w-full h-48 rounded-xl overflow-hidden shadow-lg border-2 border-dashed bg-gradient-to-br from-[#0f172a] via-[#1e293b] to-[#334155] flex items-center justify-center cursor-pointer transition-all duration-300">
                                
                                <!-- Preview de Imagen -->
                                <template x-if="modal.form.foto && !isUploading">
                                    <div class="relative w-full h-full group">
                                        <img :src="modal.form.foto" class="w-full h-full object-cover">
                                        <!-- Overlay con botones -->
                                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center gap-3">
                                            <button @click.stop="$refs.photoInput.click()" class="px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-primary/90 transition-all flex items-center gap-2">
                                                <span class="material-symbols-outlined text-sm">edit</span>
                                                Cambiar
                                            </button>
                                            <button @click.stop="modal.form.foto = ''; modal.form.fotoFile = null" class="px-4 py-2 bg-red-500 text-white rounded-lg font-semibold hover:bg-red-600 transition-all flex items-center gap-2">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                
                                <!-- Estado de Carga -->
                                <template x-if="isUploading">
                                    <div class="flex flex-col items-center gap-4 p-6">
                                        <!-- Spinner animado -->
                                        <div class="relative w-20 h-20">
                                            <svg class="animate-spin" viewBox="0 0 50 50">
                                                <circle cx="25" cy="25" r="20" stroke="currentColor" stroke-width="4" fill="none" class="text-gray-700" opacity="0.25"/>
                                                <circle cx="25" cy="25" r="20" stroke="currentColor" stroke-width="4" fill="none" class="text-primary" 
                                                    stroke-dasharray="125.6" 
                                                    :stroke-dashoffset="125.6 - (125.6 * uploadProgress / 100)"
                                                    stroke-linecap="round"
                                                    style="transition: stroke-dashoffset 0.3s ease;"/>
                                            </svg>
                                            <div class="absolute inset-0 flex items-center justify-center">
                                                <span class="text-white font-bold text-sm" x-text="uploadProgress + '%'"></span>
                                            </div>
                                        </div>
                                        <p class="text-white font-semibold">Subiendo imagen...</p>
                                    </div>
                                </template>
                                
                                <!-- Checkmark de Éxito -->
                                <template x-if="uploadSuccess">
                                    <div class="flex flex-col items-center gap-4 animate-bounce-in">
                                        <div class="w-20 h-20 rounded-full bg-green-500 flex items-center justify-center shadow-2xl">
                                            <span class="material-symbols-outlined text-white text-5xl">check</span>
                                        </div>
                                        <p class="text-green-400 font-bold text-lg">¡Imagen cargada!</p>
                                    </div>
                                </template>
                                
                                <!-- Estado Inicial / Drag & Drop -->
                                <template x-if="!modal.form.foto && !isUploading && !uploadSuccess">
                                    <div class="text-center pointer-events-none p-6">
                                        <div class="mb-4">
                                            <span class="material-symbols-outlined text-6xl text-primary animate-pulse">cloud_upload</span>
                                        </div>
                                        <p class="text-white font-semibold text-lg mb-2">Arrastra tu imagen aquí</p>
                                        <p class="text-gray-400 text-sm mb-3">o haz click para seleccionar</p>
                                        <div class="flex items-center justify-center gap-2 text-xs text-gray-500">
                                            <span class="material-symbols-outlined text-sm">image</span>
                                            <span>JPG, PNG, GIF, WEBP</span>
                                            <span>•</span>
                                            <span>Máx 3MB</span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <input 
                                x-ref="photoInput" 
                                type="file" 
                                accept="image/*" 
                                @change="handleFileSelect($event, $refs.dropZone)" 
                                class="hidden">
                        </div>

                        <script>
                        // Funciones para manejar drag & drop
                        function handleDrop(event, element) {
                            const files = event.dataTransfer.files;
                            if (files.length > 0) {
                                simulateUpload(files[0], element);
                            }
                        }
                        
                        function handleFileSelect(event, element) {
                            const files = event.target.files;
                            if (files.length > 0) {
                                simulateUpload(files[0], element);
                            }
                        }
                        
                        function simulateUpload(file, element) {
                            // Validar tamaño
                            if (file.size > 3 * 1024 * 1024) {
                                alert('La imagen no debe superar 3MB');
                                return;
                            }
                            
                            // Validar tipo
                            if (!file.type.startsWith('image/')) {
                                alert('Solo se permiten imágenes');
                                return;
                            }
                            
                            // Obtener el scope del componente drag & drop
                            const scope = Alpine.$data(element.closest('[x-data]'));
                            scope.isUploading = true;
                            scope.uploadProgress = 0;
                            
                            // Simular progreso de carga
                            const interval = setInterval(() => {
                                scope.uploadProgress += 10;
                                if (scope.uploadProgress >= 100) {
                                    clearInterval(interval);
                                    scope.isUploading = false;
                                    scope.uploadSuccess = true;
                                    
                                    // Leer archivo y mostrar preview
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        setTimeout(() => {
                                            const modalScope = Alpine.$data(document.querySelector('[x-data="app()"]'));
                                            modalScope.modal.form.foto = e.target.result;
                                            modalScope.modal.form.fotoFile = file;
                                            scope.uploadSuccess = false;
                                        }, 800);
                                    };
                                    reader.readAsDataURL(file);
                                }
                            }, 100);
                        }
                        </script>

                        <!-- SECCIÓN DE PRECIOS PERSONALIZADOS -->
                        <div class="border-t border-border-light dark:border-border-dark pt-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-semibold flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary text-base">sell</span>
                                    Precios del Producto
                                </h4>
                                <button type="button" @click="abrirGestionPrecios()" 
                                        class="text-xs text-primary hover:underline flex items-center gap-1 px-2 py-1 rounded-md hover:bg-primary/10 transition-colors">
                                    <span class="material-symbols-outlined text-sm">settings</span>
                                    Gestionar
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3">
                                <template x-for="tipo in tiposPrecios" :key="tipo.id">
                                    <div class="flex flex-col">
                                        <label class="flex items-center justify-between mb-1">
                                            <span class="text-xs font-medium flex items-center gap-1">
                                                <span x-text="tipo.nombre"></span>
                                                <span x-show="tipo.es_default" class="text-xs text-gray-500 dark:text-gray-400">*</span>
                                            </span>
                                        </label>
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 text-xs">L</span>
                                            <input type="number" 
                                                   x-model.number="modal.form.precios[tipo.id]" 
                                                   step="0.01" min="0"
                                                   :placeholder="'0.00'"
                                                   class="form-input w-full pl-6 text-sm rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary">
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <p class="text-xs text-text-secondary-light dark:text-text-secondary-dark mt-2">
                                * Precios por defecto
                            </p>
                        </div>
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

    <!-- MODAL DE GESTIÓN DE TIPOS DE PRECIOS -->
    <div x-show="modalPrecios.open" x-cloak x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @keydown.escape="modalPrecios.open = false" @click.self="modalPrecios.open = false">
        <div class="w-full max-w-3xl bg-surface-light dark:bg-surface-dark rounded-xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-border-light dark:border-border-dark">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">sell</span>
                    Gestión de Tipos de Precios
                </h3>
                <button @click="modalPrecios.open = false" class="text-text-secondary-light dark:text-text-secondary-dark hover:text-primary">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto p-6 space-y-6">
                <!-- Lista de tipos de precios -->
                <div class="space-y-3">
                    <h4 class="text-sm font-semibold text-text-secondary-light dark:text-text-secondary-dark">Tipos de Precios Existentes</h4>
                    <template x-for="tipo in tiposPrecios" :key="tipo.id">
                        <div class="flex items-center justify-between p-4 border border-border-light dark:border-border-dark rounded-lg hover:border-primary transition-colors">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="font-medium" x-text="tipo.nombre"></p>
                                    <span x-show="tipo.es_default" class="px-2 py-0.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full">Por defecto</span>
                                </div>
                                <p class="text-sm text-text-secondary-light dark:text-text-secondary-dark mt-1" x-text="tipo.descripcion || 'Sin descripción'"></p>
                            </div>
                            <div class="flex gap-2" x-show="!tipo.es_default">
                                <button @click="editarTipoPrecio(tipo)" class="p-2 hover:bg-black/5 dark:hover:bg-white/5 rounded-md transition-colors" title="Editar">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <button @click="eliminarTipoPrecio(tipo.id)" class="p-2 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors text-red-500" title="Eliminar">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Formulario para nuevo/editar tipo -->
                <div class="border-t border-border-light dark:border-border-dark pt-6">
                    <h4 class="text-sm font-semibold mb-4" x-text="modalPrecios.editando ? 'Editar Tipo de Precio' : 'Crear Nuevo Tipo de Precio'"></h4>
                    <form @submit.prevent="guardarTipoPrecio()" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1.5">Nombre del Tipo de Precio *</label>
                            <input x-model="modalPrecios.form.nombre" required
                                   class="form-input w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary"
                                   placeholder="Ej: Precio_Mayorista, Precio_Distribuidor">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1.5">Descripción</label>
                            <textarea x-model="modalPrecios.form.descripcion" rows="2"
                                      class="form-textarea w-full rounded-md border-border-light dark:border-border-dark bg-background-light dark:bg-background-dark focus:border-primary focus:ring-primary resize-none"
                                      placeholder="Descripción opcional del tipo de precio"></textarea>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="flex-1 px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90 transition-colors">
                                <span x-text="modalPrecios.editando ? 'Actualizar' : 'Crear Tipo de Precio'"></span>
                            </button>
                            <button type="button" @click="cancelarEdicionTipo()" x-show="modalPrecios.editando"
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function app() {
    return {
        // Tipos de precios globales
        tiposPrecios: [],
        
        // Unidades de medida
        unidadesMedida: {
            cantidad: [],
            peso: [],
            volumen: [],
            empaque: []
        },
        
        // Modal principal de producto
        modal: {
            open: false,
            editMode: false,
            loading: false,
            autocompletar: true,
            product_id: '',
            sugerencias: [],
            campoActivo: '',
            unidadSeleccionada: null,
            form: {
                nombre: '',
                codigo: '',
                marca: '',
                descripcion: '',
                precio: 0,
                cantidad: 0,
                categoria: '',
                fecha_vencimiento: '',
                foto: '',
                fotoFile: null,
                precios: {}, // Objeto para almacenar precios por tipo_id
                unidad_medida_id: 1 // Default: Unidad
            }
        },
        
        // Modal de gestión de precios
        modalPrecios: {
            open: false,
            editando: false,
            form: {
                id: null,
                nombre: '',
                descripcion: ''
            }
        },
        
        _searchTimeout: null,
        categorias: [], // Lista de categorías cargadas dinámicamente

        init() {
            // Cargar tipos de precios al iniciar
            this.cargarTiposPrecios();
            
            // Cargar unidades de medida
            this.cargarUnidadesMedida();
            
            // Cargar categorías dinámicamente
            this.cargarCategorias();
            
            // Escuchar cambios en categorías (cuando se crean desde otro tab/ventana)
            window.addEventListener('storage', (e) => {
                if (e.key === 'categorias_updated') {
                    this.cargarCategorias();
                }
            });
            
            // Escuchar evento personalizado para actualizaciones en la misma pestaña
            window.addEventListener('categorias_changed', () => {
                this.cargarCategorias();
            });
            
            <?php if ($ProductoParaEditar): ?>
            const producto = <?php echo $ProductoParaEditar; ?>;
            this.cargarProductoParaEditar(producto);
            <?php endif; ?>
        },
        
        async cargarCategorias() {
            try {
                const response = await fetch('api/obtener_categorias.php');
                const data = await response.json();
                
                if (data.success) {
                    this.categorias = data.categorias;
                } else {
                }
            } catch (error) {
            }
        },

        marcarTodasLeidas() {
            fetch('marcar_notificaciones_leidas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
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
        },

        async cargarProductoParaEditar(p) {
            if (!p) return;
            this.modal.editMode = true;
            this.modal.autocompletar = false;
            this.modal.open = true;
            this.modal.product_id = p.Id || p.id || '';
            this.modal.form = {
                nombre: p.Nombre_Producto || p.NombreProducto || '',
                codigo: p.Codigo_Producto || p.CodigoProducto || '',
                marca: p.Marca || '',
                descripcion: p.Descripcion || '',
                precio: parseFloat(p.Precio_Unitario || p.Precio || 0),
                cantidad: 0,
                categoria: p.Grupo || '',
                fecha_vencimiento: (p.Fecha_Vencimiento || '').split(' ')[0] || '',
                foto: p.FotoProducto || '',
                fotoFile: null,
                precios: {}
            };
            
            // Cargar precios del producto
            if (this.modal.product_id) {
                await this.cargarPreciosProducto(this.modal.product_id);
            }
        },

        abrirModal() {
            this.limpiarFormulario();
            this.modal.open = true;
            this.modal.editMode = false;
            this.modal.autocompletar = true;
            this.cargarCategorias();
        },

        limpiarFormulario() {
            this.modal.form = {
                nombre: '',
                codigo: '',
                marca: '',
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
                if (this.$refs && this.$refs.photoInput) this.$refs.photoInput.value = '';
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

            console.log('🔍 Buscando sugerencias:', { q, tipo, autocompletar: this.modal.autocompletar });

            fetch(`buscar_sugerencias.php?${params}`)
                .then(r => {
                    console.log('📡 Respuesta HTTP:', r.status, r.statusText);
                    if (!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    return r.json();
                })
                .then(data => {
                    console.log('📦 Datos recibidos:', data);
                    if (data.success && Array.isArray(data.suggestions)) {
                        this.modal.sugerencias = data.suggestions;
                        console.log('✅ Sugerencias cargadas:', data.suggestions.length);
                    } else {
                        this.modal.sugerencias = [];
                        console.warn('⚠️ No hay sugerencias o formato incorrecto');
                    }
                })
                .catch(err => {
                    console.error('❌ Error al cargar sugerencias:', err);
                    this.modal.sugerencias = [];
                });
        },

        seleccionarSugerencia(s) {
            const codigo = s.Codigo_Producto || s.CodigoProducto || s.codigo;
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

            fetch(`obtener_producto.php?${params}`)
                .then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    return r.json();
                })
                .then(data => {
                    if (!data.success || !data.producto) {
                        mostrarError('Producto no encontrado: ' + (data.message || 'Error desconocido'));
                        return;
                    }
                    const p = data.producto;
                    this.modal.editMode = true;
                    this.modal.autocompletar = false;
                    this.modal.open = true;
                    this.modal.product_id = p.Id || p.id || '';
                    this.modal.form = {
                        nombre: p.Nombre_Producto || p.NombreProducto || '',
                        codigo: p.Codigo_Producto || p.CodigoProducto || '',
                        marca: p.Marca || '',
                        descripcion: p.Descripcion || '',
                        precio: parseFloat(p.Precio_Unitario || p.Precio || 0),
                        cantidad: 0,
                        categoria: p.Grupo || '',
                        fecha_vencimiento: (p.Fecha_Vencimiento || '').split(' ')[0] || '',
                        foto: p.FotoProducto || '',
                        fotoFile: null
                    };
                    this.$nextTick(() => {
                        if (this.$refs && this.$refs.photoInput) this.$refs.photoInput.value = '';
                    });
                })
                .catch(err => {
                    console.error('Error al cargar producto:', err);
                    mostrarError('Error al cargar el producto: ' + err.message);
                });
        },

        cargarFoto(e) {
            const file = e.target.files?.[0];
            if (!file) return;

            if (file.size > 3 * 1024 * 1024) {
                mostrarAdvertencia('La imagen es muy grande (máximo 3MB)');
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

    // Validaciones básicas
    if (!f.nombre || !f.codigo) {
        mostrarAdvertencia('Nombre y código son obligatorios');
        return;
    }

    // Normalizar números y tipos
    const precio = Number(f.precio);
    const cantidad = Number(f.cantidad);
    if (!isFinite(precio) || precio <= 0) {
        mostrarAdvertencia('Precio debe ser un número mayor que 0');
        return;
    }
    if (!isFinite(cantidad) || cantidad < 0) {
        mostrarAdvertencia('Cantidad debe ser un número igual o mayor que 0');
        return;
    }

    this.modal.loading = true;

    try {
        const fd = new FormData();
        fd.append('nombre', String(f.nombre).trim());
        fd.append('codigo', String(f.codigo).trim());
        fd.append('marca', String(f.marca || '').trim());
        fd.append('descripcion', String(f.descripcion || '').trim());
        // Envío como string para evitar problemas de tipo en PHP
        fd.append('precio', precio.toString());
        fd.append('cantidad', cantidad.toString());
        fd.append('categoria', String(f.categoria || '').trim());
        fd.append('fecha_vencimiento', String(f.fecha_vencimiento || '').trim());
        fd.append('edit_mode', this.modal.editMode ? '1' : '0');
        fd.append('unidad_medida_id', String(f.unidad_medida_id || 1)); // Agregar unidad de medida

        // Solo enviar product_id si existe y no es vacío
        if (this.modal.editMode && this.modal.product_id) {
            fd.append('product_id', String(this.modal.product_id));
        }

        // Archivo preferido; si hay fotoFile use ese, si no y la URL es http(s) se envía foto_url.
        if (f.fotoFile instanceof File) {
            fd.append('product_photo', f.fotoFile);
        } else if (f.foto && typeof f.foto === 'string' && f.foto.startsWith('http')) {
            fd.append('foto_url', f.foto);
        }

        const url = this.modal.editMode ? 'actualizar_producto.php' : 'agregar_stock_simple.php';

        const res = await fetch(url, {
            method: 'POST',
            body: fd,
            // NOTA: no incluir Content-Type; browser lo agrega para FormData
        });

        // Si la respuesta no es OK, leer texto (puede contener stacktrace o HTML del 500)
        if (!res.ok) {
            const text = await res.text();
            console.error('Respuesta HTTP no OK:', res.status, res.statusText, text);
            // Mostrar mensaje más informativo al usuario
            mostrarError(`Error del servidor ${res.status}: revisa la consola para más detalles`);
            return;
        }

        // Intentar parsear JSON de forma segura
        let data;
        try {
            data = await res.json();
        } catch (e) {
            const text = await res.text();
            console.error('No se pudo parsear JSON. Respuesta del servidor:', text);
            mostrarInfo('Respuesta inesperada del servidor: revisa la consola para más detalles');
            return;
        }

        if (data && data.success) {
            // Guardar precios si hay un product_id
            const producto_id = data.product_id || this.modal.product_id;
            if (producto_id && Object.keys(this.modal.form.precios).length > 0) {
                await this.guardarPreciosProducto(producto_id);
            }
            
            // Cerrar modal
            this.modal.open = false;
            this.limpiarFormulario();
            
            // Mostrar notificación
            mostrarExito(this.modal.editMode ? 'Producto actualizado exitosamente' : 'Producto agregado exitosamente');
            
            // Actualizar tabla dinámicamente
            await this.actualizarTablaInventario();
        } else {
            const msg = (data && data.message) ? data.message : 'No se pudo guardar el producto';
            console.warn('Respuesta de aplicación indica error:', data);
            mostrarError('Error: ' + msg);
        }
    } catch (err) {
        console.error('Error al enviar formulario:', err);
        mostrarError('Error de comunicación con el servidor: revisa la consola');
    } finally {
        this.modal.loading = false;
    }
},

async actualizarTablaInventario() {
    try {
        // Obtener parámetros actuales de búsqueda y filtro
        const urlParams = new URLSearchParams(window.location.search);
        const buscar = urlParams.get('buscar') || '';
        const filtro = urlParams.get('filtro') || 'Todos';
        
        // Hacer fetch de la tabla actualizada
        const response = await fetch(`obtener_inventario.php?buscar=${encodeURIComponent(buscar)}&filtro=${encodeURIComponent(filtro)}`);
        const data = await response.json();
        
        if (data.success && data.productos) {
            // Actualizar la tabla
            const tbody = document.getElementById('tablaInventario');
            if (!tbody) return;
            
            if (data.productos.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-text-secondary-light dark:text-text-secondary-dark">
                            <div class="flex flex-col items-center gap-2">
                                <span class="material-symbols-outlined text-4xl">inventory_2</span>
                                <p>No se encontraron productos</p>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                tbody.innerHTML = data.productos.map(producto => {
                    const stock = producto.Stock;
                    let estado_class, estado_dot, estado_text;
                    
                    if (stock == 0) {
                        estado_class = 'bg-red-500/10 text-red-500';
                        estado_dot = 'bg-red-500';
                        estado_text = 'Agotado';
                    } else if (stock <= 10) {
                        estado_class = 'bg-yellow-500/10 text-yellow-500';
                        estado_dot = 'bg-yellow-500';
                        estado_text = 'Bajo Stock';
                    } else {
                        estado_class = 'bg-green-500/10 text-green-500';
                        estado_dot = 'bg-green-500';
                        estado_text = 'Activo';
                    }
                    
                    return `
                        <tr class="border-b border-border-light dark:border-border-dark hover:bg-background-light dark:hover:bg-background-dark transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">${producto.Nombre_Producto}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary-light dark:text-text-secondary-dark">${producto.Codigo_Producto}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary-light dark:text-text-secondary-dark">${producto.Marca || 'N/A'}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary-light dark:text-text-secondary-dark">${producto.Stock}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-text-secondary-light dark:text-text-secondary-dark">L ${parseFloat(producto.Precio_Unitario).toFixed(2)}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="inline-flex items-center gap-2 rounded-full ${estado_class} px-3 py-1 text-sm font-medium">
                                    <div class="size-2 rounded-full ${estado_dot}"></div>
                                    ${estado_text}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <button @click="editarProducto('${producto.Codigo_Producto}')" class="flex h-8 w-8 items-center justify-center rounded-md hover:bg-black/5 dark:hover:bg-white/5 transition-colors" title="Editar">
                                        <span class="material-symbols-outlined text-lg text-text-secondary-light dark:text-text-secondary-dark">edit</span>
                                    </button>
                                    <button @click="eliminarProducto(${producto.Id}, '${producto.Nombre_Producto.replace(/'/g, "\\'")}')" class="flex h-8 w-8 items-center justify-center rounded-md hover:bg-black/5 dark:hover:bg-white/5 transition-colors" title="Eliminar">
                                        <span class="material-symbols-outlined text-lg text-red-500">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            }
        }
    } catch (error) {
        console.error('Error al actualizar tabla:', error);
    }
},


        editarProducto(codigo) {
            // Mantengo consistencia con cargarProductoCompleto: uso fetch
            this.modal.editMode = true;
            this.modal.autocompletar = false;
            this.modal.sugerencias = [];

            const params = new URLSearchParams({ codigo: codigo, autocompletar: '0' });

            fetch(`obtener_producto.php?${params}`)
                .then(r => {
                    if (!r.ok) throw new Error(`HTTP ${r.status}: ${r.statusText}`);
                    return r.json();
                })
                .then(async data => {
                    if (!data.success || !data.producto) {
                        mostrarInfo('Producto no encontrado');
                        return;
                    }
                    const p = data.producto;
                    this.modal.product_id = p.Id || p.id || '';
                    this.modal.form = {
                        nombre: p.Nombre_Producto || p.NombreProducto || '',
                        codigo: p.Codigo_Producto || p.CodigoProducto || '',
                        marca: p.Marca || '',
                        descripcion: p.Descripcion || '',
                        precio: parseFloat(p.Precio_Unitario || p.Precio || 0),
                        cantidad: 0,
                        categoria: p.Grupo || '',
                        fecha_vencimiento: (p.Fecha_Vencimiento || '').split(' ')[0] || '',
                        foto: p.FotoProducto || '',
                        fotoFile: null,
                        precios: {}
                    };
                    
                    // Cargar precios del producto
                    if (this.modal.product_id) {
                        await this.cargarPreciosProducto(this.modal.product_id);
                    }
                    
                    this.modal.open = true;
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
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
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

        // ===================================
        // FUNCIONES DE GESTIÓN DE PRECIOS
        // ===================================
        
        async cargarTiposPrecios() {
            try {
                const response = await fetch('api/tipos_precios.php');
                const data = await response.json();
                if (data.success && data.tipos) {
                    this.tiposPrecios = data.tipos;
                    console.log('✅ Tipos de precios cargados:', this.tiposPrecios.length);
                }
            } catch (error) {
                console.error('Error al cargar tipos de precios:', error);
            }
        },
        
        async cargarUnidadesMedida() {
            try {
                const response = await fetch('api/obtener_unidades_medida.php');
                const data = await response.json();
                if (data.success && data.agrupadas) {
                    this.unidadesMedida = data.agrupadas;
                    console.log('✅ Unidades de medida cargadas:', data.unidades.length);
                    
                    // Establecer unidad por defecto
                    const unidadDefault = data.unidades.find(u => u.es_default);
                    if (unidadDefault) {
                        this.modal.unidadSeleccionada = unidadDefault;
                    }
                }
            } catch (error) {
                console.error('Error al cargar unidades de medida:', error);
            }
        },
        
        cambiarUnidadMedida() {
            // Buscar la unidad seleccionada
            const unidadId = this.modal.form.unidad_medida_id;
            let unidadEncontrada = null;
            
            for (const tipo in this.unidadesMedida) {
                const unidad = this.unidadesMedida[tipo].find(u => u.id == unidadId);
                if (unidad) {
                    unidadEncontrada = unidad;
                    break;
                }
            }
            
            this.modal.unidadSeleccionada = unidadEncontrada;
            console.log('📏 Unidad cambiada a:', unidadEncontrada?.nombre);
            
            // Verificar si existe un tipo de precio para esta unidad
            if (unidadEncontrada && !unidadEncontrada.es_default) {
                this.verificarTipoPrecioUnidad(unidadEncontrada);
            }
        },
        
        async verificarTipoPrecioUnidad(unidad) {
            // Buscar si ya existe un tipo de precio para esta unidad
            const tipoPrecioExistente = this.tiposPrecios.find(t => t.unidad_medida_id == unidad.id);
            
            if (!tipoPrecioExistente) {
                console.log('🆕 Creando tipo de precio automático para:', unidad.nombre);
                // Crear tipo de precio automáticamente
                try {
                    const response = await fetch('api/guardar_tipo_precio.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            nombre: `Precio_${unidad.nombre}`,
                            descripcion: `Precio por ${unidad.nombre}`,
                            unidad_medida_id: unidad.id,
                            es_default: false
                        })
                    });
                    
                    const data = await response.json();
                    if (data.success) {
                        await this.cargarTiposPrecios();
                        console.log('✅ Tipo de precio creado automáticamente');
                    }
                } catch (error) {
                    console.error('Error al crear tipo de precio:', error);
                }
            }
        },
        
        abrirGestionPrecios() {
            this.modalPrecios.open = true;
            this.modalPrecios.editando = false;
            this.modalPrecios.form = { id: null, nombre: '', descripcion: '' };
        },
        
        async guardarTipoPrecio() {
            const form = this.modalPrecios.form;
            
            if (!form.nombre.trim()) {
                mostrarAdvertencia('El nombre del tipo de precio es requerido');
                return;
            }
            
            try {
                const method = this.modalPrecios.editando ? 'PUT' : 'POST';
                const body = this.modalPrecios.editando 
                    ? { id: form.id, nombre: form.nombre, descripcion: form.descripcion }
                    : { nombre: form.nombre, descripcion: form.descripcion };
                
                const response = await fetch('api/tipos_precios.php', {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarExito(data.message);
                    await this.cargarTiposPrecios();
                    this.modalPrecios.form = { id: null, nombre: '', descripcion: '' };
                    this.modalPrecios.editando = false;
                } else {
                    mostrarError(data.message);
                }
            } catch (error) {
                console.error('Error al guardar tipo de precio:', error);
                mostrarError('Error al guardar el tipo de precio');
            }
        },
        
        editarTipoPrecio(tipo) {
            this.modalPrecios.editando = true;
            this.modalPrecios.form = {
                id: tipo.id,
                nombre: tipo.nombre,
                descripcion: tipo.descripcion || ''
            };
        },
        
        cancelarEdicionTipo() {
            this.modalPrecios.editando = false;
            this.modalPrecios.form = { id: null, nombre: '', descripcion: '' };
        },
        
        async eliminarTipoPrecio(id) {
            if (!confirm('¿Estás seguro de eliminar este tipo de precio? Se eliminarán todos los precios asociados.')) {
                return;
            }
            
            try {
                const response = await fetch('api/tipos_precios.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    mostrarExito(data.message);
                    await this.cargarTiposPrecios();
                } else {
                    mostrarError(data.message);
                }
            } catch (error) {
                console.error('Error al eliminar tipo de precio:', error);
                mostrarError('Error al eliminar el tipo de precio');
            }
        },
        
        async cargarPreciosProducto(producto_id) {
            try {
                const response = await fetch(`api/precios_producto.php?producto_id=${producto_id}`);
                const data = await response.json();
                
                if (data.success && data.precios) {
                    // Inicializar objeto de precios
                    this.modal.form.precios = {};
                    data.precios.forEach(precio => {
                        this.modal.form.precios[precio.tipo_id] = precio.precio;
                    });
                }
            } catch (error) {
                console.error('Error al cargar precios del producto:', error);
            }
        },
        
        async guardarPreciosProducto(producto_id) {
            try {
                const response = await fetch('api/precios_producto.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        producto_id: producto_id,
                        precios: this.modal.form.precios
                    })
                });
                
                const data = await response.json();
                
                if (!data.success) {
                    console.error('Error al guardar precios:', data.message);
                }
            } catch (error) {
                console.error('Error al guardar precios del producto:', error);
            }
        },

        toggleAutocompletar() {
            this.modal.sugerencias = [];
            const codigo = this.modal.form.codigo?.trim() || '';
            const nombre = this.modal.form.nombre?.trim() || '';
            if (codigo.length >= 2 || nombre.length >= 3) this.buscarProducto();
        }
    };
}

// ===================================
// FILTRADO EN TIEMPO REAL DE LA TABLA
// ===================================
document.addEventListener('DOMContentLoaded', function() {
    const inputBusqueda = document.getElementById('inputBusqueda');
    const tablaInventario = document.getElementById('tablaInventario');
    
    if (inputBusqueda && tablaInventario) {
        inputBusqueda.addEventListener('input', function() {
            const filtro = this.value.toLowerCase().trim();
            const filas = tablaInventario.getElementsByTagName('tr');
            
            let productosVisibles = 0;
            let filaNoResultados = null;
            
            for (let i = 0; i < filas.length; i++) {
                const fila = filas[i];
                
                // Identificar la fila de "No se encontraron productos"
                if (fila.cells.length === 1) {
                    filaNoResultados = fila;
                    continue;
                }
                
                // Obtener el texto de las columnas relevantes (Producto, Código, Marca)
                const producto = fila.cells[0]?.textContent.toLowerCase() || '';
                const codigo = fila.cells[1]?.textContent.toLowerCase() || '';
                const marca = fila.cells[2]?.textContent.toLowerCase() || '';
                
                // Mostrar u ocultar según el filtro
                if (producto.includes(filtro) || codigo.includes(filtro) || marca.includes(filtro)) {
                    fila.style.display = '';
                    productosVisibles++;
                } else {
                    fila.style.display = 'none';
                }
            }
            
            // Mostrar mensaje personalizado si no hay resultados
            if (productosVisibles === 0 && filtro !== '') {
                if (!filaNoResultados) {
                    filaNoResultados = document.createElement('tr');
                    filaNoResultados.id = 'filaNoResultados';
                    tablaInventario.appendChild(filaNoResultados);
                }
                
                filaNoResultados.innerHTML = `
                    <td colspan="7" class="px-6 py-12 text-center bg-surface-light dark:bg-surface-dark">
                        <div class="flex flex-col items-center gap-6">
                            <div class="text-8xl animate-bounce">
                                👑😭
                            </div>
                            <div class="space-y-3">
                                <h3 class="text-3xl font-bold text-gray-700 dark:text-gray-300 animate-pulse">¡Oh no!</h3>
                                <p class="text-xl text-gray-600 dark:text-gray-400">No se ha encontrado lo que buscas</p>
                                <p class="text-base text-gray-500 dark:text-gray-500">
                                    Tu búsqueda "<span class="font-semibold text-primary">${filtro}</span>" no generó resultados 😢
                                </p>
                                <p class="text-sm text-gray-400 dark:text-gray-600 mt-4">
                                    💡 Intenta con otro término de búsqueda
                                </p>
                            </div>
                        </div>
                    </td>
                `;
                filaNoResultados.style.display = '';
            } else if (filaNoResultados) {
                filaNoResultados.style.display = 'none';
            }
            
            // Actualizar contador de resultados
            const contadorResultados = document.querySelector('.text-sm.text-text-secondary-light');
            if (contadorResultados) {
                contadorResultados.innerHTML = `Mostrando <strong>${productosVisibles}</strong> resultado(s)`;
            }
        });
    }
});
</script>

<?php include 'christmas_effects.php'; ?>
<?php include 'modal_sistema.php'; ?>
</body>
</html>