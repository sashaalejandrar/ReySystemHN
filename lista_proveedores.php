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
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Gestión de Proveedores</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com" rel="preconnect"/>
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700;900&display=swap" rel="stylesheet"/>
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
            font-variation-settings:
            'FILL' 0,
            'wght' 400,
            'GRAD' 0,
            'opsz' 24
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease-out;
        }
        .notification.show {
            transform: translateX(0);
        }
        .notification.success {
            background-color: #10b981;
            color: white;
        }
        .notification.error {
            background-color: #ef4444;
            color: white;
        }
        .notification.info {
            background-color: #3b82f6;
            color: white;
        }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
<div class="relative flex min-h-screen w-full">
 <?php include 'menu_lateral.php'; ?>
<main class="flex-1 p-8">
<div class="w-full max-w-7xl mx-auto">
<header class="flex flex-wrap items-center justify-between gap-4 mb-6">
<div class="flex flex-col gap-1">
<h1 class="text-gray-900 dark:text-white text-3xl font-bold leading-tight tracking-tight">Gestión de Proveedores</h1>
<p class="text-gray-500 dark:text-[#92adc9] text-base font-normal leading-normal">Busca, filtra y gestiona los proveedores de tu negocio.</p>
</div>
<button id="btnNuevoProveedor" onclick="RedirigirCrearProveedor();" class="flex min-w-[84px] cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-lg h-10 px-4 bg-primary text-white text-sm font-bold leading-normal tracking-[0.015em]">
<span class="material-symbols-outlined">add_circle</span>
<span class="truncate">Añadir Nuevo Proveedor</span>
</button>
</header>
<div class="flex flex-wrap items-center gap-4 mb-6">
<div class="flex-grow min-w-[280px]">
<label class="flex flex-col h-12 w-full">
<div class="flex w-full flex-1 items-stretch rounded-lg h-full bg-[#233648]">
<div class="text-[#92adc9] flex items-center justify-center pl-4">
<span class="material-symbols-outlined">search</span>
</div>
<input id="busquedaInput" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden text-white focus:outline-0 focus:ring-0 border-none bg-transparent h-full placeholder:text-[#92adc9] px-2 text-base font-normal leading-normal" placeholder="Buscar por nombre, RTN o contacto..." value=""/>
</div>
</label>
</div>
<div class="flex items-center gap-3">
<div class="relative">
<button id="btnEstado" class="flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-[#233648] px-4">
<p id="estadoTexto" class="text-white text-sm font-medium leading-normal">Estado: Todos</p>
<span class="material-symbols-outlined text-base">expand_more</span>
</button>
<div id="estadoDropdown" class="hidden absolute top-10 right-0 bg-[#233648] rounded-lg shadow-lg z-10 min-w-[150px]">
<button class="estado-option block w-full text-left px-4 py-2 text-white hover:bg-primary/20" data-estado="Todos">Todos</button>
<button class="estado-option block w-full text-left px-4 py-2 text-white hover:bg-primary/20" data-estado="Activo">Activo</button>
<button class="estado-option block w-full text-left px-4 py-2 text-white hover:bg-primary/20" data-estado="Inactivo">Inactivo</button>
<button class="estado-option block w-full text-left px-4 py-2 text-white hover:bg-primary/20" data-estado="Pendiente">Pendiente</button>
</div>
</div>
<button class="flex h-8 shrink-0 items-center justify-center gap-x-2 rounded-full bg-transparent border border-gray-600 px-4 text-gray-400 hover:bg-[#233648] hover:text-white transition-colors">
<p class="text-sm font-medium leading-normal">Más Filtros</p>
<span class="material-symbols-outlined text-base">tune</span>
</button>
</div>
</div>
<div class="overflow-hidden rounded-xl border border-[#324d67] bg-[#111a22]">
<div class="overflow-x-auto">
<table class="w-full text-left">
<thead class="bg-[#192633]">
<tr>
<th class="p-4 text-white text-sm font-medium">Nombre</th>
<th class="p-4 text-white text-sm font-medium">RTN</th>
<th class="p-4 text-white text-sm font-medium">Contacto</th>
<th class="p-4 text-white text-sm font-medium">Dirección</th>
<th class="p-4 text-white text-sm font-medium">Celular</th>
<th class="p-4 text-white text-sm font-medium">Estado</th>
<th class="p-4 text-white text-sm font-medium text-center">Acciones</th>
</tr>
</thead>
<tbody id="proveedoresTableBody">
<!-- Los datos se cargarán dinámicamente aquí -->
<tr id="loadingRow">
<td colspan="7" class="p-4 text-center text-white">
<div class="flex items-center justify-center gap-2">
<span class="loading"></span>
<span>Cargando proveedores...</span>
</div>
</td>
</tr>
</tbody>
</table>
</div>
<div class="flex items-center justify-between p-4 border-t border-t-[#324d67]">
<p id="infoPaginacion" class="text-sm text-gray-400">Mostrando 0-0 de 0 proveedores</p>
<div class="flex items-center gap-2" id="paginacion">
<!-- La paginación se cargará dinámicamente aquí -->
</div>
</div>
</div>
</div>
</main>
</div>

<!-- Notification container -->
<div id="notification" class="notification">
    <span id="notificationIcon" class="material-symbols-outlined"></span>
    <div>
        <p id="notificationTitle" class="font-semibold"></p>
        <p id="notificationMessage" class="text-sm"></p>
    </div>
</div>

<!-- Modal para editar proveedor -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-[#111a22] rounded-lg p-6 w-full max-w-md">
        <h2 class="text-white text-xl font-bold mb-4">Editar Proveedor</h2>
        <form id="editForm">
            <input type="hidden" id="editId">
            <div class="mb-4">
                <label class="block text-white text-sm font-medium mb-2">Nombre</label>
                <input type="text" id="editNombre" class="w-full p-2 rounded bg-[#233648] text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-4">
                <label class="block text-white text-sm font-medium mb-2">RTN</label>
                <input type="text" id="editRTN" class="w-full p-2 rounded bg-[#233648] text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-4">
                <label class="block text-white text-sm font-medium mb-2">Contacto</label>
                <input type="text" id="editContacto" class="w-full p-2 rounded bg-[#233648] text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-4">
                <label class="block text-white text-sm font-medium mb-2">Dirección</label>
                <input type="text" id="editDireccion" class="w-full p-2 rounded bg-[#233648] text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-4">
                <label class="block text-white text-sm font-medium mb-2">Celular</label>
                <input type="text" id="editCelular" class="w-full p-2 rounded bg-[#233648] text-white focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="mb-4">
                <label class="block text-white text-sm font-medium mb-2">Estado</label>
                <select id="editEstado" class="w-full p-2 rounded bg-[#233648] text-white focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="Activo">Activo</option>
                    <option value="Inactivo">Inactivo</option>
                    <option value="Pendiente">Pendiente</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" id="cancelEdit" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700">Cancelar</button>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let paginaActual = 1;
    let estadoActual = 'Todos';
    let busquedaActual = '';
    
    // Elementos del DOM
    const proveedoresTableBody = document.getElementById('proveedoresTableBody');
    const infoPaginacion = document.getElementById('infoPaginacion');
    const paginacion = document.getElementById('paginacion');
    const busquedaInput = document.getElementById('busquedaInput');
    const btnEstado = document.getElementById('btnEstado');
    const estadoTexto = document.getElementById('estadoTexto');
    const estadoDropdown = document.getElementById('estadoDropdown');
    const editModal = document.getElementById('editModal');
    const editForm = document.getElementById('editForm');
    const cancelEdit = document.getElementById('cancelEdit');
    
    // Event Listeners
    busquedaInput.addEventListener('input', debounce(function() {
        busquedaActual = this.value;
        paginaActual = 1;
        cargarProveedores();
    }, 500));
    
    btnEstado.addEventListener('click', function() {
        estadoDropdown.classList.toggle('hidden');
    });
    
    document.querySelectorAll('.estado-option').forEach(option => {
        option.addEventListener('click', function() {
            estadoActual = this.getAttribute('data-estado');
            estadoTexto.textContent = `Estado: ${estadoActual}`;
            estadoDropdown.classList.add('hidden');
            paginaActual = 1;
            cargarProveedores();
        });
    });
    
    cancelEdit.addEventListener('click', function() {
        editModal.classList.add('hidden');
    });
    
    editForm.addEventListener('submit', function(e) {
        e.preventDefault();
        guardarProveedor();
    });
    
    // Cerrar dropdowns al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (!btnEstado.contains(e.target) && !estadoDropdown.contains(e.target)) {
            estadoDropdown.classList.add('hidden');
        }
    });
    
    // Función para cargar proveedores
    // Función para cargar proveedores
function cargarProveedores() {
    // Mostrar indicador de carga
    proveedoresTableBody.innerHTML = `
        <tr id="loadingRow">
            <td colspan="7" class="p-4 text-center text-white">
                <div class="flex items-center justify-center gap-2">
                    <span class="loading"></span>
                    <span>Cargando proveedores...</span>
                </div>
            </td>
        </tr>
    `;
    
    // Construir URL con parámetros
    const url = `obtener_proveedores.php?pagina=${paginaActual}&busqueda=${encodeURIComponent(busquedaActual)}&estado=${encodeURIComponent(estadoActual)}`;
    
    // Realizar petición fetch
    fetch(url)
        .then(response => {
            // Comprobar si la respuesta HTTP es correcta (200 OK)
            if (!response.ok) {
                // Si no es correcta (ej: 404 Not Found, 500 Internal Server Error)
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }
            return response.json(); // Intentar parsear a JSON
        })
        .then(data => {
            // Limpiar tabla
            proveedoresTableBody.innerHTML = '';
            
            // Comprobar si se recibieron los datos esperados
            if (data && data.proveedores) {
                if (data.proveedores.length > 0) {
                    data.proveedores.forEach(proveedor => {
                        const fila = crearFilaProveedor(proveedor);
                        proveedoresTableBody.appendChild(fila);
                    });
                    
                    // Actualizar información de paginación
                    const inicio = (paginaActual - 1) * 5 + 1;
                    const fin = Math.min(inicio + 4, data.total_registros);
                    infoPaginacion.textContent = `Mostrando ${inicio}-${fin} de ${data.total_registros} proveedores`;
                    
                    // Actualizar controles de paginación
                    actualizarPaginacion(data.total_paginas);
                } else {
                    // Mostrar mensaje de no resultados
                    proveedoresTableBody.innerHTML = `
                        <tr>
                            <td colspan="7" class="p-4 text-center text-white">
                                No se encontraron proveedores que coincidan con los criterios de búsqueda.
                            </td>
                        </tr>
                    `;
                    infoPaginacion.textContent = 'Mostrando 0-0 de 0 proveedores';
                    paginacion.innerHTML = '';
                }
            } else {
                // La respuesta no es la esperada
                throw new Error('El servidor devolvió una respuesta inválida o vacía.');
            }
        })
        .catch(error => {
            // Este .catch ahora capturará tanto errores de red como los errores que lanzamos nosotros
            console.error('Error completo al cargar proveedores:', error);
            proveedoresTableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="p-4 text-center text-white">
                        <p class="font-bold">Error al cargar los proveedores.</p>
                        <p class="text-sm text-gray-400">Revisa la consola (F12) para más detalles.</p>
                        <p class="text-xs text-gray-500 mt-2">${error.message}</p>
                    </td>
                </tr>
            `;
        });
}
    // Función para crear una fila de proveedor
    function crearFilaProveedor(proveedor) {
        const tr = document.createElement('tr');
        tr.className = 'border-t border-t-[#324d67]';
        
        // Determinar clase de estado
        let estadoClass = 'bg-green-500/10 text-green-400';
        let estadoDot = 'bg-green-400';
        
        if (proveedor.Estado === 'Inactivo') {
            estadoClass = 'bg-gray-500/10 text-gray-400';
            estadoDot = 'bg-gray-400';
        } else if (proveedor.Estado === 'Pendiente') {
            estadoClass = 'bg-yellow-500/10 text-yellow-400';
            estadoDot = 'bg-yellow-400';
        }
        
        tr.innerHTML = `
            <td class="p-4 text-white text-sm font-normal">${proveedor.Nombre}</td>
            <td class="p-4 text-[#92adc9] text-sm font-normal">${proveedor.RTN}</td>
            <td class="p-4 text-[#92adc9] text-sm font-normal">${proveedor.Contacto}</td>
            <td class="p-4 text-[#92adc9] text-sm font-normal">${proveedor.Direccion}</td>
            <td class="p-4 text-[#92adc9] text-sm font-normal">${proveedor.Celular}</td>
            <td class="p-4 text-sm font-normal">
                <div class="inline-flex items-center gap-2 rounded-full ${estadoClass} px-3 py-1 text-sm font-medium">
                    <div class="h-2 w-2 rounded-full ${estadoDot}"></div>
                    ${proveedor.Estado}
                </div>
            </td>
            <td class="p-4 text-[#92adc9] text-sm font-bold text-center">
                <div class="relative">
                    <button class="text-white hover:text-primary" onclick="toggleActions(${proveedor.Id})">
                        <span class="material-symbols-outlined">more_vert</span>
                    </button>
                    <div id="actions-${proveedor.Id}" class="hidden absolute right-0 mt-2 w-48 bg-[#192633] rounded-md shadow-lg z-10">
                        <button onclick="editarProveedor(${proveedor.Id})" class="block w-full text-left px-4 py-2 text-sm text-white hover:bg-[#233648]">
                            <span class="material-symbols-outlined text-sm mr-2">edit</span> Editar
                        </button>
                        <button onclick="eliminarProveedor(${proveedor.Id})" class="block w-full text-left px-4 py-2 text-sm text-white hover:bg-[#233648]">
                            <span class="material-symbols-outlined text-sm mr-2">delete</span> Eliminar
                        </button>
                    </div>
                </div>
            </td>
        `;
        
        return tr;
    }
    
    // Función para actualizar los controles de paginación
    function actualizarPaginacion(totalPaginas) {
        paginacion.innerHTML = '';
        
        // Botón anterior
        const btnAnterior = document.createElement('button');
        btnAnterior.className = `flex items-center justify-center h-8 w-8 rounded-lg hover:bg-primary/20 text-white ${paginaActual === 1 ? 'opacity-50 cursor-not-allowed' : ''}`;
        btnAnterior.innerHTML = '<span class="material-symbols-outlined text-xl">chevron_left</span>';
        btnAnterior.disabled = paginaActual === 1;
        btnAnterior.addEventListener('click', () => {
            if (paginaActual > 1) {
                paginaActual--;
                cargarProveedores();
            }
        });
        paginacion.appendChild(btnAnterior);
        
        // Botones de página
        for (let i = 1; i <= totalPaginas; i++) {
            if (i === 1 || i === totalPaginas || (i >= paginaActual - 1 && i <= paginaActual + 1)) {
                const btnPagina = document.createElement('button');
                btnPagina.className = `flex items-center justify-center h-8 w-8 rounded-lg ${i === paginaActual ? 'bg-primary text-white' : 'hover:bg-primary/20 text-white'}`;
                btnPagina.textContent = i;
                btnPagina.addEventListener('click', () => {
                    paginaActual = i;
                    cargarProveedores();
                });
                paginacion.appendChild(btnPagina);
            } else if (i === paginaActual - 2 || i === paginaActual + 2) {
                const span = document.createElement('span');
                span.className = 'text-white';
                span.textContent = '...';
                paginacion.appendChild(span);
            }
        }
        
        // Botón siguiente
        const btnSiguiente = document.createElement('button');
        btnSiguiente.className = `flex items-center justify-center h-8 w-8 rounded-lg hover:bg-primary/20 text-white ${paginaActual === totalPaginas ? 'opacity-50 cursor-not-allowed' : ''}`;
        btnSiguiente.innerHTML = '<span class="material-symbols-outlined text-xl">chevron_right</span>';
        btnSiguiente.disabled = paginaActual === totalPaginas;
        btnSiguiente.addEventListener('click', () => {
            if (paginaActual < totalPaginas) {
                paginaActual++;
                cargarProveedores();
            }
        });
        paginacion.appendChild(btnSiguiente);
    }
    
    // Función para mostrar/ocultar menú de acciones
    window.toggleActions = function(id) {
        const actionsMenu = document.getElementById(`actions-${id}`);
        
        // Cerrar todos los menús abiertos
        document.querySelectorAll('[id^="actions-"]').forEach(menu => {
            if (menu.id !== `actions-${id}`) {
                menu.classList.add('hidden');
            }
        });
        
        // Alternar el menú actual
        actionsMenu.classList.toggle('hidden');
    }
    
    // Función para editar proveedor
window.editarProveedor = function(id) {
    // Cerrar todos los menús de acciones abiertos
    document.querySelectorAll('[id^="actions-"]').forEach(menu => {
        menu.classList.add('hidden');
    });
    
    // Mostrar indicador de carga
    showNotification('info', 'Cargando', 'Obteniendo información del proveedor...');
    
    // Obtener datos del proveedor
    fetch(`obtener_proveedores.php?id=${id}`)
        .then(response => {
            // Verificar si la respuesta es correcta
            if (!response.ok) {
                throw new Error(`Error HTTP: ${response.status} ${response.statusText}`);
            }
            
            // Intentar parsear la respuesta como JSON
            return response.json().catch(error => {
                throw new Error('La respuesta del servidor no es un JSON válido');
            });
        })
        .then(data => {
            // Verificar si la operación fue exitosa
            if (data.success && data.proveedor) {
                // Llenar formulario con datos del proveedor
                document.getElementById('editId').value = data.proveedor.Id;
                document.getElementById('editNombre').value = data.proveedor.Nombre || '';
                document.getElementById('editRTN').value = data.proveedor.RTN || '';
                document.getElementById('editContacto').value = data.proveedor.Contacto || '';
                document.getElementById('editDireccion').value = data.proveedor.Direccion || '';
                document.getElementById('editCelular').value = data.proveedor.Celular || '';
                document.getElementById('editEstado').value = data.proveedor.Estado || 'Activo';
                
                // Mostrar modal
                editModal.classList.remove('hidden');
            } else {
                // Mostrar mensaje de error específico del servidor
                showNotification('error', 'Error', data.message || 'No se pudo obtener la información del proveedor');
            }
        })
        .catch(error => {
            console.error('Error al obtener proveedor:', error);
            showNotification('error', 'Error', `No se pudo obtener la información del proveedor: ${error.message}`);
        });
}
    
    // Función para guardar proveedor
    function guardarProveedor() {
        const id = document.getElementById('editId').value;
        const nombre = document.getElementById('editNombre').value;
        const rtn = document.getElementById('editRTN').value;
        const contacto = document.getElementById('editContacto').value;
        const direccion = document.getElementById('editDireccion').value;
        const celular = document.getElementById('editCelular').value;
        const estado = document.getElementById('editEstado').value;
        
        // Validar campos
        if (!nombre || !rtn || !contacto || !direccion || !celular || !estado) {
            showNotification('error', 'Error', 'Todos los campos son obligatorios');
            return;
        }
        
        // Enviar datos al servidor
        fetch('guardar_proveedor.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                nombre: nombre,
                rtn: rtn,
                contacto: contacto,
                direccion: direccion,
                celular: celular,
                estado: estado
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', 'Éxito', 'Proveedor actualizado correctamente');
                editModal.classList.add('hidden');
                cargarProveedores();
            } else {
                showNotification('error', 'Error', data.message);
            }
        })
        .catch(error => {
            console.error('Error al guardar proveedor:', error);
            showNotification('error', 'Error', 'No se pudo actualizar el proveedor');
        });
    }
    
    // Función para eliminar proveedor
    window.eliminarProveedor = function(id) {
        if (confirm('¿Está seguro de que desea eliminar este proveedor?')) {
            fetch(`eliminar_proveedor.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification('success', 'Éxito', 'Proveedor eliminado correctamente');
                        cargarProveedores();
                    } else {
                        showNotification('error', 'Error', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error al eliminar proveedor:', error);
                    showNotification('error', 'Error', 'No se pudo eliminar el proveedor');
                });
        }
    }
    
    // Función para mostrar notificaciones
    function showNotification(type, title, message) {
        const notification = document.getElementById('notification');
        const notificationIcon = document.getElementById('notificationIcon');
        const notificationTitle = document.getElementById('notificationTitle');
        const notificationMessage = document.getElementById('notificationMessage');
        
        // Configurar notificación
        notification.className = `notification ${type}`;
        notificationTitle.textContent = title;
        notificationMessage.textContent = message;
        
        // Configurar icono
        if (type === 'success') {
            notificationIcon.textContent = 'check_circle';
        } else if (type === 'error') {
            notificationIcon.textContent = 'error';
        } else if (type === 'info') {
            notificationIcon.textContent = 'info';
        }
        
        // Mostrar notificación
        notification.classList.add('show');
        
        // Ocultar después de 5 segundos
        setTimeout(() => {
            notification.classList.remove('show');
        }, 5000);
    }
    
    // Función debounce para limitar la frecuencia de ejecución
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
    
    // Cargar proveedores al iniciar la página
    cargarProveedores();



});

    function RedirigirCrearProveedor(){
      window.location.href = "crear_proveedor.php";
    }

</script>
</body>
</html>