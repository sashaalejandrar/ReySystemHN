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
<title>Menú Principal - Aplicación de Cobros</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&amp;display=swap" rel="stylesheet"/>
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
            font-variation-settings:
            'FILL' 0,
            'wght' 400,
            'GRAD' 0,
            'opsz' 24
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>
<!-- Main Content -->
<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">
<!-- PageHeading -->
<div class="flex flex-wrap justify-between gap-4 mb-8">
<div class="flex flex-col gap-2">
<h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">Panel de Control</h1>
<p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">Accede a todas las funcionalidades clave desde aquí.</p>
</div>
</div>
<!-- TextGrid (Action Buttons) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
<a class="flex flex-col gap-4 rounded-xl border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#192233] p-6 hover:shadow-lg hover:border-primary dark:hover:border-primary transition-all duration-300 group" href="apertura_caja.php">
<span class="material-symbols-outlined text-primary text-3xl">point_of_sale</span>
<div class="flex flex-col gap-1">
<h2 class="text-gray-900 dark:text-white text-base font-bold leading-tight">Apertura De Caja</h2>
<p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">Aperturar turno</p>
</div>
</a>
<a class="flex flex-col gap-4 rounded-xl border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#192233] p-6 hover:shadow-lg hover:border-primary dark:hover:border-primary transition-all duration-300 group" href="arqueo_caja.php">
<span class="material-symbols-outlined text-primary text-3xl">point_of_sale</span>
<div class="flex flex-col gap-1">
<h2 class="text-gray-900 dark:text-white text-base font-bold leading-tight">Arqueo De Caja</h2>
<p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">Arquea caja con las ventas que hay hasta el momento</p>
</div>
</a>
<a class="flex flex-col gap-4 rounded-xl border border-gray-200 dark:border-[#324467] bg-white dark:bg-[#192233] p-6 hover:shadow-lg hover:border-primary dark:hover:border-primary transition-all duration-300 group" href="cierre_caja.php">
<span class="material-symbols-outlined text-primary text-3xl">point_of_sale</span>
<div class="flex flex-col gap-1">
<h2 class="text-gray-900 dark:text-white text-base font-bold leading-tight">Cierre De Caja</h2>
<p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-normal">Finalizacion de turno</p>
</div>
</a>
 
</div>
</a>
</div>
</div>
<!-- Footer -->
<footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
<p class="text-gray-500 dark:text-[#92a4c9]">Versión 1.0.0</p>
<a class="text-primary hover:underline" href="#">Ayuda y Soporte</a>
</footer>
</main>
</div>
</div>
</body></html>