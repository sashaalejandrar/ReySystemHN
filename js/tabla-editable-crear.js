// ===================================
// SISTEMA DE NOTIFICACIONES MODAL
// ===================================
function mostrarNotificacion(tipo, titulo, mensaje) {
    const modal = document.getElementById('modalNotificacion');
    const icon = document.getElementById('notifIcon');
    const tituloEl = document.getElementById('notifTitulo');
    const mensajeEl = document.getElementById('notifMensaje');
    const header = document.getElementById('notifHeader');

    // Configurar según el tipo
    const configs = {
        success: {
            icon: 'check_circle',
            iconClass: 'text-green-600',
            headerClass: 'bg-green-50 dark:bg-green-900/20'
        },
        error: {
            icon: 'error',
            iconClass: 'text-red-600',
            headerClass: 'bg-red-50 dark:bg-red-900/20'
        },
        warning: {
            icon: 'warning',
            iconClass: 'text-yellow-600',
            headerClass: 'bg-yellow-50 dark:bg-yellow-900/20'
        },
        info: {
            icon: 'info',
            iconClass: 'text-blue-600',
            headerClass: 'bg-blue-50 dark:bg-blue-900/20'
        }
    };

    const config = configs[tipo] || configs.info;

    icon.textContent = config.icon;
    icon.className = `material-symbols-outlined text-4xl ${config.iconClass}`;
    header.className = `p-6 border-b border-gray-200 dark:border-gray-700 ${config.headerClass}`;
    tituloEl.textContent = titulo;
    mensajeEl.textContent = mensaje;

    modal.classList.remove('hidden');
}

function cerrarNotificacion() {
    document.getElementById('modalNotificacion').classList.add('hidden');
    // Restaurar el header para otras notificaciones
    document.getElementById('notifHeader').style.display = 'block';
}

// ===================================
// TABLA EDITABLE PARA CREACIÓN DE PRODUCTOS - VERSIÓN COMPLETA
// ===================================

class TablaProductosCrear {
    constructor() {
        this.productos = [];
        this.resumen = document.getElementById('resumenTabla');
    }

    agregarProducto(producto = {}) {
        const nuevoProducto = {
            id: Date.now() + Math.random(),
            codigo: producto.codigo || '',
            nombre: producto.nombre || '',
            descripcionCorta: producto.descripcionCorta || '',
            marca: producto.marca || '',
            descripcion: producto.descripcion || '',
            categoria: producto.categoria || document.getElementById('categoriaDefecto')?.value || '',
            tipoEmpaque: producto.tipoEmpaque || 'Unidad',
            unidadesPorEmpaque: producto.unidadesPorEmpaque || 1,
            // Sistema de packaging multinivel
            tieneSubContenido: producto.tieneSubContenido || false,
            contenido: producto.contenido || 0,
            subContenido: producto.subContenido || 0,
            unidadesTotales: producto.unidadesTotales || 0,
            formatoPresentacion: producto.formatoPresentacion || '1x0',
            // Costos y precios
            costoEmpaque: producto.costoEmpaque || 0,
            costoUnidad: producto.costoUnidad || 0,
            precioUnidad: producto.precioUnidad || 0,
            precioEmpaque: producto.precioEmpaque || 0,
            margen: producto.margen || 0,
            proveedor: producto.proveedor || document.getElementById('proveedorDefecto')?.value || '',
            direccionProveedor: producto.direccionProveedor || '',
            contactoProveedor: producto.contactoProveedor || '',
            valido: false,
            errores: []
        };

        this.productos.push(nuevoProducto);
        this.validarProducto(this.productos.length - 1);
        this.renderizar();
    }

    eliminarProducto(id) {
        this.productos = this.productos.filter(p => p.id !== id);
        this.renderizar();
    }

    actualizarCampo(id, campo, valor) {
        const index = this.productos.findIndex(p => p.id === id);
        if (index !== -1) {
            this.productos[index][campo] = valor;

            // Auto-configurar packaging según el tipo de empaque
            if (campo === 'tipoEmpaque') {
                this.autoConfigurarPackaging(index, valor);
            }

            // Auto-calcular campos relacionados, pasando el campo modificado
            this.autoCalcular(index, campo);
            this.validarProducto(index);
            this.renderizar();
        }
    }

    autoConfigurarPackaging(index, tipoEmpaque) {
        const p = this.productos[index];
        const tipo = tipoEmpaque.toLowerCase();

        // Configuraciones predefinidas para tipos comunes
        const configuraciones = {
            'six pack': { tieneSubContenido: false, unidadesPorEmpaque: 6 },
            'sixpack': { tieneSubContenido: false, unidadesPorEmpaque: 6 },
            'six-pack': { tieneSubContenido: false, unidadesPorEmpaque: 6 },
            'display': { tieneSubContenido: true, contenido: 12, subContenido: 12 },
            'pallet': { tieneSubContenido: true, contenido: 48, subContenido: 12 },
            'caja': { tieneSubContenido: false, unidadesPorEmpaque: 24 },
            'docena': { tieneSubContenido: false, unidadesPorEmpaque: 12 },
            'unidad': { tieneSubContenido: false, unidadesPorEmpaque: 1 }
        };

        const config = configuraciones[tipo];
        if (config) {
            p.tieneSubContenido = config.tieneSubContenido;
            if (config.tieneSubContenido) {
                p.contenido = config.contenido;
                p.subContenido = config.subContenido;
            } else {
                p.unidadesPorEmpaque = config.unidadesPorEmpaque;
            }
        }
    }

    autoCalcular(index, campoModificado = null) {
        const p = this.productos[index];

        // Auto-calcular descripción corta si está vacía
        if (!p.descripcionCorta && p.nombre) {
            p.descripcionCorta = p.nombre.substring(0, 100);
        }

        // SISTEMA DE PACKAGING MULTINIVEL
        // Calcular unidades totales según el modo
        if (p.tieneSubContenido) {
            // Modo SubContenido: Contenido × SubContenido
            if (p.contenido > 0 && p.subContenido > 0) {
                p.unidadesTotales = p.contenido * p.subContenido;
                p.formatoPresentacion = `1x${p.contenido}x${p.subContenido}`;
            } else {
                p.unidadesTotales = 0;
                p.formatoPresentacion = '1x0x0';
            }
        } else {
            // Modo Simple: UnidadesPorEmpaque
            p.unidadesTotales = p.unidadesPorEmpaque > 0 ? p.unidadesPorEmpaque : 0;
            p.formatoPresentacion = `1x${p.unidadesTotales}`;
        }

        // PASO 1: Auto-calcular costo por unidad usando UNIDADES TOTALES
        if (p.costoEmpaque && p.unidadesTotales && p.unidadesTotales > 0) {
            p.costoUnidad = (parseFloat(p.costoEmpaque) / parseInt(p.unidadesTotales)).toFixed(2);
        }

        // PASO 2: Si se modificó el MARGEN, recalcular precios basados en el margen
        if (campoModificado === 'margen' && p.margen && p.costoUnidad > 0) {
            p.precioUnidad = (parseFloat(p.costoUnidad) * (1 + parseFloat(p.margen) / 100)).toFixed(2);
            if (p.unidadesTotales) {
                p.precioEmpaque = (parseFloat(p.precioUnidad) * parseInt(p.unidadesTotales)).toFixed(2);
            }
            return;
        }

        // PASO 3: Si se modificó costoEmpaque, recalcular TODOS los precios con margen por defecto (20%)
        if (campoModificado === 'costoEmpaque' && p.costoUnidad > 0) {
            const margenAUsar = p.margen && p.margen > 0 ? parseFloat(p.margen) : 20;
            p.precioUnidad = (parseFloat(p.costoUnidad) * (1 + margenAUsar / 100)).toFixed(2);
            p.precioEmpaque = (parseFloat(p.precioUnidad) * parseInt(p.unidadesTotales)).toFixed(2);
            p.margen = margenAUsar.toFixed(2);
        }
        else if (p.costoUnidad > 0 && p.precioUnidad == 0 && campoModificado !== 'precioEmpaque') {
            const margenAUsar = 20;
            p.precioUnidad = (parseFloat(p.costoUnidad) * (1 + margenAUsar / 100)).toFixed(2);
            p.margen = margenAUsar.toFixed(2);
        }

        // PASO 4: Si se modificó precioEmpaque manualmente, calcular precioUnidad
        if (campoModificado === 'precioEmpaque' && p.precioEmpaque && p.unidadesTotales && p.unidadesTotales > 0) {
            p.precioUnidad = (parseFloat(p.precioEmpaque) / parseInt(p.unidadesTotales)).toFixed(2);
            if (p.costoUnidad > 0) {
                p.margen = (((parseFloat(p.precioUnidad) - parseFloat(p.costoUnidad)) / parseFloat(p.costoUnidad)) * 100).toFixed(2);
            }
        }
        // PASO 5: Si se modificó precioUnidad manualmente, calcular precioEmpaque y margen
        else if (campoModificado === 'precioUnidad' && p.precioUnidad && p.unidadesTotales) {
            p.precioEmpaque = (parseFloat(p.precioUnidad) * parseInt(p.unidadesTotales)).toFixed(2);
            if (p.costoUnidad > 0) {
                p.margen = (((parseFloat(p.precioUnidad) - parseFloat(p.costoUnidad)) / parseFloat(p.costoUnidad)) * 100).toFixed(2);
            }
        }
        // PASO 6: Si se modificó contenido o subContenido, recalcular todo
        else if ((campoModificado === 'contenido' || campoModificado === 'subContenido' || campoModificado === 'unidadesPorEmpaque') && p.unidadesTotales > 0) {
            // Recalcular costo por unidad
            if (p.costoEmpaque) {
                p.costoUnidad = (parseFloat(p.costoEmpaque) / parseInt(p.unidadesTotales)).toFixed(2);
            }
            // Recalcular precio empaque desde precio unitario
            if (p.precioUnidad) {
                p.precioEmpaque = (parseFloat(p.precioUnidad) * parseInt(p.unidadesTotales)).toFixed(2);
            }
            // Recalcular margen
            if (p.costoUnidad > 0 && p.precioUnidad > 0) {
                p.margen = (((parseFloat(p.precioUnidad) - parseFloat(p.costoUnidad)) / parseFloat(p.costoUnidad)) * 100).toFixed(2);
            }
        }

        // PASO 7: Auto-calcular margen si no se ha calculado aún
        if (p.costoUnidad > 0 && p.precioUnidad > 0 && campoModificado !== 'margen') {
            p.margen = (((parseFloat(p.precioUnidad) - parseFloat(p.costoUnidad)) / parseFloat(p.costoUnidad)) * 100).toFixed(2);
        }
    }

    validarProducto(index) {
        const p = this.productos[index];
        p.errores = [];

        // Validar nombre
        if (!p.nombre || p.nombre.trim() === '') {
            p.errores.push('Nombre requerido');
        }

        // Validar costo por empaque (ahora es el campo principal)
        const costoEmpaque = parseFloat(p.costoEmpaque);
        if (isNaN(costoEmpaque) || costoEmpaque <= 0) {
            p.errores.push('Costo por empaque debe ser mayor a 0');
        }

        // Validar precio unitario
        const precio = parseFloat(p.precioUnidad);
        if (isNaN(precio) || precio <= 0) {
            p.errores.push('Precio unitario debe ser mayor a 0');
        }

        p.valido = p.errores.length === 0;
    }

    getLabelForPackageType(tipoEmpaque) {
        const tipo = tipoEmpaque.toLowerCase();
        const labels = {
            'six pack': 'Cantidad de Six Packs',
            'sixpack': 'Cantidad de Six Packs',
            'caja': 'Cantidad de Cajas',
            'display': 'Cantidad de Displays',
            'pallet': 'Cantidad de Pallets',
            'docena': 'Cantidad de Docenas',
            'unidad': 'Cantidad de Unidades'
        };
        return labels[tipo] || 'Unidades por Empaque';
    }

    getHintForPackageType(tipoEmpaque, cantidad) {
        const tipo = tipoEmpaque.toLowerCase();
        const configs = {
            'six pack': 6,
            'sixpack': 6,
            'caja': 24,
            'docena': 12,
            'unidad': 1
        };

        const unidadesPorPaquete = configs[tipo];
        if (unidadesPorPaquete && cantidad > 0) {
            const total = cantidad * unidadesPorPaquete;
            return `<p class="text-xs text-primary mt-1">= ${total} unidades totales (${cantidad} × ${unidadesPorPaquete})</p>`;
        }
        return '';
    }

    renderizar() {
        const contenedor = document.getElementById('contenedorProductos');

        if (this.productos.length === 0) {
            contenedor.innerHTML = `
                <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-8 text-center">
                    <span class="material-symbols-outlined text-6xl text-gray-400 dark:text-gray-600 mb-4">inventory_2</span>
                    <p class="text-gray-500 dark:text-gray-400 text-lg">No hay productos</p>
                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-2">Importa un CSV o agrega productos manualmente</p>
                </div>
            `;
        } else {
            contenedor.innerHTML = this.productos.map(p => this.renderizarCard(p)).join('');
        }

        this.actualizarResumen();
    }

    renderizarCard(p) {
        const borderClass = p.valido ? 'border-green-500' : 'border-red-500';
        const bgClass = p.valido ? 'bg-green-50 dark:bg-green-900/10' : 'bg-red-50 dark:bg-red-900/10';
        const iconoEstado = p.valido
            ? '<span class="material-symbols-outlined text-green-600 text-2xl">check_circle</span>'
            : `<span class="material-symbols-outlined text-red-600 text-2xl" title="${p.errores.join(', ')}">error</span>`;

        const inputClass = 'form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal';
        const inputReadonlyClass = 'form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-600 dark:text-gray-400 border-slate-300 dark:border-[#324467] bg-gray-100 dark:bg-gray-800 h-14 p-[15px] text-base font-normal leading-normal';

        return `
            <div class="bg-white dark:bg-[#192233] rounded-xl border-2 ${borderClass} p-6 relative">
                <!-- Header con estado y eliminar -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        ${iconoEstado}
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            ${p.nombre || 'Producto Nuevo'}
                        </h3>
                    </div>
                    <button onclick="tabla.eliminarProducto(${p.id})" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors p-2">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
                
                <!-- Grid de campos -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Información Básica -->
                    <div class="md:col-span-2 ${bgClass} p-4 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Información Básica</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Código del Producto</p>
                                <input type="text" value="${p.codigo}" onchange="tabla.actualizarCampo(${p.id}, 'codigo', this.value)" class="${inputClass}" placeholder="Escanea o escribe el código">
                            </label>
                            <div class="flex flex-col md:col-span-2">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Nombre del Producto *</p>
                                <div class="flex gap-2">
                                    <input 
                                        type="text" 
                                        value="${p.nombre}" 
                                        onchange="tabla.actualizarCampo(${p.id}, 'nombre', this.value)" 
                                        class="${inputClass} flex-1" 
                                        placeholder="Nombre del producto" 
                                        id="nombre-${p.id}"
                                        required>
                                    <button 
                                        onclick="tabla.autocompletarConIA(${p.id})" 
                                        class="px-4 py-2 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg hover:from-purple-700 hover:to-blue-700 transition-all flex items-center gap-2 whitespace-nowrap"
                                        title="Autocompletar con IA"
                                        id="btn-ia-${p.id}">
                                        <span class="material-symbols-outlined text-xl">auto_awesome</span>
                                        <span class="hidden sm:inline">IA</span>
                                    </button>
                                </div>
                            </div>
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Marca</p>
                                <input type="text" value="${p.marca}" onchange="tabla.actualizarCampo(${p.id}, 'marca', this.value)" class="${inputClass}" placeholder="Marca del producto">
                            </label>
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Categoría</p>
                                <input 
                                    type="text" 
                                    value="${p.categoria}" 
                                    onchange="tabla.actualizarCampo(${p.id}, 'categoria', this.value)" 
                                    list="categorias-list-${p.id}"
                                    class="${inputClass}" 
                                    placeholder="Selecciona o escribe categoría">
                                <datalist id="categorias-list-${p.id}">
                                    ${window.categoriasGlobales ? window.categoriasGlobales.map(cat => `<option value="${cat}">`).join('') : ''}
                                </datalist>
                            </label>
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Descripción Corta</p>
                                <input type="text" value="${p.descripcionCorta}" onchange="tabla.actualizarCampo(${p.id}, 'descripcionCorta', this.value)" class="${inputClass}" placeholder="Descripción breve">
                            </label>
                            <label class="flex flex-col md:col-span-2">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Descripción Completa</p>
                                <textarea onchange="tabla.actualizarCampo(${p.id}, 'descripcion', this.value)" class="${inputClass} min-h-20" placeholder="Descripción detallada del producto">${p.descripcion}</textarea>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Empaque -->
                    <div class="bg-blue-50 dark:bg-blue-900/10 p-4 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Empaque y Unidades</h4>
                        
                        <!-- Toggle SubContenido -->
                        <div class="flex items-center justify-between mb-3 p-2 bg-white dark:bg-gray-800 rounded border border-blue-200 dark:border-blue-800">
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">SubContenido</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    ${p.tieneSubContenido ? 'checked' : ''}
                                    onchange="tabla.actualizarCampo(${p.id}, 'tieneSubContenido', this.checked)" 
                                    class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/30 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                            </label>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Tipo de Empaque</p>
                                <input 
                                    type="text" 
                                    value="${p.tipoEmpaque}" 
                                    onchange="tabla.actualizarCampo(${p.id}, 'tipoEmpaque', this.value)" 
                                    list="tipos_empaque_list_${p.id}"
                                    class="${inputClass}" 
                                    placeholder="Selecciona o escribe">
                                <datalist id="tipos_empaque_list_${p.id}">
                                    <option value="Unidad">Unidad Individual</option>
                                    <option value="Six Pack">Six Pack (6 unidades)</option>
                                    <option value="Caja">Caja</option>
                                    <option value="Paquete">Paquete</option>
                                    <option value="Display">Display</option>
                                    <option value="Pallet">Pallet</option>
                                    <option value="Bolsa">Bolsa</option>
                                    <option value="Fardo">Fardo</option>
                                    <option value="Cartón">Cartón</option>
                                    <option value="Bulto">Bulto</option>
                                    <option value="Pack">Pack</option>
                                    <option value="Bandeja">Bandeja</option>
                                </datalist>
                            </label>
                            
                            ${!p.tieneSubContenido ? `
                                <label class="flex flex-col col-span-2">
                                    <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">
                                        ${this.getLabelForPackageType(p.tipoEmpaque)}
                                    </p>
                                    <input type="number" value="${p.unidadesPorEmpaque}" onchange="tabla.actualizarCampo(${p.id}, 'unidadesPorEmpaque', this.value)" class="${inputClass}" min="1">
                                    ${this.getHintForPackageType(p.tipoEmpaque, p.unidadesPorEmpaque)}
                                </label>
                            ` : `
                                <label class="flex flex-col">
                                    <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Contenido (Paquetes)</p>
                                    <input type="number" value="${p.contenido}" onchange="tabla.actualizarCampo(${p.id}, 'contenido', this.value)" class="${inputClass}" min="0" placeholder="Ej: 12">
                                </label>
                                <label class="flex flex-col col-span-2">
                                    <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">SubContenido (Unidades/Paquete)</p>
                                    <input type="number" value="${p.subContenido}" onchange="tabla.actualizarCampo(${p.id}, 'subContenido', this.value)" class="${inputClass}" min="0" placeholder="Ej: 12">
                                </label>
                            `}
                        </div>
                        
                        <!-- Indicador de Unidades Totales -->
                        <div class="mt-3 p-2 bg-gradient-to-r from-primary/10 to-blue-500/10 border-l-2 border-primary rounded">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Presentación:</span>
                                <span class="text-sm font-bold text-primary">${p.formatoPresentacion}</span>
                            </div>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs font-medium text-gray-700 dark:text-gray-300">Total Unidades:</span>
                                <span class="text-sm font-bold text-green-600">${p.unidadesTotales}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Costos -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/10 p-4 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Costos</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Costo Por Empaque *</p>
                                <input type="number" value="${p.costoEmpaque}" onchange="tabla.actualizarCampo(${p.id}, 'costoEmpaque', this.value)" class="${inputClass}" step="0.01" min="0" placeholder="0.00" required>
                            </label>
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Costo Por Unidad</p>
                                <input type="number" value="${p.costoUnidad}" class="${inputReadonlyClass}" readonly>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Precios -->
                    <div class="bg-green-50 dark:bg-green-900/10 p-4 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Precios</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Precio Unitario *</p>
                                <input type="number" value="${p.precioUnidad}" onchange="tabla.actualizarCampo(${p.id}, 'precioUnidad', this.value)" class="${inputClass}" step="0.01" min="0" placeholder="0.00" required>
                            </label>
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Precio Empaque</p>
                                <input type="number" value="${p.precioEmpaque}" onchange="tabla.actualizarCampo(${p.id}, 'precioEmpaque', this.value)" class="${inputClass}" step="0.01" min="0" placeholder="0.00">
                            </label>
                            <label class="flex flex-col col-span-2">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Margen (%) - Por defecto 20%</p>
                                <input type="number" value="${p.margen}" onchange="tabla.actualizarCampo(${p.id}, 'margen', this.value)" class="${inputClass}" step="0.01" min="0" placeholder="20">
                            </label>
                        </div>
                    </div>
                    
                    <!-- Proveedor -->
                    <div class="bg-purple-50 dark:bg-purple-900/10 p-4 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Información del Proveedor</h4>
                        <div class="space-y-4">
                            <div class="flex gap-2">
                                <label class="flex flex-col flex-1">
                                    <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Nombre del Proveedor</p>
                                    <input 
                                        type="text" 
                                        value="${p.proveedor}" 
                                        onchange="tabla.actualizarCampo(${p.id}, 'proveedor', this.value)" 
                                        list="proveedores-list-${p.id}"
                                        class="${inputClass}" 
                                        placeholder="Selecciona o escribe proveedor">
                                    <datalist id="proveedores-list-${p.id}">
                                        ${window.proveedoresGlobales ? window.proveedoresGlobales.map(prov => `<option value="${prov.Nombre}">`).join('') : ''}
                                    </datalist>
                                </label>
                                <button 
                                    onclick="tabla.cargarDatosProveedor(${p.id})" 
                                    class="mt-7 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2"
                                    title="Cargar datos del proveedor">
                                    <span class="material-symbols-outlined text-sm">download</span>
                                    Cargar
                                </button>
                            </div>
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Dirección</p>
                                <input type="text" value="${p.direccionProveedor}" onchange="tabla.actualizarCampo(${p.id}, 'direccionProveedor', this.value)" class="${inputClass}" placeholder="Dirección del proveedor">
                            </label>
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Contacto</p>
                                <input type="text" value="${p.contactoProveedor}" onchange="tabla.actualizarCampo(${p.id}, 'contactoProveedor', this.value)" class="${inputClass}" placeholder="Teléfono o email">
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    actualizarResumen() {
        const total = this.productos.length;
        const validos = this.productos.filter(p => p.valido).length;
        const errores = total - validos;

        this.resumen.innerHTML = `
            <span class="font-bold">${total}</span> productos | 
            <span class="text-green-600 font-bold">${validos}</span> válidos | 
            <span class="text-red-600 font-bold">${errores}</span> errores
        `;

        const btnCrear = document.getElementById('btnCrear');
        if (btnCrear) {
            btnCrear.disabled = validos === 0;
        }
    }

    obtenerProductosValidos() {
        return this.productos.filter(p => p.valido);
    }

    limpiar() {
        this.productos = [];
        this.renderizar();
    }

    async cargarDatosProveedor(id) {
        const index = this.productos.findIndex(p => p.id === id);
        if (index === -1) return;

        const producto = this.productos[index];
        const nombreProveedor = producto.proveedor;

        if (!nombreProveedor || nombreProveedor.trim() === '') {
            mostrarNotificacion('warning', 'Proveedor vacío', 'Por favor ingresa el nombre del proveedor primero');
            return;
        }

        try {
            const response = await fetch(`api/obtener_proveedor_por_nombre.php?nombre=${encodeURIComponent(nombreProveedor)}`);
            const data = await response.json();

            if (data.success && data.proveedor) {
                // Cargar datos del proveedor
                this.productos[index].direccionProveedor = data.proveedor.Direccion || '';
                this.productos[index].contactoProveedor = data.proveedor.Contacto || '';
                this.renderizar();
                mostrarNotificacion('success', 'Datos cargados', `Datos del proveedor "${nombreProveedor}" cargados correctamente`);
            } else {
                mostrarNotificacion('info', 'Proveedor no encontrado', `El proveedor "${nombreProveedor}" no existe en la base de datos. Puedes continuar ingresando los datos manualmente.`);
            }
        } catch (error) {
            console.error('Error al cargar proveedor:', error);
            mostrarNotificacion('error', 'Error', 'Error al cargar los datos del proveedor');
        }
    }

    async autocompletarConIA(id) {
        const index = this.productos.findIndex(p => p.id === id);
        if (index === -1) return;

        const producto = this.productos[index];
        const nombre = producto.nombre;
        const codigo = producto.codigo;

        // Validar que haya al menos nombre o código
        if (!nombre && !codigo) {
            mostrarNotificacion('warning', 'Datos insuficientes', 'Por favor ingresa el nombre o código del producto primero');
            return;
        }

        // Mostrar loading en el botón
        const btn = document.getElementById(`btn-ia-${id}`);
        const btnOriginalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `
            <span class="material-symbols-outlined text-xl animate-spin">progress_activity</span>
            <span class="hidden sm:inline">Procesando...</span>
        `;

        try {
            const response = await fetch('api/enriquecer_producto_ia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nombre: nombre,
                    codigo: codigo
                })
            });

            const data = await response.json();

            if (data.success && data.data) {
                // Actualizar campos con los datos obtenidos
                if (data.data.nombre && !producto.nombre) {
                    this.productos[index].nombre = data.data.nombre;
                }
                if (data.data.marca) {
                    this.productos[index].marca = data.data.marca;
                }
                if (data.data.descripcion) {
                    this.productos[index].descripcion = data.data.descripcion;
                }
                if (data.data.descripcionCorta) {
                    this.productos[index].descripcionCorta = data.data.descripcionCorta;
                }
                if (data.data.categoria) {
                    this.productos[index].categoria = data.data.categoria;
                }

                // Validar y renderizar
                this.validarProducto(index);
                this.renderizar();

                // Mostrar notificación de éxito
                const fuente = data.source || 'IA';
                const confianza = data.data.confianza ? ` (${Math.round(data.data.confianza * 100)}% confianza)` : '';
                mostrarNotificacion('success', '¡Datos completados!', `Información obtenida desde ${fuente}${confianza}`);
            } else {
                mostrarNotificacion('warning', 'No se encontró información', data.message || 'No se pudo obtener información del producto. Intenta con otro nombre o código.');
            }
        } catch (error) {
            console.error('Error al autocompletar:', error);
            mostrarNotificacion('error', 'Error', 'Error al conectar con el servicio de IA');
        } finally {
            // Restaurar botón
            btn.disabled = false;
            btn.innerHTML = btnOriginalHTML;
        }
    }
}

// Inicializar tabla
const tabla = new TablaProductosCrear();

// ===================================
// FUNCIONES GLOBALES
// ===================================

function agregarFilaVacia() {
    tabla.agregarProducto();
}

function limpiarTodo() {
    // Crear modal de confirmación personalizado
    const contenido = `
        <div class="text-center">
            <span class="material-symbols-outlined text-yellow-600 text-6xl mb-4">warning</span>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">¿Estás seguro?</h3>
            <p class="text-gray-600 dark:text-gray-400 mb-6">Se eliminarán todos los productos de la lista</p>
            <div class="flex gap-3 justify-center">
                <button onclick="cerrarNotificacion()" class="px-6 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                    Cancelar
                </button>
                <button onclick="cerrarNotificacion(); tabla.limpiar();" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Sí, eliminar todo
                </button>
            </div>
        </div>
    `;

    document.getElementById('notifMensaje').innerHTML = contenido;
    document.getElementById('notifHeader').style.display = 'none';
    document.getElementById('modalNotificacion').classList.remove('hidden');
}

function pegarDesdeExcel() {
    mostrarNotificacion('info', 'Pegar desde Excel', 'Copia las celdas de Excel (Ctrl+C) y pégalas aquí (Ctrl+V)');

    document.addEventListener('paste', function handlePaste(e) {
        const texto = e.clipboardData.getData('text');
        const filas = texto.split('\n');

        filas.forEach(fila => {
            if (fila.trim()) {
                const columnas = fila.split('\t');
                tabla.agregarProducto({
                    nombre: columnas[0] || '',
                    descripcionCorta: columnas[1] || '',
                    marca: columnas[2] || '',
                    descripcion: columnas[3] || '',
                    tipoEmpaque: columnas[4] || 'Unidad',
                    unidadesPorEmpaque: parseInt(columnas[5]) || 1,
                    costoUnidad: parseFloat(columnas[6]) || 0,
                    costoEmpaque: parseFloat(columnas[7]) || 0,
                    precioUnidad: parseFloat(columnas[8]) || 0,
                    precioEmpaque: parseFloat(columnas[9]) || 0,
                    margen: parseFloat(columnas[10]) || 0,
                    proveedor: columnas[11] || '',
                    direccionProveedor: columnas[12] || '',
                    contactoProveedor: columnas[13] || ''
                });
            }
        });

        document.removeEventListener('paste', handlePaste);
        cerrarNotificacion();
    }, { once: true });
}

function mostrarVistaPrevia() {
    const validos = tabla.obtenerProductosValidos();

    if (validos.length === 0) {
        mostrarNotificacion('warning', 'Sin productos', 'No hay productos válidos para crear');
        return;
    }

    const contenido = `
        <div class="space-y-4">
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <h4 class="font-bold text-blue-900 dark:text-blue-100 mb-2">Resumen</h4>
                <p class="text-blue-800 dark:text-blue-200">
                    Se crearán <strong>${validos.length}</strong> productos nuevos
                </p>
            </div>
            
            <div class="space-y-2 max-h-96 overflow-y-auto">
                ${validos.map(p => `
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">${p.nombre}</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">${p.marca || 'Sin marca'} | ${p.proveedor || 'Sin proveedor'}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-primary">L ${parseFloat(p.precioUnidad).toFixed(2)}</p>
                                <p class="text-xs text-green-600">Margen: ${p.margen}%</p>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
            
            <div class="flex gap-3 justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                <button onclick="cerrarVistaPrevia()" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg">Cancelar</button>
                <button onclick="cerrarVistaPrevia(); crearProductos();" class="px-4 py-2 bg-primary text-white rounded-lg">Confirmar y Crear</button>
            </div>
        </div>
    `;

    document.getElementById('contenidoVistaPrevia').innerHTML = contenido;
    document.getElementById('modalVistaPrevia').classList.remove('hidden');
}

function cerrarVistaPrevia() {
    document.getElementById('modalVistaPrevia').classList.add('hidden');
}

async function crearProductos() {
    const validos = tabla.obtenerProductosValidos();

    if (validos.length === 0) {
        mostrarNotificacion('warning', 'Sin productos', 'No hay productos válidos para crear');
        return;
    }

    const progresoContainer = document.getElementById('progresoContainer');
    const barraProgreso = document.getElementById('barraProgreso');
    const textoProgreso = document.getElementById('textoProgreso');

    progresoContainer.classList.remove('hidden');

    try {
        const response = await fetch('api/procesar_creacion_lote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ productos: validos })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        console.log('Respuesta del servidor:', data);

        if (data.success) {
            barraProgreso.style.width = '100%';
            barraProgreso.textContent = '100%';
            textoProgreso.textContent = `✅ ${data.exitosos} productos creados exitosamente`;

            setTimeout(() => {
                mostrarNotificacion('success', '¡Éxito!', `Se crearon ${data.exitosos} productos correctamente`);
                tabla.limpiar();
                progresoContainer.classList.add('hidden');
                barraProgreso.style.width = '0%';
            }, 2000);
        } else {
            // Mostrar errores específicos en un formato más legible
            progresoContainer.classList.add('hidden');

            const mensajeError = data.message || 'Error al crear productos';
            let detallesHTML = '';

            if (data.errores && data.errores.length > 0) {
                detallesHTML = `
                    <div class="mt-4 max-h-64 overflow-y-auto bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                        <h4 class="font-bold text-red-900 dark:text-red-100 mb-2">Errores encontrados:</h4>
                        <ul class="list-disc list-inside space-y-1 text-sm text-red-800 dark:text-red-200">
                            ${data.errores.map(err => `<li>${err}</li>`).join('')}
                        </ul>
                    </div>
                `;
            }

            // Mostrar modal con errores detallados
            const contenido = `
                <div class="text-center">
                    <span class="material-symbols-outlined text-red-600 text-6xl mb-4">error</span>
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Error al crear productos</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">${mensajeError}</p>
                    ${detallesHTML}
                    <div class="flex gap-3 justify-center mt-6">
                        <button onclick="cerrarNotificacion()" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                            Entendido
                        </button>
                    </div>
                </div>
            `;

            document.getElementById('notifMensaje').innerHTML = contenido;
            document.getElementById('notifHeader').style.display = 'none';
            document.getElementById('modalNotificacion').classList.remove('hidden');
            return; // No lanzar error, ya mostramos el modal
        }
    } catch (error) {
        console.error('Error:', error);
        textoProgreso.textContent = '❌ Error al crear productos';
        progresoContainer.classList.add('hidden');

        // Mostrar error detallado
        const errorMsg = error.message || 'Error desconocido';
        mostrarNotificacion('error', 'Error al crear productos', errorMsg);
    }
}

console.log('✅ Tabla Editable COMPLETA para Creación de Productos cargada');

