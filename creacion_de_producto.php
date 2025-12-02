<?php
// Activar reporte de errores para debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir archivo de configuración
require_once 'config.php';

// Inicializar variables
$mensaje = '';
$tipo_mensaje = '';

// Obtener conexión
$conn = new mysqli("localhost", "root", "", "tiendasrey");
date_default_timezone_set('America/Tegucigalpa');

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión']);
    exit;
}

// Función para sanitizar
function sanitize($conn, $value) {
    return htmlspecialchars(trim($conn->real_escape_string($value)));
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitizar y obtener datos del formulario
        $nombreProducto = sanitize($conn, $_POST['nombre_producto'] ?? '');
        $descripcionCorta = sanitize($conn, $_POST['descripcion_corta'] ?? '');
        $tipoEmpaque = sanitize($conn, $_POST['tipo_empaque'] ?? '');
        $unidadesPorEmpaque = (int)($_POST['unidades_por_empaque'] ?? 0);
        $codigoProducto = sanitize($conn, $_POST['codigo_producto'] ?? '');
        // Sistema de Contenido/SubContenido
        $tieneSubContenido = isset($_POST['tiene_subcontenido']) ? 1 : 0;
        $contenido = (int)($_POST['contenido'] ?? 0);
        $subContenido = (int)($_POST['sub_contenido'] ?? 0);
        
        // Calcular unidades totales según el modo
        if ($tieneSubContenido && $contenido > 0 && $subContenido > 0) {
            $unidadesTotales = $contenido * $subContenido;
            $formatoPresentacion = "1x{$contenido}x{$subContenido}";
        } else {
            $unidadesTotales = $unidadesPorEmpaque > 0 ? $unidadesPorEmpaque : 1;
            $formatoPresentacion = "1x{$unidadesTotales}";
        }
        
        $marca = sanitize($conn, $_POST['marca'] ?? '');
        $descripcion = sanitize($conn, $_POST['descripcion'] ?? '');
        
        // CÁLCULOS DE COSTOS Y PRECIOS BASADOS EN UNIDADES TOTALES
        $costoPorEmpaque = (float)($_POST['costo_por_empaque'] ?? 0);
        
        // Calcular costo por unidad usando unidades totales
        $costoPorUnidad = 0;
        if ($costoPorEmpaque > 0 && $unidadesTotales > 0) {
            $costoPorUnidad = $costoPorEmpaque / $unidadesTotales;
        }
        
        $margenSugerido = (float)($_POST['margen_sugerido'] ?? 0);
        
        // Calcular precios sugeridos
        $precioSugeridoUnidad = 0;
        $precioSugeridoEmpaque = 0;
        
        if ($costoPorUnidad > 0 && $margenSugerido > 0) {
            $precioSugeridoUnidad = $costoPorUnidad * (1 + $margenSugerido / 100);
            $precioSugeridoEmpaque = $precioSugeridoUnidad * $unidadesTotales;
        }
        $proveedor = sanitize($conn, $_POST['proveedor'] ?? '');
        $direccionProveedor = sanitize($conn, $_POST['direccion_proveedor'] ?? '');
        $contactoProveedor = sanitize($conn, $_POST['contacto_proveedor'] ?? '');
        $fotoProducto = '';

        // Manejo de subida de foto
        if (!empty($_FILES['foto_producto']['name'])) {
            $targetDir = "uploads/Productos/";
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true); // crea carpeta si no existe
            }
            $fileName = time() . "_" . basename($_FILES["foto_producto"]["name"]);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["foto_producto"]["tmp_name"], $targetFile)) {
                $fotoProducto = $targetFile;
            } else {
                $mensaje = "Error al subir la foto.";
                $tipo_mensaje = "error";
            }
        }

        // Validar datos requeridos
        if (empty($nombreProducto)) {
            $mensaje = 'Por favor complete el Nombre del Producto (campo requerido).';
            $tipo_mensaje = 'error';
        } else {
            // Preparar la consulta SQL
            $sql = "INSERT INTO creacion_de_productos (
                NombreProducto,
                DescripcionCorta,
                TipoEmpaque, 
                UnidadesPorEmpaque, 
                CodigoProducto, 
                Marca, 
                Descripcion, 
                CostoPorEmpaque, 
                CostoPorUnidad, 
                MargenSugerido, 
                PrecioSugeridoEmpaque, 
                PrecioSugeridoUnidad,
                FotoProducto,
                Proveedor,
                DireccionProveedor,
                ContactoProveedor,
                TieneSubContenido,
                Contenido,
                SubContenido,
                UnidadesTotales,
                FormatoPresentacion
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                throw new Exception('Error en prepare: ' . $conn->error);
            }
            
            $stmt->bind_param(
                "sssisssssdddssssiiiis",
                $nombreProducto,
                $descripcionCorta,
                $tipoEmpaque,
                $unidadesPorEmpaque,
                $codigoProducto,
                $marca,
                $descripcion,
                $costoPorEmpaque,
                $costoPorUnidad,
                $margenSugerido,
                $precioSugeridoEmpaque,
                $precioSugeridoUnidad,
                $fotoProducto,
                $proveedor,
                $direccionProveedor,
                $contactoProveedor,
                $tieneSubContenido,
                $contenido,
                $subContenido,
                $unidadesTotales,
                $formatoPresentacion
            );
            
            
            if ($stmt->execute()) {
                $producto_id = $conn->insert_id;
                
                // Crear o actualizar proveedor si se proporcionó información
                if (!empty($proveedor)) {
                    // Verificar si el proveedor ya existe
                    $stmt_check = $conn->prepare("SELECT Id FROM proveedores WHERE Nombre = ?");
                    $stmt_check->bind_param("s", $proveedor);
                    $stmt_check->execute();
                    $result_check = $stmt_check->get_result();
                    
                    if ($result_check->num_rows > 0) {
                        // Actualizar proveedor existente
                        $proveedor_row = $result_check->fetch_assoc();
                        $proveedor_id = $proveedor_row['Id'];
                        
                        $rtn_proveedor = 'NA';
                        $estado_proveedor = 'Activo';
                        $stmt_update = $conn->prepare("UPDATE proveedores SET Direccion = ?, Contacto = ?, Celular = ?, RTN = ?, Estado = ? WHERE Id = ?");
                        $stmt_update->bind_param("sssssi", $direccionProveedor, $contactoProveedor, $contactoProveedor, $rtn_proveedor, $estado_proveedor, $proveedor_id);
                        $stmt_update->execute();
                        $stmt_update->close();
                    } else {
                        // Crear nuevo proveedor
                        $rtn_proveedor = 'NA';
                        $estado_proveedor = 'Activo';
                        $stmt_insert = $conn->prepare("INSERT INTO proveedores (Nombre, Direccion, Contacto, Celular, RTN, Estado) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt_insert->bind_param("ssssss", $proveedor, $direccionProveedor, $contactoProveedor, $contactoProveedor, $rtn_proveedor, $estado_proveedor);
                        $stmt_insert->execute();
                        $stmt_insert->close();
                    }
                    $stmt_check->close();
                }
                
                $mensaje = '¡Producto creado exitosamente! ID: ' . $producto_id;
                if (!empty($proveedor)) {
                    $mensaje .= ' | Proveedor registrado/actualizado.';
                }
                $tipo_mensaje = 'success';
            } else {
                throw new Exception('Error al ejecutar: ' . $stmt->error);
            }
            
            $stmt->close();
        }
    } catch (Exception $e) {
        $mensaje = 'Error: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}
// Obtener datos del usuario
$resultado = $conn->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
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
// --- FIN DE LA LÓGICA DE PERMISOS ---
// Cerrar conexión
$conn->close();
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Crear Nuevo Producto</title>
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
                    },
                    borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
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
        
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            animation: slideIn 0.3s ease-out;
        }
        
        .alert-success {
            background-color: #10b981;
            color: white;
        }
        
        .alert-error {
            background-color: #ef4444;
            color: white;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .file-upload {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #1152d4, #3b82f6);
  color: white;
  font-weight: 600;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.3s ease;
}

.file-upload:hover {
  background: linear-gradient(135deg, #0d3aa0, #2563eb);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(17, 82, 212, 0.4);
}

.file-upload input[type="file"] {
  position: absolute;
  left: 0;
  top: 0;
  opacity: 0;
  cursor: pointer;
  height: 100%;
  width: 100%;
}
.dropzone {
  border: 2px dashed #1152d4;
  border-radius: 0.75rem;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.dropzone:hover {
  background-color: rgba(17, 82, 212, 0.05);
}

.dropzone.dragover {
  background-color: rgba(17, 82, 212, 0.15);
  border-color: #2563eb;
}

    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>
    
    <main class="flex-1 flex flex-col">
        <div class="flex-grow p-6 lg:p-10">
            <header class="mb-8">
                <h1 class="text-slate-800 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">Crear Nuevo Producto</h1>
            </header>
            
            <?php if (!empty($mensaje)): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
 <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" enctype="multipart/form-data">
                <div class="grid grid-cols-3 gap-8">
                    <div class="col-span-3 lg:col-span-2 flex flex-col gap-6">
                        <div class="bg-white dark:bg-[#192233] p-6 rounded-xl shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Información del Producto</h2>
                            <div class="flex flex-col gap-4">
                                <label class="flex flex-col w-full">
                                    <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Nombre del Producto *</p>
                                    <input name="nombre_producto" required class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Ej: Camiseta de algodón"/>
                                </label>
                                
                                <label class="flex flex-col w-full">
                                    <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Descripción Corta</p>
                                    <input name="descripcion_corta" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Descripción breve del producto"/>
                                </label>
                                
                                <label class="flex flex-col w-full">
                                    <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Marca</p>
                                    <input name="marca" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Ej: Nike, Samsung, etc."/>
                                </label>
                                
                                <label class="flex flex-col w-full">
                                    <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Descripción Extensa</p>
                                    <textarea name="descripcion" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] min-h-36 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Describe los detalles, materiales, y características del producto."></textarea>
                                </label>
                                
                                <label class="flex flex-col w-full">
                                    <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Código de Producto</p>
                                    <input name="codigo_producto" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Ej: PROD-001"/>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bg-white dark:bg-[#192233] p-6 rounded-xl shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Empaque y Unidades</h2>
                            <div class="flex flex-col gap-4">
                                <!-- Toggle para SubContenido -->
                                <div class="flex items-center justify-between p-4 bg-slate-100 dark:bg-[#111722] rounded-lg border border-slate-300 dark:border-[#324467]">
                                    <div class="flex flex-col">
                                        <p class="text-slate-800 dark:text-white text-base font-semibold">¿Tiene SubContenido?</p>
                                        <p class="text-slate-500 dark:text-[#92a4c9] text-sm">Activa si el producto viene en presentación multinivel (ej: 1x12x12)</p>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="toggleSubContenido" name="tiene_subcontenido" class="sr-only peer" onchange="togglePackagingMode()">
                                        <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/30 dark:peer-focus:ring-primary/50 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                                    </label>
                                </div>
                                
                                <!-- Tipo de Empaque -->
                                <label class="flex flex-col w-full">
                                    <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Tipo de Empaque</p>
                                    <input 
                                        name="tipo_empaque" 
                                        id="tipo_empaque"
                                        list="tipos_empaque_list"
                                        onchange="autoConfigurarPackaging(this.value)"
                                        class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" 
                                        placeholder="Selecciona o escribe un tipo de empaque"/>
                                    <datalist id="tipos_empaque_list">
                                        <option value="Unidad">Unidad Individual</option>
                                        <option value="Six Pack">Six Pack (6 unidades)</option>
                                        <option value="Caja">Caja</option>
                                        <option value="Paquete">Paquete</option>
                                        <option value="Display">Display</option>
                                        <option value="Pallet">Pallet</option>
                                        <option value="Bolsa">Bolsa</option>
                                        <option value="Fardo">Fardo</option>
                                        <option value="Cartón">Cartón</option>
                                        <option value="Bulto">Bulto</option>
                                        <option value="Pack">Pack</option>
                                        <option value="Bandeja">Bandeja</option>
                                    </datalist>
                                </label>
                                
                                <!-- Modo Simple (sin subcontenido) -->
                                <div id="modoSimple" class="flex flex-col gap-4">
                                    <label class="flex flex-col w-full">
                                        <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Unidades por Empaque</p>
                                        <input name="unidades_por_empaque" id="unidadesPorEmpaque" type="number" min="0" step="1" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Ej: 24" onchange="calcularUnidadesTotales()"/>
                                    </label>
                                </div>
                                
                                <!-- Modo SubContenido (multinivel) -->
                                <div id="modoSubContenido" class="hidden flex flex-col gap-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <label class="flex flex-col">
                                            <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Contenido (Paquetes)</p>
                                            <input name="contenido" id="contenido" type="number" min="0" step="1" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Ej: 12" onchange="calcularUnidadesTotales()"/>
                                            <span class="text-xs text-slate-500 dark:text-[#92a4c9] mt-1">Cantidad de paquetes intermedios</span>
                                        </label>
                                        
                                        <label class="flex flex-col">
                                            <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">SubContenido (Unidades)</p>
                                            <input name="sub_contenido" id="subContenido" type="number" min="0" step="1" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Ej: 12" onchange="calcularUnidadesTotales()"/>
                                            <span class="text-xs text-slate-500 dark:text-[#92a4c9] mt-1">Unidades por paquete</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Indicador de Unidades Totales -->
                                <div id="indicadorUnidades" class="p-4 bg-gradient-to-r from-primary/10 to-blue-500/10 border-l-4 border-primary rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <span class="material-symbols-outlined text-primary text-3xl">inventory_2</span>
                                        <div>
                                            <p class="text-slate-600 dark:text-[#92a4c9] text-sm font-medium">Presentación</p>
                                            <p id="formatoPresentacion" class="text-slate-800 dark:text-white text-2xl font-bold">1x0</p>
                                            <p class="text-slate-600 dark:text-[#92a4c9] text-sm mt-1">
                                                Total: <span id="unidadesTotalesDisplay" class="font-semibold text-primary">0</span> unidades
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Ejemplos de presentaciones -->
                                <div class="p-3 bg-slate-50 dark:bg-[#111722] rounded-lg border border-slate-200 dark:border-[#324467]">
                                    <p class="text-xs font-semibold text-slate-700 dark:text-[#92a4c9] mb-2">📦 Ejemplos de presentaciones:</p>
                                    <div class="grid grid-cols-2 gap-2 text-xs text-slate-600 dark:text-[#92a4c9]">
                                        <div>• <strong>1x24</strong> = 24 unidades</div>
                                        <div>• <strong>1x12x12</strong> = 144 unidades</div>
                                        <div>• <strong>1x6x24</strong> = 144 unidades</div>
                                        <div>• <strong>1x20x6</strong> = 120 unidades</div>
                                        <div>• <strong>1x48x12</strong> = 576 unidades</div>
                                        <div>• <strong>1x100</strong> = 100 unidades</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white dark:bg-[#192233] p-6 rounded-xl shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Información del Proveedor</h2>
                            <div class="flex flex-col gap-4">
                                <label class="flex flex-col w-full">
                                    <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Nombre del Proveedor</p>
                                    <input name="proveedor" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Ej: Distribuidora XYZ S.A."/>
                                </label>
                                
                                <label class="flex flex-col w-full">
                                    <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Dirección del Proveedor</p>
                                    <textarea name="direccion_proveedor" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] min-h-24 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Dirección completa del proveedor"></textarea>
                                </label>
                                
                                <label class="flex flex-col w-full">
                                    <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Contacto del Proveedor</p>
                                    <input name="contacto_proveedor" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Teléfono o email del contacto"/>
                                </label>
                            </div>
                        </div>
                        
                        <div class="bg-white dark:bg-[#192233] p-6 rounded-xl shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Costos</h2>
                            <div class="flex flex-col gap-4">
                                <div class="flex flex-col sm:flex-row items-end gap-4">
                                    <label class="flex flex-col min-w-40 flex-1">
                                        <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Costo por Empaque</p>
                                        <input name="costo_por_empaque" type="number" min="0" step="0.01" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="0.00"/>
                                    </label>
                                    
                                    <label class="flex flex-col min-w-40 flex-1">
                                        <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Costo por Unidad</p>
                                        <input name="costo_por_unidad" type="number" min="0" step="0.01" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="0.00"/>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white dark:bg-[#192233] p-6 rounded-xl shadow-sm">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Precios Sugeridos</h2>
                            <div class="flex flex-col gap-4">
                                <label class="flex flex-col w-full">
                                    <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Margen Sugerido (%)</p>
                                    <input name="margen_sugerido" type="number" min="0" step="0.01" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="0.00"/>
                                </label>
                                
                                <div class="flex flex-col sm:flex-row items-end gap-4">
                                    <label class="flex flex-col min-w-40 flex-1">
                                        <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Precio Sugerido Empaque</p>
                                        <input name="precio_sugerido_empaque" type="number" min="0" step="0.01" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="0.00"/>
                                    </label>
                                    
                                    <label class="flex flex-col min-w-40 flex-1">
                                        <p class="text-slate-600 dark:text-white text-base font-medium leading-normal pb-2">Precio Sugerido Unidad</p>
                                        <input name="precio_sugerido_unidad" type="number" min="0" step="0.01" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="0.00"/>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-span-3 lg:col-span-1 flex flex-col">
                        <div class="bg-white dark:bg-[#192233] p-6 rounded-xl shadow-sm sticky top-10">
                            <h2 class="text-lg font-semibold text-slate-800 dark:text-white mb-4">Información Adicional</h2>
                            <div class="flex flex-col gap-4 text-slate-600 dark:text-[#92a4c9] text-sm">
                                <p>* Campos requeridos</p>
                                <p>Complete toda la información del producto para una mejor gestión del inventario.</p>
                                <div class="pt-4 border-t border-slate-200 dark:border-[#324467]">
                                    <p class="font-semibold text-slate-800 dark:text-white mb-2">Campos de la tabla:</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs">
                                        <li>NombreProducto *</li>
                                        <li>DescripcionCorta</li>
                                        <li>TipoEmpaque</li>
                                        <li>UnidadesPortEmpaque</li>
                                        <li>CodigoProducto</li>
                                        <li>Marca</li>
                                        <li>Descripcion</li>
                                        <li>CostoPortEmpaque</li>
                                        <li>CostoPorUnidad</li>
                                        <li>MargenSugerido</li>
                                        <li>PrecioSugeridoEmpaque</li>
                                        <li>PrecioSugeridoUnidad</li>
                                    </ul>
                                </div>
                            </div>
                            <BR></BR>
           <div id="dropzone" class="dropzone">
    <span class="material-symbols-outlined text-primary text-4xl mb-2">cloud_upload</span>
    <p class="text-slate-600 dark:text-white font-medium">
        Haz click o arrastra para cargar la foto del producto
    </p>
    <input type="file" name="foto_producto" accept="image/*" class="hidden" id="fileInput">
    <div id="preview" class="mt-4"></div>
</div>


                        </div>
                    </div>
                </div>
                
                <footer class="sticky bottom-0 bg-white/80 dark:bg-[#101622]/80 backdrop-blur-sm border-t border-slate-200 dark:border-[#192233] p-4 -mx-6 lg:-mx-10 mt-10">
                    <div class="flex justify-end gap-3 px-6 lg:px-10">
                        <button type="reset" class="px-6 py-3 rounded-lg text-slate-800 dark:text-white text-base font-semibold hover:bg-slate-100 dark:hover:bg-[#192233] transition-colors">Cancelar</button>
                        <button type="submit" class="bg-primary px-6 py-3 rounded-lg text-white text-base font-semibold hover:bg-primary/90 transition-colors">Guardar Producto</button>
                    </div>
                </footer>
            </form>
        </div>
    </main>
</div>

<script>
    // Auto-ocultar mensajes después de 5 segundos
    setTimeout(function() {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }
    }, 5000);
</script>
<script>
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const preview = document.getElementById('preview');

// Click en el área = abrir selector
dropzone.addEventListener('click', () => fileInput.click());

// Drag & Drop
dropzone.addEventListener('dragover', (e) => {
  e.preventDefault();
  dropzone.classList.add('dragover');
});

dropzone.addEventListener('dragleave', () => {
  dropzone.classList.remove('dragover');
});

dropzone.addEventListener('drop', (e) => {
  e.preventDefault();
  dropzone.classList.remove('dragover');
  const file = e.dataTransfer.files[0];
  if (file) {
    fileInput.files = e.dataTransfer.files;
    showPreview(file);
  }
});

// Preview instantáneo
fileInput.addEventListener('change', () => {
  const file = fileInput.files[0];
  if (file) showPreview(file);
});

function showPreview(file) {
  const reader = new FileReader();
  reader.onload = (e) => {
    preview.innerHTML = `
      <img src="${e.target.result}" 
           alt="Preview" 
           class="w-40 h-40 object-cover rounded-lg border border-slate-300 dark:border-[#324467] mx-auto"/>
    `;
  };
  reader.readAsDataURL(file);
}
</script>

<script>
// ========== SISTEMA DE PACKAGING MULTINIVEL ==========
function togglePackagingMode() {
    const toggle = document.getElementById('toggleSubContenido');
    const modoSimple = document.getElementById('modoSimple');
    const modoSubContenido = document.getElementById('modoSubContenido');
    
    if (toggle.checked) {
        // Activar modo SubContenido
        modoSimple.classList.add('hidden');
        modoSubContenido.classList.remove('hidden');
        
        // Limpiar campo de modo simple
        document.getElementById('unidadesPorEmpaque').value = '';
    } else {
        // Activar modo Simple
        modoSimple.classList.remove('hidden');
        modoSubContenido.classList.add('hidden');
        
        // Limpiar campos de modo subcontenido
        document.getElementById('contenido').value = '';
        document.getElementById('subContenido').value = '';
    }
    
    calcularUnidadesTotales();
}

function autoConfigurarPackaging(tipoEmpaque) {
    const tipo = tipoEmpaque.toLowerCase();
    const toggle = document.getElementById('toggleSubContenido');
    
    // Configuraciones predefinidas
    const configuraciones = {
        'six pack': { tieneSubContenido: false, unidadesPorEmpaque: 6 },
        'sixpack': { tieneSubContenido: false, unidadesPorEmpaque: 6 },
        'display': { tieneSubContenido: true, contenido: 12, subContenido: 12 },
        'pallet': { tieneSubContenido: true, contenido: 48, subContenido: 12 },
        'caja': { tieneSubContenido: false, unidadesPorEmpaque: 24 },
        'docena': { tieneSubContenido: false, unidadesPorEmpaque: 12 },
        'unidad': { tieneSubContenido: false, unidadesPorEmpaque: 1 }
    };
    
    const config = configuraciones[tipo];
    if (config) {
        // Configurar el toggle
        toggle.checked = config.tieneSubContenido;
        togglePackagingMode();
        
        if (config.tieneSubContenido) {
            // Modo SubContenido
            document.getElementById('contenido').value = config.contenido;
            document.getElementById('subContenido').value = config.subContenido;
        } else {
            // Modo Simple
            document.getElementById('unidadesPorEmpaque').value = config.unidadesPorEmpaque;
        }
        
        calcularUnidadesTotales();
        calcularPrecios();
    }
}

function calcularUnidadesTotales() {
    const toggle = document.getElementById('toggleSubContenido');
    let unidadesTotales = 0;
    let formato = '1x0';
    
    if (toggle.checked) {
        // Modo SubContenido
        const contenido = parseInt(document.getElementById('contenido').value) || 0;
        const subContenido = parseInt(document.getElementById('subContenido').value) || 0;
        
        if (contenido > 0 && subContenido > 0) {
            unidadesTotales = contenido * subContenido;
            formato = `1x${contenido}x${subContenido}`;
        }
    } else {
        // Modo Simple
        const unidadesPorEmpaque = parseInt(document.getElementById('unidadesPorEmpaque').value) || 0;
        
        if (unidadesPorEmpaque > 0) {
            unidadesTotales = unidadesPorEmpaque;
            formato = `1x${unidadesPorEmpaque}`;
        }
    }
    
    // Actualizar indicadores visuales
    document.getElementById('formatoPresentacion').textContent = formato;
    document.getElementById('unidadesTotalesDisplay').textContent = unidadesTotales;
    
    // Agregar animación al cambio
    const indicador = document.getElementById('indicadorUnidades');
    indicador.classList.add('scale-105');
    setTimeout(() => indicador.classList.remove('scale-105'), 200);
}

// Inicializar al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    calcularUnidadesTotales();
});
</script>

<script>
function calcularPrecios() {
    const toggle = document.getElementById('toggleSubContenido');
    const costoEmpaque = parseFloat(document.querySelector('[name="costo_por_empaque"]').value) || 0;
    const margen = parseFloat(document.querySelector('[name="margen_sugerido"]').value) || 0;
    
    // Determinar unidades totales según el modo
    let unidadesTotales = 0;
    
    if (toggle && toggle.checked) {
        // Modo SubContenido
        const contenido = parseInt(document.getElementById('contenido').value) || 0;
        const subContenido = parseInt(document.getElementById('subContenido').value) || 0;
        unidadesTotales = contenido * subContenido;
    } else {
        // Modo Simple
        unidadesTotales = parseInt(document.querySelector('[name="unidades_por_empaque"]').value) || 0;
    }
    
    let costoUnidad = 0;
    let precioEmpaque = 0;
    let precioUnidad = 0;

    // Calcular costo por unidad usando unidades totales
    if (costoEmpaque > 0 && unidadesTotales > 0) {
        costoUnidad = costoEmpaque / unidadesTotales;
    }

    // Calcular precios con margen
    if (costoEmpaque > 0 && margen > 0) {
        precioEmpaque = costoEmpaque * (1 + margen / 100);
    }

    if (costoUnidad > 0 && margen > 0) {
        precioUnidad = costoUnidad * (1 + margen / 100);
    }

    // Asignar valores a los inputs
    document.querySelector('[name="costo_por_unidad"]').value = costoUnidad.toFixed(2);
    document.querySelector('[name="precio_sugerido_empaque"]').value = precioEmpaque.toFixed(2);
    document.querySelector('[name="precio_sugerido_unidad"]').value = precioUnidad.toFixed(2);
}

// Detectar cambios en los campos relevantes
document.querySelector('[name="costo_por_empaque"]').addEventListener('input', calcularPrecios);
document.querySelector('[name="unidades_por_empaque"]').addEventListener('input', calcularPrecios);
document.querySelector('[name="margen_sugerido"]').addEventListener('input', calcularPrecios);

// También recalcular cuando cambien los campos de subcontenido
document.addEventListener('DOMContentLoaded', function() {
    const contenidoInput = document.getElementById('contenido');
    const subContenidoInput = document.getElementById('subContenido');
    
    if (contenidoInput) contenidoInput.addEventListener('input', calcularPrecios);
    if (subContenidoInput) subContenidoInput.addEventListener('input', calcularPrecios);
});
</script>

<script>
// ========== AUTOCOMPLETADO DE PROVEEDORES ==========
let timeoutId = null;
const proveedorInput = document.querySelector('[name="proveedor"]');
const direccionInput = document.querySelector('[name="direccion_proveedor"]');
const contactoInput = document.querySelector('[name="contacto_proveedor"]');

const suggestionBox = document.createElement('div');
suggestionBox.className = 'absolute z-50 w-full bg-white dark:bg-[#192233] border border-slate-300 dark:border-[#324467] rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto hidden';
suggestionBox.style.top = '100%';

function buscarProveedores(termino) {
    if (termino.length < 2) { suggestionBox.classList.add('hidden'); return; }
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => {
        fetch(`api_proveedores.php?action=buscar&termino=${encodeURIComponent(termino)}`)
            .then(response => response.json())
            .then(data => mostrarSugerencias(data))
            .catch(error => console.error('Error:', error));
    }, 300);
}

function mostrarSugerencias(proveedores) {
    suggestionBox.innerHTML = '';
    if (proveedores.length === 0) { suggestionBox.classList.add('hidden'); return; }
    proveedores.forEach(proveedor => {
        const item = document.createElement('div');
        item.className = 'px-4 py-3 hover:bg-primary/10 cursor-pointer border-b border-slate-200 dark:border-[#324467] last:border-b-0';
        item.innerHTML = `<div class="font-semibold text-slate-800 dark:text-white">${proveedor.nombre}</div><div class="text-sm text-slate-600 dark:text-[#92a4c9]">${proveedor.direccion || 'Sin dirección'}</div><div class="text-xs text-slate-500 dark:text-[#92a4c9]">${proveedor.contacto || 'Sin contacto'}</div>`;
        item.addEventListener('click', () => {
            proveedorInput.value = proveedor.nombre;
            direccionInput.value = proveedor.direccion || '';
            contactoInput.value = proveedor.contacto || '';
            suggestionBox.classList.add('hidden');
        });
        suggestionBox.appendChild(item);
    });
    suggestionBox.classList.remove('hidden');
}

[proveedorInput, direccionInput, contactoInput].forEach(input => {
    input.parentElement.style.position = 'relative';
    if (input === proveedorInput) input.parentElement.appendChild(suggestionBox);
    input.addEventListener('input', (e) => buscarProveedores(e.target.value));
    input.addEventListener('focus', (e) => { if (e.target.value.length >= 2) buscarProveedores(e.target.value); });
});

document.addEventListener('click', (e) => {
    if (!proveedorInput.contains(e.target) && !direccionInput.contains(e.target) && !contactoInput.contains(e.target) && !suggestionBox.contains(e.target)) {
        suggestionBox.classList.add('hidden');
    }
});
</script>

</body>
</html>
<?php
// Cerrar conexión

?>