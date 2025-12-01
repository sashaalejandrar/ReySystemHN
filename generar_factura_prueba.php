<?php
session_start();
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

// Incluir TCPDF
require_once('vendor/tecnickcom/tcpdf/tcpdf.php');

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);

// Obtener proveedores
$proveedores_query = $conexion->query("SELECT * FROM proveedores WHERE Estado = 'Activo' ORDER BY Nombre");
$proveedores = [];
while($prov = $proveedores_query->fetch_assoc()) {
    $proveedores[] = $prov;
}

// Procesar generación de factura PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar'])) {
    $proveedor_id = intval($_POST['proveedor_id']);
    $num_productos = intval($_POST['num_productos']);
    
    // Obtener datos del proveedor
    $prov_query = $conexion->query("SELECT * FROM proveedores WHERE Id = $proveedor_id");
    $proveedor = $prov_query->fetch_assoc();
    
    // Obtener productos aleatorios del stock
    $productos_query = $conexion->query("SELECT * FROM stock WHERE Stock > 0 ORDER BY RAND() LIMIT $num_productos");
    $productos = [];
    $subtotal = 0;
    
    while($prod = $productos_query->fetch_assoc()) {
        $cantidad = rand(1, 10);
        $precio_unitario = floatval($prod['Precio_Unitario']);
        $total_producto = $cantidad * $precio_unitario;
        $subtotal += $total_producto;
        
        $productos[] = [
            'codigo' => $prod['Codigo_Producto'],
            'nombre' => $prod['Nombre_Producto'],
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'total' => $total_producto
        ];
    }
    
    $iva = $subtotal * 0.15; // 15% IVA
    $total = $subtotal + $iva;
    
    // Generar número de factura
    $num_factura = 'FAC-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    $fecha_factura = date('d/m/Y');
    
    // Crear PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Configurar información del documento
    $pdf->SetCreator('Rey System APP');
    $pdf->SetAuthor('Rey System');
    $pdf->SetTitle('Factura de Compra - ' . $num_factura);
    $pdf->SetSubject('Factura de Prueba');
    
    // Quitar header y footer por defecto
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Configurar márgenes
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Agregar página
    $pdf->AddPage();
    
    // Configurar fuente
    $pdf->SetFont('helvetica', '', 10);
    
    // HEADER DE LA FACTURA
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 12, htmlspecialchars($proveedor['Nombre'], ENT_QUOTES, 'UTF-8'), 0, 1, 'C', true);
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'RTN: ' . htmlspecialchars($proveedor['RTN'], ENT_QUOTES, 'UTF-8'), 0, 1, 'C');
    $pdf->Cell(0, 5, 'Dirección: ' . htmlspecialchars($proveedor['Direccion'], ENT_QUOTES, 'UTF-8'), 0, 1, 'C');
    $pdf->Cell(0, 5, 'Teléfono: ' . htmlspecialchars($proveedor['Celular'], ENT_QUOTES, 'UTF-8'), 0, 1, 'C');
    
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, 'FACTURA DE COMPRA', 0, 1, 'C');
    
    $pdf->Ln(5);
    
    // INFORMACIÓN DE LA FACTURA
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(240, 240, 240);
    
    $pdf->Cell(95, 7, 'FACTURA No: ' . $num_factura, 1, 0, 'L', true);
    $pdf->Cell(95, 7, 'FECHA: ' . $fecha_factura, 1, 1, 'L', true);
    
    $pdf->Ln(3);
    
    // DATOS DEL CLIENTE
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'CLIENTE:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, 'Tiendas Rey - La Flecha', 0, 1, 'L');
    $pdf->Cell(0, 5, 'RTN: 0801-1990-12345', 0, 1, 'L');
    $pdf->Cell(0, 5, 'San Pedro Sula, Cortés', 0, 1, 'L');
    
    $pdf->Ln(5);
    
    // TABLA DE PRODUCTOS
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetTextColor(255, 255, 255);
    
    // Encabezados de tabla
    $pdf->Cell(30, 7, 'CÓDIGO', 1, 0, 'C', true);
    $pdf->Cell(75, 7, 'DESCRIPCIÓN', 1, 0, 'C', true);
    $pdf->Cell(20, 7, 'CANT.', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'P. UNIT.', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'TOTAL', 1, 1, 'C', true);
    
    // Productos
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetFillColor(255, 255, 255);
    
    $fill = false;
    foreach($productos as $prod) {
        $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
        
        $pdf->Cell(30, 6, htmlspecialchars($prod['codigo'], ENT_QUOTES, 'UTF-8'), 1, 0, 'L', true);
        $pdf->Cell(75, 6, htmlspecialchars(substr($prod['nombre'], 0, 45), ENT_QUOTES, 'UTF-8'), 1, 0, 'L', true);
        $pdf->Cell(20, 6, $prod['cantidad'], 1, 0, 'C', true);
        $pdf->Cell(30, 6, 'L ' . number_format($prod['precio_unitario'], 2), 1, 0, 'R', true);
        $pdf->Cell(35, 6, 'L ' . number_format($prod['total'], 2), 1, 1, 'R', true);
        
        $fill = !$fill;
    }
    
    $pdf->Ln(3);
    
    // TOTALES
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(125, 7, '', 0, 0);
    $pdf->Cell(30, 7, 'SUBTOTAL:', 1, 0, 'L', true);
    $pdf->Cell(35, 7, 'L ' . number_format($subtotal, 2), 1, 1, 'R', true);
    
    $pdf->Cell(125, 7, '', 0, 0);
    $pdf->Cell(30, 7, 'IVA (15%):', 1, 0, 'L', true);
    $pdf->Cell(35, 7, 'L ' . number_format($iva, 2), 1, 1, 'R', true);
    
    $pdf->SetFillColor(0, 0, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(125, 8, '', 0, 0);
    $pdf->Cell(30, 8, 'TOTAL:', 1, 0, 'L', true);
    $pdf->Cell(35, 8, 'L ' . number_format($total, 2), 1, 1, 'R', true);
    
    $pdf->Ln(10);
    
    // FOOTER
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'Gracias por su compra', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Esta es una factura de prueba generada automáticamente', 0, 1, 'C');
    
    // Crear directorio si no existe
    if (!file_exists('facturas_prueba')) {
        mkdir('facturas_prueba', 0777, true);
    }
    
    // Guardar PDF
    $nombre_archivo = 'facturas_prueba/factura_' . $num_factura . '.pdf';
    $pdf->Output(__DIR__ . '/' . $nombre_archivo, 'F');
    
    // Redirigir para descargar
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($nombre_archivo) . '"');
    header('Content-Length: ' . filesize($nombre_archivo));
    readfile($nombre_archivo);
    exit;
}
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Generar Factura de Prueba - Rey System APP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
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
            },
        },
    }
</script>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">

<!-- Page Heading -->
<div class="flex flex-wrap justify-between gap-4 mb-8">
    <div class="flex flex-col gap-2">
        <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">
            🧾 Generador de Facturas de Prueba
        </h1>
        <p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">
            Genera facturas PDF profesionales con productos del stock para probar el sistema de escaneo OCR
        </p>
    </div>
</div>

<!-- Formulario de Generación -->
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-6">
    <form method="POST" action="">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Seleccionar Proveedor -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <span class="material-symbols-outlined text-sm align-middle">store</span>
                    Proveedor
                </label>
                <select name="proveedor_id" required class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <option value="">Seleccione un proveedor...</option>
                    <?php foreach($proveedores as $prov): ?>
                    <option value="<?= $prov['Id'] ?>"><?= htmlspecialchars($prov['Nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Número de Productos -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    <span class="material-symbols-outlined text-sm align-middle">inventory_2</span>
                    Cantidad de Productos
                </label>
                <input type="number" name="num_productos" min="1" max="20" value="5" required class="w-full px-4 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Entre 1 y 20 productos aleatorios del stock</p>
            </div>
        </div>
        
        <div class="flex gap-3">
            <button type="submit" name="generar" class="flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                <span class="material-symbols-outlined">picture_as_pdf</span>
                Generar Factura PDF
            </button>
            <a href="escanear_factura.php" class="flex items-center gap-2 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                <span class="material-symbols-outlined">photo_camera</span>
                Ir a Escanear Factura
            </a>
        </div>
    </form>
</div>

<!-- Instrucciones -->
<div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-200 dark:border-blue-800 p-6 mb-6">
    <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-3 flex items-center gap-2">
        <span class="material-symbols-outlined">info</span>
        Instrucciones de Uso
    </h3>
    <ol class="list-decimal list-inside space-y-2 text-blue-800 dark:text-blue-200">
        <li>Selecciona un proveedor de la lista</li>
        <li>Elige cuántos productos quieres incluir en la factura (se seleccionarán aleatoriamente del stock)</li>
        <li>Haz clic en "Generar Factura PDF"</li>
        <li>El PDF se descargará automáticamente a tu computadora</li>
        <li>Imprime el PDF o tómale una foto con tu celular</li>
        <li>Usa esa imagen en el sistema de escaneo de facturas para probar el OCR</li>
    </ol>
</div>

<!-- Facturas Generadas -->
<?php
$facturas_dir = 'facturas_prueba';
if (file_exists($facturas_dir)) {
    $facturas = array_diff(scandir($facturas_dir), array('.', '..'));
    if (count($facturas) > 0):
?>
<div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
        <span class="material-symbols-outlined">folder</span>
        Facturas Generadas Recientemente
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php 
        $facturas = array_reverse($facturas);
        $count = 0;
        foreach($facturas as $factura): 
            if ($count >= 6) break;
            $count++;
        ?>
        <a href="<?= $facturas_dir . '/' . $factura ?>" target="_blank" class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors border border-gray-200 dark:border-gray-600">
            <span class="material-symbols-outlined text-red-600 text-3xl">picture_as_pdf</span>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white truncate"><?= htmlspecialchars($factura) ?></p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    <?= date('d/m/Y H:i', filemtime($facturas_dir . '/' . $factura)) ?>
                </p>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php 
    endif;
}
?>

</div>
</main>
</div>
</div>
</body>
</html>
