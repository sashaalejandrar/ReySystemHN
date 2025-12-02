// usuarios.js - Manejo del frontend
class GestorUsuarios {
    constructor() {
        this.paginaActual = 1;
        this.limite = 10;
        this.busqueda = '';
        this.estadoFiltro = '';
        this.init();
    }

    init() {
        // NO cargar usuarios al inicio porque ya vienen desde PHP
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Búsqueda
        const inputBusqueda = document.getElementById('buscarUsuario');
        if (inputBusqueda) {
            let timeoutBusqueda;
            inputBusqueda.addEventListener('input', (e) => {
                clearTimeout(timeoutBusqueda);
                timeoutBusqueda = setTimeout(() => {
                    this.busqueda = e.target.value;
                    this.paginaActual = 1;
                    this.cargarUsuarios();
                }, 500);
            });
        }

        // Botón filtrar
        const btnFiltrar = document.getElementById('btnFiltrar');
        if (btnFiltrar) {
            btnFiltrar.addEventListener('click', () => this.mostrarFiltros());
        }

        // Botón nuevo usuario
        const btnNuevo = document.getElementById('btnNuevoUsuario');
        if (btnNuevo) {
            btnNuevo.addEventListener('click', () => this.mostrarFormulario());
        }

        // Paginación
        this.setupPaginacion();
    }

    setupPaginacion() {
        const btnAnterior = document.getElementById('btnAnterior');
        const btnSiguiente = document.getElementById('btnSiguiente');

        if (btnAnterior) {
            btnAnterior.addEventListener('click', (e) => {
                e.preventDefault();
                if (this.paginaActual > 1) {
                    this.paginaActual--;
                    this.cargarUsuarios();
                }
            });
        }

        if (btnSiguiente) {
            btnSiguiente.addEventListener('click', (e) => {
                e.preventDefault();
                this.paginaActual++;
                this.cargarUsuarios();
            });
        }
    }

    async cargarUsuarios() {
        try {
            const params = new URLSearchParams({
                accion: 'listar',
                pagina: this.paginaActual,
                limite: this.limite,
                busqueda: this.busqueda,
                estado: this.estadoFiltro
            });

            const response = await fetch(`usuarios_ajax.php?${params}`);
            
            // Verificar si la respuesta es válida
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const text = await response.text();
            console.log('Respuesta del servidor:', text); // Para debug
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Error al parsear JSON:', text);
                throw new Error('Respuesta inválida del servidor');
            }

            if (data.success) {
                this.renderizarUsuarios(data.usuarios);
                this.actualizarPaginacion(data.pagina, data.total_paginas, data.total);
            } else {
                this.mostrarError(data.message || 'Error desconocido');
            }
        } catch (error) {
            console.error('Error completo:', error);
            this.mostrarError('Error al cargar los usuarios: ' + error.message);
        }
    }

    renderizarUsuarios(usuarios) {
        const tbody = document.getElementById('tablaUsuarios');
        if (!tbody) return;

        if (usuarios.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-slate-500 dark:text-slate-400">
                        No se encontraron usuarios
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = usuarios.map(u => `
            <tr class="border-b border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-900/20">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center gap-3">
                        <img alt="${u.Nombre} avatar" 
                             class="w-10 h-10 rounded-full object-cover" 
                             src="${u.Perfil || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(u.Nombre + ' ' + u.Apellido)}"/>
                        <div>
                            <div class="font-medium text-slate-900 dark:text-white">${u.Nombre} ${u.Apellido}</div>
                            <div class="text-slate-500 dark:text-slate-400">${u.Email}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">${u.Rol}</td>
                <td class="px-6 py-4">
                    ${this.obtenerBadgeEstado(u.Estado_Online)}
                </td>
                <td class="px-6 py-4">${u.tiempo_actividad}</td>
                <td class="px-6 py-4 text-right">
                    <div class="relative inline-block">
                        <button onclick="gestorUsuarios.mostrarMenu(${u.Id}, event)" 
                                class="p-2 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700">
                            <span class="material-symbols-outlined text-base">more_horiz</span>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    obtenerBadgeEstado(estado) {
        const estados = {
            'Activo': 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
            'Pendiente': 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
            'Inactivo': 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300'
        };

        const clase = estados[estado] || 'bg-gray-100 dark:bg-gray-900/50 text-gray-800 dark:text-gray-300';

        return `
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${clase}">
                ${estado}
            </span>
        `;
    }

    actualizarPaginacion(paginaActual, totalPaginas, total) {
        const textoPaginacion = document.getElementById('textoPaginacion');
        if (textoPaginacion) {
            const inicio = (paginaActual - 1) * this.limite + 1;
            const fin = Math.min(paginaActual * this.limite, total);
            
            textoPaginacion.innerHTML = `
                Mostrando <span class="font-medium text-slate-700 dark:text-slate-200">${inicio}</span> a 
                <span class="font-medium text-slate-700 dark:text-slate-200">${fin}</span> de 
                <span class="font-medium text-slate-700 dark:text-slate-200">${total}</span> resultados
            `;
        }

        const btnAnterior = document.getElementById('btnAnterior');
        const btnSiguiente = document.getElementById('btnSiguiente');

        if (btnAnterior) {
            btnAnterior.disabled = paginaActual === 1;
            btnAnterior.classList.toggle('opacity-50', paginaActual === 1);
            btnAnterior.classList.toggle('cursor-not-allowed', paginaActual === 1);
        }

        if (btnSiguiente) {
            btnSiguiente.disabled = paginaActual >= totalPaginas;
            btnSiguiente.classList.toggle('opacity-50', paginaActual >= totalPaginas);
            btnSiguiente.classList.toggle('cursor-not-allowed', paginaActual >= totalPaginas);
        }
    }

    mostrarMenu(idUsuario, event) {
        event.stopPropagation();
        
        // Cerrar menú existente
        const menuExistente = document.getElementById('menu-acciones');
        if (menuExistente) {
            menuExistente.remove();
        }

        const menu = document.createElement('div');
        menu.id = 'menu-acciones';
        menu.className = 'absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 z-50';
        menu.innerHTML = `
            <div class="py-1">
                <button onclick="gestorUsuarios.editarUsuario(${idUsuario})" 
                        class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">edit</span>
                    Editar
                </button>
                <button onclick="gestorUsuarios.cambiarClave(${idUsuario})" 
                        class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">lock</span>
                    Cambiar Contraseña
                </button>
                <button onclick="gestorUsuarios.cambiarEstado(${idUsuario})" 
                        class="w-full text-left px-4 py-2 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">toggle_on</span>
                    Cambiar Estado
                </button>
                <hr class="my-1 border-slate-200 dark:border-slate-700">
                <button onclick="gestorUsuarios.eliminarUsuario(${idUsuario})" 
                        class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center gap-2">
                    <span class="material-symbols-outlined text-base">delete</span>
                    Eliminar
                </button>
            </div>
        `;

        event.target.closest('td').appendChild(menu);

        // Cerrar el menú al hacer clic fuera
        setTimeout(() => {
            document.addEventListener('click', () => menu.remove(), { once: true });
        }, 100);
    }

    async editarUsuario(id) {
        try {
            const response = await fetch(`usuarios_ajax.php?accion=obtener&id=${id}`);
            const data = await response.json();

            if (data.success) {
                this.mostrarFormularioEdicion(data.usuario);
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            console.error('Error al obtener usuario:', error);
            this.mostrarError('Error al cargar los datos del usuario');
        }
    }

    mostrarFormularioEdicion(usuario) {
        const modal = this.crearModal('Editar Usuario', `
            <form id="formEditarUsuario" class="space-y-4">
                <input type="hidden" name="id" value="${usuario.Id}">
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nombre</label>
                        <input type="text" name="nombre" value="${usuario.Nombre}" required
                               class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Apellido</label>
                        <input type="text" name="apellido" value="${usuario.Apellido}" required
                               class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Email</label>
                    <input type="email" name="email" value="${usuario.Email}" required
                           class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Celular</label>
                        <input type="text" name="celular" value="${usuario.Celular || ''}"
                               class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Usuario</label>
                        <input type="text" name="usuario" value="${usuario.Usuario}" required
                               class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Rol</label>
                        <select name="rol" required
                                class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                            <option value="Admin" ${usuario.Rol === 'Admin' ? 'selected' : ''}>Admin</option>
                            <option value="Gerente" ${usuario.Rol === 'Gerente' ? 'selected' : ''}>Gerente</option>
                            <option value="Cajero" ${usuario.Rol === 'Cajero' ? 'selected' : ''}>Cajero</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Cargo</label>
                        <input type="text" name="cargo" value="${usuario.Cargo || ''}"
                               class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Fecha Nacimiento</label>
                        <input type="date" name="fecha_nacimiento" value="${usuario.Fecha_Nacimiento || ''}"
                               class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Estado</label>
                        <select name="estado_online" required
                                class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                            <option value="Activo" ${usuario.Estado_Online === 'Activo' ? 'selected' : ''}>Activo</option>
                            <option value="Pendiente" ${usuario.Estado_Online === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                            <option value="Inactivo" ${usuario.Estado_Online === 'Inactivo' ? 'selected' : ''}>Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="this.closest('.fixed').remove()"
                            class="px-4 py-2 rounded border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded bg-primary text-white hover:bg-blue-600">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        `);

        document.body.appendChild(modal);

        document.getElementById('formEditarUsuario').addEventListener('submit', (e) => {
            e.preventDefault();
            this.guardarUsuario(new FormData(e.target));
        });
    }

    async guardarUsuario(formData) {
        formData.append('accion', 'actualizar');

        try {
            const response = await fetch('usuarios_ajax.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarExito(data.message);
                document.querySelector('.fixed').remove();
                this.cargarUsuarios();
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            console.error('Error al guardar usuario:', error);
            this.mostrarError('Error al guardar los cambios');
        }
    }

    cambiarClave(id) {
        const modal = this.crearModal('Cambiar Contraseña', `
            <form id="formCambiarClave" class="space-y-4">
                <input type="hidden" name="id" value="${id}">
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Nueva Contraseña</label>
                    <input type="password" name="clave" required minlength="6"
                           class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                    <p class="text-xs text-slate-500 mt-1">Mínimo 6 caracteres</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Confirmar Contraseña</label>
                    <input type="password" name="clave_confirmar" required minlength="6"
                           class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="this.closest('.fixed').remove()"
                            class="px-4 py-2 rounded border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded bg-primary text-white hover:bg-blue-600">
                        Cambiar Contraseña
                    </button>
                </div>
            </form>
        `);

        document.body.appendChild(modal);

        document.getElementById('formCambiarClave').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            if (formData.get('clave') !== formData.get('clave_confirmar')) {
                this.mostrarError('Las contraseñas no coinciden');
                return;
            }

            formData.append('accion', 'cambiar_clave');
            formData.delete('clave_confirmar');

            try {
                const response = await fetch('usuarios_ajax.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.mostrarExito(data.message);
                    document.querySelector('.fixed').remove();
                } else {
                    this.mostrarError(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                this.mostrarError('Error al cambiar la contraseña');
            }
        });
    }

    async cambiarEstado(id) {
        // Obtener estado actual
        const response = await fetch(`usuarios_ajax.php?accion=obtener&id=${id}`);
        const data = await response.json();
        
        if (!data.success) return;

        const estadoActual = data.usuario.Estado_Online;
        const estados = ['Activo', 'Pendiente', 'Inactivo'];
        const nuevoEstado = estados[(estados.indexOf(estadoActual) + 1) % estados.length];

        if (confirm(`¿Cambiar estado de ${estadoActual} a ${nuevoEstado}?`)) {
            const formData = new FormData();
            formData.append('accion', 'cambiar_estado');
            formData.append('id', id);
            formData.append('estado', nuevoEstado);

            try {
                const response = await fetch('usuarios_ajax.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    this.mostrarExito(data.message);
                    this.cargarUsuarios();
                } else {
                    this.mostrarError(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                this.mostrarError('Error al cambiar el estado');
            }
        }
    }

    async eliminarUsuario(id) {
        if (!confirm('¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.')) {
            return;
        }

        const formData = new FormData();
        formData.append('accion', 'eliminar');
        formData.append('id', id);

        try {
            const response = await fetch('usuarios_ajax.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.mostrarExito(data.message);
                this.cargarUsuarios();
            } else {
                this.mostrarError(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            this.mostrarError('Error al eliminar el usuario');
        }
    }

    crearModal(titulo, contenido) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50 p-4';
        modal.innerHTML = `
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6 border-b border-slate-200 dark:border-slate-700">
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">${titulo}</h3>
                </div>
                <div class="p-6">
                    ${contenido}
                </div>
            </div>
        `;

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.remove();
        });

        return modal;
    }

    mostrarExito(mensaje) {
        this.mostrarNotificacion(mensaje, 'success');
    }

    mostrarError(mensaje) {
        this.mostrarNotificacion(mensaje, 'error');
    }

    mostrarNotificacion(mensaje, tipo) {
        const notif = document.createElement('div');
        notif.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
            tipo === 'success' ? 'bg-green-500' : 'bg-red-500'
        } text-white`;
        notif.textContent = mensaje;
        
        document.body.appendChild(notif);
        
        setTimeout(() => notif.remove(), 3000);
    }

    mostrarFiltros() {
        const modal = this.crearModal('Filtros', `
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Estado</label>
                    <select id="filtroEstado"
                            class="w-full px-3 py-2 rounded border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 focus:ring-2 focus:ring-primary">
                        <option value="">Todos</option>
                        <option value="Activo" ${this.estadoFiltro === 'Activo' ? 'selected' : ''}>Activo</option>
                        <option value="Pendiente" ${this.estadoFiltro === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                        <option value="Inactivo" ${this.estadoFiltro === 'Inactivo' ? 'selected' : ''}>Inactivo</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <button type="button" onclick="gestorUsuarios.limpiarFiltros(); this.closest('.fixed').remove()"
                            class="px-4 py-2 rounded border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700">
                        Limpiar
                    </button>
                    <button type="button" onclick="gestorUsuarios.aplicarFiltros(); this.closest('.fixed').remove()"
                            class="px-4 py-2 rounded bg-primary text-white hover:bg-blue-600">
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        `);

        document.body.appendChild(modal);
    }

    aplicarFiltros() {
        this.estadoFiltro = document.getElementById('filtroEstado').value;
        this.paginaActual = 1;
        this.cargarUsuarios();
    }

    limpiarFiltros() {
        this.estadoFiltro = '';
        this.paginaActual = 1;
        this.cargarUsuarios();
    }

    mostrarFormulario() {
        window.location.href = "crear_usuarios.php";
     }
}

// Inicializar cuando el DOM esté listo
let gestorUsuarios;
document.addEventListener('DOMContentLoaded', () => {
    gestorUsuarios = new GestorUsuarios();
});