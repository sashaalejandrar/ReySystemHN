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
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Consulta Rápida de Precios</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;display=swap" rel="stylesheet"/>
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
    /* CAMBIO: Estilos para el contenedor de sugerencias */
    #suggestionsContainer {
        max-height: 200px;
        overflow-y: auto;
    }
    .suggestion-item {
        cursor: pointer;
    }
    .suggestion-item:hover, .suggestion-item.active {
        background-color: rgba(59, 130, 246, 0.1); /* primary/10 */
    }
  </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>
<!-- Main Content -->
<main class="flex h-full flex-1 flex-col overflow-y-auto">
<!-- TopNavBar -->
<header class="flex h-16 shrink-0 items-center justify-end whitespace-nowrap border-b border-solid border-slate-200 dark:border-b-[#232f48] px-8">
<div class="flex items-center gap-4">
<button class="flex h-10 w-10 cursor-pointer items-center justify-center overflow-hidden rounded-full bg-slate-100 text-slate-500 dark:bg-[#232f48] dark:text-white">
<span class="material-symbols-outlined text-2xl">notifications</span>
</button>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="User profile picture" style='background-image: url("<?php echo $Perfil; ?>");'></div>
</div>
</header>
<!-- Page Content -->
<div class="flex flex-1 flex-col gap-8 p-8">
<!-- PageHeading -->
<div class="flex flex-wrap justify-between gap-3">
<h2 class="text-slate-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em] min-w-72">Consulta Rápida de Precios</h2>
</div>
<div class="flex flex-col gap-6">
<!-- Search Section -->
<div class="flex flex-col gap-4 rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#192233]/50 p-6">
<!-- TextField -->
<label class="flex flex-col min-w-40 flex-1 relative"> <!-- CAMBIO: Añadido 'relative' para el posicionamiento -->
<p class="text-slate-900 dark:text-white text-base font-medium leading-normal pb-2">Código o Nombre del Producto</p> <!-- CAMBIO: Texto más descriptivo -->
<div class="flex w-full flex-1 items-stretch rounded-lg">
<input id="codigoInput" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#192233] focus:border-primary dark:focus:border-primary h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] rounded-r-none border-r-0 pr-2 text-base font-normal leading-normal" placeholder="Escribe para buscar o escanear..." autocomplete="off"/>
<div class="text-slate-400 dark:text-[#92a4c9] flex border border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#192233] items-center justify-center pr-[15px] rounded-r-lg border-l-0">
<span class="material-symbols-outlined text-2xl">barcode_scanner</span>
</div>
</div>
<!-- CAMBIO: Contenedor para las sugerencias -->
<div id="suggestionsContainer" class="absolute top-full left-0 right-0 z-10 bg-white dark:bg-[#192233] border border-slate-200 dark:border-[#324467] rounded-b-lg shadow-lg hidden"></div>
</label>
<!-- ButtonGroup -->
<div class="flex flex-1 gap-3 flex-wrap justify-start">
<button id="consultarBtn" class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-primary/90 transition-colors">
<span class="truncate">Consultar</span>
</button>
<button id="limpiarBtn" class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-4 bg-slate-200 text-slate-700 dark:bg-[#232f48] dark:text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-slate-300 dark:hover:bg-[#2e3c5a] transition-colors">
<span class="truncate">Limpiar</span>
</button>
</div>
</div>
<!-- Results Section -->
<div class="flex flex-col gap-4 rounded-xl border border-slate-200 dark:border-[#232f48] bg-white dark:bg-[#192233]/50 p-6">
<h3 class="text-lg font-bold text-slate-900 dark:text-white">Resultado de la Consulta</h3>
<div id="resultadoContainer">
<div class="flex flex-col items-center justify-center text-center p-12 text-slate-400 dark:text-slate-500 border-2 border-dashed border-slate-200 dark:border-[#232f48] rounded-lg">
<span class="material-symbols-outlined text-6xl">qr_code_scanner</span>
<p class="mt-4 text-sm font-medium">Introduce un código o nombre para ver sus detalles aquí.</p>
</div>
</div>
</div>
</div>
</div>
</main>
</div>
</div>

<!-- CAMBIO: Script actualizado con autocompletado -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const codigoInput = document.getElementById('codigoInput');
    const consultarBtn = document.getElementById('consultarBtn');
    const limpiarBtn = document.getElementById('limpiarBtn');
    const resultadoContainer = document.getElementById('resultadoContainer');
    const suggestionsContainer = document.getElementById('suggestionsContainer');

    let currentFocus = -1; // Para navegación con teclado
    let selectedCode = null; // Almacena el código del producto seleccionado

    // Función para mostrar el estado inicial
    function mostrarEstadoInicial() {
        resultadoContainer.innerHTML = `
            <div class="flex flex-col items-center justify-center text-center p-12 text-slate-400 dark:text-slate-500 border-2 border-dashed border-slate-200 dark:border-[#232f48] rounded-lg">
                <span class="material-symbols-outlined text-6xl">qr_code_scanner</span>
                <p class="mt-4 text-sm font-medium">Introduce un código o nombre para ver sus detalles aquí.</p>
            </div>
        `;
    }

    // Función para realizar la búsqueda principal
    function realizarBusqueda(codigo) {
        resultadoContainer.innerHTML = `<p class="text-center text-slate-500">Buscando producto...</p>`;
        
        fetch('buscar_producto_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ codigo: codigo })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const producto = data.product;
                resultadoContainer.innerHTML = `
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="flex flex-col gap-1">
                            <p class="text-sm font-medium text-slate-500 dark:text-[#92a4c9]">Nombre del Producto</p>
                            <p class="text-base font-semibold text-slate-800 dark:text-white">${producto.nombre}</p>
                        </div>
                        <div class="flex flex-col gap-1">
                            <p class="text-sm font-medium text-slate-500 dark:text-[#92a4c9]">Marca</p>
                            <p class="text-base font-semibold text-slate-800 dark:text-white">${producto.marca}</p>
                        </div>
                        <div class="flex flex-col gap-1 col-span-1 md:col-span-2">
                            <p class="text-sm font-medium text-slate-500 dark:text-[#92a4c9]">Descripción</p>
                            <p class="text-base font-semibold text-slate-800 dark:text-white">${producto.descripcion || 'Sin descripción'}</p>
                        </div>
                    </div>
                    <div class="border-t border-slate-200 dark:border-[#232f48] mt-4 pt-4">
                        <p class="text-sm font-medium text-slate-500 dark:text-[#92a4c9]">Precio</p>
                        <p class="text-4xl font-extrabold text-primary">L ${producto.precio} HNL</p>
                    </div>
                `;
            } else {
                resultadoContainer.innerHTML = `
                    <div class="text-center text-red-500">
                        <span class="material-symbols-outlined text-6xl">error</span>
                        <p class="mt-4 text-lg font-medium">${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            resultadoContainer.innerHTML = `
                <div class="text-center text-red-500">
                    <span class="material-symbols-outlined text-6xl">error</span>
                    <p class="mt-4 text-lg font-medium">Ocurrió un error al buscar el producto.</p>
                </div>
            `;
        });
    }

    // --- LÓGICA DE AUTOCOMPLETADO ---
    codigoInput.addEventListener('input', function() {
        const query = this.value.trim();
        selectedCode = null; // Resetear código seleccionado al escribir
        if (query.length < 2) {
            suggestionsContainer.innerHTML = '';
            suggestionsContainer.classList.add('hidden');
            return;
        }

        fetch('buscar_sugerencias_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ term: query })
        })
        .then(response => response.json())
        .then(suggestions => {
            suggestionsContainer.innerHTML = '';
            if (suggestions.length > 0) {
                suggestions.forEach((item, index) => {
                    const div = document.createElement('div');
                    div.innerHTML = `<strong>${item.nombre}</strong> <span class="text-sm text-slate-500">(${item.codigo})</span>`;
                    div.classList.add('suggestion-item', 'px-4', 'py-2', 'text-slate-700', 'dark:text-slate-300');
                    div.dataset.codigo = item.codigo;
                    div.dataset.nombre = item.nombre;
                    
                    div.addEventListener('click', function() {
                        codigoInput.value = this.dataset.nombre;
                        selectedCode = this.dataset.codigo;
                        suggestionsContainer.classList.add('hidden');
                        realizarBusqueda(selectedCode);
                    });
                    suggestionsContainer.appendChild(div);
                });
                suggestionsContainer.classList.remove('hidden');
            } else {
                suggestionsContainer.classList.add('hidden');
            }
        });
    });

    // Navegación con teclado en las sugerencias
    codigoInput.addEventListener('keydown', function(e) {
        const items = suggestionsContainer.getElementsByClassName('suggestion-item');
        if (e.key === 'ArrowDown') {
            currentFocus++;
            addActive(items);
        } else if (e.key === 'ArrowUp') {
            currentFocus--;
            addActive(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (currentFocus > -1 && items[currentFocus]) {
                items[currentFocus].click();
            } else {
                // Si no hay una sugerencia seleccionada, busca con el texto del input
                realizarBusqueda(this.value.trim());
            }
        } else if (e.key === 'Escape') {
            suggestionsContainer.classList.add('hidden');
        }
    });
    
    function addActive(items) {
        if (!items) return false;
        removeActive(items);
        if (currentFocus >= items.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (items.length - 1);
        items[currentFocus].classList.add('active');
    }
    function removeActive(items) {
        for (let i = 0; i < items.length; i++) {
            items[i].classList.remove('active');
        }
    }

    // Ocultar sugerencias al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.relative')) {
            suggestionsContainer.classList.add('hidden');
        }
    });

    // Eventos de botones
    consultarBtn.addEventListener('click', function() {
        const codigoABuscar = selectedCode || codigoInput.value.trim();
        if (!codigoABuscar) {
            mostrarAdvertencia('Por favor, introduce un código o selecciona un producto.');
            return;
        }
        realizarBusqueda(codigoABuscar);
    });

    limpiarBtn.addEventListener('click', function() {
        codigoInput.value = '';
        selectedCode = null;
        suggestionsContainer.classList.add('hidden');
        mostrarEstadoInicial();
    });
});
</script>
<?php include 'modal_sistema.php'; ?>
</body></html>