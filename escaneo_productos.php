<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

// Solo admin puede acceder
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
$row = $resultado->fetch_assoc();
$Rol = $row['Rol'];

if (strtolower($Rol) !== 'admin') {
    header('Location: index.php');
    exit;
}

$Nombre_Completo = $row['Nombre'] . " " . $row['Apellido'];
$Perfil = $row['Perfil'];
$rol_usuario = strtolower($Rol);
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title>Escaneo de Productos - Rey System</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- QuaggaJS para escaneo de códigos de barras -->
    <script src="https://cdn.jsdelivr.net/npm/@ericblade/quagga2/dist/quagga.min.js"></script>
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
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
        }
        #scanner-container {
            position: relative;
            width: 100%;
            max-width: 640px;
            margin: 0 auto;
        }
        #scanner-container video {
            width: 100%;
            height: auto;
            border-radius: 12px;
        }
        .drawingBuffer {
            position: absolute;
            top: 0;
            left: 0;
        }
        canvas.drawingBuffer {
            border-radius: 12px;
        }
        .scan-region {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            height: 100px;
            border: 3px solid #10b981;
            border-radius: 8px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.5);
            pointer-events: none;
        }
        .scan-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #10b981, transparent);
            animation: scan 2s linear infinite;
        }
        @keyframes scan {
            0%, 100% { top: 0; }
            50% { top: 100%; }
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200" x-data="scannerApp()">
    
    <div class="relative flex h-auto min-h-screen w-full flex-col">
        <div class="flex flex-1">
            <?php include 'menu_lateral.php'; ?>
            
            <main class="flex-1 flex flex-col">
                <div class="flex-1 p-6 lg:p-10">
                    <!-- Header -->
                    <div class="flex flex-wrap justify-between gap-4 mb-8">
                        <div class="flex flex-col gap-2">
                            <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">📱 Escaneo Inteligente</h1>
                            <p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">Escanea códigos de barras para verificar y crear productos automáticamente</p>
                        </div>
                    </div>

                    <!-- Controles de Cámara -->
                    <div class="mb-6 flex gap-2 sm:gap-3 flex-wrap">
                        <button @click="iniciarCamara()" x-show="!camaraActiva" 
                                class="flex-1 sm:flex-none px-4 sm:px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-xl font-semibold flex items-center justify-center gap-2 transition-all shadow-lg hover:shadow-xl text-sm sm:text-base">
                            <span class="material-symbols-outlined text-xl">videocam</span>
                            <span class="hidden sm:inline">Iniciar Cámara</span>
                            <span class="sm:hidden">Cámara</span>
                        </button>
                        <button @click="detenerCamara()" x-show="camaraActiva"
                                class="flex-1 sm:flex-none px-4 sm:px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl font-semibold flex items-center justify-center gap-2 transition-all shadow-lg hover:shadow-xl text-sm sm:text-base">
                            <span class="material-symbols-outlined text-xl">videocam_off</span>
                            <span class="hidden sm:inline">Detener</span>
                            <span class="sm:hidden">Detener</span>
                        </button>
                        <button @click="mostrarResumen()" x-show="productosEscaneados.length > 0"
                                class="flex-1 sm:flex-none px-4 sm:px-6 py-3 bg-primary hover:bg-primary/90 text-white rounded-xl font-semibold flex items-center justify-center gap-2 transition-all shadow-lg hover:shadow-xl text-sm sm:text-base">
                            <span class="material-symbols-outlined text-xl">summarize</span>
                            <span>Resumen (<span x-text="productosEscaneados.length"></span>)</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Columna Izquierda: Cámara -->
                        <div class="space-y-4 sm:space-y-6">
                            <!-- Contenedor de Cámara -->
                            <div x-show="camaraActiva" class="rounded-xl sm:rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-4 sm:p-6 shadow-lg">
                                <h2 class="text-lg sm:text-xl font-black mb-3 sm:mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                                    <span class="material-symbols-outlined text-green-500 text-xl sm:text-2xl">qr_code_scanner</span>
                                    Escáner Activo
                                </h2>
                                <div id="scanner-container" class="relative">
                                    <div class="scan-region">
                                        <div class="scan-line"></div>
                                    </div>
                                </div>
                                <p class="text-xs sm:text-sm text-center text-gray-500 dark:text-[#92a4c9] mt-3 sm:mt-4">
                                    Coloca el código de barras dentro del recuadro verde
                                </p>
                            </div>

                            <!-- Campo de Entrada Manual -->
                            <div class="rounded-xl sm:rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-4 sm:p-6 shadow-lg">
                                <h2 class="text-lg sm:text-xl font-black mb-3 sm:mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                                    <span class="material-symbols-outlined text-blue-500 text-xl sm:text-2xl">keyboard</span>
                                    Entrada Manual
                                </h2>
                                <p class="text-xs sm:text-sm text-gray-500 dark:text-[#92a4c9] mb-3 sm:mb-4">
                                    Ingresa el código de barras manualmente
                                </p>
                                <form @submit.prevent="procesarCodigoManual()" class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                                    <input 
                                        type="text" 
                                        x-model="codigoManual"
                                        placeholder="Ej: 7501234567890"
                                        class="flex-1 px-3 sm:px-4 py-3 rounded-lg sm:rounded-xl border-2 border-gray-200 dark:border-[#324467] bg-white dark:bg-[#111722] text-gray-900 dark:text-white focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all text-base"
                                        pattern="[0-9]*"
                                        inputmode="numeric"
                                    />
                                    <button 
                                        type="submit"
                                        class="px-6 py-3 bg-primary hover:bg-primary/90 text-white rounded-lg sm:rounded-xl font-bold flex items-center justify-center gap-2 transition-all shadow-lg hover:shadow-xl whitespace-nowrap">
                                        <span class="material-symbols-outlined">add</span>
                                        <span>Agregar</span>
                                    </button>
                                </form>
                            </div>

                            <!-- Último Escaneado -->
                            <div x-show="ultimoEscaneado" class="rounded-xl sm:rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-4 sm:p-6 shadow-lg">
                                <h3 class="text-base sm:text-lg font-black mb-2 sm:mb-3 text-gray-900 dark:text-white">🔍 Último Escaneado</h3>
                                <div x-show="ultimoEscaneado" class="p-3 sm:p-4 rounded-lg sm:rounded-xl border-2" 
                                     :class="{
                                         'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800': ultimoEscaneado?.estado === 'en_stock',
                                         'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800': ultimoEscaneado?.estado === 'creado_sin_stock',
                                         'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800': ultimoEscaneado?.estado === 'no_existe'
                                     }">
                                    <p class="font-semibold text-sm sm:text-base text-gray-900 dark:text-white" x-text="ultimoEscaneado?.mensaje"></p>
                                    <p class="text-xs sm:text-sm text-gray-600 dark:text-[#92a4c9] mt-1">
                                        Código: <span x-text="ultimoEscaneado?.codigo_barras"></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Columna Derecha: Lista de Productos -->
                        <div class="rounded-xl sm:rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-4 sm:p-6 shadow-lg">
                            <h2 class="text-lg sm:text-xl font-black mb-3 sm:mb-4 flex items-center gap-2 text-gray-900 dark:text-white">
                                <span class="material-symbols-outlined text-xl sm:text-2xl">inventory_2</span>
                                Productos (<span x-text="productosEscaneados.length"></span>)
                            </h2>

                            <div x-show="productosEscaneados.length === 0" class="text-center py-8 sm:py-12 text-gray-500 dark:text-[#92a4c9]">
                                <span class="material-symbols-outlined text-5xl sm:text-6xl mb-3 sm:mb-4 opacity-50">qr_code_2</span>
                                <p class="text-sm sm:text-base">No has escaneado ningún producto aún</p>
                            </div>

                            <div class="space-y-2 sm:space-y-3 max-h-[400px] sm:max-h-[600px] overflow-y-auto">
                                <template x-for="(producto, index) in productosEscaneados" :key="index">
                                    <div class="p-3 sm:p-4 rounded-lg sm:rounded-xl border-2 transition-all hover:shadow-md" 
                                         :class="{
                                             'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800': producto.estado === 'en_stock',
                                             'bg-yellow-50 dark:bg-yellow-900/10 border-yellow-200 dark:border-yellow-800': producto.estado === 'creado_sin_stock',
                                             'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800': producto.estado === 'no_existe'
                                         }">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="material-symbols-outlined text-sm flex-shrink-0"
                                                          :class="{
                                                              'text-green-600': producto.estado === 'en_stock',
                                                              'text-yellow-600': producto.estado === 'creado_sin_stock',
                                                              'text-red-600': producto.estado === 'no_existe'
                                                          }"
                                                          x-text="producto.estado === 'en_stock' ? 'check_circle' : (producto.estado === 'creado_sin_stock' ? 'warning' : 'cancel')"></span>
                                                    <span class="font-bold text-sm sm:text-base text-gray-900 dark:text-white truncate" x-text="producto.nombre"></span>
                                                </div>
                                                <p class="text-xs sm:text-sm text-gray-600 dark:text-[#92a4c9] truncate">
                                                    Código: <span x-text="producto.codigo_barras"></span>
                                                </p>
                                                <p class="text-xs font-medium mt-1" 
                                                   :class="{
                                                       'text-green-700 dark:text-green-400': producto.estado === 'en_stock',
                                                       'text-yellow-700 dark:text-yellow-400': producto.estado === 'creado_sin_stock',
                                                       'text-red-700 dark:text-red-400': producto.estado === 'no_existe'
                                                   }"
                                                   x-text="producto.estado === 'en_stock' ? `Stock: ${producto.stock_actual} unidades` : 
                                                           (producto.estado === 'creado_sin_stock' ? 'Sin stock' : 'Para crear')"></p>
                                            </div>
                                            <button @click="eliminarProducto(index)" class="text-gray-400 hover:text-red-500 transition flex-shrink-0">
                                                <span class="material-symbols-outlined text-xl">close</span>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Botones de Acción -->
                            <div x-show="productosEscaneados.length > 0" class="mt-4 sm:mt-6 space-y-2 sm:space-y-3">
                                <button @click="crearProductosNuevos()" 
                                        x-show="productosParaCrear.length > 0"
                                        class="w-full px-4 py-3 bg-primary hover:bg-primary/90 text-white rounded-lg sm:rounded-xl font-bold flex items-center justify-center gap-2 transition-all shadow-lg hover:shadow-xl text-sm sm:text-base">
                                    <span class="material-symbols-outlined">add_circle</span>
                                    Crear Productos (<span x-text="productosParaCrear.length"></span>)
                                </button>
                                <button @click="limpiarLista()"
                                        class="w-full px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg sm:rounded-xl font-semibold transition-all text-sm sm:text-base">
                                    Limpiar Lista
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de Resumen -->
                    <div x-show="modalResumen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="modalResumen = false">
                        <div class="rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                            <div class="p-6 border-b border-gray-200 dark:border-[#324467]">
                                <h2 class="text-2xl font-black text-gray-900 dark:text-white">📊 Resumen de Escaneo</h2>
                            </div>
                            <div class="p-6 overflow-y-auto flex-1">
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                    <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border-2 border-blue-200 dark:border-blue-800">
                                        <p class="text-sm text-gray-600 dark:text-[#92a4c9]">Total</p>
                                        <p class="text-2xl font-black text-gray-900 dark:text-white" x-text="productosEscaneados.length"></p>
                                    </div>
                                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-xl border-2 border-red-200 dark:border-red-800">
                                        <p class="text-sm text-gray-600 dark:text-[#92a4c9]">Para Crear</p>
                                        <p class="text-2xl font-black text-red-600" x-text="productosParaCrear.length"></p>
                                    </div>
                                    <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-xl border-2 border-yellow-200 dark:border-yellow-800">
                                        <p class="text-sm text-gray-600 dark:text-[#92a4c9]">Sin Stock</p>
                                        <p class="text-2xl font-black text-yellow-600" x-text="productosSinStock.length"></p>
                                    </div>
                                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-xl border-2 border-green-200 dark:border-green-800">
                                        <p class="text-sm text-gray-600 dark:text-[#92a4c9]">En Stock</p>
                                        <p class="text-2xl font-black text-green-600" x-text="productosEnStock.length"></p>
                                    </div>
                                </div>

                                <div class="overflow-x-auto rounded-xl border-2 border-gray-200 dark:border-[#324467]">
                                    <table class="w-full">
                                        <thead class="bg-gray-100 dark:bg-gray-800">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-sm font-black text-gray-900 dark:text-white">Código</th>
                                                <th class="px-4 py-3 text-left text-sm font-black text-gray-900 dark:text-white">Nombre</th>
                                                <th class="px-4 py-3 text-left text-sm font-black text-gray-900 dark:text-white">Estado</th>
                                                <th class="px-4 py-3 text-left text-sm font-black text-gray-900 dark:text-white">Stock</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="producto in productosEscaneados" :key="producto.codigo_barras">
                                                <tr class="border-b border-gray-200 dark:border-[#324467]">
                                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white" x-text="producto.codigo_barras"></td>
                                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white" x-text="producto.nombre"></td>
                                                    <td class="px-4 py-3">
                                                        <span class="px-2 py-1 text-xs rounded-full font-semibold"
                                                              :class="{
                                                                  'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': producto.estado === 'en_stock',
                                                                  'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': producto.estado === 'creado_sin_stock',
                                                                  'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': producto.estado === 'no_existe'
                                                              }"
                                                              x-text="producto.estado === 'en_stock' ? '✓ En Stock' : (producto.estado === 'creado_sin_stock' ? '⚠ Sin Stock' : '✗ No Existe')"></span>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white" x-text="producto.stock_actual || '-'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="p-6 border-t border-gray-200 dark:border-[#324467] flex gap-3">
                                <button @click="crearProductosNuevos()" x-show="productosParaCrear.length > 0"
                                        class="flex-1 px-4 py-3 bg-primary hover:bg-primary/90 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-xl">
                                    Crear Productos (<span x-text="productosParaCrear.length"></span>)
                                </button>
                                <button @click="modalResumen = false"
                                        class="px-6 py-3 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-xl font-semibold transition-all">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
                
                <!-- Footer -->
                <footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
                    <p class="text-gray-500 dark:text-[#92a4c9]">Versión 1.0.0</p>
                    <a class="text-primary hover:underline" href="#">Ayuda y Soporte</a>
                </footer>
            </main>
        </div>
    </div>

    <script src="js/barcode-scanner.js?v=<?php echo time(); ?>"></script>
</body>
</html>
