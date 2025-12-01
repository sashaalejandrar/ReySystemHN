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

// Obtener estadísticas
$stats = obtenerEstadisticas();
$top_clientes = obtenerTopClientes(10);
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Puntos de Fidelidad - Rey System APP</title>
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
                        🎁 Puntos de Fidelidad
                    </h1>
                    <p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">
                        Gestiona el programa de recompensas de tus clientes
                    </p>
                </div>
                <div class="flex gap-3">
                    <?php if ($rol_usuario === 'admin'): ?>
                    <button onclick="gestionarMembresias()" class="flex items-center gap-2 px-4 py-2 bg-yellow-600 text-white rounded-lg font-semibold hover:bg-yellow-700 transition-colors">
                        <span class="material-symbols-outlined">workspace_premium</span>
                        Gestionar Membresías
                    </button>
                    <?php endif; ?>
                    <button onclick="window.location.href='recompensas.php'" class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 transition-colors">
                        <span class="material-symbols-outlined">redeem</span>
                        Recompensas
                    </button>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Clientes -->
                <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="material-symbols-outlined text-blue-600 text-3xl">group</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['total_clientes']); ?></span>
                    </div>
                    <h3 class="text-gray-500 dark:text-[#92a4c9] text-sm font-semibold">Clientes Activos</h3>
                </div>

                <!-- Puntos en Circulación -->
                <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="material-symbols-outlined text-yellow-600 text-3xl">stars</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['puntos_circulacion']); ?></span>
                    </div>
                    <h3 class="text-gray-500 dark:text-[#92a4c9] text-sm font-semibold">Puntos Disponibles</h3>
                </div>

                <!-- Canjes del Mes -->
                <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="material-symbols-outlined text-green-600 text-3xl">local_activity</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo number_format($stats['canjes_mes']); ?></span>
                    </div>
                    <h3 class="text-gray-500 dark:text-[#92a4c9] text-sm font-semibold">Canjes Este Mes</h3>
                </div>

                <!-- Valor Canjes -->
                <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
                    <div class="flex items-center justify-between mb-4">
                        <span class="material-symbols-outlined text-purple-600 text-3xl">payments</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">L<?php echo number_format(($stats['canjes_mes'] / 100) * 10, 2); ?></span>
                    </div>
                    <h3 class="text-gray-500 dark:text-[#92a4c9] text-sm font-semibold">Valor en Descuentos</h3>
                </div>
            </div>

            <!-- Top Clientes -->
            <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">🏆 Top 10 Clientes</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-[#0d1420] border-b border-gray-200 dark:border-[#324467]">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Cliente</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Nivel</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Puntos Disponibles</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Total Ganados</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 dark:text-[#92a4c9] uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-[#324467]">
                            <?php 
                            $posicion = 1;
                            foreach ($top_clientes as $cliente): 
                                $nivel_color = [
                                    'Bronce' => 'text-orange-600',
                                    'Plata' => 'text-gray-400',
                                    'Oro' => 'text-yellow-500',
                                    'Platino' => 'text-purple-400'
                                ];
                                $color = $nivel_color[$cliente['nivel_membresia']] ?? 'text-gray-500';
                            ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-[#1a2332]">
                                <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white"><?php echo $posicion++; ?></td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($cliente['cliente_nombre']); ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold <?php echo $color; ?> bg-opacity-10">
                                        <span class="material-symbols-outlined text-sm">military_tech</span>
                                        <?php echo $cliente['nivel_membresia']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-right text-blue-600 dark:text-blue-400"><?php echo number_format($cliente['puntos_disponibles']); ?></td>
                                <td class="px-4 py-3 text-sm text-right text-gray-500 dark:text-gray-400"><?php echo number_format($cliente['puntos_totales_ganados']); ?></td>
                                <td class="px-4 py-3 text-center">
                                    <button onclick="verHistorial('<?php echo htmlspecialchars($cliente['cliente_nombre']); ?>')" class="text-primary hover:text-blue-700 dark:hover:text-blue-300">
                                        <span class="material-symbols-outlined">history</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Búsqueda de Cliente -->
            <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">🔍 Buscar Cliente</h2>
                <div class="flex gap-4">
                    <input type="text" id="buscarCliente" placeholder="Nombre del cliente..." class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[#0d1420] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary">
                    <button onclick="buscarPuntos()" class="px-6 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Buscar
                    </button>
                </div>
                <div id="resultadoBusqueda" class="mt-6"></div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
            <p class="text-gray-500 dark:text-[#92a4c9]">Sistema de Puntos v1.0</p>
            <a class="text-primary hover:underline" href="index.php">← Volver al Panel</a>
        </footer>
    </main>
</div>
</div>

<script>
function buscarPuntos() {
    const cliente = document.getElementById('buscarCliente').value.trim();
    if (!cliente) {
        Swal.fire('Error', 'Ingresa el nombre del cliente', 'error');
        return;
    }

    fetch(`api_puntos.php?action=consultar&cliente=${encodeURIComponent(cliente)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const puntos = data.datos;
                const nivelColor = {
                    'Bronce': 'orange',
                    'Plata': 'gray',
                    'Oro': 'gold',
                    'Platino': 'purple'
                };
                
                document.getElementById('resultadoBusqueda').innerHTML = `
                    <div class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 rounded-xl p-6 border-2 border-blue-200 dark:border-blue-700">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white">${puntos.cliente_nombre}</h3>
                            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-bold bg-${nivelColor[puntos.nivel_membresia]}-100 text-${nivelColor[puntos.nivel_membresia]}-800 dark:bg-${nivelColor[puntos.nivel_membresia]}-900/30 dark:text-${nivelColor[puntos.nivel_membresia]}-300">
                                <span class="material-symbols-outlined">military_tech</span>
                                ${puntos.nivel_membresia}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="bg-white dark:bg-[#192233] rounded-lg p-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Puntos Disponibles</p>
                                <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">${puntos.puntos_disponibles.toLocaleString()}</p>
                            </div>
                            <div class="bg-white dark:bg-[#192233] rounded-lg p-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Total Ganados</p>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">${puntos.puntos_totales_ganados.toLocaleString()}</p>
                            </div>
                            <div class="bg-white dark:bg-[#192233] rounded-lg p-4">
                                <p class="text-sm text-gray-500 dark:text-gray-400">Total Canjeados</p>
                                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">${puntos.puntos_totales_canjeados.toLocaleString()}</p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button onclick="verHistorial('${puntos.cliente_nombre}')" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700">
                                <span class="material-symbols-outlined">history</span>
                                Ver Historial
                            </button>
                            <button onclick="canjearPuntos('${puntos.cliente_nombre}', ${puntos.puntos_disponibles})" class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700">
                                <span class="material-symbols-outlined">redeem</span>
                                Canjear Puntos
                            </button>
                        </div>
                    </div>
                `;
            } else {
                document.getElementById('resultadoBusqueda').innerHTML = `
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                        <p class="text-yellow-800 dark:text-yellow-300">Cliente no encontrado o sin puntos registrados</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            Swal.fire('Error', 'Error al consultar puntos', 'error');
        });
}

function verHistorial(cliente) {
    fetch(`api_puntos.php?action=historial&cliente=${encodeURIComponent(cliente)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="space-y-2">';
                data.historial.forEach(item => {
                    const tipoColor = {
                        'ganado': 'green',
                        'canjeado': 'red',
                        'ajuste': 'blue'
                    };
                    const color = tipoColor[item.tipo] || 'gray';
                    const signo = item.tipo === 'ganado' ? '+' : '';
                    
                    html += `
                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-[#0d1420] rounded-lg">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">${item.descripcion}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">${new Date(item.fecha).toLocaleString('es-HN')}</p>
                            </div>
                            <span class="text-lg font-bold text-${color}-600 dark:text-${color}-400">${signo}${item.puntos}</span>
                        </div>
                    `;
                });
                html += '</div>';
                
                Swal.fire({
                    title: `Historial de ${cliente}`,
                    html: html,
                    width: '600px',
                    confirmButtonText: 'Cerrar'
                });
            }
        });
}

function canjearPuntos(cliente, puntosDisponibles) {
    Swal.fire({
        title: 'Canjear Puntos',
        html: `
            <p class="mb-4">Cliente: <strong>${cliente}</strong></p>
            <p class="mb-4">Puntos disponibles: <strong>${puntosDisponibles}</strong></p>
            <input type="number" id="puntosACanjear" class="swal2-input" placeholder="Cantidad de puntos" min="100" max="${puntosDisponibles}" step="100">
            <p class="text-sm text-gray-500 mt-2">100 puntos = L10 de descuento</p>
        `,
        showCancelButton: true,
        confirmButtonText: 'Canjear',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const puntos = document.getElementById('puntosACanjear').value;
            if (!puntos || puntos < 100) {
                Swal.showValidationMessage('Mínimo 100 puntos');
                return false;
            }
            if (puntos > puntosDisponibles) {
                Swal.showValidationMessage('Puntos insuficientes');
                return false;
            }
            return puntos;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'canjear');
            formData.append('cliente', cliente);
            formData.append('puntos', result.value);
            
            fetch('api_puntos.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('¡Éxito!', `Canjeados ${result.value} puntos. Descuento: L${data.descuento.toFixed(2)}`, 'success');
                    buscarPuntos();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            });
        }
    });
}

function gestionarMembresias() {
    // Obtener niveles de membresía
    fetch('api_puntos.php?action=niveles')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                let html = '<div class="space-y-4">';
                data.niveles.forEach(nivel => {
                    html += `
                        <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <span class="material-symbols-outlined text-3xl" style="color: ${nivel.color}">military_tech</span>
                                    <div>
                                        <h3 class="text-lg font-bold text-white">${nivel.nivel}</h3>
                                        <p class="text-sm text-gray-400">Desde ${parseInt(nivel.puntos_minimos).toLocaleString()} puntos</p>
                                    </div>
                                </div>
                                <button onclick="editarMembresia(${nivel.id}, '${nivel.nivel}', ${nivel.puntos_minimos}, ${nivel.multiplicador_puntos}, ${nivel.descuento_adicional})" 
                                        class="px-3 py-1 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-400">Multiplicador</p>
                                    <p class="text-white font-bold">${nivel.multiplicador_puntos}x</p>
                                </div>
                                <div>
                                    <p class="text-gray-400">Descuento Extra</p>
                                    <p class="text-white font-bold">${nivel.descuento_adicional}%</p>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                
                Swal.fire({
                    title: '<span class="text-2xl font-bold text-white">🏆 Gestión de Membresías</span>',
                    html: html,
                    width: '700px',
                    background: '#1a1f2e',
                    color: '#ffffff',
                    showCloseButton: true,
                    showConfirmButton: false,
                    customClass: {
                        popup: 'rounded-xl border border-gray-700'
                    }
                });
            }
        });
}

function editarMembresia(id, nombre, puntosMin, multiplicador, descuento) {
    Swal.fire({
        title: `<span class="text-2xl font-bold text-white">✏️ Editar ${nombre}</span>`,
        html: `
            <div class="text-left space-y-4 p-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-300 mb-2">
                        <span class="material-symbols-outlined text-sm align-middle">stars</span>
                        Puntos Mínimos
                    </label>
                    <input type="number" id="puntosMin" value="${puntosMin}" min="0" step="100"
                           class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <span class="material-symbols-outlined text-sm align-middle">trending_up</span>
                            Multiplicador
                        </label>
                        <input type="number" id="multiplicador" value="${multiplicador}" min="1" max="5" step="0.25"
                               class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-300 mb-2">
                            <span class="material-symbols-outlined text-sm align-middle">percent</span>
                            Descuento Extra (%)
                        </label>
                        <input type="number" id="descuento" value="${descuento}" min="0" max="50" step="0.5"
                               class="w-full px-4 py-2 border border-gray-600 rounded-lg bg-gray-800 text-white focus:ring-2 focus:ring-primary">
                    </div>
                </div>
                
                <div class="bg-blue-900/20 border border-blue-700 rounded-lg p-3">
                    <p class="text-sm text-blue-300">
                        <strong>Ejemplo:</strong> Con multiplicador ${multiplicador}x, una compra de L100 genera ${Math.floor(10 * multiplicador)} puntos
                    </p>
                </div>
            </div>
        `,
        width: '600px',
        background: '#1a1f2e',
        color: '#ffffff',
        showCancelButton: true,
        confirmButtonText: '<span class="material-symbols-outlined text-sm align-middle">save</span> Guardar Cambios',
        cancelButtonText: '<span class="material-symbols-outlined text-sm align-middle">close</span> Cancelar',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        customClass: {
            popup: 'rounded-xl border border-gray-700'
        },
        preConfirm: () => {
            return {
                puntosMin: document.getElementById('puntosMin').value,
                multiplicador: document.getElementById('multiplicador').value,
                descuento: document.getElementById('descuento').value
            };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: '¡Actualizado!',
                background: '#1a1f2e',
                color: '#ffffff',
                text: `Membresía ${nombre} actualizada (guardado pendiente de implementar)`,
                confirmButtonColor: '#1152d4'
            }).then(() => {
                gestionarMembresias();
            });
        }
    });
}
</script>
</body>
</html>
<?php $conexion->close(); ?>
