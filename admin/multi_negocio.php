<?php
session_start();
include '../funciones.php';

VerificarSiUsuarioYaInicioSesion();

// Conexión a la base de datos
require_once '../db_connect.php';

// Verificar que sea admin
$resultado = $conexion->query("SELECT * FROM usuarios WHERE Usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $super_admin = $row['super_admin'] ?? 0;
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);

if ($rol_usuario !== 'admin' && !$super_admin) {
    die('Acceso denegado. Solo administradores pueden acceder a este módulo.');
}

$mensaje = '';
$mensaje_tipo = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['crear_negocio'])) {
        $nombre = $conexion->real_escape_string($_POST['nombre']);
        $tipo = $conexion->real_escape_string($_POST['tipo_negocio']);
        $direccion = $conexion->real_escape_string($_POST['direccion'] ?? '');
        $telefono = $conexion->real_escape_string($_POST['telefono'] ?? '');
        $email = $conexion->real_escape_string($_POST['email'] ?? '');
        $rtn = $conexion->real_escape_string($_POST['rtn'] ?? '');
        $impuesto = floatval($_POST['impuesto_default'] ?? 15);
        
        $sql = "INSERT INTO negocios (nombre, tipo_negocio, direccion, telefono, email, rtn, impuesto_default) 
                VALUES ('$nombre', '$tipo', '$direccion', '$telefono', '$email', '$rtn', $impuesto)";
        
        if ($conexion->query($sql)) {
            $mensaje = "Negocio creado exitosamente";
            $mensaje_tipo = "success";
        } else {
            $mensaje = "Error al crear negocio: " . $conexion->error;
            $mensaje_tipo = "error";
        }
    }
    
    if (isset($_POST['crear_sucursal'])) {
        $id_negocio = intval($_POST['id_negocio']);
        $nombre = $conexion->real_escape_string($_POST['nombre']);
        $codigo = $conexion->real_escape_string($_POST['codigo']);
        $direccion = $conexion->real_escape_string($_POST['direccion'] ?? '');
        $telefono = $conexion->real_escape_string($_POST['telefono'] ?? '');
        
        $sql = "INSERT INTO sucursales (id_negocio, nombre, codigo, direccion, telefono) 
                VALUES ($id_negocio, '$nombre', '$codigo', '$direccion', '$telefono')";
        
        if ($conexion->query($sql)) {
            $mensaje = "Sucursal creada exitosamente";
            $mensaje_tipo = "success";
        } else {
            $mensaje = "Error al crear sucursal: " . $conexion->error;
            $mensaje_tipo = "error";
        }
    }
    
    if (isset($_POST['toggle_negocio'])) {
        $id = intval($_POST['id']);
        $activo = intval($_POST['activo']);
        $sql = "UPDATE negocios SET activo = $activo WHERE id = $id";
        if ($conexion->query($sql)) {
            $mensaje = "Estado actualizado";
            $mensaje_tipo = "success";
        }
    }
    
    if (isset($_POST['toggle_sucursal'])) {
        $id = intval($_POST['id']);
        $activo = intval($_POST['activo']);
        $sql = "UPDATE sucursales SET activo = $activo WHERE id = $id";
        if ($conexion->query($sql)) {
            $mensaje = "Estado actualizado";
            $mensaje_tipo = "success";
        }
    }
}

// Get data
$negocios = $conexion->query("SELECT * FROM negocios ORDER BY nombre");
$sucursales = $conexion->query("SELECT s.*, n.nombre as negocio_nombre FROM sucursales s JOIN negocios n ON s.id_negocio = n.id ORDER BY n.nombre, s.nombre");
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Gestión Multi-Negocio - Rey System APP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&amp;display=swap" rel="stylesheet"/>
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
        font-variation-settings:
        'FILL' 0,
        'wght' 400,
        'GRAD' 0,
        'opsz' 24
    }
</style>
<script src="../nova_rey.js"></script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
<?php include '../menu_lateral.php'; ?>
<!-- Main Content -->
<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">
<!-- PageHeading -->
<div class="flex flex-wrap justify-between gap-4 mb-8">
<div class="flex flex-col gap-2">
<h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">Gestión Multi-Negocio</h1>
<p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">Administra negocios y sucursales del sistema</p>
</div>
</div>

<?php if ($mensaje): ?>
<div class="mb-6 p-4 rounded-xl border <?php echo $mensaje_tipo === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200'; ?>">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<!-- Tabs -->
<div class="mb-6">
    <div class="border-b border-gray-200 dark:border-[#324467]">
        <nav class="-mb-px flex gap-6">
            <button onclick="showTab('negocios')" id="tab-negocios" class="tab-button border-b-2 border-primary text-primary py-4 px-1 text-sm font-semibold">
                Negocios
            </button>
            <button onclick="showTab('sucursales')" id="tab-sucursales" class="tab-button border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 py-4 px-1 text-sm font-semibold">
                Sucursales
            </button>
        </nav>
    </div>
</div>

<!-- Negocios Tab -->
<div id="content-negocios" class="tab-content">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-1">
            <div class="rounded-xl border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#192233] p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Crear Negocio</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre *</label>
                        <input type="text" name="nombre" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo *</label>
                        <select name="tipo_negocio" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="abarrotes">Abarrotes</option>
                            <option value="ropa">Ropa</option>
                            <option value="ferreteria">Ferretería</option>
                            <option value="farmacia">Farmacia</option>
                            <option value="restaurante">Restaurante</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dirección</label>
                        <textarea name="direccion" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teléfono</label>
                        <input type="text" name="telefono" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                        <input type="email" name="email" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">RTN</label>
                        <input type="text" name="rtn" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Impuesto (%)</label>
                        <input type="number" name="impuesto_default" value="15" step="0.01" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <button type="submit" name="crear_negocio" class="w-full px-4 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        Crear Negocio
                    </button>
                </form>
            </div>
        </div>
        
        <!-- List -->
        <div class="lg:col-span-2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php 
                $negocios->data_seek(0);
                while ($neg = $negocios->fetch_assoc()): 
                ?>
                <div class="rounded-xl border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#192233] p-6 hover:shadow-lg hover:border-primary dark:hover:border-primary transition-all duration-300">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1"><?php echo htmlspecialchars($neg['nombre']); ?></h3>
                            <span class="inline-block px-2 py-1 text-xs rounded bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200">
                                <?php echo ucfirst($neg['tipo_negocio']); ?>
                            </span>
                        </div>
                        <span class="material-symbols-outlined text-primary text-3xl">store</span>
                    </div>
                    
                    <div class="space-y-2 text-sm mb-4">
                        <?php if ($neg['direccion']): ?>
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <span class="material-symbols-outlined text-sm">location_on</span>
                            <span><?php echo htmlspecialchars($neg['direccion']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($neg['telefono']): ?>
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <span class="material-symbols-outlined text-sm">phone</span>
                            <span><?php echo htmlspecialchars($neg['telefono']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-[#324467]">
                        <span class="text-xs font-semibold <?php echo $neg['activo'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                            <?php echo $neg['activo'] ? '● Activo' : '● Inactivo'; ?>
                        </span>
                        <form method="POST" class="inline">
                            <input type="hidden" name="toggle_negocio" value="1">
                            <input type="hidden" name="id" value="<?php echo $neg['id']; ?>">
                            <input type="hidden" name="activo" value="<?php echo $neg['activo'] ? 0 : 1; ?>">
                            <button type="submit" class="px-3 py-1 text-sm rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                <?php echo $neg['activo'] ? 'Desactivar' : 'Activar'; ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<!-- Sucursales Tab -->
<div id="content-sucursales" class="tab-content hidden">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form -->
        <div class="lg:col-span-1">
            <div class="rounded-xl border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#192233] p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Crear Sucursal</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Negocio *</label>
                        <select name="id_negocio" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Seleccionar...</option>
                            <?php 
                            $negocios_select = $conexion->query("SELECT * FROM negocios WHERE activo = 1 ORDER BY nombre");
                            while ($neg = $negocios_select->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $neg['id']; ?>"><?php echo htmlspecialchars($neg['nombre']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre *</label>
                        <input type="text" name="nombre" required class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Código *</label>
                        <input type="text" name="codigo" required placeholder="Ej: SUC002" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dirección</label>
                        <textarea name="direccion" rows="2" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teléfono</label>
                        <input type="text" name="telefono" class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <button type="submit" name="crear_sucursal" class="w-full px-4 py-3 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                        Crear Sucursal
                    </button>
                </form>
            </div>
        </div>
        
        <!-- List -->
        <div class="lg:col-span-2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php 
                $sucursales->data_seek(0);
                while ($suc = $sucursales->fetch_assoc()): 
                ?>
                <div class="rounded-xl border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#192233] p-6 hover:shadow-lg hover:border-primary dark:hover:border-primary transition-all duration-300">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1"><?php echo htmlspecialchars($suc['nombre']); ?></h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($suc['negocio_nombre']); ?></p>
                            <span class="inline-block px-2 py-1 text-xs rounded bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-200 mt-1">
                                <?php echo $suc['codigo']; ?>
                            </span>
                        </div>
                        <span class="material-symbols-outlined text-primary text-3xl">business</span>
                    </div>
                    
                    <div class="space-y-2 text-sm mb-4">
                        <?php if ($suc['direccion']): ?>
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <span class="material-symbols-outlined text-sm">location_on</span>
                            <span><?php echo htmlspecialchars($suc['direccion']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($suc['telefono']): ?>
                        <div class="flex items-center gap-2 text-gray-600 dark:text-gray-400">
                            <span class="material-symbols-outlined text-sm">phone</span>
                            <span><?php echo htmlspecialchars($suc['telefono']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-[#324467]">
                        <span class="text-xs font-semibold <?php echo $suc['activo'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                            <?php echo $suc['activo'] ? '● Activa' : '● Inactiva'; ?>
                        </span>
                        <form method="POST" class="inline">
                            <input type="hidden" name="toggle_sucursal" value="1">
                            <input type="hidden" name="id" value="<?php echo $suc['id']; ?>">
                            <input type="hidden" name="activo" value="<?php echo $suc['activo'] ? 0 : 1; ?>">
                            <button type="submit" class="px-3 py-1 text-sm rounded bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                <?php echo $suc['activo'] ? 'Desactivar' : 'Activar'; ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

</div>
<!-- Footer -->
<footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
<p class="text-gray-500 dark:text-[#92a4c9]">Gestión Multi-Negocio v1.0</p>
<a class="text-primary hover:underline" href="../cambiar_contexto.php">Cambiar Contexto</a>
</footer>
</main>
</div>
</div>

<script>
function showTab(tab) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.tab-button').forEach(el => {
        el.classList.remove('border-primary', 'text-primary');
        el.classList.add('border-transparent', 'text-gray-500', 'dark:text-gray-400');
    });
    
    // Show selected tab
    document.getElementById('content-' + tab).classList.remove('hidden');
    document.getElementById('tab-' + tab).classList.remove('border-transparent', 'text-gray-500', 'dark:text-gray-400');
    document.getElementById('tab-' + tab).classList.add('border-primary', 'text-primary');
}
</script>
</body></html>
