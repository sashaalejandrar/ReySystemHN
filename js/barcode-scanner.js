function scannerApp() {
    return {
        camaraActiva: false,
        productosEscaneados: [],
        ultimoEscaneado: null,
        modalResumen: false,
        ultimoCodigo: null,
        ultimoTiempo: 0,
        stream: null,
        codigoManual: '',

        get productosParaCrear() {
            return this.productosEscaneados.filter(p => p.estado === 'no_existe');
        },

        get productosSinStock() {
            return this.productosEscaneados.filter(p => p.estado === 'creado_sin_stock');
        },

        get productosEnStock() {
            return this.productosEscaneados.filter(p => p.estado === 'en_stock');
        },

        async iniciarCamara() {
            try {
                // Verificar si el navegador soporta getUserMedia
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Tu navegador no soporta acceso a la cámara. Por favor usa el campo de entrada manual.');
                    return;
                }

                const container = document.getElementById('scanner-container');
                const video = document.createElement('video');
                video.setAttribute('playsinline', '');
                video.style.width = '100%';
                video.style.borderRadius = '12px';
                container.innerHTML = '';
                container.appendChild(video);

                // Solicitar acceso a la cámara trasera
                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'environment', // Cámara trasera
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    }
                });

                video.srcObject = this.stream;
                await video.play();

                this.camaraActiva = true;
                console.log('✅ Cámara iniciada correctamente');

                // Mostrar mensaje de ayuda
                alert('📷 Cámara activada. Como no podemos escanear automáticamente sin HTTPS, por favor:\n\n1. Enfoca el código de barras\n2. Toma una foto clara\n3. Ingresa el código manualmente en el campo de abajo');

            } catch (error) {
                console.error('Error al iniciar cámara:', error);
                let mensaje = 'Error al acceder a la cámara. ';

                if (error.name === 'NotAllowedError') {
                    mensaje += 'Debes permitir el acceso a la cámara.';
                } else if (error.name === 'NotFoundError') {
                    mensaje += 'No se encontró ninguna cámara en tu dispositivo.';
                } else if (error.name === 'NotSupportedError') {
                    mensaje += 'Tu navegador no soporta esta función. Usa el campo de entrada manual.';
                } else {
                    mensaje += error.message;
                }

                alert(mensaje + '\n\nPuedes usar el campo de entrada manual para ingresar códigos.');
            }
        },

        detenerCamara() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }

            const container = document.getElementById('scanner-container');
            if (container) {
                container.innerHTML = '';
            }

            this.camaraActiva = false;
            console.log('🛑 Cámara detenida');
        },

        async procesarCodigoManual() {
            const codigo = this.codigoManual.trim();

            if (!codigo) {
                alert('Por favor ingresa un código de barras');
                return;
            }

            // Verificar si ya fue escaneado
            const yaEscaneado = this.productosEscaneados.find(p => p.codigo_barras === codigo);
            if (yaEscaneado) {
                alert('⚠️ Este producto ya fue escaneado');
                this.codigoManual = '';
                return;
            }

            // Procesar el código
            await this.procesarCodigoEscaneado(codigo);

            // Limpiar campo
            this.codigoManual = '';

            // Sonido de confirmación
            this.playBeep();
        },

        async procesarCodigoEscaneado(codigo) {
            try {
                // 1. Verificar existencia en BD
                const response = await fetch('api/verificar_producto_barcode.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `codigo_barras=${encodeURIComponent(codigo)}`
                });

                const data = await response.json();

                if (!data.success) {
                    console.error('Error:', data.message);
                    alert('Error: ' + data.message);
                    return;
                }

                this.ultimoEscaneado = data;

                let productoParaAgregar = {
                    codigo_barras: codigo,
                    estado: data.estado,
                    stock_actual: data.stock_actual,
                    nombre: '',
                    marca: '',
                    categoria: '',
                    descripcion: '',
                    precio_sugerido: 0,
                    foto_url: ''
                };

                // 2. Si NO existe, enriquecer con IA
                if (data.estado === 'no_existe') {
                    console.log('🤖 Enriqueciendo con IA...');

                    const enrichResponse = await fetch('api/enriquecer_producto_scan.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `codigo_barras=${encodeURIComponent(codigo)}`
                    });

                    const enrichData = await enrichResponse.json();

                    if (enrichData.success && enrichData.datos) {
                        productoParaAgregar = {
                            ...productoParaAgregar,
                            nombre: enrichData.datos.nombre || `Producto ${codigo}`,
                            marca: enrichData.datos.marca || '',
                            categoria: enrichData.datos.categoria || '',
                            descripcion: enrichData.datos.descripcion || '',
                            precio_sugerido: enrichData.datos.precio_sugerido || 0,
                            foto_url: enrichData.datos.foto_url || ''
                        };
                    } else {
                        productoParaAgregar.nombre = `Producto ${codigo}`;
                    }
                } else {
                    // Producto existe, usar datos de BD
                    const prod = data.producto;
                    productoParaAgregar.nombre = prod.Nombre_Producto || prod.NombreProducto || `Producto ${codigo}`;
                    productoParaAgregar.marca = prod.Marca || '';
                    productoParaAgregar.categoria = prod.Grupo || prod.Categoria || '';
                }

                // 3. Agregar a la lista
                this.productosEscaneados.push(productoParaAgregar);
                console.log('✅ Producto agregado a la lista');

            } catch (error) {
                console.error('Error al procesar código:', error);
                alert('Error al procesar el código: ' + error.message);
            }
        },

        eliminarProducto(index) {
            this.productosEscaneados.splice(index, 1);
        },

        limpiarLista() {
            if (confirm('¿Estás seguro de limpiar toda la lista?')) {
                this.productosEscaneados = [];
                this.ultimoEscaneado = null;
            }
        },

        mostrarResumen() {
            this.modalResumen = true;
        },

        async crearProductosNuevos() {
            if (this.productosParaCrear.length === 0) {
                alert('No hay productos nuevos para crear');
                return;
            }

            try {
                // Preparar productos para enviar
                const productosParaCrear = this.productosParaCrear.map(p => ({
                    codigo: p.codigo_barras,
                    nombre: p.nombre,
                    marca: p.marca,
                    categoria: p.categoria,
                    descripcion: p.descripcion,
                    precioUnidad: p.precio_sugerido,
                    foto: p.foto_url
                }));

                // Enviar a la cola para que la PC los reciba
                const response = await fetch('api/scanner_queue.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=push&productos=${encodeURIComponent(JSON.stringify(productosParaCrear))}`
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ ${data.count} productos enviados a la PC.\n\nAbre o actualiza creacion_productos_lote.php en tu PC para verlos.`);

                    // Limpiar lista local
                    this.productosEscaneados = this.productosEscaneados.filter(p => p.estado !== 'no_existe');
                    this.ultimoEscaneado = null;
                } else {
                    alert('❌ Error al enviar productos: ' + data.message);
                }

            } catch (error) {
                console.error('Error:', error);
                alert('❌ Error al enviar productos a la PC');
            }
        },

        playBeep() {
            // Crear un beep corto usando Web Audio API
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.value = 800;
                oscillator.type = 'sine';

                gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.1);
            } catch (e) {
                console.log('No se pudo reproducir sonido');
            }
        },

        init() {
            console.log('📱 Módulo de escaneo inicializado');

            // Limpiar sessionStorage al cargar
            sessionStorage.removeItem('productosEscaneados');
        }
    }
}
