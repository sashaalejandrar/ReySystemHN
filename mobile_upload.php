<?php
session_start();
include 'funciones.php';

// Permitir acceso desde cualquier origen (para móviles)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Obtener el session_id de la URL
$session_id = isset($_GET['session']) ? $_GET['session'] : '';

if (empty($session_id)) {
    die('Sesión no válida');
}

// Procesar la subida de archivos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['recibos'])) {
    $upload_dir = 'uploads/recibos_temp/' . $session_id . '/';
    
    // Crear directorio si no existe
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $uploaded_files = [];
    $errors = [];
    
    foreach ($_FILES['recibos']['tmp_name'] as $key => $tmp_name) {
        $file_name = $_FILES['recibos']['name'][$key];
        $file_size = $_FILES['recibos']['size'][$key];
        $file_tmp = $_FILES['recibos']['tmp_name'][$key];
        $file_error = $_FILES['recibos']['error'][$key];
        
        if ($file_error === UPLOAD_ERR_OK) {
            if ($file_size <= 5 * 1024 * 1024) { // 5MB max
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
                
                if (in_array($file_ext, $allowed_extensions)) {
                    $new_file_name = uniqid() . '_' . $file_name;
                    $destination = $upload_dir . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $destination)) {
                        $uploaded_files[] = $new_file_name;
                    } else {
                        $errors[] = "Error al mover el archivo: $file_name";
                    }
                } else {
                    $errors[] = "Extensión no permitida: $file_name";
                }
            } else {
                $errors[] = "Archivo muy grande: $file_name";
            }
        } else {
            $errors[] = "Error al subir: $file_name";
        }
    }
    
    // Responder con JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => count($uploaded_files) > 0,
        'uploaded' => $uploaded_files,
        'errors' => $errors,
        'count' => count($uploaded_files)
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Subir Recibos - Móvil</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        #preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }
        .preview-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #374151;
        }
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .preview-item .remove-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100 min-h-screen p-4">
    <div class="max-w-lg mx-auto">
        <div class="text-center mb-6">
            <span class="material-symbols-outlined text-blue-500 text-5xl mb-2">receipt_long</span>
            <h1 class="text-2xl font-bold mb-2">Subir Recibos</h1>
            <p class="text-gray-400 text-sm">Toma fotos de tus recibos o selecciona archivos</p>
        </div>

        <div class="bg-gray-800 rounded-xl p-6 shadow-lg">
            <!-- Botón para tomar foto -->
            <button id="cameraBtn" class="w-full mb-4 flex items-center justify-center gap-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-2xl">photo_camera</span>
                <span>Tomar Foto</span>
            </button>

            <!-- Input de archivo (oculto) -->
            <input type="file" id="fileInput" accept="image/*,application/pdf" capture="environment" multiple class="hidden">
            
            <!-- Botón para seleccionar archivos -->
            <button id="fileBtn" class="w-full mb-4 flex items-center justify-center gap-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold py-4 px-6 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-2xl">upload_file</span>
                <span>Seleccionar Archivos</span>
            </button>

            <!-- Contenedor de vista previa -->
            <div id="preview-container"></div>

            <!-- Contador de archivos -->
            <div id="file-count" class="text-center text-gray-400 text-sm mt-4 hidden">
                <span id="count-text">0 archivos seleccionados</span>
            </div>

            <!-- Botón de subir -->
            <button id="uploadBtn" class="w-full mt-6 bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed hidden">
                <span class="material-symbols-outlined inline-block mr-2">cloud_upload</span>
                Subir Recibos
            </button>

            <!-- Mensaje de estado -->
            <div id="status-message" class="mt-4 p-4 rounded-lg hidden"></div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('fileInput');
        const cameraBtn = document.getElementById('cameraBtn');
        const fileBtn = document.getElementById('fileBtn');
        const uploadBtn = document.getElementById('uploadBtn');
        const previewContainer = document.getElementById('preview-container');
        const fileCount = document.getElementById('file-count');
        const countText = document.getElementById('count-text');
        const statusMessage = document.getElementById('status-message');
        
        let selectedFiles = [];

        // Botón de cámara
        cameraBtn.addEventListener('click', () => {
            fileInput.setAttribute('capture', 'environment');
            fileInput.click();
        });

        // Botón de archivos
        fileBtn.addEventListener('click', () => {
            fileInput.removeAttribute('capture');
            fileInput.click();
        });

        // Manejar selección de archivos
        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            files.forEach(file => {
                if (file.size <= 5 * 1024 * 1024) { // 5MB max
                    selectedFiles.push(file);
                    displayPreview(file);
                } else {
                    showStatus('error', `El archivo ${file.name} excede 5MB`);
                }
            });
            updateUI();
            fileInput.value = ''; // Reset input
        });

        // Mostrar vista previa
        function displayPreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="${file.name}">
                    <button class="remove-btn" onclick="removeFile('${file.name}')">×</button>
                `;
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        }

        // Eliminar archivo
        window.removeFile = function(fileName) {
            selectedFiles = selectedFiles.filter(f => f.name !== fileName);
            updateUI();
            renderPreviews();
        };

        // Re-renderizar vistas previas
        function renderPreviews() {
            previewContainer.innerHTML = '';
            selectedFiles.forEach(file => displayPreview(file));
        }

        // Actualizar UI
        function updateUI() {
            const count = selectedFiles.length;
            if (count > 0) {
                fileCount.classList.remove('hidden');
                uploadBtn.classList.remove('hidden');
                countText.textContent = `${count} archivo${count !== 1 ? 's' : ''} seleccionado${count !== 1 ? 's' : ''}`;
            } else {
                fileCount.classList.add('hidden');
                uploadBtn.classList.add('hidden');
            }
        }

        // Subir archivos
        uploadBtn.addEventListener('click', async () => {
            if (selectedFiles.length === 0) return;

            const formData = new FormData();
            selectedFiles.forEach(file => {
                formData.append('recibos[]', file);
            });

            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<span class="material-symbols-outlined inline-block mr-2 animate-spin">sync</span>Subiendo...';

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showStatus('success', `${data.count} archivo(s) subido(s) correctamente`);
                    selectedFiles = [];
                    previewContainer.innerHTML = '';
                    updateUI();
                    
                    // Cerrar ventana después de 2 segundos
                    setTimeout(() => {
                        window.close();
                    }, 2000);
                } else {
                    showStatus('error', 'Error al subir archivos: ' + data.errors.join(', '));
                }
            } catch (error) {
                showStatus('error', 'Error de conexión: ' + error.message);
            } finally {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<span class="material-symbols-outlined inline-block mr-2">cloud_upload</span>Subir Recibos';
            }
        });

        // Mostrar mensaje de estado
        function showStatus(type, message) {
            statusMessage.className = `mt-4 p-4 rounded-lg ${type === 'success' ? 'bg-green-900/40 border border-green-700 text-green-200' : 'bg-red-900/40 border border-red-700 text-red-200'}`;
            statusMessage.textContent = message;
            statusMessage.classList.remove('hidden');
            
            setTimeout(() => {
                statusMessage.classList.add('hidden');
            }, 5000);
        }
    </script>
</body>
</html>
