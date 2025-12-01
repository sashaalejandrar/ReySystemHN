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

 $productoConsultado = null;
 $codigoTraducido = null;
 $mensaje = "";

// Procesar actualización de precio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_precio'])) {
    $codigo = $_POST['codigo_producto'];
    $nuevo_precio = $_POST['nuevo_precio'];
    
    $stmt = $conexion->prepare("UPDATE stock SET Precio_Unitario = ? WHERE Codigo_Producto = ?");
    $stmt->bind_param("ds", $nuevo_precio, $codigo);
    
    if ($stmt->execute()) {
        $mensaje = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Éxito!</strong>
                    <span class="block sm:inline"> El precio ha sido actualizado correctamente.</span>
                  </div>';
    } else {
        $mensaje = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Error!</strong>
                    <span class="block sm:inline"> No se pudo actualizar el precio.</span>
                  </div>';
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buscar'])) {
    $entrada = trim($_POST['entrada']);

    // Buscar por código exacto
    $stmt = $conexion->prepare("SELECT Codigo_Producto, Nombre_Producto, Marca, Descripcion, Precio_Unitario FROM stock WHERE Codigo_Producto = ? LIMIT 1");
    $stmt->bind_param("s", $entrada);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        // Si no encuentra por código, buscar por nombre
        $stmt = $conexion->prepare("SELECT Codigo_Producto, Nombre_Producto, Marca, Descripcion, Precio_Unitario FROM stock WHERE Nombre_Producto LIKE CONCAT('%', ?, '%') LIMIT 1");
        $stmt->bind_param("s", $entrada);
        $stmt->execute();
        $res = $stmt->get_result();
    }

    if ($res->num_rows > 0) {
        $productoConsultado = $res->fetch_assoc();
        $codigoTraducido = $productoConsultado['Codigo_Producto'];
    } else {
        $mensaje = '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Atención!</strong>
                    <span class="block sm:inline"> No se encontró el producto.</span>
                  </div>';
    }
    $stmt->close();
}

// Endpoint para autocompletado
if (isset($_GET['action']) && $_GET['action'] === 'autocomplete') {
    $query = isset($_GET['query']) ? trim($_GET['query']) : '';
    
    if (!empty($query)) {
        $stmt = $conexion->prepare("SELECT Codigo_Producto, Nombre_Producto, Marca, Precio_Unitario FROM stock WHERE Nombre_Producto LIKE CONCAT('%', ?, '%') LIMIT 8");
        $stmt->bind_param("s", $query);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $productos = array();
        while ($row = $res->fetch_assoc()) {
            $productos[] = array(
                'codigo' => $row['Codigo_Producto'],
                'nombre' => $row['Nombre_Producto'],
                'marca' => $row['Marca'],
                'precio' => number_format($row['Precio_Unitario'], 2, '.', ',')
            );
        }
        
        header('Content-Type: application/json');
        echo json_encode($productos);
        exit;
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Consulta y Edición de Precios</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script id="tailwind-config">
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
    }
    
    .autocomplete-container {
      position: relative;
    }
    
    .autocomplete-list {
      position: absolute;
      top: calc(100% + 8px);
      left: 0;
      right: 0;
      z-index: 50;
      background: white;
      border: 1px solid #e2e8f0;
      border-radius: 0.75rem;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      max-height: 320px;
      overflow-y: auto;
      animation: slideDown 0.2s ease-out;
    }
    
    .dark .autocomplete-list {
      background: #1e293b;
      border-color: #334155;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
    }
    
    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    .autocomplete-item {
      padding: 12px 16px;
      cursor: pointer;
      transition: all 0.2s ease;
      border-bottom: 1px solid #f1f5f9;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    
    .autocomplete-item:last-child {
      border-bottom: none;
    }
    
    .autocomplete-item:hover {
      background: linear-gradient(to right, #f8fafc, #f1f5f9);
    }
    
    .dark .autocomplete-item:hover {
      background: linear-gradient(to right, #334155, #475569);
    }
    
    .autocomplete-item.selected {
      background: linear-gradient(to right, #dbeafe, #bfdbfe);
    }
    
    .dark .autocomplete-item.selected {
      background: linear-gradient(to right, #1e3a8a, #1e40af);
    }
    
    .product-icon {
      width: 40px;
      height: 40px;
      border-radius: 0.5rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    
    .product-info {
      flex: 1;
      min-width: 0;
    }
    
    .product-name {
      font-weight: 600;
      color: #1e293b;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    
    .dark .product-name {
      color: #f1f5f9;
    }
    
    .product-details {
      display: flex;
      gap: 12px;
      align-items: center;
      margin-top: 4px;
    }
    
    .product-brand {
      font-size: 0.875rem;
      color: #64748b;
    }
    
    .dark .product-brand {
      color: #94a3b8;
    }
    
    .product-price {
      font-weight: 700;
      color: #059669;
      font-size: 0.875rem;
      background: rgba(5, 150, 105, 0.1);
      padding: 2px 8px;
      border-radius: 0.25rem;
    }
    
    .dark .product-price {
      background: rgba(5, 150, 105, 0.2);
    }
    
    .product-code {
      font-size: 0.75rem;
      color: #94a3b8;
      background: #f1f5f9;
      padding: 2px 6px;
      border-radius: 0.25rem;
      font-family: monospace;
    }
    
    .dark .product-code {
      background: #334155;
      color: #cbd5e1;
    }
    
    .no-results {
      padding: 20px;
      text-align: center;
      color: #64748b;
    }
    
    .dark .no-results {
      color: #94a3b8;
    }
    
    /* Scrollbar styling */
    .autocomplete-list::-webkit-scrollbar {
      width: 6px;
    }
    
    .autocomplete-list::-webkit-scrollbar-track {
      background: #f1f5f9;
      border-radius: 3px;
    }
    
    .autocomplete-list::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 3px;
    }
    
    .autocomplete-list::-webkit-scrollbar-thumb:hover {
      background: #94a3b8;
    }
    
    .dark .autocomplete-list::-webkit-scrollbar-track {
      background: #334155;
    }
    
    .dark .autocomplete-list::-webkit-scrollbar-thumb {
      background: #475569;
    }
    
    .dark .autocomplete-list::-webkit-scrollbar-thumb:hover {
      background: #64748b;
    }
  </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="relative flex h-screen min-h-screen w-full flex-col dark group/design-root overflow-hidden">
<div class="flex h-full w-full">
  <?php include 'menu_lateral.php'; ?>
<main class="flex h-full flex-1 flex-col overflow-y-auto">
<header class="flex h-16 shrink-0 items-center justify-end whitespace-nowrap border-b border-solid border-slate-200 dark:border-b-[#232f48] px-8">
<div class="flex items-center gap-4">
<button class="flex h-10 w-10 cursor-pointer items-center justify-center overflow-hidden rounded-full bg-slate-100 text-slate-500 dark:bg-[#232f48] dark:text-white">
<span class="material-symbols-outlined text-2xl">
                notifications
              </span>
</button>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="User profile picture" style='background-image: url("<?php echo $Perfil;?>");'></div>
</div>
</header>
<div class="flex flex-1 flex-col gap-8 p-8">
<div class="flex flex-wrap justify-between gap-3">
<h2 class="text-slate-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em] min-w-72">Consulta y Edición de Precios</h2>
</div>

<?php if (!empty($mensaje)): ?>
<div class="mb-4">
    <?php echo $mensaje; ?>
</div>
<?php endif; ?>

<div class="flex flex-col gap-6">
<div class="flex flex-col gap-4 rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#192233]/50 p-6">
<form method="POST" action="">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-slate-900 dark:text-white text-base font-medium leading-normal pb-2">Código del Producto</p>
<div class="flex w-full flex-1 items-stretch rounded-lg autocomplete-container">
<input type="text" id="productoInput" name="entrada" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#192233] focus:border-primary dark:focus:border-primary h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] rounded-r-none border-r-0 pr-2 text-base font-normal leading-normal" placeholder="Introduce o escanea el código del producto" value="<?php echo isset($_POST['entrada']) ? htmlspecialchars($_POST['entrada']) : ''; ?>"/>
<div id="autocompleteList" class="autocomplete-list hidden"></div>
<div class="text-slate-400 dark:text-[#92a4c9] flex border border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#192233] items-center justify-center pr-[15px] rounded-r-lg border-l-0">
<span class="material-symbols-outlined text-2xl">
                      barcode_scanner
                    </span>
</div>
</div>
</label>
<br>
<div class="flex flex-1 gap-3 flex-wrap justify-start">
<button type="submit" name="buscar" class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary/90 transition-colors">
<span class="truncate">Consultar</span>
</button>
<button type="button" onclick="limpiarFormulario()" class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-slate-200 text-slate-700 dark:bg-[#232f48] dark:text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-slate-300 dark:hover:bg-[#2e3c5a] transition-colors">
<span class="truncate">Limpiar</span>
</button>
</div>
</form>
</div>

<?php if ($productoConsultado): ?>
<div class="flex flex-col gap-4 rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#192233]/50 p-6">
<h3 class="text-lg font-bold text-slate-900 dark:text-white">Resultado de la Consulta</h3>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
<div class="flex flex-col gap-1">
<p class="text-sm font-medium text-slate-500 dark:text-[#92a4c9]">Nombre del Producto</p>
<p class="text-base font-semibold text-slate-800 dark:text-white"><?php echo htmlspecialchars($productoConsultado['Nombre_Producto']); ?></p>
</div>
<div class="flex flex-col gap-1">
<p class="text-sm font-medium text-slate-500 dark:text-[#92a4c9]">Marca</p>
<p class="text-base font-semibold text-slate-800 dark:text-white"><?php echo htmlspecialchars($productoConsultado['Marca']); ?></p>
</div>
<div class="flex flex-col gap-1 col-span-1 md:col-span-2">
<p class="text-sm font-medium text-slate-500 dark:text-[#92a4c9]">Descripción</p>
<p class="text-base font-semibold text-slate-800 dark:text-white"><?php echo htmlspecialchars($productoConsultado['Descripcion']); ?></p>
</div>
</div>
<div class="border-t border-slate-200 dark:border-[#232f48] mt-4 pt-4 flex flex-col gap-4">
<form method="POST" action="">
<input type="hidden" name="codigo_producto" value="<?php echo htmlspecialchars($productoConsultado['Codigo_Producto']); ?>">
<label class="flex flex-col min-w-40">
<p class="text-slate-500 dark:text-[#92a4c9] text-sm font-medium leading-normal pb-2">Precio (Editable)</p>
<div class="relative flex w-full max-w-xs flex-1 items-stretch rounded-lg">
<div class="text-slate-500 dark:text-[#92a4c9] flex border border-slate-300 dark:border-[#324467] bg-slate-100 dark:bg-[#232f48] items-center justify-center pl-4 rounded-l-lg border-r-0 text-xl font-bold"> L.</div>
<input type="number" name="nuevo_precio" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#192233] focus:border-primary dark:focus:border-primary h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] px-4 rounded-l-none text-2xl font-extrabold" placeholder="0.00" step="0.01" value="<?php echo htmlspecialchars($productoConsultado['Precio_Unitario']); ?>"/>
</div>
</label>
<br>
<div class="flex flex-1 gap-3 flex-wrap justify-start">
<button type="submit" name="actualizar_precio" class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary/90 transition-colors">
<span class="truncate">Guardar Cambios</span>
</button>
<button type="button" onclick="limpiarFormulario()" class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-slate-200 text-slate-700 dark:bg-[#232f48] dark:text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-slate-300 dark:hover:bg-[#2e3c5a] transition-colors">
<span class="truncate">Cancelar</span>
</button>
</div>
</form>
</div>
</div>
<?php endif; ?>
</div>
</div>
</main>
</div>
</div>

<script>
function limpiarFormulario() {
    document.querySelector('input[name="entrada"]').value = '';
    window.location.href = 'consulta_edicion_precios.php';
}

// Autocompletado mejorado
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('productoInput');
    const autocompleteList = document.getElementById('autocompleteList');
    let debounceTimer;
    let selectedIndex = -1;
    let currentItems = [];

    input.addEventListener('input', function() {
        const query = this.value.trim();
        
        // Limpiar el temporizador anterior
        clearTimeout(debounceTimer);
        
        // Establecer un nuevo temporizador para esperar a que el usuario deje de escribir
        debounceTimer = setTimeout(function() {
            if (query.length >= 2) {
                fetch(`consulta_edicion_precios.php?action=autocomplete&query=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        currentItems = data;
                        selectedIndex = -1;
                        renderAutocompleteList(data);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        autocompleteList.classList.add('hidden');
                    });
            } else {
                autocompleteList.classList.add('hidden');
            }
        }, 300); // Esperar 300ms después de que el usuario deje de escribir
    });
    
    // Navegación con teclado
    input.addEventListener('keydown', function(e) {
        const items = autocompleteList.querySelectorAll('.autocomplete-item');
        
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            updateSelection(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            updateSelection(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedIndex >= 0 && items[selectedIndex]) {
                items[selectedIndex].click();
            }
        } else if (e.key === 'Escape') {
            autocompleteList.classList.add('hidden');
            selectedIndex = -1;
        }
    });
    
    function updateSelection(items) {
        items.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        });
    }
    
    function renderAutocompleteList(data) {
        // Limpiar la lista anterior
        autocompleteList.innerHTML = '';
        
        if (data.length > 0) {
            autocompleteList.classList.remove('hidden');
            
            // Crear elementos para cada producto
            data.forEach((producto, index) => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.dataset.codigo = producto.codigo;
                item.dataset.index = index;
                
                // Icono del producto
                const icon = document.createElement('div');
                icon.className = 'product-icon';
                icon.innerHTML = '<span class="material-symbols-outlined text-white text-xl">inventory_2</span>';
                
                // Información del producto
                const info = document.createElement('div');
                info.className = 'product-info';
                
                const name = document.createElement('div');
                name.className = 'product-name';
                name.textContent = producto.nombre;
                
                const details = document.createElement('div');
                details.className = 'product-details';
                
                const brand = document.createElement('span');
                brand.className = 'product-brand';
                brand.textContent = producto.marca;
                
                const price = document.createElement('span');
                price.className = 'product-price';
                price.textContent = `L.${producto.precio}`;
                
                const code = document.createElement('span');
                code.className = 'product-code';
                code.textContent = producto.codigo;
                
                details.appendChild(brand);
                details.appendChild(price);
                details.appendChild(code);
                
                info.appendChild(name);
                info.appendChild(details);
                
                item.appendChild(icon);
                item.appendChild(info);
                
                item.addEventListener('click', function() {
                    input.value = this.dataset.codigo;
                    autocompleteList.classList.add('hidden');
                    selectedIndex = -1;
                });
                
                autocompleteList.appendChild(item);
            });
        } else {
            autocompleteList.classList.remove('hidden');
            const noResults = document.createElement('div');
            noResults.className = 'no-results';
            noResults.innerHTML = `
                <span class="material-symbols-outlined text-4xl mb-2">search_off</span>
                <p>No se encontraron productos</p>
                <p class="text-sm mt-1">Intenta con otra búsqueda</p>
            `;
            autocompleteList.appendChild(noResults);
        }
    }
    
    // Ocultar la lista cuando el usuario hace clic fuera de ella
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !autocompleteList.contains(e.target)) {
            autocompleteList.classList.add('hidden');
            selectedIndex = -1;
        }
    });
});
</script>

</body></html>