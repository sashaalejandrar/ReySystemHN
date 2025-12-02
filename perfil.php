<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'funciones.php';
require_once 'db_connect.php';

VerificarSiUsuarioYaInicioSesion();

// Obtener datos del usuario logueado
$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Usuario_Logueado = $row['Usuario'];
    $Nombre_Logueado = $row['Nombre'];
    $Apellido_Logueado = $row['Apellido'];
    $Nombre_Completo_Logueado = $Nombre_Logueado." ".$Apellido_Logueado;
    $Email_Logueado = $row['Email'];
    $Celular_Logueado = $row['Celular'];
    $Perfil_Logueado = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);

// Determinar qué usuario mostrar (el logueado o uno específico por GET)
$usuario_a_mostrar = isset($_GET['usuario']) ? $_GET['usuario'] : $Usuario_Logueado;

// Obtener datos del usuario a mostrar
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE Usuario = ?");
$stmt->bind_param("s", $usuario_a_mostrar);
$stmt->execute();
$resultado_perfil = $stmt->get_result();

if ($resultado_perfil->num_rows == 0) {
    die("Usuario no encontrado");
}

$perfil_data = $resultado_perfil->fetch_assoc();
$Usuario = $perfil_data['Usuario'];
$Nombre = $perfil_data['Nombre'];
$Apellido = $perfil_data['Apellido'];
$Nombre_Completo = $Nombre." ".$Apellido;
$Email = $perfil_data['Email'];
$Celular = $perfil_data['Celular'];
$Perfil = $perfil_data['Perfil'];
$Rol_Perfil = $perfil_data['Rol'];

// ========== ESTADÍSTICAS DEL USUARIO ==========

// Logros completados y pendientes
$logros_completados = $conexion->query("
    SELECT COUNT(*) as total FROM usuarios_logros 
    WHERE usuario = '$Usuario' AND completado = 1
")->fetch_assoc()['total'];

$logros_pendientes = $conexion->query("
    SELECT COUNT(*) as total FROM logros l
    LEFT JOIN usuarios_logros ul ON l.id = ul.logro_id AND ul.usuario = '$Usuario'
    WHERE l.activo = 1 AND (ul.completado IS NULL OR ul.completado = 0)
")->fetch_assoc()['total'];

// Puntos totales de logros
$puntos_logros = $conexion->query("
    SELECT COALESCE(SUM(l.puntos), 0) as total FROM usuarios_logros ul
    JOIN logros l ON ul.logro_id = l.id
    WHERE ul.usuario = '$Usuario' AND ul.completado = 1
")->fetch_assoc()['total'];

// Ventas realizadas
$ventas_stats = $conexion->query("
    SELECT COUNT(*) as total_ventas, COALESCE(SUM(Total), 0) as monto_total
    FROM ventas WHERE Vendedor = '$Usuario'
")->fetch_assoc();

// Deudas por cobrar (como vendedor)
$deudas_cobrar = $conexion->query("
    SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto
    FROM deudas WHERE Vendedor = '$Usuario' AND estado = 'Pendiente'
")->fetch_assoc();

// Deudas por pagar (como cliente - deudas donde el usuario es el deudor)
// Nota: La tabla clientes no tiene columna creado_por, así que buscamos por vendedor
$deudas_pagar = $conexion->query("
    SELECT COUNT(*) as total, COALESCE(SUM(monto), 0) as monto
    FROM deudas WHERE vendedor = '$Usuario' AND estado = 'Pendiente'
")->fetch_assoc();

// Operaciones de caja
$aperturas_caja = $conexion->query("
    SELECT COUNT(*) as total FROM caja WHERE usuario = '$Usuario'
")->fetch_assoc()['total'];

$arqueo_caja = $conexion->query("
    SELECT COUNT(*) as total FROM arqueo_caja WHERE usuario = '$Usuario'
")->fetch_assoc()['total'];

$cierres_caja = $conexion->query("
    SELECT COUNT(*) as total FROM cierre_caja WHERE usuario = '$Usuario'
")->fetch_assoc()['total'];

// Arqueos perfectos (sin sobrante ni faltante)
$arqueos_perfectos = $conexion->query("
    SELECT COUNT(*) as total FROM arqueo_caja 
    WHERE usuario = '$Usuario' AND sobrante = 0 AND faltante = 0
")->fetch_assoc()['total'];

// Últimos logros desbloqueados
$ultimos_logros = $conexion->query("
    SELECT l.*, ul.fecha_desbloqueo FROM usuarios_logros ul
    JOIN logros l ON ul.logro_id = l.id
    WHERE ul.usuario = '$Usuario' AND ul.completado = 1
    ORDER BY ul.fecha_desbloqueo DESC LIMIT 5
");

// Logros pendientes más cercanos
$logros_pendientes_query = $conexion->query("
    SELECT l.*, COALESCE(ul.progreso_actual, 0) as progreso
    FROM logros l
    LEFT JOIN usuarios_logros ul ON l.id = ul.logro_id AND ul.usuario = '$Usuario'
    WHERE l.activo = 1 AND (ul.completado IS NULL OR ul.completado = 0)
    ORDER BY (COALESCE(ul.progreso_actual, 0) / l.valor_objetivo) DESC
    LIMIT 5
");

// Obtener lista de usuarios para comparación
$usuarios_lista = $conexion->query("SELECT Usuario, Nombre, Apellido, Perfil FROM usuarios WHERE Usuario != '$Usuario_Logueado' ORDER BY Nombre, Apellido");

?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Perfil de <?php echo $Nombre_Completo; ?> - Rey System</title>
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
        
        .stat-card {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(17, 82, 212, 0.2);
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>

<!-- Main Content -->
<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">

<!-- Selector de Usuario -->
<div class="mb-6 bg-white dark:bg-[#192233] p-4 rounded-xl shadow-sm">
    <div class="flex flex-wrap items-center gap-4">
        <span class="material-symbols-outlined text-primary text-2xl">group</span>
        <div class="flex-1">
            <label class="text-sm font-medium text-gray-600 dark:text-[#92a4c9] mb-2 block">Ver perfil de otro usuario:</label>
            <select onchange="window.location.href='perfil.php?usuario='+this.value" class="form-select w-full max-w-md rounded-lg border-gray-300 dark:border-[#324467] bg-gray-50 dark:bg-[#111722] text-gray-800 dark:text-white focus:ring-2 focus:ring-primary">
                <option value="<?php echo $Usuario_Logueado; ?>" <?php echo $Usuario == $Usuario_Logueado ? 'selected' : ''; ?>>Mi Perfil</option>
                <?php while($user = $usuarios_lista->fetch_assoc()): ?>
                    <option value="<?php echo $user['Usuario']; ?>" <?php echo $Usuario == $user['Usuario'] ? 'selected' : ''; ?>>
                        <?php echo $user['Nombre'].' '.$user['Apellido'].' (@'.$user['Usuario'].')'; ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
    </div>
</div>

<!-- Header del Perfil (Estilo Facebook) -->
<div class="bg-gradient-to-r from-primary to-blue-600 rounded-xl p-8 mb-6 shadow-lg">
    <div class="flex flex-col md:flex-row items-center gap-6">
        <div class="relative">
            <img src="<?php echo !empty($Perfil) ? $Perfil : 'https://ui-avatars.com/api/?name='.urlencode($Nombre_Completo).'&size=200&background=1152d4&color=fff'; ?>" 
                 alt="Foto de perfil" 
                 class="w-32 h-32 rounded-full border-4 border-white dark:border-gray-800 shadow-xl object-cover"/>
            <?php if($Usuario == $Usuario_Logueado): ?>
                <span class="absolute bottom-2 right-2 w-6 h-6 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></span>
            <?php endif; ?>
        </div>
        <div class="flex-1 text-center md:text-left">
            <h1 class="text-white text-4xl font-black mb-2"><?php echo $Nombre_Completo; ?></h1>
            <p class="text-blue-100 text-lg mb-1">@<?php echo $Usuario; ?></p>
            <div class="flex flex-wrap gap-2 justify-center md:justify-start mt-3">
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-semibold">
                    <span class="material-symbols-outlined text-sm align-middle">badge</span> <?php echo $Rol_Perfil; ?>
                </span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm">
                    <span class="material-symbols-outlined text-sm align-middle">email</span> <?php echo $Email; ?>
                </span>
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm">
                    <span class="material-symbols-outlined text-sm align-middle">phone</span> <?php echo $Celular; ?>
                </span>
            </div>
        </div>
        <div class="text-center">
            <div class="bg-white/20 backdrop-blur-sm rounded-xl p-4">
                <div class="text-3xl font-black text-white"><?php echo $puntos_logros; ?></div>
                <div class="text-blue-100 text-sm">Puntos de Logros</div>
            </div>
        </div>
    </div>
</div>

<!-- Grid de Estadísticas -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <!-- Ventas -->
    <div class="stat-card bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
        <div class="flex items-center justify-between mb-4">
            <span class="material-symbols-outlined text-green-500 text-4xl">shopping_cart</span>
            <span class="text-2xl font-black text-gray-900 dark:text-white">
                <?php echo $ventas_stats['total_ventas']; ?>
            </span>
        </div>
        <h3 class="text-gray-600 dark:text-[#92a4c9] text-sm font-semibold mb-1">Ventas Realizadas</h3>
        <p class="text-green-600 dark:text-green-400 text-lg font-bold">
            L <?php echo number_format($ventas_stats['monto_total'], 2); ?>
        </p>
    </div>

    <!-- Logros -->
    <div class="stat-card bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
        <div class="flex items-center justify-between mb-4">
            <span class="material-symbols-outlined text-yellow-500 text-4xl">emoji_events</span>
            <span class="text-2xl font-black text-gray-900 dark:text-white">
                <?php echo $logros_completados; ?>
            </span>
        </div>
        <h3 class="text-gray-600 dark:text-[#92a4c9] text-sm font-semibold mb-1">Logros Completados</h3>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm">
            <?php echo $logros_pendientes; ?> pendientes
        </p>
    </div>

    <!-- Deudas por Cobrar -->
    <div class="stat-card bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
        <div class="flex items-center justify-between mb-4">
            <span class="material-symbols-outlined text-blue-500 text-4xl">account_balance_wallet</span>
            <span class="text-2xl font-black text-gray-900 dark:text-white">
                <?php echo $deudas_cobrar['total']; ?>
            </span>
        </div>
        <h3 class="text-gray-600 dark:text-[#92a4c9] text-sm font-semibold mb-1">Por Cobrar</h3>
        <p class="text-blue-600 dark:text-blue-400 text-lg font-bold">
            L <?php echo number_format($deudas_cobrar['monto'], 2); ?>
        </p>
    </div>

    <!-- Operaciones de Caja -->
    <div class="stat-card bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
        <div class="flex items-center justify-between mb-4">
            <span class="material-symbols-outlined text-purple-500 text-4xl">point_of_sale</span>
            <span class="text-2xl font-black text-gray-900 dark:text-white">
                <?php echo $aperturas_caja + $arqueo_caja + $cierres_caja; ?>
            </span>
        </div>
        <h3 class="text-gray-600 dark:text-[#92a4c9] text-sm font-semibold mb-1">Operaciones de Caja</h3>
        <p class="text-gray-500 dark:text-[#92a4c9] text-sm">
            <?php echo $arqueos_perfectos; ?> arqueos perfectos
        </p>
    </div>
</div>

<!-- Detalles de Caja -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
        <div class="flex items-center gap-3 mb-2">
            <span class="material-symbols-outlined text-green-500">lock_open</span>
            <h3 class="text-gray-900 dark:text-white font-bold">Aperturas</h3>
        </div>
        <p class="text-3xl font-black text-gray-900 dark:text-white"><?php echo $aperturas_caja; ?></p>
    </div>

    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
        <div class="flex items-center gap-3 mb-2">
            <span class="material-symbols-outlined text-blue-500">calculate</span>
            <h3 class="text-gray-900 dark:text-white font-bold">Arqueos</h3>
        </div>
        <p class="text-3xl font-black text-gray-900 dark:text-white"><?php echo $arqueo_caja; ?></p>
    </div>

    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
        <div class="flex items-center gap-3 mb-2">
            <span class="material-symbols-outlined text-red-500">lock</span>
            <h3 class="text-gray-900 dark:text-white font-bold">Cierres</h3>
        </div>
        <p class="text-3xl font-black text-gray-900 dark:text-white"><?php echo $cierres_caja; ?></p>
    </div>
</div>

<!-- Logros y Progreso -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Últimos Logros Desbloqueados -->
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-yellow-500">star</span>
            Últimos Logros Desbloqueados
        </h2>
        <?php if($ultimos_logros->num_rows > 0): ?>
            <div class="space-y-3">
                <?php while($logro = $ultimos_logros->fetch_assoc()): ?>
                    <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-[#111722] rounded-lg">
                        <span class="material-symbols-outlined text-3xl" style="color: <?php echo $logro['color']; ?>">
                            <?php echo $logro['icono']; ?>
                        </span>
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 dark:text-white"><?php echo $logro['nombre']; ?></h4>
                            <p class="text-sm text-gray-600 dark:text-[#92a4c9]"><?php echo $logro['descripcion']; ?></p>
                            <p class="text-xs text-gray-500 dark:text-[#92a4c9] mt-1">
                                Desbloqueado: <?php echo date('d/m/Y', strtotime($logro['fecha_desbloqueo'])); ?>
                            </p>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-yellow-500">+<?php echo $logro['puntos']; ?></div>
                            <div class="text-xs text-gray-500 dark:text-[#92a4c9]">puntos</div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 dark:text-[#92a4c9] text-center py-8">Aún no hay logros desbloqueados</p>
        <?php endif; ?>
    </div>

    <!-- Logros Pendientes -->
    <div class="bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467]">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <span class="material-symbols-outlined text-gray-400">flag</span>
            Próximos Logros
        </h2>
        <?php if($logros_pendientes_query->num_rows > 0): ?>
            <div class="space-y-3">
                <?php while($logro = $logros_pendientes_query->fetch_assoc()): ?>
                    <?php 
                        $porcentaje = ($logro['progreso'] / $logro['valor_objetivo']) * 100;
                        $porcentaje = min($porcentaje, 100);
                    ?>
                    <div class="p-3 bg-gray-50 dark:bg-[#111722] rounded-lg">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="material-symbols-outlined text-2xl text-gray-400">
                                <?php echo $logro['icono']; ?>
                            </span>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 dark:text-white text-sm"><?php echo $logro['nombre']; ?></h4>
                                <p class="text-xs text-gray-600 dark:text-[#92a4c9]"><?php echo $logro['descripcion']; ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-200 dark:bg-[#324467] rounded-full h-2">
                                <div class="bg-primary h-2 rounded-full transition-all" style="width: <?php echo $porcentaje; ?>%"></div>
                            </div>
                            <span class="text-xs font-semibold text-gray-600 dark:text-[#92a4c9]">
                                <?php echo $logro['progreso']; ?>/<?php echo $logro['valor_objetivo']; ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 dark:text-[#92a4c9] text-center py-8">¡Todos los logros completados! 🎉</p>
        <?php endif; ?>
    </div>
</div>

</div>
</main>
</div>
</div>
</body>
</html>
<?php
$conexion->close();
?>
