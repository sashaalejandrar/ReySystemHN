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
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);

// Generar URL para QR (para abrir desde móvil)
$url_actual = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Escanear Factura - Rey System APP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script src="js/image-processor.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>
<script src="https://js.puter.com/v2/"></script>
<script src="js/parseo-ia.js"></script>
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
            },
        },
    }
</script>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    
    #video {
        transform: scaleX(-1);
    }
    
    .camera-container {
        position: relative;
        max-width: 100%;
        aspect-ratio: 4/3;
        background: #000;
        border-radius: 1rem;
        overflow: hidden;
    }
    
    /* Animaciones para modal */
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
</style>
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
        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">
            📸 Escanear Factura con IA
        </h1>
        <p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">
            Captura facturas y extrae productos automáticamente usando inteligencia artificial
        </p>
    </div>
    
    <!-- Toggle de Mejora de Imagen -->
    <div class="flex items-center gap-3 bg-blue-50 dark:bg-blue-900/20 px-4 py-2 rounded-lg">
        <span class="material-symbols-outlined text-blue-600">auto_fix_high</span>
        <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" id="toggleMejoraImagen" checked class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Mejorar Imagen Automáticamente</span>
        </label>
    </div>
    
    <!-- Selector de Método OCR -->
    <div class="flex items-center gap-3 bg-purple-50 dark:bg-purple-900/20 px-4 py-2 rounded-lg">
        <span class="material-symbols-outlined text-purple-600">psychology</span>
        <label class="flex items-center gap-2">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Método OCR:</span>
            <select id="metodoOCR" class="bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded px-3 py-1 text-sm">
                <option value="tesseract">Tesseract.js (Recomendado - Local - Gratis)</option>
                <option value="puter-aws">Puter.js AWS Textract (Gratis Ilimitado) 🚀</option>
                <option value="puter-mistral">Puter.js Mistral OCR + IA (Gratis Ilimitado) ⭐</option>
                <option value="ocrspace">OCR.space (Rápido - Gratis)</option>
                <option value="cloudmersive">Cloudmersive OCR (800/mes - Gratis) ⭐</option>
                <option value="mindee">Mindee Invoice OCR (Experimental)</option>
                <option value="mistral">Mistral OCR (IA Avanzada) ⭐</option>
            </select>
        </label>
    </div>
</div>

<!-- Opciones de Captura -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <!-- Opción 1: Cámara Web/Móvil -->
    <div class="bg-white dark:bg-[#192233] rounded-xl border-2 border-gray-200 dark:border-[#324467] p-6 hover:border-primary transition-all cursor-pointer" onclick="activarCamara()">
        <div class="text-center">
            <span class="material-symbols-outlined text-6xl text-blue-600 mb-4">photo_camera</span>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Usar Cámara</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Captura la factura con tu cámara web o móvil</p>
        </div>
    </div>

    <!-- Opción 2: Subir Imagen -->
    <div class="bg-white dark:bg-[#192233] rounded-xl border-2 border-gray-200 dark:border-[#324467] p-6 hover:border-primary transition-all cursor-pointer" onclick="document.getElementById('fileInput').click()">
        <div class="text-center">
            <span class="material-symbols-outlined text-6xl text-green-600 mb-4">upload_file</span>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Subir Imagen</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Selecciona una foto de tu galería</p>
        </div>
        <input type="file" id="fileInput" accept="image/*" class="hidden" onchange="cargarImagen(event)">
    </div>

    <!-- Opción 3: Código QR para Móvil -->
    <div class="bg-white dark:bg-[#192233] rounded-xl border-2 border-gray-200 dark:border-[#324467] p-6 hover:border-primary transition-all cursor-pointer" onclick="mostrarModalMovil()">
        <div class="text-center">
            <span class="material-symbols-outlined text-6xl text-purple-600 mb-4">qr_code_2</span>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Usar Móvil (QR/Link)</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Escanea el código QR o usa el enlace</p>
        </div>
    </div>
</div>

<!-- Área de Captura/Vista Previa -->
<div id="areaCaptura" class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6 hidden">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Captura de Factura</h3>
        <button onclick="cerrarCamara()" class="text-gray-400 hover:text-gray-600">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <!-- Video de Cámara -->
    <div id="camaraContainer" class="camera-container mb-4 hidden">
        <video id="video" autoplay playsinline class="w-full h-full object-cover"></video>
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2">
            <button onclick="capturarFoto()" class="px-6 py-3 bg-primary text-white rounded-full hover:bg-primary/90 transition-colors shadow-lg flex items-center gap-2">
                <span class="material-symbols-outlined">photo_camera</span>
                Capturar
            </button>
        </div>
    </div>

    <!-- Canvas para captura -->
    <canvas id="canvas" class="hidden"></canvas>

    <!-- Vista previa de imagen -->
    <div id="vistaPrevia" class="hidden">
        <img id="imagenPrevia" class="w-full rounded-lg mb-4">
        <div class="flex gap-3 justify-center">
            <button onclick="procesarConIA()" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <span class="material-symbols-outlined">auto_awesome</span>
                Procesar con IA
            </button>
            <button onclick="reiniciar()" class="flex items-center gap-2 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <span class="material-symbols-outlined">refresh</span>
                Tomar Otra
            </button>
        </div>
    </div>
</div>

<!-- Área de Procesamiento -->
<div id="areaProcesamiento" class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6 hidden">
    <div class="flex items-center gap-3 mb-4">
        <div class="animate-spin">
            <span class="material-symbols-outlined text-primary text-3xl">progress_activity</span>
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Procesando con IA...</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Extrayendo información de la factura</p>
        </div>
    </div>
    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
        <div id="progressBar" class="bg-primary h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
    </div>
</div>

<!-- Resultados Extraídos -->
<div id="areaResultados" class="hidden">
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Productos Detectados</h3>
        <div id="productosDetectados" class="space-y-4">
            <!-- Se llenará dinámicamente -->
        </div>
    </div>

    <div class="flex gap-3 justify-end">
        <button onclick="reiniciar()" class="flex items-center gap-2 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
            <span class="material-symbols-outlined">close</span>
            Cancelar
        </button>
        <button onclick="guardarProductos()" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
            <span class="material-symbols-outlined">save</span>
            Guardar Productos
        </button>
    </div>
</div>

</div>
</main>
</div>
</div>

<!-- Modal de Notificaciones -->
<div id="modalNotificacion" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#192233] rounded-xl max-w-md w-full overflow-hidden shadow-2xl">
        <div id="notifHeader" class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <span id="notifIcon" class="material-symbols-outlined text-4xl"></span>
                <h3 id="notifTitulo" class="text-xl font-bold text-gray-900 dark:text-white"></h3>
            </div>
        </div>
        <div class="p-6">
            <p id="notifMensaje" class="text-gray-600 dark:text-gray-400"></p>
        </div>
        <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end">
            <button onclick="cerrarNotificacion()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                Entendido
            </button>
        </div>
    </div>
</div>

<script src="js/escanear-factura.js"></script>
</body>
</html>
