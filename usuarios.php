<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
 
// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'config_users.php';
require_once 'usuario.php';

$database = new Database();
$db = $database->getConnection();
$usuario = new Usuario($db);

// Parámetros de paginación
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 10;
$offset = ($pagina - 1) * $limite;

// Obtener usuarios
$result = $usuario->listarUsuarios('', $limite, $offset);
$total = $usuario->contarUsuarios();
$total_paginas = ceil($total / $limite);

// Usuario actual de sesión
$nombre_usuario = isset($_SESSION['nombre']) ? $_SESSION['nombre'] : 'Usuario';
$rol_usuario = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'Usuario';
$foto_usuario = isset($_SESSION['foto']) ? $_SESSION['foto'] : '';
// Opcional: puedes consultar la tabla usuarios si necesitas validar algo más
 $resultado = $db->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
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
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Panel de Administración - Usuarios Registrados</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        primary: "#3b82f6",
                        "background-light": "#f1f5f9",
                        "background-dark": "#0f172a",
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"],
                    },
                    borderRadius: {
                        DEFAULT: "0.5rem",
                    },
                },
            },
        };
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-200">
    <div class="flex h-screen">
        <!-- Sidebar -->
       <?php include 'menu_lateral.php'; ?>

        <!-- Contenido principal -->
        <main class="flex-1 overflow-y-auto">
            <div class="p-8">
                <header class="mb-8">
                    <h2 class="text-3xl font-bold text-slate-900 dark:text-white">Usuarios Registrados</h2>
                    <p class="text-slate-500 dark:text-slate-400 mt-1">Administra todos los usuarios registrados en el sistema.</p>
                </header>

                <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm">
                    <!-- Barra de herramientas -->
                    <div class="p-4 md:p-6 border-b border-slate-200 dark:border-slate-700 flex flex-col md:flex-row items-center justify-between gap-4">
                        <div class="relative w-full md:w-auto">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">search</span>
                            <input id="buscarUsuario" class="w-full md:w-64 pl-10 pr-4 py-2 rounded border border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-700 focus:ring-2 focus:ring-primary focus:border-primary transition duration-150 text-sm" 
                                   placeholder="Buscar por nombre o email..." type="text"/>
                        </div>
                        <div class="flex items-center gap-2 w-full md:w-auto">
                            <button id="btnFiltrar" class="flex-1 md:flex-initial flex items-center justify-center gap-2 px-4 py-2 rounded border border-slate-300 dark:border-slate-600 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-700 transition duration-150">
                                <span class="material-symbols-outlined text-base">filter_list</span>
                                Filtrar
                            </button>
                            <button id="btnNuevoUsuario" class="flex-1 md:flex-initial flex items-center justify-center gap-2 px-4 py-2 rounded bg-primary text-white text-sm font-medium hover:bg-blue-600 transition duration-150">
                                <span class="material-symbols-outlined text-base">add</span>
                                Nuevo Usuario
                            </button>
                        </div>
                    </div>

                    <!-- Tabla de usuarios -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-slate-500 dark:text-slate-400 uppercase bg-slate-50 dark:bg-slate-900/50">
                                <tr>
                                    <th class="px-6 py-3" scope="col">Nombre</th>
                                    <th class="px-6 py-3" scope="col">Rol</th>
                                    <th class="px-6 py-3" scope="col">Estado</th>
                                    <th class="px-6 py-3" scope="col">Último Acceso</th>
                                    <th class="px-6 py-3" scope="col"><span class="sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody id="tablaUsuarios">
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $tiempo_actividad = $usuario->tiempoDesdeActividad($row['Ultima_Actividad']);
                                        
                                        // Determinar clase del badge según estado
                                        $badge_class = '';
                                        switch($row['Estado_Online']) {
                                            case 'Activo':
                                                $badge_class = 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300';
                                                break;
                                            case 'Pendiente':
                                                $badge_class = 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300';
                                                break;
                                            case 'Inactivo':
                                                $badge_class = 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300';
                                                break;
                                        }
                                        
                                        $foto_perfil = !empty($row['Perfil']) ? $row['Perfil'] : 'https://ui-avatars.com/api/?name=' . urlencode($row['Nombre'] . ' ' . $row['Apellido']);
                                ?>
                                <tr class="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900/20">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <img alt="<?php echo htmlspecialchars($row['Nombre']); ?> avatar" 
                                                 class="w-10 h-10 rounded-full object-cover" 
                                                 src="<?php echo htmlspecialchars($foto_perfil); ?>"/>
                                            <div>
                                                <div class="font-medium text-slate-900 dark:text-white">
                                                    <?php echo htmlspecialchars($row['Nombre'] . ' ' . $row['Apellido']); ?>
                                                </div>
                                                <div class="text-slate-500 dark:text-slate-400">
                                                    <?php echo htmlspecialchars($row['Email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($row['Rol']); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $badge_class; ?>">
                                            <?php echo htmlspecialchars($row['Estado_Online']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4"><?php echo htmlspecialchars($tiempo_actividad); ?></td>
                                    <td class="px-6 py-4 text-right">
                                        <button onclick="gestorUsuarios.mostrarMenu(<?php echo $row['Id']; ?>, event)" 
                                                class="p-2 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700">
                                            <span class="material-symbols-outlined text-base">more_horiz</span>
                                        </button>
                                    </td>
                                </tr>
                                <?php
                                    }
                                } else {
                                ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                                        No hay usuarios registrados
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="p-4 md:p-6 border-t border-slate-200 dark:border-slate-700 flex flex-col md:flex-row items-center justify-between">
                        <span class="text-sm text-slate-500 dark:text-slate-400 mb-4 md:mb-0" id="textoPaginacion">
                            Mostrando <span class="font-medium text-slate-700 dark:text-slate-200"><?php echo $offset + 1; ?></span> a 
                            <span class="font-medium text-slate-700 dark:text-slate-200"><?php echo min($offset + $limite, $total); ?></span> de 
                            <span class="font-medium text-slate-700 dark:text-slate-200"><?php echo $total; ?></span> resultados
                        </span>
                        <div class="inline-flex rounded shadow-sm">
                            <button id="btnAnterior" 
                                    <?php echo $pagina <= 1 ? 'disabled' : ''; ?>
                                    class="px-3 py-2 rounded-l border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm font-medium text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 <?php echo $pagina <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                Anterior
                            </button>
                            <button id="btnSiguiente" 
                                    <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>
                                    class="px-3 py-2 border-t border-b border-r border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm font-medium text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 <?php echo $pagina >= $total_paginas ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                                Siguiente
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="usuarios.js"></script>
</body>
</html>
<?php
$db->close();
?>