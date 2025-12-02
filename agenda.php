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
    $Perfil = $row['Perfil'];
    $Email = $row['Email'] ?? '';
    $Telefono = $row['Celular'] ?? '';
}

$rol_usuario = strtolower($Rol);

// Verificar permisos (solo Admin y Cajero/Gerente)
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
<title>Agenda & Notas - Rey System APP</title>
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
    .tab-active {
        border-bottom: 2px solid #1152d4;
        color: #1152d4;
    }
    .priority-baja { border-left: 4px solid #10b981; }
    .priority-media { border-left: 4px solid #3b82f6; }
    .priority-alta { border-left: 4px solid #f59e0b; }
    .priority-urgente { border-left: 4px solid #ef4444; }
</style>
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
<h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">📅 Agenda & Notas</h1>
<p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">Gestiona tus tareas, notas y correos desde aquí.</p>
</div>
<div class="flex gap-3">
<button onclick="openNewTaskModal()" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-xl hover:bg-blue-700 transition-all duration-300 font-semibold">
<span class="material-symbols-outlined">add</span>
Nueva Tarea
</button>
<button onclick="openNewNoteModal()" class="flex items-center gap-2 px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all duration-300 font-semibold">
<span class="material-symbols-outlined">note_add</span>
Nueva Nota
</button>
</div>
</div>

<!-- Tabs -->
<div class="flex gap-6 border-b border-gray-200 dark:border-[#324467] mb-6">
<button onclick="switchTab('tareas')" id="tab-tareas" class="tab-active px-4 py-3 font-semibold transition-all">
<span class="flex items-center gap-2">
<span class="material-symbols-outlined">task_alt</span>
Tareas
</span>
</button>
<button onclick="switchTab('notas')" id="tab-notas" class="px-4 py-3 font-semibold text-gray-500 dark:text-[#92a4c9] hover:text-primary transition-all">
<span class="flex items-center gap-2">
<span class="material-symbols-outlined">sticky_note_2</span>
Notas
</span>
</button>
<button onclick="switchTab('correos')" id="tab-correos" class="px-4 py-3 font-semibold text-gray-500 dark:text-[#92a4c9] hover:text-primary transition-all">
<span class="flex items-center gap-2">
<span class="material-symbols-outlined">mail</span>
Correos
</span>
</button>
</div>

<!-- Content Sections -->
<div id="content-tareas" class="tab-content">
<!-- Filtros -->
<div class="flex flex-wrap gap-3 mb-6">
<select onchange="filterTasks()" id="filter-estado" class="px-4 py-2 bg-white dark:bg-[#192233] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white">
<option value="">Todos los estados</option>
<option value="pendiente">Pendiente</option>
<option value="en_progreso">En Progreso</option>
<option value="completada">Completada</option>
</select>
<select onchange="filterTasks()" id="filter-prioridad" class="px-4 py-2 bg-white dark:bg-[#192233] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white">
<option value="">Todas las prioridades</option>
<option value="baja">Baja</option>
<option value="media">Media</option>
<option value="alta">Alta</option>
<option value="urgente">Urgente</option>
</select>
</div>

<!-- Kanban Board -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
<!-- Pendientes -->
<div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
<h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
<span class="material-symbols-outlined text-yellow-500">schedule</span>
Pendientes
<span id="count-pendiente" class="ml-auto text-sm bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-2 py-1 rounded-full">0</span>
</h3>
<div id="tasks-pendiente" class="space-y-3">
<!-- Tasks will be loaded here -->
</div>
</div>

<!-- En Progreso -->
<div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
<h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
<span class="material-symbols-outlined text-blue-500">play_circle</span>
En Progreso
<span id="count-en_progreso" class="ml-auto text-sm bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded-full">0</span>
</h3>
<div id="tasks-en_progreso" class="space-y-3">
<!-- Tasks will be loaded here -->
</div>
</div>

<!-- Completadas -->
<div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
<h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
<span class="material-symbols-outlined text-green-500">check_circle</span>
Completadas
<span id="count-completada" class="ml-auto text-sm bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded-full">0</span>
</h3>
<div id="tasks-completada" class="space-y-3">
<!-- Tasks will be loaded here -->
</div>
</div>
</div>
</div>

<div id="content-notas" class="tab-content hidden">
<!-- Grid de Notas -->
<div id="notes-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
<!-- Notes will be loaded here -->
</div>
</div>

<div id="content-correos" class="tab-content hidden">
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
<!-- Formulario de Envío -->
<div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
<h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
<span class="material-symbols-outlined text-primary">send</span>
Enviar Correo
</h3>
<form id="email-form" class="space-y-4">
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Destinatario(s)</label>
<input type="text" id="email-destinatario" required placeholder="correo1@ejemplo.com, correo2@ejemplo.com" class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white">
<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Separa múltiples correos con comas</p>
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de Correo</label>
<select id="email-tipo" onchange="handleEmailTypeChange()" class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white">
<option value="pedido">Pedido General</option>
<option value="reabastecer_stock">🔄 Reabastecer Stock Completo</option>
<option value="reabastecer_selectivo">📦 Reabastecer Stock Selectivo</option>
<option value="nota">Nota</option>
<option value="recordatorio">Recordatorio</option>
<option value="otro">Otro</option>
</select>
</div>

<!-- Selector de Productos (solo para selectivo) -->
<div id="product-selector" class="hidden">
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
Seleccionar Productos
<button type="button" onclick="loadLowStockProducts()" class="ml-2 text-xs text-primary hover:underline">
Cargar productos con bajo stock
</button>
</label>
<div id="product-list" class="max-h-60 overflow-y-auto bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg p-3 space-y-2">
<!-- Products will be loaded here -->
</div>
</div>

<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Asunto</label>
<input type="text" id="email-asunto" required class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white">
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex justify-between">
<span>Mensaje</span>
<button type="button" onclick="generateTemplate()" class="text-xs text-primary hover:underline">
Generar Plantilla
</button>
</label>
<textarea id="email-mensaje" rows="10" required class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white font-mono text-sm"></textarea>
</div>
<button type="submit" class="w-full px-6 py-3 bg-primary text-white rounded-xl hover:bg-blue-700 transition-all duration-300 font-semibold flex items-center justify-center gap-2">
<span class="material-symbols-outlined">send</span>
Enviar Correo
</button>
</form>
</div>

<!-- Historial de Correos -->
<div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
<h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
<span class="material-symbols-outlined text-green-600">history</span>
Historial de Envíos
</h3>
<div id="email-history" class="space-y-3 max-h-[600px] overflow-y-auto">
<!-- Email history will be loaded here -->
</div>
</div>
</div>
</div>

</div>
</main>
</div>
</div>

<!-- Modal Nueva Tarea -->
<div id="modal-task" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
<div class="bg-white dark:bg-[#192233] rounded-xl p-8 max-w-2xl w-full mx-4 border border-gray-200 dark:border-[#324467]">
<div class="flex justify-between items-center mb-6">
<h2 class="text-2xl font-bold text-gray-900 dark:text-white">Nueva Tarea</h2>
<button onclick="closeTaskModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
<span class="material-symbols-outlined">close</span>
</button>
</div>
<form id="task-form" class="space-y-4">
<input type="hidden" id="task-id">
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Título</label>
<input type="text" id="task-titulo" required class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white">
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Descripción</label>
<textarea id="task-descripcion" rows="4" class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white"></textarea>
</div>
<div class="grid grid-cols-2 gap-4">
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Prioridad</label>
<select id="task-prioridad" class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white">
<option value="baja">Baja</option>
<option value="media" selected>Media</option>
<option value="alta">Alta</option>
<option value="urgente">Urgente</option>
</select>
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha Vencimiento</label>
<input type="date" id="task-fecha" class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white">
</div>
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Etiquetas (separadas por comas)</label>
<input type="text" id="task-etiquetas" placeholder="trabajo, urgente, cliente" class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white">
</div>
<div class="flex gap-3 pt-4">
<button type="submit" class="flex-1 px-6 py-3 bg-primary text-white rounded-xl hover:bg-blue-700 transition-all duration-300 font-semibold">
Guardar
</button>
<button type="button" onclick="closeTaskModal()" class="px-6 py-3 bg-gray-200 dark:bg-[#324467] text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-[#3d5478] transition-all duration-300 font-semibold">
Cancelar
</button>
</div>
</form>
</div>
</div>

<!-- Modal Nueva Nota -->
<div id="modal-note" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
<div class="bg-white dark:bg-[#192233] rounded-xl p-8 max-w-2xl w-full mx-4 border border-gray-200 dark:border-[#324467]">
<div class="flex justify-between items-center mb-6">
<h2 class="text-2xl font-bold text-gray-900 dark:text-white">Nueva Nota</h2>
<button onclick="closeNoteModal()" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
<span class="material-symbols-outlined">close</span>
</button>
</div>
<form id="note-form" class="space-y-4">
<input type="hidden" id="note-id">
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Título</label>
<input type="text" id="note-titulo" required class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white">
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Contenido</label>
<textarea id="note-descripcion" rows="8" required class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white"></textarea>
</div>
<div>
<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Etiquetas (separadas por comas)</label>
<input type="text" id="note-etiquetas" placeholder="personal, ideas, importante" class="w-full px-4 py-2 bg-white dark:bg-[#101622] border border-gray-200 dark:border-[#324467] rounded-lg text-gray-900 dark:text-white">
</div>
<div class="flex gap-3 pt-4">
<button type="submit" class="flex-1 px-6 py-3 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-all duration-300 font-semibold">
Guardar
</button>
<button type="button" onclick="closeNoteModal()" class="px-6 py-3 bg-gray-200 dark:bg-[#324467] text-gray-700 dark:text-gray-300 rounded-xl hover:bg-gray-300 dark:hover:bg-[#3d5478] transition-all duration-300 font-semibold">
Cancelar
</button>
</div>
</form>
</div>
</div>

<!-- Modal de Notificaciones -->
<div id="notification-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
<div class="bg-white dark:bg-[#192233] rounded-xl p-6 max-w-md w-full mx-4 border border-gray-200 dark:border-[#324467] shadow-2xl transform transition-all">
<div class="flex items-start gap-4">
<div id="notification-icon" class="flex-shrink-0">
<!-- Icon will be inserted here -->
</div>
<div class="flex-1">
<h3 id="notification-title" class="text-lg font-bold text-gray-900 dark:text-white mb-2"></h3>
<p id="notification-message" class="text-sm text-gray-600 dark:text-gray-400"></p>
</div>
</div>
<div id="notification-buttons" class="flex gap-3 mt-6">
<!-- Buttons will be inserted here -->
</div>
</div>
</div>

<script>
// Datos del usuario para firmas de correo
const userData = {
    nombre: '<?php echo $Nombre_Completo; ?>',
    email: '<?php echo $Email; ?>',
    telefono: '<?php echo $Telefono; ?>',
    usuario: '<?php echo $Usuario; ?>'
};

// Sistema de notificaciones
const NotificationSystem = {
    show: function(type, title, message, callback = null) {
        const modal = document.getElementById('notification-modal');
        const iconContainer = document.getElementById('notification-icon');
        const titleEl = document.getElementById('notification-title');
        const messageEl = document.getElementById('notification-message');
        const buttonsContainer = document.getElementById('notification-buttons');
        
        // Configurar icono y colores según el tipo
        const configs = {
            success: {
                icon: 'check_circle',
                iconClass: 'text-green-500 text-4xl',
                titleClass: 'text-green-700 dark:text-green-400'
            },
            error: {
                icon: 'error',
                iconClass: 'text-red-500 text-4xl',
                titleClass: 'text-red-700 dark:text-red-400'
            },
            warning: {
                icon: 'warning',
                iconClass: 'text-orange-500 text-4xl',
                titleClass: 'text-orange-700 dark:text-orange-400'
            },
            info: {
                icon: 'info',
                iconClass: 'text-blue-500 text-4xl',
                titleClass: 'text-blue-700 dark:text-blue-400'
            },
            confirm: {
                icon: 'help',
                iconClass: 'text-primary text-4xl',
                titleClass: 'text-primary'
            }
        };
        
        const config = configs[type] || configs.info;
        
        // Establecer icono
        iconContainer.innerHTML = `<span class="material-symbols-outlined ${config.iconClass}">${config.icon}</span>`;
        
        // Establecer título y mensaje
        titleEl.textContent = title;
        titleEl.className = `text-lg font-bold mb-2 ${config.titleClass}`;
        messageEl.textContent = message;
        
        // Configurar botones
        if (type === 'confirm') {
            buttonsContainer.innerHTML = `
                <button onclick="NotificationSystem.close(false)" class="flex-1 px-4 py-2 bg-gray-200 dark:bg-[#324467] text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-300 dark:hover:bg-[#3d5478] transition-all font-semibold">
                    Cancelar
                </button>
                <button onclick="NotificationSystem.close(true)" class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-all font-semibold">
                    Confirmar
                </button>
            `;
        } else {
            buttonsContainer.innerHTML = `
                <button onclick="NotificationSystem.close()" class="w-full px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition-all font-semibold">
                    Entendido
                </button>
            `;
        }
        
        // Guardar callback
        this.callback = callback;
        
        // Mostrar modal
        modal.classList.remove('hidden');
    },
    
    close: function(confirmed = null) {
        const modal = document.getElementById('notification-modal');
        modal.classList.add('hidden');
        
        if (this.callback) {
            this.callback(confirmed);
            this.callback = null;
        }
    },
    
    success: function(message, title = '✅ Éxito') {
        this.show('success', title, message);
    },
    
    error: function(message, title = '❌ Error') {
        this.show('error', title, message);
    },
    
    warning: function(message, title = '⚠️ Advertencia') {
        this.show('warning', title, message);
    },
    
    info: function(message, title = 'ℹ️ Información') {
        this.show('info', title, message);
    },
    
    confirm: function(message, callback, title = '❓ Confirmar') {
        this.show('confirm', title, message, callback);
    }
};

// Alias para compatibilidad
window.showNotification = NotificationSystem;
</script>
<script src="js/agenda.js"></script>
</body>
</html>
