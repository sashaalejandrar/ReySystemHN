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
    $Email = $row['Email'];
    $Celular = $row['Celular'];
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);

// Obtener proveedores para el selector
$proveedores = $conexion->query("SELECT * FROM proveedores ORDER BY Nombre");

// Obtener categorías únicas
$categorias = $conexion->query("SELECT DISTINCT Grupo FROM stock WHERE Grupo IS NOT NULL AND Grupo != '' ORDER BY Grupo");
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Creación de Productos en Lote - Rey System APP</title>
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
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>

<!-- Main Content -->
<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">

<!-- Page Heading -->
<div class="flex flex-wrap justify-between gap-4 mb-8">
    <div class="flex flex-col gap-2">
        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">
            📦 Creación de Productos en Lote
        </h1>
        <p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">
            Crea múltiples productos a la vez importando desde CSV o ingresando manualmente
        </p>
    </div>
    <div class="flex gap-3">
        <a href="templates/plantilla_creacion_productos.csv" download class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
            <span class="material-symbols-outlined">download</span>
            Descargar Plantilla CSV
        </a>
    </div>
</div>

<!-- Import Section -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Importar Datos</h3>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Importar CSV con Drag & Drop -->
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Importar desde CSV</label>
            <div id="dropZone" class="relative border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center cursor-pointer transition-all duration-200 hover:border-primary hover:bg-primary/5 dark:hover:bg-primary/10">
                <input 
                    type="file" 
                    id="csvFile" 
                    accept=".csv" 
                    class="hidden" 
                    onchange="importarCSV(this.files[0])">
                <div class="flex flex-col items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-3xl">upload_file</span>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-semibold text-primary">Click aquí</span> o arrastra el CSV
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-500">Solo archivos .csv</p>
                </div>
            </div>
        </div>
        
        <!-- Proveedor por defecto -->
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Proveedor (para todos)</label>
            <select 
                id="proveedorDefecto" 
                class="block w-full px-3 py-2 
                       text-gray-900 dark:text-white 
                       bg-gray-50 dark:bg-slate-800 
                       border border-gray-300 dark:border-gray-600 
                       rounded-lg 
                       focus:ring-2 focus:ring-primary focus:border-primary
                       hover:border-gray-400 dark:hover:border-gray-500
                       transition-colors">
                <option value="">Sin proveedor</option>
                <?php while($prov = $proveedores->fetch_assoc()): ?>
                <option value="<?= $prov['Nombre'] ?>"><?= $prov['Nombre'] ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <!-- Categoría por defecto -->
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Categoría (para todos)</label>
            <div class="relative">
                <select 
                    id="categoriaDefecto" 
                    class="block w-full px-3 py-2 pr-10
                           text-gray-900 dark:text-white 
                           bg-gray-50 dark:bg-slate-800 
                           border border-gray-300 dark:border-gray-600 
                           rounded-lg 
                           focus:ring-2 focus:ring-primary focus:border-primary
                           hover:border-gray-400 dark:hover:border-gray-500
                           transition-colors appearance-none cursor-pointer"
                    onchange="handleCategoriaChange(this)">
                    <option value="">Selecciona o crea nueva...</option>
                    <?php while($cat = $categorias->fetch_assoc()): ?>
                    <option value="<?= $cat['Grupo'] ?>"><?= $cat['Grupo'] ?></option>
                    <?php endwhile; ?>
                    <option value="__NUEVA__" class="font-semibold text-primary">➕ Crear nueva categoría...</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300">
                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                </div>
            </div>
        </div>
    </div>
    
    <div class="mt-4 flex gap-3">
        <button onclick="agregarFilaVacia()" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
            <span class="material-symbols-outlined">add</span>
            Agregar Fila Manual
        </button>
        <button onclick="pegarDesdeExcel()" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
            <span class="material-symbols-outlined">content_paste</span>
            Pegar desde Excel
        </button>
    </div>
</div>

<!-- Contenedor de Productos (Cards Verticales) -->
<div id="contenedorProductos" class="space-y-6 mb-6">
    <!-- Aquí se agregarán las cards de productos dinámicamente -->
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-8 text-center">
        <span class="material-symbols-outlined text-6xl text-gray-400 dark:text-gray-600 mb-4">inventory_2</span>
        <p class="text-gray-500 dark:text-gray-400 text-lg">No hay productos</p>
        <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Importa un CSV o agrega productos manualmente</p>
    </div>
</div>

<!-- Resumen -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-4 mb-6">
    <div id="resumenTabla" class="text-center text-sm text-gray-600 dark:text-gray-400">
        0 productos | 0 válidos | 0 errores
    </div>
</div>

<!-- Barra de Progreso (oculta inicialmente) -->
<div id="progresoContainer" class="hidden bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Procesando...</h3>
    <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
        <div id="barraProgreso" class="bg-primary h-6 rounded-full transition-all duration-300 flex items-center justify-center text-white text-sm font-bold" style="width: 0%">
            0%
        </div>
    </div>
    <p id="textoProgreso" class="text-sm text-gray-600 dark:text-gray-400 mt-2">Iniciando...</p>
</div>

<!-- Botones de Acción -->
<div class="flex gap-3 justify-end">
    <button onclick="limpiarTodo()" class="flex items-center gap-2 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
        <span class="material-symbols-outlined">delete</span>
        Limpiar Todo
    </button>
    <button onclick="mostrarVistaPrevia()" class="flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        <span class="material-symbols-outlined">visibility</span>
        Vista Previa
    </button>
    <button onclick="crearProductos()" id="btnCrear" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed">
        <span class="material-symbols-outlined">check_circle</span>
        Crear Productos
    </button>
</div>

</div>
</main>
</div>
</div>

<!-- Modal de Notificaciones -->
<div id="modalNotificacion" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111722] rounded-xl max-w-md w-full overflow-hidden shadow-2xl">
        <div id="notifHeader" class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <span id="notifIcon" class="material-symbols-outlined text-4xl"></span>
                <h3 id="notifTitulo" class="text-xl font-bold text-gray-900 dark:text-white"></h3>
            </div>
        </div>
        <div class="p-6">
            <p id="notifMensaje" class="text-gray-700 dark:text-gray-300"></p>
        </div>
        <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
            <button onclick="cerrarNotificacion()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                Entendido
            </button>
        </div>
    </div>
</div>

<!-- Modal de Vista Previa -->
<div id="modalVistaPrevia" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111722] rounded-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Vista Previa</h3>
                <button onclick="cerrarVistaPrevia()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>
        </div>
        <div id="contenidoVistaPrevia" class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
            <!-- Contenido dinámico -->
        </div>
    </div>
</div>

<script>
// ===================================
// DRAG & DROP PARA CSV
// ===================================
const dropZone = document.getElementById('dropZone');
const csvFileInput = document.getElementById('csvFile');

// Click en la zona abre el selector de archivos
dropZone.addEventListener('click', () => {
    csvFileInput.click();
});

// Prevenir comportamiento por defecto
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// Highlight cuando arrastras sobre la zona
['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, unhighlight, false);
});

function highlight(e) {
    dropZone.classList.add('border-primary', 'bg-primary/10', 'scale-105');
}

function unhighlight(e) {
    dropZone.classList.remove('border-primary', 'bg-primary/10', 'scale-105');
}

// Manejar el drop
dropZone.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        const file = files[0];
        
        // Validar que sea CSV
        if (file.name.endsWith('.csv')) {
            csvFileInput.files = files;
            importarCSV(file);
            
            // Feedback visual
            dropZone.innerHTML = `
                <div class="flex flex-col items-center gap-2">
                    <span class="material-symbols-outlined text-green-600 text-3xl">check_circle</span>
                    <p class="text-sm text-green-600 font-semibold">${file.name}</p>
                    <p class="text-xs text-gray-500">Archivo cargado correctamente</p>
                </div>
            `;
            
            // Restaurar después de 3 segundos
            setTimeout(() => {
                dropZone.innerHTML = `
                    <div class="flex flex-col items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-3xl">upload_file</span>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-semibold text-primary">Click aquí</span> o arrastra el CSV
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Solo archivos .csv</p>
                    </div>
                `;
            }, 3000);
        } else {
            // Error: no es CSV
            dropZone.innerHTML = `
                <div class="flex flex-col items-center gap-2">
                    <span class="material-symbols-outlined text-red-600 text-3xl">error</span>
                    <p class="text-sm text-red-600 font-semibold">Solo archivos .csv</p>
                    <p class="text-xs text-gray-500">Intenta de nuevo</p>
                </div>
            `;
            
            setTimeout(() => {
                dropZone.innerHTML = `
                    <div class="flex flex-col items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-3xl">upload_file</span>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-semibold text-primary">Click aquí</span> o arrastra el CSV
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Solo archivos .csv</p>
                    </div>
                `;
            }, 3000);
        }
    }
}
</script>

<script>
// Cargar proveedores y categorías globalmente al iniciar la página
window.proveedoresGlobales = [];
window.categoriasGlobales = [];

async function cargarProveedoresGlobales() {
    try {
        const response = await fetch('obtener_proveedores.php');
        const data = await response.json();
        if (data.success && data.proveedores) {
            window.proveedoresGlobales = data.proveedores;
            console.log('✅ Proveedores cargados:', window.proveedoresGlobales.length);
        }
    } catch (error) {
        console.error('Error al cargar proveedores:', error);
    }
}

async function cargarCategoriasGlobales() {
    try {
        const response = await fetch('api/obtener_categorias.php');
        const data = await response.json();
        if (data.success && data.categorias) {
            window.categoriasGlobales = data.categorias;
            console.log('✅ Categorías cargadas:', window.categoriasGlobales.length);
        }
    } catch (error) {
        console.error('Error al cargar categorías:', error);
    }
}

// Cargar datos al iniciar
cargarProveedoresGlobales();
cargarCategoriasGlobales();

// Cargar productos del escáner si vienen de escaneo_productos.php
window.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const fromScanner = urlParams.get('from');
    
    if (fromScanner === 'scanner') {
        const productosEscaneados = sessionStorage.getItem('productosEscaneados');
        
        if (productosEscaneados) {
            try {
                const productos = JSON.parse(productosEscaneados);
                console.log('📱 Cargando productos del escáner:', productos);
                
                // Agregar cada producto a la tabla
                productos.forEach(producto => {
                    tabla.agregarProducto({
                        codigo: producto.codigo || '',
                        nombre: producto.nombre || `Producto ${producto.codigo}`,
                        marca: producto.marca || '',
                        categoria: producto.categoria || '',
                        descripcion: producto.descripcion || '',
                        precioUnidad: producto.precioUnidad || 0,
                        costoEmpaque: producto.precioUnidad || 0,
                        unidadesPorEmpaque: 1,
                        tipoEmpaque: 'Unidad'
                    });
                });
                
                sessionStorage.removeItem('productosEscaneados');
                alert(`✅ Se agregaron ${productos.length} productos del escáner.\n\nRevisa y completa la información antes de guardar.`);
                
            } catch (error) {
                console.error('Error al cargar productos del escáner:', error);
            }
        }
    }
});

// Polling para recibir productos desde el móvil
let pollingInterval = null;

// Manejar cambio de categoría
function handleCategoriaChange(select) {
    if (select.value === '__NUEVA__') {
        const nuevaCategoria = prompt('Ingresa el nombre de la nueva categoría:');
        
        if (nuevaCategoria && nuevaCategoria.trim()) {
            const categoriaNombre = nuevaCategoria.trim();
            
            // Agregar la nueva opción antes de "Crear nueva"
            const nuevaOpcion = document.createElement('option');
            nuevaOpcion.value = categoriaNombre;
            nuevaOpcion.textContent = categoriaNombre;
            nuevaOpcion.selected = true;
            
            // Insertar antes de la última opción (__NUEVA__)
            const ultimaOpcion = select.options[select.options.length - 1];
            select.insertBefore(nuevaOpcion, ultimaOpcion);
            
            // Mostrar notificación
            showToast('Categoría creada', `"${categoriaNombre}" agregada al listado`);
        } else {
            // Si cancela, volver a la opción vacía
            select.value = '';
        }
    }
}

// Función para mostrar notificación toast
function showToast(title, message) {
    const toast = document.createElement('div');
    toast.className = 'fixed top-4 right-4 bg-gradient-to-r from-primary to-blue-600 text-white px-6 py-4 rounded-lg shadow-2xl z-50 max-w-sm transform translate-x-full transition-transform duration-300 ease-out';
    toast.style.transform = 'translateX(400px)';
    
    toast.innerHTML = `
        <div class=\"flex items-center gap-3\">
            <svg class=\"w-6 h-6 flex-shrink-0\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z\"></path>
            </svg>
            <div class=\"flex-1\">
                <p class=\"font-bold text-lg\">${title}</p>
                <p class=\"text-sm opacity-90\">${message}</p>
            </div>
            <button onclick=\"this.parentElement.parentElement.remove()\" class=\"flex-shrink-0 hover:bg-white/20 rounded-lg p-1 transition\">
                <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                    <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M6 18L18 6M6 6l12 12\"></path>
                </svg>
            </button>
        </div>
    `;
    
    document.body.appendChild(toast);
    
    // Animar entrada
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto-remover después de 5 segundos
    setTimeout(() => {
        toast.style.transform = 'translateX(400px)';
        setTimeout(() => toast.remove(), 500);
    }, 5000);
}

function checkScannerQueue() {
    fetch('api/scanner_queue.php?action=pull&t=' + Date.now())
        .then(response => response.json())
        .then(data => {
            // Solo mostrar en consola si hay datos reales
            if (data.success && data.hasData && data.productos && data.productos.length > 0) {
                console.log('📱 Productos recibidos del móvil:', data.productos);
                
                // Agregar productos a la tabla
                data.productos.forEach(producto => {
                    tabla.agregarProducto({
                        codigo: producto.codigo || '',
                        nombre: producto.nombre || `Producto ${producto.codigo}`,
                        marca: producto.marca || '',
                        categoria: producto.categoria || '',
                        descripcion: producto.descripcion || '',
                        precioUnidad: producto.precioUnidad || 0,
                        costoEmpaque: producto.precioUnidad || 0,
                        unidadesPorEmpaque: 1,
                        tipoEmpaque: 'Unidad'
                    });
                });
                
                // Mostrar notificación toast
                showToast('📱 Productos Recibidos', `${data.productos.length} ${data.productos.length === 1 ? 'producto recibido' : 'productos recibidos'} del móvil`);
                
                // Sonido de notificación
                try {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.value = 800;
                    oscillator.type = 'sine';
                    
                    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
                    
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.2);
                } catch (e) {
                    console.log('No se pudo reproducir sonido');
                }
            }
        })
        .catch(error => {
            // Solo mostrar errores reales, no problemas de conexión normales
            if (error.message !== 'Failed to fetch') {
                console.error('Error checking scanner queue:', error);
            }
        });
}

// Iniciar polling cuando la página esté lista
window.addEventListener('load', function() {
    // Primera verificación inmediata (silenciosa)
    checkScannerQueue();
    
    // Luego cada 10 segundos (reducido de 3 segundos)
    pollingInterval = setInterval(checkScannerQueue, 10000);
});

// Detener polling al cerrar/salir de la página
window.addEventListener('beforeunload', function() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        console.log('🛑 Polling detenido');
    }
});
</script>

<script src="js/tabla-editable-crear.js?v=<?= time() ?>"></script>
<script src="js/importador-csv.js?v=<?= time() ?>"></script>
</body>
</html>
