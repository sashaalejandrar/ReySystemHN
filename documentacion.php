<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Obtener datos del usuario
$query_usuario = "SELECT * FROM usuarios WHERE usuario = ?";
$stmt_usuario = $conexion->prepare($query_usuario);
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
 $rol_usuario = strtolower($Rol);
$stmt_usuario->close();

$esAdmin = strtolower($Rol) === 'admin';
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Documentación del Sistema - ReySystem</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
</style>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#1152d4",
                    "background-dark": "#101622",
                }
            }
        }
    }
</script>
<?php include "pwa-head.php"; ?>
</head>
<body class="bg-gray-50 dark:bg-[#101622]">

<div class="flex min-h-screen">
    <?php include "menu_lateral.php"; ?>

    <div class="flex-1 p-6 lg:p-10">
        <div x-data="documentacionApp()" x-init="init()">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📚 Documentación del Sistema</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Guía completa de todos los módulos de ReySystem</p>
                </div>
            
            <?php if ($esAdmin): ?>
            <div class="flex gap-3">
                <button @click="escanearModulos()" 
                        :disabled="generando"
                        class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    <span class="material-symbols-outlined">refresh</span>
                    <span>Escanear Módulos</span>
                </button>
                <button @click="generarTodo()" 
                        :disabled="generando"
                        x-show="!generando"
                        class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50">
                    <span class="material-symbols-outlined">auto_awesome</span>
                    <span>Generar Todo con IA</span>
                </button>
                <button @click="detenerGeneracion()" 
                        x-show="generando"
                        class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors animate-pulse">
                    <span class="material-symbols-outlined">stop_circle</span>
                    <span>Detener Generación</span>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Barra de progreso -->
        <div x-show="generando" class="bg-white dark:bg-[#192233] rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Generando Documentación...</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400" x-text="progresoTexto"></p>
                </div>
                <span class="text-2xl font-bold text-primary" x-text="progresoPorcentaje + '%'"></span>
            </div>
            
            <!-- Barra de progreso -->
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-4">
                <div class="bg-gradient-to-r from-blue-500 to-green-500 h-3 rounded-full transition-all duration-300" 
                     :style="`width: ${progresoPorcentaje}%`"></div>
            </div>
            
            <!-- Último módulo generado -->
            <div x-show="ultimoModuloGenerado" class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                <span class="material-symbols-outlined text-green-500 animate-pulse">check_circle</span>
                <span>Último generado: <strong x-text="ultimoModuloGenerado"></strong></span>
            </div>
        </div>

        <!-- Búsqueda y Filtros -->
        <div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Buscar</label>
                    <input type="text" 
                           x-model="busqueda" 
                           @input="cargarDocumentacion()"
                           placeholder="Buscar en documentación..."
                           class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Categoría</label>
                    <select x-model="categoriaFiltro" 
                            @change="cargarDocumentacion()"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                        <option value="">Todas las categorías</option>
                        <template x-for="cat in categorias" :key="cat.categoria">
                            <option :value="cat.categoria" x-text="`${cat.categoria} (${cat.total})`"></option>
                        </template>
                    </select>
                </div>
            </div>
        </div>

        <!-- Lista de Documentación -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sidebar con lista -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm p-6 sticky top-6">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Módulos</h2>
                    
                    <div x-show="cargando" class="text-center py-8">
                        <span class="material-symbols-outlined text-4xl animate-spin text-gray-400">progress_activity</span>
                    </div>
                    
                    <div x-show="!cargando" class="space-y-2 max-h-[600px] overflow-y-auto">
                        <template x-for="doc in documentacion" :key="doc.id">
                            <div @click="seleccionarDoc(doc)" 
                                 :class="{'bg-primary/10 border-primary': docSeleccionado?.id === doc.id}"
                                 class="p-3 rounded-lg border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <div class="flex items-start gap-2">
                                    <span class="material-symbols-outlined text-primary mt-0.5">description</span>
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-sm text-gray-900 dark:text-white truncate" x-text="doc.nombre_modulo"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="doc.categoria"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <div x-show="documentacion.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <span class="material-symbols-outlined text-5xl mb-2">search_off</span>
                            <p>No se encontró documentación</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido principal -->
            <div class="lg:col-span-2">
                <div x-show="!docSeleccionado" class="bg-white dark:bg-[#192233] rounded-xl shadow-sm p-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-gray-300 dark:text-gray-600 mb-4">menu_book</span>
                    <p class="text-gray-500 dark:text-gray-400">Selecciona un módulo para ver su documentación</p>
                </div>

                <div x-show="docSeleccionado" class="bg-white dark:bg-[#192233] rounded-xl shadow-sm">
                    <!-- Header del módulo -->
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white" x-text="docSeleccionado?.nombre_modulo"></h2>
                                    <span class="px-3 py-1 bg-primary/10 text-primary rounded-full text-sm font-medium" x-text="docSeleccionado?.categoria"></span>
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <span class="material-symbols-outlined text-xs">folder</span>
                                    <span x-text="docSeleccionado?.ruta_archivo"></span>
                                </p>
                            </div>
                            
                            <?php if ($esAdmin): ?>
                            <button @click="editarDoc()" 
                                    class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700">
                                <span class="material-symbols-outlined text-sm">edit</span>
                                <span>Editar</span>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6 space-y-6">
                        <!-- Descripción -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">info</span>
                                Descripción
                            </h3>
                            <p class="text-gray-700 dark:text-white" x-text="docSeleccionado?.descripcion"></p>
                        </div>

                        <!-- Propósito -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">target</span>
                                Propósito
                            </h3>
                            <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-white" x-html="marked.parse(docSeleccionado?.proposito || '')"></div>
                        </div>

                        <!-- Cómo usar -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">help</span>
                                Cómo Usar
                            </h3>
                            <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-white" x-html="marked.parse(docSeleccionado?.como_usar || '')"></div>
                        </div>

                        <!-- Ejemplos -->
                        <div x-show="docSeleccionado?.ejemplos">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary">code</span>
                                Ejemplos
                            </h3>
                            <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-white" x-html="marked.parse(docSeleccionado?.ejemplos || '')"></div>
                        </div>

                        <!-- Metadata -->
                        <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Permisos Requeridos</p>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="docSeleccionado?.permisos_requeridos || 'N/A'"></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Última Actualización</p>
                                <p class="font-medium text-gray-900 dark:text-white" x-text="formatearFecha(docSeleccionado?.ultima_actualizacion)"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function documentacionApp() {
    return {
        documentacion: [],
        categorias: [],
        docSeleccionado: null,
        busqueda: '',
        categoriaFiltro: '',
        cargando: false,
        generando: false,
        abortController: null,
        progresoPorcentaje: 0,
        progresoTexto: '',
        ultimoModuloGenerado: '',

        async init() {
            await this.cargarCategorias();
            await this.cargarDocumentacion();
        },

        async cargarCategorias() {
            try {
                const response = await fetch('api/documentacion.php?action=categorias');
                const data = await response.json();
                if (data.success) {
                    this.categorias = data.categorias;
                }
            } catch (error) {
                console.error('Error al cargar categorías:', error);
            }
        },

        async cargarDocumentacion() {
            this.cargando = true;
            try {
                let url = 'api/documentacion.php?action=list';
                if (this.categoriaFiltro) url += `&categoria=${encodeURIComponent(this.categoriaFiltro)}`;
                if (this.busqueda) url += `&busqueda=${encodeURIComponent(this.busqueda)}`;

                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    this.documentacion = data.documentacion;
                }
            } catch (error) {
                console.error('Error al cargar documentación:', error);
            } finally {
                this.cargando = false;
            }
        },

        seleccionarDoc(doc) {
            this.docSeleccionado = doc;
        },

        escanearModulos() {
            mostrarConfirmacion(
                '¿Escanear y generar documentación? Esto regenerará la documentación de los módulos.',
                () => this.ejecutarEscaneo(),
                null
            );
        },

        async ejecutarEscaneo() {
            this.generando = true;
            this.abortController = new AbortController();
            this.progresoPorcentaje = 0;
            this.ultimoModuloGenerado = '';
            
            try {
                // Primero escanear
                const scanResponse = await fetch('api/generar_documentacion.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=scan',
                    signal: this.abortController.signal
                });
                const scanData = await scanResponse.json();
                
                if (!scanData.success) {
                    mostrarError(scanData.message || 'Error al escanear');
                    this.generando = false;
                    return;
                }

                const modulos = scanData.modulos.slice(0, 10);
                const total = modulos.length;
                let generados = 0;
                let errores = 0;

                this.progresoTexto = `Generando 0 de ${total} módulos...`;

                for (let i = 0; i < modulos.length; i++) {
                    if (this.abortController.signal.aborted) {
                        mostrarAdvertencia('Generación cancelada por el usuario');
                        break;
                    }
                    
                    const modulo = modulos[i];
                    
                    try {
                        const genResponse = await fetch('api/generar_documentacion.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: `action=generate&modulo=${encodeURIComponent(JSON.stringify(modulo))}`,
                            signal: this.abortController.signal
                        });
                        const genData = await genResponse.json();
                        
                        if (genData.success) {
                            generados++;
                            this.ultimoModuloGenerado = genData.documentacion?.nombre_modulo || modulo.nombre;
                            
                            // Recargar documentación en tiempo real
                            await this.cargarDocumentacion();
                        } else {
                            errores++;
                        }
                    } catch (e) {
                        if (e.name === 'AbortError') {
                            break;
                        }
                        errores++;
                    }
                    
                    // Actualizar progreso
                    this.progresoPorcentaje = Math.round(((i + 1) / total) * 100);
                    this.progresoTexto = `Generando ${i + 1} de ${total} módulos...`;
                }

                if (!this.abortController.signal.aborted) {
                    mostrarExito(`✅ Generados: ${generados} | ❌ Errores: ${errores}`);
                }

            } catch (error) {
                if (error.name !== 'AbortError') {
                    mostrarError('Error al generar documentación');
                    console.error(error);
                }
            } finally {
                this.generando = false;
                this.abortController = null;
                this.progresoPorcentaje = 0;
            }
        },

        generarTodo() {
            mostrarConfirmacion(
                '¿Generar documentación automática para todos los módulos? Esto puede tardar varios minutos.',
                () => this.ejecutarGenerarTodo(),
                null
            );
        },

        async ejecutarGenerarTodo() {
            this.generando = true;
            this.abortController = new AbortController();
            
            try {
                const response = await fetch('api/generar_documentacion.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=generate_all',
                    signal: this.abortController.signal
                });
                const data = await response.json();
                
                if (data.success) {
                    mostrarExito(data.message);
                    await this.cargarDocumentacion();
                } else {
                    mostrarError(data.message);
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    mostrarAdvertencia('Generación cancelada por el usuario');
                } else {
                    mostrarError('Error al generar documentación');
                }
            } finally {
                this.generando = false;
                this.abortController = null;
            }
        },

        detenerGeneracion() {
            if (this.abortController) {
                this.abortController.abort();
                this.generando = false;
                this.abortController = null;
                mostrarAdvertencia('Generación detenida por el usuario');
            }
        },

        editarDoc() {
            // TODO: Implementar modal de edición
            mostrarInfo('Función de edición en desarrollo');
        },

        formatearFecha(fecha) {
            if (!fecha) return 'N/A';
            return new Date(fecha).toLocaleString('es-HN');
        }
    };
}
</script>

<?php include 'modal_sistema.php'; ?>
</div> <!-- Cierre del contenedor flex principal -->
</body>
</html>
