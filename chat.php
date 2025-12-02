<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

$rol_usuario = strtolower($Rol);
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Chat - Rey System APP</title>
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
<link rel="stylesheet" href="chat_premium.css">
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
    }
</style>
<script src="nova_rey.js"></script>
<script src="chat_foro_shared.js"></script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-screen w-full">
<?php include 'menu_lateral.php'; ?>

<!-- Chat Container Full Screen - Después del menú lateral -->
<div class="chat-full-container flex-1 chat-bg-gradient flex">
    
    <!-- Users Sidebar - Izquierda -->
    <div class="users-sidebar">
        <!-- Sidebar Header -->
        <div class="p-6 border-b border-gray-200 dark:border-[#324467]">
            <h2 class="text-2xl font-black mb-4 bg-gradient-to-r from-primary to-purple-600 bg-clip-text text-transparent">💬 Chat Interno</h2>
            
            <!-- Search -->
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                <input type="text" id="search-users" placeholder="Buscar conversación..." 
                       class="search-input-premium w-full pl-10 outline-none"
                       oninput="filtrarUsuarios(this.value)">
            </div>
        </div>
        
        <!-- Users List -->
        <div id="users-list" class="flex-1 overflow-y-auto">
            <div class="p-8 text-center text-gray-500">
                <div class="animate-spin w-8 h-8 border-4 border-primary border-t-transparent rounded-full mx-auto mb-3"></div>
                <p>Cargando usuarios...</p>
            </div>
        </div>
    </div>
    
    <!-- Chat Area - Derecha, ocupa todo el espacio restante -->
    <div class="flex-1 flex flex-col">
        <!-- Chat Header -->
        <div id="chat-header" class="chat-header-premium hidden">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <div id="active-user-avatar" class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-14 ring-4 ring-white/30"></div>
                        <div class="status-indicator"></div>
                    </div>
                    <div>
                        <h3 id="active-user-name" class="text-white text-xl font-bold"></h3>
                        <p id="active-user-status" class="text-white/80 text-sm"></p>
                    </div>
                </div>
                <button class="text-white/80 hover:text-white transition p-2">
                    <span class="material-symbols-outlined">more_vert</span>
                </button>
            </div>
        </div>
        
        <!-- Messages Area -->
        <div id="messages-container" class="messages-area">
            <div class="empty-state">
                <span class="material-symbols-outlined empty-state-icon">forum</span>
                <h3 class="text-xl font-bold">Selecciona una conversación</h3>
                <p>Elige un usuario para comenzar a chatear</p>
            </div>
        </div>
        
        <!-- Message Input -->
        <div id="message-input-area" class="message-input-premium hidden">
            <div class="flex gap-3 items-center">
                <input type="file" id="chat-file-input" class="hidden" accept="image/*,video/*,.pdf,.doc,.docx" onchange="handleChatFileSelect(event)">
                <button onclick="document.getElementById('chat-file-input').click()" class="text-gray-500 hover:text-primary transition p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" title="Adjuntar archivo">
                    <span class="material-symbols-outlined">attach_file</span>
                </button>
                <button onclick="toggleEmojiPicker('chat')" class="text-gray-500 hover:text-primary transition p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" title="Agregar emoji">
                    <span class="material-symbols-outlined">mood</span>
                </button>
                <input type="text" id="message-input" placeholder="Escribe un mensaje..." 
                       class="message-input-field flex-1 outline-none" 
                       onkeypress="if(event.key==='Enter') enviarMensaje()">
                <button onclick="enviarMensaje()" class="send-button-premium" title="Enviar mensaje">
                    <span class="material-symbols-outlined">send</span>
                </button>
            </div>
            <!-- Emoji Picker -->
            <div id="chat-emoji-picker" class="hidden mt-2 p-3 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-8 gap-2 max-h-48 overflow-y-auto">
                    <!-- Emojis will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<script>
let activeUserId = null;
let lastMessageId = 0;
let pollingInterval = null;

document.addEventListener('DOMContentLoaded', function() {
    cargarUsuarios();
    
    // Refresh users list every 10 seconds
    setInterval(cargarUsuarios, 10000);
});

async function cargarUsuarios() {
    const usersList = document.getElementById('users-list');
    
    try {
        const response = await fetch('api/get_chat_users.php');
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            mostrarUsuarios(result.users);
        } else {
            console.error('API Error:', result.message);
            usersList.innerHTML = `
                <div class="p-8 text-center">
                    <span class="material-symbols-outlined text-6xl text-red-500 mb-4">error</span>
                    <p class="text-red-500 font-semibold">Error al cargar usuarios</p>
                    <p class="text-sm text-gray-500 mt-2">${result.message}</p>
                    <button onclick="cargarUsuarios()" class="mt-4 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/80">
                        Reintentar
                    </button>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error:', error);
        usersList.innerHTML = `
            <div class="p-8 text-center">
                <span class="material-symbols-outlined text-6xl text-red-500 mb-4">wifi_off</span>
                <p class="text-red-500 font-semibold">Error de conexión</p>
                <p class="text-sm text-gray-500 mt-2">${error.message}</p>
                <button onclick="cargarUsuarios()" class="mt-4 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/80">
                    Reintentar
                </button>
            </div>
        `;
    }
}

function mostrarUsuarios(users) {
    const usersList = document.getElementById('users-list');
    usersList.innerHTML = '';
    
    if (users.length === 0) {
        usersList.innerHTML = '<div class="p-8 text-center text-gray-500">No hay usuarios disponibles</div>';
        return;
    }
    
    users.forEach(user => {
        const userDiv = document.createElement('div');
        userDiv.className = `user-item flex items-center gap-3 p-4 cursor-pointer ${activeUserId === user.id ? 'active' : ''}`;
        userDiv.onclick = () => seleccionarUsuario(user);
        userDiv.dataset.username = user.nombre.toLowerCase();
        
        const badge = user.unread_count > 0 ? `<span class="unread-badge">${user.unread_count}</span>` : '';
        
        userDiv.innerHTML = `
            <div class="relative">
                <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-12 ring-2 ring-gray-200 dark:ring-gray-700" style='background-image: url("${user.avatar || 'default-avatar.png'}");'></div>
                ${user.unread_count > 0 ? '' : '<div class="status-indicator"></div>'}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                    <p class="font-bold truncate">${user.nombre}</p>
                    ${badge}
                </div>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">${user.rol}</p>
            </div>
        `;
        
        usersList.appendChild(userDiv);
    });
}

// Función para filtrar usuarios
function filtrarUsuarios(query) {
    const userItems = document.querySelectorAll('.user-item');
    const searchQuery = query.toLowerCase();
    
    userItems.forEach(item => {
        const username = item.dataset.username || '';
        if (username.includes(searchQuery)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

async function seleccionarUsuario(user) {
    console.log('Usuario seleccionado:', user);
    
    if (!user || !user.id) {
        console.error('Usuario inválido:', user);
        showNotification('Error al seleccionar usuario', 'error');
        return;
    }
    
    activeUserId = user.id;
    lastMessageId = 0;
    
    console.log('activeUserId establecido a:', activeUserId);
    
    // Show chat header and input
    document.getElementById('chat-header').classList.remove('hidden');
    document.getElementById('message-input-area').classList.remove('hidden');
    
    // Update header
    document.getElementById('active-user-avatar').style.backgroundImage = `url("${user.avatar || 'default-avatar.png'}")`;
    document.getElementById('active-user-name').textContent = user.nombre;
    document.getElementById('active-user-status').textContent = user.rol;
    
    // Clear messages
    document.getElementById('messages-container').innerHTML = '';
    
    // Load messages
    await cargarMensajes();
    
    // Mark as read
    await fetch('api/mark_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ from_user_id: activeUserId })
    });
    
    // Start polling
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(cargarMensajes, 2000);
    
    // Refresh users to update unread count
    cargarUsuarios();
}

async function cargarMensajes() {
    if (!activeUserId) return;
    
    try {
        const response = await fetch(`api/get_messages.php?user_id=${activeUserId}&last_id=${lastMessageId}`);
        const result = await response.json();
        
        if (result.success && result.messages.length > 0) {
            result.messages.forEach(msg => mostrarMensaje(msg));
            lastMessageId = result.messages[result.messages.length - 1].id;
            scrollToBottom();
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function mostrarMensaje(msg) {
    const container = document.getElementById('messages-container');
    const isOwn = msg.from_user_id != activeUserId;
    
    const msgDiv = document.createElement('div');
    msgDiv.className = `flex gap-3 mb-6 ${isOwn ? 'justify-end' : ''}`;
    
    const time = new Date(msg.created_at).toLocaleTimeString('es-HN', { hour: '2-digit', minute: '2-digit' });
    
    // Check if there's a file and if it's an image
    let fileHtml = '';
    if (msg.file_path) {
        const isImage = /\.(jpg|jpeg|png|gif|webp)$/i.test(msg.file_path);
        
        if (isImage) {
            fileHtml = `
                <div class="mt-2">
                    <img src="${msg.file_path}" alt="Imagen" class="max-w-xs rounded-lg border-2 border-gray-200 dark:border-gray-700 cursor-pointer hover:border-primary transition" onclick="window.open('${msg.file_path}', '_blank')">
                </div>
            `;
        } else {
            const fileName = msg.file_path.split('/').pop();
            fileHtml = `
                <div class="mt-2">
                    <a href="${msg.file_path}" download class="inline-flex items-center gap-2 px-3 py-2 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        <span class="material-symbols-outlined text-sm">download</span>
                        <span class="text-sm">${fileName}</span>
                    </a>
                </div>
            `;
        }
    }
    
    if (isOwn) {
        msgDiv.innerHTML = `
            <div class="max-w-lg">
                <div class="message-bubble-own">
                    ${msg.mensaje !== '[Archivo adjunto]' ? `<p class="text-white">${escapeHtml(msg.mensaje)}</p>` : ''}
                    ${fileHtml}
                </div>
                <p class="text-xs text-gray-400 mt-1 text-right">${time}</p>
            </div>
        `;
    } else {
        msgDiv.innerHTML = `
            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 flex-shrink-0 ring-2 ring-gray-200 dark:ring-gray-700" style='background-image: url("${msg.from_avatar || 'default-avatar.png'}");'></div>
            <div class="max-w-lg">
                <div class="message-bubble-other">
                    ${msg.mensaje !== '[Archivo adjunto]' ? `<p class="text-gray-800 dark:text-gray-200">${escapeHtml(msg.mensaje)}</p>` : ''}
                    ${fileHtml}
                </div>
                <p class="text-xs text-gray-400 mt-1">${time}</p>
            </div>
        `;
    }
    
    container.appendChild(msgDiv);
}

// Función para escapar HTML y prevenir XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function enviarMensaje() {
    const input = document.getElementById('message-input');
    const mensaje = input.value.trim();
    
    // Check if user is selected
    if (!activeUserId) {
        showNotification('Selecciona un usuario primero', 'warning');
        return;
    }
    
    // Allow sending if there's a message OR a file
    if (!mensaje && !selectedChatFile) {
        return;
    }
    
    try {
        let response;
        
        // If there's a file, use FormData
        if (selectedChatFile) {
            const formData = new FormData();
            formData.append('to_user_id', activeUserId);
            formData.append('mensaje', mensaje || ''); // Allow empty message if there's a file
            formData.append('file', selectedChatFile);
            
            response = await fetch('api/send_message.php', {
                method: 'POST',
                body: formData // Don't set Content-Type, browser will set it with boundary
            });
        } else {
            // Text-only message
            response = await fetch('api/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ to_user_id: activeUserId, mensaje: mensaje })
            });
        }
        
        const result = await response.json();
        
        if (result.success) {
            input.value = '';
            input.placeholder = 'Escribe un mensaje...';
            input.style.borderColor = '';
            
            // Clear file selection
            selectedChatFile = null;
            const fileInput = document.getElementById('chat-file-input');
            if (fileInput) fileInput.value = '';
            
            showNotification('Mensaje enviado', 'success');
            // Message will appear via polling
        } else {
            showNotification(result.message || 'Error al enviar mensaje', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al enviar mensaje', 'error');
    }
}

function scrollToBottom() {
    const container = document.getElementById('messages-container');
    container.scrollTop = container.scrollHeight;
}

// Note: Emoji picker and file handling functions are loaded from chat_foro_shared.js
</script>
</body>
</html>

