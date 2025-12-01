<?php
/**
 * Selector de Negocio y Sucursal
 * Permite cambiar el contexto de trabajo
 */
session_start();
require_once 'db_connect.php';
require_once 'funciones.php';

VerificarSiUsuarioYaInicioSesion();

// Handle context change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_negocio']) && isset($_POST['id_sucursal'])) {
    $id_negocio = intval($_POST['id_negocio']);
    $id_sucursal = intval($_POST['id_sucursal']);
    
    setCurrentContext($id_negocio, $id_sucursal);
    
    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => true]);
        exit;
    }
    
    header('Location: ' . ($_POST['redirect'] ?? 'index.php'));
    exit;
}

$current_negocio = getCurrentNegocio($conexion);
$current_sucursal = getCurrentSucursal($conexion);
$user_negocios = getUserNegocios($conexion, $_SESSION['usuario']);
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <title>Cambiar Contexto</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
</head>
<body class="bg-gray-900 text-white p-8">
    <div class="max-w-md mx-auto">
        <h1 class="text-2xl font-bold mb-6">Cambiar Negocio y Sucursal</h1>
        
        <div class="bg-gray-800 p-6 rounded-lg mb-4">
            <p class="text-sm text-gray-400 mb-2">Contexto Actual:</p>
            <p class="font-bold"><?php echo htmlspecialchars($current_negocio['nombre'] ?? 'N/A'); ?></p>
            <p class="text-sm"><?php echo htmlspecialchars($current_sucursal['nombre'] ?? 'N/A'); ?></p>
        </div>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block mb-2">Negocio</label>
                <select name="id_negocio" id="negocio_select" onchange="loadSucursales()" class="w-full px-4 py-2 bg-gray-700 rounded">
                    <?php foreach ($user_negocios as $negocio): ?>
                        <option value="<?php echo $negocio['id']; ?>" <?php echo ($current_negocio && $current_negocio['id'] == $negocio['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($negocio['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block mb-2">Sucursal</label>
                <select name="id_sucursal" id="sucursal_select" class="w-full px-4 py-2 bg-gray-700 rounded">
                    <?php 
                    if ($current_negocio) {
                        $sucursales = getUserSucursales($conexion, $_SESSION['usuario'], $current_negocio['id']);
                        foreach ($sucursales as $sucursal): 
                    ?>
                        <option value="<?php echo $sucursal['id']; ?>" <?php echo ($current_sucursal && $current_sucursal['id'] == $sucursal['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sucursal['nombre']); ?>
                        </option>
                    <?php 
                        endforeach;
                    }
                    ?>
                </select>
            </div>
            
            <div class="flex gap-4">
                <a href="index.php" class="flex-1 px-4 py-2 bg-gray-700 rounded text-center">Cancelar</a>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 rounded">Cambiar</button>
            </div>
        </form>
    </div>
    
    <script>
    function loadSucursales() {
        const negocioId = document.getElementById('negocio_select').value;
        const sucursalSelect = document.getElementById('sucursal_select');
        
        fetch(`get_sucursales.php?id_negocio=${negocioId}`)
            .then(r => r.json())
            .then(data => {
                sucursalSelect.innerHTML = '';
                data.forEach(s => {
                    const option = document.createElement('option');
                    option.value = s.id;
                    option.textContent = s.nombre;
                    sucursalSelect.appendChild(option);
                });
            });
    }
    </script>
</body>
</html>
