<?php
session_start();
include 'funciones.php';

VerificarSiUsuarioYaInicioSesion();
// Conexión a la base de datos
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Opcional: puedes consultar la tabla usuarios si necesitas validar algo más
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
// --- FIN DE LA LÓGICA DE PERMISOS ---

// Función para buscar producto por código
function buscarProducto($conexion, $codigo) {
    // Buscar en la tabla creacion_de_productos
    $stmt = $conexion->prepare("SELECT * FROM creacion_de_productos WHERE CodigoProducto = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $producto_creacion = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Buscar en la tabla stock
    $stmt = $conexion->prepare("SELECT * FROM stock WHERE Codigo_Producto = ?");
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $producto_stock = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Combinar datos de ambas tablas
    $producto = [];
    
    // Priorizar datos de stock para los campos que existen en ambas tablas
    if ($producto_stock) {
        $producto = $producto_stock;
        // Asegurar que tenemos los nombres de campos correctos
        $producto['CodigoProducto'] = $producto_stock['Codigo_Producto'];
        $producto['Nombre_Producto'] = $producto_stock['Nombre_Producto'];
        $producto['Precio_Unitario'] = $producto_stock['Precio_Unitario'];
        $producto['Grupo'] = $producto_stock['Grupo'];
        $producto['Stock'] = $producto_stock['Stock'];
    }
    
    // Complementar con datos de creacion_de_productos si faltan
    if ($producto_creacion) {
        foreach ($producto_creacion as $campo => $valor) {
            if (!isset($producto[$campo]) || empty($producto[$campo])) {
                $producto[$campo] = $valor;
            }
        }
    }
    
    return empty($producto) ? null : $producto;
}

// Función para obtener sugerencias de productos
function obtenerSugerencias($conexion, $termino) {
    $termino = "%" . $termino . "%";
    $sugerencias = [];
    
    // Buscar en la tabla stock
    $stmt = $conexion->prepare("SELECT DISTINCT Codigo_Producto, Nombre_Producto FROM stock WHERE Codigo_Producto LIKE ? OR Nombre_Producto LIKE ? LIMIT 10");
    $stmt->bind_param("ss", $termino, $termino);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $sugerencias[] = $row;
    }
    $stmt->close();
    
    return $sugerencias;
}

// Procesar búsqueda AJAX
if (isset($_GET['accion']) && $_GET['accion'] === 'buscar') {
    $codigo = $_GET['codigo'] ?? '';
    $producto = buscarProducto($conexion, $codigo);
    
    if ($producto) {
        // Determinar el estado del stock
        $stock = isset($producto['Stock']) ? $producto['Stock'] : 0;
        $stock_status = '';
        $stock_class = '';
        
        if ($stock < 3) {
            $stock_status = 'Stock crítico - No hay stock suficiente';
            $stock_class = 'text-red-500';
        } elseif ($stock < 10) {
            $stock_status = 'Stock bajo - Se recomienda reponer';
            $stock_class = 'text-yellow-500';
        } else {
            $stock_status = 'Stock adecuado';
            $stock_class = 'text-green-500';
        }
        
        echo json_encode([
            'existe' => true,
            'producto' => $producto,
            'stock_status' => $stock_status,
            'stock_class' => $stock_class
        ]);
    } else {
        echo json_encode(['existe' => false]);
    }
    exit;
}

// Procesar solicitud de sugerencias AJAX
if (isset($_GET['accion']) && $_GET['accion'] === 'sugerencias') {
    $termino = $_GET['termino'] ?? '';
    $sugerencias = obtenerSugerencias($conexion, $termino);
    echo json_encode($sugerencias);
    exit;
}

?>
<!DOCTYPE html>

<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Chequeo de Stock</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#137fec",
                        "background-light": "#f6f7f8",
                        "background-dark": "#101922",
                    },
                    fontFamily: {
                        "display": ["Inter", "sans-serif"]
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
            font-size: 24px;
        }
        
        .suggestion-item {
            transition: all 0.2s ease;
        }
        
        .suggestion-item:hover {
            background-color: rgba(19, 127, 236, 0.1);
        }
        
        .stock-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .stock-critical {
            background-color: #ef4444;
            animation: pulse 2s infinite;
        }
        
        .stock-low {
            background-color: #eab308;
        }
        
        .stock-good {
            background-color: #10b981;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(239, 68, 68, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .product-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        #productStock, #productPrice {
    color: white !important;
}

#productStock span, #productPrice span {
    color: white !important;
}
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="relative flex min-h-screen w-full">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>
<!-- Main Content -->
<main class="flex-1 p-8">
<div class="w-full max-w-4xl mx-auto">
<!-- Header -->
<div class="flex flex-wrap justify-between items-start gap-4 mb-6">
<!-- PageHeading -->
<div class="flex flex-col gap-2">
<p class="text-white text-4xl font-black leading-tight tracking-[-0.033em]">Chequeo de Stock</p>
<p class="text-gray-400 text-base font-normal leading-normal">Introduce un código de producto para ver sus detalles.</p>
</div>
<!-- SingleButton -->
 
</div>
<!-- SearchBar -->
<div class="mb-8 relative">
<label class="flex flex-col min-w-40 h-12 w-full">
<div class="flex w-full flex-1 items-stretch rounded-lg h-full bg-slate-800/50 dark:bg-[#2A2A2A]">
<div class="text-gray-400 flex items-center justify-center pl-4">
<span class="material-symbols-outlined">search</span>
</div>
<input id="productSearch" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden text-white focus:outline-0 focus:ring-0 border-none bg-transparent h-full placeholder:text-gray-500 px-4 text-base font-normal leading-normal" placeholder="Buscar por código de producto..." value=""/>
</div>
</label>
<!-- Lista de sugerencias -->
<div id="suggestionsList" class="absolute z-10 w-full bg-slate-800 dark:bg-[#2A2A2A] rounded-lg mt-1 hidden">
    <!-- Las sugerencias se cargarán aquí dinámicamente -->
</div>
</div>
<!-- Product Display Area -->
<div>
<!-- EmptyState - Se mostrará cuando no hay búsqueda o el input está vacío -->
<div id="emptyState" class="flex flex-col items-center gap-6 text-center py-10">
<img class="w-full max-w-xs h-auto" data-alt="An illustration of a person searching through files and documents." src="https://lh3.googleusercontent.com/aida-public/AB6AXuD6XTh6nEX-EH3cGbJe3LA6MGtyGx5HfA9U0qp3m4gaQhIUaYZaOKIwI7j4f4WYXEefwJ3-DM7lqmfZHGH5clr3nWUKGRTs4GBvAF9Yw2yDts2nbS8D8IcCRk7XT-l1ENp7ht5qSyj64whFACU8yotoPfssffpbjwzzSxH6wgtIfrgj5DTwGuXA-GPhH9ycNNTu_tQmH1RY5tfM73zV4rcgNVr4yjqJAc_khfTojoN2o6QpRKfv0aVIbXPDReKXjW-dRLx-_BxTVKQs"/>
<div class="flex max-w-md flex-col items-center gap-2">
<p class="text-white text-lg font-bold leading-tight tracking-[-0.015em]">Ingresa un código de producto</p>
<p class="text-gray-400 text-sm font-normal leading-normal">Utiliza la barra de búsqueda superior para encontrar la información detallada de un producto en el inventario.</p>
</div>
</div>

<!-- Product Not Found State - Se mostrará cuando el producto no existe -->
<div id="productNotFound" class="hidden fade-in">
<div class="bg-red-900/20 border border-red-500/30 rounded-xl p-6 text-center">
<div class="flex justify-center mb-4">
<span class="material-symbols-outlined text-5xl text-red-500">error</span>
</div>
<h3 class="text-2xl font-bold text-white mb-2">Producto no encontrado</h3>
<p class="text-gray-300 mb-6">El código de producto ingresado no existe en el sistema. Debe crear este producto antes de poder consultar su stock.</p>
<a href="creacion_de_producto.php" class="inline-flex items-center justify-center rounded-lg h-12 px-6 bg-red-600 text-white font-medium hover:bg-red-700 transition-colors">
<span class="material-symbols-outlined mr-2">add_circle</span>
Crear Nuevo Producto
</a>
</div>
</div>

<!-- Product Details Card - Se mostrará cuando se encuentra el producto -->
<div id="productDetails" class="hidden fade-in">
<div class="product-card rounded-xl p-6">
<div class="flex items-center justify-between mb-6">
<h3 class="text-2xl font-bold text-white">Detalles del Producto</h3>
<div id="stockIndicator" class="flex items-center">
<span id="stockStatusDot" class="stock-indicator"></span>
<span id="stockStatusText" class="font-medium"></span>
</div>
</div>
<div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
<div class="flex flex-col gap-1">
<label class="text-sm font-medium text-gray-400">Código del Producto</label>
<p id="productCode" class="text-base text-white font-mono"></p>
</div>
<div class="flex flex-col gap-1">
<label class="text-sm font-medium text-gray-400">Nombre del Producto</label>
<p id="productName" class="text-base text-white"></p>
</div>
<div class="flex flex-col gap-1">
<label class="text-sm font-medium text-gray-400">Marca</label>
<p id="productBrand" class="text-base text-white"></p>
</div>
<div class="flex flex-col gap-1">
<label class="text-sm font-medium text-gray-400">Categoría</label>
<p id="productCategory" class="text-base text-white"></p>
</div>
<div class="flex flex-col gap-1 md:col-span-2">
<label class="text-sm font-medium text-gray-400">Descripción</label>
<p id="productDescription" class="text-base text-white"></p>
</div>
<div class="flex flex-col gap-1">
<label class="text-sm font-medium text-gray-400">Stock Disponible</label>
<p id="productStock" class="text-base font-bold"></p>
</div>
<div class="flex flex-col gap-1">
<label class="text-sm font-medium text-gray-400">Precio Unitario</label>
<p id="productPrice" class="text-base text-white"></p>
</div>
</div>
<div class="mt-6 flex justify-end gap-3">
<button id="editProductBtn" class="flex items-center justify-center rounded-lg h-10 px-4 bg-blue-600 text-white font-medium hover:bg-blue-700 transition-colors">
<span class="material-symbols-outlined mr-2">edit</span>
Editar Producto
</button>
<a href="creacion_de_producto.php" class="flex items-center justify-center rounded-lg h-10 px-4 bg-primary text-white font-medium hover:bg-primary/90 transition-colors">
<span class="material-symbols-outlined mr-2">add_circle</span>
Crear Nuevo Producto
</a>
</div>
</div>
</div>
</div>
</div>
</main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSearch = document.getElementById('productSearch');
    const suggestionsList = document.getElementById('suggestionsList');
    const emptyState = document.getElementById('emptyState');
    const productNotFound = document.getElementById('productNotFound');
    const productDetails = document.getElementById('productDetails');
    const editProductBtn = document.getElementById('editProductBtn');
    
    let currentProductCode = ''; // Variable para almacenar el código del producto actual
    
    let debounceTimer;
    
    // Función para buscar producto
    function searchProduct(code) {
        if (!code.trim()) {
            resetDisplay();
            return;
        }
        
        fetch(`chequeo_stock.php?accion=buscar&codigo=${encodeURIComponent(code)}`)
            .then(response => response.json())
            .then(data => {
                if (data.existe) {
                    currentProductCode = data.producto.CodigoProducto || data.producto.Codigo_Producto; // Guardar el código del producto
                    showProductDetails(data.producto, data.stock_status, data.stock_class);
                } else {
                    showProductNotFound();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error al buscar el producto', 'error');
            });
    }
    
    // Función para obtener sugerencias
    function getSuggestions(term) {
        if (!term.trim()) {
            suggestionsList.innerHTML = '';
            suggestionsList.classList.add('hidden');
            return;
        }
        
        fetch(`chequeo_stock.php?accion=sugerencias&termino=${encodeURIComponent(term)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    displaySuggestions(data);
                } else {
                    suggestionsList.innerHTML = '';
                    suggestionsList.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }
    
    // Función para mostrar sugerencias
    function displaySuggestions(suggestions) {
        suggestionsList.innerHTML = '';
        suggestionsList.classList.remove('hidden');
        
        suggestions.forEach(item => {
            const suggestionItem = document.createElement('div');
            suggestionItem.className = 'suggestion-item px-4 py-3 cursor-pointer flex items-center';
            suggestionItem.innerHTML = `
                <div class="flex-1">
                    <div class="text-white font-medium">${item.Codigo_Producto}</div>
                    <div class="text-gray-400 text-sm">${item.Nombre_Producto}</div>
                </div>
            `;
            
            suggestionItem.addEventListener('click', function() {
                productSearch.value = item.Codigo_Producto;
                suggestionsList.innerHTML = '';
                suggestionsList.classList.add('hidden');
                searchProduct(item.Codigo_Producto);
            });
            
            suggestionsList.appendChild(suggestionItem);
        });
    }
    
    // Función para mostrar detalles del producto
    function showProductDetails(product, stockStatus, stockClass) {
        emptyState.classList.add('hidden');
        productNotFound.classList.add('hidden');
        productDetails.classList.remove('hidden');
        
        // Guardar el código del producto actual
        currentProductCode = product.CodigoProducto || product.Codigo_Producto || '';
        
        // Actualizar datos del producto con los nombres de columna correctos
        document.getElementById('productCode').textContent = currentProductCode || 'N/A';
        document.getElementById('productName').textContent = product.Nombre_Producto || 'N/A';
        document.getElementById('productBrand').textContent = product.Marca || 'N/A';
        document.getElementById('productCategory').textContent = product.Grupo || 'N/A'; // Cambiado de Categoria a Grupo
        document.getElementById('productDescription').textContent = product.Descripcion || 'N/A';
        
        // Stock en color blanco y formato HTML
        const stockElement = document.getElementById('productStock');
        stockElement.innerHTML = `<span class="text-white font-bold">${product.Stock || '0'} unidades</span>`;
        
        // Precio en formato HTML con color blanco
        const priceElement = document.getElementById('productPrice');
        const precio = parseFloat(product.Precio_Unitario || 0).toFixed(2);
        priceElement.innerHTML = `<span class="text-white font-bold">L.${precio}</span>`;
        
        // Actualizar indicador de stock
        const stockIndicator = document.getElementById('stockIndicator');
        const stockStatusDot = document.getElementById('stockStatusDot');
        const stockStatusText = document.getElementById('stockStatusText');
        
        stockStatusDot.className = 'stock-indicator';
        stockStatusText.className = 'font-medium';
        
        if (stockClass.includes('red')) {
            stockStatusDot.classList.add('stock-critical');
            stockStatusText.classList.add('text-red-500');
        } else if (stockClass.includes('yellow')) {
            stockStatusDot.classList.add('stock-low');
            stockStatusText.classList.add('text-yellow-500');
        } else {
            stockStatusDot.classList.add('stock-good');
            stockStatusText.classList.add('text-green-500');
        }
        
        stockStatusText.textContent = stockStatus;
    }
    
    // Función para mostrar producto no encontrado
    function showProductNotFound() {
        emptyState.classList.add('hidden');
        productDetails.classList.add('hidden');
        productNotFound.classList.remove('hidden');
        currentProductCode = ''; // Limpiar el código del producto
    }
    
    // Función para resetear la visualización
    function resetDisplay() {
        emptyState.classList.remove('hidden');
        productDetails.classList.add('hidden');
        productNotFound.classList.add('hidden');
        suggestionsList.innerHTML = '';
        suggestionsList.classList.add('hidden');
        currentProductCode = ''; // Limpiar el código del producto
    }
    
    // Event listener para el botón de editar producto
    if (editProductBtn) {
        editProductBtn.addEventListener('click', function() {
            if (currentProductCode) {
                // Redirigir a inventario.php con el código del producto como parámetro
                window.location.href = `inventario.php?codigo=${encodeURIComponent(currentProductCode)}`;
            } else {
                showNotification('No hay ningún producto seleccionado para editar', 'error');
            }
        });
    }
    
    // Event listener para el input de búsqueda
    productSearch.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        
        // Cancelar el temporizador anterior
        clearTimeout(debounceTimer);
        
        // Configurar un nuevo temporizador
        debounceTimer = setTimeout(() => {
            if (searchTerm.length >= 2) {
                getSuggestions(searchTerm);
            } else {
                suggestionsList.innerHTML = '';
                suggestionsList.classList.add('hidden');
            }
        }, 300);
    });
    
    // Event listener para la tecla Enter
    productSearch.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchProduct(this.value);
            suggestionsList.innerHTML = '';
            suggestionsList.classList.add('hidden');
        }
    });
    
    // Cerrar la lista de sugerencias al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!productSearch.contains(e.target) && !suggestionsList.contains(e.target)) {
            suggestionsList.innerHTML = '';
            suggestionsList.classList.add('hidden');
        }
    });
    
    // Función para mostrar notificaciones
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="flex items-center">
                <span class="material-symbols-outlined mr-2">${type === 'success' ? 'check_circle' : 'error'}</span>
                <span>${message}</span>
            </div>
        `;
        
        const container = document.getElementById('notificationContainer');
        if (container) {
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
        }
    }
});
</script>

<div id="notificationContainer"></div>

<style>
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 16px 24px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateX(400px);
    transition: transform 0.3s ease-out;
    opacity: 0;
}
.notification.show {
    transform: translateX(0);
    opacity: 1;
}
.notification.success {
    background-color: #10b981;
}
.notification.error {
    background-color: #ef4444;
}
</style>
</body></html>