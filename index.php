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
<title>Menú Principal - Rey System APP</title>
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
        
        /* Grid base */
        .dashboard-grid {
            display: grid;
            gap: 1.5rem;
        }
        
        /* Mobile: 1 columna */
        @media (max-width: 639px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Tablet: 2 columnas */
        @media (min-width: 640px) and (max-width: 1023px) {
            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Desktop: Layout según rol */
        @media (min-width: 1024px) {
            /* Admin: 2 filas x 4 columnas (8 botones) */
            .dashboard-grid.admin-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            /* Cajero/Gerente: 3 botones arriba, 2 abajo centrados (5 botones) */
            .dashboard-grid.cajero-grid {
                grid-template-columns: repeat(6, 1fr);
            }
            
            .dashboard-grid.cajero-grid > a:nth-child(1),
            .dashboard-grid.cajero-grid > a:nth-child(2),
            .dashboard-grid.cajero-grid > a:nth-child(3) {
                grid-column: span 2;
            }
            
            .dashboard-grid.cajero-grid > a:nth-child(4) {
                grid-column: 2 / span 2;
            }
            
            .dashboard-grid.cajero-grid > a:nth-child(5) {
                grid-column: 4 / span 2;
            }
        }
    </style>
    <script src="nova_rey.js"></script>
<?php include "pwa-head.php"; ?>
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
<div class="dashboard-grid <?php echo $rol_usuario === 'admin' ? 'admin-grid' : 'cajero-grid'; ?>">
<!-- INICIO: BOTONES CON PERMISOS EN PANEL PRINCIPAL -->
<!-- Visible para Cajero, Gerente y Admin -->
<?php if (in_array($rol_usuario, ['cajero/gerente'])): ?>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-primary dark:hover:border-primary hover:scale-105 transition-all duration-300 group" href="caja_al_dia.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-primary/10 dark:bg-primary/20 group-hover:bg-primary/20 dark:group-hover:bg-primary/30 transition-all duration-300">
    <span class="material-symbols-outlined text-primary text-5xl group-hover:scale-110 transition-transform duration-300">point_of_sale</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Caja al Día</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Controla apertura, cierre y arqueo de caja</p>
</div>
</a>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-green-500 dark:hover:border-green-500 hover:scale-105 transition-all duration-300 group" href="nueva_venta.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-green-500/10 dark:bg-green-500/20 group-hover:bg-green-500/20 dark:group-hover:bg-green-500/30 transition-all duration-300">
    <span class="material-symbols-outlined text-green-600 dark:text-green-500 text-5xl group-hover:scale-110 transition-transform duration-300">payments</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Ventas</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Realiza y gestiona ventas rápidamente</p>
</div>
</a>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-purple-500 dark:hover:border-purple-500 hover:scale-105 transition-all duration-300 group" href="inventario.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-purple-500/10 dark:bg-purple-500/20 group-hover:bg-purple-500/20 dark:group-hover:bg-purple-500/30 transition-all duration-300">
    <span class="material-symbols-outlined text-purple-600 dark:text-purple-500 text-5xl group-hover:scale-110 transition-transform duration-300">inventory_2</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Inventario</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Controla y gestiona tu stock</p>
</div>
</a>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-orange-500 dark:hover:border-orange-500 hover:scale-105 transition-all duration-300 group" href="compra_desde_ventas.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-orange-500/10 dark:bg-orange-500/20 group-hover:bg-orange-500/20 dark:group-hover:bg-orange-500/30 transition-all duration-300">
    <span class="material-symbols-outlined text-orange-600 dark:text-orange-500 text-5xl group-hover:scale-110 transition-transform duration-300">shopping_cart</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Compras Desde Ventas</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Desglosa el dinero invertido de ventas</p>
</div>
</a>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-cyan-500 dark:hover:border-cyan-500 hover:scale-105 transition-all duration-300 group" href="consulta_precios.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-cyan-500/10 dark:bg-cyan-500/20 group-hover:bg-cyan-500/20 dark:group-hover:bg-cyan-500/30 transition-all duration-300">
    <span class="material-symbols-outlined text-cyan-600 dark:text-cyan-500 text-5xl group-hover:scale-110 transition-transform duration-300">search</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Consulta De Precios</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Busca precios de productos rápidamente</p>
</div>
</a>
<?php endif; ?>

<!-- Visible solo para Admin -->
<?php if ($rol_usuario === 'admin'): ?>
    <a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-primary dark:hover:border-primary hover:scale-105 transition-all duration-300 group" href="caja_al_dia.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-primary/10 dark:bg-primary/20 group-hover:bg-primary/20 dark:group-hover:bg-primary/30 transition-all duration-300">
    <span class="material-symbols-outlined text-primary text-5xl group-hover:scale-110 transition-transform duration-300">point_of_sale</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Caja al Día</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Controla apertura, cierre y arqueo de caja</p>
</div>
</a>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-green-500 dark:hover:border-green-500 hover:scale-105 transition-all duration-300 group" href="nueva_venta.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-green-500/10 dark:bg-green-500/20 group-hover:bg-green-500/20 dark:group-hover:bg-green-500/30 transition-all duration-300">
    <span class="material-symbols-outlined text-green-600 dark:text-green-500 text-5xl group-hover:scale-110 transition-transform duration-300">payments</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Ventas</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Realiza y gestiona ventas rápidamente</p>
</div>
</a>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-purple-500 dark:hover:border-purple-500 hover:scale-105 transition-all duration-300 group" href="inventario.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-purple-500/10 dark:bg-purple-500/20 group-hover:bg-purple-500/20 dark:group-hover:bg-purple-500/30 transition-all duration-300">
    <span class="material-symbols-outlined text-purple-600 dark:text-purple-500 text-5xl group-hover:scale-110 transition-transform duration-300">inventory_2</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Inventario</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Controla y gestiona tu stock</p>
</div>
</a>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-blue-500 dark:hover:border-blue-500 hover:scale-105 transition-all duration-300 group" href="creacion_de_producto.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-blue-500/10 dark:bg-blue-500/20 group-hover:bg-blue-500/20 dark:group-hover:bg-blue-500/30 transition-all duration-300">
    <span class="material-symbols-outlined text-blue-600 dark:text-blue-500 text-5xl group-hover:scale-110 transition-transform duration-300">add_box</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Creación De Productos</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Crea nuevos productos en el sistema</p>
</div>
</a>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-indigo-500 dark:hover:border-indigo-500 hover:scale-105 transition-all duration-300 group" href="crear_usuarios.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-indigo-500/10 dark:bg-indigo-500/20 group-hover:bg-indigo-500/20 dark:group-hover:bg-indigo-500/30 transition-all duration-300">
    <span class="material-symbols-outlined text-indigo-600 dark:text-indigo-500 text-5xl group-hover:scale-110 transition-transform duration-300">group_add</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Creación de Usuarios</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Gestiona y crea usuarios del sistema</p>
</div>
</a>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-orange-500 dark:hover:border-orange-500 hover:scale-105 transition-all duration-300 group" href="compra_desde_ventas.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-orange-500/10 dark:bg-orange-500/20 group-hover:bg-orange-500/20 dark:group-hover:bg-orange-500/30 transition-all duration-300">
    <span class="material-symbols-outlined text-orange-600 dark:text-orange-500 text-5xl group-hover:scale-110 transition-transform duration-300">shopping_cart</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Compras Desde Ventas</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Desglosa el dinero invertido de ventas</p>
</div>
</a>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-gray-200 dark:border-[#324467] bg-gradient-to-br from-white to-gray-50 dark:from-[#192233] dark:to-[#111722] p-8 hover:shadow-2xl hover:border-teal-500 dark:hover:border-teal-500 hover:scale-105 transition-all duration-300 group" href="reporte_ventas.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-teal-500/10 dark:bg-teal-500/20 group-hover:bg-teal-500/20 dark:group-hover:bg-teal-500/30 transition-all duration-300">
    <span class="material-symbols-outlined text-teal-600 dark:text-teal-500 text-5xl group-hover:scale-110 transition-transform duration-300">bar_chart</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-gray-900 dark:text-white text-xl font-black leading-tight">Reporte De Ventas</h2>
    <p class="text-gray-500 dark:text-[#92a4c9] text-sm font-normal leading-relaxed">Administra y visualiza reportes de ventas</p>
</div>
</a>
<a class="flex flex-col gap-6 rounded-2xl border-2 border-red-300 dark:border-red-500/50 bg-gradient-to-br from-red-50 to-white dark:from-red-900/10 dark:to-[#111722] p-8 hover:shadow-2xl hover:border-red-500 dark:hover:border-red-500 hover:scale-105 transition-all duration-300 group" href="configuracion.php">
<div class="flex items-center justify-center w-20 h-20 rounded-2xl bg-red-500/10 dark:bg-red-500/20 group-hover:bg-red-500/20 dark:group-hover:bg-red-500/30 transition-all duration-300">
    <span class="material-symbols-outlined text-red-600 dark:text-red-500 text-5xl group-hover:scale-110 transition-transform duration-300">settings</span>
</div>
<div class="flex flex-col gap-2">
    <h2 class="text-red-800 dark:text-red-400 text-xl font-black leading-tight">Configuración</h2>
    <p class="text-red-600 dark:text-red-400/80 text-sm font-normal leading-relaxed">Edita datos importantes del sistema</p>
</div>
</a>
<?php endif; ?>
<!-- FIN: BOTONES CON PERMISOS -->

</div>
</div>
<?php include 'version_helper.php'; $system_version = getSystemVersion(); ?>
<!-- Footer -->
<footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
<p class="text-gray-500 dark:text-[#92a4c9]">Versión <?= $system_version ?></p>
<a class="text-primary hover:underline" href="#">Ayuda y Soporte</a>
</footer>
</main>
</div>
</div>
</body></html>