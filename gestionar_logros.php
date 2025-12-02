<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'funciones.php';
date_default_timezone_set('America/Tegucigalpa');

VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Obtener información del usuario
$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre_Completo = $row['Nombre']." ".$row['Apellido'];
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);

// Verificar que sea Admin
if ($rol_usuario !== 'admin') {
    header('Location: logros.php');
    exit();
}

$mensaje = '';
$tipo_mensaje = '';

// Crear logro
if (isset($_POST['crear_logro'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $icono = $_POST['icono'];
    $tipo_condicion = $_POST['tipo_condicion'];
    $valor_objetivo = intval($_POST['valor_objetivo']);
    $puntos = intval($_POST['puntos']);
    $color = $_POST['color'];
    
    $stmt = $conexion->prepare("INSERT INTO logros (nombre, descripcion, icono, tipo_condicion, valor_objetivo, puntos, color, es_predefinido, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)");
    $stmt->bind_param("ssssiiis", $nombre, $descripcion, $icono, $tipo_condicion, $valor_objetivo, $puntos, $color, $Usuario);
    
    if ($stmt->execute()) {
        $mensaje = 'Logro creado exitosamente';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'Error al crear logro: ' . $stmt->error;
        $tipo_mensaje = 'error';
    }
    $stmt->close();
}

// Eliminar logro
if (isset($_POST['eliminar_logro'])) {
    $logro_id = intval($_POST['logro_id']);
    
    // Verificar que no sea predefinido
    $check = $conexion->query("SELECT es_predefinido FROM logros WHERE id = $logro_id");
    $logro = $check->fetch_assoc();
    
    if ($logro && $logro['es_predefinido'] == 0) {
        $conexion->query("DELETE FROM logros WHERE id = $logro_id");
        $mensaje = 'Logro eliminado exitosamente';
        $tipo_mensaje = 'success';
    } else {
        $mensaje = 'No se pueden eliminar logros predefinidos';
        $tipo_mensaje = 'error';
    }
}

// Editar logro
if (isset($_POST['editar_logro'])) {
    $logro_id = intval($_POST['logro_id']);
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $icono = $_POST['icono'];
    $tipo_condicion = $_POST['tipo_condicion'];
    $valor_objetivo = intval($_POST['valor_objetivo']);
    $puntos = intval($_POST['puntos']);
    $color = $_POST['color'];
    
    // Verificar que no sea predefinido
    $check = $conexion->query("SELECT es_predefinido FROM logros WHERE id = $logro_id");
    $logro = $check->fetch_assoc();
    
    if ($logro && $logro['es_predefinido'] == 0) {
        $stmt = $conexion->prepare("UPDATE logros SET nombre = ?, descripcion = ?, icono = ?, tipo_condicion = ?, valor_objetivo = ?, puntos = ?, color = ? WHERE id = ?");
        $stmt->bind_param("ssssiisi", $nombre, $descripcion, $icono, $tipo_condicion, $valor_objetivo, $puntos, $color, $logro_id);

        
        if ($stmt->execute()) {
            $mensaje = 'Logro actualizado exitosamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'Error al actualizar logro: ' . $stmt->error;
            $tipo_mensaje = 'error';
        }
        $stmt->close();
    } else {
        $mensaje = 'No se pueden editar logros predefinidos';
        $tipo_mensaje = 'error';
    }
}

// Activar/Desactivar logro
if (isset($_POST['toggle_activo'])) {
    $logro_id = intval($_POST['logro_id']);
    $nuevo_estado = intval($_POST['nuevo_estado']);
    
    $stmt = $conexion->prepare("UPDATE logros SET activo = ? WHERE id = ?");
    $stmt->bind_param("ii", $nuevo_estado, $logro_id);
    $stmt->execute();
    $stmt->close();
    
    $mensaje = 'Estado actualizado';
    $tipo_mensaje = 'success';
}

// Obtener todos los logros
$logros = $conexion->query("SELECT * FROM logros ORDER BY es_predefinido DESC, fecha_creacion DESC");

?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Gestionar Logros - Rey System</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: { "primary": "#1152d4" },
            fontFamily: { "display": ["Inter", "sans-serif"] }
        }
    }
}
</script>
<style>
.material-symbols-outlined {
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 16px 24px;
    border-radius: 12px;
    display: none;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.notification.show { display: block; }
.notification.success { background: #10b981; color: white; }
.notification.error { background: #ef4444; color: white; }
</style>
</head>
<body class="bg-[#f6f7f8] dark:bg-[#101922] font-display">

<?php if ($mensaje): ?>
<div class="notification <?php echo $tipo_mensaje; ?> show">
    <?php echo $mensaje; ?>
</div>
<script>
setTimeout(() => {
    document.querySelector('.notification').classList.remove('show');
}, 5000);
</script>
<?php endif; ?>

<div class="flex min-h-screen w-full">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 p-8">
<div class="mx-auto max-w-7xl">

<!-- Header -->
<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">Gestionar Logros</h1>
            <p class="text-gray-500 dark:text-[#92a4c9] text-base mt-2">Crea y administra logros personalizados</p>
        </div>
        <a href="logros.php" class="flex items-center gap-2 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-semibold hover:bg-gray-300 dark:hover:bg-gray-600">
            <span class="material-symbols-outlined">arrow_back</span>
            Ver Logros
        </a>
    </div>
</div>

<!-- Formulario para crear logro -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-2xl">add_circle</span>
        </div>
        <div>
            <h2 class="text-gray-900 dark:text-white text-xl font-bold">Crear Nuevo Logro</h2>
            <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Define un logro personalizado</p>
        </div>
    </div>

    <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Nombre del Logro</label>
            <input type="text" name="nombre" required placeholder="Ej: Super Vendedor"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Icono (Material Symbol)</label>
            <input type="text" name="icono" required placeholder="Ej: emoji_events" value="emoji_events"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Tipo de Condición</label>
            <select name="tipo_condicion" required
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
                <option value="ventas_count">Cantidad de Ventas</option>
                <option value="ventas_monto">Monto de Ventas</option>
                <option value="clientes_count">Cantidad de Clientes</option>
                <option value="aperturas_count">Aperturas de Caja</option>
                <option value="arqueos_sin_error">Arqueos Sin Error</option>
                <option value="meta_alcanzada">Meta Alcanzada</option>
                <option value="dias_consecutivos">Días Consecutivos</option>
                <option value="inventario_updates">Actualizaciones de Inventario</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Valor Objetivo</label>
            <input type="number" name="valor_objetivo" required min="1" placeholder="Ej: 50"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Puntos</label>
            <input type="number" name="puntos" required min="1" value="10" placeholder="Ej: 25"
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Color</label>
            <input type="color" name="color" value="#1152d4"
                class="w-full h-10 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622]">
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Descripción</label>
            <textarea name="descripcion" rows="2" required placeholder="Describe el logro..."
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white"></textarea>
        </div>
        
        <div class="md:col-span-2">
            <button type="submit" name="crear_logro" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90">
                <span class="material-symbols-outlined">add</span>
                Crear Logro
            </button>
        </div>
    </form>
</div>

<!-- Tabla de logros -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
    <div class="p-6 border-b border-gray-200 dark:border-[#324467]">
        <h3 class="text-gray-900 dark:text-white text-lg font-bold">Todos los Logros</h3>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Administra los logros existentes</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-[#324467]">
            <thead class="bg-gray-50 dark:bg-[#111a22]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Logro</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Condición</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Objetivo</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Puntos</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Estado</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-[#324467]">
                <?php while($logro = $logros->fetch_assoc()): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-[#1a2332]">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: <?php echo $logro['color']; ?>20;">
                                <span class="material-symbols-outlined" style="color: <?php echo $logro['color']; ?>">
                                    <?php echo $logro['icono']; ?>
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?php echo $logro['nombre']; ?></p>
                                <p class="text-xs text-gray-500 dark:text-[#92a4c9]"><?php echo substr($logro['descripcion'], 0, 50); ?>...</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                        <?php 
                        $condiciones = [
                            'ventas_count' => 'Ventas',
                            'clientes_count' => 'Clientes',
                            'aperturas_count' => 'Aperturas',
                            'arqueos_sin_error' => 'Arqueos',
                            'meta_alcanzada' => 'Meta',
                            'dias_consecutivos' => 'Días',
                            'inventario_updates' => 'Inventario'
                        ];
                        echo $condiciones[$logro['tipo_condicion']] ?? $logro['tipo_condicion'];
                        ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900 dark:text-white"><?php echo $logro['valor_objetivo']; ?></td>
                    <td class="px-6 py-4 text-sm text-yellow-500 font-bold"><?php echo $logro['puntos']; ?></td>
                    <td class="px-6 py-4">
                        <form method="POST" class="inline">
                            <input type="hidden" name="logro_id" value="<?php echo $logro['id']; ?>">
                            <input type="hidden" name="nuevo_estado" value="<?php echo $logro['activo'] ? 0 : 1; ?>">
                            <button type="submit" name="toggle_activo" class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $logro['activo'] ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400'; ?>">
                                <?php echo $logro['activo'] ? 'Activo' : 'Inactivo'; ?>
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <?php if ($logro['es_predefinido']): ?>
                                <span class="text-xs text-gray-500 dark:text-[#92a4c9]">Predefinido</span>
                            <?php else: ?>
                                <button onclick="editarLogro(<?php echo htmlspecialchars(json_encode($logro)); ?>)" class="text-blue-500 hover:text-blue-700" title="Editar">
                                    <span class="material-symbols-outlined text-sm">edit</span>
                                </button>
                                <form method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este logro?');">
                                    <input type="hidden" name="logro_id" value="<?php echo $logro['id']; ?>">
                                    <button type="submit" name="eliminar_logro" class="text-red-500 hover:text-red-700" title="Eliminar">
                                        <span class="material-symbols-outlined text-sm">delete</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
</main>
</div>

<!-- Modal de Edición -->
<div id="modalEditar" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" onclick="if(event.target === this) cerrarModal()">
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 rounded-full bg-blue-500/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-blue-500 text-2xl">edit</span>
                </div>
                <div>
                    <h2 class="text-gray-900 dark:text-white text-xl font-bold">Editar Logro</h2>
                    <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Modifica los datos del logro</p>
                </div>
            </div>
            <button onclick="cerrarModal()" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form method="POST" id="formEditar" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="logro_id" id="edit_logro_id">
            
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Nombre del Logro</label>
                <input type="text" name="nombre" id="edit_nombre" required placeholder="Ej: Super Vendedor"
                    class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Icono (Material Symbol)</label>
                <input type="text" name="icono" id="edit_icono" required placeholder="Ej: emoji_events"
                    class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Tipo de Condición</label>
                <select name="tipo_condicion" id="edit_tipo_condicion" required
                    class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
                    <option value="ventas_count">Cantidad de Ventas</option>
                    <option value="ventas_monto">Monto de Ventas</option>
                    <option value="clientes_count">Cantidad de Clientes</option>
                    <option value="aperturas_count">Aperturas de Caja</option>
                    <option value="arqueos_sin_error">Arqueos Sin Error</option>
                    <option value="meta_alcanzada">Meta Alcanzada</option>
                    <option value="dias_consecutivos">Días Consecutivos</option>
                    <option value="inventario_updates">Actualizaciones de Inventario</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Valor Objetivo</label>
                <input type="number" name="valor_objetivo" id="edit_valor_objetivo" required min="1" placeholder="Ej: 50"
                    class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Puntos</label>
                <input type="number" name="puntos" id="edit_puntos" required min="1" placeholder="Ej: 25"
                    class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Color</label>
                <input type="color" name="color" id="edit_color"
                    class="w-full h-10 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622]">
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Descripción</label>
                <textarea name="descripcion" id="edit_descripcion" rows="2" required placeholder="Describe el logro..."
                    class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white"></textarea>
            </div>
            
            <div class="md:col-span-2 flex gap-3">
                <button type="submit" name="editar_logro" class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-blue-500 text-white rounded-lg font-bold hover:bg-blue-600 transition-colors">
                    <span class="material-symbols-outlined">save</span>
                    Guardar Cambios
                </button>
                <button type="button" onclick="cerrarModal()" class="px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editarLogro(logro) {
    // Llenar el formulario con los datos del logro
    document.getElementById('edit_logro_id').value = logro.id;
    document.getElementById('edit_nombre').value = logro.nombre;
    document.getElementById('edit_descripcion').value = logro.descripcion;
    document.getElementById('edit_icono').value = logro.icono;
    document.getElementById('edit_tipo_condicion').value = logro.tipo_condicion;
    document.getElementById('edit_valor_objetivo').value = logro.valor_objetivo;
    document.getElementById('edit_puntos').value = logro.puntos;
    document.getElementById('edit_color').value = logro.color;
    
    // Mostrar el modal
    document.getElementById('modalEditar').classList.remove('hidden');
    document.getElementById('modalEditar').classList.add('flex');
}

function cerrarModal() {
    document.getElementById('modalEditar').classList.add('hidden');
    document.getElementById('modalEditar').classList.remove('flex');
}

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModal();
    }
});
</script>

</body></html>
<?php
$conexion->close();
?>
