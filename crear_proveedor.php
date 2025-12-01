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
<title>Añadir Nuevo Proveedor</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script id="tailwind-config">
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
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="relative flex min-h-screen w-full">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>
<!-- Main Content -->
<main class="flex-1 p-8 overflow-y-auto">
<div class="max-w-4xl mx-auto">
<!-- Breadcrumbs -->
<div class="flex flex-wrap gap-2 mb-4">
<a class="text-[#92adc9] text-base font-medium leading-normal hover:text-primary" href="#">Proveedores</a>
<span class="text-[#92adc9] text-base font-medium leading-normal">/</span>
<span class="text-white text-base font-medium leading-normal">Añadir Nuevo</span>
</div>
<!-- Page Heading -->
<div class="flex flex-wrap justify-between gap-3 mb-8">
<h1 class="text-white text-4xl font-black leading-tight tracking-[-0.033em] min-w-72">Añadir Nuevo Proveedor</h1>
</div>
<!-- Form Container -->
<div class="bg-[#192633] rounded-xl p-6 md:p-8">
<!-- Section Header -->
<h2 class="text-white text-[22px] font-bold leading-tight tracking-[-0.015em] mb-6">Información del Proveedor</h2>
<div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
<!-- Nombre del Proveedor -->
<div class="flex flex-col">
<label class="text-white text-base font-medium leading-normal pb-2" for="supplier-name">Nombre del Proveedor</label>
<input class="form-input w-full rounded-lg text-white border border-[#324d67] bg-[#101922] focus:ring-primary focus:border-primary h-12 placeholder:text-[#92adc9] px-4 text-base font-normal" id="supplier-name" placeholder="Ej: Tech Solutions S.A." type="text"/>
</div>
<!-- RTN -->
<div class="flex flex-col">
<label class="text-white text-base font-medium leading-normal pb-2" for="rtn">RTN</label>
<input class="form-input w-full rounded-lg text-white border border-[#324d67] bg-[#101922] focus:ring-primary focus:border-primary h-12 placeholder:text-[#92adc9] px-4 text-base font-normal" id="rtn" placeholder="Ej: 08019000123456" type="text"/>
</div>
<!-- Persona de Contacto -->
<div class="flex flex-col">
<label class="text-white text-base font-medium leading-normal pb-2" for="contact-person">Persona de Contacto</label>
<input class="form-input w-full rounded-lg text-white border border-[#324d67] bg-[#101922] focus:ring-primary focus:border-primary h-12 placeholder:text-[#92adc9] px-4 text-base font-normal" id="contact-person" placeholder="Ej: Juan Pérez" type="text"/>
</div>
<!-- Celular -->
<div class="flex flex-col">
<label class="text-white text-base font-medium leading-normal pb-2" for="cellphone">Celular</label>
<input class="form-input w-full rounded-lg text-white border border-[#324d67] bg-[#101922] focus:ring-primary focus:border-primary h-12 placeholder:text-[#92adc9] px-4 text-base font-normal" id="cellphone" placeholder="Ej: 9988-7766" type="tel"/>
</div>
<!-- Dirección -->
<div class="flex flex-col md:col-span-2">
<label class="text-white text-base font-medium leading-normal pb-2" for="address">Dirección</label>
<input class="form-input w-full rounded-lg text-white border border-[#324d67] bg-[#101922] focus:ring-primary focus:border-primary h-12 placeholder:text-[#92adc9] px-4 text-base font-normal" id="address" placeholder="Ej: Col. Las Acacias, Bloque 5, Casa 10" type="text"/>
</div>
<!-- Estado -->
<div class="flex flex-col md:col-span-2">
<label class="text-white text-base font-medium leading-normal pb-2" for="status">Estado</label>
<div class="flex items-center gap-4">
<span class="text-[#92adc9]">Inactivo</span>
<label class="relative inline-flex items-center cursor-pointer">
<input checked="" class="sr-only peer" type="checkbox" value=""/>
<div class="w-11 h-6 bg-[#324d67] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
</label>
<span class="text-white font-medium">Activo</span>
</div>
</div>
</div>
<!-- Action Buttons -->
<div class="flex justify-end gap-4 mt-8 pt-6 border-t border-[#324d67]">
<button class="flex items-center justify-center font-semibold text-white px-6 py-2.5 rounded-lg h-12 hover:bg-white/10 transition-colors">
                            Cancelar
                        </button>
<button class="flex items-center justify-center font-semibold text-white px-6 py-2.5 rounded-lg h-12 bg-primary hover:bg-primary/90 transition-colors">
                            Guardar Proveedor
                        </button>
</div>
</div>
</div>
</main>
</div>
<script>
document.querySelector('button.bg-primary').addEventListener('click', function () {
    const nombre = document.getElementById('supplier-name').value.trim();
    const rtn = document.getElementById('rtn').value.trim();
    const contacto = document.getElementById('contact-person').value.trim();
    const celular = document.getElementById('cellphone').value.trim();
    const direccion = document.getElementById('address').value.trim();
    const estado = document.querySelector('input[type="checkbox"]').checked ? 'Activo' : 'Inactivo';

    // Validación rápida
    if (!nombre || !rtn || !contacto || !celular || !direccion) {
        mostrarAdvertencia('Todos los campos son obligatorios.');
        return;
    }

    fetch('añadir_proveedor.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nombre, rtn, contacto, celular, direccion, estado })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            mostrarExito('Proveedor creado correctamente.');
            window.location.href = 'lista_proveedores.php'; // Redirige al listado
        } else {
            mostrarError('Error: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Error al crear proveedor:', err);
        mostrarError('Error inesperado al crear proveedor.');
    });
});
</script>

<?php include 'modal_sistema.php'; ?>
</body>
</html>