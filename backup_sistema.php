<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'funciones.php';
date_default_timezone_set('America/Tegucigalpa');

VerificarSiUsuarioYaInicioSesion();

// Solo admin puede acceder
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$resultado = $conexion->query("SELECT Rol FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
$row = $resultado->fetch_assoc();
$Rol = $row['Rol'];

if (strtolower($Rol) !== 'admin') {
    header("Location: index.php");
    exit();
}

$mensaje = '';
$tipo_mensaje = '';

// Procesar backup
if (isset($_POST['crear_backup'])) {
    $fecha = date('Y-m-d_H-i-s');
    $archivo = "backup_tiendasrey_$fecha.sql";
    $ruta = __DIR__ . "/backups/$archivo";
    
    // Crear directorio si no existe
    if (!is_dir(__DIR__ . "/backups")) {
        if (!mkdir(__DIR__ . "/backups", 0755, true)) {
            $mensaje = "Error: No se pudo crear el directorio de backups";
            $tipo_mensaje = 'error';
        }
    }
    
    if (empty($mensaje)) {
        // Escapar la ruta para evitar problemas con espacios
        $ruta_escapada = escapeshellarg($ruta);
        $comando = "/opt/lampp/bin/mysqldump -u root tiendasrey 2>&1 > $ruta_escapada";
        
        exec($comando, $output, $return_var);
        
        if ($return_var === 0 && file_exists($ruta) && filesize($ruta) > 0) {
            $tamano = number_format(filesize($ruta) / 1024, 2);
            $mensaje = "✅ Backup creado exitosamente: $archivo ($tamano KB)";
            $tipo_mensaje = 'success';
        } else {
            // Capturar el error específico
            $error_detail = implode("\n", $output);
            
            // Verificar si el archivo se creó pero está vacío
            if (file_exists($ruta) && filesize($ruta) === 0) {
                unlink($ruta); // Eliminar archivo vacío
                $mensaje = "❌ Error: El backup se creó vacío. Verifica los permisos de MySQL.";
            } else if (!empty($error_detail)) {
                $mensaje = "❌ Error al crear backup: " . htmlspecialchars($error_detail);
            } else {
                $mensaje = "❌ Error desconocido al crear el backup. Código: $return_var";
            }
            
            $tipo_mensaje = 'error';
        }
    }
}

// Descargar backup
if (isset($_GET['descargar'])) {
    $archivo = basename($_GET['descargar']);
    $ruta = __DIR__ . "/backups/$archivo";
    
    if (file_exists($ruta)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header('Content-Length: ' . filesize($ruta));
        readfile($ruta);
        exit();
    }
}

// Eliminar backup
if (isset($_POST['eliminar_backup'])) {
    $archivo = basename($_POST['archivo']);
    $ruta = __DIR__ . "/backups/$archivo";
    
    if (file_exists($ruta) && unlink($ruta)) {
        $mensaje = "Backup eliminado exitosamente";
        $tipo_mensaje = 'success';
    } else {
        $mensaje = "Error al eliminar el backup";
        $tipo_mensaje = 'error';
    }
}

// Listar backups
$backups = [];
if (is_dir(__DIR__ . "/backups")) {
    $archivos = scandir(__DIR__ . "/backups");
    foreach ($archivos as $archivo) {
        if (pathinfo($archivo, PATHINFO_EXTENSION) === 'sql') {
            $ruta = __DIR__ . "/backups/$archivo";
            $backups[] = [
                'nombre' => $archivo,
                'tamano' => filesize($ruta),
                'fecha' => filemtime($ruta)
            ];
        }
    }
    usort($backups, function($a, $b) {
        return $b['fecha'] - $a['fecha'];
    });
}

$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Email = $row['Email'];
    $Celular = $row['Celular'];
    $Perfil = $row['Perfil'];
}

// --- INICIO DE LA LÓGICA DE PERMISOS ---
// Convertimos el rol a minúsculas para hacer la comparación insensible a mayúsculas/minúsculas.
$rol_usuario = strtolower($Rol);
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Backup del Sistema - Rey System</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: { "primary": "#137fec" },
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

<div class="mb-8">
    <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">Backup del Sistema</h1>
    <p class="text-gray-500 dark:text-[#92a4c9] text-base mt-2">Gestiona los respaldos de tu base de datos</p>
</div>

<!-- Crear backup -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
            <span class="material-symbols-outlined text-primary text-2xl">backup</span>
        </div>
        <div>
            <h2 class="text-gray-900 dark:text-white text-xl font-bold">Crear Nuevo Backup</h2>
            <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Genera un respaldo completo de la base de datos</p>
        </div>
    </div>
    <form method="POST">
        <button type="submit" name="crear_backup" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90">
            <span class="material-symbols-outlined">add_circle</span>
            Crear Backup Ahora
        </button>
    </form>
</div>

<!-- Lista de backups -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
    <div class="p-6 border-b border-gray-200 dark:border-[#324467]">
        <h3 class="text-gray-900 dark:text-white text-lg font-bold">Backups Disponibles</h3>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Total: <?php echo count($backups); ?> archivos</p>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-[#324467]">
            <thead class="bg-gray-50 dark:bg-[#111a22]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Archivo</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Tamaño</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-[#324467]">
                <?php if (count($backups) > 0): ?>
                    <?php foreach($backups as $backup): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#1a2332]">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-outlined text-primary">description</span>
                                    <?php echo htmlspecialchars($backup['nombre']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-[#92a4c9]">
                                <?php echo date('d/m/Y H:i:s', $backup['fecha']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-[#92a4c9]">
                                <?php echo number_format($backup['tamano'] / 1024, 2); ?> KB
                            </td>
                            <td class="px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="?descargar=<?php echo urlencode($backup['nombre']); ?>" 
                                       class="flex items-center gap-1 px-3 py-1 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                        <span class="material-symbols-outlined text-sm">download</span>
                                        Descargar
                                    </a>
                                    <form method="POST" class="inline" onsubmit="return confirm('¿Eliminar este backup?');">
                                        <input type="hidden" name="archivo" value="<?php echo htmlspecialchars($backup['nombre']); ?>">
                                        <button type="submit" name="eliminar_backup" 
                                                class="flex items-center gap-1 px-3 py-1 bg-red-500 text-white rounded-lg hover:bg-red-600">
                                            <span class="material-symbols-outlined text-sm">delete</span>
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-16 text-center">
                            <span class="material-symbols-outlined text-gray-400 dark:text-[#92a4c9] text-4xl">folder_off</span>
                            <p class="text-gray-500 dark:text-[#92a4c9] mt-2">No hay backups disponibles</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Información -->
<div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6 mt-6">
    <div class="flex items-start gap-3">
        <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">info</span>
        <div>
            <h4 class="text-blue-900 dark:text-blue-300 font-bold text-sm mb-2">Recomendaciones</h4>
            <ul class="text-blue-800 dark:text-blue-400 text-xs space-y-1">
                <li>• Crea backups regularmente (diario o semanal)</li>
                <li>• Descarga y guarda los backups en un lugar seguro</li>
                <li>• Verifica que los backups se puedan restaurar</li>
                <li>• Elimina backups antiguos para liberar espacio</li>
            </ul>
        </div>
    </div>
</div>

</div>
</main>
</div>

</body></html>
<?php
$conexion->close();
?>
