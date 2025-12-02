<?php
// NO REQUIERE LOGIN - Página para subir facturas desde móvil
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Subir Factura - Rey System</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
            },
        },
    }
</script>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    
    .camera-container {
        position: relative;
        max-width: 100%;
        aspect-ratio: 4/3;
        background: #000;
        border-radius: 1rem;
        overflow: hidden;
    }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="min-h-screen flex flex-col items-center justify-center p-4">

<!-- Logo y Título -->
<div class="text-center mb-8">
    <h1 class="text-4xl font-black text-gray-900 dark:text-white mb-2">
        📸 Subir Factura
    </h1>
    <p class="text-gray-500 dark:text-gray-400">
        Captura o sube la foto de tu factura
    </p>
</div>

<!-- Opciones de Captura -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full max-w-2xl mb-6">
    <!-- Opción 1: Cámara -->
    <button onclick="activarCamara()" class="bg-white dark:bg-[#192233] rounded-xl border-2 border-gray-200 dark:border-[#324467] p-8 hover:border-primary transition-all">
        <span class="material-symbols-outlined text-6xl text-blue-600 mb-4">photo_camera</span>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Tomar Foto</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Usa la cámara de tu dispositivo</p>
    </button>

    <!-- Opción 2: Subir -->
    <button onclick="document.getElementById('fileInput').click()" class="bg-white dark:bg-[#192233] rounded-xl border-2 border-gray-200 dark:border-[#324467] p-8 hover:border-primary transition-all">
        <span class="material-symbols-outlined text-6xl text-green-600 mb-4">upload_file</span>
        <h3 class="text-xl font-bold text-gray-900 dark:text-white">Subir Imagen</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Selecciona desde tu galería</p>
    </button>
    <input type="file" id="fileInput" accept="image/*" class="hidden" onchange="cargarImagen(event)">
</div>

<!-- Área de Captura -->
<div id="areaCaptura" class="w-full max-w-2xl bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 hidden">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Captura de Factura</h3>
        <button onclick="cerrar()" class="text-gray-400 hover:text-gray-600">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <!-- Video -->
    <div id="camaraContainer" class="camera-container mb-4 hidden">
        <video id="video" autoplay playsinline class="w-full h-full object-cover"></video>
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2">
            <button onclick="capturarFoto()" class="px-6 py-3 bg-primary text-white rounded-full hover:bg-primary/90 transition-colors shadow-lg flex items-center gap-2">
                <span class="material-symbols-outlined">photo_camera</span>
                Capturar
            </button>
        </div>
    </div>

    <canvas id="canvas" class="hidden"></canvas>

    <!-- Vista previa -->
    <div id="vistaPrevia" class="hidden">
        <img id="imagenPrevia" class="w-full rounded-lg mb-4">
        <div class="flex gap-3 justify-center">
            <button onclick="enviarFactura()" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <span class="material-symbols-outlined">send</span>
                Enviar Factura
            </button>
            <button onclick="cerrar()" class="flex items-center gap-2 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <span class="material-symbols-outlined">close</span>
                Cancelar
            </button>
        </div>
    </div>
</div>

<!-- Procesando -->
<div id="procesando" class="w-full max-w-2xl bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 hidden">
    <div class="flex flex-col items-center gap-4">
        <div class="animate-spin">
            <span class="material-symbols-outlined text-primary text-5xl">progress_activity</span>
        </div>
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Enviando factura...</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">Por favor espera</p>
    </div>
</div>

<!-- Éxito -->
<div id="exito" class="w-full max-w-2xl bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-8 text-center hidden">
    <span class="material-symbols-outlined text-green-600 text-6xl mb-4">check_circle</span>
    <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">¡Factura Enviada!</h3>
    <p class="text-gray-500 dark:text-gray-400 mb-6">La factura se procesará automáticamente</p>
    <button onclick="location.reload()" class="px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
        Subir Otra Factura
    </button>
</div>

</div>

<script>
let stream = null;
let imagenCapturada = null;

async function activarCamara() {
    const areaCaptura = document.getElementById('areaCaptura');
    const camaraContainer = document.getElementById('camaraContainer');
    const video = document.getElementById('video');

    areaCaptura.classList.remove('hidden');
    camaraContainer.classList.remove('hidden');

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } }
        });
        video.srcObject = stream;
    } catch (error) {
        alert('Error al acceder a la cámara');
    }
}

function capturarFoto() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    ctx.drawImage(video, 0, 0);

    imagenCapturada = canvas.toDataURL('image/jpeg', 0.9);
    mostrarVistaPrevia(imagenCapturada);
    
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    document.getElementById('camaraContainer').classList.add('hidden');
}

function cargarImagen(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(e) {
        imagenCapturada = e.target.result;
        mostrarVistaPrevia(imagenCapturada);
    };
    reader.readAsDataURL(file);
}

function mostrarVistaPrevia(imagenSrc) {
    document.getElementById('areaCaptura').classList.remove('hidden');
    document.getElementById('vistaPrevia').classList.remove('hidden');
    document.getElementById('imagenPrevia').src = imagenSrc;
}

async function enviarFactura() {
    if (!imagenCapturada) return;

    document.getElementById('vistaPrevia').classList.add('hidden');
    document.getElementById('procesando').classList.remove('hidden');

    try {
        const response = await fetch('api/procesar_factura_ia.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ imagen: imagenCapturada })
        });

        const data = await response.json();

        if (data.success && data.productos.length > 0) {
            // Guardar automáticamente
            const responseGuardar = await fetch('api/guardar_productos_factura.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ productos: data.productos })
            });

            const dataGuardar = await responseGuardar.json();

            document.getElementById('procesando').classList.add('hidden');
            document.getElementById('exito').classList.remove('hidden');
        } else {
            alert('No se detectaron productos en la factura');
            cerrar();
        }
    } catch (error) {
        alert('Error al procesar la factura');
        cerrar();
    }
}

function cerrar() {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }
    document.getElementById('areaCaptura').classList.add('hidden');
    document.getElementById('vistaPrevia').classList.add('hidden');
    document.getElementById('camaraContainer').classList.add('hidden');
    document.getElementById('procesando').classList.add('hidden');
    document.getElementById('fileInput').value = '';
    imagenCapturada = null;
}
</script>
</body>
</html>
