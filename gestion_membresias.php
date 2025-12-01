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
    $Usuario = $row['Usuario'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);

// Solo admin puede acceder
if ($rol_usuario !== 'admin') {
    header("Location: index.php");
    exit();
}

// Obtener membresías existentes
$membresias = $conexion->query("SELECT * FROM niveles_membresia ORDER BY puntos_minimos ASC");
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Gestión de Membresías - Rey System APP</title>
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
    </style>
    <script src="nova_rey.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                        🏆 Gestión de Membresías
                    </h1>
                    <p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">
                        Administra los niveles de membresía y sus beneficios
                    </p>
                </div>
                <div class="flex gap-3">
                    <button onclick="crearMembresia()" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        <span class="material-symbols-outlined">add</span>
                        Nueva Membresía
                    </button>
                    <button onclick="window.location.href='puntos_fidelidad.php'" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Volver a Puntos
                    </button>
                </div>
            </div>

            <!-- Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl p-6 text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="material-symbols-outlined text-3xl">info</span>
                        <h3 class="text-lg font-bold">Puntos Mínimos</h3>
                    </div>
                    <p class="text-sm opacity-90">Define cuántos puntos totales se necesitan para alcanzar cada nivel</p>
                </div>
                <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-xl p-6 text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="material-symbols-outlined text-3xl">trending_up</span>
                        <h3 class="text-lg font-bold">Multiplicador</h3>
                    </div>
                    <p class="text-sm opacity-90">Multiplica los puntos ganados en cada compra (1.0x - 5.0x)</p>
                </div>
                <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-xl p-6 text-white">
                    <div class="flex items-center gap-3 mb-2">
                        <span class="material-symbols-outlined text-3xl">percent</span>
                        <h3 class="text-lg font-bold">Descuento Extra</h3>
                    </div>
                    <p class="text-sm opacity-90">Descuento adicional en todas las compras (0% - 50%)</p>
                </div>
            </div>

            <!-- Membresías List -->
            <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">📋 Niveles de Membresía</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php while($membresia = $membresias->fetch_assoc()): ?>
                    <div class="bg-gray-50 dark:bg-[#0d1420] rounded-lg p-6 border-2 border-gray-200 dark:border-[#324467] hover:border-primary transition-all">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-4">
                            <span class="material-symbols-outlined text-4xl" style="color: <?php echo $membresia['color']; ?>">
                                <?php echo $membresia['icono']; ?>
                            </span>
                            <div class="flex gap-2">
                                <button onclick="editarMembresia(<?php echo $membresia['id']; ?>)" 
                                        class="p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <button onclick="eliminarMembresia(<?php echo $membresia['id']; ?>, '<?php echo $membresia['nivel']; ?>')" 
                                        class="p-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                    <span class="material-symbols-outlined text-sm">delete</span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Title -->
                        <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-4">
                            <?php echo $membresia['nivel']; ?>
                        </h3>
                        
                        <!-- Stats -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Puntos Mínimos</span>
                                <span class="font-bold text-gray-900 dark:text-white"><?php echo number_format($membresia['puntos_minimos']); ?></span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Multiplicador</span>
                                <span class="font-bold text-blue-600 dark:text-blue-400"><?php echo $membresia['multiplicador_puntos']; ?>x</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Descuento</span>
                                <span class="font-bold text-green-600 dark:text-green-400"><?php echo $membresia['descuento_adicional']; ?>%</span>
                            </div>
                        </div>
                        
                        <!-- Benefits -->
                        <?php if (!empty($membresia['beneficios'])): ?>
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                <?php echo htmlspecialchars($membresia['beneficios']); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
            <p class="text-gray-500 dark:text-[#92a4c9]">Sistema de Membresías v1.0</p>
            <a class="text-primary hover:underline" href="puntos_fidelidad.php">← Volver a Puntos</a>
        </footer>
    </main>
</div>
</div>

<script>
function crearMembresia() {
    Swal.fire({
        title: '<span class="text-2xl font-bold text-white">🏆 Nueva Membresía</span>',
        html: `
            <div class="text-left space-y-4 p-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">label</span>
                        Nombre del Nivel *
                    </label>
                    <input type="text" id="nivel" 
                           class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary"
                           placeholder="Ej: Diamante">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">stars</span>
                        Puntos Mínimos *
                    </label>
                    <input type="number" id="puntos_minimos" min="0" step="100"
                           class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary"
                           placeholder="1000">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <span class="material-symbols-outlined text-sm align-middle">trending_up</span>
                            Multiplicador *
                        </label>
                        <input type="number" id="multiplicador" min="1" max="5" step="0.25"
                               class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary"
                               placeholder="2.5">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <span class="material-symbols-outlined text-sm align-middle">percent</span>
                            Descuento (%)
                        </label>
                        <input type="number" id="descuento" min="0" max="50" step="0.5"
                               class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary"
                               placeholder="15">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <span class="material-symbols-outlined text-sm align-middle">palette</span>
                            Color (Hex)
                        </label>
                        <input type="color" id="color"
                               class="w-full h-10 border border-gray-600 rounded-lg bg-gray-800 cursor-pointer"
                               value="#9333ea">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <span class="material-symbols-outlined text-sm align-middle">emoji_events</span>
                            Icono
                        </label>
                        <select id="icono" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary">
                            <option value="military_tech">🎖️ Medalla</option>
                            <option value="workspace_premium">👑 Corona</option>
                            <option value="diamond">💎 Diamante</option>
                            <option value="star">⭐ Estrella</option>
                            <option value="emoji_events">🏆 Trofeo</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">description</span>
                        Beneficios
                    </label>
                    <textarea id="beneficios" rows="2"
                              class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary"
                              placeholder="Describe los beneficios especiales..."></textarea>
                </div>
            </div>
        `,
        width: '600px',
        background: '#1a1f2e',
        color: '#ffffff',
        showCancelButton: true,
        confirmButtonText: '<span class="material-symbols-outlined text-sm align-middle">add</span> Crear Membresía',
        cancelButtonText: '<span class="material-symbols-outlined text-sm align-middle">close</span> Cancelar',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        customClass: {
            popup: 'rounded-xl border border-gray-700'
        },
        preConfirm: () => {
            const nivel = document.getElementById('nivel').value;
            const puntos_minimos = document.getElementById('puntos_minimos').value;
            const multiplicador = document.getElementById('multiplicador').value;
            const descuento = document.getElementById('descuento').value || 0;
            const color = document.getElementById('color').value;
            const icono = document.getElementById('icono').value;
            const beneficios = document.getElementById('beneficios').value;
            
            if (!nivel || !puntos_minimos || !multiplicador) {
                Swal.showValidationMessage('⚠️ Completa los campos obligatorios');
                return false;
            }
            
            return { nivel, puntos_minimos, multiplicador, descuento, color, icono, beneficios };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            guardarMembresia(result.value);
        }
    });
}

function editarMembresia(id) {
    // Obtener datos de la membresía
    fetch(`api_puntos.php?action=obtener_membresia&id=${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const m = data.membresia;
                Swal.fire({
                    title: `<span class="text-2xl font-bold text-white">✏️ Editar ${m.nivel}</span>`,
                    html: `
                        <div class="text-left space-y-4 p-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Nombre del Nivel *</label>
                                <input type="text" id="nivel" value="${m.nivel}"
                                       class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Puntos Mínimos *</label>
                                <input type="number" id="puntos_minimos" value="${m.puntos_minimos}" min="0" step="100"
                                       class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-300 mb-2">Multiplicador *</label>
                                    <input type="number" id="multiplicador" value="${m.multiplicador_puntos}" min="1" max="5" step="0.25"
                                           class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-300 mb-2">Descuento (%)</label>
                                    <input type="number" id="descuento" value="${m.descuento_adicional}" min="0" max="50" step="0.5"
                                           class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-300 mb-2">Color (Hex)</label>
                                    <input type="color" id="color" value="${m.color}"
                                           class="w-full h-10 border border-gray-600 rounded-lg bg-gray-800 cursor-pointer">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-300 mb-2">Icono</label>
                                    <select id="icono" class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white">
                                        <option value="military_tech" ${m.icono === 'military_tech' ? 'selected' : ''}>🎖️ Medalla</option>
                                        <option value="workspace_premium" ${m.icono === 'workspace_premium' ? 'selected' : ''}>👑 Corona</option>
                                        <option value="diamond" ${m.icono === 'diamond' ? 'selected' : ''}>💎 Diamante</option>
                                        <option value="star" ${m.icono === 'star' ? 'selected' : ''}>⭐ Estrella</option>
                                        <option value="emoji_events" ${m.icono === 'emoji_events' ? 'selected' : ''}>🏆 Trofeo</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Beneficios</label>
                                <textarea id="beneficios" rows="2"
                                          class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary">${m.beneficios || ''}</textarea>
                            </div>
                        </div>
                    `,
                    width: '600px',
                    background: '#1a1f2e',
                    color: '#ffffff',
                    showCancelButton: true,
                    confirmButtonText: '<span class="material-symbols-outlined text-sm align-middle">save</span> Guardar Cambios',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#10b981',
                    customClass: { popup: 'rounded-xl border border-gray-700' },
                    preConfirm: () => {
                        return {
                            id: id,
                            nivel: document.getElementById('nivel').value,
                            puntos_minimos: document.getElementById('puntos_minimos').value,
                            multiplicador: document.getElementById('multiplicador').value,
                            descuento: document.getElementById('descuento').value,
                            color: document.getElementById('color').value,
                            icono: document.getElementById('icono').value,
                            beneficios: document.getElementById('beneficios').value
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        actualizarMembresia(result.value);
                    }
                });
            }
        });
}

function guardarMembresia(data) {
    const formData = new FormData();
    formData.append('action', 'crear_membresia');
    Object.keys(data).forEach(key => formData.append(key, data[key]));
    
    fetch('api_puntos.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Creada!',
                text: 'Membresía creada exitosamente',
                background: '#1a1f2e',
                color: '#ffffff'
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    });
}

function actualizarMembresia(data) {
    const formData = new FormData();
    formData.append('action', 'actualizar_membresia');
    Object.keys(data).forEach(key => formData.append(key, data[key]));
    
    fetch('api_puntos.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(result => {
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Actualizada!',
                text: 'Membresía actualizada exitosamente',
                background: '#1a1f2e',
                color: '#ffffff'
            }).then(() => location.reload());
        } else {
            Swal.fire('Error', result.message, 'error');
        }
    });
}

function eliminarMembresia(id, nombre) {
    Swal.fire({
        title: '¿Eliminar Membresía?',
        text: `Se eliminará la membresía "${nombre}"`,
        icon: 'warning',
        background: '#1a1f2e',
        color: '#ffffff',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'eliminar_membresia');
            formData.append('id', id);
            
            fetch('api_puntos.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminada!',
                        text: 'Membresía eliminada exitosamente',
                        background: '#1a1f2e',
                        color: '#ffffff'
                    }).then(() => location.reload());
                } else {
                    Swal.fire('Error', result.message, 'error');
                }
            });
        }
    });
}
</script>
</body>
</html>
<?php $conexion->close(); ?>
