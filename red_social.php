<?php
require_once 'config.php';

// Mostrar errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Verificación de seguridad inicial
if (!isset($_SESSION['usuario'])) {
    die("Error: Usuario no autenticado. Por favor, inicia sesión.");
}

// Obtener datos del usuario actual
$conexion_temp = new mysqli("127.0.0.1", "root", "", "tiendasrey");
if ($conexion_temp->connect_error) {
    die("Error de conexión: " . $conexion_temp->connect_error);
}
$stmt = $conexion_temp->prepare("SELECT Id, Nombre, Apellido, Perfil, Cargo FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$usuario_actual = $result->fetch_assoc();
$stmt->close();
$conn = $conexion_temp; // Para compatibilidad con el resto del código

date_default_timezone_set('America/Tegucigalpa');

// Función para calcular estado de usuario (en línea o última actividad)
function calcularEstado($ultima_actividad) {
    if (!$ultima_actividad) {
        return ['clase' => 'bg-gray-500', 'texto' => 'Desconectado'];
    }
    
    $tiempo_diff = time() - strtotime($ultima_actividad);
    
    if ($tiempo_diff < 300) { // 5 minutos
        return ['clase' => 'bg-green-500', 'texto' => 'En línea'];
    } elseif ($tiempo_diff < 3600) { // menos de 1 hora
        $minutos = floor($tiempo_diff / 60);
        return ['clase' => 'bg-orange-500', 'texto' => "Hace {$minutos}min"];
    } elseif ($tiempo_diff < 86400) { // menos de 24 horas
        $horas = floor($tiempo_diff / 3600);
        return ['clase' => 'bg-orange-500', 'texto' => "Hace {$horas}h"];
    } else {
        $dias = floor($tiempo_diff / 86400);
        return ['clase' => 'bg-gray-500', 'texto' => "Hace {$dias}d"];
    }
}

// --- LÓGICA PARA OBTENER PUBLICACIONES ---
 $query = "SELECT 
    p.*,
    u.Nombre,
    u.Id,
    u.Perfil,
    u.Cargo,
    (SELECT COUNT(*) FROM likes WHERE publicacion_id = p.id) as total_likes,
    (SELECT COUNT(*) FROM comentarios WHERE publicacion_id = p.id) as total_comentarios,
    (SELECT COUNT(*) FROM likes WHERE publicacion_id = p.id AND usuario_id = " . (int)$usuario_actual['Id'] . ") as user_liked
FROM publicaciones p
INNER JOIN usuarios u ON p.usuario_id = u.id
ORDER BY p.fecha_creacion DESC
LIMIT 220";

 $result = mysqli_query($conn, $query);
 $publicaciones = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pub_id = $row['id'];
    
    // Obtener archivos multimedia
    $stmt_archivos = $conn->prepare("SELECT * FROM archivos_multimedia WHERE publicacion_id = ?");
    $stmt_archivos->bind_param("i", $pub_id);
    $stmt_archivos->execute();
    $result_archivos = $stmt_archivos->get_result();
    $row['archivos'] = [];
    while ($archivo = mysqli_fetch_assoc($result_archivos)) {
        $row['archivos'][] = $archivo;
    }
    $stmt_archivos->close();
    
    // Obtener comentarios
    $stmt_comentarios = $conn->prepare("SELECT c.*, u.Nombre, u.Perfil FROM comentarios c INNER JOIN usuarios u ON c.usuario_id = u.Id WHERE c.publicacion_id = ? ORDER BY c.fecha_creacion DESC LIMIT 5");
    $stmt_comentarios->bind_param("i", $pub_id);
    $stmt_comentarios->execute();
    $result_comentarios = $stmt_comentarios->get_result();
    $row['comentarios_detalle'] = [];
    while ($comentario = mysqli_fetch_assoc($result_comentarios)) {
        $row['comentarios_detalle'][] = $comentario;
    }
    $stmt_comentarios->close();
    
    $publicaciones[] = $row;
}

// --- LÓGICA PARA LA BARRA LATERAL DINÁMICA ---
// Próximos Cumpleaños
 $hoy = date("m-d");
 $mañana = date("m-d", strtotime("+1 day"));
 $query_cumpleanios = $conn->prepare("SELECT Id, Nombre, Perfil, DATE_FORMAT(Fecha_Nacimiento, '%m-%d') as dia_mes FROM usuarios WHERE DATE_FORMAT(Fecha_Nacimiento, '%m-%d') IN (?, ?) ORDER BY Fecha_Nacimiento");
 $query_cumpleanios->bind_param("ss", $hoy, $mañana);
 $query_cumpleanios->execute();
 $result_cumpleanios = $query_cumpleanios->get_result();
 $cumpleaneros = [];
while($row = mysqli_fetch_assoc($result_cumpleanios)){
    $row['cuando'] = ($row['dia_mes'] == $hoy) ? 'Hoy' : 'Mañana';
    $cumpleaneros[] = $row;
}
 $query_cumpleanios->close();

// Nuevos Empleados
 $treinta_dias_atras = date("Y-m-d", strtotime("-30 days"));
 $query_nuevos = $conn->prepare("SELECT Id, Nombre, Perfil, Cargo FROM usuarios WHERE fecha_ingreso >= ? ORDER BY fecha_ingreso DESC LIMIT 5");
 $query_nuevos->bind_param("s", $treinta_dias_atras);
 $query_nuevos->execute();
 $result_nuevos = $query_nuevos->get_result();
 $nuevos_empleados = [];
while($row = mysqli_fetch_assoc($result_nuevos)){
    $nuevos_empleados[] = $row;
}
 $query_nuevos->close();

// Amigos/Conexiones - Tabla no existe, usar array vacío por ahora
$amigos = [];
/*
 $query_amigos = $conn->prepare("SELECT u.Id, u.Nombre, u.Perfil, u.Cargo, u.Estado FROM usuarios u 
INNER JOIN conexiones c ON (u.Id = c.usuario_id_1 OR u.Id = c.usuario_id_2) 
WHERE (c.usuario_id_1 = ? OR c.usuario_id_2 = ?) AND u.Id != ? AND c.estado = 'aceptada' 
ORDER BY RAND() LIMIT 5");
 $query_amigos->bind_param("iii", $usuario_actual['Id'], $usuario_actual['Id'], $usuario_actual['Id']);
 $query_amigos->execute();
 $result_amigos = $query_amigos->get_result();
 $amigos = [];
while($row = mysqli_fetch_assoc($result_amigos)){
    $amigos[] = $row;
}
 $query_amigos->close();
*/

// Notificaciones no leídas de la red social
 $query_notif = $conn->prepare("SELECT COUNT(*) as total FROM notificaciones_red WHERE usuario_id = ? AND leida = 0");
 $query_notif->bind_param("i", $usuario_actual['Id']);
 $query_notif->execute();
 $result_notif = $query_notif->get_result();
 $notif_count = 0;
if($row = mysqli_fetch_assoc($result_notif)){
    $notif_count = $row['total'];
}
 $query_notif->close();

?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Red Social - ReySystem</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
<link rel="stylesheet" href="css/facebook-style.css">
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
            "DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"
          },
        },
      },
    }
</script>
<style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
      line-height: 1;
    }
    .material-symbols-outlined.fill {
      font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    .preview-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }
    .preview-item {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        background: rgba(0,0,0,0.1);
    }
    .preview-item img, .preview-item video {
        width: 100%;
        height: 150px;
        object-fit: cover;
    }
    .remove-preview {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(255,0,0,0.8);
        color: white;
        border: none;
        border-radius: 50%;
        width: 25px;
        height: 25px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
    }
    .remove-preview:hover {
        background: rgba(255,0,0,1);
    }
    /* Animaciones */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: #ef4444;
        color: white;
        border-radius: 50%;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: bold;
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 100;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.5);
    }
    .modal.active {
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .online-indicator {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 12px;
        height: 12px;
        background-color: #10b981;
        border-radius: 50%;
        border: 2px solid white;
    }
    .typing-indicator {
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        background-color: #e5e7eb;
        border-radius: 18px;
        margin-top: 5px;
    }
    .typing-indicator span {
        height: 8px;
        width: 8px;
        background-color: #6b7280;
        border-radius: 50%;
        display: inline-block;
        margin: 0 2px;
        animation: typing 1.4s infinite;
    }
    .typing-indicator span:nth-child(2) {
        animation-delay: 0.2s;
    }
    .typing-indicator span:nth-child(3) {
        animation-delay: 0.4s;
    }
    @keyframes typing {
        0%, 60%, 100% {
            transform: translateY(0);
        }
        30% {
            transform: translateY(-10px);
        }
    }
    .chat-message {
        max-width: 70%;
        padding: 8px 12px;
        border-radius: 18px;
        margin-bottom: 8px;
        word-wrap: break-word;
    }
    .chat-message.sent {
        background-color: #1152d4;
        color: white;
        align-self: flex-end;
        margin-left: auto;
    }
    .chat-message.received {
        background-color: #e5e7eb;
        color: #1f2937;
        align-self: flex-start;
    }
    .chat-message-time {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 4px;
        text-align: right;
    }
    .chat-message.received .chat-message-time {
        text-align: left;
    }
    
    /* Animaciones modernas */
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .animate-slideInRight {
        animation: slideInRight 0.3s ease-out;
    }
    
    /* Reaction Picker */
    .reaction-picker {
        animation: fadeIn 0.2s ease-out;
    }
    
    .reaction-picker button:hover {
        transform: scale(1.25);
    }
    
    /* Skeleton loader pulse */
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
    
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    /* Smooth transitions */
    * {
        transition: background-color 0.2s ease, color 0.2s ease, transform 0.2s ease;
    }
    
    /* Glassmorphism effect */
    .glass-effect {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    /* Loading spinner */
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
    
    .spinner {
        animation: spin 1s linear infinite;
    }
    
    /* Notification badge */
    .notification-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: #ef4444;
        color: white;
        font-size: 10px;
        font-weight: bold;
        padding: 2px 6px;
        border-radius: 10px;
        min-width: 18px;
        text-align: center;
        animation: fadeIn 0.3s ease-out;
    }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<!-- Header -->
<header class="sticky top-0 z-20 flex items-center justify-between whitespace-nowrap border-b border-gray-200 dark:border-gray-800 px-6 py-3 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm">
<div class="flex items-center gap-4">
<span class="material-symbols-outlined text-primary" style="font-size: 28px;">hub</span>
<h2 class="text-gray-900 dark:text-white text-lg font-bold leading-tight tracking-[-0.015em]">ReySystem Social</h2>
</div>
<div class="flex flex-1 justify-end gap-4 items-center">
<label class="relative flex-col min-w-40 !h-10 max-w-sm hidden md:flex">
<div class="flex w-full flex-1 items-stretch rounded-lg h-full">
<div class="text-gray-500 dark:text-gray-400 flex bg-gray-100 dark:bg-gray-800/50 items-center justify-center pl-3 rounded-l-lg border-y border-l border-gray-200 dark:border-gray-700">
<span class="material-symbols-outlined text-base">search</span>
</div>
<input id="search-input" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-r-lg text-gray-900 dark:text-white focus:outline-0 focus:ring-0 border-y border-r border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/50 h-full placeholder:text-gray-500 dark:placeholder:text-gray-400 px-3 text-sm font-normal leading-normal" placeholder="Buscar empleados, grupos..." value=""/>
<div id="search-results" class="absolute top-full mt-1 w-full bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 hidden z-50 max-h-80 overflow-y-auto"></div>
</div>
</label>
<div class="flex items-center gap-2">
<div class="relative">
<button id="notifications-btn" class="flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 w-10 bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-600 dark:text-gray-300">
<span class="material-symbols-outlined">notifications</span>
<?php if ($notif_count > 0): ?>
<div class="notification-badge"><?php echo $notif_count; ?></div>
<?php endif; ?>
</button>
</div>
<button id="dark-mode-toggle" class="flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 w-10 bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-600 dark:text-gray-300" title="Cambiar tema">
<span class="material-symbols-outlined">dark_mode</span>
</button>
<button id="messages-btn" class="flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 w-10 bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-600 dark:text-gray-300">
<span class="material-symbols-outlined">chat_bubble</span>
</button>
<button id="profile-btn" class="flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 w-10 bg-transparent hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-600 dark:text-gray-300">
<span class="material-symbols-outlined">account_circle</span>
</button>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Avatar de usuario" style='background-image: url("<?php echo htmlspecialchars($usuario_actual['Perfil']); ?>");'></div>
</div>
</div>
</header>

<div class="flex flex-1">
<!-- Barra Lateral Izquierda -->
<aside class="w-64 shrink-0 p-4 border-r border-gray-200 dark:border-gray-800 hidden lg:block">
<div class="flex h-full flex-col justify-between">
<div class="flex flex-col gap-4">
<div class="flex gap-3 px-3">
<div class="flex flex-col">
<h1 class="text-gray-900 dark:text-white text-base font-medium leading-normal">Red Social Interna</h1>
<p class="text-gray-500 dark:text-gray-400 text-sm font-normal leading-normal">Comunicación y Colaboración</p>
</div>
</div>
<nav class="flex flex-col gap-1">
<a class="flex items-center gap-3 px-3 py-2 rounded-lg bg-primary/10 text-primary" href="#">
<span class="material-symbols-outlined fill">home</span>
<p class="text-sm font-medium leading-normal">Inicio</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-700 dark:text-gray-300" href="#">
<span class="material-symbols-outlined">person</span>
<p class="text-sm font-medium leading-normal">Mi Perfil</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-700 dark:text-gray-300" href="#">
<span class="material-symbols-outlined">group</span>
<p class="text-sm font-medium leading-normal">Directorio</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-700 dark:text-gray-300" href="#">
<span class="material-symbols-outlined">forum</span>
<p class="text-sm font-medium leading-normal">Grupos</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-700 dark:text-gray-300" href="#">
<span class="material-symbols-outlined">event</span>
<p class="text-sm font-medium leading-normal">Eventos</p>
</a>
<a class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-700 dark:text-gray-300" href="#">
<span class="material-symbols-outlined">work</span>
<p class="text-sm font-medium leading-normal">Proyectos</p>
</a>
</nav>
</div>
</div>
</aside>

<!-- Contenido Principal -->
<main class="flex-1 p-4 md:p-6 grid grid-cols-12 gap-6">
<!-- Columna Central (Publicaciones) -->
<div class="col-span-12 lg:col-span-8 xl:col-span-7 flex flex-col gap-6">
<!-- Formulario de Publicación -->
<div class="flex items-center p-4 gap-3 bg-white dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800 @container">
<div class="flex-1">
<div class="flex w-full items-start gap-4">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 shrink-0" data-alt="Avatar de usuario" style='background-image: url("<?php echo htmlspecialchars($usuario_actual['Perfil']); ?>");'></div>
<div class="flex flex-1 flex-col">
<textarea id="textarea-publicacion" class="form-input w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-900 dark:text-white focus:outline-0 focus:ring-0 ring-inset focus:ring-2 focus:ring-primary border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800/60 focus:bg-background-light dark:focus:bg-background-dark h-24 placeholder:text-gray-500 dark:placeholder:text-gray-400 p-3 text-base font-normal leading-normal" placeholder="¿Qué estás pensando?"></textarea>
<div id="preview-container" class="preview-container"></div>
<div class="flex justify-between items-center mt-3">
<div class="flex items-center gap-1">
<input type="file" id="input-imagen" accept="image/*" multiple style="display:none">
<input type="file" id="input-video" accept="video/*" multiple style="display:none">
<button id="btn-imagen" class="flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-500 dark:text-gray-400">
<span class="material-symbols-outlined text-xl">image</span>
</button>
<button id="btn-video" class="flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-500 dark:text-gray-400">
<span class="material-symbols-outlined text-xl">videocam</span>
</button>
<button id="btn-encuesta" class="flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-500 dark:text-gray-400">
<span class="material-symbols-outlined text-xl">poll</span>
</button>
<button id="btn-ubicacion" class="flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-500 dark:text-gray-400">
<span class="material-symbols-outlined text-xl">location_on</span>
</button>
<button id="btn-etiqueta" class="flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800/60 text-gray-500 dark:text-gray-400">
<span class="material-symbols-outlined text-xl">person_add</span>
</button>
</div>
<div class="flex items-center gap-2">
<select id="audience-select" class="text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
<option value="todos">Todos</option>
<option value="amigos">Amigos</option>
<option value="equipo">Mi Equipo</option>
</select>
<button id="btn-publicar" class="min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-9 px-4 bg-primary hover:bg-primary/90 text-white text-sm font-medium leading-normal">
<span class="truncate">Publicar</span>
</button>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- Filtros -->
<div class="border-b border-gray-200 dark:border-gray-800">
<div class="flex gap-3 pb-3 overflow-x-auto">
<button class="filter-btn flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-primary/10 text-primary" data-filter="todo">
<p class="text-sm font-medium leading-normal">Todo</p>
</button>
<button class="filter-btn flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-gray-100 dark:bg-gray-800/60 hover:bg-gray-200 dark:hover:bg-gray-800 px-4" data-filter="anuncios">
<p class="text-sm font-medium leading-normal">Anuncios</p>
</button>
<button class="filter-btn flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-gray-100 dark:bg-gray-800/60 hover:bg-gray-200 dark:hover:bg-gray-800 px-4" data-filter="menciones">
<p class="text-sm font-medium leading-normal">Menciones</p>
</button>
<button class="filter-btn flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-gray-100 dark:bg-gray-800/60 hover:bg-gray-200 dark:hover:bg-gray-800 px-4" data-filter="amigos">
<p class="text-sm font-medium leading-normal">Amigos</p>
</button>
</div>
</div>

<!-- Stories Carousel -->
<div class="mb-6 fb-card p-4">
<div class="flex items-center gap-3 overflow-x-auto pb-2" style="scrollbar-width: thin;">
<!-- Crear Story -->
<div class="flex-shrink-0 cursor-pointer group" id="create-story-btn">
<div class="relative w-28 h-44 rounded-xl overflow-hidden bg-gradient-to-br from-primary to-accent hover:scale-105 transition-transform">
<div class="absolute inset-0 flex flex-col items-center justify-center text-white">
<div class="w-12 h-12 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center mb-2 group-hover:scale-110 transition-transform">
<span class="material-symbols-outlined text-3xl">add</span>
</div>
<p class="text-xs font-bold">Crear Story</p>
</div>
</div>
</div>

<!-- Stories de usuarios -->
<div id="stories-container" class="flex gap-3">
<!-- Las stories se cargarán aquí dinámicamente -->
</div>
</div>
</div>

<!-- Contenedor de Publicaciones -->
<div id="contenedor-publicaciones" class="flex flex-col gap-6">
<?php foreach ($publicaciones as $pub): ?>
<div class="p-4 bg-white dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800 @container" data-publicacion-id="<?php echo $pub['id']; ?>">
<div class="flex flex-col items-stretch justify-start">
<div class="flex items-center justify-between mb-4">
<div class="flex items-center gap-3">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Avatar de <?php echo htmlspecialchars($pub['Nombre']); ?>" style='background-image: url("<?php echo htmlspecialchars($pub['Perfil']); ?>");'></div>
<div>
<p class="text-gray-900 dark:text-white font-semibold"><?php echo htmlspecialchars($pub['Nombre']); ?></p>
<p class="text-gray-500 dark:text-gray-400 text-sm"><?php echo htmlspecialchars($pub['Cargo']); ?> · hace <?php echo tiempoRelativo($pub['fecha_creacion']); ?></p>
</div>
</div>
<div class="flex items-center gap-1">
<?php if (isset($pub['audience']) && $pub['audience'] === 'amigos'): ?>
<span class="material-symbols-outlined text-sm text-gray-500">people</span>
<?php elseif (isset($pub['audience']) && $pub['audience'] === 'equipo'): ?>
<span class="material-symbols-outlined text-sm text-gray-500">groups</span>
<?php endif; ?>
<button class="post-options-btn p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800" data-publicacion-id="<?php echo $pub['id']; ?>">
<span class="material-symbols-outlined text-sm">more_horiz</span>
</button>
</div>
</div>

<?php if ($pub['tipo'] === 'repost'): ?>
<div class="border-l-4 border-primary pl-4 mb-4">
<p class="text-sm text-gray-500 mb-1">Reposteado de <?php echo htmlspecialchars($pub['nombre_original']); ?></p>
<div class="text-gray-700 dark:text-gray-300"><?php echo nl2br(htmlspecialchars($pub['contenido_original'])); ?></div>
</div>
<?php endif; ?>

<?php if (!empty($pub['archivos'])): ?>
<div class="grid grid-cols-<?php echo count($pub['archivos']) == 1 ? '1' : '2'; ?> gap-2 mb-4">
<?php foreach ($pub['archivos'] as $archivo): ?>
    <?php if ($archivo['tipo_archivo'] == 'imagen'): ?>
        <img src="<?php echo htmlspecialchars($archivo['ruta_archivo']); ?>" alt="Imagen" class="w-full rounded-lg object-cover <?php echo count($pub['archivos']) == 1 ? 'max-h-96' : 'h-48'; ?>">
    <?php elseif ($archivo['tipo_archivo'] == 'video'): ?>
        <video controls class="w-full rounded-lg <?php echo count($pub['archivos']) == 1 ? 'max-h-96' : 'h-48'; ?>">
            <source src="<?php echo htmlspecialchars($archivo['ruta_archivo']); ?>" type="<?php echo htmlspecialchars($archivo['tipo_mime']); ?>">
            Tu navegador no soporta videos.
        </video>
    <?php endif; ?>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="flex w-full grow flex-col items-stretch justify-center gap-1">
<p class="text-gray-900 dark:text-white text-base font-normal leading-normal"><?php echo nl2br(htmlspecialchars($pub['contenido'])); ?></p>
</div>
<div class="flex items-center gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
<button class="like-btn flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors <?php echo $pub['user_liked'] ? 'text-primary' : ''; ?>" data-publicacion-id="<?php echo $pub['id']; ?>">
<span class="material-symbols-outlined <?php echo $pub['user_liked'] ? 'fill' : ''; ?>">thumb_up</span>
<span class="text-sm font-medium"><?php echo $pub['total_likes'] > 0 ? $pub['total_likes'] . ' Me gusta' : 'Me gusta'; ?></span>
</button>
<button class="comment-btn flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors" data-publicacion-id="<?php echo $pub['id']; ?>">
<span class="material-symbols-outlined">chat_bubble_outline</span>
<span class="text-sm font-medium"><?php echo $pub['total_comentarios'] > 0 ? $pub['total_comentarios'] . ' Comentarios' : 'Comentar'; ?></span>
</button>
<button class="share-btn flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors" data-publicacion-id="<?php echo $pub['id']; ?>" data-nombre-usuario="<?php echo htmlspecialchars($pub['Nombre']); ?>" data-contenido="<?php echo htmlspecialchars(substr($pub['contenido'], 0, 100)); ?>">
<span class="material-symbols-outlined">share</span>
<span class="text-sm font-medium">Compartir</span>
</button>
<button class="reaction-btn flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors" data-publicacion-id="<?php echo $pub['id']; ?>">
<span class="material-symbols-outlined">sentiment_satisfied_alt</span>
<span class="text-sm font-medium">Reaccionar</span>
</button>
</div>

<!-- Sección de comentarios -->
<div class="comment-section mt-4 pt-4 border-t border-gray-200 dark:border-gray-800 hidden">
<div class="space-y-3 mb-4 max-h-60 overflow-y-auto">
<?php if (!empty($pub['comentarios_detalle'])): ?>
<?php foreach ($pub['comentarios_detalle'] as $comentario): ?>
<div class="flex gap-3">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8 shrink-0" style='background-image: url("<?php echo htmlspecialchars($comentario['Perfil']); ?>");'></div>
<div class="flex-1">
<div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-3">
<p class="font-medium text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($comentario['Nombre']); ?></p>
<p class="text-gray-700 dark:text-gray-300 text-sm"><?php echo nl2br(htmlspecialchars($comentario['contenido'])); ?></p>
</div>
<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">hace <?php echo tiempoRelativo($comentario['fecha_creacion']); ?></p>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<div class="flex gap-3">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8 shrink-0" style='background-image: url("<?php echo htmlspecialchars($usuario_actual['Perfil']); ?>");'></div>
<div class="flex-1">
<textarea class="comment-textarea form-input w-full resize-none rounded-lg text-sm" placeholder="Escribe un comentario..."></textarea>
<button class="mt-2 px-3 py-1 bg-primary text-white text-sm rounded-lg hover:bg-primary/90 comment-submit-btn">Comentar</button>
</div>
</div>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<!-- Barra Lateral Derecha -->
<aside class="col-span-12 lg:col-span-4 xl:col-span-5 hidden lg:flex flex-col gap-6">
<!-- Cumpleaños -->
<div class="p-4 bg-white dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800">
<h3 class="font-bold mb-4 text-gray-900 dark:text-white">Próximos Cumpleaños</h3>
<div class="flex flex-col gap-4">
<?php if (empty($cumpleaneros)): ?>
<p class="text-sm text-gray-500">No hay cumpleaños próximos.</p>
<?php else: ?>
<?php foreach ($cumpleaneros as $emp): ?>
<div class="flex items-center gap-3">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo htmlspecialchars($emp['Perfil']); ?>");'></div>
<div>
<p class="font-medium text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($emp['Nombre']); ?></p>
<p class="text-sm text-gray-500 dark:text-gray-400"><?php echo $emp['cuando']; ?></p>
</div>
<button class="saludar-btn ml-auto text-sm text-primary font-medium hover:underline" data-usuario-nombre="<?php echo htmlspecialchars($emp['Nombre']); ?>">Saludar</button>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>

<!-- Nuevos Empleados -->
<div class="p-4 bg-white dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800">
<h3 class="font-bold mb-4 text-gray-900 dark:text-white">Nuevos Empleados</h3>
<div class="flex flex-col gap-4">
<?php if (empty($nuevos_empleados)): ?>
<p class="text-sm text-gray-500">No hay empleados nuevos recientemente.</p>
<?php else: ?>
<?php foreach ($nuevos_empleados as $emp): ?>
<div class="flex items-center gap-3">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo htmlspecialchars($emp['Perfil']); ?>");'></div>
<div>
<p class="font-medium text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($emp['Nombre']); ?></p>
<p class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($emp['Cargo']); ?></p>
</div>
<button class="saludar-btn ml-auto text-sm text-primary font-medium hover:underline" data-usuario-nombre="<?php echo htmlspecialchars($emp['Nombre']); ?>">Saludar</button>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>

<!-- Amigos/Conexiones -->
<div class="p-4 bg-white dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800">
<h3 class="font-bold mb-4 text-gray-900 dark:text-white">Mis Conexiones</h3>
<div class="flex flex-col gap-4">
<?php if (empty($amigos)): ?>
<p class="text-sm text-gray-500">No tienes conexiones aún.</p>
<?php else: ?>
<?php foreach ($amigos as $amigo): ?>
<div class="flex items-center gap-3">
<div class="relative">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo htmlspecialchars($amigo['Perfil']); ?>");'></div>
<?php if ($amigo['Estado'] === 'online'): ?>
<div class="online-indicator"></div>
<?php endif; ?>
</div>
<div>
<p class="font-medium text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($amigo['Nombre']); ?></p>
<p class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($amigo['Cargo']); ?></p>
</div>
<button class="message-btn ml-auto text-sm text-primary font-medium hover:underline" data-usuario-id="<?php echo $amigo['Id']; ?>" data-usuario-nombre="<?php echo htmlspecialchars($amigo['Nombre']); ?>">Mensaje</button>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>

<!-- Sugerencias de Conexión -->
<div class="p-4 bg-white dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800">
<h3 class="font-bold mb-4 text-gray-900 dark:text-white">Sugerencias de Conexión</h3>
<div class="flex flex-col gap-4">
<?php 
// Simulación de sugerencias (en una implementación real, esto vendría de la BD)
 $sugerencias = [
    ['Id' => 101, 'Nombre' => 'Ana Martínez', 'Cargo' => 'Diseñadora UX', 'Perfil' => 'https://picsum.photos/seed/user101/100/100.jpg'],
    ['Id' => 102, 'Nombre' => 'Carlos Rodríguez', 'Cargo' => 'Desarrollador Backend', 'Perfil' => 'https://picsum.photos/seed/user102/100/100.jpg'],
    ['Id' => 103, 'Nombre' => 'Laura Gómez', 'Cargo' => 'Product Manager', 'Perfil' => 'https://picsum.photos/seed/user103/100/100.jpg']
];
?>
<?php foreach ($sugerencias as $sugerencia): ?>
<div class="flex items-center gap-3">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo htmlspecialchars($sugerencia['Perfil']); ?>");'></div>
<div>
<p class="font-medium text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($sugerencia['Nombre']); ?></p>
<p class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($sugerencia['Cargo']); ?></p>
</div>
<button class="connect-btn ml-auto text-sm text-primary font-medium hover:underline" data-usuario-id="<?php echo $sugerencia['Id']; ?>" data-usuario-nombre="<?php echo htmlspecialchars($sugerencia['Nombre']); ?>">Conectar</button>
</div>
<?php endforeach; ?>
</div>
</div>
</aside>
</main>
</div>
</div>

<!-- Modal de Notificaciones -->
<div id="notifications-modal" class="modal">
<div class="bg-white dark:bg-gray-800 rounded-xl p-6 w-full max-w-md">
<div class="flex justify-between items-center mb-4">
<h3 class="text-lg font-bold text-gray-900 dark:text-white">Notificaciones</h3>
<button id="close-notifications" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
<span class="material-symbols-outlined">close</span>
</button>
</div>
<div class="space-y-3 max-h-96 overflow-y-auto">
<?php 
// Simulación de notificaciones (en una implementación real, esto vendría de la BD)
 $notificaciones = [
    ['id' => 1, 'texto' => 'Juan Pérez le gustó tu publicación', 'tiempo' => 'hace 5 minutos', 'leida' => false],
    ['id' => 2, 'texto' => 'María García comentó en tu publicación', 'tiempo' => 'hace 15 minutos', 'leida' => false],
    ['id' => 3, 'texto' => 'Tienes una nueva solicitud de conexión', 'tiempo' => 'hace 1 hora', 'leida' => true],
    ['id' => 4, 'texto' => 'Recordatorio: Reunión de equipo a las 3:00 PM', 'tiempo' => 'hace 2 horas', 'leida' => true]
];
?>
<?php foreach ($notificaciones as $notif): ?>
<div class="p-3 rounded-lg <?php echo $notif['leida'] ? 'bg-gray-100 dark:bg-gray-700' : 'bg-blue-50 dark:bg-blue-900/20'; ?>">
<p class="text-sm text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($notif['texto']); ?></p>
<p class="text-xs text-gray-500 dark:text-gray-400 mt-1"><?php echo htmlspecialchars($notif['tiempo']); ?></p>
</div>
<?php endforeach; ?>
</div>
</div>
</div>

<!-- Modal de Mensajes -->
<div id="messages-modal" class="modal">
<div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-md h-96 flex flex-col">
<div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
<h3 class="text-lg font-bold text-gray-900 dark:text-white">Mensajes</h3>
<button id="close-messages" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
<span class="material-symbols-outlined">close</span>
</button>
</div>
<div class="flex-1 overflow-y-auto p-4">
<div class="space-y-3">
<?php 
// Simulación de conversaciones (en una implementación real, esto vendría de la BD)
 $conversaciones = [
    ['id' => 1, 'nombre' => 'Ana Martínez', 'ultimo_mensaje' => '¿Revisaste el documento?', 'tiempo' => 'hace 5 minutos', 'no_leidos' => 2, 'perfil' => 'https://picsum.photos/seed/user101/100/100.jpg'],
    ['id' => 2, 'nombre' => 'Carlos Rodríguez', 'ultimo_mensaje' => 'Nos vemos en la reunión', 'tiempo' => 'hace 1 hora', 'no_leidos' => 0, 'perfil' => 'https://picsum.photos/seed/user102/100/100.jpg'],
    ['id' => 3, 'nombre' => 'Laura Gómez', 'ultimo_mensaje' => 'Gracias por tu ayuda', 'tiempo' => 'ayer', 'no_leidos' => 0, 'perfil' => 'https://picsum.photos/seed/user103/100/100.jpg']
];
?>
<?php foreach ($conversaciones as $conv): ?>
<div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer conversation-item" data-conversacion-id="<?php echo $conv['id']; ?>" data-nombre="<?php echo htmlspecialchars($conv['nombre']); ?>">
<div class="relative">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo htmlspecialchars($conv['perfil']); ?>");'></div>
<div class="online-indicator"></div>
</div>
<div class="flex-1">
<p class="font-medium text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($conv['nombre']); ?></p>
<p class="text-sm text-gray-500 dark:text-gray-400 truncate"><?php echo htmlspecialchars($conv['ultimo_mensaje']); ?></p>
</div>
<div class="text-right">
<p class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($conv['tiempo']); ?></p>
<?php if ($conv['no_leidos'] > 0): ?>
<div class="bg-primary text-white text-xs rounded-full w-5 h-5 flex items-center justify-center ml-auto mt-1"><?php echo $conv['no_leidos']; ?></div>
<?php endif; ?>
</div>
</div>
<?php endforeach; ?>
</div>
</div>
</div>
</div>

<!-- Modal de Chat Individual -->
<div id="chat-modal" class="modal">
<div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-md h-96 flex flex-col">
<div class="flex justify-between items-center p-4 border-b border-gray-200 dark:border-gray-700">
<div class="flex items-center gap-3">
<div id="chat-user-avatar" class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10"></div>
<div>
<h3 id="chat-user-name" class="text-lg font-bold text-gray-900 dark:text-white"></h3>
<p class="text-xs text-green-500">En línea</p>
</div>
</div>
<button id="close-chat" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
<span class="material-symbols-outlined">close</span>
</button>
</div>
<div id="chat-messages" class="flex-1 overflow-y-auto p-4 space-y-3 flex flex-col">
<!-- Los mensajes se cargarán dinámicamente -->
</div>
<div class="p-4 border-t border-gray-200 dark:border-gray-700">
<div class="flex gap-2">
<input id="chat-input" type="text" class="flex-1 form-input rounded-lg" placeholder="Escribe un mensaje...">
<button id="send-message" class="p-2 bg-primary text-white rounded-lg hover:bg-primary/90">
<span class="material-symbols-outlined text-sm">send</span>
</button>
</div>
</div>
</div>
</div>

<!-- Modal de Perfil de Usuario -->
<div id="profile-modal" class="modal">
<div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-md">
<div class="relative h-32 bg-gradient-to-r from-primary to-blue-600">
<button id="close-profile" class="absolute top-4 right-4 text-white hover:text-gray-200">
<span class="material-symbols-outlined">close</span>
</button>
</div>
<div class="relative px-6 pb-6">
<div class="absolute -top-12 left-6">
<div id="profile-modal-avatar" class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-24 border-4 border-white dark:border-gray-800"></div>
</div>
<div class="pt-16">
<h3 id="profile-modal-name" class="text-xl font-bold text-gray-900 dark:text-white"></h3>
<p id="profile-modal-position" class="text-gray-600 dark:text-gray-400"></p>
<p id="profile-modal-department" class="text-sm text-gray-500 dark:text-gray-500"></p>
<div class="flex gap-4 mt-4">
<div class="text-center">
<p class="text-xl font-bold text-gray-900 dark:text-white">152</p>
<p class="text-sm text-gray-500 dark:text-gray-400">Conexiones</p>
</div>
<div class="text-center">
<p class="text-xl font-bold text-gray-900 dark:text-white">28</p>
<p class="text-sm text-gray-500 dark:text-gray-400">Proyectos</p>
</div>
<div class="text-center">
<p class="text-xl font-bold text-gray-900 dark:text-white">4.8</p>
<p class="text-sm text-gray-500 dark:text-gray-400">Rating</p>
</div>
</div>
<div class="flex gap-2 mt-6">
<button id="profile-message-btn" class="flex-1 py-2 bg-primary text-white rounded-lg hover:bg-primary/90">Mensaje</button>
<button id="profile-connect-btn" class="flex-1 py-2 border border-primary text-primary rounded-lg hover:bg-primary/10">Conectar</button>
</div>
<div class="mt-6">
<h4 class="font-bold text-gray-900 dark:text-white mb-2">Acerca de mí</h4>
<p id="profile-modal-about" class="text-sm text-gray-600 dark:text-gray-400"></p>
</div>
<div class="mt-6">
<h4 class="font-bold text-gray-900 dark:text-white mb-2">Habilidades</h4>
<div class="flex flex-wrap gap-2">
<span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm">Liderazgo</span>
<span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm">Comunicación</span>
<span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm">Trabajo en equipo</span>
<span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-full text-sm">Resolución de problemas</span>
</div>
</div>
</div>
</div>
</div>
</div>

<!-- Modal de Opciones de Publicación -->
<div id="post-options-modal" class="modal">
<div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-xs p-4">
<div class="space-y-2">
<button id="edit-post-btn" class="w-full text-left p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
<span class="material-symbols-outlined text-gray-600 dark:text-gray-400">edit</span>
<span class="text-gray-800 dark:text-gray-200">Editar publicación</span>
</button>
<button id="delete-post-btn" class="w-full text-left p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
<span class="material-symbols-outlined text-gray-600 dark:text-gray-400">delete</span>
<span class="text-gray-800 dark:text-gray-200">Eliminar publicación</span>
</button>
<button id="report-post-btn" class="w-full text-left p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
<span class="material-symbols-outlined text-gray-600 dark:text-gray-400">flag</span>
<span class="text-gray-800 dark:text-gray-200">Reportar publicación</span>
</button>
<button id="save-post-btn" class="w-full text-left p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
<span class="material-symbols-outlined text-gray-600 dark:text-gray-400">bookmark</span>
<span class="text-gray-800 dark:text-gray-200">Guardar publicación</span>
</button>
<button id="close-post-options" class="w-full text-left p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 flex items-center gap-3">
<span class="material-symbols-outlined text-gray-600 dark:text-gray-400">close</span>
<span class="text-gray-800 dark:text-gray-200">Cancelar</span>
</button>
</div>
</div>
</div>

<!-- Modal de Reacciones -->
<div id="reactions-modal" class="modal">
<div class="bg-white dark:bg-gray-800 rounded-xl w-full max-w-xs p-4">
<div class="flex justify-around">
<button class="reaction-option p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full" data-reaction="like">
<span class="text-2xl">👍</span>
</button>
<button class="reaction-option p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full" data-reaction="love">
<span class="text-2xl">❤️</span>
</button>
<button class="reaction-option p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full" data-reaction="laugh">
<span class="text-2xl">😂</span>
</button>
<button class="reaction-option p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full" data-reaction="wow">
<span class="text-2xl">😮</span>
</button>
<button class="reaction-option p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full" data-reaction="sad">
<span class="text-2xl">😢</span>
</button>
<button class="reaction-option p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full" data-reaction="angry">
<span class="text-2xl">😠</span>
</button>
</div>
</div>
</div>

<script>
// --- VARIABLES GLOBALES ---
const textarea = document.getElementById('textarea-publicacion');
const btnPublicar = document.getElementById('btn-publicar');
const btnImagen = document.getElementById('btn-imagen');
const btnVideo = document.getElementById('btn-video');
const btnEncuesta = document.getElementById('btn-encuesta');
const btnUbicacion = document.getElementById('btn-ubicacion');
const btnEtiqueta = document.getElementById('btn-etiqueta');
const inputImagen = document.getElementById('input-imagen');
const inputVideo = document.getElementById('input-video');
const previewContainer = document.getElementById('preview-container');
const contenedorPublicaciones = document.getElementById('contenedor-publicaciones');
const searchInput = document.getElementById('search-input');
const searchResults = document.getElementById('search-results');
const notificationsBtn = document.getElementById('notifications-btn');
const messagesBtn = document.getElementById('messages-btn');
const profileBtn = document.getElementById('profile-btn');

let archivosSeleccionados = [];
let currentPostId = null;
let currentChatUserId = null;
let currentChatUserName = null;

// --- NOTIFICACIONES EN TIEMPO REAL ---
let notificaciones = [];
let mensajesNoLeidos = 0;

// Cargar notificaciones
async function cargarNotificaciones() {
    try {
        const response = await fetch('api/notifications.php?limit=10');
        const data = await response.json();
        
        if (data.success) {
            notificaciones = data.notificaciones;
            actualizarBadgeNotificaciones();
        }
    } catch (error) {
        console.error('Error cargando notificaciones:', error);
    }
}

// Actualizar badge de notificaciones
function actualizarBadgeNotificaciones() {
    const noLeidas = notificaciones.filter(n => n.leida == 0).length;
    const badge = notificationsBtn.querySelector('.notification-badge');
    
    if (noLeidas > 0) {
        if (!badge) {
            const newBadge = document.createElement('div');
            newBadge.className = 'notification-badge';
            newBadge.textContent = noLeidas;
            notificationsBtn.appendChild(newBadge);
        } else {
            badge.textContent = noLeidas;
        }
    } else {
        if (badge) badge.remove();
    }
}

// Mostrar modal de notificaciones
notificationsBtn.addEventListener('click', () => {
    const modal = document.getElementById('notifications-modal');
    modal.classList.add('active');
    
    // Renderizar notificaciones
    const container = modal.querySelector('.space-y-3');
    container.innerHTML = '';
    
    if (notificaciones.length === 0) {
        container.innerHTML = '<p class="text-sm text-gray-500">No tienes notificaciones</p>';
    } else {
        notificaciones.forEach(notif => {
            const div = document.createElement('div');
            div.className = `p-3 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 ${notif.leida == 0 ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-100 dark:bg-gray-700'}`;
            div.onclick = () => marcarComoLeida(notif.id);
            
            const tiempo = tiempoRelativoJS(notif.fecha_creacion);
            
            div.innerHTML = `
                <div class="flex gap-3">
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 shrink-0" style="background-image: url('${notif.emisor_perfil}');"></div>
                    <div class="flex-1">
                        <p class="text-sm text-gray-800 dark:text-gray-200">${notif.mensaje_formateado}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${tiempo}</p>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });
    }
});

// Marcar notificación como leída
async function marcarComoLeida(notifId) {
    try {
        await fetch('api/notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notificacion_id: notifId })
        });
        
        // Actualizar localmente
        const notif = notificaciones.find(n => n.id == notifId);
        if (notif) notif.leida = 1;
        actualizarBadgeNotificaciones();
    } catch (error) {
        console.error('Error marcando notificación:', error);
    }
}

// Botón de mensajes - redirigir al chat
messagesBtn.addEventListener('click', () => {
    window.location.href = 'chat_interno.php';
});

// Función auxiliar para tiempo relativo en JS
function tiempoRelativoJS(fecha) {
    const timestamp = new Date(fecha).getTime();
    const ahora = Date.now();
    const diferencia = Math.floor((ahora - timestamp) / 1000);
    
    if (diferencia < 60) return 'ahora mismo';
    if (diferencia < 3600) return Math.floor(diferencia / 60) + ' min';
    if (diferencia < 86400) return Math.floor(diferencia / 3600) + ' h';
    if (diferencia < 604800) return Math.floor(diferencia / 86400) + ' días';
    return new Date(fecha).toLocaleDateString();
}

// Cargar notificaciones al inicio y cada 30 segundos
cargarNotificaciones();
setInterval(cargarNotificaciones, 30000);

// --- STORIES FUNCTIONALITY ---
const createStoryBtn = document.getElementById('create-story-btn');
const storiesContainer = document.getElementById('stories-container');

// Cargar stories existentes
async function cargarStories() {
    try {
        const response = await fetch('api/stories.php');
        const data = await response.json();
        
        if (data.success && data.stories.length > 0) {
            storiesContainer.innerHTML = '';
            data.stories.forEach(story => {
                agregarStoryAlCarousel(story);
            });
        }
    } catch (error) {
        console.error('Error cargando stories:', error);
    }
}

// Agregar story al carousel
function agregarStoryAlCarousel(story) {
    const storyCard = document.createElement('div');
    storyCard.className = 'flex-shrink-0 cursor-pointer group';
    storyCard.innerHTML = `
        <div class="relative w-28 h-44 rounded-xl overflow-hidden">
            <img src="${story.archivo_url}" alt="Story" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
            <div class="absolute top-2 left-2">
                <div class="w-10 h-10 rounded-full border-4 border-primary bg-cover bg-center" style="background-image: url('${story.Perfil}');"></div>
            </div>
            <div class="absolute bottom-2 left-2 right-2">
                <p class="text-white text-xs font-semibold truncate">${story.Nombre}</p>
            </div>
        </div>
    `;
    
    storyCard.addEventListener('click', () => mostrarStory(story));
    storiesContainer.appendChild(storyCard);
}

// Crear nueva story
createStoryBtn.addEventListener('click', () => {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*,video/*';
    
    input.onchange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('archivo', file);
        
        try {
            const response = await fetch('api/stories.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showToast('Story creada exitosamente');
                agregarStoryAlCarousel(data.story);
                
                // Broadcast via WebSocket
                if (socialSocket && socialSocket.readyState === WebSocket.OPEN) {
                    socialSocket.send(JSON.stringify({
                        type: 'new_story',
                        story: data.story
                    }));
                }
            } else {
                showToast('Error al crear story: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error al subir story', 'error');
        }
    };
    
    input.click();
});

// Mostrar story en modal
function mostrarStory(story) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black/90 z-50 flex items-center justify-center';
    modal.innerHTML = `
        <button class="absolute top-4 right-4 text-white text-4xl hover:scale-110 transition-transform" onclick="this.parentElement.remove()">&times;</button>
        <div class="max-w-md w-full">
            ${story.tipo_archivo === 'video' 
                ? `<video src="${story.archivo_url}" class="w-full rounded-lg" controls autoplay></video>`
                : `<img src="${story.archivo_url}" class="w-full rounded-lg" alt="Story">`
            }
            <div class="mt-4 text-white text-center">
                <p class="font-bold">${story.Nombre}</p>
                <p class="text-sm text-gray-300">${tiempoRelativoJS(story.fecha_creacion)}</p>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

// Cargar stories al inicio
cargarStories();
setInterval(cargarStories, 60000); // Actualizar cada minuto

// Dark Mode Toggle
const darkModeToggle = document.getElementById('dark-mode-toggle');
const darkModeIcon = darkModeToggle.querySelector('.material-symbols-outlined');

// Cargar preferencia guardada
const savedTheme = localStorage.getItem('theme') || 'light';
if (savedTheme === 'dark') {
    document.documentElement.classList.add('dark');
    darkModeIcon.textContent = 'light_mode';
}

darkModeToggle.addEventListener('click', () => {
    document.documentElement.classList.toggle('dark');
    const isDark = document.documentElement.classList.contains('dark');
    
    // Guardar preferencia
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    
    // Cambiar icono
    darkModeIcon.textContent = isDark ? 'light_mode' : 'dark_mode';
    
    // Animación suave
    document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
});

// --- FUNCIONES AUXILIARES ---
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function mostrarNotificacion(mensaje, tipo) {
    const notif = document.createElement('div');
    notif.className = `fixed top-20 right-6 z-50 px-6 py-3 rounded-lg shadow-lg ${
        tipo === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white font-medium`;
    notif.textContent = mensaje;
    notif.style.animation = 'slideInRight 0.3s ease-out';
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// --- LÓGICA DE PUBLICACIÓN ---
btnImagen.addEventListener('click', () => inputImagen.click());
inputImagen.addEventListener('change', (e) => manejarArchivos(e.target.files, 'imagen'));
btnVideo.addEventListener('click', () => inputVideo.click());
inputVideo.addEventListener('change', (e) => manejarArchivos(e.target.files, 'video'));

function manejarArchivos(files, tipo) {
    Array.from(files).forEach(file => {
        if (archivosSeleccionados.length >= 4) {
            mostrarNotificacion('Máximo 4 archivos permitidos', 'error');
            return;
        }
        const reader = new FileReader();
        reader.onload = (e) => {
            const archivo = { file: file, tipo: tipo, preview: e.target.result, id: Date.now() + Math.random() };
            archivosSeleccionados.push(archivo);
            mostrarPreview(archivo);
        };
        reader.readAsDataURL(file);
    });
}

function mostrarPreview(archivo) {
    const div = document.createElement('div');
    div.className = 'preview-item';
    div.dataset.id = archivo.id;
    if (archivo.tipo === 'imagen') {
        div.innerHTML = `<img src="${archivo.preview}" alt="Preview"><button class="remove-preview" onclick="eliminarArchivo(${archivo.id})">×</button>`;
    } else {
        div.innerHTML = `<video src="${archivo.preview}"></video><button class="remove-preview" onclick="eliminarArchivo(${archivo.id})">×</button>`;
    }
    previewContainer.appendChild(div);
}

window.eliminarArchivo = function(id) {
    archivosSeleccionados = archivosSeleccionados.filter(a => a.id !== id);
    const preview = document.querySelector(`[data-id="${id}"]`);
    if (preview) preview.remove();
}

async function publicarContenido() {
    const contenido = textarea.value.trim();
    const audience = document.getElementById('audience-select').value;
    
    if (!contenido && archivosSeleccionados.length === 0) {
        mostrarNotificacion('Escribe algo o adjunta un archivo', 'error');
        return;
    }
    
    // Deshabilitar botón mientras se publica
    btnPublicar.disabled = true;
    btnPublicar.innerHTML = '<span class="material-symbols-outlined spinner">progress_activity</span>';
    
    try {
        // Crear publicación
        const response = await fetch('api/posts.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ contenido, audience })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Agregar publicación al feed localmente
            agregarPublicacionAlFeed(data.publicacion);
            
            // Enviar por WebSocket para actualización en tiempo real a otros usuarios
            if (socialSocket && socialSocket.readyState === WebSocket.OPEN) {
                socialSocket.send(JSON.stringify({
                    type: 'new_post',
                    post: data.publicacion
                }));
            }
            
            // Limpiar formulario
            textarea.value = '';
            archivosSeleccionados = [];
            previewContainer.innerHTML = '';
            
            mostrarNotificacion('Publicación creada exitosamente', 'success');
        } else {
            mostrarNotificacion('Error al crear publicación: ' + (data.message || 'Error desconocido'), 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('Error de conexión', 'error');
    } finally {
        // Restaurar botón
        btnPublicar.disabled = false;
        btnPublicar.innerHTML = '<span class="truncate">Publicar</span>';
    }
}

// Conectar eventos
textarea.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) { 
        e.preventDefault(); 
        publicarContenido(); 
    }
});
btnPublicar.addEventListener('click', publicarContenido);

function agregarPublicacionAlFeed(pub) {
    const nueva = document.createElement('div');
    nueva.className = 'p-4 bg-white dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800 @container';
    nueva.setAttribute('data-publicacion-id', pub.id);
    nueva.style.animation = 'fadeIn 0.5s ease-out';
    
    let archivosHtml = '';
    if (pub.archivos && pub.archivos.length > 0) {
        const gridClass = pub.archivos.length === 1 ? 'grid-cols-1' : 'grid-cols-2';
        archivosHtml = `<div class="grid ${gridClass} gap-2 mb-4">`;
        
        pub.archivos.forEach(archivo => {
            if (archivo.tipo_archivo === 'imagen') {
                archivosHtml += `<img src="${escapeHtml(archivo.ruta_archivo)}" alt="Imagen" class="w-full rounded-lg object-cover ${pub.archivos.length === 1 ? 'max-h-96' : 'h-48'}">`;
            } else if (archivo.tipo_archivo === 'video') {
                archivosHtml += `<video controls class="w-full rounded-lg ${pub.archivos.length === 1 ? 'max-h-96' : 'h-48'}">
                    <source src="${escapeHtml(archivo.ruta_archivo)}" type="${escapeHtml(archivo.tipo_mime)}">
                    Tu navegador no soporta videos.
                </video>`;
            }
        });
        
        archivosHtml += '</div>';
    }
    
    let audienceIcon = '';
    if (pub.audience === 'amigos') {
        audienceIcon = '<span class="material-symbols-outlined text-sm text-gray-500">people</span>';
    } else if (pub.audience === 'equipo') {
        audienceIcon = '<span class="material-symbols-outlined text-sm text-gray-500">groups</span>';
    }
    
    nueva.innerHTML = `
        <div class="flex flex-col items-stretch justify-start">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style="background-image: url('${escapeHtml(pub.Perfil)}');"></div>
                    <div>
                        <p class="text-gray-900 dark:text-white font-semibold">${escapeHtml(pub.Nombre)}</p>
                        <p class="text-gray-500 dark:text-gray-400 text-sm">${escapeHtml(pub.Cargo)} · ahora mismo</p>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    ${audienceIcon}
                    <button class="post-options-btn p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-800" data-publicacion-id="${pub.id}">
                        <span class="material-symbols-outlined text-sm">more_horiz</span>
                    </button>
                </div>
            </div>
            ${archivosHtml}
            <div class="flex w-full grow flex-col items-stretch justify-center gap-1">
                <p class="text-gray-900 dark:text-white text-base font-normal leading-normal">${escapeHtml(pub.contenido).replace(/\n/g, '<br>')}</p>
            </div>
            <div class="flex items-center gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                <button class="like-btn flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors" data-publicacion-id="${pub.id}">
                    <span class="material-symbols-outlined">thumb_up</span>
                    <span class="text-sm font-medium">Me gusta</span>
                </button>
                <button class="comment-btn flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors" data-publicacion-id="${pub.id}">
                    <span class="material-symbols-outlined">chat_bubble_outline</span>
                    <span class="text-sm font-medium">Comentar</span>
                </button>
                <button class="share-btn flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors" data-publicacion-id="${pub.id}" data-nombre-usuario="${escapeHtml(pub.Nombre)}" data-contenido="${escapeHtml(pub.contenido.substring(0, 100))}">
                    <span class="material-symbols-outlined">share</span>
                    <span class="text-sm font-medium">Compartir</span>
                </button>
                <button class="reaction-btn flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors" data-publicacion-id="${pub.id}">
                    <span class="material-symbols-outlined">sentiment_satisfied_alt</span>
                    <span class="text-sm font-medium">Reaccionar</span>
                </button>
            </div>
            <div class="comment-section mt-4 pt-4 border-t border-gray-200 dark:border-gray-800 hidden">
                <div class="space-y-3 mb-4 max-h-60 overflow-y-auto">
                    <!-- Los comentarios se cargarán dinámicamente -->
                </div>
                <div class="flex gap-3">
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8 shrink-0" style='background-image: url("<?php echo htmlspecialchars($usuario_actual['Perfil']); ?>");'></div>
                    <div class="flex-1">
                        <textarea class="comment-textarea form-input w-full resize-none rounded-lg text-sm" placeholder="Escribe un comentario..."></textarea>
                        <button class="mt-2 px-3 py-1 bg-primary text-white text-sm rounded-lg hover:bg-primary/90 comment-submit-btn">Comentar</button>
                    </div>
                </div>
            </div>
        </div>`;
    
    contenedorPublicaciones.insertBefore(nueva, contenedorPublicaciones.firstChild);
}

// --- LÓGICA DE INTERACCIÓN (LIKES, COMENTARIOS, ETC.) ---
document.addEventListener('DOMContentLoaded', function() {
    // Manejo de likes
    contenedorPublicaciones.addEventListener('click', async function(e) {
        const target = e.target.closest('button');
        
        // Likes
        if (target && target.classList.contains('like-btn')) {
            e.preventDefault();
            const likeBtn = target;
            const publicacionId = likeBtn.dataset.publicacionId;
            const icon = likeBtn.querySelector('.material-symbols-outlined');
            const textSpan = likeBtn.querySelector('.text-sm');
            
            // Verificar que existan los elementos
            if (!icon || !textSpan) {
                console.error('No se encontraron elementos en el botón');
                return;
            }
            
            const isLiked = icon.classList.contains('fill');
            
            try {
                const response = await fetch('api/reactions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        publicacion_id: publicacionId,
                        tipo_reaccion: 'like',
                        accion: isLiked ? 'eliminar' : 'agregar'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Actualizar UI
                    if (isLiked) {
                        icon.classList.remove('fill');
                        likeBtn.classList.remove('text-primary');
                        const currentCount = parseInt(textSpan.textContent.match(/\d+/)?.[0] || 0);
                        textSpan.textContent = currentCount > 1 ? `${currentCount - 1} Me gusta` : 'Me gusta';
                    } else {
                        icon.classList.add('fill');
                        likeBtn.classList.add('text-primary');
                        const currentCount = parseInt(textSpan.textContent.match(/\d+/)?.[0] || 0);
                        textSpan.textContent = `${currentCount + 1} Me gusta`;
                    }
                }
            } catch (error) {
                console.error('Error al dar like:', error);
            }
        }

// Función para actualizar usuarios en línea
function updateOnlineUsers(onlineUsers) {
    console.log('👥 Usuarios en línea:', onlineUsers);
    // Aquí puedes actualizar la UI para mostrar usuarios en línea
    // Por ejemplo, agregar indicadores verdes en avatares
}

// Función para agregar nueva publicación al feed
function addNewPostToFeed(post) {
        }
        
        // Comentarios
        if (target && target.classList.contains('comment-btn')) {
            e.preventDefault();
            const postContainer = target.closest('[data-publicacion-id]');
            const commentSection = postContainer.querySelector('.comment-section');
            commentSection.classList.toggle('hidden');
        }
        
        // Envío de comentarios
        if (target && target.classList.contains('comment-submit-btn')) {
            e.preventDefault();
            const formContainer = target.closest('.comment-section');
            const postContainer = formContainer.closest('[data-publicacion-id]');
            const pubId = postContainer.dataset.publicacionId;
            const textarea = formContainer.querySelector('.comment-textarea');
            const contenido = textarea.value.trim();
            
            if (!contenido) return;
            
            // Simulación de envío de comentario
            const commentsContainer = formContainer.querySelector('.space-y-3');
            const newComment = document.createElement('div');
            newComment.className = 'flex gap-3';
            newComment.innerHTML = `
                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8 shrink-0" style='background-image: url("<?php echo htmlspecialchars($usuario_actual['Perfil']); ?>");'></div>
                <div class="flex-1">
                    <div class="bg-gray-100 dark:bg-gray-800 rounded-lg p-3">
                        <p class="font-medium text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($usuario_actual['Nombre']); ?></p>
                        <p class="text-gray-700 dark:text-gray-300 text-sm">${escapeHtml(contenido)}</p>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">ahora mismo</p>
                </div>
            `;
            
            commentsContainer.appendChild(newComment);
            textarea.value = '';
            
            // Actualizar contador de comentarios
            const commentBtn = postContainer.querySelector('.comment-btn span:last-child');
            const currentCount = parseInt(commentBtn.textContent) || 0;
            commentBtn.textContent = `${currentCount + 1} Comentarios`;
            
            mostrarNotificacion('Comentario añadido', 'success');
        }
        
        // Compartir
        if (target && target.classList.contains('share-btn')) {
            e.preventDefault();
            const pubId = target.dataset.publicacionId;
            const nombreUsuario = target.dataset.nombreUsuario;
            const contenidoOriginal = target.dataset.contenido;
            
            const contenidoRepost = `Reposteado de ${nombreUsuario}: "${contenidoOriginal}..."`;
            
            // Simulación de repost
            const repostPublicacion = {
                id: Date.now(),
                contenido: contenidoRepost,
                usuario_id: <?php echo $usuario_actual['Id']; ?>,
                Nombre: "<?php echo htmlspecialchars($usuario_actual['Nombre']); ?>",
                Perfil: "<?php echo htmlspecialchars($usuario_actual['Perfil']); ?>",
                Cargo: "<?php echo htmlspecialchars($usuario_actual['Cargo']); ?>",
                archivos: [],
                total_likes: 0,
                total_comentarios: 0,
                user_liked: false,
                audience: 'todos',
                fecha_creacion: new Date().toISOString(),
                tipo: 'repost',
                nombre_original: nombreUsuario,
                contenido_original: contenidoOriginal
            };
            
            agregarPublicacionAlMuro(repostPublicacion);
            mostrarNotificacion('¡Reposteado con éxito!', 'success');
        }
        
        
        // Reacciones - Mostrar picker inline
        if (target && target.classList.contains('reaction-btn')) {
            e.preventDefault();
            const reactionBtn = target;
            const publicacionId = reactionBtn.dataset.publicacionId;
            
            if (!publicacionId) {
                console.error('No se encontró ID de publicación');
                return;
            }
            
            // Verificar si ya existe un picker
            const existingPicker = document.querySelector('.reaction-picker');
            if (existingPicker) {
                existingPicker.remove();
                return;
            }
            
            // Crear reaction picker
            const picker = document.createElement('div');
            picker.className = 'reaction-picker';
            picker.innerHTML = `
                <button data-reaction="like" title="Me gusta">👍</button>
                <button data-reaction="love" title="Me encanta">❤️</button>
                <button data-reaction="wow" title="Me asombra">😮</button>
                <button data-reaction="sad" title="Me entristece">😢</button>
                <button data-reaction="angry" title="Me enoja">😠</button>
            `;
            
            // Agregar event listeners a cada reacción
            picker.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const reactionType = btn.dataset.reaction;
                    
                    try {
                        const response = await fetch('api/reactions.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                publicacion_id: publicacionId,
                                tipo_reaccion: reactionType,
                                accion: 'agregar'
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            showToast(`Reaccionaste con ${btn.textContent}`);
                            picker.remove();
                        }
                    } catch (error) {
                        console.error('Error al reaccionar:', error);
                    }
                });
            });
            
            // Posicionar el picker
            const container = reactionBtn.parentElement;
            container.style.position = 'relative';
            container.appendChild(picker);
            
            // Cerrar al hacer clic fuera
            setTimeout(() => {
                document.addEventListener('click', function closePicker(e) {
                    if (!picker.contains(e.target) && e.target !== reactionBtn) {
                        picker.remove();
                        document.removeEventListener('click', closePicker);
                    }
                });
            }, 100);
        }
        
        // Opciones de publicación
        if (target && target.classList.contains('post-options-btn')) {
            e.preventDefault();
            currentPostId = target.dataset.publicacionId;
            document.getElementById('post-options-modal').classList.add('active');
        }
    });
    
    // Filtros de publicaciones
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Actualizar botón activo
            document.querySelectorAll('.filter-btn').forEach(b => {
                b.classList.remove('bg-primary/10', 'text-primary');
                b.classList.add('bg-gray-100', 'dark:bg-gray-800/60');
            });
            
            this.classList.remove('bg-gray-100', 'dark:bg-gray-800/60');
            this.classList.add('bg-primary/10', 'text-primary');
            
            // Filtrar publicaciones (simulación)
            const filter = this.dataset.filter;
            mostrarNotificacion(`Filtrando por: ${filter}`, 'success');
            
            // En una implementación real, aquí se haría una llamada AJAX para filtrar las publicaciones
        });
    });
    
    // Botones de saludar
    document.querySelectorAll('.saludar-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const nombre = this.dataset.usuarioNombre;
            mostrarNotificación(`¡Has saludado a ${nombre}!`, 'success');
            this.disabled = true;
            this.textContent = 'Saludado';
        });
    });
    
    // Botones de conectar
    document.querySelectorAll('.connect-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const nombre = this.dataset.usuarioNombre;
            mostrarNotificacion(`Solicitud de conexión enviada a ${nombre}`, 'success');
            this.disabled = true;
            this.textContent = 'Conectado';
        });
    });
    
    // Botones de mensaje
    document.querySelectorAll('.message-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.usuarioId;
            const userName = this.dataset.usuarioNombre;
            abrirChat(userId, userName);
        });
    });
    
    // Búsqueda
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        if (query.length > 0) {
            // Simulación de búsqueda
            const resultados = [
                { id: 1, nombre: 'Juan Pérez', cargo: 'Desarrollador', perfil: 'https://picsum.photos/seed/user1/100/100.jpg' },
                { id: 2, nombre: 'María García', cargo: 'Diseñadora', perfil: 'https://picsum.photos/seed/user2/100/100.jpg' },
                { id: 3, nombre: 'Carlos López', cargo: 'Project Manager', perfil: 'https://picsum.photos/seed/user3/100/100.jpg' }
            ].filter(u => u.nombre.toLowerCase().includes(query.toLowerCase()));
            
            searchResults.innerHTML = '';
            
            if (resultados.length > 0) {
                searchResults.classList.remove('hidden');
                
                resultados.forEach(usuario => {
                    const item = document.createElement('div');
                    item.className = 'flex items-center gap-3 p-3 hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer';
                    item.innerHTML = `
                        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("${usuario.perfil}");'></div>
                        <div>
                            <p class="font-medium text-sm text-gray-900 dark:text-white">${usuario.nombre}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">${usuario.cargo}</p>
                        </div>
                    `;
                    
                    item.addEventListener('click', () => {
                        searchResults.classList.add('hidden');
                        searchInput.value = '';
                        abrirPerfil(usuario.id, usuario.nombre, usuario.cargo, usuario.perfil);
                    });
                    
                    searchResults.appendChild(item);
                });
            } else {
                searchResults.classList.add('hidden');
            }
        } else {
            searchResults.classList.add('hidden');
        }
    });
    
    // Clic fuera de la búsqueda para cerrar resultados
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.add('hidden');
        }
    });
    
    // Modal de notificaciones
    notificationsBtn.addEventListener('click', () => {
        document.getElementById('notifications-modal').classList.add('active');
    });
    
    document.getElementById('close-notifications').addEventListener('click', () => {
        document.getElementById('notifications-modal').classList.remove('active');
    });
    
    // Modal de mensajes
    messagesBtn.addEventListener('click', () => {
        document.getElementById('messages-modal').classList.add('active');
    });
    
    document.getElementById('close-messages').addEventListener('click', () => {
        document.getElementById('messages-modal').classList.remove('active');
    });
    
    // Conversaciones
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', function() {
            const nombre = this.dataset.nombre;
            document.getElementById('messages-modal').classList.remove('active');
            abrirChat(null, nombre);
        });
    });
    
    // Modal de perfil
    profileBtn.addEventListener('click', () => {
        abrirPerfil(
            <?php echo $usuario_actual['Id']; ?>,
            "<?php echo htmlspecialchars($usuario_actual['Nombre']); ?>",
            "<?php echo htmlspecialchars($usuario_actual['Cargo']); ?>",
            "<?php echo htmlspecialchars($usuario_actual['Perfil']); ?>"
        );
    });
    
    document.getElementById('close-profile').addEventListener('click', () => {
        document.getElementById('profile-modal').classList.remove('active');
    });
    
    document.getElementById('profile-message-btn').addEventListener('click', () => {
        const nombre = document.getElementById('profile-modal-name').textContent;
        document.getElementById('profile-modal').classList.remove('active');
        abrirChat(null, nombre);
    });
    
    // Modal de chat
    document.getElementById('close-chat').addEventListener('click', () => {
        document.getElementById('chat-modal').classList.remove('active');
    });
    
    document.getElementById('send-message').addEventListener('click', enviarMensaje);
    
    document.getElementById('chat-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            enviarMensaje();
        }
    });
    
    // Modal de opciones de publicación
    document.getElementById('close-post-options').addEventListener('click', () => {
        document.getElementById('post-options-modal').classList.remove('active');
    });
    
    document.getElementById('edit-post-btn').addEventListener('click', () => {
        document.getElementById('post-options-modal').classList.remove('active');
        mostrarNotificacion('Función de edición en desarrollo', 'success');
    });
    
    document.getElementById('delete-post-btn').addEventListener('click', () => {
        document.getElementById('post-options-modal').classList.remove('active');
        
        if (confirm('¿Estás seguro de que quieres eliminar esta publicación?')) {
            // Eliminar publicación del DOM
            const postElement = document.querySelector(`[data-publicacion-id="${currentPostId}"]`);
            if (postElement) {
                postElement.remove();
                mostrarNotificacion('Publicación eliminada', 'success');
            }
        }
    });
    
    document.getElementById('report-post-btn').addEventListener('click', () => {
        document.getElementById('post-options-modal').classList.remove('active');
        mostrarNotificacion('Publicación reportada', 'success');
    });
    
    document.getElementById('save-post-btn').addEventListener('click', () => {
        document.getElementById('post-options-modal').classList.remove('active');
        mostrarNotificación('Publicación guardada', 'success');
    });
    
    // Modal de reacciones
    document.querySelectorAll('.reaction-option').forEach(btn => {
        btn.addEventListener('click', function() {
            const reaction = this.dataset.reaction;
            document.getElementById('reactions-modal').classList.remove('active');
            
            // Actualizar botón de reacción en la publicación
            const postElement = document.querySelector(`[data-publicacion-id="${currentPostId}"]`);
            if (postElement) {
                const reactionBtn = postElement.querySelector('.reaction-btn span:last-child');
                const reactionEmojis = {
                    like: '👍',
                    love: '❤️',
                    laugh: '😂',
                    wow: '😮',
                    sad: '😢',
                    angry: '😠'
                };
                
                reactionBtn.textContent = reactionEmojis[reaction];
                mostrarNotificacion(`Reacción ${reaction} añadida`, 'success');
            }
        });
    });
    
    // Funciones adicionales
    btnEncuesta.addEventListener('click', () => {
        mostrarNotificacion('Función de encuestas en desarrollo', 'success');
    });
    
    btnUbicacion.addEventListener('click', () => {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                position => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    mostrarNotificación(`Ubicación obtenida: ${lat.toFixed(4)}, ${lng.toFixed(4)}`, 'success');
                    
                    // Agregar ubicación al textarea
                    textarea.value += `\n📍 Ubicación: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                },
                error => {
                    mostrarNotificación('No se pudo obtener la ubicación', 'error');
                }
            );
        } else {
            mostrarNotificación('Tu navegador no soporta geolocalización', 'error');
        }
    });
    
    btnEtiqueta.addEventListener('click', () => {
        mostrarNotificación('Función de etiquetado en desarrollo', 'success');
    });
});

// --- FUNCIONES AUXILIARES DE MODALES ---
function abrirChat(userId, userName) {
    currentChatUserId = userId;
    currentChatUserName = userName;
    
    document.getElementById('chat-user-name').textContent = userName;
    document.getElementById('chat-user-avatar').style.backgroundImage = `url('https://picsum.photos/seed/${userName}/100/100.jpg')`;
    document.getElementById('chat-messages').innerHTML = '';
    
    // Simulación de mensajes previos
    const mensajes = [
        { texto: 'Hola, ¿cómo estás?', enviado: false, tiempo: '10:30 AM' },
        { texto: '¡Hola! Muy bien, gracias por preguntar', enviado: true, tiempo: '10:32 AM' },
        { texto: '¿Ya revisaste el documento que te envié?', enviado: false, tiempo: '10:35 AM' }
    ];
    
    mensajes.forEach(msg => {
        agregarMensajeAlChat(msg.texto, msg.enviado, msg.tiempo);
    });
    
    document.getElementById('chat-modal').classList.add('active');
}

function enviarMensaje() {
    const input = document.getElementById('chat-input');
    const texto = input.value.trim();
    
    if (texto) {
        const ahora = new Date();
        const tiempo = ahora.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        
        agregarMensajeAlChat(texto, true, tiempo);
        input.value = '';
        
        // Simulación de respuesta
        setTimeout(() => {
            const respuestas = [
                'Entendido, gracias por la información',
                'Lo revisaré y te respondo pronto',
                '¡Claro! Nos vemos luego',
                'Perfecto, me parece bien'
            ];
            
            const respuesta = respuestas[Math.floor(Math.random() * respuestas.length)];
            agregarMensajeAlChat(respuesta, false, tiempo);
        }, 1000 + Math.random() * 2000);
    }
}

function agregarMensajeAlChat(texto, enviado, tiempo) {
    const messagesContainer = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${enviado ? 'sent' : 'received'}`;
    
    messageDiv.innerHTML = `
        <div>${escapeHtml(texto)}</div>
        <div class="chat-message-time">${tiempo}</div>
    `;
    
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function abrirPerfil(userId, nombre, cargo, perfil) {
    document.getElementById('profile-modal-name').textContent = nombre;
    document.getElementById('profile-modal-position').textContent = cargo;
    document.getElementById('profile-modal-department').textContent = 'Departamento de Tecnología';
    document.getElementById('profile-modal-avatar').style.backgroundImage = `url('${perfil}')`;
    document.getElementById('profile-modal-about').textContent = 'Apasionado por la tecnología y la innovación. Siempre buscando nuevos desafíos y oportunidades para crecer profesionalmente.';
    
    document.getElementById('profile-modal').classList.add('active');
}

// ========== WEBSOCKET Y TIEMPO REAL ==========

let socialSocket = null;
let currentPage = 1;
let isLoadingMore = false;
let hasMorePosts = true;

// Conectar WebSocket
function initSocialWebSocket() {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const host = window.location.hostname;
    
    try {
        socialSocket = new WebSocket(`${protocol}//${host}:8081`);
        
        socialSocket.onopen = () => {
            console.log('✅ WebSocket Social conectado');
            // Identificarse
            socialSocket.send(JSON.stringify({
                type: 'login',
                userId: <?php echo $usuario_actual['Id']; ?>,
                userName: '<?php echo addslashes($usuario_actual['Nombre']); ?>'
            }));
            
            // Heartbeat cada 30 segundos
            setInterval(() => {
                if (socialSocket && socialSocket.readyState === WebSocket.OPEN) {
                    socialSocket.send(JSON.stringify({
                        type: 'heartbeat',
                        userId: <?php echo $usuario_actual['Id']; ?>
                    }));
                }
            }, 30000);
        };
        
        socialSocket.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                handleSocialMessage(data);
            } catch (e) {
                console.error('Error procesando mensaje:', e);
            }
        };
        
        socialSocket.onclose = () => {
            console.log('❌ WebSocket Social desconectado');
            // Reconectar después de 3 segundos
            setTimeout(initSocialWebSocket, 3000);
        };
        
        socialSocket.onerror = (error) => {
            console.error('Error WebSocket:', error);
        };
        
    } catch (e) {
        console.error('Error al conectar WebSocket:', e);
    }
}

// Manejar mensajes del WebSocket
function handleSocialMessage(data) {
    console.log('📨 Mensaje recibido:', data.type);
    
    switch (data.type) {
        case 'new_post':
            addNewPostToFeed(data.post);
            showToast(`Nueva publicación de ${data.post.Nombre}`);
            break;
        case 'new_reaction':
            updateReactionCount(data);
            break;
        case 'new_comment':
            updateCommentCount(data);
            break;
        case 'new_story':
            addNewStory(data.story);
            break;
        case 'typing':
            showTypingIndicator(data);
            break;
        case 'user_status':
            updateOnlineUsers(data.onlineUsers);
            break;
        case 'new_notification':
            // Notificación en tiempo real
            notificaciones.unshift(data.notification);
            actualizarBadgeNotificaciones();
            showToast(data.notification.mensaje);
            break;
    }
}

// Función para actualizar usuarios en línea
function updateOnlineUsers(onlineUsers) {
    console.log('👥 Usuarios en línea:', onlineUsers);
}

// Función para agregar nueva publicación al feed
function addNewPostToFeed(post) {
    if (!post || !contenedorPublicaciones) return;
    agregarPublicacionAlFeed(post);
}

// Función para actualizar contador de reacciones
function updateReactionCount(data) {
    const post = document.querySelector(`[data-publicacion-id="${data.publicacionId}"]`);
    if (post) {
        const likeBtn = post.querySelector('.like-btn');
        if (likeBtn) {
            const textSpan = likeBtn.querySelector('.text-sm');
            if (textSpan) {
                const currentCount = parseInt(textSpan.textContent.match(/\d+/)?.[0] || 0);
                textSpan.textContent = `${currentCount + 1} Me gusta`;
            }
        }
    }
}

// Función para actualizar contador de comentarios
function updateCommentCount(data) {
    const post = document.querySelector(`[data-publicacion-id="${data.publicacionId}"]`);
    if (post) {
        const commentBtn = post.querySelector('.comment-btn');
        if (commentBtn) {
            const textSpan = commentBtn.querySelector('.text-sm');
            if (textSpan) {
                const currentCount = parseInt(textSpan.textContent.match(/\d+/)?.[0] || 0);
                textSpan.textContent = `${currentCount + 1} Comentarios`;
            }
        }
    }
}

// Función para agregar nueva story
function addNewStory(story) {
    console.log('📸 Nueva story:', story);
    // Implementar cuando se agregue el carousel de stories
}

// Función para mostrar indicador de escritura
function showTypingIndicator(data) {
    console.log('✍️ Usuario escribiendo:', data.userName);
    // Implementar indicador de escritura
}

// Función para mostrar toast notification
function showToast(mensaje) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-6 py-3 rounded-lg shadow-lg z-50';
    toast.style.animation = 'slideInRight 0.3s ease-out';
    toast.textContent = mensaje;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Agregar nueva publicación al feed
function addNewPostToFeed(post) {
    const container = document.getElementById('contenedor-publicaciones');
    const postHtml = createPostHTML(post);
    container.insertAdjacentHTML('afterbegin', postHtml);
    
    // Animación de entrada
    const newPost = container.firstElementChild;
    newPost.style.animation = 'fadeIn 0.5s ease-out';
    
    // Toast notification
    showToast(`Nueva publicación de ${post.Nombre}`);
}

// Crear HTML de publicación
function createPostHTML(pub) {
    const tiempoRelativo = 'ahora mismo';
    return `
        <div class="p-4 bg-white dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800 @container" data-publicacion-id="${pub.id}" style="animation: fadeIn 0.5s">
            <div class="flex flex-col items-stretch justify-start">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("${pub.Perfil}");'></div>
                        <div>
                            <p class="text-gray-900 dark:text-white font-semibold">${escapeHtml(pub.Nombre)}</p>
                            <p class="text-gray-500 dark:text-gray-400 text-sm">${escapeHtml(pub.Cargo || '')} · ${tiempoRelativo}</p>
                        </div>
                    </div>
                </div>
                <div class="flex w-full grow flex-col items-stretch justify-center gap-1">
                    <p class="text-gray-900 dark:text-white text-base font-normal leading-normal">${escapeHtml(pub.contenido)}</p>
                </div>
                <div class="flex items-center gap-4 mt-4 pt-4 border-t border-gray-200 dark:border-gray-800">
                    <button class="reaction-btn flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary" data-publicacion-id="${pub.id}">
                        <span class="material-symbols-outlined">sentiment_satisfied_alt</span>
                        <span class="text-sm font-medium">Reaccionar</span>
                    </button>
                    <button class="comment-btn flex items-center gap-2 text-gray-600 dark:text-gray-400 hover:text-primary" data-publicacion-id="${pub.id}">
                        <span class="material-symbols-outlined">chat_bubble_outline</span>
                        <span class="text-sm font-medium">Comentar</span>
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Infinite Scroll
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting && !isLoadingMore && hasMorePosts) {
            loadMorePosts();
        }
    });
}, { threshold: 0.1 });

// Observar el último elemento
function observeLastPost() {
    const posts = document.querySelectorAll('[data-publicacion-id]');
    if (posts.length > 0) {
        observer.observe(posts[posts.length - 1]);
    }
}

// Cargar más publicaciones
async function loadMorePosts() {
    if (isLoadingMore || !hasMorePosts) return;
    
    isLoadingMore = true;
    currentPage++;
    
    // Mostrar skeleton loader
    showSkeletonLoader();
    
    try {
        const response = await fetch(`api/posts.php?page=${currentPage}`);
        const data = await response.json();
        
        if (data.success && data.publicaciones.length > 0) {
            data.publicaciones.forEach(pub => {
                const postHtml = createPostHTML(pub);
                document.getElementById('contenedor-publicaciones').insertAdjacentHTML('beforeend', postHtml);
            });
            
            if (data.publicaciones.length < 10) {
                hasMorePosts = false;
            }
            
            observeLastPost();
        } else {
            hasMorePosts = false;
        }
    } catch (error) {
        console.error('Error cargando publicaciones:', error);
    } finally {
        hideSkeletonLoader();
        isLoadingMore = false;
    }
}

function showSkeletonLoader() {
    const loader = `
        <div id="skeleton-loader" class="p-4 bg-white dark:bg-gray-900/50 rounded-xl border border-gray-200 dark:border-gray-800 animate-pulse">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-gray-300 dark:bg-gray-700 rounded-full size-10"></div>
                <div class="flex-1">
                    <div class="h-4 bg-gray-300 dark:bg-gray-700 rounded w-1/4 mb-2"></div>
                    <div class="h-3 bg-gray-300 dark:bg-gray-700 rounded w-1/3"></div>
                </div>
            </div>
            <div class="h-20 bg-gray-300 dark:bg-gray-700 rounded"></div>
        </div>
    `;
    document.getElementById('contenedor-publicaciones').insertAdjacentHTML('beforeend', loader);
}

function hideSkeletonLoader() {
    const loader = document.getElementById('skeleton-loader');
    if (loader) loader.remove();
}

// Sistema de reacciones múltiples
document.addEventListener('click', async (e) => {
    const reactionBtn = e.target.closest('.reaction-btn');
    if (reactionBtn) {
        const publicacionId = reactionBtn.dataset.publicacionId;
        showReactionPicker(publicacionId, reactionBtn);
    }
});

function showReactionPicker(publicacionId, button) {
    // Remover picker existente
    const existingPicker = document.querySelector('.reaction-picker');
    if (existingPicker) existingPicker.remove();
    
    const picker = document.createElement('div');
    picker.className = 'reaction-picker absolute bg-white dark:bg-gray-800 rounded-full shadow-lg p-2 flex gap-2 z-50';
    picker.style.bottom = '100%';
    picker.style.left = '0';
    picker.style.marginBottom = '8px';
    
    const reactions = [
        { type: 'like', emoji: '👍', label: 'Me gusta' },
        { type: 'love', emoji: '❤️', label: 'Me encanta' },
        { type: 'wow', emoji: '😮', label: 'Me asombra' },
        { type: 'sad', emoji: '😢', label: 'Me entristece' },
        { type: 'angry', emoji: '😠', label: 'Me enoja' }
    ];
    
    reactions.forEach(reaction => {
        const btn = document.createElement('button');
        btn.className = 'text-2xl hover:scale-125 transition-transform';
        btn.textContent = reaction.emoji;
        btn.title = reaction.label;
        btn.onclick = () => addReaction(publicacionId, reaction.type);
        picker.appendChild(btn);
    });
    
    button.style.position = 'relative';
    button.appendChild(picker);
    
    // Cerrar al hacer clic fuera
    setTimeout(() => {
        document.addEventListener('click', function closePicker(e) {
            if (!picker.contains(e.target)) {
                picker.remove();
                document.removeEventListener('click', closePicker);
            }
        });
    }, 100);
}

async function addReaction(publicacionId, reactionType) {
    try {
        const response = await fetch('api/reactions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ publicacionId, reactionType })
        });
        
        const data = await response.json();
        
        if (data.success && socialSocket && socialSocket.readyState === WebSocket.OPEN) {
            socialSocket.send(JSON.stringify({
                type: 'new_reaction',
                publicacionId,
                userId: <?php echo $usuario_actual['Id']; ?>,
                userName: '<?php echo addslashes($usuario_actual['Nombre']); ?>',
                reactionType
            }));
        }
        
        // Remover picker
        document.querySelector('.reaction-picker')?.remove();
    } catch (error) {
        console.error('Error al agregar reacción:', error);
    }
}

function updateReactionCount(data) {
    // Actualizar contador de reacciones en la publicación
    const post = document.querySelector(`[data-publicacion-id="${data.publicacionId}"]`);
    if (post) {
        // Mostrar toast
        showToast(`${data.userName} reaccionó con ${getReactionEmoji(data.reactionType)}`);
    }
}

function getReactionEmoji(type) {
    const emojis = { like: '👍', love: '❤️', wow: '😮', sad: '😢', angry: '😠' };
    return emojis[type] || '👍';
}

function updateCommentCount(data) {
    showToast('Nuevo comentario en una publicación');
}

// Toast notifications
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 bg-gray-900 text-white px-4 py-3 rounded-lg shadow-lg z-50 animate-slideInRight';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease-out';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', () => {
    initSocialWebSocket();
    observeLastPost();
    
    // Solicitar permiso para notificaciones
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
});

</script>
</body>
</html>