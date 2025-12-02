<?php
session_start();
include 'funciones.php';

VerificarSiUsuarioYaInicioSesion();
// Conexión a la base de datos
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");
date_default_timezone_set('America/Tegucigalpa');

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener datos del usuario (usando prepared statement para seguridad)
 $stmt_usuario = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = ?");
 $stmt_usuario->bind_param("s", $_SESSION['usuario']);
 $stmt_usuario->execute();
 $resultado = $stmt_usuario->get_result();

if ($resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Email = $row['Email'];
    $Celular = $row['Celular'];
    $Perfil = $row['Perfil'];
}
 $stmt_usuario->close();

// --- LÓGICA DE PERMISOS ---
 $rol_usuario = strtolower($Rol);
// --- FIN DE LA LÓGICA DE PERMISOS ---
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Registrar Nuevo Egreso</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#4A90E2",
                    "background-light": "#F8F9FA",
                    "background-dark": "#111827",
                    "text-light": "#333333",
                    "text-dark": "#F3F4F6",
                    "border-light": "#D0D0D0",
                    "border-dark": "#4B5563",
                    "card-light": "#FFFFFF",
                    "card-dark": "#1F2937",
                    "secondary-light": "#E5E7EB",
                    "secondary-dark": "#374151"
                },
                fontFamily: {
                    "display": ["Inter", "sans-serif"]
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
    }
    /* Estilos para la vista previa de archivos */
    #file-preview-container {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    .file-preview-item {
        position: relative;
        width: 100px;
        height: 100px;
        border: 1px solid #ccc;
        border-radius: 8px;
        overflow: hidden;
        background-color: #f0f0f0;
    }
    .file-preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .file-preview-item .remove-file {
        position: absolute;
        top: 2px;
        right: 2px;
        background: rgba(255, 0, 0, 0.7);
        color: white;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        cursor: pointer;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Animaciones */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out;
    }
    
    .animate-slideUp {
        animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }
    
    /* Efecto hover en el botón principal */
    #uploadOptionsBtn:hover {
        box-shadow: 0 20px 25px -5px rgba(79, 70, 229, 0.3), 0 10px 10px -5px rgba(79, 70, 229, 0.2);
    }
    
    /* Transición suave para el menú */
    #uploadMenu {
        transition: all 0.3s ease;
    }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
  <?php include 'menu_lateral.php'; ?>
<main class="flex flex-1 justify-center py-8 px-4 sm:px-6 lg:px-8">
<div class="flex w-full max-w-2xl flex-col">
<div class="mb-8">
<p class="text-text-dark text-4xl font-black leading-tight tracking-[-0.033em]">Registrar Nuevo Egreso</p>
<p class="text-gray-400 text-base font-normal leading-normal mt-2">Ingresa los detalles de tu compra o justificación.</p>
</div>
<div class="bg-card-dark rounded-xl shadow-sm border border-border-dark p-6 md:p-8">
<!-- Mensaje de error -->
<div id="mensaje-error" class="hidden mb-4 p-4 rounded-lg bg-red-100 dark:bg-red-900/40 border border-red-300 dark:border-red-700">
<p class="text-red-800 dark:text-red-200 font-medium"></p>
</div>

<!-- Mensaje de éxito -->
<div id="mensaje-exito" class="hidden mb-4 p-4 rounded-lg bg-green-100 dark:bg-green-900/40 border border-green-300 dark:border-green-700">
<p class="text-green-800 dark:text-green-200 font-medium"></p>
</div>

<form id="egresoForm" class="flex flex-col gap-6" enctype="multipart/form-data">
<div class="flex flex-col gap-2">
<p class="text-text-dark text-base font-medium leading-normal">Tipo de Egreso</p>
<div class="flex h-10 w-full items-center justify-center rounded-lg bg-secondary-dark p-1">
<label class="flex h-full flex-1 cursor-pointer items-center justify-center overflow-hidden rounded-lg px-2 has-[:checked]:bg-card-dark has-[:checked]:shadow-sm has-[:checked]:text-primary text-gray-400 text-sm font-medium leading-normal transition-colors">
<span class="truncate">Compra</span>
<input checked="" class="invisible w-0" name="expense_type" type="radio" value="Compra"/>
</label>
<label class="flex h-full flex-1 cursor-pointer items-center justify-center overflow-hidden rounded-lg px-2 has-[:checked]:bg-card-dark has-[:checked]:shadow-sm has-[:checked]:text-primary text-gray-400 text-sm font-medium leading-normal transition-colors">
<span class="truncate">Justificación</span>
<input class="invisible w-0" name="expense_type" type="radio" value="Justificación"/>
</label>
</div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-text-dark text-base font-medium leading-normal pb-2">Monto</p>
<input id="amountInput" name="amount" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-dark bg-background-dark h-12 placeholder:text-gray-500 p-3 text-base font-normal leading-normal" placeholder="L 0.00" type="number" step="0.01" required/>
</label>
<label class="flex flex-col min-w-40 flex-1">
<p class="text-text-dark text-base font-medium leading-normal pb-2">Fecha</p>
<div class="flex w-full flex-1 items-stretch rounded-lg">
<input id="dateInput" name="date" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-dark bg-background-dark h-12 placeholder:text-gray-500 p-3 rounded-r-none border-r-0" type="date" value="<?php echo date('Y-m-d'); ?>" required/>
<div class="text-gray-400 flex border border-border-dark bg-background-dark items-center justify-center pr-3 rounded-r-lg border-l-0">
</div>
</div>
</label>
</div>
<label class="flex flex-col w-full">
<p class="text-text-dark text-base font-medium leading-normal pb-2">Descripción</p>
<textarea id="descriptionInput" name="description" class="form-textarea flex w-full min-w-0 flex-1 resize-y overflow-hidden rounded-lg text-text-dark focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-border-dark bg-background-dark min-h-28 placeholder:text-gray-500 p-3 text-base font-normal leading-normal" placeholder="Ej: Insumos de oficina para el nuevo proyecto" required></textarea>
</label>
<div class="flex flex-col w-full">
<p class="text-text-dark text-base font-medium leading-normal pb-2">Adjuntar Recibo(s)</p>

<!-- Botón principal de opciones de carga -->
<div class="relative mb-4">
<button type="button" id="uploadOptionsBtn" class="w-full flex items-center justify-center gap-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold py-4 px-6 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-[1.02] hover:shadow-xl">
<span class="material-symbols-outlined text-2xl">add_photo_alternate</span>
<span>Subir Recibos</span>
<span class="material-symbols-outlined text-xl ml-auto">expand_more</span>
</button>

<!-- Menú desplegable de opciones -->
<div id="uploadMenu" class="hidden absolute top-full left-0 right-0 mt-2 bg-card-dark border border-border-dark rounded-xl shadow-2xl overflow-hidden z-50 animate-fadeIn">
<!-- Opción: Cámara PC -->
<button type="button" id="pcCameraBtn" class="w-full flex items-center gap-4 px-6 py-4 hover:bg-gray-700 transition-colors text-left border-b border-border-dark">
<div class="flex items-center justify-center w-12 h-12 bg-blue-600/20 rounded-lg">
<span class="material-symbols-outlined text-blue-400 text-2xl">photo_camera</span>
</div>
<div class="flex-1">
<p class="text-white font-semibold">Usar Cámara (PC)</p>
<p class="text-gray-400 text-sm">Toma fotos directamente desde tu cámara</p>
</div>
</button>

<!-- Opción: Seleccionar archivos -->
<button type="button" id="fileSelectBtn" class="w-full flex items-center gap-4 px-6 py-4 hover:bg-gray-700 transition-colors text-left border-b border-border-dark">
<div class="flex items-center justify-center w-12 h-12 bg-green-600/20 rounded-lg">
<span class="material-symbols-outlined text-green-400 text-2xl">upload_file</span>
</div>
<div class="flex-1">
<p class="text-white font-semibold">Seleccionar Archivos</p>
<p class="text-gray-400 text-sm">Sube archivos desde tu computadora</p>
</div>
</button>

<!-- Opción: Móvil (QR) -->
<button type="button" id="mobileQRBtn" class="w-full flex items-center gap-4 px-6 py-4 hover:bg-gray-700 transition-colors text-left">
<div class="flex items-center justify-center w-12 h-12 bg-purple-600/20 rounded-lg">
<span class="material-symbols-outlined text-purple-400 text-2xl">qr_code_2</span>
</div>
<div class="flex-1">
<p class="text-white font-semibold">Usar Móvil (QR/Link)</p>
<p class="text-gray-400 text-sm">Escanea el código QR o usa el enlace</p>
</div>
</button>
</div>
</div>

<!-- Dropzone tradicional (oculto por defecto) -->
<div id="dropzone" class="hidden justify-center items-center w-full h-48 border-2 border-border-dark border-dashed rounded-lg cursor-pointer bg-background-dark hover:bg-gray-800 transition-colors">
<div class="flex flex-col items-center justify-center pt-5 pb-6 text-center">
<span class="material-symbols-outlined text-4xl text-gray-400 mb-2">cloud_upload</span>
<p class="mb-2 text-sm text-gray-400"><span class="font-semibold">Haz clic para subir</span> o arrastra y suelta</p>
<p class="text-xs text-gray-400">PDF, JPG, PNG (MÁX. 5MB por archivo)</p>
</div>
<input id="recibosInput" name="recibos[]" class="hidden" type="file" multiple accept="image/*,application/pdf"/>
</div>

<!-- Input para cámara PC -->
<input id="cameraInput" name="camera[]" class="hidden" type="file" accept="image/*" capture="environment" multiple/>

<!-- Vista previa de archivos -->
<div id="file-preview-container"></div>
</div>
<div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-4 border-t border-border-dark mt-2">
<button type="button" onclick="window.history.back();" class="flex w-full sm:w-auto cursor-pointer items-center justify-center overflow-hidden rounded-lg h-11 bg-transparent text-gray-300 gap-2 text-sm font-bold leading-normal tracking-[0.015em] min-w-0 px-6 hover:bg-secondary-dark transition-colors">Cancelar</button>
<button type="submit" class="flex w-full sm:w-auto cursor-pointer items-center justify-center overflow-hidden rounded-lg h-11 bg-primary text-white gap-2 text-sm font-bold leading-normal tracking-[0.015em] min-w-0 px-6 hover:bg-primary/90 transition-colors shadow-sm">Guardar Egreso</button>
</div>
</form>
</div>
</div>
</main>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('egresoForm');
    const dropzone = document.getElementById('dropzone');
    const fileInput = document.getElementById('recibosInput');
    const cameraInput = document.getElementById('cameraInput');
    const filePreviewContainer = document.getElementById('file-preview-container');
    const errorMessage = document.getElementById('mensaje-error');
    const successMessage = document.getElementById('mensaje-exito');
    const uploadOptionsBtn = document.getElementById('uploadOptionsBtn');
    const uploadMenu = document.getElementById('uploadMenu');
    const pcCameraBtn = document.getElementById('pcCameraBtn');
    const fileSelectBtn = document.getElementById('fileSelectBtn');
    const mobileQRBtn = document.getElementById('mobileQRBtn');
    
    let selectedFiles = [];
    let uploadSessionId = generateSessionId();

    // Generar ID de sesión único
    function generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    // Toggle menú de opciones
    uploadOptionsBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        uploadMenu.classList.toggle('hidden');
    });

    // Cerrar menú al hacer clic fuera
    document.addEventListener('click', (e) => {
        if (!uploadMenu.contains(e.target) && e.target !== uploadOptionsBtn) {
            uploadMenu.classList.add('hidden');
        }
    });

    // Opción: Usar cámara PC
    pcCameraBtn.addEventListener('click', () => {
        uploadMenu.classList.add('hidden');
        openCameraModal();
    });

    // Opción: Seleccionar archivos
    fileSelectBtn.addEventListener('click', () => {
        uploadMenu.classList.add('hidden');
        fileInput.click();
    });

    // Opción: Móvil (QR/Link)
    mobileQRBtn.addEventListener('click', () => {
        uploadMenu.classList.add('hidden');
        showMobileUploadModal();
    });

    // Mostrar modal con QR y link para móvil
    function showMobileUploadModal() {
        const mobileUrl = window.location.origin + window.location.pathname.replace('compra_desde_ventas.php', 'mobile_upload.php') + '?session=' + uploadSessionId;
        
        // Crear modal
        const modal = document.createElement('div');
        modal.id = 'mobileModal';
        modal.className = 'fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-4';
        modal.innerHTML = `
            <div class="bg-card-dark rounded-2xl shadow-2xl max-w-md w-full p-8 relative animate-slideUp">
                <button onclick="closeMobileModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
                
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-600/20 rounded-full mb-4">
                        <span class="material-symbols-outlined text-purple-400 text-4xl">smartphone</span>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Subir desde Móvil</h2>
                    <p class="text-gray-400 text-sm">Escanea el código QR o usa el enlace</p>
                </div>

                <div class="bg-white p-6 rounded-xl mb-6 flex items-center justify-center">
                    <div id="qrcode"></div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center gap-3 p-4 bg-background-dark rounded-lg border border-border-dark">
                        <span class="material-symbols-outlined text-blue-400">link</span>
                        <input type="text" readonly value="${mobileUrl}" class="flex-1 bg-transparent text-gray-300 text-sm outline-none" id="mobileUrlInput">
                        <button onclick="copyMobileUrl()" class="text-blue-400 hover:text-blue-300 transition-colors">
                            <span class="material-symbols-outlined">content_copy</span>
                        </button>
                    </div>

                    <button onclick="openMobileUrl()" class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">open_in_new</span>
                        <span>Abrir en Nueva Pestaña</span>
                    </button>
                </div>

                <div class="mt-6 p-4 bg-blue-900/20 border border-blue-700/50 rounded-lg">
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-blue-400 text-xl">info</span>
                        <div class="text-sm text-gray-300">
                            <p class="font-semibold mb-1">Instrucciones:</p>
                            <ol class="list-decimal list-inside space-y-1 text-gray-400">
                                <li>Escanea el QR con tu móvil</li>
                                <li>Toma fotos de los recibos</li>
                                <li>Los archivos se cargarán aquí automáticamente</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <div id="uploadStatus" class="mt-4 hidden">
                    <div class="flex items-center gap-3 p-4 bg-green-900/20 border border-green-700 rounded-lg">
                        <span class="material-symbols-outlined text-green-400">check_circle</span>
                        <span class="text-green-200 text-sm" id="uploadStatusText"></span>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);

        // Generar código QR
        new QRCode(document.getElementById("qrcode"), {
            text: mobileUrl,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });

        // Iniciar polling para verificar archivos subidos
        startPollingForUploads();
    }

    // Cerrar modal
    window.closeMobileModal = function() {
        const modal = document.getElementById('mobileModal');
        if (modal) {
            modal.remove();
        }
        stopPollingForUploads();
    };

    // Copiar URL
    window.copyMobileUrl = function() {
        const input = document.getElementById('mobileUrlInput');
        input.select();
        document.execCommand('copy');
        mostrarExito('Enlace copiado al portapapeles');
    };

    // Abrir URL en nueva pestaña
    window.openMobileUrl = function() {
        const url = document.getElementById('mobileUrlInput').value;
        window.open(url, '_blank');
    };

    // Abrir modal de cámara PC
    let cameraStream = null;
    function openCameraModal() {
        // Crear modal de cámara
        const cameraModal = document.createElement('div');
        cameraModal.id = 'cameraModal';
        cameraModal.className = 'fixed inset-0 bg-black/90 backdrop-blur-sm flex items-center justify-center z-50 p-4';
        cameraModal.innerHTML = `
            <div class="bg-card-dark rounded-2xl shadow-2xl max-w-3xl w-full p-6 relative animate-slideUp">
                <button onclick="closeCameraModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors z-10">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
                
                <div class="text-center mb-4">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600/20 rounded-full mb-3">
                        <span class="material-symbols-outlined text-blue-400 text-4xl">photo_camera</span>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">Capturar con Cámara</h2>
                    <p class="text-gray-400 text-sm">Toma fotos de tus recibos</p>
                </div>

                <div class="relative bg-black rounded-xl overflow-hidden mb-4" style="aspect-ratio: 4/3;">
                    <video id="cameraVideo" autoplay playsinline class="w-full h-full object-cover"></video>
                    <div id="cameraError" class="hidden absolute inset-0 flex items-center justify-center bg-red-900/20 border-2 border-red-500 rounded-xl">
                        <div class="text-center p-6">
                            <span class="material-symbols-outlined text-red-400 text-5xl mb-3">error</span>
                            <p class="text-red-200 font-semibold mb-2">No se pudo acceder a la cámara</p>
                            <p class="text-red-300 text-sm">Verifica los permisos del navegador</p>
                        </div>
                    </div>
                    <canvas id="cameraCanvas" class="hidden"></canvas>
                </div>

                <div class="flex gap-3 justify-center">
                    <button onclick="capturePhoto()" class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition-colors shadow-lg">
                        <span class="material-symbols-outlined text-2xl">photo_camera</span>
                        <span>Capturar Foto</span>
                    </button>
                    <button onclick="closeCameraModal()" class="flex items-center justify-center gap-2 bg-gray-700 hover:bg-gray-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                        <span class="material-symbols-outlined">close</span>
                        <span>Cerrar</span>
                    </button>
                </div>

                <div id="captureStatus" class="mt-4 hidden">
                    <div class="flex items-center gap-3 p-4 bg-green-900/20 border border-green-700 rounded-lg">
                        <span class="material-symbols-outlined text-green-400">check_circle</span>
                        <span class="text-green-200 text-sm" id="captureStatusText"></span>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(cameraModal);

        // Iniciar cámara
        startCamera();
    }

    // Iniciar cámara
    async function startCamera() {
        const video = document.getElementById('cameraVideo');
        const errorDiv = document.getElementById('cameraError');
        
        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    facingMode: 'environment',
                    width: { ideal: 1920 },
                    height: { ideal: 1080 }
                } 
            });
            video.srcObject = cameraStream;
        } catch (error) {
            console.error('Error al acceder a la cámara:', error);
            errorDiv.classList.remove('hidden');
            mostrarError('No se pudo acceder a la cámara. Verifica los permisos.');
        }
    }

    // Capturar foto
    window.capturePhoto = function() {
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        const statusDiv = document.getElementById('captureStatus');
        const statusText = document.getElementById('captureStatusText');
        
        if (!video.srcObject) {
            mostrarError('La cámara no está activa');
            return;
        }

        // Configurar canvas con las dimensiones del video
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        
        // Dibujar el frame actual del video en el canvas
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        // Convertir canvas a blob
        canvas.toBlob((blob) => {
            const fileName = `recibo_${Date.now()}.jpg`;
            const file = new File([blob], fileName, { type: 'image/jpeg' });
            
            // Agregar a la lista de archivos
            selectedFiles.push(file);
            displayFilePreview(file);
            
            // Mostrar mensaje de éxito
            statusText.textContent = 'Foto capturada correctamente';
            statusDiv.classList.remove('hidden');
            
            // Ocultar mensaje después de 2 segundos
            setTimeout(() => {
                statusDiv.classList.add('hidden');
            }, 2000);
            
            mostrarExito('Foto capturada y agregada');
        }, 'image/jpeg', 0.9);
    };

    // Cerrar modal de cámara
    window.closeCameraModal = function() {
        const modal = document.getElementById('cameraModal');
        if (modal) {
            // Detener stream de cámara
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }
            modal.remove();
        }
    };

    // Polling para archivos subidos desde móvil
    let pollingInterval;
    function startPollingForUploads() {
        pollingInterval = setInterval(async () => {
            try {
                const response = await fetch(`check_mobile_uploads.php?session=${uploadSessionId}`);
                const data = await response.json();
                
                if (data.files && data.files.length > 0) {
                    // Agregar archivos a la lista
                    data.files.forEach(fileInfo => {
                        // Crear objeto File desde la información
                        fetch(fileInfo.path)
                            .then(res => res.blob())
                            .then(blob => {
                                const file = new File([blob], fileInfo.name, { type: fileInfo.type });
                                selectedFiles.push(file);
                                displayFilePreview(file);
                            });
                    });

                    // Mostrar mensaje de éxito
                    const statusDiv = document.getElementById('uploadStatus');
                    const statusText = document.getElementById('uploadStatusText');
                    statusText.textContent = `${data.files.length} archivo(s) recibido(s) desde móvil`;
                    statusDiv.classList.remove('hidden');

                    // Cerrar modal después de 2 segundos
                    setTimeout(() => {
                        closeMobileModal();
                    }, 2000);
                }
            } catch (error) {
                console.error('Error al verificar archivos:', error);
            }
        }, 2000); // Verificar cada 2 segundos
    }

    function stopPollingForUploads() {
        if (pollingInterval) {
            clearInterval(pollingInterval);
        }
    }

    // --- LÓGICA PARA EL DROPZONE Y SELECCIÓN DE ARCHIVOS ---
    dropzone.addEventListener('click', () => fileInput.click());

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-primary', 'bg-primary/10');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-primary', 'bg-primary/10');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-primary', 'bg-primary/10');
        handleFiles(e.dataTransfer.files);
    });

    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    cameraInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    function handleFiles(files) {
        Array.from(files).forEach(file => {
            if (file.size > 5 * 1024 * 1024) { // 5MB limit
                mostrarError(`El archivo "${file.name}" excede el tamaño máximo de 5MB.`);
                return;
            }
            selectedFiles.push(file);
            displayFilePreview(file);
        });
    }

    function displayFilePreview(file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            const previewItem = document.createElement('div');
            previewItem.className = 'file-preview-item';
            
            // Determinar si es PDF o imagen
            if (file.type === 'application/pdf') {
                previewItem.innerHTML = `
                    <div class="flex items-center justify-center h-full bg-red-100 dark:bg-red-900/20">
                        <span class="material-symbols-outlined text-red-600 text-4xl">picture_as_pdf</span>
                    </div>
                    <button type="button" class="remove-file" onclick="removeFile('${file.name}')">×</button>
                `;
            } else {
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Vista previa de ${file.name}">
                    <button type="button" class="remove-file" onclick="removeFile('${file.name}')">×</button>
                `;
            }
            filePreviewContainer.appendChild(previewItem);
        };
        reader.readAsDataURL(file);
    }

    window.removeFile = function(fileName) {
        selectedFiles = selectedFiles.filter(file => file.name !== fileName);
        // Actualizar la vista previa
        filePreviewContainer.innerHTML = '';
        selectedFiles.forEach(file => displayFilePreview(file));
    };

    // --- FUNCIONES PARA MOSTRAR MENSAJES ---
    function mostrarError(mensaje) {
        errorMessage.querySelector('p').textContent = mensaje;
        errorMessage.classList.remove('hidden');
        successMessage.classList.add('hidden');
        
        // Ocultar después de 5 segundos
        setTimeout(() => {
            errorMessage.classList.add('hidden');
        }, 5000);
    }

    function mostrarExito(mensaje) {
        successMessage.querySelector('p').textContent = mensaje;
        successMessage.classList.remove('hidden');
        errorMessage.classList.add('hidden');
        
        // Ocultar después de 5 segundos
        setTimeout(() => {
            successMessage.classList.add('hidden');
        }, 5000);
    }

    // --- LÓGICA PARA EL ENVÍO DEL FORMULARIO ---
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        
        // Limpiar archivos previos en el FormData para evitar duplicados
        formData.delete('recibos[]');

        // Añadir los archivos seleccionados al FormData
        selectedFiles.forEach(file => {
            formData.append('recibos[]', file);
        });

        // Mostrar indicador de carga
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = 'Guardando...';

        fetch('procesar_egreso.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Primero obtener la respuesta como texto para depuración
            return response.text().then(text => {
                console.log('Respuesta del servidor:', text);
                
                try {
                    // Intentar parsear como JSON
                    return JSON.parse(text);
                } catch (e) {
                    // Si no es JSON, mostrar el texto crudo
                    throw new Error('Respuesta no válida del servidor: ' + text.substring(0, 200));
                }
            });
        })
        .then(data => {
            if (data.success) {
                mostrarExito('Egreso guardado con éxito.');
                form.reset();
                filePreviewContainer.innerHTML = '';
                selectedFiles = [];
                
                // Redirigir después de un breve retraso
                setTimeout(() => {
                    window.location.href = 'caja_al_dia.php';
                }, 2000);
            } else {
                mostrarError('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            mostrarError('Ocurrió un error inesperado: ' + error.message);
        })
        .finally(() => {
            // Restaurar el botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
});
</script>
</body>
</html>