<?php
session_start();
include 'funciones.php';
include 'funciones_puntos.php';

VerificarSiUsuarioYaInicioSesion();

// Obtener datos del usuario
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

// Obtener recompensas
$recompensas = obtenerRecompensas();
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Catálogo de Recompensas - Rey System APP</title>
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
                        🎁 Catálogo de Recompensas
                    </h1>
                    <p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">
                        Canjea tus puntos por increíbles premios
                    </p>
                </div>
                <div class="flex gap-3">
                    <button onclick="window.location.href='puntos_fidelidad.php'" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Volver a Puntos
                    </button>
                    <?php if ($rol_usuario === 'admin'): ?>
                    <button onclick="agregarRecompensa()" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        <span class="material-symbols-outlined">add</span>
                        Nueva Recompensa
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Búsqueda de Cliente -->
            <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🔍 Buscar Cliente para Canjear</h2>
                <div class="flex gap-4">
                    <input type="text" id="buscarCliente" placeholder="Nombre del cliente..." class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1420] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                    <button onclick="buscarCliente()" class="px-6 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Buscar
                    </button>
                </div>
                <div id="infoCliente" class="mt-4"></div>
            </div>

            <!-- Grid de Recompensas -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6" id="gridRecompensas">
                <?php foreach ($recompensas as $recompensa): 
                    $tipo_icon = [
                        'descuento' => 'local_offer',
                        'producto' => 'redeem',
                        'servicio' => 'handshake'
                    ];
                    $tipo_color = [
                        'descuento' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300',
                        'producto' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300',
                        'servicio' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300'
                    ];
                    $icon = $tipo_icon[$recompensa['tipo']] ?? 'redeem';
                    $color = $tipo_color[$recompensa['tipo']] ?? 'bg-gray-100';
                ?>
                <div class="recompensa-card bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-105" data-puntos="<?php echo $recompensa['puntos_requeridos']; ?>">
                    <!-- Imagen/Icono -->
                    <div class="bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 p-8 flex items-center justify-center">
                        <span class="material-symbols-outlined text-primary text-6xl"><?php echo $icon; ?></span>
                    </div>
                    
                    <!-- Contenido -->
                    <div class="p-6">
                        <!-- Tipo -->
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold <?php echo $color; ?> mb-3">
                            <?php echo ucfirst($recompensa['tipo']); ?>
                        </span>
                        
                        <!-- Nombre -->
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2">
                            <?php echo htmlspecialchars($recompensa['nombre']); ?>
                        </h3>
                        
                        <!-- Descripción -->
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            <?php echo htmlspecialchars($recompensa['descripcion']); ?>
                        </p>
                        
                        <!-- Valor -->
                        <?php if ($recompensa['valor'] > 0): ?>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="material-symbols-outlined text-green-600 text-sm">payments</span>
                            <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                                Valor: L<?php echo number_format($recompensa['valor'], 2); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Puntos Requeridos -->
                        <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg mb-4">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Puntos necesarios:</span>
                            <span class="text-xl font-bold text-blue-600 dark:text-blue-400">
                                <?php echo number_format($recompensa['puntos_requeridos']); ?>
                            </span>
                        </div>
                        
                        <!-- Botón Canjear -->
                        <button onclick="canjearRecompensa(<?php echo $recompensa['id']; ?>, '<?php echo htmlspecialchars($recompensa['nombre']); ?>', <?php echo $recompensa['puntos_requeridos']; ?>)" 
                                class="w-full flex items-center justify-center gap-2 px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                id="btn-canjear-<?php echo $recompensa['id']; ?>"
                                disabled>
                            <span class="material-symbols-outlined">redeem</span>
                            Canjear
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($recompensas)): ?>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-8 text-center">
                <span class="material-symbols-outlined text-yellow-600 text-6xl mb-4">sentiment_dissatisfied</span>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No hay recompensas disponibles</h3>
                <p class="text-gray-500 dark:text-gray-400">Pronto habrá nuevas recompensas para canjear</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
            <p class="text-gray-500 dark:text-[#92a4c9]">Sistema de Recompensas v1.0</p>
            <a class="text-primary hover:underline" href="puntos_fidelidad.php">← Volver a Puntos</a>
        </footer>
    </main>
</div>
</div>

<script>
let clienteActual = null;
let puntosDisponibles = 0;

function buscarCliente() {
    const cliente = document.getElementById('buscarCliente').value.trim();
    if (!cliente) {
        Swal.fire('Error', 'Ingresa el nombre del cliente', 'error');
        return;
    }

    fetch(`api_puntos.php?action=consultar&cliente=${encodeURIComponent(cliente)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                clienteActual = data.datos.cliente_nombre;
                puntosDisponibles = data.datos.puntos_disponibles;
                
                const nivelColor = {
                    'Bronce': 'orange',
                    'Plata': 'gray',
                    'Oro': 'gold',
                    'Platino': 'purple'
                };
                
                document.getElementById('infoCliente').innerHTML = `
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-lg p-4 border-2 border-blue-200 dark:border-blue-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">${data.datos.cliente_nombre}</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Nivel: ${data.datos.nivel_membresia}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Puntos disponibles</p>
                                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">${puntosDisponibles.toLocaleString()}</p>
                            </div>
                        </div>
                    </div>
                `;
                
                // Habilitar/deshabilitar botones según puntos
                actualizarBotones();
            } else {
                document.getElementById('infoCliente').innerHTML = `
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                        <p class="text-yellow-800 dark:text-yellow-300">Cliente no encontrado o sin puntos registrados</p>
                    </div>
                `;
                clienteActual = null;
                puntosDisponibles = 0;
                actualizarBotones();
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Error al consultar puntos', 'error');
        });
}

function actualizarBotones() {
    const cards = document.querySelectorAll('.recompensa-card');
    cards.forEach(card => {
        const puntosRequeridos = parseInt(card.dataset.puntos);
        const btn = card.querySelector('button');
        
        if (clienteActual && puntosDisponibles >= puntosRequeridos) {
            btn.disabled = false;
            card.classList.add('ring-2', 'ring-green-500');
        } else {
            btn.disabled = true;
            card.classList.remove('ring-2', 'ring-green-500');
        }
    });
}

function canjearRecompensa(id, nombre, puntosRequeridos) {
    if (!clienteActual) {
        Swal.fire('Error', 'Primero busca un cliente', 'warning');
        return;
    }
    
    if (puntosDisponibles < puntosRequeridos) {
        Swal.fire('Error', 'Puntos insuficientes', 'error');
        return;
    }
    
    Swal.fire({
        title: '¿Confirmar Canje?',
        html: `
            <p class="mb-2"><strong>Cliente:</strong> ${clienteActual}</p>
            <p class="mb-2"><strong>Recompensa:</strong> ${nombre}</p>
            <p class="mb-2"><strong>Puntos a canjear:</strong> ${puntosRequeridos.toLocaleString()}</p>
            <p class="text-sm text-gray-500">Puntos restantes: ${(puntosDisponibles - puntosRequeridos).toLocaleString()}</p>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, Canjear',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#1152d4'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'canjear');
            formData.append('cliente', clienteActual);
            formData.append('puntos', puntosRequeridos);
            formData.append('descripcion', `Canje: ${nombre}`);
            
            fetch('api_puntos.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Canje Exitoso!',
                        html: `
                            <p class="mb-2">Recompensa: <strong>${nombre}</strong></p>
                            <p class="mb-2">Puntos canjeados: <strong>${puntosRequeridos.toLocaleString()}</strong></p>
                            <p class="text-green-600 font-bold">Descuento aplicado: L${data.descuento.toFixed(2)}</p>
                        `,
                        confirmButtonText: 'Aceptar'
                    });
                    
                    // Actualizar puntos disponibles
                    puntosDisponibles = data.puntos_restantes;
                    buscarCliente();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

function agregarRecompensa() {
    Swal.fire({
        title: '<span class="text-2xl font-bold text-white">🎁 Nueva Recompensa</span>',
        html: `
            <div class="text-left space-y-4 p-4">
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">label</span>
                        Nombre de la Recompensa *
                    </label>
                    <input type="text" id="nombre" 
                           class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary focus:border-transparent" 
                           placeholder="Ej: Descuento L50">
                </div>
                
                <!-- Descripción -->
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">description</span>
                        Descripción
                    </label>
                    <textarea id="descripcion" rows="3"
                              class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary focus:border-transparent" 
                              placeholder="Describe la recompensa..."></textarea>
                </div>
                
                <!-- Tipo -->
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">category</span>
                        Tipo de Recompensa *
                    </label>
                    <select id="tipo" 
                            class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="descuento">💰 Descuento</option>
                        <option value="producto">🎁 Producto</option>
                        <option value="servicio">🤝 Servicio</option>
                    </select>
                </div>
                
                <!-- Grid de Puntos y Valor -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <span class="material-symbols-outlined text-sm align-middle">stars</span>
                            Puntos Requeridos *
                        </label>
                        <input type="number" id="puntos" min="100" step="100"
                               class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary focus:border-transparent" 
                               placeholder="100">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <span class="material-symbols-outlined text-sm align-middle">payments</span>
                            Valor (Lempiras)
                        </label>
                        <input type="number" id="valor" step="0.01"
                               class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary focus:border-transparent" 
                               placeholder="0.00">
                    </div>
                </div>
                
                <p class="text-xs text-gray-400 mt-2">
                    * Campos obligatorios
                </p>
            </div>
        `,
        width: '600px',
        background: '#1a1f2e',
        color: '#ffffff',
        showCancelButton: true,
        confirmButtonText: '<span class="material-symbols-outlined text-sm align-middle">add</span> Crear Recompensa',
        cancelButtonText: '<span class="material-symbols-outlined text-sm align-middle">close</span> Cancelar',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        customClass: {
            popup: 'rounded-xl border border-gray-700',
            confirmButton: 'px-6 py-2 rounded-lg font-semibold',
            cancelButton: 'px-6 py-2 rounded-lg font-semibold'
        },
        preConfirm: () => {
            const nombre = document.getElementById('nombre').value;
            const descripcion = document.getElementById('descripcion').value;
            const tipo = document.getElementById('tipo').value;
            const puntos = document.getElementById('puntos').value;
            const valor = document.getElementById('valor').value;
            
            if (!nombre || !puntos) {
                Swal.showValidationMessage('⚠️ Completa los campos obligatorios');
                return false;
            }
            
            if (parseInt(puntos) < 100) {
                Swal.showValidationMessage('⚠️ Mínimo 100 puntos');
                return false;
            }
            
            return { nombre, descripcion, tipo, puntos, valor };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Aquí iría la llamada para crear la recompensa
            Swal.fire({
                icon: 'success',
                title: '¡Recompensa Creada!',
                background: '#1a1f2e',
                color: '#ffffff',
                html: `
                    <p class="mb-2 text-gray-300"><strong class="text-white">Nombre:</strong> ${result.value.nombre}</p>
                    <p class="mb-2 text-gray-300"><strong class="text-white">Tipo:</strong> ${result.value.tipo}</p>
                    <p class="mb-2 text-gray-300"><strong class="text-white">Puntos:</strong> ${parseInt(result.value.puntos).toLocaleString()}</p>
                    <p class="text-sm text-gray-400 mt-4">Función de guardado pendiente de implementar</p>
                `,
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#1152d4'
            });
        }
    });
}
</script>
</body>
</html>
<?php $conexion->close(); ?>
