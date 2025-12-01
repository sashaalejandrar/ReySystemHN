<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'funciones.php';
date_default_timezone_set('America/Tegucigalpa');

VerificarSiUsuarioYaInicioSesion();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

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

// Crear tabla de movimientos si no existe
$conexion->query("CREATE TABLE IF NOT EXISTS movimientos_inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    producto VARCHAR(255) NOT NULL,
    tipo ENUM('entrada', 'salida', 'ajuste') NOT NULL,
    cantidad INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    motivo VARCHAR(500),
    usuario VARCHAR(100),
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_fecha (fecha),
    INDEX idx_producto (producto)
)");

// Filtros
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-01');
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$tipo_filtro = $_GET['tipo'] ?? '';
$producto_filtro = $_GET['producto'] ?? '';

// Construir query
$where = ["DATE(fecha) BETWEEN '$fecha_desde' AND '$fecha_hasta'"];
if ($tipo_filtro) $where[] = "tipo = '$tipo_filtro'";
if ($producto_filtro) $where[] = "producto LIKE '%$producto_filtro%'";

$where_clause = implode(' AND ', $where);

// Obtener movimientos
$movimientos = $conexion->query("SELECT * FROM movimientos_inventario WHERE $where_clause ORDER BY fecha DESC LIMIT 100");

// Estadísticas
$total_entradas = $conexion->query("SELECT COUNT(*) as total FROM movimientos_inventario WHERE tipo = 'entrada' AND $where_clause")->fetch_assoc()['total'];
$total_salidas = $conexion->query("SELECT COUNT(*) as total FROM movimientos_inventario WHERE tipo = 'salida' AND $where_clause")->fetch_assoc()['total'];
$total_ajustes = $conexion->query("SELECT COUNT(*) as total FROM movimientos_inventario WHERE tipo = 'ajuste' AND $where_clause")->fetch_assoc()['total'];
$productos_bajo_stock = $conexion->query("SELECT COUNT(*) as total FROM stock WHERE Stock < 10")->fetch_assoc()['total'];

?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Historial de Inventario - Rey System</title>
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
</style>
</head>
<body class="bg-[#f6f7f8] dark:bg-[#101922] font-display">

<div class="flex min-h-screen w-full">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 p-8">
<div class="mx-auto max-w-7xl">

<!-- Header -->
<div class="mb-8">
    <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight">Historial de Inventario</h1>
    <p class="text-gray-500 dark:text-[#92a4c9] text-base mt-2">Seguimiento de movimientos y cambios en el inventario</p>
</div>

<!-- Estadísticas -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <span class="material-symbols-outlined text-4xl opacity-80">arrow_downward</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Entradas</p>
                <p class="text-3xl font-bold"><?php echo $total_entradas; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <span class="material-symbols-outlined text-4xl opacity-80">arrow_upward</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Salidas</p>
                <p class="text-3xl font-bold"><?php echo $total_salidas; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <span class="material-symbols-outlined text-4xl opacity-80">tune</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Ajustes</p>
                <p class="text-3xl font-bold"><?php echo $total_ajustes; ?></p>
            </div>
        </div>
    </div>

    <div class="bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl p-6 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <span class="material-symbols-outlined text-4xl opacity-80">warning</span>
            <div class="text-right">
                <p class="text-sm opacity-90">Bajo Stock</p>
                <p class="text-3xl font-bold"><?php echo $productos_bajo_stock; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6">
    <h3 class="text-gray-900 dark:text-white text-lg font-bold mb-4">Filtros</h3>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Desde</label>
            <input type="date" name="fecha_desde" value="<?php echo $fecha_desde; ?>" 
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Hasta</label>
            <input type="date" name="fecha_hasta" value="<?php echo $fecha_hasta; ?>" 
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Tipo</label>
            <select name="tipo" class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
                <option value="">Todos</option>
                <option value="entrada" <?php echo $tipo_filtro == 'entrada' ? 'selected' : ''; ?>>Entradas</option>
                <option value="salida" <?php echo $tipo_filtro == 'salida' ? 'selected' : ''; ?>>Salidas</option>
                <option value="ajuste" <?php echo $tipo_filtro == 'ajuste' ? 'selected' : ''; ?>>Ajustes</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">Producto</label>
            <input type="text" name="producto" value="<?php echo $producto_filtro; ?>" placeholder="Buscar producto..."
                class="w-full px-4 py-2 rounded-lg border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#101622] text-gray-900 dark:text-white">
        </div>
        <div class="md:col-span-4">
            <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg font-bold hover:bg-primary/90">
                Aplicar Filtros
            </button>
        </div>
    </form>
</div>

<!-- Tabla de movimientos -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-[#324467]">
            <thead class="bg-gray-50 dark:bg-[#111a22]">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Producto</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Cantidad</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Stock Anterior</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Stock Nuevo</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-[#92a4c9]">Motivo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-[#324467]">
                <?php if ($movimientos && $movimientos->num_rows > 0): ?>
                    <?php while($mov = $movimientos->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50 dark:hover:bg-[#1a2332]">
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                <?php echo htmlspecialchars($mov['producto']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <?php
                                $badge_class = [
                                    'entrada' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'salida' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                    'ajuste' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                ][$mov['tipo']];
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($mov['tipo']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">
                                <?php echo $mov['cantidad']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-[#92a4c9]">
                                <?php echo $mov['stock_anterior']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-900 dark:text-white">
                                <?php echo $mov['stock_nuevo']; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-[#92a4c9]">
                                <?php echo htmlspecialchars($mov['usuario']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-[#92a4c9]">
                                <?php echo htmlspecialchars($mov['motivo'] ?: '-'); ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center">
                            <span class="material-symbols-outlined text-gray-400 dark:text-[#92a4c9] text-4xl">inventory</span>
                            <p class="text-gray-500 dark:text-[#92a4c9] mt-2">No hay movimientos registrados</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div>
</main>
</div>

</body></html>
<?php
$conexion->close();
?>
