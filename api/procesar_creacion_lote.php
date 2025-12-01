<?php
session_start();
header('Content-Type: application/json');

// Habilitar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar en pantalla, solo en logs
ini_set('log_errors', 1);

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión: ' . $conexion->connect_error]);
    exit;
}

$conexion->set_charset("utf8mb4");

// Obtener usuario de la sesión
$usuario_creador = $_SESSION['usuario'] ?? 'Sistema';

// Recibir datos
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Error al decodificar JSON: ' . json_last_error_msg()]);
    exit;
}

$productos = $input['productos'] ?? [];

// Log para debugging
error_log("Productos recibidos: " . count($productos));
error_log("Datos recibidos: " . json_encode($input));

if (empty($productos)) {
    echo json_encode(['success' => false, 'message' => 'No se recibieron productos', 'errores' => []]);
    exit;
}

$exitosos = 0;
$errores = [];

foreach ($productos as $i => $producto) {
    try {
        // Generar código si está vacío o es AUTO
        if (!isset($producto['codigo']) || $producto['codigo'] === '' || $producto['codigo'] === 'AUTO') {
            $producto['codigo'] = generarCodigoUnico($conexion);
        }
        
        // Verificar si el código ya existe
        $check = $conexion->prepare("SELECT Id FROM creacion_de_productos WHERE CodigoProducto = ?");
        if (!$check) {
            $errores[] = "Producto #{$i}: Error al preparar consulta de verificación: " . $conexion->error;
            continue;
        }
        
        $check->bind_param("s", $producto['codigo']);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $errores[] = "Producto #{$i} ({$producto['nombre']}): El código {$producto['codigo']} ya existe";
            $check->close();
            continue;
        }
        $check->close();
        
        // Preparar datos con valores por defecto
        $nombre = $producto['nombre'] ?? '';
        if (empty($nombre)) {
            $errores[] = "Producto #{$i}: El nombre es requerido";
            continue;
        }
        
        $descripcionCorta = $producto['descripcionCorta'] ?? substr($nombre, 0, 100);
        $marca = $producto['marca'] ?? '';
        $descripcion = $producto['descripcion'] ?? '';
        $tipoEmpaque = $producto['tipoEmpaque'] ?? 'Unidad';
        $unidadesPorEmpaque = isset($producto['unidadesPorEmpaque']) ? intval($producto['unidadesPorEmpaque']) : 1;
        $costoUnidad = isset($producto['costoUnidad']) ? floatval($producto['costoUnidad']) : 0;
        $costoEmpaque = isset($producto['costoEmpaque']) ? floatval($producto['costoEmpaque']) : ($costoUnidad * $unidadesPorEmpaque);
        $precioUnidad = isset($producto['precioUnidad']) ? floatval($producto['precioUnidad']) : 0;
        $precioEmpaque = isset($producto['precioEmpaque']) ? floatval($producto['precioEmpaque']) : ($precioUnidad * $unidadesPorEmpaque);
        $margen = isset($producto['margen']) ? floatval($producto['margen']) : 0;
        $proveedor = $producto['proveedor'] ?? '';
        $direccionProveedor = $producto['direccionProveedor'] ?? '';
        $contactoProveedor = $producto['contactoProveedor'] ?? '';
        $fotoProducto = '';
        $idNegocio = 1;
        $idSucursal = 1;
        
        // Sistema de Packaging Multinivel
        $tieneSubContenido = isset($producto['tieneSubContenido']) && $producto['tieneSubContenido'] ? 1 : 0;
        $contenido = isset($producto['contenido']) ? intval($producto['contenido']) : 0;
        $subContenido = isset($producto['subContenido']) ? intval($producto['subContenido']) : 0;
        
        // Calcular unidades totales y formato de presentación
        if ($tieneSubContenido && $contenido > 0 && $subContenido > 0) {
            $unidadesTotales = $contenido * $subContenido;
            $formatoPresentacion = "1x{$contenido}x{$subContenido}";
        } else {
            $unidadesTotales = $unidadesPorEmpaque > 0 ? $unidadesPorEmpaque : 1;
            $formatoPresentacion = "1x{$unidadesTotales}";
        }
        
        // Validar datos mínimos
        if ($costoEmpaque <= 0) {
            $errores[] = "Producto #{$i} ({$nombre}): El costo por empaque debe ser mayor a 0";
            continue;
        }
        
        if ($precioUnidad <= 0) {
            $errores[] = "Producto #{$i} ({$nombre}): El precio unitario debe ser mayor a 0";
            continue;
        }
        
        // Insertar producto
        $stmt = $conexion->prepare("
            INSERT INTO creacion_de_productos 
            (NombreProducto, TipoEmpaque, UnidadesPorEmpaque, DescripcionCorta, 
             CodigoProducto, Marca, Descripcion, CostoPorEmpaque, CostoPorUnidad, 
             MargenSugerido, PrecioSugeridoEmpaque, PrecioSugeridoUnidad, 
             FotoProducto, Proveedor, DireccionProveedor, ContactoProveedor,
             TieneSubContenido, Contenido, SubContenido, UnidadesTotales, FormatoPresentacion,
             id_negocio, id_sucursal, Creado_Por, Fecha_Creacion)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if (!$stmt) {
            $errores[] = "Producto #{$i} ({$nombre}): Error al preparar inserción: " . $conexion->error;
            continue;
        }
        
        $stmt->bind_param("ssissssdddddssssiiiisiis", 
            $nombre,                    // NombreProducto - s
            $tipoEmpaque,               // TipoEmpaque - s
            $unidadesPorEmpaque,        // UnidadesPorEmpaque - i
            $descripcionCorta,          // DescripcionCorta - s
            $producto['codigo'],        // CodigoProducto - s
            $marca,                     // Marca - s
            $descripcion,               // Descripcion - s
            $costoEmpaque,              // CostoPorEmpaque - d
            $costoUnidad,               // CostoPorUnidad - d
            $margen,                    // MargenSugerido - d
            $precioEmpaque,             // PrecioSugeridoEmpaque - d
            $precioUnidad,              // PrecioSugeridoUnidad - d
            $fotoProducto,              // FotoProducto - s
            $proveedor,                 // Proveedor - s
            $direccionProveedor,        // DireccionProveedor - s
            $contactoProveedor,         // ContactoProveedor - s
            $tieneSubContenido,         // TieneSubContenido - i
            $contenido,                 // Contenido - i
            $subContenido,              // SubContenido - i
            $unidadesTotales,           // UnidadesTotales - i
            $formatoPresentacion,       // FormatoPresentacion - s
            $idNegocio,                 // id_negocio - i
            $idSucursal,                // id_sucursal - i
            $usuario_creador            // Creado_Por - s
        );
        
        if ($stmt->execute()) {
            $exitosos++;
        } else {
            $errores[] = "Producto #{$i} ({$nombre}): Error al insertar: " . $stmt->error;
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $errores[] = "Producto #{$i}: Excepción: " . $e->getMessage();
    }
}

$conexion->close();

// Considerar éxito si al menos un producto se creó
$success = $exitosos > 0;

echo json_encode([
    'success' => $success,
    'exitosos' => $exitosos,
    'total' => count($productos),
    'errores' => $errores,
    'message' => $success 
        ? "Se crearon $exitosos de " . count($productos) . " productos" 
        : "No se pudo crear ningún producto. Revisa los errores."
]);

// Función para generar código único
function generarCodigoUnico($conexion) {
    $intentos = 0;
    do {
        $codigo = 'P' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $check = $conexion->query("SELECT Id FROM creacion_de_productos WHERE CodigoProducto = '$codigo'");
        $intentos++;
        
        if ($intentos > 100) {
            // Evitar loop infinito
            $codigo = 'P' . uniqid();
            break;
        }
    } while ($check && $check->num_rows > 0);
    
    return $codigo;
}
?>
