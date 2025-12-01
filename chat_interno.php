<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
// --- CONFIGURACIÓN DEL SERVIDOR WEBSOCKET ---
// Usar el host actual del servidor en lugar de localhost
$websocket_host = $_SERVER['HTTP_HOST'] . ":8080"; // Detecta automáticamente el dominio

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener datos del usuario actual
$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
$usuario_actual = $resultado->fetch_assoc();
$Nombre_Completo = $usuario_actual['Nombre']." ".$usuario_actual['Apellido'];
$Perfil = $usuario_actual['Perfil'];
$Usuario_Id = $usuario_actual['Id'];

// Obtener preferencias de tema
$query_tema = "SELECT tema_color, notificaciones_escritorio FROM preferencias_usuario WHERE Id_Usuario = $Usuario_Id";
$resultado_tema = $conexion->query($query_tema);
$tema_usuario = $resultado_tema ? $resultado_tema->fetch_assoc() : null;
$tema_color = $tema_usuario ? $tema_usuario['tema_color'] : 'primary';
$notificaciones_escritorio = $tema_usuario ? (bool)$tema_usuario['notificaciones_escritorio'] : true;

// Obtener usuario seleccionado
$chat_con = isset($_GET['chat']) ? intval($_GET['chat']) : 0;

// Obtener lista de usuarios
$query_usuarios = "SELECT * FROM usuarios WHERE Id != $Usuario_Id ORDER BY Nombre";
$usuarios_lista = $conexion->query($query_usuarios);

// Si no hay usuario seleccionado, seleccionar el primero
if ($chat_con == 0 && $usuarios_lista->num_rows > 0) {
    $usuarios_lista->data_seek(0);
    $primer_usuario = $usuarios_lista->fetch_assoc();
    $chat_con = $primer_usuario['Id'];
    $usuarios_lista->data_seek(0);
}

// Obtener información del usuario con quien se está chateando
if ($chat_con > 0) {
    $query_chat_usuario = "SELECT * FROM usuarios WHERE Id = $chat_con";
    $resultado_chat = $conexion->query($query_chat_usuario);
    $chat_usuario = $resultado_chat->fetch_assoc();
}

// Obtener mensajes del chat actual
$mensajes = [];
if ($chat_con > 0) {
    $query_mensajes = "SELECT m.*, 
                              u1.Nombre as Emisor_Nombre, 
                              u1.Apellido as Emisor_Apellido, 
                              u1.Perfil as Emisor_Perfil
                       FROM mensajes_chat m
                       LEFT JOIN usuarios u1 ON m.Id_Emisor = u1.Id
                       WHERE (m.Id_Emisor = $Usuario_Id AND m.Id_Receptor = $chat_con)
                          OR (m.Id_Emisor = $chat_con AND m.Id_Receptor = $Usuario_Id)
                       ORDER BY m.Fecha_Mensaje ASC";
    $resultado_mensajes = $conexion->query($query_mensajes);
    if ($resultado_mensajes) {
        while($msg = $resultado_mensajes->fetch_assoc()) {
            $mensajes[] = $msg;
        }
    }
    
    // Marcar mensajes como leídos
    $conexion->query("UPDATE mensajes_chat SET leido = 1, Estado_Entrega = 'read' WHERE Id_Emisor = $chat_con AND Id_Receptor = $Usuario_Id AND leido = 0");
}

// Función para calcular estado detallado
function calcularEstado($ultima_actividad) {
    $ahora = new DateTime();
    $ultima = new DateTime($ultima_actividad);
    $diferencia = $ahora->diff($ultima);
    
    $total_segundos = $diferencia->days * 86400 + $diferencia->h * 3600 + $diferencia->i * 60 + $diferencia->s;
    
    if ($total_segundos < 60) {
        return ['clase' => 'status-online', 'texto' => 'En línea'];
    } elseif ($total_segundos < 300) { // 5 minutos
        return ['clase' => 'status-online', 'texto' => 'Activo hace ' . floor($total_segundos / 60) . ' min'];
    } elseif ($total_segundos < 3600) {
        $minutos = floor($total_segundos / 60);
        return ['clase' => 'status-away', 'texto' => "Hace {$minutos} min"];
    } elseif ($total_segundos < 86400) {
        $horas = floor($total_segundos / 3600);
        return ['clase' => 'status-away', 'texto' => "Hace {$horas}h"];
    } else {
        $dias = floor($total_segundos / 86400);
        return ['clase' => 'status-offline', 'texto' => "Hace {$dias}d"];
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Chat Interno - ReySystemAPP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    primary: "var(--color-primary)",
                    secondary: "var(--color-secondary)",
                    background: {
                        light: "#f8fafc",
                        dark: "#0f172a"
                    }
                },
                fontFamily: {
                    sans: ['Manrope', 'sans-serif'],
                },
                keyframes: {
                    wave: {
                        '0%, 100%': { height: '10%' },
                        '50%': { height: '100%' },
                    },
                    fadeIn: {
                        '0%': { opacity: '0', transform: 'translateY(10px)' },
                        '100%': { opacity: '1', transform: 'translateY(0)' }
                    },
                    pulse: {
                        '0%, 100%': { opacity: '1' },
                        '50%': { opacity: '0.5' }
                    }
                },
                animation: {
                    'fadeIn': 'fadeIn 0.3s ease-out',
                    'pulse': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite'
                }
            }
        }
    }
</script>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
    }
    
    :root {
        --color-primary: <?php echo $tema_color == 'primary' ? '#3b82f6' : ($tema_color == 'success' ? '#10b981' : '#f59e0b'); ?>;
        --color-secondary: #64748b;
    }
    
    .chat-bubble {
        background: linear-gradient(135deg, var(--color-primary) 0%, rgba(59, 130, 246, 0.8) 100%);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .status-indicator {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        position: relative;
    }
    
    .status-online { 
        background-color: #10b981;
        animation: pulse 2s infinite;
    }
    
    .status-online::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background-color: #10b981;
        animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
    }
    
    @keyframes ping {
        75%, 100% {
            transform: scale(2);
            opacity: 0;
        }
    }
    
    .status-away { background-color: #f59e0b; }
    .status-offline { background-color: #6b7280; }
    
    .typing-indicator span {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #6b7280;
        margin: 0 2px;
        animation: typing 1.4s infinite;
    }
    
    .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
    
    @keyframes typing {
        0%, 60%, 100% {
            transform: translateY(0);
            opacity: 0.7;
        }
        30% {
            transform: translateY(-10px);
            opacity: 1;
        }
    }
    
    .message-container {
        transition: all 0.3s ease;
    }
    
    .message-container:hover {
        transform: translateX(-2px);
    }
    
    .scroll-smooth {
        scroll-behavior: smooth;
    }
    
    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #1e293b;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #475569;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }
</style>
</head>
<body class="bg-background-dark font-sans">
<div class="flex h-screen w-full overflow-hidden">
    
<!-- ========== SIDEBAR ========== -->
<aside class="flex w-64 flex-col bg-[#111722] border-r border-slate-800">
    <div class="flex h-full flex-col justify-between p-4">
        <div class="flex flex-col gap-4">
            <!-- Logo -->
            <div class="flex items-center gap-3 px-3 py-2">
                <div class="size-8 text-primary">
                    <svg fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 4H17.3334V17.3334H30.6666V30.6666H44V44H4V4Z" fill="currentColor"></path>
                    </svg>
                </div>
                <h2 class="text-white text-lg font-bold">ReySystemAPP</h2>
            </div>
            
            <!-- Navigation -->
            <nav class="flex flex-col gap-2 mt-4">
                <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-800 transition-colors" href="index.php">
                    <span class="material-symbols-outlined text-2xl">home</span>
                    <p class="text-sm font-medium">Inicio</p>
                </a>
                <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-800 transition-colors" href="nueva_venta.php">
                    <span class="material-symbols-outlined text-2xl">shopping_cart</span>
                    <p class="text-sm font-medium">Ventas</p>
                </a>
                <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-800 transition-colors" href="inventario.php">
                    <span class="material-symbols-outlined text-2xl">inventory</span>
                    <p class="text-sm font-medium">Inventario</p>
                </a>
                <a class="flex items-center gap-3 rounded-lg bg-slate-800 px-3 py-2 text-primary" href="chat_interno.php">
                    <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">chat_bubble</span>
                    <p class="text-sm font-medium">Chat</p>
                </a>
            </nav>
        </div>
        
        <div class="flex flex-col gap-1">
            <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-800 transition-colors" href="#" onclick="document.getElementById('settingsModal').classList.remove('hidden')">
                <span class="material-symbols-outlined text-2xl">settings</span>
                <p class="text-sm font-medium">Ajustes</p>
            </a>
            <a class="flex items-center gap-3 rounded-lg px-3 py-2 text-slate-400 hover:bg-slate-800 transition-colors" href="logout.php">
                <span class="material-symbols-outlined text-2xl">logout</span>
                <p class="text-sm font-medium">Salir</p>
            </a>
        </div>
    </div>
</aside>

<main class="flex flex-1 flex-col overflow-hidden">
    
<!-- ========== TOPBAR ========== -->
<header class="flex h-16 flex-shrink-0 items-center justify-between border-b border-slate-800 px-8 bg-[#111722] relative z-10">
    <div class="flex items-center gap-4" id="headerTitle">
        <h2 class="text-white text-lg font-bold">Chat Interno</h2>
    </div>

    <!-- Search Bar (Hidden by default) -->
    <div id="messageSearchBar" class="hidden absolute inset-x-0 top-0 h-full bg-[#111722] px-8 flex items-center gap-4 z-20">
        <span class="material-symbols-outlined text-slate-500">search</span>
        <input type="text" id="messageSearchInput" class="flex-1 bg-transparent border-none focus:ring-0 text-white placeholder:text-slate-400" placeholder="Buscar en este chat...">
        <span id="searchCount" class="text-xs text-slate-500 font-medium hidden">0/0</span>
        <div class="flex items-center gap-1">
            <button id="searchUpBtn" class="p-1 rounded hover:bg-slate-700 text-slate-500 disabled:opacity-50">
                <span class="material-symbols-outlined">keyboard_arrow_up</span>
            </button>
            <button id="searchDownBtn" class="p-1 rounded hover:bg-slate-700 text-slate-500 disabled:opacity-50">
                <span class="material-symbols-outlined">keyboard_arrow_down</span>
            </button>
            <button id="closeSearchBtn" class="p-1 rounded hover:bg-slate-700 text-slate-500 ml-2">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
    </div>

    <div class="flex flex-1 items-center justify-end gap-4">
        <button id="toggleSearchBtn" class="p-2 rounded-full text-slate-400 hover:bg-slate-800 transition-colors" title="Buscar mensaje">
            <span class="material-symbols-outlined">search</span>
        </button>
        
        <!-- Connection Status -->
        <div id="connectionStatus" class="flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800 text-xs font-medium text-slate-400">
            <span class="w-2 h-2 rounded-full bg-slate-600"></span>
            <span>Conectando...</span>
        </div>
        
        <!-- User Profile -->
        <div class="flex items-center gap-3">
            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style="background-image: url('<?php echo $Perfil; ?>');"></div>
            <div class="flex flex-col text-sm">
                <p class="font-semibold text-white"><?php echo $Nombre_Completo; ?></p>
                <p class="text-green-500 font-medium text-xs">En línea</p>
            </div>
        </div>
    </div>
</header>

<!-- ========== CHAT LAYOUT ========== -->
<div class="flex flex-1 overflow-hidden">
    
    <!-- ========== CHAT LIST PANEL ========== -->
    <div class="flex w-96 flex-col border-r border-slate-800 bg-[#111722] overflow-hidden">
        <div class="p-4">
            <div class="flex items-center justify-between mb-3">
                <label class="flex w-full flex-col">
                    <div class="flex h-12 w-full flex-1 items-stretch rounded-lg">
                        <div class="text-slate-400 flex items-center justify-center rounded-l-lg border border-r-0 border-slate-700 bg-slate-800 pl-4">
                            <span class="material-symbols-outlined text-2xl">search</span>
                        </div>
                        <input id="searchChat" class="form-input h-full w-full min-w-0 flex-1 resize-none overflow-hidden rounded-r-lg border border-l-0 border-slate-700 bg-slate-800 px-4 text-base text-white placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Buscar chats..."/>
                    </div>
                </label>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto scroll-smooth" id="chatList">
<?php 
$usuarios_lista->data_seek(0);
while($usuario = $usuarios_lista->fetch_assoc()): 
    // Obtener último mensaje
    $stmt_ultimo = $conexion->prepare("SELECT Mensaje, Fecha_Mensaje, Tipo_Mensaje FROM mensajes_chat WHERE (Id_Emisor = ? AND Id_Receptor = ?) OR (Id_Emisor = ? AND Id_Receptor = ?) ORDER BY Id DESC LIMIT 1");
    $stmt_ultimo->bind_param("iiii", $Usuario_Id, $usuario['Id'], $usuario['Id'], $Usuario_Id);
    $stmt_ultimo->execute();
    $result_ultimo = $stmt_ultimo->get_result();
    $ultimo_mensaje = $result_ultimo->fetch_assoc();
    $stmt_ultimo->close();
    
    // Contar mensajes no leídos
    $stmt_no_leidos = $conexion->prepare("SELECT COUNT(*) as total FROM mensajes_chat WHERE Id_Emisor = ? AND Id_Receptor = ? AND leido = 0");
    $stmt_no_leidos->bind_param("ii", $usuario['Id'], $Usuario_Id);
    $stmt_no_leidos->execute();
    $result_no_leidos = $stmt_no_leidos->get_result();
    $no_leidos = $result_no_leidos->fetch_assoc()['total'];
    $stmt_no_leidos->close();
    
    $activo = ($chat_con == $usuario['Id']) ? 'bg-slate-800 border-l-2 border-primary' : 'hover:bg-slate-800/50';
?>
<a href="?chat=<?php echo $usuario['Id']; ?>" class="flex cursor-pointer justify-between gap-4 <?php echo $activo; ?> px-4 py-3 chat-item transition-all" data-nombre="<?php echo strtolower($usuario['Nombre'].' '.$usuario['Apellido']); ?>" data-user-id="<?php echo $usuario['Id']; ?>">
                        <div class="flex gap-3 items-center flex-1 min-w-0">
                            <div class="relative flex-shrink-0">
                                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-12 ring-2 ring-slate-700" style="background-image: url('<?php echo $usuario['Perfil']; ?>');"></div>
                                <!-- Status dot indicator -->
                                <span class="status-dot absolute bottom-0 right-0 w-3 h-3 bg-slate-600 border-2 border-slate-900 rounded-full"></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="font-medium text-white truncate"><?php echo $usuario['Nombre'].' '.$usuario['Apellido']; ?></h3>
                                    <?php if ($ultimo_mensaje): ?>
                                        <span class="text-xs text-slate-400 flex-shrink-0"><?php echo date('g:i A', strtotime($ultimo_mensaje['Fecha_Mensaje'])); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center justify-between gap-2 mt-1">
                                    <p class="text-sm text-slate-400 truncate">
                                        <?php 
                                        if ($ultimo_mensaje) {
                                            echo $ultimo_mensaje['Tipo_Mensaje'] === 'audio' ? '🎤 Audio' : 
                                                 ($ultimo_mensaje['Tipo_Mensaje'] === 'image' ? '📷 Imagen' : 
                                                 ($ultimo_mensaje['Tipo_Mensaje'] === 'video' ? '🎥 Video' : 
                                                 ($ultimo_mensaje['Tipo_Mensaje'] === 'document' ? '📄 Documento' : 
                                                 htmlspecialchars(substr($ultimo_mensaje['Mensaje'], 0, 30)))));
                                        } else {
                                            echo 'Sin mensajes';
                                        }
                                        ?>
                                    </p>
                                    <?php if ($no_leidos > 0): ?>
                                        <span class="flex-shrink-0 bg-primary text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $no_leidos; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </a>
<?php endwhile; ?>
        </div>
    </div>

    <!-- ========== MAIN CONVERSATION PANEL ========== -->
    <?php if ($chat_con > 0 && isset($chat_usuario)): 
        $estado_chat = calcularEstado($chat_usuario['Ultima_Actividad']);
    ?>
    <div class="flex flex-1 flex-col bg-slate-900">
        <!-- Chat Header -->
        <div class="flex items-center justify-between border-b border-slate-800 bg-[#111722] p-4">
            <div class="flex items-center gap-3">
                <div class="relative">
                    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-12 ring-2 ring-slate-700" style="background-image: url('<?php echo $chat_usuario['Perfil']; ?>');"></div>
                    <!-- Status dot in header -->
                    <span id="headerStatusDot" class="absolute bottom-0 right-0 w-3 h-3 <?php echo $estado_chat['clase']; ?> border-2 border-slate-900 rounded-full"></span>
                </div>
                <div>
                    <h3 class="font-semibold text-white"><?php echo $chat_usuario['Nombre'].' '.$chat_usuario['Apellido']; ?></h3>
                    <p class="text-sm text-slate-400" id="userStatusText"><?php echo $estado_chat['texto']; ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button id="searchInChatBtn" class="p-2 rounded-lg hover:bg-slate-800 text-slate-400 transition-colors">
                    <span class="material-symbols-outlined">search</span>
                </button>
                <button id="moreOptionsBtn" class="p-2 rounded-lg hover:bg-slate-800 text-slate-400 transition-colors">
                    <span class="material-symbols-outlined">more_vert</span>
                </button>
            </div>
        </div>

        <!-- Message History -->
        <div class="flex-1 space-y-6 overflow-y-auto p-6 scroll-smooth" id="messageContainer" style="background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiMyMzJmNDgiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDE4YzAtOS45NC04LjA2LTE4LTE4LTE4SDBoMzZWMGMwIDkuOTQtOC4wNiAxOC0xOCAxOHoiLz48L2c+PC9nPjwvc3ZnPg==');">
<?php foreach($mensajes as $mensaje): 
    $tipoMensaje = $mensaje['Tipo_Mensaje'] ?? 'text';
    $archivoUrl = $mensaje['Archivo_URL'] ?? null;
?>
<?php if ($mensaje['Id_Emisor'] == $Usuario_Id): ?>
<!-- Sent Message -->
<div class="flex flex-row-reverse items-end gap-3 message-container animate-fadeIn" data-message-id="<?php echo $mensaje['Id']; ?>">
    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8 ring-2 ring-primary/20" style="background-image: url('<?php echo $Perfil; ?>');"></div>
    <div class="max-w-md space-y-1">
        <div class="rounded-2xl rounded-br-md chat-bubble p-3 flex flex-col">
            <?php if ($tipoMensaje === 'image' && $archivoUrl): ?>
                <a href="<?php echo htmlspecialchars($archivoUrl); ?>" target="_blank" class="block">
                    <img src="<?php echo htmlspecialchars($archivoUrl); ?>" alt="<?php echo htmlspecialchars($mensaje['Mensaje']); ?>" class="max-w-xs rounded-lg cursor-pointer hover:opacity-90 transition-opacity" loading="lazy">
                </a>
                <p class="text-xs mt-2 text-white/70"><?php echo htmlspecialchars($mensaje['Mensaje']); ?></p>
            <?php elseif ($tipoMensaje === 'video' && $archivoUrl): ?>
                <video controls class="max-w-xs rounded-lg">
                    <source src="<?php echo htmlspecialchars($archivoUrl); ?>" type="video/mp4">
                    Tu navegador no soporta el elemento de video.
                </video>
                <p class="text-xs mt-2 text-white/70"><?php echo htmlspecialchars($mensaje['Mensaje']); ?></p>
            <?php elseif ($tipoMensaje === 'audio' && $archivoUrl): ?>
                <div class="flex items-center gap-2 bg-black/20 rounded-lg p-2">
                    <span class="material-symbols-outlined text-2xl">mic</span>
                    <audio controls class="flex-1" style="max-width: 250px;">
                        <source src="<?php echo htmlspecialchars($archivoUrl); ?>" type="audio/webm">
                        <source src="<?php echo htmlspecialchars($archivoUrl); ?>" type="audio/mpeg">
                        Tu navegador no soporta el elemento de audio.
                    </audio>
                </div>
            <?php elseif ($tipoMensaje === 'document' && $archivoUrl): ?>
                <a href="<?php echo htmlspecialchars($archivoUrl); ?>" target="_blank" class="flex items-center gap-2 bg-black/20 rounded-lg p-3 hover:bg-black/30 transition-colors">
                    <span class="material-symbols-outlined text-3xl">description</span>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($mensaje['Mensaje']); ?></p>
                        <p class="text-xs text-white/70">Documento PDF</p>
                    </div>
                    <span class="material-symbols-outlined">download</span>
                </a>
            <?php elseif ($tipoMensaje === 'file' && $archivoUrl): ?>
                <a href="<?php echo htmlspecialchars($archivoUrl); ?>" target="_blank" class="flex items-center gap-2 bg-black/20 rounded-lg p-3 hover:bg-black/30 transition-colors">
                    <span class="material-symbols-outlined text-3xl">attach_file</span>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($mensaje['Mensaje']); ?></p>
                        <p class="text-xs text-white/70">Archivo adjunto</p>
                    </div>
                    <span class="material-symbols-outlined">download</span>
                </a>
            <?php else: ?>
                <p class="text-sm text-white whitespace-pre-wrap break-words"><?php echo htmlspecialchars($mensaje['Mensaje']); ?></p>
            <?php endif; ?>
            <div class="flex items-center justify-end gap-1 mt-2">
                <span class="text-xs text-white/70"><?php echo date('g:i A', strtotime($mensaje['Fecha_Mensaje'])); ?></span>
                <?php 
                    $statusIcon = 'done';
                    $statusColor = 'text-white/70';
                    
                    if ($mensaje['leido'] == 1) {
                        $statusIcon = 'done_all';
                        $statusColor = 'text-blue-300';
                    } elseif ($mensaje['Estado_Entrega'] == 'delivered') {
                        $statusIcon = 'done_all';
                    }
                ?>
                <span class="material-symbols-outlined text-xs <?php echo $statusColor; ?> status-icon" data-status="<?php echo $mensaje['leido'] ? 'read' : $mensaje['Estado_Entrega']; ?>"><?php echo $statusIcon; ?></span>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Received Message -->
<div class="flex items-end gap-3 message-container animate-fadeIn" data-message-id="<?php echo $mensaje['Id']; ?>">
    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8 ring-2 ring-slate-700" style="background-image: url('<?php echo $mensaje['Emisor_Perfil']; ?>');"></div>
    <div class="max-w-md space-y-1">
        <div class="rounded-2xl rounded-bl-md bg-slate-800 p-3 flex flex-col shadow-lg">
            <?php if ($tipoMensaje === 'image' && $archivoUrl): ?>
                <a href="<?php echo htmlspecialchars($archivoUrl); ?>" target="_blank" class="block">
                    <img src="<?php echo htmlspecialchars($archivoUrl); ?>" alt="<?php echo htmlspecialchars($mensaje['Mensaje']); ?>" class="max-w-xs rounded-lg cursor-pointer hover:opacity-90 transition-opacity" loading="lazy">
                </a>
                <p class="text-xs mt-2 text-slate-400"><?php echo htmlspecialchars($mensaje['Mensaje']); ?></p>
            <?php elseif ($tipoMensaje === 'video' && $archivoUrl): ?>
                <video controls class="max-w-xs rounded-lg">
                    <source src="<?php echo htmlspecialchars($archivoUrl); ?>" type="video/mp4">
                    Tu navegador no soporta el elemento de video.
                </video>
                <p class="text-xs mt-2 text-slate-400"><?php echo htmlspecialchars($mensaje['Mensaje']); ?></p>
            <?php elseif ($tipoMensaje === 'audio' && $archivoUrl): ?>
                <div class="flex items-center gap-2 bg-black/20 rounded-lg p-2">
                    <span class="material-symbols-outlined text-2xl">mic</span>
                    <audio controls class="flex-1" style="max-width: 250px;">
                        <source src="<?php echo htmlspecialchars($archivoUrl); ?>" type="audio/webm">
                        <source src="<?php echo htmlspecialchars($archivoUrl); ?>" type="audio/mpeg">
                        Tu navegador no soporta el elemento de audio.
                    </audio>
                </div>
            <?php elseif ($tipoMensaje === 'document' && $archivoUrl): ?>
                <a href="<?php echo htmlspecialchars($archivoUrl); ?>" target="_blank" class="flex items-center gap-2 bg-black/20 rounded-lg p-3 hover:bg-black/30 transition-colors">
                    <span class="material-symbols-outlined text-3xl">description</span>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-200"><?php echo htmlspecialchars($mensaje['Mensaje']); ?></p>
                        <p class="text-xs text-slate-400">Documento PDF</p>
                    </div>
                    <span class="material-symbols-outlined">download</span>
                </a>
            <?php elseif ($tipoMensaje === 'file' && $archivoUrl): ?>
                <a href="<?php echo htmlspecialchars($archivoUrl); ?>" target="_blank" class="flex items-center gap-2 bg-black/20 rounded-lg p-3 hover:bg-black/30 transition-colors">
                    <span class="material-symbols-outlined text-3xl">attach_file</span>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-200"><?php echo htmlspecialchars($mensaje['Mensaje']); ?></p>
                        <p class="text-xs text-slate-400">Archivo adjunto</p>
                    </div>
                    <span class="material-symbols-outlined">download</span>
                </a>
            <?php else: ?>
                <p class="text-sm text-slate-200 whitespace-pre-wrap break-words"><?php echo htmlspecialchars($mensaje['Mensaje']); ?></p>
            <?php endif; ?>
            <span class="text-xs text-slate-400 mt-2"><?php echo date('g:i A', strtotime($mensaje['Fecha_Mensaje'])); ?></span>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<!-- Typing Indicator -->
<div id="typingIndicator" class="hidden flex items-end gap-3 animate-fadeIn">
    <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8 ring-2 ring-slate-700" style="background-image: url('<?php echo $chat_usuario['Perfil']; ?>');"></div>
    <div class="bg-slate-800 rounded-2xl rounded-bl-md px-4 py-3 shadow-lg">
        <div class="typing-indicator">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
</div>
        </div>

        <!-- Message Input -->
        <div class="flex-shrink-0 border-t border-slate-800 p-4 bg-[#111722]">
            <form id="formEnviarMensaje" class="flex items-end gap-2">
                <button type="button" id="emojiBtn" class="p-2 rounded-lg hover:bg-slate-800 text-slate-400 transition-colors relative">
                    <span class="material-symbols-outlined">emoji_emotions</span>
                    <!-- Emoji Picker -->
                    <div id="emojiPicker" class="hidden absolute bottom-12 left-0 bg-slate-800 border border-slate-700 rounded-xl shadow-2xl p-3 w-80 grid grid-cols-8 gap-2 z-50 max-h-64 overflow-y-auto">
                        <!-- Emojis will be injected via JS -->
                    </div>
                </button>
                
                <div class="flex-1 flex items-end gap-2 rounded-xl border border-slate-700 bg-slate-800 p-2 focus-within:ring-2 focus-within:ring-primary/50 transition-all">
                    <textarea id="mensajeInput" class="form-textarea w-full resize-none border-0 bg-transparent p-2 text-sm text-white placeholder-slate-400 focus:ring-0 max-h-32" placeholder="Escribe un mensaje..." rows="1" required></textarea>
                    <button type="submit" class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg chat-bubble text-white hover:opacity-90 transition-all hover:scale-105">
                        <span class="material-symbols-outlined text-2xl">send</span>
                    </button>
                </div>
                
                <button type="button" id="attachBtn" class="p-2 rounded-lg hover:bg-slate-800 text-slate-400 transition-colors">
                    <span class="material-symbols-outlined">attach_file</span>
                </button>
                <button type="button" id="voiceBtn" class="p-2 rounded-lg hover:bg-slate-800 text-slate-400 transition-colors relative">
                    <span class="material-symbols-outlined" id="voiceIcon">mic</span>
                    <span id="recordingTimer" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full font-mono animate-pulse">0:00</span>
                </button>
            </form>
        </div>
    </div>
    <?php else: ?>
    <!-- Empty State -->
    <div class="flex flex-1 items-center justify-center bg-slate-900">
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-slate-800 mb-4">
                <span class="material-symbols-outlined text-6xl text-slate-600">chat</span>
            </div>
            <p class="text-xl font-semibold text-white mb-2">Bienvenido al Chat</p>
            <p class="text-slate-400">Selecciona un chat para comenzar a conversar</p>
        </div>
    </div>
    <?php endif; ?>
</div>
</main>
</div>

<!-- ========== MODALS ========== -->

<!-- Settings Modal -->
<div id="settingsModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-slate-800 rounded-2xl p-6 max-w-md w-full mx-4 shadow-2xl border border-slate-700 animate-fadeIn">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-semibold text-white">Configuración</h3>
            <button onclick="document.getElementById('settingsModal').classList.add('hidden')" class="text-slate-400 hover:text-white transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <div class="space-y-6">
            <!-- Notifications -->
            <div>
                <h4 class="text-sm font-medium text-slate-400 mb-3 uppercase tracking-wider">Notificaciones</h4>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg">
                        <div>
                            <p class="text-white font-medium">Sonidos</p>
                            <p class="text-sm text-slate-400">Reproducir al enviar/recibir</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="soundToggle" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-slate-900 rounded-lg">
                        <div>
                            <p class="text-white font-medium">Notificaciones de escritorio</p>
                            <p class="text-sm text-slate-400">Mostrar alertas emergentes</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="desktopNotifToggle" class="sr-only peer" <?php echo $notificaciones_escritorio ? 'checked' : ''; ?>>
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Appearance -->
            <div>
                <h4 class="text-sm font-medium text-slate-400 mb-3 uppercase tracking-wider">Apariencia</h4>
                <div class="grid grid-cols-3 gap-3">
                    <button onclick="changeTheme('primary')" class="h-12 rounded-lg bg-blue-500 hover:opacity-90 transition-all ring-2 ring-offset-2 ring-offset-slate-800 ring-transparent hover:ring-blue-500 <?php echo $tema_color == 'primary' ? 'ring-blue-500' : ''; ?>"></button>
                    <button onclick="changeTheme('success')" class="h-12 rounded-lg bg-emerald-500 hover:opacity-90 transition-all ring-2 ring-offset-2 ring-offset-slate-800 ring-transparent hover:ring-emerald-500 <?php echo $tema_color == 'success' ? 'ring-emerald-500' : ''; ?>"></button>
                    <button onclick="changeTheme('warning')" class="h-12 rounded-lg bg-amber-500 hover:opacity-90 transition-all ring-2 ring-offset-2 ring-offset-slate-800 ring-transparent hover:ring-amber-500 <?php echo $tema_color == 'warning' ? 'ring-amber-500' : ''; ?>"></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Context Menu -->
<div id="contextMenu" class="hidden fixed z-50 bg-slate-800 rounded-lg shadow-2xl border border-slate-700 w-48 py-1 overflow-hidden">
    <button class="w-full text-left px-4 py-2 text-sm text-slate-200 hover:bg-slate-700 flex items-center gap-2 transition-colors" data-action="copy">
        <span class="material-symbols-outlined text-lg">content_copy</span> Copiar
    </button>
    <button class="w-full text-left px-4 py-2 text-sm text-slate-200 hover:bg-slate-700 flex items-center gap-2 transition-colors" data-action="forward">
        <span class="material-symbols-outlined text-lg">forward</span> Reenviar
    </button>
    <div class="h-px bg-slate-700 my-1"></div>
    <button class="w-full text-left px-4 py-2 text-sm text-slate-200 hover:bg-slate-700 flex items-center gap-2 hidden transition-colors" id="ctxEditBtn" data-action="edit">
        <span class="material-symbols-outlined text-lg">edit</span> Editar
    </button>
    <button class="w-full text-left px-4 py-2 text-sm text-red-400 hover:bg-red-900/20 flex items-center gap-2 hidden transition-colors" id="ctxDeleteBtn" data-action="delete">
        <span class="material-symbols-outlined text-lg">delete</span> Eliminar
    </button>
</div>

<!-- File Input -->
<input type="file" id="fileInput" class="hidden" accept="image/*,video/*,audio/*,application/pdf,.doc,.docx">

<!-- Voice Recorder Modal -->
<div id="voiceRecorderModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm">
    <div class="bg-slate-800 rounded-2xl p-6 w-80 text-center border border-slate-700 animate-fadeIn">
        <h3 class="text-lg font-semibold mb-4 text-white">Grabando...</h3>
        <div class="flex justify-center mb-4 h-12 items-end gap-1">
            <div class="w-1 bg-red-500 animate-[wave_1s_ease-in-out_infinite]"></div>
            <div class="w-1 bg-red-500 animate-[wave_1.2s_ease-in-out_infinite]"></div>
            <div class="w-1 bg-red-500 animate-[wave_0.8s_ease-in-out_infinite]"></div>
            <div class="w-1 bg-red-500 animate-[wave_1.1s_ease-in-out_infinite]"></div>
            <div class="w-1 bg-red-500 animate-[wave_0.9s_ease-in-out_infinite]"></div>
        </div>
        <p id="recordingTime" class="text-2xl font-mono text-white mb-4">00:00</p>
        <div class="flex justify-center gap-4">
            <button id="cancelVoiceBtn" class="p-3 rounded-full bg-slate-700 text-slate-300 hover:bg-slate-600 transition-all">
                <span class="material-symbols-outlined">delete</span>
            </button>
            <button id="stopVoiceBtn" class="p-3 rounded-full bg-red-500 text-white hover:bg-red-600 transition-all">
                <span class="material-symbols-outlined">stop</span>
            </button>
        </div>
    </div>
</div>

<script>
// ========== GLOBAL VARIABLES ==========
const chatCon = <?php echo $chat_con; ?>;
const usuarioId = <?php echo $Usuario_Id; ?>;
const perfilUsuario = "<?php echo $Perfil; ?>";
const nombreUsuario = "<?php echo $Nombre_Completo; ?>";

console.log("🔧 Variables globales inicializadas:");
console.log("   chatCon:", chatCon);
console.log("   usuarioId:", usuarioId);
console.log("   nombreUsuario:", nombreUsuario);
console.log("   perfilUsuario:", perfilUsuario);

// App State
const AppState = {
    socket: null,
    reconnectInterval: null,
    reconnectAttempts: 0,
    maxReconnectAttempts: 5,
    isPageVisible: true,
    messageQueue: [],
    typingTimeout: null,
    lastTypingTime: 0,
    onlineUsers: new Set(),
    audioContext: null,
    settings: {
        sound: localStorage.getItem('chat_sound') !== 'false',
        desktop: localStorage.getItem('chat_desktop') === 'true'
    },
    activeMessageId: null,
    editingMessageId: null
};


// DOM Elements
const DOM = {
    connectionStatus: document.getElementById('connectionStatus'),
    messageContainer: document.getElementById('messageContainer'),
    messageInput: document.getElementById('mensajeInput'),
    emojiBtn: document.getElementById('emojiBtn'),
    emojiPicker: document.getElementById('emojiPicker'),
    typingIndicator: document.getElementById('typingIndicator'),
    userStatusText: document.getElementById('userStatusText'),
    form: document.getElementById('formEnviarMensaje'),
    chatList: document.getElementById('chatList'),
    searchChat: document.getElementById('searchChat')
};

// ========== WEBSOCKET CONNECTION ==========

function initWebSocket() {
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const serverUrl = "<?php echo $websocket_host; ?>";
    const fullUrl = `${protocol}//${serverUrl}`;
    
    console.log(`🔌 Intentando conectar WebSocket a: ${fullUrl}`);
    console.log(`   Protocolo de página: ${window.location.protocol}`);
    console.log(`   Host de página: ${window.location.host}`);
    
    updateConnectionStatus('connecting');
    
    try {
        // Verificar que WebSocket esté soportado
        if (typeof WebSocket === 'undefined') {
            console.error('❌ WebSocket no está soportado en este navegador');
            updateConnectionStatus('disconnected');
            return;
        }
        
        AppState.socket = new WebSocket(fullUrl);
        console.log(`✅ Objeto WebSocket creado, estado: ${AppState.socket.readyState}`);
        
        AppState.socket.onopen = handleSocketOpen;
        AppState.socket.onmessage = handleSocketMessage;
        AppState.socket.onclose = handleSocketClose;
        AppState.socket.onerror = handleSocketError;
        
    } catch (e) {
        console.error("❌ Error al crear WebSocket:", e);
        console.error("   Mensaje:", e.message);
        console.error("   Stack:", e.stack);
        handleSocketClose();
    }
}

function handleSocketOpen() {
    console.log("✅ WebSocket conectado exitosamente!");
    console.log(`   Estado: ${AppState.socket.readyState} (1 = OPEN)`);
    updateConnectionStatus('connected');
    AppState.reconnectAttempts = 0;
    
    // Identificarse
    const loginData = {
        type: 'login',
        userId: usuarioId,
        userName: nombreUsuario
    };
    console.log("📤 Enviando identificación:", loginData);
    sendSocketMessage(loginData);
    
    // Enviar cola de mensajes
    while (AppState.messageQueue.length > 0) {
        const queuedMsg = AppState.messageQueue.shift();
        console.log("📤 Enviando mensaje de cola:", queuedMsg);
        sendSocketMessage(queuedMsg);
    }
    
    if (AppState.reconnectInterval) {
        clearInterval(AppState.reconnectInterval);
        AppState.reconnectInterval = null;
    }
    
    playSound('connected');
}

function handleSocketClose() {
    console.log("🔴 WebSocket cerrado");
    updateConnectionStatus('disconnected');
    
    if (!AppState.reconnectInterval && AppState.reconnectAttempts < AppState.maxReconnectAttempts) {
        AppState.reconnectAttempts++;
        const delay = Math.min(1000 * Math.pow(2, AppState.reconnectAttempts), 30000);
        console.log(`Reintentando en ${delay/1000}s... (Intento ${AppState.reconnectAttempts})`);
        AppState.reconnectInterval = setTimeout(initWebSocket, delay);
    }
}

function handleSocketError(error) {
    console.error("❌ WebSocket error:", error);
}

function sendSocketMessage(data) {
    if (AppState.socket && AppState.socket.readyState === WebSocket.OPEN) {
        AppState.socket.send(JSON.stringify(data));
        return true;
    } else {
        if (data.type === 'new_message') {
            AppState.messageQueue.push(data);
        }
        return false;
    }
}

function updateConnectionStatus(status) {
    const el = DOM.connectionStatus;
    const dot = el.querySelector('span:first-child');
    const text = el.querySelector('span:last-child');
    
    el.className = 'flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium transition-all duration-300';
    dot.className = 'w-2 h-2 rounded-full transition-all duration-300';
    
    switch(status) {
        case 'connected':
            el.classList.add('bg-green-900/30', 'text-green-400');
            dot.classList.add('bg-green-500', 'animate-pulse');
            text.textContent = 'Conectado';
            break;
        case 'connecting':
            el.classList.add('bg-yellow-900/30', 'text-yellow-400');
            dot.classList.add('bg-yellow-500', 'animate-pulse');
            text.textContent = 'Conectando...';
            break;
        case 'disconnected':
            el.classList.add('bg-red-900/30', 'text-red-400');
            dot.classList.add('bg-red-500');
            text.textContent = 'Desconectado';
            break;
    }
}

// ========== MESSAGE HANDLING ==========

function handleSocketMessage(event) {
    try {
        const data = JSON.parse(event.data);
        console.log("📩 Recibido:", data);
        
        switch(data.type) {
            case 'new_message':
                handleNewMessage(data);
                break;
            case 'message_delivered':
                updateMessageStatus(data.messageId, 'delivered');
                break;
            case 'message_read':
                updateMessageStatus(data.messageId, 'read');
                break;
            case 'typing':
                handleTyping(data);
                break;
            case 'user_status':
                handleUserStatus(data);
                break;
            case 'message_deleted':
                removeMessageFromUI(data.messageId);
                break;
            case 'message_edited':
                updateMessageContent(data.messageId, data.newMessage);
                break;
        }
    } catch (e) {
        console.error("Error procesando mensaje:", e);
    }
}

function handleNewMessage(data) {
    if ((data.id_emisor == chatCon || data.id_emisor == usuarioId) && 
        (data.id_receptor == chatCon || data.id_receptor == usuarioId)) {
        
        if (!document.querySelector(`[data-message-id="${data.id}"]`)) {
            addMessageToUI(data);
            
            if (data.id_receptor == usuarioId && AppState.isPageVisible) {
                markMessageAsRead(data.id, data.id_emisor);
                playSound('received');
            }
            
            if (!AppState.isPageVisible && data.id_receptor == usuarioId) {
                showNotification(
                    data.emisor_nombre || 'Nuevo mensaje',
                    data.mensaje,
                    data.emisor_perfil
                );
            }
        }
    }
}

function sendMessage(text) {
    if (!text.trim()) return;
    
    // Check if editing
    if (AppState.editingMessageId) {
        fetch('edit_message.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                messageId: AppState.editingMessageId,
                userId: usuarioId,
                newMessage: text
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateMessageContent(AppState.editingMessageId, text);
                sendSocketMessage({
                    type: 'message_edited',
                    messageId: AppState.editingMessageId,
                    newMessage: text,
                    senderId: usuarioId,
                    receiverId: chatCon
                });
                cancelEditing();
            }
        });
        return;
    }
    
    // Normal send
    fetch('send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            senderId: usuarioId,
            receiverId: chatCon,
            message: text,
            type: 'text'
        })
    })
    .then(res => {
        console.log("📥 Respuesta de send_message.php:", res);
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        console.log("✅ Datos recibidos:", data);
        if (data.success) {
            const wsData = {
                type: 'new_message',
                id: data.messageId,
                id_emisor: usuarioId,
                id_receptor: chatCon,
                mensaje: text,
                fecha: data.timestamp,
                emisor_nombre: nombreUsuario,
                emisor_perfil: perfilUsuario
            };
            
            console.log("📤 Enviando por WebSocket:", wsData);
            sendSocketMessage(wsData);
            addMessageToUI(wsData, true);
            playSound('sent');
            
            DOM.messageInput.value = '';
            DOM.messageInput.style.height = 'auto';
        } else {
            console.error("❌ Error del servidor:", data.message || 'Error desconocido');
            showToast('Error al enviar mensaje: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(err => {
        console.error("❌ Error al enviar mensaje:", err);
        showToast('Error de conexión al enviar mensaje');
    });
}

function addMessageToUI(msg, isOwn = false) {
    const container = DOM.messageContainer;
    const wrapper = document.createElement("div");
    
    const isMine = isOwn || msg.id_emisor == usuarioId;
    
    wrapper.className = isMine
        ? "flex flex-row-reverse items-end gap-3 message-container opacity-0 translate-y-2 transition-all duration-300"
        : "flex items-end gap-3 message-container opacity-0 translate-y-2 transition-all duration-300";
        
    wrapper.setAttribute("data-message-id", msg.id);

    const profile = isMine ? perfilUsuario : msg.emisor_perfil;
    const bubbleClass = isMine 
        ? 'rounded-2xl rounded-br-md chat-bubble text-white shadow-lg' 
        : 'rounded-2xl rounded-bl-md bg-slate-800 text-slate-200 shadow-lg';
    const timeClass = isMine ? 'text-white/70' : 'text-slate-400';
    
    const statusHtml = isMine 
        ? `<span class="material-symbols-outlined text-xs text-white/70 status-icon" data-status="sent">done</span>` 
        : '';

    // Renderizar contenido según el tipo de mensaje
    let contentHtml = '';
    const messageType = msg.tipo_mensaje || 'text';
    
    if (messageType === 'image' && msg.archivo_url) {
        contentHtml = `
            <a href="${msg.archivo_url}" target="_blank" class="block">
                <img src="${msg.archivo_url}" alt="${escapeHtml(msg.mensaje)}" class="max-w-xs rounded-lg cursor-pointer hover:opacity-90 transition-opacity" loading="lazy">
            </a>
            <p class="text-xs mt-2 opacity-75">${escapeHtml(msg.mensaje)}</p>
        `;
    } else if (messageType === 'video' && msg.archivo_url) {
        contentHtml = `
            <video controls class="max-w-xs rounded-lg">
                <source src="${msg.archivo_url}" type="video/mp4">
                Tu navegador no soporta el elemento de video.
            </video>
            <p class="text-xs mt-2 opacity-75">${escapeHtml(msg.mensaje)}</p>
        `;
    } else if (messageType === 'audio' && msg.archivo_url) {
        contentHtml = `
            <div class="flex items-center gap-2 bg-black/20 rounded-lg p-2">
                <span class="material-symbols-outlined text-2xl">mic</span>
                <audio controls class="flex-1" style="max-width: 250px;">
                    <source src="${msg.archivo_url}" type="audio/webm">
                    <source src="${msg.archivo_url}" type="audio/mpeg">
                    Tu navegador no soporta el elemento de audio.
                </audio>
            </div>
        `;
    } else if (messageType === 'document' && msg.archivo_url) {
        contentHtml = `
            <a href="${msg.archivo_url}" target="_blank" class="flex items-center gap-2 bg-black/20 rounded-lg p-3 hover:bg-black/30 transition-colors">
                <span class="material-symbols-outlined text-3xl">description</span>
                <div class="flex-1">
                    <p class="text-sm font-medium">${escapeHtml(msg.mensaje)}</p>
                    <p class="text-xs opacity-75">Documento PDF</p>
                </div>
                <span class="material-symbols-outlined">download</span>
            </a>
        `;
    } else if (messageType === 'file' && msg.archivo_url) {
        contentHtml = `
            <a href="${msg.archivo_url}" target="_blank" class="flex items-center gap-2 bg-black/20 rounded-lg p-3 hover:bg-black/30 transition-colors">
                <span class="material-symbols-outlined text-3xl">attach_file</span>
                <div class="flex-1">
                    <p class="text-sm font-medium">${escapeHtml(msg.mensaje)}</p>
                    <p class="text-xs opacity-75">Archivo adjunto</p>
                </div>
                <span class="material-symbols-outlined">download</span>
            </a>
        `;
    } else {
        // Mensaje de texto normal
        contentHtml = `<p class="text-sm whitespace-pre-wrap break-words">${escapeHtml(msg.mensaje)}</p>`;
    }

    wrapper.innerHTML = `
        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-8 ring-2 ${isMine ? 'ring-primary/20' : 'ring-slate-700'}" style="background-image: url('${profile}')"></div>
        <div class="max-w-md space-y-1">
            <div class="${bubbleClass} p-3 flex flex-col">
                ${contentHtml}
                <div class="flex items-center ${isMine ? 'justify-end' : ''} gap-1 mt-2">
                    <span class="text-xs ${timeClass}">${msg.fecha || 'Ahora'}</span>
                    ${statusHtml}
                </div>
            </div>
        </div>
    `;
    
    if (DOM.typingIndicator && DOM.typingIndicator.parentNode === container) {
        container.insertBefore(wrapper, DOM.typingIndicator);
    } else {
        container.appendChild(wrapper);
    }
    
    requestAnimationFrame(() => {
        wrapper.classList.remove('opacity-0', 'translate-y-2');
    });
    
    scrollToBottom();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function updateMessageStatus(msgId, status) {
    const msgEl = document.querySelector(`[data-message-id="${msgId}"] .status-icon`);
    if (!msgEl) return;
    
    if (status === 'read') {
        msgEl.textContent = 'done_all';
        msgEl.classList.remove('text-white/70');
        msgEl.classList.add('text-blue-300');
    } else if (status === 'delivered') {
        msgEl.textContent = 'done_all';
    }
    
    msgEl.dataset.status = status;
}

function removeMessageFromUI(msgId) {
    const el = document.querySelector(`[data-message-id="${msgId}"]`);
    if (el) {
        el.classList.add('opacity-0', 'scale-95');
        setTimeout(() => el.remove(), 300);
    }
}

function updateMessageContent(msgId, newContent) {
    const el = document.querySelector(`[data-message-id="${msgId}"] p`);
    if (el) {
        el.textContent = newContent;
        const timeEl = el.closest('.flex').querySelector('.text-xs');
        if (timeEl && !timeEl.textContent.includes('(editado)')) {
            timeEl.textContent += ' (editado)';
        }
    }
}

function markMessageAsRead(msgId, senderId) {
    sendSocketMessage({
        type: 'message_read',
        messageId: msgId,
        senderId: senderId,
        readerId: usuarioId
    });
}

function scrollToBottom() {
    if (DOM.messageContainer) {
        DOM.messageContainer.scrollTo({
            top: DOM.messageContainer.scrollHeight,
            behavior: 'smooth'
        });
    }
}

// ========== TYPING INDICATOR ==========

function handleTyping(data) {
    if (data.senderId == chatCon) {
        if (data.isTyping) {
            DOM.typingIndicator.classList.remove('hidden');
            scrollToBottom();
        } else {
            DOM.typingIndicator.classList.add('hidden');
        }
    }
}

// ========== USER STATUS ==========

function handleUserStatus(data) {
    console.log("👤 Estado de usuario actualizado:", data);
    
    if (data.onlineUsers) {
        AppState.onlineUsers = new Set(data.onlineUsers);
        console.log("📋 Usuarios en línea:", Array.from(AppState.onlineUsers));
        
        // Actualizar indicadores en la lista de contactos
        document.querySelectorAll('[data-user-id]').forEach(item => {
            const userId = parseInt(item.dataset.userId);
            const statusDot = item.querySelector('.status-dot');
            if (statusDot) {
                if (AppState.onlineUsers.has(userId)) {
                    // Usuario en línea - punto verde
                    statusDot.classList.remove('bg-slate-600', 'bg-orange-500');
                    statusDot.classList.add('bg-green-500');
                } else {
                    // Usuario desconectado - punto gris
                    statusDot.classList.remove('bg-green-500', 'bg-orange-500');
                    statusDot.classList.add('bg-slate-600');
                }
            }
        });
    }
    
    // Actualizar estado del usuario actual del chat
    if (data.userId == chatCon && DOM.userStatusText) {
        updateChatUserStatus();
    }
}

// Nueva función para actualizar el estado del usuario del chat
function updateChatUserStatus() {
    if (!DOM.userStatusText) return;
    
    const isOnline = AppState.onlineUsers.has(chatCon);
    const headerDot = document.getElementById('headerStatusDot');
    
    if (isOnline) {
        DOM.userStatusText.textContent = 'En línea';
        DOM.userStatusText.classList.remove('text-slate-400');
        DOM.userStatusText.classList.add('text-green-400');
        
        // Cambiar punto del header a verde
        if (headerDot) {
            headerDot.classList.remove('bg-slate-600', 'bg-orange-500');
            headerDot.classList.add('bg-green-500');
        }
    } else {
        // Obtener última actividad del servidor
        fetch('get_last_activity.php?userId=' + chatCon)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.lastActivity) {
                    const lastActivityTime = new Date(data.lastActivity);
                    const now = new Date();
                    const diffMs = now - lastActivityTime;
                    const diffMins = Math.floor(diffMs / 60000);
                    
                    let statusText = '';
                    if (diffMins < 1) {
                        statusText = 'Hace un momento';
                    } else if (diffMins < 60) {
                        statusText = `Hace ${diffMins}min`;
                    } else if (diffMins < 1440) { // menos de 24 horas
                        const hours = Math.floor(diffMins / 60);
                        statusText = `Hace ${hours}h`;
                    } else {
                        const days = Math.floor(diffMins / 1440);
                        statusText = `Hace ${days}d`;
                    }
                    
                    DOM.userStatusText.textContent = statusText;
                } else {
                    DOM.userStatusText.textContent = 'Desconectado';
                }
                DOM.userStatusText.classList.remove('text-green-400');
                DOM.userStatusText.classList.add('text-slate-400');
                
                // Cambiar punto del header a gris
                if (headerDot) {
                    headerDot.classList.remove('bg-green-500', 'bg-orange-500');
                    headerDot.classList.add('bg-slate-600');
                }
            })
            .catch(err => {
                console.error("Error al obtener última actividad:", err);
                DOM.userStatusText.textContent = 'Desconectado';
                DOM.userStatusText.classList.remove('text-green-400');
                DOM.userStatusText.classList.add('text-slate-400');
                
                // Cambiar punto del header a gris
                if (headerDot) {
                    headerDot.classList.remove('bg-green-500', 'bg-orange-500');
                    headerDot.classList.add('bg-slate-600');
                }
            });
    }
}

// Actualizar el estado cada 30 segundos
setInterval(() => {
    updateChatUserStatus();
}, 30000);

function updateOnlineIndicators() {
    document.querySelectorAll('.chat-item').forEach(item => {
        const uid = parseInt(item.dataset.userId);
        const badge = item.querySelector('.status-indicator, span[class*="status-"]');
        
        if (badge && AppState.onlineUsers.has(uid)) {
            badge.className = badge.className.replace(/status-\w+/, 'status-online');
        }
    });
}

function startHeartbeat() {
    setInterval(() => {
        if (AppState.socket && AppState.socket.readyState === WebSocket.OPEN) {
            sendSocketMessage({ type: 'heartbeat', userId: usuarioId });
        }
        
        fetch('update_status.php')
            .then(res => res.json())
            .then(data => {
                if (data.success && data.online_users) {
                    AppState.onlineUsers = new Set(data.online_users);
                    updateOnlineIndicators();
                }
            });
    }, 30000);
}

// ========== SOUND & NOTIFICATIONS ==========

function initAudio() {
    if (!AppState.audioContext) {
        AppState.audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
}

function playSound(type) {
    if (!AppState.settings.sound) return;
    
    initAudio();
    const ctx = AppState.audioContext;
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    
    osc.connect(gain);
    gain.connect(ctx.destination);
    
    switch(type) {
        case 'sent':
            osc.type = 'sine';
            osc.frequency.setValueAtTime(600, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(300, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.1);
            osc.start();
            osc.stop(ctx.currentTime + 0.1);
            break;
        case 'received':
            osc.type = 'sine';
            osc.frequency.setValueAtTime(400, ctx.currentTime);
            osc.frequency.setValueAtTime(600, ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
            break;
        case 'connected':
            osc.type = 'sine';
            osc.frequency.setValueAtTime(800, ctx.currentTime);
            gain.gain.setValueAtTime(0.05, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.2);
            osc.start();
            osc.stop(ctx.currentTime + 0.2);
            break;
    }
}

function showNotification(title, body, icon) {
    if (!AppState.settings.desktop) return;
    if (Notification.permission !== "granted") return;
    
    new Notification(title, {
        body: body,
        icon: icon || perfilUsuario,
        badge: '/favicon.ico',
        tag: 'chat-message'
    });
}

// ========== CONTEXT MENU ==========

const contextMenu = document.getElementById('contextMenu');
const ctxEditBtn = document.getElementById('ctxEditBtn');
const ctxDeleteBtn = document.getElementById('ctxDeleteBtn');

document.addEventListener('click', () => {
    contextMenu.classList.add('hidden');
});

document.addEventListener('contextmenu', (e) => {
    const messageEl = e.target.closest('.message-container');
    if (messageEl) {
        e.preventDefault();
        const msgId = messageEl.dataset.messageId;
        const isMine = messageEl.classList.contains('flex-row-reverse');
        
        AppState.activeMessageId = msgId;
        
        if (isMine) {
            ctxEditBtn.classList.remove('hidden');
            ctxDeleteBtn.classList.remove('hidden');
        } else {
            ctxEditBtn.classList.add('hidden');
            ctxDeleteBtn.classList.add('hidden');
        }
        
        const x = Math.min(e.clientX, window.innerWidth - 200);
        const y = Math.min(e.clientY, window.innerHeight - 200);
        
        contextMenu.style.left = `${x}px`;
        contextMenu.style.top = `${y}px`;
        contextMenu.classList.remove('hidden');
    }
});

contextMenu.querySelectorAll('button').forEach(btn => {
    btn.addEventListener('click', () => {
        const action = btn.dataset.action;
        handleMessageAction(action, AppState.activeMessageId);
        contextMenu.classList.add('hidden');
    });
});

function handleMessageAction(action, msgId) {
    if (!msgId) return;
    
    switch(action) {
        case 'copy':
            const text = document.querySelector(`[data-message-id="${msgId}"] p`)?.textContent;
            if (text) {
                navigator.clipboard.writeText(text);
                showToast('Mensaje copiado');
            }
            break;
        case 'delete':
            if (confirm('¿Eliminar mensaje?')) {
                deleteMessage(msgId);
            }
            break;
        case 'edit':
            startEditingMessage(msgId);
            break;
    }
}

function deleteMessage(msgId) {
    fetch('delete_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ messageId: msgId, userId: usuarioId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            removeMessageFromUI(msgId);
            sendSocketMessage({
                type: 'message_deleted',
                messageId: msgId,
                senderId: usuarioId,
                receiverId: chatCon});
            showToast('Mensaje eliminado');
        }
    });
}

function startEditingMessage(msgId) {
    const msgEl = document.querySelector(`[data-message-id="${msgId}"]`);
    const text = msgEl.querySelector('p').textContent;
    
    DOM.messageInput.value = text;
    DOM.messageInput.focus();
    AppState.editingMessageId = msgId;
    
    DOM.messageInput.classList.add('ring-2', 'ring-yellow-500');
    const submitBtn = DOM.form.querySelector('button[type="submit"]');
    submitBtn.classList.add('bg-yellow-500');
    submitBtn.classList.remove('chat-bubble');
    
    showToast('Editando mensaje - ESC para cancelar');
}

function cancelEditing() {
    AppState.editingMessageId = null;
    DOM.messageInput.value = '';
    DOM.messageInput.classList.remove('ring-2', 'ring-yellow-500');
    
    const submitBtn = DOM.form.querySelector('button[type="submit"]');
    submitBtn.classList.remove('bg-yellow-500');
    submitBtn.classList.add('chat-bubble');
}

// ========== SEARCH FUNCTIONALITY ==========

const toggleSearchBtn = document.getElementById('toggleSearchBtn');
const messageSearchBar = document.getElementById('messageSearchBar');
const messageSearchInput = document.getElementById('messageSearchInput');
const closeSearchBtn = document.getElementById('closeSearchBtn');
const searchUpBtn = document.getElementById('searchUpBtn');
const searchDownBtn = document.getElementById('searchDownBtn');
const searchCount = document.getElementById('searchCount');

let searchMatches = [];
let currentMatchIndex = -1;

toggleSearchBtn?.addEventListener('click', () => {
    messageSearchBar.classList.remove('hidden');
    document.getElementById('headerTitle').classList.add('hidden');
    messageSearchInput.focus();
});

closeSearchBtn?.addEventListener('click', () => {
    messageSearchBar.classList.add('hidden');
    document.getElementById('headerTitle').classList.remove('hidden');
    messageSearchInput.value = '';
    clearSearchHighlights();
});

messageSearchInput?.addEventListener('input', (e) => {
    const query = e.target.value.trim().toLowerCase();
    clearSearchHighlights();
    
    if (query.length < 2) {
        searchCount.classList.add('hidden');
        return;
    }
    
    performSearch(query);
});

function performSearch(query) {
    const messages = document.querySelectorAll('.message-container p');
    searchMatches = [];
    currentMatchIndex = -1;
    
    messages.forEach(p => {
        const text = p.textContent.toLowerCase();
        if (text.includes(query)) {
            const regex = new RegExp(`(${escapeRegExp(query)})`, 'gi');
            const originalText = p.textContent;
            p.innerHTML = originalText.replace(regex, '<mark class="bg-yellow-300 dark:bg-yellow-600 text-black rounded px-1">$1</mark>');
            searchMatches.push(p);
        }
    });
    
    updateSearchControls();
    
    if (searchMatches.length > 0) {
        currentMatchIndex = 0;
        scrollToMatch(currentMatchIndex);
    }
}

function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function clearSearchHighlights() {
    document.querySelectorAll('mark').forEach(mark => {
        const parent = mark.parentNode;
        parent.textContent = parent.textContent;
    });
    searchMatches = [];
    updateSearchControls();
}

function updateSearchControls() {
    if (searchMatches.length > 0) {
        searchCount.textContent = `${currentMatchIndex + 1}/${searchMatches.length}`;
        searchCount.classList.remove('hidden');
        searchUpBtn.disabled = false;
        searchDownBtn.disabled = false;
    } else {
        searchCount.classList.add('hidden');
        searchUpBtn.disabled = true;
        searchDownBtn.disabled = true;
    }
}

function scrollToMatch(index) {
    if (index < 0 || index >= searchMatches.length) return;
    
    const match = searchMatches[index];
    match.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    searchCount.textContent = `${index + 1}/${searchMatches.length}`;
}

searchUpBtn?.addEventListener('click', () => {
    if (searchMatches.length === 0) return;
    currentMatchIndex--;
    if (currentMatchIndex < 0) currentMatchIndex = searchMatches.length - 1;
    scrollToMatch(currentMatchIndex);
});

searchDownBtn?.addEventListener('click', () => {
    if (searchMatches.length === 0) return;
    currentMatchIndex++;
    if (currentMatchIndex >= searchMatches.length) currentMatchIndex = 0;
    scrollToMatch(currentMatchIndex);
});

// ========== EMOJI PICKER ==========

const emojis = ['😀','😃','😄','😁','😆','😅','😂','🤣','😊','😇','🙂','🙃','😉','😌','😍','🥰','😘','😗','😙','😚','😋','😛','😝','😜','🤪','🤨','🧐','🤓','😎','🤩','🥳','😏','😒','😞','😔','😟','😕','🙁','☹️','😣','😖','😫','😩','🥺','😢','😭','😤','😠','😡','🤬','🤯','😳','🥵','🥶','😱','😨','😰','😥','😓','🤗','🤔','🤭','🤫','🤥','😶','😐','😑','😬','🙄','😯','😦','😧','😮','😲','🥱','😴','🤤','😪','😵','🤐','🥴','🤢','🤮','🤧','😷','🤒','🤕','🤑','🤠','👍','👎','👌','✌️','🤞','🤟','🤘','👏','🙌','👐','🤲','🙏','❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','🔥','✨','💫','⭐','🌟','⚡','💥','💢','💯','🎉','🎊','🎈','🎁','🏆','🥇','🥈','🥉','🏅'];

if (DOM.emojiBtn && DOM.emojiPicker) {
    DOM.emojiPicker.innerHTML = emojis.map(e => 
        `<button type="button" class="p-2 hover:bg-slate-700 rounded text-xl transition-colors">${e}</button>`
    ).join('');
    
    DOM.emojiBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        DOM.emojiPicker.classList.toggle('hidden');
    });
    
    DOM.emojiPicker.addEventListener('click', (e) => {
        e.stopPropagation();
        if (e.target.tagName === 'BUTTON') {
            const emoji = e.target.textContent;
            const cursorPos = DOM.messageInput.selectionStart;
            const textBefore = DOM.messageInput.value.substring(0, cursorPos);
            const textAfter = DOM.messageInput.value.substring(cursorPos);
            DOM.messageInput.value = textBefore + emoji + textAfter;
            DOM.messageInput.focus();
            DOM.messageInput.setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
        }
    });
    
    document.addEventListener('click', (e) => {
        if (!DOM.emojiBtn.contains(e.target) && !DOM.emojiPicker.contains(e.target)) {
            DOM.emojiPicker.classList.add('hidden');
        }
    });
}

// ========== CHAT LIST SEARCH ==========

if (DOM.searchChat) {
    DOM.searchChat.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        document.querySelectorAll('.chat-item').forEach(item => {
            const nombre = item.dataset.nombre;
            item.style.display = nombre.includes(query) ? 'flex' : 'none';
        });
    });
}

// ========== SETTINGS ==========

const soundToggle = document.getElementById('soundToggle');
const desktopNotifToggle = document.getElementById('desktopNotifToggle');

if (soundToggle) {
    soundToggle.addEventListener('change', (e) => {
        AppState.settings.sound = e.target.checked;
        localStorage.setItem('chat_sound', e.target.checked);
        if (e.target.checked) playSound('sent');
    });
}

if (desktopNotifToggle) {
    desktopNotifToggle.addEventListener('change', (e) => {
        if (e.target.checked && Notification.permission !== 'granted') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    AppState.settings.desktop = true;
                    localStorage.setItem('chat_desktop', true);
                } else {
                    e.target.checked = false;
                    showToast('Permiso de notificaciones denegado');
                }
            });
        } else {
            AppState.settings.desktop = e.target.checked;
            localStorage.setItem('chat_desktop', e.target.checked);
        }
    });
}

function changeTheme(theme) {
    const colors = {
        primary: '#3b82f6',
        success: '#10b981',
        warning: '#f59e0b'
    };
    
    document.documentElement.style.setProperty('--color-primary', colors[theme]);
    
    fetch('update_theme.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ theme: theme })
    })
    .then(() => {
        showToast('Tema actualizado');
        setTimeout(() => location.reload(), 500);
    });
}

// ========== TOAST NOTIFICATIONS ==========

function showToast(message, duration = 3000) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 bg-slate-800 text-white px-6 py-3 rounded-lg shadow-2xl border border-slate-700 z-50 animate-fadeIn';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ========== FILE UPLOAD ==========

const attachBtn = document.getElementById('attachBtn');
const fileInput = document.getElementById('fileInput');

attachBtn?.addEventListener('click', () => fileInput.click());

fileInput?.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        const file = e.target.files[0];
        if (file.size > 10 * 1024 * 1024) { // 10MB limit
            showToast('El archivo es demasiado grande (máx 10MB)');
            return;
        }
        uploadFile(file);
    }
});

function uploadFile(file) {
    console.log("📁 Iniciando subida de archivo:", file.name, "Tamaño:", file.size, "bytes");
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('senderId', usuarioId);
    formData.append('receiverId', chatCon);
    
    console.log("📤 FormData creado con:", {
        fileName: file.name,
        senderId: usuarioId,
        receiverId: chatCon
    });
    
    showToast('Subiendo archivo...');
    
    fetch('handle_file_upload.php', {
        method: 'POST',
        body: formData
    })
    .then(res => {
        console.log("📥 Respuesta del servidor:", res);
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        console.log("✅ Datos recibidos:", data);
        if (data.success) {
            const wsData = {
                type: 'new_message',
                id: data.messageId,
                id_emisor: usuarioId,
                id_receptor: chatCon,
                mensaje: data.fileName,
                fecha: data.timestamp,
                emisor_nombre: nombreUsuario,
                emisor_perfil: perfilUsuario,
                tipo_mensaje: data.messageType,
                archivo_url: data.fileUrl
            };
            
            console.log("📤 Enviando por WebSocket:", wsData);
            sendSocketMessage(wsData);
            addMessageToUI(wsData, true);
            playSound('sent');
            showToast('✅ Archivo enviado');
        } else {
            console.error("❌ Error del servidor:", data.message);
            showToast('❌ Error al subir archivo: ' + data.message);
        }
        fileInput.value = '';
    })
    .catch(err => {
        console.error('❌ Error al subir archivo:', err);
        showToast('❌ Error de conexión al subir archivo');
        fileInput.value = '';
    });
}

// ========== VOICE RECORDING ==========

const voiceBtn = document.getElementById('voiceBtn');
const voiceIcon = document.getElementById('voiceIcon');
const recordingTimer = document.getElementById('recordingTimer');
let mediaRecorder;
let audioChunks = [];
let recordingInterval;
let recordingStartTime;

voiceBtn?.addEventListener('click', async () => {
    // Si ya está grabando, detener
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        return;
    }
    
    try {
        // Verificar soporte de getUserMedia
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showToast("❌ Tu navegador no soporta grabación de audio");
            console.error("getUserMedia no está disponible");
            return;
        }
        
        // Verificar soporte de MediaRecorder
        if (typeof MediaRecorder === 'undefined') {
            showToast("❌ Tu navegador no soporta MediaRecorder");
            console.error("MediaRecorder no está disponible");
            return;
        }
        
        console.log("🎤 Solicitando acceso al micrófono...");
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        console.log("✅ Acceso al micrófono concedido");
        
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];
        recordingStartTime = Date.now();

        // Cambiar apariencia del botón a estado de grabación
        voiceBtn.classList.add('bg-red-500', 'text-white');
        voiceBtn.classList.remove('text-slate-400');
        voiceIcon.textContent = 'stop';
        recordingTimer.classList.remove('hidden');
        
        // Actualizar contador cada segundo
        recordingInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            recordingTimer.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
        
        showToast("🎤 Grabando audio... (Haz clic de nuevo para detener)");

        mediaRecorder.ondataavailable = e => {
            if (e.data.size > 0) audioChunks.push(e.data);
        };

        mediaRecorder.onstop = async () => {
            console.log("🛑 Grabación detenida, procesando audio...");
            
            // Restaurar apariencia normal del botón
            clearInterval(recordingInterval);
            voiceBtn.classList.remove('bg-red-500', 'text-white');
            voiceBtn.classList.add('text-slate-400');
            voiceIcon.textContent = 'mic';
            recordingTimer.classList.add('hidden');
            recordingTimer.textContent = '0:00';
            
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            console.log("📦 Audio blob creado, tamaño:", audioBlob.size, "bytes");
            
            const formData = new FormData();
            formData.append('file', audioBlob, 'voice_message.webm');
            formData.append('senderId', usuarioId);
            formData.append('receiverId', chatCon);

            showToast("📤 Subiendo audio...");

            try {
                const res = await fetch('handle_file_upload.php', {
                    method: 'POST',
                    body: formData
                });
                
                console.log("📥 Respuesta del servidor:", res);
                const data = await res.json();
                console.log("✅ Datos recibidos:", data);

                if (data.success) {
                    const wsData = {
                        type: 'new_message',
                        id: data.messageId,
                        id_emisor: usuarioId,
                        id_receptor: chatCon,
                        mensaje: "🎤 Mensaje de voz",
                        fecha: data.timestamp,
                        emisor_nombre: nombreUsuario,
                        emisor_perfil: perfilUsuario,
                        tipo_mensaje: "audio",
                        archivo_url: data.fileUrl
                    };
                    sendSocketMessage(wsData);
                    addMessageToUI(wsData, true);
                    playSound('sent');
                    showToast("✅ Audio enviado");
                } else {
                    console.error("❌ Error del servidor:", data.message);
                    showToast("❌ Error al subir audio: " + data.message);
                }
            } catch (err) {
                console.error("❌ Error al subir audio:", err);
                showToast("❌ Error de conexión al subir audio");
            }
            
            // Detener todos los tracks del stream
            stream.getTracks().forEach(track => track.stop());
        };

        mediaRecorder.start();
        console.log("🔴 Grabación iniciada");

        // Detener después de 30s automáticamente
        setTimeout(() => {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                showToast("⏱️ Grabación detenida (30s máximo)");
            }
        }, 30000);

    } catch (err) {
        console.error("❌ Error al acceder al micrófono:", err);
        console.error("   Nombre del error:", err.name);
        console.error("   Mensaje:", err.message);
        
        // Restaurar apariencia del botón en caso de error
        if (recordingInterval) clearInterval(recordingInterval);
        voiceBtn.classList.remove('bg-red-500', 'text-white');
        voiceBtn.classList.add('text-slate-400');
        voiceIcon.textContent = 'mic';
        recordingTimer.classList.add('hidden');
        recordingTimer.textContent = '0:00';
        
        if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            showToast("❌ Permiso denegado. Permite el acceso al micrófono en la configuración del navegador.");
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            showToast("❌ No se encontró ningún micrófono conectado.");
        } else if (err.name === 'NotSupportedError') {
            showToast("❌ Tu navegador no soporta grabación de audio en HTTP. Usa HTTPS.");
        } else {
            showToast("❌ No se pudo acceder al micrófono: " + err.message);
        }
    }
});

// ========== INIT ==========
document.addEventListener("DOMContentLoaded", () => {
    initWebSocket();
    startHeartbeat();
    
    // Actualizar estado del usuario del chat al cargar
    setTimeout(() => {
        updateChatUserStatus();
    }, 2000); // Esperar 2 segundos para que el WebSocket se conecte
    
    // Request notification permission
    if ("Notification" in window && Notification.permission === "default") {
        Notification.requestPermission();
    }
    
    // Page visibility
    document.addEventListener("visibilitychange", () => {
        AppState.isPageVisible = !document.hidden;
        if (AppState.isPageVisible && chatCon > 0) {
            const unreadMessages = document.querySelectorAll('[data-status="delivered"]');
            unreadMessages.forEach(msg => {
                const msgId = msg.closest('[data-message-id]')?.dataset.messageId;
                if (msgId) markMessageAsRead(msgId, chatCon);
            });
        }
    });
    
    // Cancel edit on ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (AppState.editingMessageId) {
                cancelEditing();
            }
            if (!messageSearchBar.classList.contains('hidden')) {
                closeSearchBtn.click();
            }
        }
    });
    
    // Form submit
    if (DOM.form) {
        DOM.form.addEventListener('submit', (e) => {
            e.preventDefault();
            const text = DOM.messageInput.value.trim();
            if (text) {
                sendMessage(text);
            }
        });
    }
    
    // Auto-resize textarea
    if (DOM.messageInput) {
        DOM.messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            
            // Typing indicator
            const now = Date.now();
            if (now - AppState.lastTypingTime > 2000) {
                sendSocketMessage({
                    type: 'typing',
                    senderId: usuarioId,
                    receiverId: chatCon,
                    isTyping: true
                });
                AppState.lastTypingTime = now;
            }
            
            clearTimeout(AppState.typingTimeout);
            AppState.typingTimeout = setTimeout(() => {
                sendSocketMessage({
                    type: 'typing',
                    senderId: usuarioId,
                    receiverId: chatCon,
                    isTyping: false
                });
            }, 3000);
        });
        
        DOM.messageInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                DOM.form.dispatchEvent(new Event('submit'));
            }
        });
    }
    
    // Scroll to bottom on load
    scrollToBottom();
    
    console.log("✅ Chat inicializado correctamente");
});

// ========== CLEANUP ==========

window.addEventListener('beforeunload', () => {
    if (AppState.socket) {
        AppState.socket.close();
    }
});
</script>

</body>
</html>