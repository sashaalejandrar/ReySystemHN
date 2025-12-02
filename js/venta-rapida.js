// ===================================
// VENTA RÁPIDA CON SHORTCUTS
// ===================================

// Variables globales para favoritos
let productosFavoritos = [];
let scannerActive = false;

// Cargar productos favoritos al iniciar
document.addEventListener('DOMContentLoaded', function () {
    cargarProductosFavoritos();
    inicializarAtajosTeclado();
});

// Cargar productos más vendidos
async function cargarProductosFavoritos() {
    try {
        const response = await fetch('api/productos_favoritos.php');
        const data = await response.json();

        if (data.success && data.productos) {
            productosFavoritos = data.productos;
            renderizarFavoritos();
        }
    } catch (error) {
        console.error('Error cargando favoritos:', error);
    }
}

// Renderizar grid de favoritos
function renderizarFavoritos() {
    const grid = document.getElementById('favoritesGrid');
    if (!grid) return;

    if (productosFavoritos.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-8 text-slate-400">
                <span class="material-symbols-outlined text-4xl">inventory_2</span>
                <p class="mt-2 text-sm">No hay productos disponibles</p>
            </div>
        `;
        return;
    }

    grid.innerHTML = productosFavoritos.map((producto, index) => {
        const tecla = index < 12 ? `F${index + 1}` : '';
        const iniciales = producto.nombre.substring(0, 2).toUpperCase();
        const foto = producto.foto || '';

        return `
            <button 
                onclick="agregarFavorito(${index})"
                class="relative group bg-white dark:bg-[#192233] rounded-lg p-3 hover:shadow-lg hover:scale-105 transition-all duration-200 border-2 border-transparent hover:border-primary"
                title="${producto.nombre} - L ${producto.precio.toFixed(2)}">
                
                <!-- Badge de tecla -->
                ${tecla ? `
                <div class="absolute -top-2 -right-2 bg-primary text-white text-xs font-bold px-2 py-1 rounded-full shadow-lg z-10">
                    ${tecla}
                </div>
                ` : ''}
                
                <!-- Imagen o iniciales -->
                <div class="aspect-square rounded-lg overflow-hidden mb-2 bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                    ${foto ?
                `<img src="${foto}" alt="${producto.nombre}" class="w-full h-full object-cover">` :
                `<div class="text-2xl font-bold text-slate-400">${iniciales}</div>`
            }
                </div>
                
                <!-- Info del producto -->
                <div class="text-left">
                    <p class="text-xs font-semibold text-slate-900 dark:text-white truncate">${producto.nombre}</p>
                    <p class="text-xs text-primary font-bold">L ${producto.precio.toFixed(2)}</p>
                    <p class="text-[10px] text-slate-400">Stock: ${producto.stock}</p>
                </div>
                
                <!-- Icono de agregar -->
                <div class="absolute inset-0 bg-primary/90 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                    <span class="material-symbols-outlined text-white text-4xl">add_circle</span>
                </div>
            </button>
        `;
    }).join('');
}

// Agregar producto favorito al carrito
function agregarFavorito(index) {
    if (index < 0 || index >= productosFavoritos.length) return;

    const producto = productosFavoritos[index];
    const productData = {
        id: producto.id,
        codigo: producto.codigo,
        nombre: producto.nombre,
        marca: producto.marca || '',
        precio: producto.precio,
        stock: producto.stock
    };

    // Usar la función existente addToCart
    if (typeof addToCart === 'function') {
        addToCart(productData);

        // Feedback visual
        const btn = document.querySelectorAll('#favoritesGrid button')[index];
        if (btn) {
            btn.classList.add('ring-4', 'ring-green-500');
            setTimeout(() => {
                btn.classList.remove('ring-4', 'ring-green-500');
            }, 300);
        }

        // Sonido
        if (typeof playBeepSound === 'function') {
            playBeepSound();
        }
    }
}

// ===================================
// ATAJOS DE TECLADO
// ===================================

function inicializarAtajosTeclado() {
    document.addEventListener('keydown', function (e) {
        // Ignorar si está escribiendo en un input
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
            // Excepto para Ctrl+Enter y Ctrl+B
            if (!(e.ctrlKey && (e.key === 'Enter' || e.key === 'b'))) {
                return;
            }
        }

        // F1-F12: Agregar productos favoritos
        if (e.key.startsWith('F') && e.key.length <= 3) {
            e.preventDefault();
            const fNumber = parseInt(e.key.replace('F', ''));
            if (fNumber >= 1 && fNumber <= 12) {
                agregarFavorito(fNumber - 1);
            }
        }

        // Ctrl+Enter: Finalizar venta
        if (e.ctrlKey && e.key === 'Enter') {
            e.preventDefault();
            const checkoutBtn = document.getElementById('checkoutBtn');
            if (checkoutBtn && !checkoutBtn.disabled) {
                checkoutBtn.click();
            }
        }

        // Ctrl+B: Buscar producto
        if (e.ctrlKey && e.key === 'b') {
            e.preventDefault();
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }

        // Esc: Cerrar modales
        if (e.key === 'Escape') {
            if (scannerActive) {
                toggleScanner();
            }
        }
    });
}

// ===================================
// ESCÁNER DE CÓDIGO DE BARRAS
// ===================================

function toggleScanner() {
    const modal = document.getElementById('scannerModal');
    if (!modal) return;

    if (scannerActive) {
        // Cerrar scanner
        modal.classList.add('hidden');
        detenerScanner();
        scannerActive = false;
    } else {
        // Abrir scanner
        modal.classList.remove('hidden');
        iniciarScanner();
        scannerActive = true;
    }
}

function iniciarScanner() {
    // Verificar si QuaggaJS está disponible
    if (typeof Quagga === 'undefined') {
        console.error('QuaggaJS no está cargado');
        mostrarError('El escáner no está disponible. Recarga la página.');
        return;
    }

    Quagga.init({
        inputStream: {
            name: "Live",
            type: "LiveStream",
            target: document.querySelector('#scanner'),
            constraints: {
                width: 640,
                height: 480,
                facingMode: "environment"
            },
        },
        decoder: {
            readers: [
                "ean_reader",
                "ean_8_reader",
                "code_128_reader",
                "code_39_reader",
                "upc_reader",
                "upc_e_reader"
            ]
        },
    }, function (err) {
        if (err) {
            console.error('Error inicializando scanner:', err);
            mostrarError('No se pudo acceder a la cámara');
            toggleScanner();
            return;
        }
        Quagga.start();
    });

    Quagga.onDetected(function (result) {
        const codigo = result.codeResult.code;
        console.log('Código detectado:', codigo);

        // Buscar producto por código
        buscarYAgregarPorCodigo(codigo);

        // Cerrar scanner después de detectar
        setTimeout(() => {
            toggleScanner();
        }, 500);
    });
}

function detenerScanner() {
    if (typeof Quagga !== 'undefined') {
        Quagga.stop();
    }
}

function buscarYAgregarPorCodigo(codigo) {
    // Buscar en productos favoritos primero
    const favoritoEncontrado = productosFavoritos.find(p =>
        p.codigo.toLowerCase() === codigo.toLowerCase()
    );

    if (favoritoEncontrado) {
        const index = productosFavoritos.indexOf(favoritoEncontrado);
        agregarFavorito(index);
        return;
    }

    // Buscar en todos los productos
    const productos = document.querySelectorAll('.product-item');
    let encontrado = false;

    productos.forEach(producto => {
        if (producto.dataset.codigo.toLowerCase() === codigo.toLowerCase()) {
            const productData = {
                id: producto.dataset.id,
                codigo: producto.dataset.codigo,
                nombre: producto.dataset.nombre,
                marca: producto.dataset.marca,
                precio: parseFloat(producto.dataset.precio),
                stock: parseInt(producto.dataset.stock)
            };

            if (typeof addToCart === 'function') {
                addToCart(productData);
                encontrado = true;

                if (typeof playBeepSound === 'function') {
                    playBeepSound();
                }
            }
        }
    });

    if (!encontrado) {
        if (typeof mostrarError === 'function') {
            mostrarError('Producto no encontrado: ' + codigo);
        }
        if (typeof playErrorSound === 'function') {
            playErrorSound();
        }
    }
}

// ===================================
// AYUDA DE ATAJOS (TOOLTIP)
// ===================================

// Mostrar ayuda de atajos al presionar ?
document.addEventListener('keydown', function (e) {
    if (e.key === '?' && !e.target.matches('input, textarea')) {
        e.preventDefault();
        mostrarAyudaAtajos();
    }
});

function mostrarAyudaAtajos() {
    const ayuda = `
        <div class="bg-white dark:bg-[#111722] rounded-xl p-6 max-w-md">
            <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-4">Atajos de Teclado</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">F1 - F12</span>
                    <span class="text-slate-900 dark:text-white font-semibold">Agregar favorito</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Ctrl + Enter</span>
                    <span class="text-slate-900 dark:text-white font-semibold">Finalizar venta</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Ctrl + B</span>
                    <span class="text-slate-900 dark:text-white font-semibold">Buscar producto</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Esc</span>
                    <span class="text-slate-900 dark:text-white font-semibold">Cerrar modal</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">?</span>
                    <span class="text-slate-900 dark:text-white font-semibold">Mostrar ayuda</span>
                </div>
            </div>
            <button onclick="cerrarAyuda()" class="mt-4 w-full bg-primary text-white py-2 rounded-lg hover:bg-primary/90">
                Cerrar
            </button>
        </div>
    `;

    // Crear modal temporal
    const modal = document.createElement('div');
    modal.id = 'ayudaModal';
    modal.className = 'fixed inset-0 bg-black/50 z-[9999] flex items-center justify-center p-4';
    modal.innerHTML = ayuda;
    modal.onclick = function (e) {
        if (e.target === modal) cerrarAyuda();
    };
    document.body.appendChild(modal);
}

function cerrarAyuda() {
    const modal = document.getElementById('ayudaModal');
    if (modal) modal.remove();
}

console.log('✅ Venta Rápida con Shortcuts cargado');
console.log('💡 Presiona ? para ver los atajos de teclado');
