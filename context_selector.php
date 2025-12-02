<?php
/**
 * Business and Branch Selector Component
 * Allows users to select their active business and branch context
 */

if (!isset($conexion)) {
    die('Database connection required');
}

// Handle context change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_context'])) {
    $id_negocio = intval($_POST['id_negocio']);
    $id_sucursal = intval($_POST['id_sucursal']);
    
    if (checkUserAccess($conexion, $_SESSION['usuario'], $id_negocio, $id_sucursal)) {
        setCurrentContext($id_negocio, $id_sucursal);
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error_context = "No tienes acceso a este negocio/sucursal";
    }
}

$current_negocio = getCurrentNegocio($conexion);
$current_sucursal = getCurrentSucursal($conexion);
$user_negocios = getUserNegocios($conexion, $_SESSION['usuario']);
?>

<!-- Business/Branch Context Selector -->
<div class="business-context-selector" x-data="{ open: false }">
    <button @click="open = !open" class="flex items-center gap-2 px-4 py-2 bg-gray-800 dark:bg-[#232f48] rounded-lg hover:bg-gray-700 transition">
        <span class="material-symbols-outlined">store</span>
        <div class="text-left">
            <div class="text-sm font-semibold">
                <?php echo $current_negocio ? htmlspecialchars($current_negocio['nombre']) : 'Sin negocio'; ?>
            </div>
            <div class="text-xs text-gray-400">
                <?php echo $current_sucursal ? htmlspecialchars($current_sucursal['nombre']) : 'Sin sucursal'; ?>
            </div>
        </div>
        <span class="material-symbols-outlined text-sm">expand_more</span>
    </button>
    
    <div x-show="open" @click.away="open = false" class="absolute mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50">
        <div class="p-4">
            <h3 class="font-bold mb-3">Cambiar Contexto</h3>
            
            <?php if (isset($error_context)): ?>
                <div class="mb-3 p-2 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded text-sm">
                    <?php echo $error_context; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-3">
                <input type="hidden" name="change_context" value="1">
                
                <div>
                    <label class="block text-sm font-medium mb-1">Negocio</label>
                    <select name="id_negocio" id="context_negocio" onchange="loadSucursales(this.value)" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                        <?php foreach ($user_negocios as $negocio): ?>
                            <option value="<?php echo $negocio['id']; ?>" <?php echo ($current_negocio && $current_negocio['id'] == $negocio['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($negocio['nombre']); ?> (<?php echo $negocio['tipo_negocio']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-1">Sucursal</label>
                    <select name="id_sucursal" id="context_sucursal" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:outline-none">
                        <?php 
                        if ($current_negocio) {
                            $sucursales = getUserSucursales($conexion, $_SESSION['usuario'], $current_negocio['id']);
                            foreach ($sucursales as $sucursal): 
                        ?>
                            <option value="<?php echo $sucursal['id']; ?>" <?php echo ($current_sucursal && $current_sucursal['id'] == $sucursal['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sucursal['nombre']); ?> (<?php echo $sucursal['codigo']; ?>)
                            </option>
                        <?php 
                            endforeach;
                        }
                        ?>
                    </select>
                </div>
                
                <button type="submit" class="w-full px-4 py-2 bg-primary text-white rounded-lg font-semibold hover:bg-blue-700 transition">
                    Cambiar Contexto
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function loadSucursales(id_negocio) {
    const sucursalSelect = document.getElementById('context_sucursal');
    sucursalSelect.innerHTML = '<option>Cargando...</option>';
    
    fetch(`get_sucursales.php?id_negocio=${id_negocio}`)
        .then(r => r.json())
        .then(data => {
            sucursalSelect.innerHTML = '';
            data.forEach(s => {
                const option = document.createElement('option');
                option.value = s.id;
                option.textContent = `${s.nombre} (${s.codigo})`;
                sucursalSelect.appendChild(option);
            });
        });
}
</script>

<style>
.business-context-selector {
    position: relative;
}
</style>
