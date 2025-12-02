<?php
session_start();
include 'funciones.php';
date_default_timezone_set('America/Tegucigalpa');
VerificarSiUsuarioYaInicioSesion();

// Conexión a la base de datos
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// --- LÓGICA DE VERIFICACIÓN DE CAJA CORREGIDA ---
 $hoy = date("Y-m-d");

// Usamos prepared statement para evitar inyección SQL
 $query_caja = "SELECT Estado FROM caja WHERE DATE(Fecha) = ? ORDER BY Id DESC LIMIT 1";
 $stmt = $conexion->prepare($query_caja);
 $stmt->bind_param("s", $hoy);
 $stmt->execute();
 $resultado_caja = $stmt->get_result();

if ($resultado_caja->num_rows > 0) {
    // Existe un registro para hoy, obtenemos su estado
    $row_caja = $resultado_caja->fetch_assoc();
    $estado_actual = $row_caja['Estado'];
    
    // Depuración (opcional, puedes comentar o eliminar en producción)
    // error_log("Estado de caja encontrado: " . $estado_actual);
    
    // Verificamos el estado de forma segura
    if ($estado_actual === 'Cerrada') {
        // Si está cerrada, redirigimos al índice con mensaje
        $_SESSION['mensaje_error'] = "La caja de hoy ya está cerrada. No se pueden realizar más ventas.";
        $stmt->close();
        header("Location: index.php");
        exit();
    }
    // Si está 'Abierta', continuamos con el script sin hacer nada más
} else {
    // No existe registro para hoy, redirigimos a apertura de caja
    $_SESSION['mensaje_error'] = "Debe abrir la caja del día antes de realizar cualquier venta.";
    $stmt->close();
    header("Location: apertura_caja.php");
    exit();
}

 $stmt->close();
// --- FIN DE LA LÓGICA DE VERIFICACIÓN ---

// Obtener datos del usuario (usando prepared statement para mayor seguridad)
 $query_usuario = "SELECT * FROM usuarios WHERE usuario = ?";
 $stmt_usuario = $conexion->prepare($query_usuario);
 $stmt_usuario->bind_param("s", $_SESSION['usuario']);
 $stmt_usuario->execute();
 $resultado = $stmt_usuario->get_result();

if ($resultado->num_rows > 0) {
    $row = $resultado->fetch_assoc();
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Email = $row['Email'];
    $Celular = $row['Celular'];
    $Perfil = $row['Perfil'];
}
 $stmt_usuario->close();

// Obtener productos del stock
 $query_productos = "SELECT * FROM stock WHERE Stock > 0 ORDER BY Nombre_Producto";
 $productos = $conexion->query($query_productos);
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Rey System APP - Cobrar</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
</style>
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
                borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
            },
        },
    }
</script>
<link rel="stylesheet" href="nueva_venta_modern.css">
<?php include "pwa-head.php"; ?>
</head>
<body class="font-display">
<div class="relative flex h-auto min-h-screen w-full flex-col bg-background-light dark:bg-background-dark group/design-root overflow-x-hidden" style='font-family: Manrope, "Noto Sans", sans-serif;'>
<div class="layout-container flex h-full grow flex-col">
<header class="flex items-center justify-between whitespace-nowrap border-b border-solid border-slate-200 dark:border-b-[#232f48] px-10 py-3 bg-white dark:bg-[#111722]">
<div class="flex items-center gap-4 text-slate-900 dark:text-white">
<div class="size-6 text-primary">
<svg fill="none" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<path d="M4 4H17.3334V17.3334H30.6666V30.6666H44V44H4V4Z" fill="currentColor"></path>
</svg>
</div>
<h2 class="text-slate-900 dark:text-white text-lg font-bold leading-tight tracking-[-0.015em]">ReySystemAPP</h2>
</div>
<div class="flex flex-1 justify-end gap-8">
<div class="flex items-center gap-9">
    <a class="text-slate-600 dark:text-slate-300 text-sm font-medium leading-normal" href="index.php">Dashboard</a>
<a class="text-slate-600 dark:text-slate-300 text-sm font-medium leading-normal" href="reporte_ventas.php">Historial de Ventas</a>
<a class="text-slate-600 dark:text-slate-300 text-sm font-medium leading-normal" href="inventario.php">Inventario</a>
</div>
<div class="flex items-center gap-4">
<button class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-slate-100 dark:bg-[#232f48] text-slate-900 dark:text-white text-sm font-bold leading-normal tracking-[0.015em]">
<span class="truncate"><?php echo $Nombre_Completo; ?></span>
</button>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" style='background-image: url("<?php echo $Perfil; ?>");'></div>
</div>
</div>
</header>

<main class="flex-1 p-6 lg:p-10 grid grid-cols-1 lg:grid-cols-12 gap-6">
<!-- Left Column: Product Catalog -->
<div class="lg:col-span-4 flex flex-col gap-6">
<div class="bg-white dark:bg-[#111722] rounded-xl shadow-sm p-6">
<div class="flex flex-wrap justify-between gap-3">
<p class="text-slate-900 dark:text-white text-2xl font-bold leading-tight tracking-[-0.033em] min-w-72">Buscar Productos</p>
</div>
<div class="mt-4">
<label class="flex flex-col min-w-40 h-12 w-full">
<div class="flex w-full flex-1 items-stretch rounded-lg h-full">
<div class="text-slate-400 dark:text-[#92a4c9] flex border border-r-0 border-slate-200 dark:border-[#232f48] bg-slate-100 dark:bg-[#232f48] items-center justify-center pl-4 rounded-l-lg">
<span class="material-symbols-outlined">search</span>
</div>
<input id="searchInput" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-200 dark:border-[#232f48] bg-slate-100 dark:bg-[#232f48] h-full placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] px-4 rounded-l-none border-l-0 pl-2 text-base font-normal leading-normal" placeholder="Buscar por nombre o código de producto"/>
</div>
</label>
</div>
</div>

<div class="bg-white dark:bg-[#111722] rounded-xl shadow-sm p-6 flex-1 overflow-y-auto" style="max-height: calc(100vh - 300px);">
<div id="productGrid" class="grid grid-cols-[repeat(auto-fill,minmax(140px,1fr))] gap-4">
<?php while($producto = $productos->fetch_assoc()): ?>
<div class="flex flex-col gap-3 pb-3 group product-item" 
     data-id="<?php echo $producto['Id']; ?>"
     data-codigo="<?php echo $producto['Codigo_Producto']; ?>"
     data-nombre="<?php echo htmlspecialchars($producto['Nombre_Producto']); ?>"
     data-marca="<?php echo htmlspecialchars($producto['Marca']); ?>"
     data-precio="<?php echo $producto['Precio_Unitario']; ?>"
     data-stock="<?php echo $producto['Stock']; ?>"
     data-search="<?php echo strtolower($producto['Codigo_Producto'] . ' ' . $producto['Nombre_Producto'] . ' ' . $producto['Marca']); ?>">
    
    <!-- Imagen clickeable -->
    <div class="relative w-full bg-center bg-no-repeat aspect-square bg-cover rounded-lg overflow-hidden cursor-pointer product-image-click">
        <?php if (!empty($producto['FotoProducto'])): ?>
            <img src="<?php echo $producto['FotoProducto']; ?>" 
                 alt="<?php echo htmlspecialchars($producto['Nombre_Producto']); ?>" 
                 class="w-full h-full object-cover rounded-lg">
        <?php else: ?>
            <div class="w-full h-full bg-slate-600 flex items-center justify-center text-white font-bold text-2xl rounded-lg">
                <?php echo strtoupper(substr($producto['Nombre_Producto'], 0, 2)); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div>
        <p class="text-slate-800 dark:text-white text-base font-medium leading-normal"><?php echo $producto['Nombre_Producto']; ?></p>
        <p class="text-slate-500 dark:text-[#92a4c9] text-xs"><?php echo $producto['Marca']; ?></p>
        <p class="text-slate-500 dark:text-[#92a4c9] text-sm font-bold">L <?php echo number_format($producto['Precio_Unitario'], 2); ?></p>
        <p class="text-slate-400 dark:text-[#92a4c9] text-xs mb-2">Stock: <?php echo $producto['Stock']; ?></p>
        
        <!-- Botón de agregar debajo del stock -->
        <button class="add-product-btn-new w-full flex items-center justify-center gap-2 bg-gradient-to-r from-primary to-blue-600 text-white font-semibold py-2 px-4 rounded-lg hover:shadow-lg transition-all">
            <span class="material-symbols-outlined text-lg">add_shopping_cart</span>
            <span class="text-sm">Agregar</span>
        </button>
    </div>
</div>
<?php endwhile; ?>
</div>
</div>
</div>

<!-- Middle Column: Order Summary -->
<div class="lg:col-span-4 bg-white dark:bg-[#111722] rounded-xl shadow-sm p-6 flex flex-col">
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em] mb-4">Resumen del Pedido</h2>
<div id="orderItems" class="flex-1 space-y-4 overflow-y-auto" style="max-height: 400px;">
<div class="text-center text-slate-400 dark:text-slate-500 py-8">
<span class="material-symbols-outlined text-6xl">shopping_cart</span>
<p class="mt-2">No hay productos en el carrito</p>
</div>
</div>

<div class="mt-auto pt-6 border-t border-slate-200 dark:border-slate-700 space-y-3">
<div class="flex justify-between text-slate-600 dark:text-slate-300">
<span>Subtotal</span>
<span id="subtotal">L 0.00</span>
</div>
<div class="flex justify-between text-slate-600 dark:text-slate-300">
<span>Impuestos (15%)</span>
<span id="tax">L 0.00</span>
</div>
<div class="flex justify-between text-slate-900 dark:text-white font-bold text-xl">
<span>Total a Pagar</span>
<span id="total">L 0.00</span>
</div>
</div>
</div>

<!-- Right Column: Payment -->
<div class="lg:col-span-4 bg-white dark:bg-[#111722] rounded-xl shadow-sm p-6 flex flex-col">
<h2 class="text-slate-900 dark:text-white text-[22px] font-bold leading-tight tracking-[-0.015em] mb-4">Proceso de Pago</h2>
<div class="grid grid-cols-3 gap-3 mb-6">
<button class="payment-method-btn flex flex-col items-center justify-center gap-2 p-4 rounded-lg border-2 border-primary bg-primary/10 text-primary font-semibold" data-method="Efectivo">
<span class="material-symbols-outlined">payments</span>
<span>Efectivo</span>
</button>
<button class="payment-method-btn flex flex-col items-center justify-center gap-2 p-4 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300" data-method="Tarjeta">
<span class="material-symbols-outlined">credit_card</span>
<span>Tarjeta</span>
</button>
<button class="payment-method-btn flex flex-col items-center justify-center gap-2 p-4 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300" data-method="Transferencia">
<span class="material-symbols-outlined">account_balance</span>
<span>Transferencia</span>
</button>
<button class="payment-method-btn flex flex-col items-center justify-center gap-2 p-4 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300" data-method="Credito">
<span class="material-symbols-outlined">receipt_long</span>
<span>Crédito</span>
</button>
<button class="payment-method-btn flex flex-col items-center justify-center gap-2 p-4 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300" data-method="Mixto">
<span class="material-symbols-outlined">splitscreen</span>
<span>Pago Mixto</span>
</button>
</div>
<div id="creditoCampos" class="space-y-4 hidden mt-4">
    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300">Seleccionar Cliente</label>
    <select id="clienteSelect" class="form-select w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
        <option value="">-- Selecciona un cliente --</option>
    </select>

    <input id="clienteNombre" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-7 text-lg font-semibold focus:ring-2 focus:ring-primary/50 focus:border-primary/50" placeholder="Nombre Cliente" readonly />
    <input id="clienteCelular" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-7 text-lg font-semibold focus:ring-2 focus:ring-primary/50 focus:border-primary/50" placeholder="Celular" readonly />
    <input id="clienteDireccion" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-7 text-lg font-semibold focus:ring-2 focus:ring-primary/50 focus:border-primary/50" placeholder="Dirección" readonly />
</div>
<div id="clienteCampos" class="space-y-4 hidden mt-4">
    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300">Seleccionar Cliente (Opcional)</label>
    <select id="clienteSelectGeneral" class="form-select w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
        <option value="">-- Selecciona un cliente o deja vacío para Consumidor Final --</option>
    </select>

    <input id="clienteNombreGeneral" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-3 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary/50" placeholder="Nombre (opcional)" />
    <input id="clienteCelularGeneral" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-3 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary/50" placeholder="Celular (opcional)" />
    <input id="clienteDireccionGeneral" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-3 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary/50" placeholder="Dirección (opcional)" />
</div>
<div id="bancoCampos" class="space-y-4 hidden mt-4">
    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300">Banco</label>
    <select id="bancoSelect" class="form-select w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
        <option value="">-- Selecciona el banco --</option>
        <option value="Ficohsa">Ficohsa</option>
        <option value="Banco Atlántida">Banco Atlántida</option>
        <option value="BAC Credomatic">BAC Credomatic</option>
        <option value="Banco de Occidente">Banco de Occidente</option>
        <option value="Banpaís">Banpaís</option>
        <option value="Banco Azteca">Banco Azteca</option>
        <option value="Banco Davivienda">Banco Davivienda</option>
        <option value="Banco de Honduras">Banco de Honduras</option>
        <option value="FICENSA">FICENSA</option>
        <option value="BANHCAFE">BANHCAFE</option>
        <option value="Banco Lafise">Banco Lafise</option>
        <option value="Banco Promérica">Banco Promérica</option>
        <option value="BANRURAL">BANRURAL</option>
        <option value="Banco Popular">Banco Popular</option>
        <option value="Banco Cuscatlán">Banco Cuscatlán</option>
        <option value="Otro">Otro</option>
    </select>
</div>
<div id="pagoMixtoCampos" class="space-y-4 hidden mt-4">
    <label class="block text-sm font-medium text-slate-600 dark:text-slate-300">Desglose de Pago</label>
    
    <!-- Efectivo -->
    <div>
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Efectivo</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">L</span>
            <input id="mixtoEfectivo" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-7 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary/50" type="number" step="0.01" value="0.00" />
        </div>
    </div>
    
    <!-- Tarjeta -->
    <div>
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Tarjeta</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">L</span>
            <input id="mixtoTarjeta" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-7 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary/50" type="number" step="0.01" value="0.00" />
        </div>
    </div>
    
    <!-- Transferencia -->
    <div>
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Transferencia</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">L</span>
            <input id="mixtoTransferencia" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-7 text-base focus:ring-2 focus:ring-primary/50 focus:border-primary/50" type="number" step="0.01" value="0.00" />
        </div>
    </div>
    
    <!-- Banco (solo si transferencia > 0) -->
    <div id="mixtoBancoContainer" class="hidden">
        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Banco</label>
        <select id="mixtoBanco" class="form-select w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white">
            <option value="">-- Selecciona el banco --</option>
            <option value="Ficohsa">Ficohsa</option>
            <option value="Banco Atlántida">Banco Atlántida</option>
            <option value="BAC Credomatic">BAC Credomatic</option>
            <option value="Banco de Occidente">Banco de Occidente</option>
            <option value="Banpaís">Banpaís</option>
            <option value="Banco Azteca">Banco Azteca</option>
            <option value="Banco Davivienda">Banco Davivienda</option>
            <option value="Banco de Honduras">Banco de Honduras</option>
            <option value="FICENSA">FICENSA</option>
            <option value="BANHCAFE">BANHCAFE</option>
            <option value="Banco Lafise">Banco Lafise</option>
            <option value="Banco Promérica">Banco Promérica</option>
            <option value="BANRURAL">BANRURAL</option>
            <option value="Banco Popular">Banco Popular</option>
            <option value="Banco Cuscatlán">Banco Cuscatlán</option>
            <option value="Otro">Otro</option>
        </select>
    </div>
    
    <!-- Indicador de diferencia -->
    <div class="bg-slate-100 dark:bg-slate-800 rounded-lg p-4 space-y-2">
        <div class="flex justify-between text-sm">
            <span class="text-slate-600 dark:text-slate-400">Total a pagar:</span>
            <span id="mixtoTotalAPagar" class="font-bold text-slate-900 dark:text-white">L.0.00</span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-slate-600 dark:text-slate-400">Total ingresado:</span>
            <span id="mixtoTotalIngresado" class="font-bold text-slate-900 dark:text-white">L.0.00</span>
        </div>
        <div class="flex justify-between text-base pt-2 border-t border-slate-300 dark:border-slate-600">
            <span class="font-semibold text-slate-700 dark:text-slate-300">Diferencia:</span>
            <span id="mixtoDiferencia" class="font-bold text-red-600 dark:text-red-400">Falta: L.0.00</span>
        </div>
    </div>
</div>
<br>
<div class="space-y-4">
<div>
<label class="text-sm font-medium text-slate-600 dark:text-slate-300 mb-1 block">Monto Recibido</label>
<div class="relative">
<span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-500">L</span>
<input id="amountReceived" class="form-input w-full rounded-lg border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white pl-7 text-lg font-semibold focus:ring-2 focus:ring-primary/50 focus:border-primary/50" type="number" step="0.01" value="0.00"/>
</div>
</div>

<div class="flex justify-between items-center bg-slate-100 dark:bg-slate-800/50 p-4 rounded-lg">
<span class="text-lg font-semibold text-slate-600 dark:text-slate-300">Cambio a Devolver</span>
<span id="change" class="text-2xl font-bold text-green-600 dark:text-green-500">L 0.00</span>
</div>
</div>

<div class="mt-auto pt-6">
<button id="checkoutBtn" class="w-full flex items-center justify-center gap-2 bg-primary text-white font-bold h-14 rounded-xl text-lg hover:bg-primary/90 transition-colors disabled:bg-slate-400 disabled:cursor-not-allowed">
<span>Cobrar</span>
<span id="checkoutTotal">L 0.00</span>
</button>
</div>
</div>

<!-- Modal de Éxito con Animación -->
<div id="successModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111722] rounded-3xl shadow-2xl max-w-md w-full overflow-hidden transform scale-0 transition-all duration-500 ease-out" id="successModalContent">
        <div class="p-8 text-center">
            <!-- Animación del Check -->
            <div class="mx-auto mb-6 relative">
                <div class="success-checkmark">
                    <div class="check-icon">
                        <span class="icon-line line-tip"></span>
                        <span class="icon-line line-long"></span>
                        <div class="icon-circle"></div>
                        <div class="icon-fix"></div>
                    </div>
                </div>
            </div>
            
            <!-- Texto -->
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">¡Venta Procesada!</h3>
            <p class="text-gray-600 dark:text-gray-300 mb-1">La transacción se completó exitosamente</p>
            <p id="facturaNumber" class="text-primary font-mono text-lg font-bold mb-6"></p>
            
            <!-- Botón de Cerrar -->
            <button onclick="cerrarModalExito()" class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105">
                Continuar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Venta -->
<div id="confirmModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111722] rounded-3xl shadow-2xl max-w-md w-full overflow-hidden transform scale-0 transition-all duration-500 ease-out" id="confirmModalContent">
        <div class="p-8 text-center">
            <!-- Icono de pregunta -->
            <div class="mx-auto mb-6 relative">
                <div class="w-20 h-20 mx-auto bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                    <span class="material-symbols-outlined text-5xl text-amber-600 dark:text-amber-500">help</span>
                </div>
            </div>
            
            <!-- Texto -->
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">¿Confirmar Venta?</h3>
            <p class="text-gray-600 dark:text-gray-300 mb-6">¿Está seguro de que desea procesar esta venta?</p>
            
            <!-- Botones -->
            <div class="flex gap-3">
                <button onclick="cerrarModalConfirm()" class="flex-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-800 dark:text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105">
                    Cancelar
                </button>
                <button onclick="confirmarVenta()" class="flex-1 bg-primary hover:bg-primary/90 text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 transform hover:scale-105">
                    Continuar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Selección de Tipo de Precio -->
<div id="priceModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111722] rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-primary to-blue-600 text-white p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold">Seleccionar Tipo de Precio</h3>
                    <p class="text-sm opacity-90 mt-1">Elige el precio para este producto</p>
                </div>
                <button onclick="cerrarModalPrecios()" class="text-white/80 hover:text-white">
                    <span class="material-symbols-outlined text-3xl">close</span>
                </button>
            </div>
        </div>
        
        <!-- Product Info -->
        <div class="p-6 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <div class="flex items-center gap-4">
                <div id="priceModalImage" class="w-20 h-20 bg-gray-300 dark:bg-gray-600 rounded-lg overflow-hidden flex-shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <h4 id="priceModalNombre" class="text-lg font-bold text-gray-900 dark:text-white truncate"></h4>
                    <p id="priceModalMarca" class="text-sm text-gray-500 dark:text-gray-400"></p>
                    <p id="priceModalCodigo" class="text-xs text-gray-400 dark:text-gray-500 font-mono"></p>
                    <p id="priceModalStock" class="text-xs text-green-600 dark:text-green-400 font-semibold mt-1"></p>
                </div>
            </div>
        </div>
        
        <!-- Price Options Grid -->
        <div class="p-6 overflow-y-auto" style="max-height: 400px;">
            <div id="priceOptions" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Price options will be dynamically loaded here -->
            </div>
            <div id="priceOptionsLoading" class="text-center py-8 text-gray-400">
                <span class="material-symbols-outlined text-4xl animate-spin">progress_activity</span>
                <p class="mt-2">Cargando precios...</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="p-6 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <kbd class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded text-xs">ESC</kbd> para cerrar
                </p>
                <button onclick="crearNuevoTipoPrecio()" class="flex items-center gap-1 px-3 py-2 bg-green-600 text-white rounded-lg text-xs font-semibold hover:bg-green-700 transition-colors">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add_circle</span>
                    <span>Crear Nuevo Precio</span>
                </button>
            </div>
            <button onclick="cerrarModalPrecios()" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                Cancelar
            </button>
        </div>
    </div>
</div>

<!-- Modal para Crear Nuevo Tipo de Precio -->
<div id="createPriceTypeModal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm z-[10000] flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111722] rounded-xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-green-600 to-green-700 text-white p-6 rounded-t-xl">
            <h3 class="text-xl font-bold">Crear Nuevo Tipo de Precio</h3>
            <p class="text-sm opacity-90 mt-1">Define un nuevo tipo de precio para tus productos</p>
        </div>
        
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre del Tipo de Precio</label>
                <input id="newPriceTypeName" type="text" placeholder="Ej: Mayoreo, Distribuidor, VIP..." 
                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Descripción (Opcional)</label>
                <textarea id="newPriceTypeDesc" rows="2" placeholder="Descripción del tipo de precio..." 
                          class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Precio para este Producto</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">L</span>
                    <input id="newPriceTypeValue" type="number" step="0.01" placeholder="0.00" 
                           class="w-full pl-8 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent">
                </div>
            </div>
        </div>
        
        <div class="p-6 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3 rounded-b-xl">
            <button onclick="cerrarModalCrearPrecio()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                Cancelar
            </button>
            <button onclick="guardarNuevoTipoPrecio()" class="px-4 py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition-colors">
                Crear y Usar
            </button>
        </div>
    </div>
</div>

<style>
/* Animación del Check Mark */
.success-checkmark {
    width: 80px;
    height: 80px;
    margin: 0 auto;
}

.check-icon {
    width: 80px;
    height: 80px;
    position: relative;
    border-radius: 50%;
    box-sizing: content-box;
    border: 4px solid #10b981;
}

.check-icon::before {
    top: 3px;
    left: -2px;
    width: 30px;
    transform-origin: 100% 50%;
    border-radius: 100px 0 0 100px;
}

.check-icon::after {
    top: 0;
    left: 30px;
    width: 60px;
    transform-origin: 0 50%;
    border-radius: 0 100px 100px 0;
    animation: rotate-circle 4.25s ease-in;
}

.check-icon::before, .check-icon::after {
    content: '';
    height: 100px;
    position: absolute;
    background: #FFFFFF;
    transform: rotate(-45deg);
}

.icon-line {
    height: 5px;
    background-color: #10b981;
    display: block;
    border-radius: 2px;
    position: absolute;
    z-index: 10;
}

.icon-line.line-tip {
    top: 46px;
    left: 14px;
    width: 25px;
    transform: rotate(45deg);
    animation: icon-line-tip 0.75s;
}

.icon-line.line-long {
    top: 38px;
    right: 8px;
    width: 47px;
    transform: rotate(-45deg);
    animation: icon-line-long 0.75s;
}

.icon-circle {
    top: -4px;
    left: -4px;
    z-index: 10;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    position: absolute;
    box-sizing: content-box;
    border: 4px solid rgba(16, 185, 129, 0.5);
}

.icon-fix {
    top: 8px;
    width: 5px;
    left: 26px;
    z-index: 1;
    height: 85px;
    position: absolute;
    transform: rotate(-45deg);
    background-color: #FFFFFF;
}

@keyframes rotate-circle {
    0% {
        transform: rotate(-45deg);
    }
    5% {
        transform: rotate(-45deg);
    }
    12% {
        transform: rotate(-405deg);
    }
    100% {
        transform: rotate(-405deg);
    }
}

@keyframes icon-line-tip {
    0% {
        width: 0;
        left: 1px;
        top: 19px;
    }
    54% {
        width: 0;
        left: 1px;
        top: 19px;
    }
    70% {
        width: 50px;
        left: -8px;
        top: 37px;
    }
    84% {
        width: 17px;
        left: 21px;
        top: 48px;
    }
    100% {
        width: 25px;
        left: 14px;
        top: 45px;
    }
}

@keyframes icon-line-long {
    0% {
        width: 0;
        right: 46px;
        top: 54px;
    }
    65% {
        width: 0;
        right: 46px;
        top: 54px;
    }
    84% {
        width: 55px;
        right: 0px;
        top: 35px;
    }
    100% {
        width: 47px;
        right: 8px;
        top: 38px;
    }
}

/* Estilos dark mode para el check */
.dark .check-icon::before,
.dark .check-icon::after,
.dark .icon-fix {
    background: #111722;
}
</style>

</main>
</div>
</div>

<script>
// Variables globales
let cart = [];
let selectedPaymentMethod = 'Efectivo';
const TAX_RATE = 0.00; // Corregido: 15% en lugar de 0%

// Variables para sistema de precios
let tiposPrecios = []; // Almacena todos los tipos de precios disponibles
let priceModalProduct = null; // Producto actual en el modal de precios

// Búsqueda de productos con filtrado
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const products = document.querySelectorAll('.product-item');
    
    products.forEach(product => {
        const searchData = product.dataset.search;
        if (searchData.includes(searchTerm)) {
            product.style.display = 'flex';
        } else {
            product.style.display = 'none';
        }
    });
});

// Escáner de código de barras - detectar Enter
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const searchTerm = e.target.value.trim();
        
        if (searchTerm === '') return;
        
        // Buscar producto por código exacto
        const products = document.querySelectorAll('.product-item');
        let productoEncontrado = null;
        
        products.forEach(product => {
            const codigo = product.dataset.codigo.toLowerCase();
            if (codigo === searchTerm.toLowerCase()) {
                productoEncontrado = product;
            }
        });
        
        if (productoEncontrado) {
            // Agregar producto al carrito
            const product = {
                id: productoEncontrado.dataset.id,
                codigo: productoEncontrado.dataset.codigo,
                nombre: productoEncontrado.dataset.nombre,
                marca: productoEncontrado.dataset.marca,
                precio: parseFloat(productoEncontrado.dataset.precio),
                stock: parseInt(productoEncontrado.dataset.stock)
            };
            
            addToCart(product);
            
            // Feedback visual y sonoro
            productoEncontrado.classList.add('ring-4', 'ring-green-500');
            setTimeout(() => {
                productoEncontrado.classList.remove('ring-4', 'ring-green-500');
            }, 500);
            
            // Sonido de beep (opcional)
            playBeepSound();
            
            // Limpiar búsqueda para siguiente escaneo
            e.target.value = '';
            
            // Mostrar todos los productos
            products.forEach(p => p.style.display = 'flex');
        } else {
            // Producto no encontrado - feedback negativo
            e.target.classList.add('ring-4', 'ring-red-500');
            setTimeout(() => {
                e.target.classList.remove('ring-4', 'ring-red-500');
            }, 500);
            
            // Sonido de error
            playErrorSound();
        }
    }
});

// Función para reproducir sonido de beep del escáner
function playBeepSound() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.value = 800; // Beep agudo
    oscillator.type = 'square';
    
    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
    
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.1);
}

// Función para reproducir sonido de error
function playErrorSound() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.value = 200; // Beep grave
    oscillator.type = 'sawtooth';
    
    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
    
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + 0.3);
}

// Función para reproducir sonido al agregar al carrito
function playAddToCartSound() {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    
    // Primer tono (más agudo)
    const osc1 = audioContext.createOscillator();
    const gain1 = audioContext.createGain();
    
    osc1.connect(gain1);
    gain1.connect(audioContext.destination);
    
    osc1.frequency.value = 600; // Nota más aguda
    osc1.type = 'sine';
    
    gain1.gain.setValueAtTime(0.2, audioContext.currentTime);
    gain1.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.15);
    
    osc1.start(audioContext.currentTime);
    osc1.stop(audioContext.currentTime + 0.15);
    
    // Segundo tono (más grave, ligeramente después)
    const osc2 = audioContext.createOscillator();
    const gain2 = audioContext.createGain();
    
    osc2.connect(gain2);
    gain2.connect(audioContext.destination);
    
    osc2.frequency.value = 450; // Nota más grave
    osc2.type = 'sine';
    
    gain2.gain.setValueAtTime(0.2, audioContext.currentTime + 0.08);
    gain2.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.25);
    
    osc2.start(audioContext.currentTime + 0.08);
    osc2.stop(audioContext.currentTime + 0.25);
}

// Agregar producto al carrito - Nuevo botón debajo del stock
document.querySelectorAll('.add-product-btn-new').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        const productDiv = this.closest('.product-item');
        const product = {
            id: productDiv.dataset.id,
            codigo: productDiv.dataset.codigo,
            nombre: productDiv.dataset.nombre,
            marca: productDiv.dataset.marca,
            precio: parseFloat(productDiv.dataset.precio),
            stock: parseInt(productDiv.dataset.stock)
        };
        
        // Abrir modal de selección de precios
        addToCart(product);
    });
});

// Agregar producto al hacer click en la imagen
document.querySelectorAll('.product-image-click').forEach(img => {
    img.addEventListener('click', function(e) {
        e.stopPropagation();
        const productDiv = this.closest('.product-item');
        const product = {
            id: productDiv.dataset.id,
            codigo: productDiv.dataset.codigo,
            nombre: productDiv.dataset.nombre,
            marca: productDiv.dataset.marca,
            precio: parseFloat(productDiv.dataset.precio),
            stock: parseInt(productDiv.dataset.stock)
        };
        
        // Abrir modal de selección de precios
        addToCart(product);
    });
});

// Función principal para agregar productos al carrito con soporte de tipos de precios
async function addToCart(product, tipoPrecioId = null, tipoPrecioNombre = null, precio = null) {
    // Si no se proporciona tipo de precio, usar el precio por defecto
    if (tipoPrecioId === null) {
        // Usar el precio por defecto del producto (Precio_Unitario)
        tipoPrecioId = 1; // ID del tipo de precio por defecto
        tipoPrecioNombre = 'Precio Normal';
        precio = product.precio;
    }
    
    // Verificar si el producto ya existe en el carrito con el mismo tipo de precio
    const existingItem = cart.find(item => item.id === product.id && item.tipoPrecioId === tipoPrecioId);
    
    if (existingItem) {
        // Si ya existe, incrementar cantidad
        if (existingItem.cantidad < product.stock) {
            existingItem.cantidad++;
            renderCart();
            updateTotals();
            mostrarInfo(`Cantidad actualizada: ${product.nombre}`);
        } else {
            mostrarAdvertencia(`Stock insuficiente para ${product.nombre}`);
        }
    } else {
        // Agregar nuevo item al carrito
        cart.push({
            id: product.id,
            codigo: product.codigo,
            nombre: product.nombre,
            marca: product.marca,
            precio: precio,
            cantidad: 1,
            stock: product.stock,
            tipoPrecioId: tipoPrecioId,
            tipoPrecioNombre: tipoPrecioNombre
        });
        
        renderCart();
        updateTotals();
        mostrarExito(`${product.nombre} agregado al carrito`);
        playAddToCartSound(); // Sonido de producto agregado
    }
}

// Eliminar producto del carrito
function removeFromCart(productId, tipoPrecioId) {
    cart = cart.filter(item => !(item.id === productId && item.tipoPrecioId === tipoPrecioId));
    renderCart();
    updateTotals();
}

// Actualizar cantidad de un producto
function updateQuantity(productId, tipoPrecioId, newQuantity) {
    const item = cart.find(item => item.id === productId && item.tipoPrecioId === tipoPrecioId);
    if (item) {
        if (newQuantity <= 0) {
            removeFromCart(productId, tipoPrecioId);
        } else if (newQuantity <= item.stock) {
            item.cantidad = newQuantity;
            renderCart();
            updateTotals();
        } else {
            mostrarAdvertencia('No hay suficiente stock disponible');
        }
    }
}

// Renderizar carrito
function renderCart() {
    const orderItems = document.getElementById('orderItems');
    
    if (cart.length === 0) {
        orderItems.innerHTML = `
            <div class="text-center text-slate-400 dark:text-slate-500 py-8">
                <span class="material-symbols-outlined text-6xl">shopping_cart</span>
                <p class="mt-2">No hay productos en el carrito</p>
            </div>
        `;
        return;
    }
    
    orderItems.innerHTML = cart.map(item => `
        <div class="flex items-center gap-4 p-3 bg-slate-50 dark:bg-slate-800/30 rounded-lg">
            <div class="flex items-center gap-2 border border-slate-200 dark:border-slate-700 rounded-lg p-1">
                <button onclick="updateQuantity('${item.id}', ${item.tipoPrecioId}, ${item.cantidad - 1})" class="size-6 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-primary dark:hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-base">remove</span>
                </button>
                <span class="font-medium text-slate-800 dark:text-white w-4 text-center">${item.cantidad}</span>
                <button onclick="updateQuantity('${item.id}', ${item.tipoPrecioId}, ${item.cantidad + 1})" class="size-6 flex items-center justify-center text-slate-500 dark:text-slate-400 hover:text-primary dark:hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-base">add</span>
                </button>
            </div>
            <div class="flex-1">
                <p class="font-medium text-slate-800 dark:text-white">${item.nombre}</p>
                <p class="text-xs text-slate-500 dark:text-slate-400">${item.marca}</p>
                <div class="flex items-center gap-2 mt-1">
                    <button onclick="cambiarPrecioCarrito('${item.id}', ${item.tipoPrecioId})" 
                            class="text-xs px-2 py-0.5 bg-primary/10 text-primary rounded-full font-medium hover:bg-primary/20 transition-colors cursor-pointer flex items-center gap-1"
                            title="Cambiar tipo de precio">
                        <span class="material-symbols-outlined" style="font-size: 14px;">edit</span>
                        ${item.tipoPrecioNombre}
                    </button>
                    <span class="text-sm text-slate-500 dark:text-slate-400">L ${item.precio.toFixed(2)}</span>
                </div>
            </div>
            <p class="font-semibold text-slate-800 dark:text-white">L ${(item.precio * item.cantidad).toFixed(2)}</p>
            <button onclick="removeFromCart('${item.id}', ${item.tipoPrecioId})" class="text-slate-400 hover:text-red-500 dark:hover:text-red-500 transition-colors">
                <span class="material-symbols-outlined">delete</span>
            </button>
        </div>
    `).join('');
}

function updateTotals() {
    const subtotal = cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    const tax = subtotal * TAX_RATE;
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = `L ${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `L ${tax.toFixed(2)}`;
    document.getElementById('total').textContent = `L ${total.toFixed(2)}`;
    document.getElementById('checkoutTotal').textContent = `L ${total.toFixed(2)}`;
    
    // Auto-actualizar campos de pago según el método seleccionado
    const amountReceivedInput = document.getElementById('amountReceived');
    
    if (selectedPaymentMethod === 'Credito' || selectedPaymentMethod === 'Transferencia' || selectedPaymentMethod === 'Tarjeta') {
        // Para estos métodos, el monto recibido siempre es igual al total
        amountReceivedInput.value = total.toFixed(2);
    } else if (selectedPaymentMethod === 'Mixto') {
        // Para pago mixto, actualizar el indicador
        actualizarIndicadorMixto();
    }
    // Para efectivo, no auto-llenar (el usuario ingresa el monto)
    
    calculateChange();
}

// Métodos de pago
document.querySelectorAll('.payment-method-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.payment-method-btn').forEach(b => {
            b.classList.remove('border-primary', 'bg-primary/10', 'text-primary', 'border-2');
            b.classList.add('border', 'border-slate-200', 'dark:border-slate-700', 'text-slate-600', 'dark:text-slate-300');
        });
        
        this.classList.remove('border', 'border-slate-200', 'dark:border-slate-700', 'text-slate-600', 'dark:text-slate-300');
        this.classList.add('border-2', 'border-primary', 'bg-primary/10', 'text-primary');
        
        selectedPaymentMethod = this.dataset.method;
        
        // Mostrar u ocultar campos según el método de pago seleccionado
        const creditoCampos = document.getElementById('creditoCampos');
        const clienteCampos = document.getElementById('clienteCampos');
        const bancoCampos = document.getElementById('bancoCampos');
        const pagoMixtoCampos = document.getElementById('pagoMixtoCampos');
        
        if (selectedPaymentMethod === 'Credito') {
            creditoCampos.classList.remove('hidden');
            clienteCampos.classList.add('hidden');
            bancoCampos.classList.add('hidden');
            pagoMixtoCampos.classList.add('hidden');
            cargarClientes();
        } else if (selectedPaymentMethod === 'Transferencia') {
            creditoCampos.classList.add('hidden');
            clienteCampos.classList.remove('hidden');
            bancoCampos.classList.remove('hidden');
            pagoMixtoCampos.classList.add('hidden');
            cargarClientesGeneral();
        } else if (selectedPaymentMethod === 'Mixto') {
            creditoCampos.classList.add('hidden');
            clienteCampos.classList.remove('hidden');
            bancoCampos.classList.add('hidden');
            pagoMixtoCampos.classList.remove('hidden');
            cargarClientesGeneral();
            actualizarIndicadorMixto();
        } else if (selectedPaymentMethod === 'Efectivo' || selectedPaymentMethod === 'Tarjeta') {
            creditoCampos.classList.add('hidden');
            clienteCampos.classList.remove('hidden');
            bancoCampos.classList.add('hidden');
            pagoMixtoCampos.classList.add('hidden');
            cargarClientesGeneral();
        } else {
            creditoCampos.classList.add('hidden');
            clienteCampos.classList.add('hidden');
            bancoCampos.classList.add('hidden');
            pagoMixtoCampos.classList.add('hidden');
        }
        
        // Auto-llenar monto recibido si NO es efectivo ni mixto
        const amountReceivedInput = document.getElementById('amountReceived');
        if (selectedPaymentMethod === 'Credito' || selectedPaymentMethod === 'Transferencia' || selectedPaymentMethod === 'Tarjeta') {
            const total = cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0) * (1 + TAX_RATE);
            amountReceivedInput.value = total.toFixed(2);
            calculateChange();
        } else if (selectedPaymentMethod === 'Mixto') {
            // Para pago mixto, no auto-llenar el monto recibido
            amountReceivedInput.value = '0.00';
            calculateChange();
        } else {
            // Si es efectivo, limpiar el campo
            amountReceivedInput.value = '0.00';
            calculateChange();
        }
    });
});

// Funciones para Pago Mixto
function actualizarIndicadorMixto() {
    const total = cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0) * (1 + TAX_RATE);
    const efectivo = parseFloat(document.getElementById('mixtoEfectivo').value) || 0;
    const tarjeta = parseFloat(document.getElementById('mixtoTarjeta').value) || 0;
    const transferencia = parseFloat(document.getElementById('mixtoTransferencia').value) || 0;
    
    const totalIngresado = efectivo + tarjeta + transferencia;
    const diferencia = total - totalIngresado;
    
    // Actualizar displays
    document.getElementById('mixtoTotalAPagar').textContent = `L.${total.toFixed(2)}`;
    document.getElementById('mixtoTotalIngresado').textContent = `L.${totalIngresado.toFixed(2)}`;
    
    // Auto-llenar monto recibido con el total ingresado
    document.getElementById('amountReceived').value = totalIngresado.toFixed(2);
    calculateChange();
    
    const diferenciaElement = document.getElementById('mixtoDiferencia');
    if (Math.abs(diferencia) < 0.01) {
        diferenciaElement.textContent = '✓ Completo';
        diferenciaElement.className = 'font-bold text-green-600 dark:text-green-400';
    } else if (diferencia > 0) {
        diferenciaElement.textContent = `Falta: L.${diferencia.toFixed(2)}`;
        diferenciaElement.className = 'font-bold text-red-600 dark:text-red-400';
    } else {
        diferenciaElement.textContent = `Sobra: L.${Math.abs(diferencia).toFixed(2)}`;
        diferenciaElement.className = 'font-bold text-blue-600 dark:text-blue-400';
    }
    
    // Mostrar/ocultar campo de banco si hay transferencia
    const mixtoBancoContainer = document.getElementById('mixtoBancoContainer');
    if (transferencia > 0) {
        mixtoBancoContainer.classList.remove('hidden');
    } else {
        mixtoBancoContainer.classList.add('hidden');
        document.getElementById('mixtoBanco').value = '';
    }
}

// Event listeners para campos de pago mixto
document.addEventListener('DOMContentLoaded', () => {
    const mixtoEfectivo = document.getElementById('mixtoEfectivo');
    const mixtoTarjeta = document.getElementById('mixtoTarjeta');
    const mixtoTransferencia = document.getElementById('mixtoTransferencia');
    
    if (mixtoEfectivo) mixtoEfectivo.addEventListener('input', actualizarIndicadorMixto);
    if (mixtoTarjeta) mixtoTarjeta.addEventListener('input', actualizarIndicadorMixto);
    if (mixtoTransferencia) mixtoTransferencia.addEventListener('input', actualizarIndicadorMixto);
});

// Calcular cambio
document.getElementById('amountReceived').addEventListener('input', calculateChange);

function calculateChange() {
    const total = cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0) * (1 + TAX_RATE);
    const received = parseFloat(document.getElementById('amountReceived').value) || 0;
    const change = Math.max(0, received - total);
    
    document.getElementById('change').textContent = `L ${change.toFixed(2)}`;
}

// Procesar venta
document.getElementById('checkoutBtn').addEventListener('click', function() {
    if (cart.length === 0) {
        mostrarAdvertencia('El carrito está vacío');
        return;
    }
    
    const total = cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0) * (1 + TAX_RATE);
    const received = parseFloat(document.getElementById('amountReceived').value) || 0;
    
    if (selectedPaymentMethod === 'Efectivo' && received < total) {
        mostrarAdvertencia('El monto recibido es insuficiente');
        return;
    }
    
    // Abrir modal de confirmación en lugar de confirm() nativo
    mostrarModalConfirm();
});

function processSale() {
    const subtotal = cart.reduce((sum, item) => sum + (item.precio * item.cantidad), 0);
    const tax = subtotal * TAX_RATE;
    const total = subtotal + tax;
    
    const saleData = {
        items: cart,
        subtotal: subtotal,
        tax: tax,
        total: total,
        paymentMethod: selectedPaymentMethod,
        amountReceived: parseFloat(document.getElementById('amountReceived').value) || 0,
        vendedor: '<?php echo $Nombre_Completo; ?>'
    };
    
    // DEBUG: Ver qué se está enviando
    console.log('=== DATOS DE VENTA ===');
    console.log('Cart items:', cart);
    console.log('Sale data:', saleData);
    
    // Obtener referencias a los elementos del DOM
    const clienteSelect = document.getElementById('clienteSelect');
    const clienteNombre = document.getElementById('clienteNombre');
    const clienteCelular = document.getElementById('clienteCelular');
    const clienteDireccion = document.getElementById('clienteDireccion');
    
    if (selectedPaymentMethod === 'Credito') {
        if (!clienteSelect.value || !clienteNombre.value || !clienteCelular.value || !clienteDireccion.value) {
            mostrarAdvertencia('Debes seleccionar un cliente válido para crédito.');
            return;
        }

        saleData.idCliente = clienteSelect.value;
        saleData.nombreCliente = clienteNombre.value;
        saleData.celular = clienteCelular.value;
        saleData.direccion = clienteDireccion.value;
    } else if (selectedPaymentMethod === 'Efectivo' || selectedPaymentMethod === 'Tarjeta' || selectedPaymentMethod === 'Transferencia' || selectedPaymentMethod === 'Mixto') {
        // Manejar cliente para métodos de pago generales
        const clienteSelectGeneral = document.getElementById('clienteSelectGeneral');
        const nombreGeneral = document.getElementById('clienteNombreGeneral').value.trim();
        const celularGeneral = document.getElementById('clienteCelularGeneral').value.trim();
        const direccionGeneral = document.getElementById('clienteDireccionGeneral').value.trim();
        
        // Si es transferencia, validar que se haya seleccionado un banco
        if (selectedPaymentMethod === 'Transferencia') {
            const bancoSelect = document.getElementById('bancoSelect');
            if (!bancoSelect.value) {
                mostrarAdvertencia('Por favor selecciona el banco para la transferencia');
                return;
            }
            saleData.banco = bancoSelect.value;
        }
        
        // Si es pago mixto, validar y agregar datos
        if (selectedPaymentMethod === 'Mixto') {
            const mixtoEfectivo = parseFloat(document.getElementById('mixtoEfectivo').value) || 0;
            const mixtoTarjeta = parseFloat(document.getElementById('mixtoTarjeta').value) || 0;
            const mixtoTransferencia = parseFloat(document.getElementById('mixtoTransferencia').value) || 0;
            const totalIngresado = mixtoEfectivo + mixtoTarjeta + mixtoTransferencia;
            
            // Validar que la suma sea igual al total
            if (Math.abs(totalIngresado - total) > 0.01) {
                mostrarAdvertencia(`La suma de los pagos (L.${totalIngresado.toFixed(2)}) no coincide con el total (L.${total.toFixed(2)})`);
                return;
            }
            
            // Validar banco si hay transferencia
            if (mixtoTransferencia > 0) {
                const mixtoBanco = document.getElementById('mixtoBanco').value;
                if (!mixtoBanco) {
                    mostrarAdvertencia('Por favor selecciona el banco para la transferencia');
                    return;
                }
                saleData.mixto_banco = mixtoBanco;
            }
            
            // Agregar montos al saleData
            saleData.mixto_efectivo = mixtoEfectivo;
            saleData.mixto_tarjeta = mixtoTarjeta;
            saleData.mixto_transferencia = mixtoTransferencia;
        }
        
        if (nombreGeneral && celularGeneral && direccionGeneral) {
            // Si llenaron los campos pero no hay cliente seleccionado, crear nuevo cliente
            if (!clienteSelectGeneral.value) {
                // Crear cliente nuevo antes de procesar la venta
                crearClienteYProcesarVenta(saleData, nombreGeneral, celularGeneral, direccionGeneral);
                return; // Salir aquí porque crearClienteYProcesarVenta manejará el resto
            } else {
                // Cliente existente seleccionado
                saleData.nombreCliente = nombreGeneral;
                saleData.celular = celularGeneral;
                saleData.direccion = direccionGeneral;
            }
        } else if (!nombreGeneral && !celularGeneral && !direccionGeneral) {
            // Si están vacíos, usar valores por defecto
            saleData.nombreCliente = 'CONSUMIDOR FINAL';
            saleData.celular = 'NA';
            saleData.direccion = 'NA';
        } else {
            // Si llenaron algunos campos pero no todos
            mostrarAdvertencia('Por favor complete todos los campos del cliente o déjelos todos vacíos para usar Consumidor Final');
            return;
        }
    }

    fetch('procesar_venta.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(saleData)
    })
    .then(response => response.json())
    .then(data => {
         if (data.success) {
            // Mostrar modal de éxito con animación
            mostrarModalExito(data.factura_id);
            
            // Limpiar carrito y campos
            cart = [];
            renderCart();
            updateTotals();
            document.getElementById('amountReceived').value = '0.00';
            document.getElementById('change').textContent = 'L 0.00';
            
            // Limpiar y ocultar campos de cliente si estaban visibles
            if (selectedPaymentMethod === 'Credito') {
                clienteSelect.value = '';
                clienteNombre.value = '';
                clienteCelular.value = '';
                clienteDireccion.value = '';
                
                // Ocultar los campos de cliente
                document.getElementById('creditoCampos').classList.add('hidden');
                
                // Restablecer el método de pago a Efectivo y actualizar la UI
                selectedPaymentMethod = 'Efectivo';
                resetPaymentMethodUI();
            } else if (selectedPaymentMethod === 'Efectivo' || selectedPaymentMethod === 'Tarjeta' || selectedPaymentMethod === 'Transferencia') {
                const clienteSelectGeneral = document.getElementById('clienteSelectGeneral');
                const nombreGeneral = document.getElementById('clienteNombreGeneral');
                const celularGeneral = document.getElementById('clienteCelularGeneral');
                const direccionGeneral = document.getElementById('clienteDireccionGeneral');
                
                clienteSelectGeneral.value = '';
                nombreGeneral.value = '';
                celularGeneral.value = '';
                direccionGeneral.value = '';
                nombreGeneral.readOnly = false;
                celularGeneral.readOnly = false;
                direccionGeneral.readOnly = false;
                
                document.getElementById('clienteCampos').classList.add('hidden');
                
                selectedPaymentMethod = 'Efectivo';
                resetPaymentMethodUI();
            }
            
            // Abrir ventana de impresión automáticamente si se indica
            if (data.should_print && data.factura_id) {
                setTimeout(() => {
                    const printUrl = `imprimir_documento.php?factura_id=${encodeURIComponent(data.factura_id)}`;
                    window.open(printUrl, '_blank', 'width=800,height=600');
                }, 1000); // Delay para que el modal de éxito se muestre primero
            }
        } else {
            mostrarError('Error al procesar la venta: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error al procesar la venta');
    });
}

function resetPaymentMethodUI() {
    // Restablecer todos los botones de método de pago
    document.querySelectorAll('.payment-method-btn').forEach(btn => {
        btn.classList.remove('border-primary', 'bg-primary/10', 'text-primary', 'border-2');
        btn.classList.add('border', 'border-slate-200', 'dark:border-slate-700', 'text-slate-600', 'dark:text-slate-300');
    });
    
    // Seleccionar el botón de Efectivo
    const efectivoBtn = document.querySelector('.payment-method-btn[data-method="Efectivo"]');
    if (efectivoBtn) {
        efectivoBtn.classList.remove('border', 'border-slate-200', 'dark:border-slate-700', 'text-slate-600', 'dark:text-slate-300');
        efectivoBtn.classList.add('border-2', 'border-primary', 'bg-primary/10', 'text-primary');
    }
}

function cargarClientes() {
    fetch('obtener_clientes.php')
        .then(res => {
            if (!res.ok) throw new Error('Error al obtener clientes');
            return res.json();
        })
        .then(data => {
            if (!data.success || !Array.isArray(data.clientes)) {
                throw new Error('Respuesta inválida del servidor');
            }

            const clienteSelect = document.getElementById('clienteSelect');
            clienteSelect.innerHTML = '<option value="">-- Selecciona un cliente --</option>';
            data.clientes.forEach(cliente => {
                const option = document.createElement('option');
                option.value = cliente.idCliente;
                option.textContent = cliente.nombreCliente;
                option.dataset.celular = cliente.celular;
                option.dataset.direccion = cliente.direccion;
                clienteSelect.appendChild(option);
            });
        })
        .catch(err => {
            console.error('Error al cargar clientes:', err);
            mostrarInfo('No se pudieron cargar los clientes. Revisa la consola.');
        });
}

document.getElementById('clienteSelect').addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];
    document.getElementById('clienteNombre').value = selected.textContent;
    document.getElementById('clienteCelular').value = selected.dataset.celular || '';
    document.getElementById('clienteDireccion').value = selected.dataset.direccion || '';
});

// Cargar clientes para métodos de pago generales (Efectivo, Tarjeta, Transferencia)
function cargarClientesGeneral() {
    fetch('obtener_clientes.php')
        .then(res => {
            if (!res.ok) throw new Error('Error al obtener clientes');
            return res.json();
        })
        .then(data => {
            if (!data.success || !Array.isArray(data.clientes)) {
                throw new Error('Respuesta inválida del servidor');
            }

            const clienteSelect = document.getElementById('clienteSelectGeneral');
            clienteSelect.innerHTML = '<option value="">-- Selecciona un cliente o deja vacío para Consumidor Final --</option>';
            data.clientes.forEach(cliente => {
                const option = document.createElement('option');
                option.value = cliente.Id;
                option.textContent = cliente.nombreCliente;
                option.dataset.celular = cliente.celular;
                option.dataset.direccion = cliente.direccion;
                clienteSelect.appendChild(option);
            });
        })
        .catch(err => {
            console.error('Error al cargar clientes:', err);
            mostrarInfo('No se pudieron cargar los clientes. Revisa la consola.');
        });
}

// Manejar selección de cliente para métodos de pago generales
document.getElementById('clienteSelectGeneral').addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];
    const nombreInput = document.getElementById('clienteNombreGeneral');
    const celularInput = document.getElementById('clienteCelularGeneral');
    const direccionInput = document.getElementById('clienteDireccionGeneral');
    
    if (selected.value) {
        // Si se seleccionó un cliente existente, llenar los campos y hacerlos readonly
        nombreInput.value = selected.textContent;
        celularInput.value = selected.dataset.celular || '';
        direccionInput.value = selected.dataset.direccion || '';
        nombreInput.readOnly = true;
        celularInput.readOnly = true;
        direccionInput.readOnly = true;
    } else {
        // Si se deselecciona, limpiar campos y permitir edición
        nombreInput.value = '';
        celularInput.value = '';
        direccionInput.value = '';
        nombreInput.readOnly = false;
        celularInput.readOnly = false;
        direccionInput.readOnly = false;
    }
});

// Función para mostrar modal de éxito
function mostrarModalExito(facturaId) {
    // Reproducir sonido de éxito
    playSuccessSound();
    
    // Vibración si está disponible
    if ('vibrate' in navigator) {
        navigator.vibrate([100, 50, 100]);
    }
    
    // Actualizar número de factura
    document.getElementById('facturaNumber').textContent = 'Factura: ' + facturaId;
    
    // Mostrar modal
    const modal = document.getElementById('successModal');
    const content = document.getElementById('successModalContent');
    
    modal.classList.remove('hidden');
    
    // Animación de entrada
    setTimeout(() => {
        content.style.transform = 'scale(1)';
    }, 10);
}

// Función para cerrar modal
function cerrarModalExito() {
    const modal = document.getElementById('successModal');
    const content = document.getElementById('successModalContent');
    
    // Animación de salida
    content.style.transform = 'scale(0)';
    
    setTimeout(() => {
        modal.classList.add('hidden');
        // Recargar la página para limpiar todo
        location.reload();
    }, 300);
}

// Función para reproducir sonido de éxito tipo Apple Pay
function playSuccessSound() {
    // Crear contexto de audio
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    
    // Frecuencias para el sonido de éxito (Do, Mi, Sol - acorde mayor)
    const frequencies = [523.25, 659.25, 783.99]; // C5, E5, G5
    
    frequencies.forEach((freq, index) => {
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.value = freq;
        oscillator.type = 'sine';
        
        const startTime = audioContext.currentTime + (index * 0.05);
        const duration = 0.15;
        
        gainNode.gain.setValueAtTime(0, startTime);
        gainNode.gain.linearRampToValueAtTime(0.3, startTime + 0.01);
        gainNode.gain.exponentialRampToValueAtTime(0.01, startTime + duration);
        
        oscillator.start(startTime);
        oscillator.stop(startTime + duration);
    });
}

// Funciones para el modal de confirmación
function mostrarModalConfirm() {
    const modal = document.getElementById('confirmModal');
    const content = document.getElementById('confirmModalContent');
    
    modal.classList.remove('hidden');
    
    // Animación de entrada
    setTimeout(() => {
        content.style.transform = 'scale(1)';
    }, 10);
}

function cerrarModalConfirm() {
    const modal = document.getElementById('confirmModal');
    const content = document.getElementById('confirmModalContent');
    
    // Animación de salida
    content.style.transform = 'scale(0)';
    
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

function confirmarVenta() {
    // Cerrar el modal de confirmación
    cerrarModalConfirm();
    
    // Procesar la venta
    processSale();
}

// Función para crear un cliente y luego procesar la venta
function crearClienteYProcesarVenta(saleData, nombre, celular, direccion) {
    // Primero crear el cliente
    fetch('gestionar_cliente.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'crear_cliente',
            nombre: nombre,
            celular: celular,
            direccion: direccion
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cliente creado exitosamente, añadir datos a la venta
            saleData.nombreCliente = data.cliente.Nombre;
            saleData.celular = data.cliente.Celular;
            saleData.direccion = data.cliente.Direccion;
            
            // Ahora procesar la venta
            procesarVentaFinal(saleData);
        } else {
            mostrarError('Error al crear cliente: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error al crear cliente');
    });
}

// Función auxiliar para procesar la venta (separada para reutilización)
function procesarVentaFinal(saleData) {
    fetch('procesar_venta.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(saleData)
    })
    .then(response => response.json())
    .then(data => {
         if (data.success) {
            // Mostrar modal de éxito con animación
            mostrarModalExito(data.factura_id);
            
            // Limpiar carrito y campos
            cart = [];
            renderCart();
            updateTotals();
            document.getElementById('amountReceived').value = '0.00';
            document.getElementById('change').textContent = 'L 0.00';
            
            // Limpiar y ocultar campos de cliente si estaban visibles
            if (selectedPaymentMethod === 'Credito') {
                const clienteSelect = document.getElementById('clienteSelect');
                const clienteNombre = document.getElementById('clienteNombre');
                const clienteCelular = document.getElementById('clienteCelular');
                const clienteDireccion = document.getElementById('clienteDireccion');
                
                clienteSelect.value = '';
                clienteNombre.value = '';
                clienteCelular.value = '';
                clienteDireccion.value = '';
                
                document.getElementById('creditoCampos').classList.add('hidden');
                
                selectedPaymentMethod = 'Efectivo';
                resetPaymentMethodUI();
            } else if (selectedPaymentMethod === 'Efectivo' || selectedPaymentMethod === 'Tarjeta' || selectedPaymentMethod === 'Transferencia') {
                const clienteSelectGeneral = document.getElementById('clienteSelectGeneral');
                const nombreGeneral = document.getElementById('clienteNombreGeneral');
                const celularGeneral = document.getElementById('clienteCelularGeneral');
                const direccionGeneral = document.getElementById('clienteDireccionGeneral');
                
                clienteSelectGeneral.value = '';
                nombreGeneral.value = '';
                celularGeneral.value = '';
                direccionGeneral.value = '';
                nombreGeneral.readOnly = false;
                celularGeneral.readOnly = false;
                direccionGeneral.readOnly = false;
                
                document.getElementById('clienteCampos').classList.add('hidden');
            }
            
            // Abrir ventana de impresión automáticamente si se indica
            if (data.should_print && data.factura_id) {
                setTimeout(() => {
                    const printUrl = `imprimir_documento.php?factura_id=${encodeURIComponent(data.factura_id)}`;
                    window.open(printUrl, '_blank', 'width=800,height=600');
                }, 1000); // Delay para que el modal de éxito se muestre primero
            }
        } else {
            mostrarError('Error al procesar la venta: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        mostrarError('Error al procesar la venta');
    });
}

// ========================================
// SISTEMA DE SELECCIÓN DE TIPOS DE PRECIOS
// ========================================

// Cargar tipos de precios disponibles
async function cargarTiposPrecios() {
    try {
        const response = await fetch('api/tipos_precios.php');
        const data = await response.json();
        
        if (data.success) {
            tiposPrecios = data.tipos || [];
            console.log('Tipos de precios cargados:', tiposPrecios.length);
        } else {
            console.error('Error al cargar tipos de precios:', data.message);
            mostrarError('No se pudieron cargar los tipos de precios');
        }
    } catch (error) {
        console.error('Error al cargar tipos de precios:', error);
        mostrarError('Error de conexión al cargar tipos de precios');
    }
}

// Abrir modal de selección de precios
async function abrirModalPrecios(product) {
    priceModalProduct = product;
    
    // Actualizar información del producto en el modal
    document.getElementById('priceModalNombre').textContent = product.nombre;
    document.getElementById('priceModalMarca').textContent = product.marca || 'Sin marca';
    document.getElementById('priceModalCodigo').textContent = `Código: ${product.codigo}`;
    document.getElementById('priceModalStock').textContent = `Stock disponible: ${product.stock} unidades`;
    
    // Actualizar imagen del producto
    const imageContainer = document.getElementById('priceModalImage');
    const productDiv = document.querySelector(`.product-item[data-id="${product.id}"]`);
    if (productDiv) {
        const img = productDiv.querySelector('img');
        if (img) {
            imageContainer.innerHTML = `<img src="${img.src}" alt="${product.nombre}" class="w-full h-full object-cover rounded-lg">`;
        } else {
            const initials = product.nombre.substring(0, 2).toUpperCase();
            imageContainer.innerHTML = `<div class="w-full h-full bg-slate-600 flex items-center justify-center text-white font-bold text-2xl rounded-lg">${initials}</div>`;
        }
    }
    
    // Mostrar loading
    document.getElementById('priceOptions').innerHTML = '';
    document.getElementById('priceOptionsLoading').classList.remove('hidden');
    
    // Mostrar modal
    document.getElementById('priceModal').classList.remove('hidden');
    
    // Cargar precios del producto
    await cargarPreciosProducto(product.id);
}

// Cargar precios específicos de un producto
async function cargarPreciosProducto(productoId) {
    try {
        const response = await fetch(`api/precios_producto.php?producto_id=${productoId}`);
        const data = await response.json();
        
        document.getElementById('priceOptionsLoading').classList.add('hidden');
        
        if (data.success) {
            const precios = data.precios || {};
            renderPriceOptions(precios);
        } else {
            document.getElementById('priceOptions').innerHTML = `
                <div class="col-span-2 text-center py-8 text-gray-400">
                    <span class="material-symbols-outlined text-4xl">error</span>
                    <p class="mt-2">No se pudieron cargar los precios</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error al cargar precios del producto:', error);
        document.getElementById('priceOptionsLoading').classList.add('hidden');
        document.getElementById('priceOptions').innerHTML = `
            <div class="col-span-2 text-center py-8 text-gray-400">
                <span class="material-symbols-outlined text-4xl">cloud_off</span>
                <p class="mt-2">Error de conexión</p>
            </div>
        `;
    }
}

// Renderizar opciones de precios
function renderPriceOptions(precios) {
    const container = document.getElementById('priceOptions');
    container.innerHTML = '';
    
    tiposPrecios.forEach(tipo => {
        const precioRaw = precios[tipo.id] || 0;
        const precio = parseFloat(precioRaw);
        const isDefault = tipo.es_default == 1;
        
        const priceCard = document.createElement('div');
        priceCard.className = `relative p-4 border-2 rounded-lg transition-all ${
            isDefault ? 'border-primary/30 bg-primary/5' : 'border-gray-200 dark:border-gray-700'
        }`;
        priceCard.id = `price-card-${tipo.id}`;
        
        priceCard.innerHTML = `
            ${isDefault ? '<div class="absolute top-2 right-2"><span class="text-xs bg-primary text-white px-2 py-1 rounded-full">Por defecto</span></div>' : ''}
            <div class="mb-2">
                <h5 class="font-bold text-gray-900 dark:text-white text-sm">${tipo.nombre}</h5>
                ${tipo.descripcion ? `<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">${tipo.descripcion}</p>` : ''}
            </div>
            
            <!-- Vista normal del precio -->
            <div id="price-view-${tipo.id}" class="price-view">
                <div class="text-2xl font-black text-primary">
                    L <span id="price-value-${tipo.id}">${precio.toFixed(2)}</span>
                </div>
                <div class="mt-3 flex gap-2">
                    <button onclick="seleccionarPrecio(${tipo.id}, '${tipo.nombre}', ${precio})" class="flex-1 text-xs bg-primary text-white px-3 py-2 rounded-lg hover:bg-primary/90 transition-colors">
                        Seleccionar
                    </button>
                    <button onclick="editarPrecio(${tipo.id}, ${precio})" class="text-xs text-gray-500 dark:text-gray-400 hover:text-primary px-2 py-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" title="Editar precio">
                        <span class="material-symbols-outlined text-base">edit</span>
                    </button>
                </div>
            </div>
            
            <!-- Vista de edición del precio -->
            <div id="price-edit-${tipo.id}" class="price-edit hidden">
                <div class="mb-3">
                    <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">Nuevo precio:</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">L</span>
                        <input type="number" id="price-input-${tipo.id}" step="0.01" min="0" value="${precio.toFixed(2)}" 
                               class="w-full pl-8 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="guardarPrecioEditado(${tipo.id})" class="flex-1 text-xs bg-green-600 text-white px-3 py-2 rounded-lg hover:bg-green-700 transition-colors">
                        Guardar
                    </button>
                    <button onclick="cancelarEdicionPrecio(${tipo.id}, ${precio})" class="flex-1 text-xs bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 px-3 py-2 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        Cancelar
                    </button>
                </div>
            </div>
        `;
        
        container.appendChild(priceCard);
    });
    
    if (tiposPrecios.length === 0) {
        container.innerHTML = `
            <div class="col-span-2 text-center py-8 text-gray-400">
                <span class="material-symbols-outlined text-4xl">info</span>
                <p class="mt-2">No hay tipos de precios configurados</p>
            </div>
        `;
    }
}

// Función para editar precio
function editarPrecio(tipoPrecioId, precioActual) {
    // Ocultar vista normal y mostrar vista de edición
    document.getElementById(`price-view-${tipoPrecioId}`).classList.add('hidden');
    document.getElementById(`price-edit-${tipoPrecioId}`).classList.remove('hidden');
    
    // Enfocar el input
    setTimeout(() => {
        const input = document.getElementById(`price-input-${tipoPrecioId}`);
        input.focus();
        input.select();
    }, 100);
}

// Función para cancelar edición de precio
function cancelarEdicionPrecio(tipoPrecioId, precioOriginal) {
    // Restaurar valor original
    document.getElementById(`price-input-${tipoPrecioId}`).value = precioOriginal.toFixed(2);
    
    // Mostrar vista normal y ocultar vista de edición
    document.getElementById(`price-view-${tipoPrecioId}`).classList.remove('hidden');
    document.getElementById(`price-edit-${tipoPrecioId}`).classList.add('hidden');
}

// Función para guardar precio editado
async function guardarPrecioEditado(tipoPrecioId) {
    if (!priceModalProduct) return;
    
    const inputElement = document.getElementById(`price-input-${tipoPrecioId}`);
    const nuevoPrecio = parseFloat(inputElement.value);
    
    if (isNaN(nuevoPrecio) || nuevoPrecio < 0) {
        mostrarError('Por favor ingresa un precio válido');
        return;
    }
    
    try {
        // Preparar datos para guardar
        const preciosActualizados = {};
        
        // Obtener todos los precios actuales del producto
        tiposPrecios.forEach(tipo => {
            const valorActual = document.getElementById(`price-value-${tipo.id}`);
            if (valorActual) {
                preciosActualizados[tipo.id] = parseFloat(valorActual.textContent);
            }
        });
        
        // Actualizar el precio editado
        preciosActualizados[tipoPrecioId] = nuevoPrecio;
        
        // Enviar a la API
        const response = await fetch('api/precios_producto.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                producto_id: priceModalProduct.id,
                precios: preciosActualizados
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Actualizar la vista
            document.getElementById(`price-value-${tipoPrecioId}`).textContent = nuevoPrecio.toFixed(2);
            
            // Actualizar el onclick del botón seleccionar
            const tipoNombre = tiposPrecios.find(t => t.id == tipoPrecioId)?.nombre || 'Precio';
            const selectBtn = document.querySelector(`#price-view-${tipoPrecioId} button[onclick*="seleccionarPrecio"]`);
            if (selectBtn) {
                selectBtn.onclick = () => seleccionarPrecio(tipoPrecioId, tipoNombre, nuevoPrecio);
            }
            
            // Volver a vista normal
            document.getElementById(`price-view-${tipoPrecioId}`).classList.remove('hidden');
            document.getElementById(`price-edit-${tipoPrecioId}`).classList.add('hidden');
            
            mostrarExito('Precio actualizado correctamente');
        } else {
            mostrarError('Error al actualizar el precio: ' + (data.message || 'Error desconocido'));
        }
    } catch (error) {
        console.error('Error al guardar precio:', error);
        mostrarError('Error de conexión al guardar el precio');
    }
}

// Seleccionar precio y agregar al carrito
function seleccionarPrecio(tipoPrecioId, tipoPrecioNombre, precio) {
    if (!priceModalProduct) return;
    
    // Verificar si estamos cambiando el precio de un producto en el carrito
    if (priceModalProduct.isCartEdit) {
        // Actualizar el producto en el carrito
        const cartIndex = cart.findIndex(item => 
            item.id === priceModalProduct.id && item.tipoPrecioId === priceModalProduct.oldTipoPrecioId
        );
        
        if (cartIndex !== -1) {
            // Actualizar el precio y tipo de precio
            cart[cartIndex].tipoPrecioId = tipoPrecioId;
            cart[cartIndex].tipoPrecioNombre = tipoPrecioNombre;
            cart[cartIndex].precio = precio;
            
            // Actualizar la vista
            renderCart();
            updateTotals();
            
            mostrarExito(`Precio cambiado a ${tipoPrecioNombre}`);
        }
    } else {
        // Agregar producto al carrito con el precio seleccionado
        addToCart(priceModalProduct, tipoPrecioId, tipoPrecioNombre, precio);
    }
    
    // Cerrar modal
    cerrarModalPrecios();
    
    // Feedback visual
    playBeepSound();
}

// Cambiar precio de un producto que ya está en el carrito
function cambiarPrecioCarrito(productId, currentTipoPrecioId) {
    // Buscar el producto en el carrito
    const cartItem = cart.find(item => item.id === productId && item.tipoPrecioId === currentTipoPrecioId);
    
    if (!cartItem) {
        mostrarError('Producto no encontrado en el carrito');
        return;
    }
    
    // Crear objeto de producto para el modal
    const product = {
        id: cartItem.id,
        codigo: cartItem.codigo,
        nombre: cartItem.nombre,
        marca: cartItem.marca,
        precio: cartItem.precio,
        stock: cartItem.stock,
        isCartEdit: true, // Flag para indicar que estamos editando desde el carrito
        oldTipoPrecioId: currentTipoPrecioId // Guardar el ID del precio actual
    };
    
    // Abrir modal de precios
    abrirModalPrecios(product);
}

// Abrir modal para crear nuevo tipo de precio
function crearNuevoTipoPrecio() {
    if (!priceModalProduct) {
        mostrarError('Primero selecciona un producto');
        return;
    }
    
    // Limpiar campos
    document.getElementById('newPriceTypeName').value = '';
    document.getElementById('newPriceTypeDesc').value = '';
    document.getElementById('newPriceTypeValue').value = '';
    
    // Mostrar modal
    document.getElementById('createPriceTypeModal').classList.remove('hidden');
}

// Cerrar modal de crear precio
function cerrarModalCrearPrecio() {
    document.getElementById('createPriceTypeModal').classList.add('hidden');
}

// Guardar nuevo tipo de precio
async function guardarNuevoTipoPrecio() {
    const nombre = document.getElementById('newPriceTypeName').value.trim();
    const descripcion = document.getElementById('newPriceTypeDesc').value.trim();
    const precio = parseFloat(document.getElementById('newPriceTypeValue').value);
    
    // Validaciones
    if (!nombre) {
        mostrarError('El nombre del tipo de precio es obligatorio');
        return;
    }
    
    if (isNaN(precio) || precio < 0) {
        mostrarError('El precio debe ser un número válido mayor o igual a 0');
        return;
    }
    
    if (!priceModalProduct) {
        mostrarError('No hay producto seleccionado');
        return;
    }
    
    try {
        // Crear el tipo de precio
        const responseTipo = await fetch('api/tipos_precios.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'crear',
                nombre: nombre,
                descripcion: descripcion
            })
        });
        
        const dataTipo = await responseTipo.json();
        
        if (!dataTipo.success) {
            mostrarError('Error al crear tipo de precio: ' + (dataTipo.message || 'Error desconocido'));
            return;
        }
        
        const nuevoTipoId = dataTipo.tipo_id;
        
        // Asignar el precio al producto
        const responsePrecio = await fetch('api/precios_producto.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                producto_id: priceModalProduct.id,
                tipo_precio_id: nuevoTipoId,
                precio: precio
            })
        });
        
        const dataPrecio = await responsePrecio.json();
        
        if (!dataPrecio.success) {
            mostrarError('Tipo creado pero error al asignar precio: ' + (dataPrecio.message || 'Error desconocido'));
            return;
        }
        
        // Cerrar modal de creación
        cerrarModalCrearPrecio();
        
        // Recargar tipos de precios
        await cargarTiposPrecios();
        
        // Recargar precios del producto en el modal
        await cargarPreciosProducto(priceModalProduct.id);
        
        // Seleccionar automáticamente el nuevo precio
        seleccionarPrecio(nuevoTipoId, nombre, precio);
        
        mostrarExito(`Tipo de precio "${nombre}" creado exitosamente`);
        
    } catch (error) {
        console.error('Error al crear tipo de precio:', error);
        mostrarError('Error de conexión al crear el tipo de precio');
    }
}

// Cerrar modal de precios
function cerrarModalPrecios() {
    document.getElementById('priceModal').classList.add('hidden');
    priceModalProduct = null;
}


// Atajos de teclado
document.addEventListener('keydown', function(e) {
    // Tecla P para abrir modal de precios del último producto
    if (e.key === 'p' || e.key === 'P') {
        if (cart.length > 0 && !document.getElementById('priceModal').classList.contains('hidden')) {
            return; // Modal ya está abierto
        }
        
        // Si hay productos en el carrito, abrir modal para el último
        if (cart.length > 0) {
            const lastItem = cart[cart.length - 1];
            const product = {
                id: lastItem.id,
                codigo: lastItem.codigo,
                nombre: lastItem.nombre,
                marca: lastItem.marca,
                precio: lastItem.precio,
                stock: lastItem.stock
            };
            abrirModalPrecios(product);
            e.preventDefault();
        }
    }
    
    // Tecla ESC para cerrar modal de precios
    if (e.key === 'Escape') {
        if (!document.getElementById('priceModal').classList.contains('hidden')) {
            cerrarModalPrecios();
            e.preventDefault();
        }
    }
});

// Cargar tipos de precios al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarTiposPrecios();
});

</script>
<?php include 'christmas_effects.php'; ?>
<?php include 'modal_sistema.php'; ?>
</body>
</html>