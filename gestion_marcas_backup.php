<?php
session_start();
include 'funciones.php';

VerificarSiUsuarioYaInicioSesion();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

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

$rol_usuario = strtolower($Rol);

// Verificar que sea admin
if ($rol_usuario !== 'admin') {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Gestión de Categorías - Rey System APP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    "display": ["Poppins", "sans-serif"]
                }
            }
        }
    }
</script>
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
    }
</style>
<?php include "pwa-head.php"; ?>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 flex flex-col">
<div class="flex-1 p-6 lg:p-10">
    <!-- Header con Gradiente -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-purple-600 via-indigo-600 to-blue-600 p-8 mb-8 shadow-2xl">
        <div class="absolute inset-0 bg-black/10"></div>
        <div class="relative z-10 flex flex-wrap justify-between items-center gap-4">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-4xl">category</span>
                </div>
                <div>
                    <h1 class="text-white text-4xl font-black leading-tight">Gestión de Categorías</h1>
                    <p class="text-purple-100 text-base font-medium">Administra las categorías de productos del sistema</p>
                </div>
            </div>
            <button onclick="abrirModalCrear()" class="flex items-center gap-2 px-6 py-3 bg-white/20 backdrop-blur-md hover:bg-white/30 text-white rounded-xl font-bold transition-all shadow-lg hover:shadow-xl border-2 border-white/30">
                <span class="material-symbols-outlined">add</span>
                Nueva Categoría
            </button>
        </div>
        <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full -mr-32 -mt-32"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-white/5 rounded-full -ml-24 -mb-24"></div>
    </div>

    <!-- Tabla Mejorada -->
    <div class="relative">
        <div class="absolute inset-0 bg-gradient-to-r from-purple-500/5 via-blue-500/5 to-indigo-500/5 rounded-xl blur-xl"></div>
        <div class="relative bg-white dark:bg-[#192233] rounded-xl shadow-2xl border-2 border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-6">
                    <div class="flex-1 relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">search</span>
                        <input 
                            type="text" 
                            id="busqueda" 
                            placeholder="Buscar categorías..." 
                            class="w-full pl-10 pr-4 py-3 rounded-lg border-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary transition-all"
                            onkeyup="filtrarTabla()">
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full" id="tablaCategorias">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-[#111722] dark:to-[#1a2332] border-b-2 border-primary/20">
                                <th class="px-6 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary text-sm">tag</span>
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">ID</span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary text-sm">category</span>
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Nombre</span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary text-sm">description</span>
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Descripción</span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left">
                                    <div class="flex items-center gap-2">
                                        <span class="material-symbols-outlined text-primary text-sm">person</span>
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Creado Por</span>
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-right">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Acciones</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody id="cuerpoTabla" class="divide-y divide-gray-100 dark:divide-gray-700">
                            <!-- Se llenará dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</main>
</div>
</div>

<!-- Modal Crear/Editar -->
<div id="modalCategoria" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#192233] rounded-xl shadow-2xl max-w-md w-full">
        <div class="p-6">
            <h2 id="tituloModal" class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Nueva Categoría</h2>
            
            <form id="formCategoria" onsubmit="guardarCategoria(event)">
                <input type="hidden" id="categoriaId">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre *</label>
                    <input 
                        type="text" 
                        id="categoriaNombre" 
                        required
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="Ej: Electrónica">
                </div>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Descripción</label>
                    <textarea 
                        id="categoriaDescripcion" 
                        rows="3"
                        class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-[#111722] text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-primary"
                        placeholder="Descripción opcional"></textarea>
                </div>
                
                <div class="flex gap-3">
                    <button 
                        type="button" 
                        onclick="cerrarModal()" 
                        class="flex-1 px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        Cancelar
                    </button>
                    <button 
                        type="submit" 
                        class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let categorias = [];

// Cargar categorías al iniciar
document.addEventListener('DOMContentLoaded', () => {
    cargarCategorias();
});

async function cargarCategorias() {
    try {
        const response = await fetch('api/categorias_crud.php');
        const data = await response.json();
        
        if (data.success) {
            categorias = data.data;
            renderizarTabla();
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Error al cargar categorías', 'error');
    }
}

function renderizarTabla() {
    const tbody = document.getElementById('cuerpoTabla');
    
    if (categorias.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    <span class="material-symbols-outlined text-6xl mb-2">category</span>
                    <p>No hay categorías registradas</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = categorias.map(cat => `
        <tr class="hover:bg-gray-50 dark:hover:bg-[#111722] transition-colors">
            <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">${cat.id_categoria}</td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">${cat.nombre}</td>
            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">${cat.descripcion || '-'}</td>
            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">${cat.creado_por || '-'}</td>
            <td class="px-6 py-4 text-right text-sm">
                <button onclick="editarCategoria(${cat.id_categoria})" class="text-primary hover:text-primary/80 mr-3">
                    <span class="material-symbols-outlined">edit</span>
                </button>
                <button onclick="eliminarCategoria(${cat.id_categoria}, '${cat.nombre}')" class="text-red-600 hover:text-red-800">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            </td>
        </tr>
    `).join('');
}

function filtrarTabla() {
    const busqueda = document.getElementById('busqueda').value.toLowerCase();
    const filas = document.querySelectorAll('#cuerpoTabla tr');
    
    filas.forEach(fila => {
        const texto = fila.textContent.toLowerCase();
        fila.style.display = texto.includes(busqueda) ? '' : 'none';
    });
}

function abrirModalCrear() {
    document.getElementById('tituloModal').textContent = 'Nueva Categoría';
    document.getElementById('categoriaId').value = '';
    document.getElementById('categoriaNombre').value = '';
    document.getElementById('categoriaDescripcion').value = '';
    document.getElementById('modalCategoria').classList.remove('hidden');
}

function editarCategoria(id) {
    const categoria = categorias.find(c => c.id_categoria == id);
    if (!categoria) return;
    
    document.getElementById('tituloModal').textContent = 'Editar Categoría';
    document.getElementById('categoriaId').value = categoria.id_categoria;
    document.getElementById('categoriaNombre').value = categoria.nombre;
    document.getElementById('categoriaDescripcion').value = categoria.descripcion || '';
    document.getElementById('modalCategoria').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modalCategoria').classList.add('hidden');
}

async function guardarCategoria(event) {
    event.preventDefault();
    
    const id = document.getElementById('categoriaId').value;
    const nombre = document.getElementById('categoriaNombre').value.trim();
    const descripcion = document.getElementById('categoriaDescripcion').value.trim();
    
    const metodo = id ? 'PUT' : 'POST';
    const datos = { nombre, descripcion };
    if (id) datos.id = id;
    
    try {
        const response = await fetch('api/categorias_crud.php', {
            method: metodo,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos)
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire('Éxito', data.message, 'success');
            cerrarModal();
            cargarCategorias();
            
            // Notificar a otras pestañas/ventanas
            localStorage.setItem('categorias_updated', Date.now().toString());
            
            // Notificar a la misma pestaña (para inventario.php si está abierto)
            window.dispatchEvent(new CustomEvent('categorias_changed'));
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Error al guardar categoría', 'error');
    }
}

async function eliminarCategoria(id, nombre) {
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: `Se eliminará la categoría "${nombre}"`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch('api/categorias_crud.php', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire('Eliminado', data.message, 'success');
            cargarCategorias();
            
            // Notificar a otras pestañas/ventanas
            localStorage.setItem('categorias_updated', Date.now().toString());
            
            // Notificar a la misma pestaña
            window.dispatchEvent(new CustomEvent('categorias_changed'));
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    } catch (error) {
        Swal.fire('Error', 'Error al eliminar categoría', 'error');
    }
}
</script>

</body>
</html>
