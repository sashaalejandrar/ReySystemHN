<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'funciones.php';

VerificarSiUsuarioYaInicioSesion();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener información del usuario
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

$rol_usuario = strtolower($Rol);

// Procesar formulario
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_cliente'])) {
    $nombre = trim($_POST['nombre']);
    $celular = trim($_POST['celular']);
    $direccion = trim($_POST['direccion']);
    $email = trim($_POST['email']);
    $identidad = trim($_POST['identidad']);
    $notas = trim($_POST['notas']);
    
    // Validar campos requeridos
    if (empty($nombre) || empty($celular)) {
        $mensaje = 'El nombre y celular son obligatorios';
        $tipo_mensaje = 'error';
    } else {
        // Insertar cliente
        $stmt = $conexion->prepare("INSERT INTO clientes (Nombre, Celular, Direccion, Identidad, Notas) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $nombre, $celular, $direccion, $identidad, $notas);
        
        if ($stmt->execute()) {
            $mensaje = 'Cliente creado exitosamente';
            $tipo_mensaje = 'success';
            // Limpiar campos
            $nombre = $celular = $direccion = $email = $identidad = $notas = '';
        } else {
            $mensaje = 'Error al crear el cliente: ' . $stmt->error;
            $tipo_mensaje = 'error';
        }
        
        $stmt->close();
    }
}

// Obtener estadísticas
$total_clientes = $conexion->query("SELECT COUNT(*) as total FROM clientes")->fetch_assoc()['total'];
$clientes_mes = $conexion->query("SELECT COUNT(*) as total FROM clientes WHERE MONTH(Fecha_Registro) = MONTH(CURDATE()) AND YEAR(Fecha_Registro) = YEAR(CURDATE())")->fetch_assoc()['total'];
$ultimos_clientes = $conexion->query("SELECT Nombre, Celular, Fecha_Registro FROM clientes ORDER BY Fecha_Registro DESC LIMIT 5");
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Crear Cliente - Rey System</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
<script>
tailwind.config = {
    darkMode: "class",
    theme: {
        extend: {
            colors: {
                "primary": "#137fec",
            },
            fontFamily: {
                "display": ["Inter", "sans-serif"]
            }
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
    align-items: center;
    gap: 12px;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    animation: slideIn 0.3s ease-out;
}
.notification.show { display: flex; }
.notification.success {
    background: #10b981;
    color: white;
}
.notification.error {
    background: #ef4444;
    color: white;
}
@keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>
</head>
<body class="bg-[#f6f7f8] dark:bg-[#101922] font-display">

<!-- Notificación -->
<div id="notification" class="notification">
    <span class="material-symbols-outlined" id="notificationIcon"></span>
    <div>
        <p class="font-bold" id="notificationTitle"></p>
        <p class="text-sm" id="notificationMessage"></p>
    </div>
</div>

<div class="flex min-h-screen w-full">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 p-8">
<div class="mx-auto max-w-7xl">

<!-- Header -->
<div class="flex items-center justify-between mb-8">
    <div>
        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">Crear Nuevo Cliente</h1>
        <p class="text-gray-500 dark:text-[#92a4c9] text-base mt-2">Registra un nuevo cliente en el sistema</p>
    </div>
    <a href="clientes.php" class="flex items-center gap-2 px-6 py-3 bg-gray-200 dark:bg-[#192233] text-gray-900 dark:text-white rounded-lg font-bold hover:bg-gray-300 dark:hover:bg-[#232f48] transition-colors">
        <span class="material-symbols-outlined">arrow_back</span>
        Volver a Clientes
    </a>
</div>

<!-- Estadísticas rápidas -->
<div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-medium">Total Clientes</p>
                <p class="text-gray-900 dark:text-white text-2xl font-bold mt-1"><?php echo number_format($total_clientes); ?></p>
            </div>
            <span class="material-symbols-outlined text-primary text-4xl">group</span>
        </div>
    </div>
    <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-medium">Nuevos Este Mes</p>
                <p class="text-gray-900 dark:text-white text-2xl font-bold mt-1"><?php echo number_format($clientes_mes); ?></p>
            </div>
            <span class="material-symbols-outlined text-green-500 text-4xl">trending_up</span>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Formulario -->
    <div class="lg:col-span-2">
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary text-2xl">person_add</span>
                </div>
                <div>
                    <h2 class="text-gray-900 dark:text-white text-xl font-bold">Información del Cliente</h2>
                    <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Completa los datos del nuevo cliente</p>
                </div>
            </div>

            <form method="POST" class="space-y-6">
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Nombre Completo <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-[#92a4c9]">person</span>
                        <input type="text" name="nombre" required 
                            class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Ej: Juan Pérez García"
                            value="<?php echo isset($nombre) ? htmlspecialchars($nombre) : ''; ?>">
                    </div>
                </div>

                <!-- Celular e Identidad -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            Celular <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-[#92a4c9]">phone</span>
                            <input type="tel" name="celular" required 
                                class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="Ej: 9999-9999"
                                value="<?php echo isset($celular) ? htmlspecialchars($celular) : ''; ?>">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                            Identidad (Opcional)
                        </label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-[#92a4c9]">badge</span>
                            <input type="text" name="identidad" 
                                class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                                placeholder="Ej: 0801-1990-12345"
                                value="<?php echo isset($identidad) ? htmlspecialchars($identidad) : ''; ?>">
                        </div>
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Email (Opcional)
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-[#92a4c9]">mail</span>
                        <input type="email" name="email" 
                            class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Ej: cliente@ejemplo.com"
                            value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                </div>

                <!-- Dirección -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Dirección
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-3 text-gray-400 dark:text-[#92a4c9]">location_on</span>
                        <textarea name="direccion" rows="3" 
                            class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Ej: Col. Kennedy, Calle Principal, Casa #123"><?php echo isset($direccion) ? htmlspecialchars($direccion) : ''; ?></textarea>
                    </div>
                </div>

                <!-- Notas -->
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Notas Adicionales (Opcional)
                    </label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-3 text-gray-400 dark:text-[#92a4c9]">note</span>
                        <textarea name="notas" rows="3" 
                            class="w-full pl-12 pr-4 py-3 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                            placeholder="Información adicional sobre el cliente..."><?php echo isset($notas) ? htmlspecialchars($notas) : ''; ?></textarea>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex gap-4 pt-4">
                    <button type="submit" name="crear_cliente" 
                        class="flex-1 flex items-center justify-center gap-2 px-6 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition-colors">
                        <span class="material-symbols-outlined">add_circle</span>
                        Crear Cliente
                    </button>
                    <button type="reset" 
                        class="px-6 py-3 border border-gray-200 dark:border-[#324467] text-gray-900 dark:text-white rounded-lg font-bold hover:bg-gray-100 dark:hover:bg-[#232f48] transition-colors">
                        Limpiar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sidebar - Últimos clientes -->
    <div class="lg:col-span-1">
        <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
            <div class="flex items-center gap-3 mb-4">
                <span class="material-symbols-outlined text-primary text-2xl">history</span>
                <h3 class="text-gray-900 dark:text-white text-lg font-bold">Últimos Clientes</h3>
            </div>
            
            <div class="space-y-3">
                <?php if ($ultimos_clientes->num_rows > 0): ?>
                    <?php while($cliente = $ultimos_clientes->fetch_assoc()): ?>
                        <div class="p-3 rounded-lg bg-gray-50 dark:bg-[#101622] border border-gray-200 dark:border-[#324467]">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center flex-shrink-0">
                                    <span class="material-symbols-outlined text-primary text-xl">person</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($cliente['Nombre']); ?></p>
                                    <p class="text-xs text-gray-500 dark:text-[#92a4c9]"><?php echo htmlspecialchars($cliente['Celular']); ?></p>
                                    <p class="text-xs text-gray-400 dark:text-[#92a4c9] mt-1">
                                        <?php echo date('d/m/Y', strtotime($cliente['Fecha_Registro'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-8">
                        <span class="material-symbols-outlined text-gray-400 dark:text-[#92a4c9] text-4xl">person_off</span>
                        <p class="text-gray-500 dark:text-[#92a4c9] text-sm mt-2">No hay clientes registrados</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tips -->
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6 mt-6">
            <div class="flex items-start gap-3">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">lightbulb</span>
                <div>
                    <h4 class="text-blue-900 dark:text-blue-300 font-bold text-sm mb-2">Consejos</h4>
                    <ul class="text-blue-800 dark:text-blue-400 text-xs space-y-1">
                        <li>• Verifica el número de celular</li>
                        <li>• La dirección ayuda en entregas</li>
                        <li>• Usa notas para recordatorios</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
</main>
</div>

<script>
<?php if (!empty($mensaje)): ?>
    showNotification('<?php echo $tipo_mensaje; ?>', '<?php echo $tipo_mensaje === 'success' ? 'Éxito' : 'Error'; ?>', '<?php echo addslashes($mensaje); ?>');
<?php endif; ?>

function showNotification(type, title, message) {
    const notification = document.getElementById('notification');
    const icon = document.getElementById('notificationIcon');
    const titleEl = document.getElementById('notificationTitle');
    const messageEl = document.getElementById('notificationMessage');
    
    notification.className = `notification ${type}`;
    titleEl.textContent = title;
    messageEl.textContent = message;
    
    if (type === 'success') {
        icon.textContent = 'check_circle';
    } else if (type === 'error') {
        icon.textContent = 'error';
    }
    
    notification.classList.add('show');
    
    setTimeout(() => {
        notification.classList.remove('show');
    }, 5000);
}
</script>

</body></html>
<?php
$conexion->close();
?>
