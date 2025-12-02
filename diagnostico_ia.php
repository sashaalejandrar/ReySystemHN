<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

// Obtener datos del usuario
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Nombre_Completo = $row['Nombre']." ".$row['Apellido'];
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);

// Verificar que solo admin pueda acceder
if ($rol_usuario !== 'admin') {
    header('Location: index.php');
    exit;
}

$conexion->close();
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Diagnóstico IA - Rey System APP</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
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
                    fontFamily: { "display": ["Manrope", "sans-serif"] }
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
        }
        [x-cloak] { display: none !important; }
    </style>
    <?php include "pwa-head.php"; ?>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200" x-data="diagnosticoIA()" x-init="init()">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">
    <!-- Header -->
    <div class="flex flex-wrap justify-between gap-4 mb-8">
        <div class="flex flex-col gap-2">
            <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">🤖 Diagnóstico con IA</h1>
            <p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">Detección y corrección automática de errores con Inteligencia Artificial</p>
        </div>
        <div class="flex items-center gap-3">
            <!-- Selector de Modo de Escaneo -->
            <div class="flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-[#192233] rounded-lg border border-gray-300 dark:border-[#324467]">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Modo:</span>
                <select x-model="modoEscaneo" class="bg-transparent text-sm font-semibold text-gray-900 dark:text-white focus:ring-0 cursor-pointer" style="border: none; outline: none;">
                    <option value="rapido" class="text-gray-900 bg-white">Rápido (5 archivos)</option>
                    <option value="completo" class="text-gray-900 bg-white">Completo (todos)</option>
                </select>
            </div>
            
            <button @click="iniciarEscaneo" :disabled="escaneando"
                    class="flex items-center gap-2 px-5 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                <span class="material-symbols-outlined" x-show="!escaneando">search</span>
                <svg x-show="escaneando" class="animate-spin h-5 w-5" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                <span x-text="escaneando ? 'Escaneando...' : 'Escanear Sistema'"></span>
            </button>
            <button x-show="escaneando" @click="detenerEscaneo"
                    class="flex items-center gap-2 px-5 py-3 bg-red-600 text-white rounded-lg font-bold hover:bg-red-700 transition shadow-md">
                <span class="material-symbols-outlined">stop_circle</span>
                <span>Detener</span>
            </button>
        </div>
    </div>

    <!-- Indicador de Progreso -->
    <div x-show="escaneando" x-cloak class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex items-center gap-3 mb-3">
            <svg class="animate-spin h-5 w-5 text-blue-600" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
            <div class="flex-1">
                <p class="text-sm font-semibold text-blue-900 dark:text-blue-100">Escaneando sistema...</p>
                <p class="text-xs text-blue-700 dark:text-blue-300" x-text="modoEscaneo === 'completo' ? 'Análisis completo en progreso. Esto puede tomar varios minutos.' : 'Analizando archivos críticos con IA. Esto puede tomar 1-2 minutos.'"></p>
            </div>
        </div>
        <!-- Barra de progreso -->
        <div x-show="progresoTotal > 0" class="w-full bg-blue-200 dark:bg-blue-900 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                 :style="`width: ${(progresoActual / progresoTotal * 100)}%`"></div>
        </div>
        <p x-show="progresoTotal > 0" class="text-xs text-blue-600 dark:text-blue-400 mt-1 text-right" 
           x-text="`${progresoActual} / ${progresoTotal} archivos`"></p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm font-medium">Errores Críticos</p>
                    <p class="text-3xl font-bold mt-1" x-text="stats.criticos"></p>
                </div>
                <span class="material-symbols-outlined text-5xl opacity-30">error</span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm font-medium">Advertencias</p>
                    <p class="text-3xl font-bold mt-1" x-text="stats.advertencias"></p>
                </div>
                <span class="material-symbols-outlined text-5xl opacity-30">warning</span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Compatibilidad</p>
                    <p class="text-3xl font-bold mt-1" x-text="stats.compatibilidad"></p>
                </div>
                <span class="material-symbols-outlined text-5xl opacity-30">devices</span>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Correcciones</p>
                    <p class="text-3xl font-bold mt-1" x-text="stats.corregidos"></p>
                </div>
                <span class="material-symbols-outlined text-5xl opacity-30">check_circle</span>
            </div>
        </div>
    </div>

    <!-- Errores Detectados -->
    <div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Errores Detectados</h2>
            <div class="flex gap-2">
                <button @click="filtro = 'todos'" :class="filtro === 'todos' ? 'bg-primary text-white' : 'bg-gray-200 dark:bg-[#324467] text-gray-900 dark:text-white'"
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-all">Todos</button>
                <button @click="filtro = 'critico'" :class="filtro === 'critico' ? 'bg-red-600 text-white' : 'bg-gray-200 dark:bg-[#324467] text-gray-900 dark:text-white'"
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-all">Críticos</button>
                <button @click="filtro = 'advertencia'" :class="filtro === 'advertencia' ? 'bg-yellow-600 text-white' : 'bg-gray-200 dark:bg-[#324467] text-gray-900 dark:text-white'"
                        class="px-4 py-2 rounded-lg text-sm font-semibold transition-all">Advertencias</button>
            </div>
        </div>

        <div x-show="errores.length === 0" class="text-center py-12">
            <span class="material-symbols-outlined text-gray-300 dark:text-gray-600 text-6xl mb-4">check_circle</span>
            <p class="text-gray-500 dark:text-[#92a4c9] text-lg">No se han detectado errores</p>
            <p class="text-gray-400 dark:text-gray-600 text-sm mt-2">Ejecuta un escaneo para analizar el sistema</p>
        </div>

        <div class="space-y-4">
            <template x-for="(error, index) in erroresFiltrados" :key="index">
                <div class="border-2 rounded-lg p-4 transition-all hover:shadow-md"
                     :class="{
                         'border-red-500 bg-red-50 dark:bg-red-900/20': error.nivel === 'critico',
                         'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20': error.nivel === 'advertencia',
                         'border-blue-500 bg-blue-50 dark:bg-blue-900/20': error.nivel === 'info'
                     }">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="material-symbols-outlined text-2xl"
                                      :class="{
                                          'text-red-600': error.nivel === 'critico',
                                          'text-yellow-600': error.nivel === 'advertencia',
                                          'text-blue-600': error.nivel === 'info'
                                      }"
                                      x-text="error.nivel === 'critico' ? 'error' : (error.nivel === 'advertencia' ? 'warning' : 'info')"></span>
                                <h3 class="font-bold text-gray-900 dark:text-white" x-text="error.titulo"></h3>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold"
                                      :class="{
                                          'bg-red-600 text-white': error.nivel === 'critico',
                                          'bg-yellow-600 text-white': error.nivel === 'advertencia',
                                          'bg-blue-600 text-white': error.nivel === 'info'
                                      }"
                                      x-text="error.nivel.toUpperCase()"></span>
                            </div>
                            <p class="text-gray-700 dark:text-gray-300 text-sm mb-2" x-text="error.descripcion"></p>
                            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <span class="material-symbols-outlined text-sm">folder</span>
                                <span x-text="error.archivo"></span>
                            </div>
                            <div x-show="error.solucion" class="mt-3 p-3 bg-white dark:bg-[#111722] rounded-lg border border-gray-200 dark:border-[#324467]">
                                <p class="text-sm font-semibold text-green-600 dark:text-green-400 mb-1">💡 Solución Sugerida:</p>
                                <p class="text-sm text-gray-700 dark:text-gray-300" x-text="error.solucion"></p>
                            </div>
                        </div>
                        <div class="flex flex-col gap-2 ml-4">
                            <button @click="corregirError(index)" 
                                    :disabled="error.corrigiendo || false"
                                    :class="error.corrigiendo ? 'opacity-50 cursor-not-allowed' : ''"
                                    class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700 transition-all">
                                <span x-show="!error.corrigiendo">Corregir</span>
                                <span x-show="error.corrigiendo">Corrigiendo...</span>
                            </button>
                            <button @click="ignorarError(index)"
                                    class="px-4 py-2 bg-gray-200 dark:bg-[#324467] text-gray-900 dark:text-white rounded-lg text-sm font-semibold hover:bg-gray-300 dark:hover:bg-[#3d5578] transition-all">
                                Ignorar
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Historial de Correcciones -->
    <div class="bg-white dark:bg-[#192233] rounded-xl shadow-sm p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Historial de Correcciones</h2>
        <div x-show="historial.length === 0" class="text-center py-8">
            <p class="text-gray-500 dark:text-[#92a4c9]">No hay correcciones aplicadas aún</p>
        </div>
        <div class="space-y-3">
            <template x-for="(item, index) in historial" :key="index">
                <div class="p-4 bg-gray-50 dark:bg-[#111722] rounded-lg border border-gray-200 dark:border-[#324467]">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-3 flex-1">
                            <span class="material-symbols-outlined text-green-600 mt-1">check_circle</span>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900 dark:text-white text-sm" x-text="item.titulo"></p>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1" x-text="item.descripcion"></p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs">folder</span>
                                        <span x-text="item.archivo"></span>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span class="material-symbols-outlined text-xs">person</span>
                                        <span x-text="item.usuario || 'Sistema'"></span>
                                    </span>
                                    <span x-text="item.fecha"></span>
                                </div>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full ml-3" x-text="item.proveedor"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
    <p class="text-gray-500 dark:text-[#92a4c9]">Diagnóstico IA v1.0.0 - Powered by Mistral & Groq</p>
</footer>
</main>
</div>
</div>

<script>
function diagnosticoIA() {
    return {
        escaneando: false,
        abortController: null,
        modoEscaneo: 'rapido', // 'rapido' o 'completo'
        progresoActual: 0,
        progresoTotal: 0,
        filtro: 'todos',
        stats: {
            criticos: 0,
            advertencias: 0,
            compatibilidad: 0,
            corregidos: 0
        },
        errores: [],
        historial: [],

        init() {
            console.log('Módulo de Diagnóstico IA inicializado');
            this.cargarHistorial();
        },

        get erroresFiltrados() {
            if (this.filtro === 'todos') return this.errores;
            return this.errores.filter(e => e.nivel === this.filtro);
        },

        async cargarHistorial() {
            try {
                const response = await fetch('api/diagnostico_historial.php?limite=20');
                const data = await response.json();
                
                if (data.success) {
                    this.historial = data.historial || [];
                    this.stats.corregidos = data.total || 0;
                } else {
                    // Fallback a localStorage si falla la DB
                    const saved = localStorage.getItem('diagnostico_historial');
                    if (saved) {
                        this.historial = JSON.parse(saved);
                        this.stats.corregidos = this.historial.length;
                    }
                }
            } catch (error) {
                console.error('Error al cargar historial:', error);
                // Fallback a localStorage
                const saved = localStorage.getItem('diagnostico_historial');
                if (saved) {
                    this.historial = JSON.parse(saved);
                    this.stats.corregidos = this.historial.length;
                }
            }
        },

        async iniciarEscaneo() {
            this.escaneando = true;
            this.errores = [];
            this.progresoActual = 0;
            this.progresoTotal = 0;
            this.abortController = new AbortController();

            try {
                if (this.modoEscaneo === 'completo') {
                    // Escaneo completo por lotes
                    await this.escaneoCompleto();
                } else {
                    // Escaneo rápido
                    const response = await fetch('api/diagnostico_escanear.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ modo: 'rapido' }),
                        signal: this.abortController.signal
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.errores = data.errores || [];
                        this.actualizarStats();
                        mostrarExito(`Escaneo completado: ${this.errores.length} problemas detectados`);
                    } else {
                        mostrarError(data.message || 'Error al escanear');
                    }
                }
            } catch (error) {
                if (error.name === 'AbortError') {
                    mostrarAdvertencia('Escaneo detenido por el usuario');
                } else {
                    console.error('Error:', error);
                    mostrarError('Error de comunicación con el servidor');
                }
            } finally {
                this.escaneando = false;
                this.abortController = null;
                this.progresoActual = 0;
                this.progresoTotal = 0;
            }
        },

        async escaneoCompleto() {
            let lote = 0;
            let hayMasArchivos = true;

            while (hayMasArchivos && !this.abortController.signal.aborted) {
                const response = await fetch('api/diagnostico_escanear.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        modo: 'completo',
                        lote: lote
                    }),
                    signal: this.abortController.signal
                });

                const data = await response.json();

                if (data.success) {
                    // Agregar nuevos errores
                    if (data.errores && data.errores.length > 0) {
                        this.errores.push(...data.errores);
                        this.actualizarStats();
                    }

                    // Actualizar progreso
                    this.progresoActual = data.archivos_procesados || 0;
                    this.progresoTotal = data.total_archivos || 0;
                    hayMasArchivos = data.hay_mas || false;

                    console.log(`Lote ${lote}: ${data.errores?.length || 0} errores encontrados`);
                    lote++;

                    // Pequeña pausa entre lotes para no saturar
                    if (hayMasArchivos) {
                        await new Promise(resolve => setTimeout(resolve, 1000));
                    }
                } else {
                    mostrarError(data.message || 'Error en el escaneo');
                    break;
                }
            }

            if (!this.abortController.signal.aborted) {
                mostrarExito(`Escaneo completo finalizado: ${this.errores.length} problemas detectados en ${this.progresoTotal} archivos`);
            }
        },

        detenerEscaneo() {
            if (this.abortController) {
                this.abortController.abort();
                mostrarInfo('Deteniendo escaneo...');
            }
        },

        async corregirError(index) {
            const error = this.errores[index];
            
            // Asegurarse de que la propiedad existe y es reactiva
            if (!error.hasOwnProperty('corrigiendo')) {
                error.corrigiendo = false;
            }
            
            error.corrigiendo = true;
            console.log('Corrigiendo error:', error);

            try {
                const response = await fetch('api/diagnostico_corregir.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(error)
                });

                const data = await response.json();
                console.log('Respuesta de corrección:', data);

                if (data.success) {
                    mostrarExito(data.message || 'Error corregido exitosamente');
                    this.historial.unshift({
                        titulo: error.titulo,
                        fecha: new Date().toLocaleString('es-HN'),
                        proveedor: data.proveedor || 'IA'
                    });
                    this.errores.splice(index, 1);
                    this.stats.corregidos++;
                    this.actualizarStats();
                    this.guardarHistorial();
                } else {
                    mostrarError(data.message || 'No se pudo corregir el error');
                    error.corrigiendo = false;
                }
            } catch (err) {
                console.error('Error al corregir:', err);
                mostrarError('Error de comunicación con el servidor');
                error.corrigiendo = false;
            }
        },

        ignorarError(index) {
            this.errores.splice(index, 1);
            this.actualizarStats();
            mostrarExito('Error ignorado');
        },

        actualizarStats() {
            this.stats.criticos = this.errores.filter(e => e.nivel === 'critico').length;
            this.stats.advertencias = this.errores.filter(e => e.nivel === 'advertencia').length;
            this.stats.compatibilidad = this.errores.filter(e => e.tipo === 'compatibilidad').length;
        },

        guardarHistorial() {
            // Guardar también en localStorage como backup
            localStorage.setItem('diagnostico_historial', JSON.stringify(this.historial));
            // Recargar desde la base de datos para tener datos actualizados
            this.cargarHistorial();
        }
    };
}
</script>

<?php include 'modal_sistema.php'; ?>
</body>
</html>
