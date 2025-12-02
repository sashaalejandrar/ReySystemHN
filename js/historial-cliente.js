// ===================================
// HISTORIAL RÁPIDO DE CLIENTE
// ===================================

class HistorialCliente {
    constructor() {
        this.clienteActual = null;
        this.inicializar();
    }

    inicializar() {
        // Buscar campo de teléfono en nueva_venta.php
        const telefonoInput = document.getElementById('clienteTelefonoRapido');
        if (telefonoInput) {
            telefonoInput.addEventListener('input', this.debounce((e) => {
                this.buscarCliente(e.target.value);
            }, 800));
        }

        console.log('✅ Historial Rápido de Cliente inicializado');
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    async buscarCliente(telefono) {
        // Limpiar si el teléfono está vacío
        if (!telefono || telefono.length < 8) {
            this.limpiarHistorial();
            return;
        }

        try {
            const response = await fetch(`api/historial_cliente.php?telefono=${encodeURIComponent(telefono)}`);
            const data = await response.json();

            if (data.success) {
                this.clienteActual = data;
                this.mostrarHistorial(data);

                // Si es cliente frecuente, mostrar alerta
                if (data.estadisticas.es_frecuente) {
                    this.alertaClienteFrecuente(data);
                }
            } else {
                this.limpiarHistorial();
                if (!data.nuevo_cliente) {
                    console.log('Cliente no encontrado');
                }
            }
        } catch (error) {
            console.error('Error buscando cliente:', error);
        }
    }

    mostrarHistorial(data) {
        const container = document.getElementById('historialClienteContainer');
        if (!container) return;

        const { cliente, estadisticas, ultimas_compras, productos_favoritos } = data;

        // Badge de cliente frecuente
        const badgeFrecuente = estadisticas.es_frecuente
            ? '<span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full text-xs font-bold"><span class="material-symbols-outlined text-sm">star</span>Cliente Frecuente</span>'
            : '';

        // Badge de deuda
        const badgeDeuda = estadisticas.deuda_pendiente > 0
            ? `<span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-full text-xs font-bold"><span class="material-symbols-outlined text-sm">warning</span>Deuda: L ${estadisticas.deuda_pendiente.toFixed(2)}</span>`
            : '';

        container.innerHTML = `
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/10 rounded-lg p-4 border-2 border-blue-200 dark:border-blue-700 space-y-3">
                <!-- Header -->
                <div class="flex items-start justify-between">
                    <div>
                        <h4 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <span class="material-symbols-outlined text-blue-600">person</span>
                            ${cliente.nombre}
                        </h4>
                        <p class="text-xs text-slate-600 dark:text-slate-400">${cliente.celular}</p>
                    </div>
                    <div class="flex flex-col gap-1">
                        ${badgeFrecuente}
                        ${badgeDeuda}
                    </div>
                </div>
                
                <!-- Estadísticas -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="bg-white dark:bg-slate-800 rounded p-2 text-center">
                        <p class="text-xs text-slate-600 dark:text-slate-400">Compras</p>
                        <p class="text-lg font-bold text-blue-600">${estadisticas.num_compras}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded p-2 text-center">
                        <p class="text-xs text-slate-600 dark:text-slate-400">Total Gastado</p>
                        <p class="text-lg font-bold text-green-600">L ${estadisticas.total_gastado.toFixed(2)}</p>
                    </div>
                    <div class="bg-white dark:bg-slate-800 rounded p-2 text-center">
                        <p class="text-xs text-slate-600 dark:text-slate-400">Última Compra</p>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">${estadisticas.dias_desde_ultima !== null ? `Hace ${estadisticas.dias_desde_ultima}d` : 'N/A'}</p>
                    </div>
                </div>
                
                <!-- Productos Favoritos -->
                ${productos_favoritos.length > 0 ? `
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <h5 class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">favorite</span>
                        Productos Favoritos
                    </h5>
                    <div class="space-y-1">
                        ${productos_favoritos.slice(0, 3).map(p => `
                            <div class="flex justify-between text-xs">
                                <span class="text-slate-600 dark:text-slate-400 truncate">${p.nombre}</span>
                                <span class="text-blue-600 font-semibold">${p.veces}x</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
                ` : ''}
                
                <!-- Últimas Compras -->
                ${ultimas_compras.length > 0 ? `
                <div class="bg-white dark:bg-slate-800 rounded p-3">
                    <h5 class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-2 flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">history</span>
                        Últimas Compras
                    </h5>
                    <div class="space-y-1">
                        ${ultimas_compras.slice(0, 3).map(c => {
            const fecha = new Date(c.fecha);
            const fechaStr = fecha.toLocaleDateString('es-HN', { month: 'short', day: 'numeric' });
            return `
                                <div class="flex justify-between text-xs">
                                    <span class="text-slate-600 dark:text-slate-400">${fechaStr} - ${c.num_productos} prod.</span>
                                    <span class="text-green-600 font-semibold">L ${c.total.toFixed(2)}</span>
                                </div>
                            `;
        }).join('')}
                    </div>
                </div>
                ` : ''}
            </div>
        `;

        container.classList.remove('hidden');
    }

    limpiarHistorial() {
        const container = document.getElementById('historialClienteContainer');
        if (container) {
            container.innerHTML = '';
            container.classList.add('hidden');
        }
        this.clienteActual = null;
    }

    alertaClienteFrecuente(data) {
        if (window.alertSystem) {
            window.alertSystem.alerta(
                '⭐ Cliente Frecuente',
                `${data.cliente.nombre} ha realizado ${data.estadisticas.num_compras} compras. Total gastado: L ${data.estadisticas.total_gastado.toFixed(2)}`,
                'cliente_frecuente',
                {
                    id: 'frecuente-' + data.cliente.id
                }
            );
        }
    }
}

// Inicializar
document.addEventListener('DOMContentLoaded', function () {
    window.historialCliente = new HistorialCliente();
});

console.log('✅ Historial Rápido de Cliente cargado');
