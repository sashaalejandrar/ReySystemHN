// ===================================
// SISTEMA DE NOTIFICACIONES MODAL
// ===================================
function mostrarNotificacion(tipo, titulo, mensaje) {
    const modal = document.getElementById('modalNotificacion');
    const icon = document.getElementById('notifIcon');
    const tituloEl = document.getElementById('notifTitulo');
    const mensajeEl = document.getElementById('notifMensaje');
    const header = document.getElementById('notifHeader');

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
}

// ===================================
// SISTEMA DE CONFIRMACIÓN MODAL
// ===================================
let confirmCallback = null;

function mostrarConfirmacion(mensaje) {
    return new Promise((resolve) => {
        const modal = document.getElementById('modalConfirmacion');
        const mensajeEl = document.getElementById('confirmMensaje');

        mensajeEl.textContent = mensaje;
        modal.classList.remove('hidden');

        confirmCallback = resolve;
    });
}

function cerrarConfirmacion(resultado) {
    document.getElementById('modalConfirmacion').classList.add('hidden');
    if (confirmCallback) {
        confirmCallback(resultado);
        confirmCallback = null;
    }
}

// ===================================
// TABLA EDITABLE PARA ACTUALIZACIÓN DE STOCK - VERSIÓN CARDS
// ===================================

class TablaProductosActualizar {
    constructor() {
        this.productos = [];
        this.resumen = document.getElementById('resumenActualizar');
    }

    agregarProducto(producto = {}) {
        const nuevoProducto = {
            id: Date.now() + Math.random(),
            codigo: producto.codigo || '',
            nombre: producto.nombre || '',
            stockActual: producto.stockActual || 0,
            cantidad: producto.cantidad || 0,
            precio: producto.precio || 0,
            marca: producto.marca || '',
            categoria: producto.categoria || ''
        };

        this.productos.push(nuevoProducto);
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
            this.renderizar();
        }
    }

    calcularStockNuevo(producto) {
        const tipoAjuste = document.querySelector('input[name="tipoAjuste"]:checked').value;
        const stockActual = parseFloat(producto.stockActual) || 0;
        const cantidad = parseFloat(producto.cantidad) || 0;

        switch (tipoAjuste) {
            case 'sumar':
                return stockActual + cantidad;
            case 'restar':
                return Math.max(0, stockActual - cantidad);
            case 'reemplazar':
                return cantidad;
            default:
                return stockActual;
        }
    }

    renderizar() {
        const contenedor = document.getElementById('contenedorProductosActualizar');

        if (this.productos.length === 0) {
            contenedor.innerHTML = `
                <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-12 text-center">
                    <img src="uploads/gatito.png" alt="Rey jugando con gato" class="w-64 h-64 mx-auto mb-6 animate-pulse">
                    <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-2">Todo calmado por aquí</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-lg mb-4">Importa un CSV o añade una fila manualmente</p>
                    <div class="flex gap-3 justify-center mt-6">
                        <button onclick="document.getElementById('csvFileActualizar').click()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <span class="material-symbols-outlined">upload_file</span>
                            Importar CSV
                        </button>
                        <button onclick="agregarFilaManual()" class="flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                            <span class="material-symbols-outlined">add</span>
                            Agregar Fila
                        </button>
                    </div>
                </div>
            `;
        } else {
            contenedor.innerHTML = this.productos.map(p => this.renderizarCard(p)).join('');
        }

        this.actualizarResumen();
    }

    renderizarCard(p) {
        const stockNuevo = this.calcularStockNuevo(p);
        const diferencia = stockNuevo - (parseFloat(p.stockActual) || 0);
        const diferenciaClass = diferencia > 0 ? 'text-green-600' : diferencia < 0 ? 'text-red-600' : 'text-gray-600';
        const diferenciaIcon = diferencia > 0 ? 'arrow_upward' : diferencia < 0 ? 'arrow_downward' : 'remove';

        const inputClass = 'form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary border-slate-300 dark:border-[#324467] bg-slate-50 dark:bg-[#111722] h-14 placeholder:text-slate-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal';
        const inputReadonlyClass = 'form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-600 dark:text-gray-400 border-slate-300 dark:border-[#324467] bg-gray-100 dark:bg-gray-800 h-14 p-[15px] text-base font-normal leading-normal';

        return `
            <div class="bg-white dark:bg-[#192233] rounded-xl border-2 border-gray-200 dark:border-gray-700 p-6 relative hover:border-primary transition-colors">
                <!-- Header con nombre y eliminar -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary text-2xl">inventory_2</span>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            ${p.nombre || 'Producto Nuevo'}
                        </h3>
                    </div>
                    <button onclick="tablaActualizar.eliminarProducto(${p.id})" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors p-2">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
                
                <!-- Grid de campos -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Información del Producto -->
                    <div class="md:col-span-2 bg-blue-50 dark:bg-blue-900/10 p-4 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Información del Producto</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex flex-col relative">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Código / SKU *</p>
                                <input type="text" value="${p.codigo}" 
                                       onchange="tablaActualizar.actualizarCampo(${p.id}, 'codigo', this.value)"
                                       oninput="buscarProductoAutocompletar(${p.id}, this.value)"
                                       class="${inputClass}" 
                                       placeholder="Buscar por código..."
                                       id="codigo_${p.id}">
                                <div id="sugerencias_${p.id}" class="suggestions-container hidden"></div>
                            </div>
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Nombre del Producto *</p>
                                <input type="text" value="${p.nombre}" 
                                       onchange="tablaActualizar.actualizarCampo(${p.id}, 'nombre', this.value)"
                                       oninput="buscarProductoAutocompletar(${p.id}, this.value)"
                                       class="${inputClass}" 
                                       placeholder="Buscar por nombre...">
                            </label>
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Marca</p>
                                <input type="text" value="${p.marca}" 
                                       onchange="tablaActualizar.actualizarCampo(${p.id}, 'marca', this.value)"
                                       class="${inputClass}" 
                                       placeholder="Marca del producto"
                                       readonly>
                            </label>
                            <label class="flex flex-col">
                                <p class="text-slate-600 dark:text-white text-sm font-medium pb-2">Categoría</p>
                                <input type="text" value="${p.categoria}" 
                                       onchange="tablaActualizar.actualizarCampo(${p.id}, 'categoria', this.value)"
                                       class="${inputClass}" 
                                       placeholder="Categoría"
                                       readonly>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Stock Actual -->
                    <div class="bg-gray-50 dark:bg-gray-900/10 p-4 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Stock Actual</h4>
                        <input type="number" value="${p.stockActual}" class="${inputReadonlyClass}" readonly>
                    </div>
                    
                    <!-- Cantidad a Ajustar -->
                    <div class="bg-yellow-50 dark:bg-yellow-900/10 p-4 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Cantidad a Ajustar *</h4>
                        <input type="number" value="${p.cantidad}" 
                               onchange="tablaActualizar.actualizarCampo(${p.id}, 'cantidad', this.value)"
                               class="${inputClass}" 
                               min="0"
                               placeholder="0">
                    </div>
                    
                    <!-- Precio Unitario -->
                    <div class="bg-blue-50 dark:bg-blue-900/10 p-4 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Precio Unitario (L)</h4>
                        <input type="number" value="${p.precio}" 
                               onchange="tablaActualizar.actualizarCampo(${p.id}, 'precio', this.value)"
                               class="${inputClass}" 
                               step="0.01"
                               min="0"
                               placeholder="0.00">
                    </div>
                    
                    <!-- Stock Nuevo -->
                    <div class="bg-green-50 dark:bg-green-900/10 p-4 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Stock Nuevo</h4>
                        <div class="flex items-center gap-2">
                            <input type="number" value="${stockNuevo}" class="${inputReadonlyClass}" readonly>
                        </div>
                    </div>
                    
                    <!-- Diferencia -->
                    <div class="bg-purple-50 dark:bg-purple-900/10 p-4 rounded-lg">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Diferencia</h4>
                        <div class="flex items-center gap-2 ${diferenciaClass} font-bold text-lg">
                            <span class="material-symbols-outlined">${diferenciaIcon}</span>
                            <span>${diferencia > 0 ? '+' : ''}${diferencia}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    actualizarResumen() {
        const total = this.productos.length;
        this.resumen.innerHTML = `<span class="font-bold">${total}</span> productos`;

        const btnAplicar = document.getElementById('btnAplicar');
        if (btnAplicar) {
            btnAplicar.disabled = total === 0;
        }
    }

    limpiar() {
        this.productos = [];
        this.renderizar();
    }
}

// Inicializar tabla
const tablaActualizar = new TablaProductosActualizar();

// ===================================
// FUNCIONES GLOBALES
// ===================================

function agregarFilaManual() {
    tablaActualizar.agregarProducto();
}

async function limpiarActualizacion() {
    const confirmado = await mostrarConfirmacion('¿Estás seguro de que quieres eliminar todos los productos de la lista?');
    if (confirmado) {
        tablaActualizar.limpiar();
    }
}

// ===================================
// DRAG & DROP PARA CSV
// ===================================
const dropZone = document.getElementById('dropZoneActualizar');
const csvFileInput = document.getElementById('csvFileActualizar');

dropZone.addEventListener('click', () => {
    csvFileInput.click();
});

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
        dropZone.classList.add('border-primary', 'bg-primary/10', 'scale-105');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => {
        dropZone.classList.remove('border-primary', 'bg-primary/10', 'scale-105');
    }, false);
});

dropZone.addEventListener('drop', (e) => {
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        const file = files[0];
        if (file.name.endsWith('.csv')) {
            csvFileInput.files = files;
            importarCSVActualizar(file);

            dropZone.innerHTML = `
                <div class="flex flex-col items-center gap-2">
                    <span class="material-symbols-outlined text-green-600 text-3xl">check_circle</span>
                    <p class="text-sm text-green-600 font-semibold">${file.name}</p>
                    <p class="text-xs text-gray-500">Archivo cargado correctamente</p>
                </div>
            `;

            setTimeout(() => {
                dropZone.innerHTML = `
                    <div class="flex flex-col items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-4xl">upload_file</span>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <span class="font-semibold text-primary">Click aquí</span> o arrastra el CSV
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Solo archivos .csv</p>
                    </div>
                `;
            }, 3000);
        } else {
            mostrarNotificacion('warning', 'Archivo Inválido', 'Por favor selecciona un archivo CSV');
        }
    }
}, false);

csvFileInput.addEventListener('change', function () {
    if (this.files.length > 0) {
        importarCSVActualizar(this.files[0]);
    }
});

function importarCSVActualizar(archivo) {
    if (!archivo) {
        mostrarNotificacion('warning', 'Sin Archivo', 'Por favor selecciona un archivo CSV');
        return;
    }

    const reader = new FileReader();

    reader.onload = function (e) {
        try {
            const texto = e.target.result;
            procesarCSVActualizar(texto);
        } catch (error) {
            console.error('Error:', error);
            mostrarNotificacion('error', 'Error al Leer CSV', 'Error al leer el archivo CSV: ' + error.message);
        }
    };

    reader.onerror = function () {
        mostrarNotificacion('error', 'Error de Lectura', 'Error al leer el archivo');
    };

    reader.readAsText(archivo);
}

function procesarCSVActualizar(texto) {
    const lineas = texto.split('\n');

    if (lineas.length < 2) {
        mostrarNotificacion('warning', 'CSV Vacío', 'El archivo CSV está vacío o no tiene datos');
        return;
    }

    let productosImportados = 0;

    for (let i = 1; i < lineas.length; i++) {
        const linea = lineas[i].trim();
        if (!linea) continue;

        const columnas = linea.split(',');

        if (columnas.length >= 2) {
            tablaActualizar.agregarProducto({
                codigo: columnas[0] || '',
                cantidad: parseFloat(columnas[1]) || 0
            });
            productosImportados++;
        }
    }

    if (productosImportados > 0) {
        mostrarNotificacion('success', 'Importación Exitosa', `Se importaron ${productosImportados} productos del CSV`);
    } else {
        mostrarNotificacion('error', 'Error de Importación', 'No se pudieron importar productos. Verifica el formato del CSV');
    }

    csvFileInput.value = '';
}

// ===================================
// AUTOCOMPLETADO DE PRODUCTOS - SISTEMA MEJORADO
// ===================================
let timeoutBusqueda = null;
let sugerenciasAbiertas = null; // Track which suggestions box is open
let sugerenciaSeleccionada = -1; // Index of highlighted suggestion
let sugerenciasActuales = []; // Current suggestions array

function buscarProductoAutocompletar(productoId, termino) {
    clearTimeout(timeoutBusqueda);

    // Cerrar cualquier otra sugerencia abierta
    if (sugerenciasAbiertas && sugerenciasAbiertas !== productoId) {
        const contenedorAnterior = document.getElementById(`sugerencias_${sugerenciasAbiertas}`);
        if (contenedorAnterior) {
            contenedorAnterior.classList.add('hidden');
            contenedorAnterior.innerHTML = '';
        }
    }

    const contenedor = document.getElementById(`sugerencias_${productoId}`);

    if (termino.length < 2) {
        contenedor.classList.add('hidden');
        contenedor.innerHTML = '';
        sugerenciasAbiertas = null;
        sugerenciaSeleccionada = -1;
        sugerenciasActuales = [];
        return;
    }

    timeoutBusqueda = setTimeout(() => {
        fetch(`api/buscar_producto_stock.php?termino=${encodeURIComponent(termino)}`)
            .then(response => response.json())
            .then(data => {
                mostrarSugerencias(productoId, data);
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }, 300);
}

function mostrarSugerencias(productoId, sugerencias) {
    const contenedor = document.getElementById(`sugerencias_${productoId}`);

    if (!sugerencias || sugerencias.length === 0) {
        contenedor.classList.add('hidden');
        contenedor.innerHTML = '';
        sugerenciasAbiertas = null;
        sugerenciaSeleccionada = -1;
        sugerenciasActuales = [];
        return;
    }

    sugerenciasAbiertas = productoId;
    sugerenciaSeleccionada = -1;
    sugerenciasActuales = sugerencias;
    contenedor.innerHTML = '';
    contenedor.classList.remove('hidden');

    sugerencias.forEach((s, index) => {
        const item = document.createElement('div');
        item.className = 'suggestion-item-modern group relative';
        item.dataset.index = index;

        item.innerHTML = `
            <div class="flex items-center gap-3 p-3 cursor-pointer transition-all duration-200 hover:bg-gradient-to-r hover:from-blue-500/10 hover:to-purple-500/10 border-l-4 border-transparent hover:border-blue-500">
                <!-- Icono -->
                <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500/20 to-purple-500/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-blue-500 text-xl">inventory_2</span>
                </div>
                
                <!-- Info -->
                <div class="flex-1 min-w-0">
                    <div class="font-semibold text-gray-900 dark:text-white text-sm truncate">
                        ${s.Nombre_Producto}
                    </div>
                    <div class="flex items-center gap-2 mt-0.5 text-xs">
                        <span class="text-gray-500 dark:text-gray-400">
                            <span class="font-medium">SKU:</span> ${s.Codigo_Producto}
                        </span>
                        <span class="text-gray-400">•</span>
                        <span class="text-green-600 dark:text-green-400 font-medium">
                            Stock: ${s.Stock}
                        </span>
                        <span class="text-gray-400">•</span>
                        <span class="text-blue-600 dark:text-blue-400 font-medium">
                            L ${parseFloat(s.Precio_Unitario).toFixed(2)}
                        </span>
                    </div>
                </div>
                
                <!-- Arrow -->
                <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                    <span class="material-symbols-outlined text-blue-500">arrow_forward</span>
                </div>
            </div>
        `;

        // Click handler
        item.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            seleccionarSugerencia(productoId, s);
        });

        // Hover handler
        item.addEventListener('mouseenter', function () {
            sugerenciaSeleccionada = index;
            actualizarSeleccionVisual(productoId);
        });

        contenedor.appendChild(item);
    });
}

function seleccionarSugerencia(productoId, producto) {
    console.log('✅ Producto seleccionado:', producto);

    // Actualizar el producto
    const index = tablaActualizar.productos.findIndex(p => p.id === productoId);
    if (index !== -1) {
        tablaActualizar.productos[index].codigo = producto.Codigo_Producto;
        tablaActualizar.productos[index].nombre = producto.Nombre_Producto;
        tablaActualizar.productos[index].stockActual = producto.Stock;
        tablaActualizar.productos[index].precio = producto.Precio_Unitario;
        tablaActualizar.productos[index].marca = producto.Marca || '';
        tablaActualizar.productos[index].categoria = producto.Grupo || '';

        console.log('✅ Datos cargados:', tablaActualizar.productos[index]);

        // Renderizar
        tablaActualizar.renderizar();
    }

    // Cerrar sugerencias
    const contenedor = document.getElementById(`sugerencias_${productoId}`);
    contenedor.innerHTML = '';
    contenedor.classList.add('hidden');
    sugerenciasAbiertas = null;
    sugerenciaSeleccionada = -1;
    sugerenciasActuales = [];
}

function actualizarSeleccionVisual(productoId) {
    const contenedor = document.getElementById(`sugerencias_${productoId}`);
    if (!contenedor) return;

    const items = contenedor.querySelectorAll('.suggestion-item-modern');
    items.forEach((item, index) => {
        const innerDiv = item.querySelector('div');
        if (index === sugerenciaSeleccionada) {
            innerDiv.classList.add('bg-gradient-to-r', 'from-blue-500/10', 'to-purple-500/10', 'border-blue-500');
            innerDiv.classList.remove('border-transparent');
        } else {
            innerDiv.classList.remove('bg-gradient-to-r', 'from-blue-500/10', 'to-purple-500/10', 'border-blue-500');
            innerDiv.classList.add('border-transparent');
        }
    });
}

// Manejar teclas en los inputs
document.addEventListener('keydown', function (e) {
    if (!sugerenciasAbiertas) return;

    const contenedor = document.getElementById(`sugerencias_${sugerenciasAbiertas}`);
    if (!contenedor || contenedor.classList.contains('hidden')) return;

    // Arrow Down
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        sugerenciaSeleccionada = Math.min(sugerenciaSeleccionada + 1, sugerenciasActuales.length - 1);
        actualizarSeleccionVisual(sugerenciasAbiertas);

        // Scroll into view
        const items = contenedor.querySelectorAll('.suggestion-item-modern');
        if (items[sugerenciaSeleccionada]) {
            items[sugerenciaSeleccionada].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    // Arrow Up
    else if (e.key === 'ArrowUp') {
        e.preventDefault();
        sugerenciaSeleccionada = Math.max(sugerenciaSeleccionada - 1, 0);
        actualizarSeleccionVisual(sugerenciasAbiertas);

        // Scroll into view
        const items = contenedor.querySelectorAll('.suggestion-item-modern');
        if (items[sugerenciaSeleccionada]) {
            items[sugerenciaSeleccionada].scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }

    // Tab or Enter
    else if (e.key === 'Tab' || e.key === 'Enter') {
        if (sugerenciaSeleccionada >= 0 && sugerenciaSeleccionada < sugerenciasActuales.length) {
            e.preventDefault();
            seleccionarSugerencia(sugerenciasAbiertas, sugerenciasActuales[sugerenciaSeleccionada]);
        }
    }

    // Escape
    else if (e.key === 'Escape') {
        e.preventDefault();
        contenedor.innerHTML = '';
        contenedor.classList.add('hidden');
        sugerenciasAbiertas = null;
        sugerenciaSeleccionada = -1;
        sugerenciasActuales = [];
    }
});

// Cerrar sugerencias al hacer click fuera
document.addEventListener('click', function (e) {
    if (sugerenciasAbiertas) {
        const contenedor = document.getElementById(`sugerencias_${sugerenciasAbiertas}`);
        const input = document.getElementById(`codigo_${sugerenciasAbiertas}`);

        if (contenedor && !contenedor.contains(e.target) && !input.contains(e.target)) {
            contenedor.classList.add('hidden');
            contenedor.innerHTML = '';
            sugerenciasAbiertas = null;
            sugerenciaSeleccionada = -1;
            sugerenciasActuales = [];
        }
    }
});

// ===================================
// APLICAR ACTUALIZACIONES
// ===================================
async function aplicarActualizaciones() {
    if (tablaActualizar.productos.length === 0) {
        mostrarNotificacion('warning', 'Sin Productos', 'No hay productos para actualizar');
        return;
    }

    const tipoAjuste = document.querySelector('input[name="tipoAjuste"]:checked').value;

    const productosParaActualizar = tablaActualizar.productos.map(p => ({
        codigo: p.codigo,
        cantidad: p.cantidad,
        stockActual: p.stockActual,
        stockNuevo: tablaActualizar.calcularStockNuevo(p),
        precio: p.precio || 0
    }));

    try {
        const response = await fetch('api/procesar_actualizacion_lote.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                productos: productosParaActualizar,
                tipoAjuste: tipoAjuste
            })
        });

        const data = await response.json();

        if (data.success) {
            mostrarNotificacion('success', '¡Éxito!', `${data.actualizados} productos actualizados exitosamente`);
            tablaActualizar.limpiar();
        } else {
            mostrarNotificacion('error', 'Error', data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error', 'Error al aplicar actualizaciones');
    }
}

console.log('✅ Tabla Editable para Actualización de Stock cargada');
