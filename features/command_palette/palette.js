/**
 * Command Palette - Acceso rápido a funciones con Ctrl+K
 */

class CommandPalette {
    constructor() {
        this.isOpen = false;
        this.commands = [];
        this.filteredCommands = [];
        this.selectedIndex = 0;
        this.init();
    }

    init() {
        this.createPaletteHTML();
        this.registerCommands();
        this.attachEventListeners();
    }

    createPaletteHTML() {
        const html = `
            <div id="commandPalette" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-start justify-center pt-32">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                    <!-- Search Input -->
                    <div class="p-4 border-b border-slate-200 dark:border-slate-700">
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-slate-400">search</span>
                            <input 
                                type="text" 
                                id="commandSearch" 
                                placeholder="Buscar acciones... (Ctrl+K)"
                                class="flex-1 bg-transparent border-none outline-none text-slate-900 dark:text-white placeholder-slate-400"
                                autocomplete="off"
                            />
                            <kbd class="px-2 py-1 text-xs bg-slate-100 dark:bg-slate-800 rounded border border-slate-300 dark:border-slate-600">ESC</kbd>
                        </div>
                    </div>

                    <!-- Commands List -->
                    <div id="commandsList" class="max-h-96 overflow-y-auto p-2">
                        <!-- Commands will be inserted here -->
                    </div>

                    <!-- Footer -->
                    <div class="p-3 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                        <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                            <div class="flex items-center gap-4">
                                <span><kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 rounded border">↑↓</kbd> Navegar</span>
                                <span><kbd class="px-1.5 py-0.5 bg-white dark:bg-slate-700 rounded border">Enter</kbd> Seleccionar</span>
                            </div>
                            <span>⚡ Acceso Rápido</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
    }

    registerCommands() {
        this.commands = [
            // Navegación
            { icon: 'home', name: 'Ir al Inicio', category: 'Navegación', action: () => window.location.href = 'index.php' },
            { icon: 'show_chart', name: 'Dashboard Analítico', category: 'Navegación', action: () => window.location.href = 'features/dashboard/analytics.php' },
            { icon: 'inventory_2', name: 'Inventario', category: 'Navegación', action: () => window.location.href = 'inventario.php' },
            { icon: 'shopping_cart', name: 'Nueva Venta', category: 'Navegación', action: () => window.location.href = 'nueva_venta.php' },
            { icon: 'receipt_long', name: 'Reportes de Caja', category: 'Navegación', action: () => window.location.href = 'reportes_caja.php' },
            { icon: 'people', name: 'Clientes', category: 'Navegación', action: () => window.location.href = 'clientes.php' },
            { icon: 'local_shipping', name: 'Proveedores', category: 'Navegación', action: () => window.location.href = 'proveedores.php' },

            // Acciones Rápidas
            { icon: 'add_circle', name: 'Crear Producto', category: 'Acciones', action: () => window.location.href = 'creacion_de_producto.php' },
            { icon: 'person_add', name: 'Crear Usuario', category: 'Acciones', action: () => window.location.href = 'crear_usuarios.php' },
            { icon: 'upload', name: 'Subir Archivo', category: 'Acciones', action: () => window.location.href = 'mobile_upload.php' },

            // Configuración
            { icon: 'settings', name: 'Configuración', category: 'Sistema', action: () => window.location.href = 'configuracion.php' },
            { icon: 'account_circle', name: 'Mi Perfil', category: 'Sistema', action: () => window.location.href = 'perfil_usuario.php' },
            { icon: 'dark_mode', name: 'Cambiar Tema', category: 'Sistema', action: () => this.toggleTheme() },
            { icon: 'logout', name: 'Cerrar Sesión', category: 'Sistema', action: () => window.location.href = 'logout.php' },

            // Reportes
            { icon: 'assessment', name: 'Reporte de Ventas', category: 'Reportes', action: () => window.location.href = 'reporte_ventas.php' },
            { icon: 'account_balance_wallet', name: 'Lista de Deudas', category: 'Reportes', action: () => window.location.href = 'lista_deudas.php' },

            // Caja
            { icon: 'point_of_sale', name: 'Apertura de Caja', category: 'Caja', action: () => window.location.href = 'apertura_caja.php' },
            { icon: 'calculate', name: 'Arqueo de Caja', category: 'Caja', action: () => window.location.href = 'arqueo_caja.php' },
            { icon: 'lock', name: 'Cierre de Caja', category: 'Caja', action: () => window.location.href = 'cierre_caja.php' },
        ];
    }

    attachEventListeners() {
        // Ctrl+K o Cmd+K para abrir
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.toggle();
            }

            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });

        // Click fuera para cerrar
        document.getElementById('commandPalette').addEventListener('click', (e) => {
            if (e.target.id === 'commandPalette') {
                this.close();
            }
        });

        // Búsqueda
        const searchInput = document.getElementById('commandSearch');
        searchInput.addEventListener('input', (e) => {
            this.filter(e.target.value);
        });

        // Navegación con teclado
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.selectNext();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectPrevious();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                this.executeSelected();
            }
        });
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.isOpen = true;
        document.getElementById('commandPalette').classList.remove('hidden');
        document.getElementById('commandSearch').value = '';
        document.getElementById('commandSearch').focus();
        this.filter('');
    }

    close() {
        this.isOpen = false;
        document.getElementById('commandPalette').classList.add('hidden');
        this.selectedIndex = 0;
    }

    filter(query) {
        query = query.toLowerCase();

        if (!query) {
            this.filteredCommands = this.commands;
        } else {
            this.filteredCommands = this.commands.filter(cmd =>
                cmd.name.toLowerCase().includes(query) ||
                cmd.category.toLowerCase().includes(query)
            );
        }

        this.selectedIndex = 0;
        this.render();
    }

    render() {
        const commandsList = document.getElementById('commandsList');

        if (this.filteredCommands.length === 0) {
            commandsList.innerHTML = `
                <div class="p-8 text-center text-slate-500 dark:text-slate-400">
                    <span class="material-symbols-outlined text-4xl mb-2 opacity-50">search_off</span>
                    <p>No se encontraron comandos</p>
                </div>
            `;
            return;
        }

        // Agrupar por categoría
        const grouped = {};
        this.filteredCommands.forEach(cmd => {
            if (!grouped[cmd.category]) {
                grouped[cmd.category] = [];
            }
            grouped[cmd.category].push(cmd);
        });

        let html = '';
        let globalIndex = 0;

        Object.keys(grouped).forEach(category => {
            html += `
                <div class="px-2 py-1 text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                    ${category}
                </div>
            `;

            grouped[category].forEach(cmd => {
                const isSelected = globalIndex === this.selectedIndex;
                html += `
                    <div 
                        class="command-item flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer transition ${isSelected ? 'bg-blue-100 dark:bg-blue-900/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800'
                    }"
                        data-index="${globalIndex}"
                    >
                        <span class="material-symbols-outlined text-slate-600 dark:text-slate-400">${cmd.icon}</span>
                        <span class="flex-1 text-slate-900 dark:text-white">${cmd.name}</span>
                        ${isSelected ? '<span class="material-symbols-outlined text-blue-600">arrow_forward</span>' : ''}
                    </div>
                `;
                globalIndex++;
            });
        });

        commandsList.innerHTML = html;

        // Agregar event listeners a los items
        document.querySelectorAll('.command-item').forEach(item => {
            item.addEventListener('click', () => {
                const index = parseInt(item.dataset.index);
                this.selectedIndex = index;
                this.executeSelected();
            });
        });

        // Scroll al elemento seleccionado
        const selected = commandsList.querySelector(`[data-index="${this.selectedIndex}"]`);
        if (selected) {
            selected.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    selectNext() {
        this.selectedIndex = (this.selectedIndex + 1) % this.filteredCommands.length;
        this.render();
    }

    selectPrevious() {
        this.selectedIndex = this.selectedIndex === 0 ? this.filteredCommands.length - 1 : this.selectedIndex - 1;
        this.render();
    }

    executeSelected() {
        if (this.filteredCommands[this.selectedIndex]) {
            this.filteredCommands[this.selectedIndex].action();
            this.close();
        }
    }

    toggleTheme() {
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        this.close();
    }
}

// Inicializar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.commandPalette = new CommandPalette();
    });
} else {
    window.commandPalette = new CommandPalette();
}
