<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
$conexion->set_charset("utf8mb4");


// Obtener información del usuario
$stmt = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$resultado = $stmt->get_result();
$row = $resultado->num_rows > 0 ? $resultado->fetch_assoc() : null;
$stmt->close();

$Nombre_Completo = $row ? ($row['Nombre'] . " " . $row['Apellido']) : 'Usuario';
$Perfil = $row['Perfil'] ?? 'uploads/default-avatar.png';
$Rol = $row['Rol'] ?? 'Usuario';
$rol_usuario = strtolower($Rol);
// Filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01'); // Primer día del mes actual
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d'); // Hoy

// Consultar APERTURAS (tabla caja)
$query_aperturas = "SELECT * FROM caja 
                    WHERE DATE(Fecha) BETWEEN ? AND ? 
                    ORDER BY Fecha DESC, Id DESC";
$stmt = $conexion->prepare($query_aperturas);
$stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt->execute();
$aperturas = $stmt->get_result();
$stmt->close();

// Consultar ARQUEOS (tabla arqueo_caja)
$query_arqueos = "SELECT * FROM arqueo_caja 
                  WHERE DATE(Fecha) BETWEEN ? AND ? 
                  ORDER BY Fecha DESC, Id DESC";
$stmt = $conexion->prepare($query_arqueos);
$stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt->execute();
$arqueos = $stmt->get_result();
$stmt->close();

// Consultar CIERRES (tabla cierre_caja)
$query_cierres = "SELECT * FROM cierre_caja 
                  WHERE DATE(Fecha) BETWEEN ? AND ? 
                  ORDER BY Fecha DESC, Id DESC";
$stmt = $conexion->prepare($query_cierres);
$stmt->bind_param("ss", $fecha_desde, $fecha_hasta);
$stmt->execute();
$cierres = $stmt->get_result();
$stmt->close();

?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Reportes de Caja - ReySystemAPP</title>
    
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
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
                    }
                }
            }
        }
    </script>
    
    <link rel="stylesheet" href="reportes_caja_modern.css">
    <link rel="stylesheet" href="reportes_caja_modal_fix.css">
    
    <style>
        body { font-family: 'Manrope', sans-serif; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">

<!-- Include Christmas Effects -->
<?php include 'christmas_effects.php'; ?>

<!-- NAVBAR -->
<header class="flex shrink-0 items-center justify-between whitespace-nowrap border-b border-gray-200 dark:border-[#232f48] px-6 py-3 bg-white dark:bg-[#111722]">
    <div class="flex items-center gap-4">
        <div class="size-6 text-primary">
            <svg fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 4H17.3334V17.3334H30.6666V30.6666H44V44H4V4Z" fill="currentColor"></path>
            </svg>
        </div>
        <h2 class="text-lg font-bold text-gray-900 dark:text-white">ReySystemAPP</h2>
    </div>
    <div class="flex flex-1 justify-center gap-8">
        <nav class="flex items-center gap-9">
            <a class="text-sm font-medium text-gray-500 dark:text-[#92a4c9] hover:text-primary dark:hover:text-primary transition-colors" href="index.php">Dashboard</a>
            <a class="text-sm font-medium text-gray-500 dark:text-[#92a4c9] hover:text-primary dark:hover:text-primary transition-colors" href="nueva_venta.php">Ventas</a>
            <a class="text-sm font-medium text-gray-500 dark:text-[#92a4c9] hover:text-primary dark:hover:text-primary transition-colors" href="inventario.php">Inventario</a>
            <a class="text-sm font-bold text-primary dark:text-primary" href="reportes_caja.php">Reportes Caja</a>
        </nav>
    </div>
    <div class="flex items-center gap-4">
        <button class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-slate-100 dark:bg-[#232f48] text-slate-900 dark:text-white text-sm font-bold leading-normal tracking-[0.015em]">
            <span class="truncate"><?php echo htmlspecialchars($Nombre_Completo); ?></span>
        </button>
        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo htmlspecialchars($Perfil); ?>");'></div>
    </div>
</header>

<main class="flex-1 overflow-y-auto p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">
        
        <!-- HEADER NAVIDEÑO IMPONENTE -->
        <div class="christmas-header fade-in-up mb-8">
            <div class="flex items-center gap-4 mb-3">
                <svg class="crown-icon" width="48" height="48" viewBox="0 0 24 24">
                    <path d="M12 2L14.5 8.5L21 9L16 14L18 21L12 17.5L6 21L8 14L3 9L9.5 8.5L12 2Z" fill="#fbbf24" stroke="#f59e0b" stroke-width="0.5"/>
                    <circle cx="12" cy="9" r="1.5" fill="#dc2626"/>
                    <circle cx="8" cy="10" r="1" fill="#16a34a"/>
                    <circle cx="16" cy="10" r="1" fill="#16a34a"/>
                </svg>
                <h1 class="text-5xl font-black leading-tight tracking-[-0.033em]">Reportes de Caja</h1>
            </div>
            <p class="text-white/90 text-lg font-medium">Visualiza y administra el historial completo de aperturas, arqueos y cierres de caja 🎄</p>
        </div>

        <!-- FILTROS -->
        <form method="GET" class="bg-white dark:bg-[#111722] p-5 rounded-xl border border-gray-200 dark:border-[#232f48] mb-6">
            <div class="flex flex-wrap items-end gap-4">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Desde</label>
                    <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>" 
                           class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#192233] text-gray-800 dark:text-white">
                </div>
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hasta</label>
                    <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>" 
                           class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#192233] text-gray-800 dark:text-white">
                </div>
                <button type="submit" class="flex items-center gap-2 px-5 py-2.5 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition">
                    <span class="material-symbols-outlined text-lg">filter_alt</span>
                    Filtrar
                </button>
            </div>
        </form>

        <!-- TABLA APERTURAS -->
        <div class="mb-8 fade-in-up">
            <div class="section-header section-header-green">
                <span class="material-symbols-outlined text-green-500">lock_open</span>
                <h2 class="text-gray-900 dark:text-white text-2xl font-bold">Aperturas de Caja</h2>
            </div>
            <div class="table-container">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Monto Inicial</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Total Contado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Nota</th>
                                <?php if ($rol_usuario === 'admin'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($aperturas->num_rows > 0): ?>
                                <?php while ($apertura = $aperturas->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                            <?php echo date('d/m/Y H:i', strtotime($apertura['Fecha'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-lg">person</span>
                                                <?php echo htmlspecialchars($apertura['usuario'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-white">
                                            L.<?php echo number_format($apertura['monto_inicial'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600 dark:text-green-400">
                                            L.<?php echo number_format($apertura['Total'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $estado = $apertura['Estado'] ?? 'Abierta';
                                            $badge_class = $estado == 'Cerrada' ? 'badge-closed' : 'badge-open';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <span class="material-symbols-outlined text-sm"><?php echo $estado == 'Cerrada' ? 'lock' : 'lock_open'; ?></span>
                                                <?php echo $estado; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-[#92a4c9] max-w-xs truncate">
                                            <?php echo htmlspecialchars($apertura['Nota'] ?? '-'); ?>
                                        </td>
                                        <?php if ($rol_usuario === 'admin'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex items-center gap-2">
                                                <button onclick="editarRegistro('caja', <?php echo $apertura['Id']; ?>, <?php echo htmlspecialchars(json_encode($apertura)); ?>)" 
                                                        class="action-btn action-btn-edit" title="Editar">
                                                    <span class="material-symbols-outlined text-sm">edit</span>
                                                </button>
                                                <button onclick="confirmarEliminacion('caja', <?php echo $apertura['Id']; ?>)" 
                                                        class="action-btn action-btn-delete" title="Eliminar">
                                                    <span class="material-symbols-outlined text-sm">delete</span>
                                                </button>
                                                <button onclick="transferirRegistro('caja', <?php echo $apertura['Id']; ?>)" 
                                                        class="action-btn action-btn-transfer" title="Transferir">
                                                    <span class="material-symbols-outlined text-sm">swap_horiz</span>
                                                </button>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $rol_usuario === 'admin' ? '7' : '6'; ?>" class="px-6 py-8 text-center text-gray-500 dark:text-[#92a4c9]">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="material-symbols-outlined text-4xl">inbox</span>
                                            <p>No hay aperturas en este rango de fechas</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TABLA ARQUEOS -->
        <div class="mb-8 fade-in-up">
            <div class="section-header section-header-blue">
                <span class="material-symbols-outlined text-blue-500">calculate</span>
                <h2 class="text-gray-900 dark:text-white text-2xl font-bold">Arqueos de Caja</h2>
            </div>
            <div class="table-container">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Efectivo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Transferencia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Tarjeta</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Nota</th>
                                <?php if ($rol_usuario === 'admin'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($arqueos->num_rows > 0): ?>
                                <?php while ($arqueo = $arqueos->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                            <?php echo date('d/m/Y H:i', strtotime($arqueo['Fecha'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-lg">person</span>
                                                <?php echo htmlspecialchars($arqueo['Usuario'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600 dark:text-green-400">
                                            L.<?php echo number_format(floatval($arqueo['Efectivo'] ?? 0), 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            L.<?php echo number_format(floatval($arqueo['Transferencia'] ?? 0), 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            L.<?php echo number_format(floatval($arqueo['Tarjeta'] ?? 0), 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600 dark:text-blue-400">
                                            L.<?php echo number_format(floatval($arqueo['Total'] ?? 0), 2); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-[#92a4c9] max-w-xs truncate">
                                            <?php echo htmlspecialchars($arqueo['Nota_justi'] ?? '-'); ?>
                                        </td>
                                        <?php if ($rol_usuario === 'admin'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex items-center gap-2">
                                                <button onclick="editarRegistro('arqueo_caja', <?php echo $arqueo['Id']; ?>, <?php echo htmlspecialchars(json_encode($arqueo)); ?>)" 
                                                        class="action-btn action-btn-edit" title="Editar">
                                                    <span class="material-symbols-outlined text-sm">edit</span>
                                                </button>
                                                <button onclick="confirmarEliminacion('arqueo_caja', <?php echo $arqueo['Id']; ?>)" 
                                                        class="action-btn action-btn-delete" title="Eliminar">
                                                    <span class="material-symbols-outlined text-sm">delete</span>
                                                </button>
                                                <button onclick="transferirRegistro('arqueo_caja', <?php echo $arqueo['Id']; ?>)" 
                                                        class="action-btn action-btn-transfer" title="Transferir">
                                                    <span class="material-symbols-outlined text-sm">swap_horiz</span>
                                                </button>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $rol_usuario === 'admin' ? '8' : '7'; ?>" class="px-6 py-8 text-center text-gray-500 dark:text-[#92a4c9]">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="material-symbols-outlined text-4xl">inbox</span>
                                            <p>No hay arqueos en este rango de fechas</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TABLA CIERRES -->
        <div class="mb-8 fade-in-up">
            <div class="section-header section-header-red">
                <span class="material-symbols-outlined text-red-500">lock</span>
                <h2 class="text-gray-900 dark:text-white text-2xl font-bold">Cierres de Caja</h2>
            </div>
            <div class="table-container">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Usuario</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Efectivo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Transferencia</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Tarjeta</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Nota</th>
                                <?php if ($rol_usuario === 'admin'): ?>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-[#92a4c9] uppercase">Acciones</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($cierres->num_rows > 0): ?>
                                <?php while ($cierre = $cierres->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                            <?php echo date('d/m/Y H:i', strtotime($cierre['Fecha'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            <div class="flex items-center gap-2">
                                                <span class="material-symbols-outlined text-lg">person</span>
                                                <?php echo htmlspecialchars($cierre['Usuario'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600 dark:text-green-400">
                                            L.<?php echo number_format(floatval($cierre['Efectivo'] ?? 0), 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            L.<?php echo number_format(floatval($cierre['Transferencia'] ?? 0), 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                            L.<?php echo number_format(floatval($cierre['Tarjeta'] ?? 0), 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-red-600 dark:text-red-400">
                                            L.<?php echo number_format(floatval($cierre['Total'] ?? 0), 2); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-[#92a4c9] max-w-xs truncate">
                                            <?php echo htmlspecialchars($cierre['Nota_Justifi'] ?? '-'); ?>
                                        </td>
                                        <?php if ($rol_usuario === 'admin'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex items-center gap-2">
                                                <button onclick="editarRegistro('cierre_caja', <?php echo $cierre['Id']; ?>, <?php echo htmlspecialchars(json_encode($cierre)); ?>)" 
                                                        class="action-btn action-btn-edit" title="Editar">
                                                    <span class="material-symbols-outlined text-sm">edit</span>
                                                </button>
                                                <button onclick="confirmarEliminacion('cierre_caja', <?php echo $cierre['Id']; ?>)" 
                                                        class="action-btn action-btn-delete" title="Eliminar">
                                                    <span class="material-symbols-outlined text-sm">delete</span>
                                                </button>
                                                <button onclick="transferirRegistro('cierre_caja', <?php echo $cierre['Id']; ?>)" 
                                                        class="action-btn action-btn-transfer" title="Transferir">
                                                    <span class="material-symbols-outlined text-sm">swap_horiz</span>
                                                </button>
                                            </div>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $rol_usuario === 'admin' ? '8' : '7'; ?>" class="px-6 py-8 text-center text-gray-500 dark:text-[#92a4c9]">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="material-symbols-outlined text-4xl">inbox</span>
                                            <p>No hay cierres en este rango de fechas</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- MODAL EDITAR -->
<div id="editModal" class="modal hidden">
    <div class="modal-content">
        <div class="modal-header">
            <h3>✏️ Editar Registro</h3>
        </div>
        <form onsubmit="event.preventDefault(); guardarEdicion();">
            <input type="hidden" id="editTable">
            <input type="hidden" id="editId">
            <div id="editFormFields"></div>
            <div class="flex gap-3 mt-6">
                <button type="button" onclick="cerrarModal('editModal')" 
                        class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-xl font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                    Cancelar
                </button>
                <button type="submit" 
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl font-bold hover:shadow-lg transition">
                    💾 Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CONFIRMAR ELIMINACIÓN -->
<div id="confirmModal" class="modal hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header bg-gradient-to-r from-red-500 to-red-600">
            <h3>🗑️ Confirmar Eliminación</h3>
        </div>
        <div class="py-6 text-center">
            <div class="mx-auto mb-4 w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-4xl text-red-600 dark:text-red-400">warning</span>
            </div>
            <p class="text-gray-700 dark:text-gray-300 text-lg font-medium mb-2">¿Estás seguro?</p>
            <p class="text-gray-500 dark:text-gray-400">Esta acción no se puede deshacer</p>
        </div>
        <div class="flex gap-3">
            <button onclick="cerrarModal('confirmModal')" 
                    class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-xl font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                Cancelar
            </button>
            <button onclick="eliminarRegistro()" 
                    class="flex-1 px-4 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl font-bold hover:shadow-lg transition">
                🗑️ Eliminar
            </button>
        </div>
    </div>
</div>

<!-- MODAL TRANSFERIR -->
<div id="transferModal" class="modal hidden">
    <div class="modal-content max-w-md">
        <div class="modal-header bg-gradient-to-r from-blue-500 to-blue-600">
            <h3>🔄 Transferir Responsabilidad</h3>
        </div>
        <div class="py-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nuevo Usuario Responsable</label>
            <select id="transferUsuario" class="form-input-christmas w-full">
                <option value="">Selecciona un usuario</option>
            </select>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">El usuario seleccionado será el nuevo responsable de este registro</p>
        </div>
        <div class="flex gap-3">
            <button onclick="cerrarModal('transferModal')" 
                    class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white rounded-xl font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                Cancelar
            </button>
            <button onclick="confirmarTransferencia()" 
                    class="flex-1 px-4 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl font-bold hover:shadow-lg transition">
                🔄 Transferir
            </button>
        </div>
    </div>
</div>

<script src="reportes_caja_functions.js"></script>

</body>
</html>
<?php $conexion->close(); ?>
