<?php
session_start();
include 'funciones.php';
// Zona horaria correcta para Honduras
date_default_timezone_set('America/Tegucigalpa');
VerificarSiUsuarioYaInicioSesion();
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// ------------------------------------------------
// 1. OBTENER DATOS DEL USUARIO (Mantenido)
// ------------------------------------------------
$Rol = $Usuario = $Nombre = $Apellido = $Nombre_Completo = $Email = $Celular = $Perfil = "";
$resultado_usuario = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
if ($resultado_usuario && $row = $resultado_usuario->fetch_assoc()){ 
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Email = $row['Email'];
    $Celular = $row['Celular'];
    $Perfil = $row['Perfil'];
}

// ------------------------------------------------
// 2. CÁLCULO DE INDICADORES (TARJETAS) - ¡CORRECCIÓN CRÍTICA DE COMILLAS!
// ------------------------------------------------
// NOTA IMPORTANTE: Para SUM y AVG, DEBES USAR COMILLAS INVERTIDAS (`) o ninguna.
// USAR COMILLAS SIMPLES ('Total') hace que la consulta siempre devuelva el número 0.
// ------------------------------------------------
// 2. CÁLCULO DE INDICADORES (TARJETAS) - ¡DINÁMICO!
// ------------------------------------------------

// --- DEFINICIÓN DE FECHAS DE COMPARACIÓN ---
$fecha_hoy = date('Y-m-d');
$fecha_ayer = date('Y-m-d', strtotime('-1 day'));

// --- FUNCIONES DE CONSULTA (Para simplificar el código) ---

/** Obtiene la suma de una columna para un día específico. */
function obtener_suma_dia($conexion, $columna, $fecha) {
    $query = "SELECT SUM(`$columna`) AS total FROM ventas WHERE DATE(`Fecha_Venta`) = '$fecha'";
    $resultado = $conexion->query($query);
    return $resultado ? ($resultado->fetch_assoc()['total'] ?? 0) : 0;
}

/** Obtiene el conteo de registros para un día específico. */
function obtener_conteo_dia($conexion, $fecha) {
    $query = "SELECT COUNT(Id) AS total FROM ventas WHERE DATE(`Fecha_Venta`) = '$fecha'";
    $resultado = $conexion->query($query);
    return $resultado ? ($resultado->fetch_assoc()['total'] ?? 0) : 0;
}

// --- CÁLCULO DE MÉTRICAS DIARIAS ---

// 1. INGRESOS TOTALES
$ingresos_hoy = obtener_suma_dia($conexion, 'Total', $fecha_hoy);
$ingresos_ayer = obtener_suma_dia($conexion, 'Total', $fecha_ayer);

// 2. TRANSACCIONES
$transacciones_hoy = obtener_conteo_dia($conexion, $fecha_hoy);
$transacciones_ayer = obtener_conteo_dia($conexion, $fecha_ayer);

// 3. TICKET PROMEDIO
$ticket_promedio_hoy = ($ingresos_hoy > 0 && $transacciones_hoy > 0) ? ($ingresos_hoy / $transacciones_hoy) : 0;
$ticket_promedio_ayer = ($ingresos_ayer > 0 && $transacciones_ayer > 0) ? ($ingresos_ayer / $transacciones_ayer) : 0;


// --- CÁLCULO DE PORCENTAJES Y FORMATO ---

/** Calcula el porcentaje de cambio y define el color. */
function calcular_porcentaje_cambio($hoy, $ayer) {
    if ($ayer == 0) {
        $porcentaje = ($hoy > 0) ? 100 : 0; // Si Ayer es 0, y Hoy > 0, es 100% de crecimiento
        $clase_color = ($hoy > 0) ? 'text-green-500' : 'text-slate-500';
        $signo = ($hoy > 0) ? '+' : '';
    } else {
        $cambio = $hoy - $ayer;
        $porcentaje = abs(($cambio / $ayer) * 100);
        $clase_color = ($cambio >= 0) ? 'text-green-500' : 'text-red-500';
        $signo = ($cambio >= 0) ? '+' : '-';
    }
    
    $texto_porcentaje = $signo . number_format($porcentaje, 1) . '%';
    
    return [
        'valor' => $hoy,
        'formato' => number_format($hoy, 2),
        'porcentaje' => $texto_porcentaje,
        'clase_color' => $clase_color
    ];
}

$data_ingresos = calcular_porcentaje_cambio($ingresos_hoy, $ingresos_ayer);
$data_transacciones = calcular_porcentaje_cambio($transacciones_hoy, $transacciones_ayer);
$data_ticket = calcular_porcentaje_cambio($ticket_promedio_hoy, $ticket_promedio_ayer);

// Variables finales para el HTML
$IngresosTotales = $data_ingresos['formato'];
$PorcentajeIngresos = $data_ingresos['porcentaje'];
$ClaseColorIngresos = $data_ingresos['clase_color'];

$TotalTransacciones = number_format($data_transacciones['valor'], 0, '.', ','); // Sin decimales
$PorcentajeTransacciones = $data_transacciones['porcentaje'];
$ClaseColorTransacciones = $data_transacciones['clase_color'];

$TicketPromedio = $data_ticket['formato'];
$PorcentajeTicket = $data_ticket['porcentaje'];
$ClaseColorTicket = $data_ticket['clase_color'];

// 4. NUEVOS CLIENTES
$clientes_hoy = $conexion->query("SELECT COUNT(*) as total FROM clientes WHERE DATE(Fecha_Registro) = '$fecha_hoy'")->fetch_assoc()['total'] ?? 0;
$clientes_ayer = $conexion->query("SELECT COUNT(*) as total FROM clientes WHERE DATE(Fecha_Registro) = '$fecha_ayer'")->fetch_assoc()['total'] ?? 0;

$data_clientes = calcular_porcentaje_cambio($clientes_hoy, $clientes_ayer);

$NuevosClientes = number_format($data_clientes['valor'], 0, '.', ',');
$PorcentajeClientes = $data_clientes['porcentaje'];
$ClaseColorClientes = $data_clientes['clase_color'];

// ------------------------------------------------
// 3. LÓGICA DE FILTRADO Y CONSULTA DE VENTAS
// ------------------------------------------------

// Capturar parámetros de filtro de la URL (GET)
$fecha_desde = $_GET['date-from'] ?? '';
$fecha_hasta = $_GET['date-to'] ?? '';
$cliente_filtro = $_GET['client-filter'] ?? '';
$producto_filtro = $_GET['product-filter'] ?? '';

$condiciones = [];

// 1. Filtro por Fecha (Desde)
if (!empty($fecha_desde)) {
    // Añadimos el inicio del día (00:00:00) para incluir todas las ventas de ese día.
    $condiciones[] = "`Fecha_Venta` >= '" . $conexion->real_escape_string($fecha_desde) . " 00:00:00'";
}

// 2. Filtro por Fecha (Hasta)
if (!empty($fecha_hasta)) {
    // Añadimos el final del día (23:59:59) para incluir todas las ventas de ese día.
    $condiciones[] = "`Fecha_Venta` <= '" . $conexion->real_escape_string($fecha_hasta) . " 23:59:59'";
}

// 3. Filtro por Cliente
if (!empty($cliente_filtro)) {
    $condiciones[] = "`Cliente` LIKE '%" . $conexion->real_escape_string($cliente_filtro) . "%'";
}

// 4. Filtro por Producto
if (!empty($producto_filtro)) {
    $condiciones[] = "`Producto_Vendido` LIKE '%" . $conexion->real_escape_string($producto_filtro) . "%'";
}

// Construir la cláusula WHERE
$where_clause = '';
if (!empty($condiciones)) {
    $where_clause = ' WHERE ' . implode(' AND ', $condiciones);
}

// Consulta final de ventas con filtros aplicados
$query_ventas = "SELECT Id, Cliente, Producto_Vendido, Factura_Id, Total, Fecha_Venta, Vendedor 
                 FROM ventas" . $where_clause . " 
                 ORDER BY Id DESC LIMIT 910";
                 
$resultado_ventas = $conexion->query($query_ventas);
// --- INICIO DE LA LÓGICA DE PERMISOS ---
// Convertimos el rol a minúsculas para hacer la comparación insensible a mayúsculas/minúsculas.
 $rol_usuario = strtolower($Rol);
// --- FIN DE LA LÓGICA DE PERMISOS ---
?>
<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Reportes de Ventas</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
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
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            font-size: 20px;
        }
        .material-symbols-outlined.fill {
             font-variation-settings: 'FILL' 1, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>
<main class="flex-1 overflow-y-auto">
<div class="p-6 md:p-8 lg:p-10">
<div class="flex flex-wrap justify-between items-center gap-4 mb-6">
<div class="flex flex-col">
<p class="text-3xl font-black leading-tight tracking-tight text-slate-900 dark:text-white">Reportes de Ventas</p>
<p class="text-slate-500 dark:text-slate-400 text-base font-normal">Analiza el rendimiento de tu negocio con los siguientes datos.</p>
</div>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
<div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
<p class="text-slate-600 dark:text-slate-400 text-base font-medium">Ingresos Totales (Hoy)</p>
<p class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight">L.<?php echo $IngresosTotales; ?></p>
<p class="<?php echo $ClaseColorIngresos; ?> text-base font-medium"><?php echo $PorcentajeIngresos; ?></p>
</div>

<div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
<p class="text-slate-600 dark:text-slate-400 text-base font-medium">Transacciones (Hoy)</p>
<p class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight"><?php echo $TotalTransacciones; ?></p>
<p class="<?php echo $ClaseColorTransacciones; ?> text-base font-medium"><?php echo $PorcentajeTransacciones; ?></p>
</div>

<div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
<p class="text-slate-600 dark:text-slate-400 text-base font-medium">Ticket Promedio (Hoy)</p>
<p class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight">L.<?php echo $TicketPromedio; ?></p>
<p class="<?php echo $ClaseColorTicket; ?> text-base font-medium"><?php echo $PorcentajeTicket; ?></p>
</div>

<div class="flex flex-col gap-2 rounded-xl p-6 bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700">
<p class="text-slate-600 dark:text-slate-400 text-base font-medium">Nuevos Clientes</p>
<p class="text-slate-900 dark:text-white text-3xl font-bold tracking-tight"><?php echo $NuevosClientes; ?></p>
<p class="<?php echo $ClaseColorClientes; ?> text-base font-medium"><?php echo $PorcentajeClientes; ?></p>
</div>
</div>
<div class="bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden">
<div class="p-4 md:p-6 border-b border-slate-200 dark:border-slate-700">
<h3 class="text-lg font-bold text-slate-900 dark:text-white mb-4">Ventas Realizadas</h3>
<form method="GET" action="reporte_ventas.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
<div>
<label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1" for="date-from">Desde</label>
<input class="w-full h-10 px-3 rounded-lg bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm focus:ring-primary focus:border-primary" 
       id="date-from" type="date" name="date-from" value="<?php echo $fecha_desde; ?>"/>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1" for="date-to">Hasta</label>
<input class="w-full h-10 px-3 rounded-lg bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm focus:ring-primary focus:border-primary" 
       id="date-to" type="date" name="date-to" value="<?php echo $fecha_hasta; ?>"/>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1" for="client-filter">Cliente</label>
<input class="w-full h-10 px-3 rounded-lg bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm focus:ring-primary focus:border-primary placeholder:text-slate-400 dark:placeholder:text-slate-500" 
       id="client-filter" placeholder="Buscar cliente..." type="text" name="client-filter" value="<?php echo $cliente_filtro; ?>"/>
</div>
<div>
<label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1" for="product-filter">Producto</label>
<input class="w-full h-10 px-3 rounded-lg bg-white dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-sm focus:ring-primary focus:border-primary placeholder:text-slate-400 dark:placeholder:text-slate-500" 
       id="product-filter" placeholder="Buscar producto..." type="text" name="product-filter" value="<?php echo $producto_filtro; ?>"/>
</div>
<div class="flex items-end">
    <button type="submit" class="w-full h-10 bg-primary text-white font-medium rounded-lg hover:bg-primary/90 transition-colors duration-200">
        Filtrar
    </button>
</div>
</form>
</div>
<div class="p-4 md:p-6 overflow-x-auto">
<table class="w-full text-sm text-left text-slate-500 dark:text-slate-400">
<thead class="text-xs text-slate-700 uppercase bg-slate-50 dark:bg-slate-700/50 dark:text-slate-400">
<tr>
<th class="px-6 py-3" scope="col">ID Transacción</th>
<th class="px-6 py-3" scope="col">Fecha</th>
<th class="px-6 py-3" scope="col">Cliente</th>
<th class="px-6 py-3" scope="col">Producto(s)</th>
<th class="px-6 py-3" scope="col">Monto</th>
<th class="px-6 py-3" scope="col">Estado</th>
</tr>
</thead>
<tbody>
<?php 
// Bucle para mostrar las ventas de la base de datos
if ($resultado_ventas->num_rows > 0) {
    while($row_venta = $resultado_ventas->fetch_assoc()) {
        // Asumimos estado "Completado" ya que no hay columna de estado
        $estado = "Completado"; 
        $clase_estado = "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200";
        
        // Formatear la fecha
        $fecha_hora = new DateTime($row_venta['Fecha_Venta']);
        $fecha_formato = $fecha_hora->format('Y-m-d H:i');
        
        // Formatear el monto
        $monto_formato = number_format($row_venta['Total'], 2);
?>
<tr class="bg-white dark:bg-transparent border-b dark:border-slate-700">
<th class="px-6 py-4 font-medium text-slate-900 whitespace-nowrap dark:text-white" scope="row"><?php echo $row_venta['Factura_Id']; ?></th>
<td class="px-6 py-4"><?php echo $fecha_formato; ?></td>
<td class="px-6 py-4"><?php echo $row_venta['Cliente']; ?></td>
<td class="px-6 py-4"><?php echo $row_venta['Producto_Vendido']; ?></td>
<td class="px-6 py-4">L.<?php echo $monto_formato; ?></td>
<td class="px-6 py-4"><span class="inline-flex items-center gap-1.5 py-1 px-2 rounded-full text-xs font-medium <?php echo $clase_estado; ?>"><?php echo $estado; ?></span></td>
</tr>
<?php
    }
} else {
    // Si no hay resultados
?>
<tr class="bg-white dark:bg-transparent">
<td class="px-6 py-4 text-center text-slate-500" colspan="6">No se encontraron ventas.</td>
</tr>
<?php
}
// Cierra la conexión a la base de datos
$conexion->close(); 
?>
</tbody>
</table>
</div>
</div>
</div>
</main>
</div>
</body></html>