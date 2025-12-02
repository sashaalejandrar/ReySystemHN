// ===================================
// SISTEMA DE NOTIFICACIONES
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
// MANEJO DE CÁMARA
// ===================================
let stream = null;
let imagenCapturada = null;
let productosExtraidos = [];

async function activarCamara() {
    const areaCaptura = document.getElementById('areaCaptura');
    const camaraContainer = document.getElementById('camaraContainer');
    const video = document.getElementById('video');

    areaCaptura.classList.remove('hidden');
    camaraContainer.classList.remove('hidden');

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment', // Cámara trasera en móviles
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            }
        });

        video.srcObject = stream;
    } catch (error) {
        console.error('Error al acceder a la cámara:', error);
        mostrarNotificacion('error', 'Error de Cámara', 'No se pudo acceder a la cámara. Verifica los permisos.');
    }
}

function capturarFoto() {
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;

    // Dibujar imagen (invertida horizontalmente para corregir el espejo)
    ctx.translate(canvas.width, 0);
    ctx.scale(-1, 1);
    ctx.drawImage(video, 0, 0);

    // Convertir a imagen
    imagenCapturada = canvas.toDataURL('image/jpeg', 0.9);

    // Mostrar vista previa
    mostrarVistaPrevia(imagenCapturada);

    // Detener cámara
    cerrarCamara();
}

function cargarImagen(event) {
    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        imagenCapturada = e.target.result;
        mostrarVistaPrevia(imagenCapturada);
    };
    reader.readAsDataURL(file);
}

function mostrarVistaPrevia(imagenSrc) {
    const areaCaptura = document.getElementById('areaCaptura');
    const vistaPrevia = document.getElementById('vistaPrevia');
    const imagenPrevia = document.getElementById('imagenPrevia');

    areaCaptura.classList.remove('hidden');
    vistaPrevia.classList.remove('hidden');
    imagenPrevia.src = imagenSrc;
}

function cerrarCamara() {
    const camaraContainer = document.getElementById('camaraContainer');

    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
    }

    camaraContainer.classList.add('hidden');

    // Detener escaneo de QR si está activo
    if (escaneoQRActivo) {
        escaneoQRActivo = false;
    }
}

// ===================================
// ESCANEO DE CÓDIGOS QR
// ===================================
let escaneoQRActivo = false;

async function activarEscanerQR() {
    const areaCaptura = document.getElementById('areaCaptura');
    const camaraContainer = document.getElementById('camaraContainer');
    const video = document.getElementById('video');

    areaCaptura.classList.remove('hidden');
    camaraContainer.classList.remove('hidden');

    try {
        stream = await navigator.mediaDevices.getUserMedia({
            video: {
                facingMode: 'environment',
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            }
        });

        video.srcObject = stream;
        escaneoQRActivo = true;

        // Iniciar escaneo continuo
        escanearQRContinuo();

    } catch (error) {
        console.error('Error al acceder a la cámara:', error);
        mostrarNotificacion('error', 'Error de Cámara', 'No se pudo acceder a la cámara. Verifica los permisos.');
    }
}

function escanearQRContinuo() {
    if (!escaneoQRActivo) return;

    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');

    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

        const code = jsQR(imageData.data, imageData.width, imageData.height);

        if (code) {
            // QR detectado!
            escaneoQRActivo = false;
            cerrarCamara();

            // Buscar producto por código QR
            buscarProductoPorQR(code.data);
            return;
        }
    }

    requestAnimationFrame(escanearQRContinuo);
}

async function buscarProductoPorQR(codigoQR) {
    document.getElementById('areaCaptura').classList.add('hidden');
    document.getElementById('areaProcesamiento').classList.remove('hidden');

    const progressBar = document.getElementById('progressBar');
    progressBar.style.width = '50%';

    try {
        const response = await fetch(`api/buscar_producto_stock.php?termino=${encodeURIComponent(codigoQR)}`);
        const productos = await response.json();

        progressBar.style.width = '100%';

        setTimeout(() => {
            document.getElementById('areaProcesamiento').classList.add('hidden');

            if (productos && productos.length > 0) {
                // Producto encontrado
                const producto = productos[0];
                productosExtraidos = [{
                    codigo: producto.Codigo_Producto,
                    nombre: producto.Nombre_Producto,
                    cantidad: 1,
                    precio: producto.Precio_Unitario,
                    marca: producto.Marca || '',
                    descripcion: '',
                    existe: true,
                    stockActual: producto.Stock
                }];

                mostrarResultados(productosExtraidos);
            } else {
                mostrarNotificacion('warning', 'Producto No Encontrado',
                    `No se encontró ningún producto con el código: ${codigoQR}`);
                reiniciar();
            }
        }, 500);

    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error', 'Error al buscar el producto');
        reiniciar();
    }
}

function reiniciar() {
    document.getElementById('areaCaptura').classList.add('hidden');
    document.getElementById('areaProcesamiento').classList.add('hidden');
    document.getElementById('areaResultados').classList.add('hidden');
    document.getElementById('vistaPrevia').classList.add('hidden');
    document.getElementById('fileInput').value = '';

    imagenCapturada = null;
    productosExtraidos = [];

    cerrarCamara();
}

// ===================================
// PROCESAMIENTO CON IA (OCR)
// ===================================
async function procesarConIA() {
    if (!imagenCapturada) {
        mostrarNotificacion('warning', 'Sin Imagen', 'No hay imagen para procesar');
        return;
    }

    // Ocultar vista previa y mostrar procesamiento
    document.getElementById('vistaPrevia').classList.add('hidden');
    document.getElementById('areaProcesamiento').classList.remove('hidden');

    // Simular progreso
    let progreso = 0;
    const progressBar = document.getElementById('progressBar');
    const intervalo = setInterval(() => {
        progreso += 10;
        progressBar.style.width = progreso + '%';
        if (progreso >= 90) clearInterval(intervalo);
    }, 200);

    try {
        let imagenParaProcesar = imagenCapturada;

        // NUEVO: Preprocesar imagen si está activado
        const mejoraActivada = document.getElementById('toggleMejoraImagen')?.checked ?? true;

        if (mejoraActivada) {
            console.log('🔄 Preprocesando imagen para mejorar OCR...');
            try {
                const processor = new ImageProcessor();
                imagenParaProcesar = await processor.processInvoice(imagenCapturada);
                console.log('✅ Imagen preprocesada correctamente');
            } catch (error) {
                console.warn('⚠️ Error en preprocesamiento, usando imagen original:', error);
                // Si falla el preprocesamiento, usar imagen original
            }
        } else {
            console.log('ℹ️ Mejora de imagen desactivada, usando imagen original');
        }

        // Verificar método OCR seleccionado
        const metodoOCR = document.getElementById('metodoOCR')?.value || 'tesseract';

        let data;

        if (metodoOCR === 'mindee') {
            // Usar Mindee Invoice OCR (Especializado en facturas)
            console.log('📄 Usando Mindee Invoice OCR...');

            const response = await fetch('api/procesar_factura_mindee.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ imagen: imagenParaProcesar })
            });

            data = await response.json();

        } else if (metodoOCR === 'mistral') {
            // Usar Mistral OCR (IA Avanzada)
            console.log('🤖 Usando Mistral OCR (IA Avanzada)...');

            const response = await fetch('api/procesar_factura_mistral.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ imagen: imagenParaProcesar })
            });

            data = await response.json();

        } else if (metodoOCR === 'cloudmersive') {
            // Usar Cloudmersive OCR
            console.log('☁️ Usando Cloudmersive OCR...');

            const response = await fetch('api/procesar_factura_cloudmersive.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ imagen: imagenParaProcesar })
            });

            data = await response.json();

        } else if (metodoOCR === 'puter-aws' || metodoOCR === 'puter-mistral') {
            // Usar Puter.js OCR (AWS Textract o Mistral)
            const provider = metodoOCR === 'puter-aws' ? 'aws-textract' : 'mistral';
            const providerName = metodoOCR === 'puter-aws' ? 'AWS Textract' : 'Mistral OCR';

            console.log(`🚀 Usando Puter.js ${providerName} (Gratis Ilimitado)...`);

            try {
                // Convertir base64 a Blob
                const base64Data = imagenParaProcesar.split(',')[1];
                const byteCharacters = atob(base64Data);
                const byteNumbers = new Array(byteCharacters.length);
                for (let i = 0; i < byteCharacters.length; i++) {
                    byteNumbers[i] = byteCharacters.charCodeAt(i);
                }
                const byteArray = new Uint8Array(byteNumbers);
                const blob = new Blob([byteArray], { type: 'image/jpeg' });

                // Llamar a Puter.js OCR
                const result = await puter.ai.img2txt({
                    source: blob,
                    provider: provider,
                    testMode: false // Cambiar a true para testing sin consumir créditos
                });

                console.log('✅ Texto extraído con Puter.js:', result);

                // Parsear el texto con IA (Groq) para extracción inteligente
                const parseoIA = await parsearTextoConIA(result);

                if (parseoIA.success && parseoIA.productos.length > 0) {
                    // Verificar productos en la base de datos
                    data = await verificarProductosEnBD(parseoIA.productos);
                } else {
                    data = parseoIA;
                }

            } catch (error) {
                console.error('❌ Error con Puter.js:', error);

                // Si falla, mostrar error
                data = {
                    success: false,
                    message: `Error con Puter.js ${providerName}: ${error.message}`,
                    productos: []
                };
            }

        } else if (metodoOCR === 'tesseract') {
            // Usar Tesseract.js (OCR en el navegador)
            console.log('🔍 Usando Tesseract.js para OCR...');

            const { data: { text } } = await Tesseract.recognize(
                imagenParaProcesar,
                'spa', // Español
                {
                    logger: m => {
                        if (m.status === 'recognizing text') {
                            const progress = Math.round(m.progress * 100);
                            progressBar.style.width = progress + '%';
                            console.log(`Tesseract: ${progress}%`);
                        }
                    }
                }
            );

            console.log('📄 Texto extraído:', text);

            // Parsear el texto localmente
            data = parsearTextoLocal(text);

        } else {
            // Usar OCR.space (API)
            const response = await fetch('api/procesar_factura_ia.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ imagen: imagenParaProcesar })
            });

            data = await response.json();
        }

        clearInterval(intervalo);
        progressBar.style.width = '100%';

        setTimeout(() => {
            document.getElementById('areaProcesamiento').classList.add('hidden');

            if (data.success) {
                productosExtraidos = data.productos;
                mostrarResultados(productosExtraidos);
            } else {
                // Mostrar mensaje de error con detalles
                let mensaje = data.message || 'No se pudo procesar la imagen';

                // Si hay información de debug, mostrarla en consola
                if (data.debug) {
                    console.log('Debug OCR:', data.debug);
                }

                // Si hay sugerencias, agregarlas al mensaje
                if (data.sugerencias && data.sugerencias.length > 0) {
                    mensaje += '\n\nSugerencias:\n' + data.sugerencias.map(s => '• ' + s).join('\n');
                }

                // Si hay texto extraído pero no productos, mostrar el texto
                if (data.texto_completo) {
                    console.log('Texto extraído:', data.texto_completo);
                    mensaje += '\n\nTexto detectado: ' + data.texto_completo.substring(0, 200);
                }

                mostrarNotificacion('error', 'Error de Procesamiento', mensaje);
                reiniciar();
            }
        }, 500);

    } catch (error) {
        console.error('Error:', error);
        clearInterval(intervalo);
        mostrarNotificacion('error', 'Error', 'Error al procesar la imagen con IA');
        reiniciar();
    }
}

function mostrarResultados(productos) {
    const areaResultados = document.getElementById('areaResultados');
    const contenedor = document.getElementById('productosDetectados');

    if (productos.length === 0) {
        mostrarNotificacion('warning', 'Sin Productos', 'No se detectaron productos en la factura');
        reiniciar();
        return;
    }

    contenedor.innerHTML = productos.map((p, index) => `
        <div class="border-2 ${p.existe ? 'border-blue-500' : 'border-green-500'} rounded-lg p-4 bg-${p.existe ? 'blue' : 'green'}-50 dark:bg-${p.existe ? 'blue' : 'green'}-900/10" id="producto-${index}">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-2 flex-1">
                    <span class="material-symbols-outlined text-${p.existe ? 'blue' : 'green'}-600">${p.existe ? 'inventory' : 'add_circle'}</span>
                    <div class="flex-1">
                        <h4 class="font-bold text-gray-900 dark:text-white" id="nombre-display-${index}">${p.nombre}</h4>
                        <input type="text" id="nombre-edit-${index}" value="${p.nombre}" 
                               class="hidden w-full px-3 py-2 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 border-2 border-blue-500 dark:border-blue-400 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent font-semibold" 
                               placeholder="Nombre del producto" />
                        <p class="text-xs text-gray-500 dark:text-gray-400">${p.existe ? 'Producto Existente - Se actualizará stock' : 'Producto Nuevo - Se creará'}</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="editarProducto(${index})" id="btn-editar-${index}" 
                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors" 
                            title="Editar">
                        <span class="material-symbols-outlined">edit</span>
                    </button>
                    <button onclick="guardarEdicion(${index})" id="btn-guardar-${index}" 
                            class="hidden text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 transition-colors" 
                            title="Guardar cambios">
                        <span class="material-symbols-outlined">save</span>
                    </button>
                    <button onclick="cancelarEdicion(${index})" id="btn-cancelar-${index}" 
                            class="hidden text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-300 transition-colors" 
                            title="Cancelar">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                    <button onclick="eliminarProducto(${index})" 
                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors" 
                            title="Eliminar">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div>
                    <p class="text-gray-500 dark:text-gray-400 mb-1">Código</p>
                    <p class="font-semibold text-gray-900 dark:text-white" id="codigo-display-${index}">${p.codigo || 'N/A'}</p>
                    <input type="text" id="codigo-edit-${index}" value="${p.codigo || ''}" 
                           class="hidden w-full px-3 py-2 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 border-2 border-blue-500 dark:border-blue-400 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           placeholder="Código de barras" />
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 mb-1">Cantidad</p>
                    <p class="font-semibold text-gray-900 dark:text-white" id="cantidad-display-${index}">${p.cantidad}</p>
                    <input type="number" id="cantidad-edit-${index}" value="${p.cantidad}" min="1" 
                           class="hidden w-full px-3 py-2 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 border-2 border-blue-500 dark:border-blue-400 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 mb-1">Precio Unit.</p>
                    <p class="font-semibold text-gray-900 dark:text-white" id="precio-display-${index}">L ${parseFloat(p.precio).toFixed(2)}</p>
                    <input type="number" id="precio-edit-${index}" value="${p.precio}" step="0.01" min="0" 
                           class="hidden w-full px-3 py-2 text-gray-900 dark:text-gray-100 bg-white dark:bg-gray-700 border-2 border-blue-500 dark:border-blue-400 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" />
                </div>
                <div>
                    <p class="text-gray-500 dark:text-gray-400 mb-1">Total</p>
                    <p class="font-semibold text-gray-900 dark:text-white" id="total-${index}">L ${(p.cantidad * p.precio).toFixed(2)}</p>
                </div>
            </div>
        </div>
    `).join('');

    areaResultados.classList.remove('hidden');
}

// ===================================
// EDICIÓN DE PRODUCTOS
// ===================================

// Variable para guardar el estado original antes de editar
let estadoOriginal = {};

function editarProducto(index) {
    // Guardar estado original
    estadoOriginal[index] = { ...productosExtraidos[index] };

    // Ocultar displays y mostrar inputs
    document.getElementById(`nombre-display-${index}`).classList.add('hidden');
    document.getElementById(`nombre-edit-${index}`).classList.remove('hidden');

    document.getElementById(`codigo-display-${index}`).classList.add('hidden');
    document.getElementById(`codigo-edit-${index}`).classList.remove('hidden');

    document.getElementById(`cantidad-display-${index}`).classList.add('hidden');
    document.getElementById(`cantidad-edit-${index}`).classList.remove('hidden');

    document.getElementById(`precio-display-${index}`).classList.add('hidden');
    document.getElementById(`precio-edit-${index}`).classList.remove('hidden');

    // Cambiar botones
    document.getElementById(`btn-editar-${index}`).classList.add('hidden');
    document.getElementById(`btn-guardar-${index}`).classList.remove('hidden');
    document.getElementById(`btn-cancelar-${index}`).classList.remove('hidden');

    // Focus en el primer campo
    document.getElementById(`nombre-edit-${index}`).focus();
}

function guardarEdicion(index) {
    // Obtener valores editados
    const nombre = document.getElementById(`nombre-edit-${index}`).value.trim();
    const codigo = document.getElementById(`codigo-edit-${index}`).value.trim();
    const cantidad = parseInt(document.getElementById(`cantidad-edit-${index}`).value) || 1;
    const precio = parseFloat(document.getElementById(`precio-edit-${index}`).value) || 0;

    // Validar
    if (!nombre) {
        mostrarNotificacion('warning', 'Campo requerido', 'El nombre del producto es obligatorio');
        return;
    }

    if (cantidad <= 0) {
        mostrarNotificacion('warning', 'Cantidad inválida', 'La cantidad debe ser mayor a 0');
        return;
    }

    if (precio < 0) {
        mostrarNotificacion('warning', 'Precio inválido', 'El precio no puede ser negativo');
        return;
    }

    // Actualizar producto en el array
    productosExtraidos[index] = {
        ...productosExtraidos[index],
        nombre: nombre,
        codigo: codigo,
        cantidad: cantidad,
        precio: precio
    };

    // Actualizar displays
    document.getElementById(`nombre-display-${index}`).textContent = nombre;
    document.getElementById(`codigo-display-${index}`).textContent = codigo || 'N/A';
    document.getElementById(`cantidad-display-${index}`).textContent = cantidad;
    document.getElementById(`precio-display-${index}`).textContent = `L ${precio.toFixed(2)}`;
    document.getElementById(`total-${index}`).textContent = `L ${(cantidad * precio).toFixed(2)}`;

    // Volver a modo display
    salirModoEdicion(index);

    // Mostrar notificación
    mostrarNotificacion('success', 'Cambios guardados', 'Los cambios se aplicarán al guardar la factura');

    // Limpiar estado original
    delete estadoOriginal[index];
}

function cancelarEdicion(index) {
    // Restaurar valores originales si existen
    if (estadoOriginal[index]) {
        document.getElementById(`nombre-edit-${index}`).value = estadoOriginal[index].nombre;
        document.getElementById(`codigo-edit-${index}`).value = estadoOriginal[index].codigo || '';
        document.getElementById(`cantidad-edit-${index}`).value = estadoOriginal[index].cantidad;
        document.getElementById(`precio-edit-${index}`).value = estadoOriginal[index].precio;

        delete estadoOriginal[index];
    }

    // Volver a modo display
    salirModoEdicion(index);
}

function salirModoEdicion(index) {
    // Mostrar displays y ocultar inputs
    document.getElementById(`nombre-display-${index}`).classList.remove('hidden');
    document.getElementById(`nombre-edit-${index}`).classList.add('hidden');

    document.getElementById(`codigo-display-${index}`).classList.remove('hidden');
    document.getElementById(`codigo-edit-${index}`).classList.add('hidden');

    document.getElementById(`cantidad-display-${index}`).classList.remove('hidden');
    document.getElementById(`cantidad-edit-${index}`).classList.add('hidden');

    document.getElementById(`precio-display-${index}`).classList.remove('hidden');
    document.getElementById(`precio-edit-${index}`).classList.add('hidden');

    // Cambiar botones
    document.getElementById(`btn-editar-${index}`).classList.remove('hidden');
    document.getElementById(`btn-guardar-${index}`).classList.add('hidden');
    document.getElementById(`btn-cancelar-${index}`).classList.add('hidden');
}

function eliminarProducto(index) {
    productosExtraidos.splice(index, 1);
    if (productosExtraidos.length === 0) {
        reiniciar();
    } else {
        mostrarResultados(productosExtraidos);
    }
}

// ===================================
// GUARDAR PRODUCTOS
// ===================================
async function guardarProductos() {
    if (productosExtraidos.length === 0) {
        mostrarNotificacion('warning', 'Sin Productos', 'No hay productos para guardar');
        return;
    }

    try {
        const response = await fetch('api/guardar_productos_factura.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ productos: productosExtraidos })
        });

        const responseText = await response.text();
        console.log('Respuesta del servidor:', responseText);

        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Error parseando JSON:', e);
            console.error('Respuesta completa:', responseText);
            mostrarNotificacion('error', 'Error de Servidor', 'Error del servidor: ' + responseText.substring(0, 200));
            return;
        }

        if (data.success) {
            mostrarNotificacion('success', '¡Éxito!',
                `${data.nuevos || 0} productos nuevos creados, ${data.actualizados || 0} productos actualizados`);

            setTimeout(() => {
                reiniciar();
            }, 2000);
        } else {
            mostrarNotificacion('error', 'Error', data.message || 'Error al guardar productos');
        }

    } catch (error) {
        console.error('Error:', error);
        mostrarNotificacion('error', 'Error', 'Error al guardar productos');
    }
}

// ===================================
// MOBILE UPLOAD CON QR
// ===================================
let uploadSessionId = generateSessionId();
let pollingInterval = null;

function generateSessionId() {
    return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

function mostrarModalMovil() {
    const mobileUrl = window.location.origin + window.location.pathname.replace('escanear_factura.php', 'mobile_upload_factura.php') + '?session=' + uploadSessionId;

    // Crear modal
    const modal = document.createElement('div');
    modal.id = 'mobileModal';
    modal.className = 'fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-[9999] p-4';
    modal.innerHTML = `
        <div class="bg-white dark:bg-[#192233] rounded-2xl shadow-2xl max-w-md w-full p-8 relative animate-slideUp">
            <button onclick="cerrarModalMovil()" class="absolute top-4 right-4 text-gray-400 hover:text-white transition-colors">
                <span class="material-symbols-outlined text-3xl">close</span>
            </button>
            
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-600/20 rounded-full mb-4">
                    <span class="material-symbols-outlined text-purple-400 text-4xl">smartphone</span>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Subir desde Móvil</h2>
                <p class="text-gray-500 dark:text-gray-400 text-sm">Escanea el código QR o usa el enlace</p>
            </div>

            <div class="bg-white p-6 rounded-xl mb-6 flex items-center justify-center">
                <div id="qrcode"></div>
            </div>

            <div class="space-y-4">
                <div class="flex items-center gap-3 p-4 bg-gray-100 dark:bg-[#101622] rounded-lg border border-gray-200 dark:border-[#324467]">
                    <span class="material-symbols-outlined text-blue-400">link</span>
                    <input type="text" readonly value="${mobileUrl}" class="flex-1 bg-transparent text-gray-700 dark:text-gray-300 text-sm outline-none" id="mobileUrlInput">
                    <button onclick="copiarUrlMovil()" class="text-blue-400 hover:text-blue-300 transition-colors">
                        <span class="material-symbols-outlined">content_copy</span>
                    </button>
                </div>

                <button onclick="abrirUrlMovil()" class="w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                    <span class="material-symbols-outlined">open_in_new</span>
                    <span>Abrir en Nueva Pestaña</span>
                </button>
            </div>

            <div class="mt-6 p-4 bg-blue-900/20 border border-blue-700/50 rounded-lg">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-blue-400 text-xl">info</span>
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        <p class="font-semibold mb-1">Instrucciones:</p>
                        <ol class="list-decimal list-inside space-y-1 text-gray-600 dark:text-gray-400">
                            <li>Escanea el QR con tu móvil</li>
                            <li>Toma fotos de las facturas</li>
                            <li>Los archivos se cargarán aquí automáticamente</li>
                        </ol>
                    </div>
                </div>
            </div>

            <div id="uploadStatus" class="mt-4 hidden">
                <div class="flex items-center gap-3 p-4 bg-green-900/20 border border-green-700 rounded-lg">
                    <span class="material-symbols-outlined text-green-400">check_circle</span>
                    <span class="text-green-200 text-sm" id="uploadStatusText"></span>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Generar código QR
    new QRCode(document.getElementById("qrcode"), {
        text: mobileUrl,
        width: 200,
        height: 200,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });

    // Iniciar polling para verificar archivos subidos
    iniciarPollingArchivos();
}

function cerrarModalMovil() {
    const modal = document.getElementById('mobileModal');
    if (modal) {
        modal.remove();
    }
    detenerPollingArchivos();
}

function copiarUrlMovil() {
    const input = document.getElementById('mobileUrlInput');
    input.select();
    document.execCommand('copy');
    mostrarNotificacion('success', 'Copiado', 'Enlace copiado al portapapeles');
}

function abrirUrlMovil() {
    const url = document.getElementById('mobileUrlInput').value;
    window.open(url, '_blank');
}

function iniciarPollingArchivos() {
    pollingInterval = setInterval(async () => {
        try {
            const response = await fetch(`check_mobile_uploads_factura.php?session=${uploadSessionId}`);
            const data = await response.json();

            if (data.files && data.files.length > 0) {
                // Archivos recibidos
                const statusDiv = document.getElementById('uploadStatus');
                const statusText = document.getElementById('uploadStatusText');
                statusText.textContent = `${data.files.length} archivo(s) recibido(s) desde móvil`;
                statusDiv.classList.remove('hidden');

                // Procesar el primer archivo automáticamente
                procesarArchivoMovil(data.files[0]);

                // Cerrar modal después de 2 segundos
                setTimeout(() => {
                    cerrarModalMovil();
                }, 2000);
            }
        } catch (error) {
            console.error('Error al verificar archivos:', error);
        }
    }, 2000); // Verificar cada 2 segundos
}

function detenerPollingArchivos() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

async function procesarArchivoMovil(fileInfo) {
    try {
        // Cargar la imagen desde el servidor
        const response = await fetch(fileInfo.path);
        const blob = await response.blob();

        // Convertir a base64 para procesamiento
        const reader = new FileReader();
        reader.onload = function (e) {
            imagenCapturada = e.target.result;
            mostrarVistaPrevia(imagenCapturada);
        };
        reader.readAsDataURL(blob);

    } catch (error) {
        console.error('Error al procesar archivo móvil:', error);
        mostrarNotificacion('error', 'Error', 'Error al cargar el archivo desde móvil');
    }
}

// Función para parsear texto extraído localmente (Tesseract.js)
// Incluye los 7 patrones de detección
function parsearTextoLocal(texto) {
    const productos = [];
    const lineas = texto.split('\n');

    for (let i = 0; i < lineas.length; i++) {
        const linea = lineas[i].trim();
        if (!linea) continue;

        // PATRÓN 1: FORMATO DE FILA COMPLETA
        // 7501234567890 COCA COLA 2L 2 45.50
        const patron1 = /^(\d{13})\s+(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i;
        const match1 = linea.match(patron1);
        if (match1) {
            productos.push({
                codigo: match1[1],
                nombre: match1[1] + ' ' + match1[2].trim(),
                cantidad: parseInt(match1[3]),
                precio: parseFloat(match1[4])
            });
            continue;
        }

        // PATRÓN 2: FORMATO POR COLUMNAS (código en línea separada)
        if (/^\d{13}$/.test(linea) && i + 1 < lineas.length) {
            const codigo = linea;
            const siguienteLinea = lineas[i + 1].trim();

            for (let j = i + 1; j < Math.min(i + 4, lineas.length); j++) {
                const lineaBusqueda = lineas[j].trim();

                // Descripción + Cantidad + Precio
                const patron2a = /^(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i;
                const match2a = lineaBusqueda.match(patron2a);
                if (match2a) {
                    productos.push({
                        codigo: codigo,
                        nombre: codigo + ' ' + match2a[1].trim(),
                        cantidad: parseInt(match2a[2]),
                        precio: parseFloat(match2a[3])
                    });
                    break;
                }

                // Solo cantidad y precio
                const patron2b = /^(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i;
                const match2b = lineaBusqueda.match(patron2b);
                if (match2b) {
                    productos.push({
                        codigo: codigo,
                        nombre: codigo + ' ' + siguienteLinea,
                        cantidad: parseInt(match2b[1]),
                        precio: parseFloat(match2b[2])
                    });
                    break;
                }
            }
        }

        // PATRÓN 3: FORMATO CON CÓDIGO EMBEBIDO
        // COCA COLA 2L (7501234567890) 2 45.50
        const patron3 = /^(.+?)\(?(\d{13})\)?\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i;
        const match3 = linea.match(patron3);
        if (match3) {
            productos.push({
                codigo: match3[2],
                nombre: match3[2] + ' ' + match3[1].trim(),
                cantidad: parseInt(match3[3]),
                precio: parseFloat(match3[4])
            });
            continue;
        }

        // PATRÓN 4: FORMATO DE TABLA CON SEPARADORES
        // COCA COLA | 7501234567890 | 2 | 45.50
        const patron4 = /^(.+?)\s*[\|\/]\s*(\d{13})\s*[\|\/]\s*(\d+)\s*[\|\/]\s*(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i;
        const match4 = linea.match(patron4);
        if (match4) {
            productos.push({
                codigo: match4[2],
                nombre: match4[2] + ' ' + match4[1].trim(),
                cantidad: parseInt(match4[3]),
                precio: parseFloat(match4[4])
            });
            continue;
        }

        // PATRÓN 5: FORMATO INVERSO (precio primero)
        // 45.50 2 COCA COLA 2L 7501234567890
        const patron5 = /^(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)\s+(\d+)\s+(.+?)\s+(\d{13})$/i;
        const match5 = linea.match(patron5);
        if (match5) {
            productos.push({
                codigo: match5[4],
                nombre: match5[4] + ' ' + match5[3].trim(),
                cantidad: parseInt(match5[2]),
                precio: parseFloat(match5[1])
            });
            continue;
        }

        // PATRÓN 6: FORMATO CON TABULACIONES
        // 7501234567890    COCA COLA 2L    2    45.50
        const patron6 = /^(\d{13})\s{2,}(.+?)\s{2,}(\d+)\s{2,}(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i;
        const match6 = linea.match(patron6);
        if (match6) {
            productos.push({
                codigo: match6[1],
                nombre: match6[1] + ' ' + match6[2].trim(),
                cantidad: parseInt(match6[3]),
                precio: parseFloat(match6[4])
            });
            continue;
        }

        // PATRÓN 7: FORMATO COMPACTO
        // 7501234567890COCACOLA2L2 45.50
        const patron7 = /^(\d{13})([A-Z\s]+)(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i;
        const match7 = linea.match(patron7);
        if (match7) {
            productos.push({
                codigo: match7[1],
                nombre: match7[1] + ' ' + match7[2].trim(),
                cantidad: parseInt(match7[3]),
                precio: parseFloat(match7[4])
            });
            continue;
        }
    }

    return {
        success: productos.length > 0,
        productos: productos,
        message: productos.length > 0 ? `${productos.length} productos detectados` : 'No se detectaron productos'
    };
}

console.log('✅ Sistema de Escaneo de Facturas cargado');

