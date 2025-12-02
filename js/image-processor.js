/**
 * Procesador de Imágenes Avanzado para Mejorar OCR
 * Implementa técnicas avanzadas de procesamiento de imágenes
 * para maximizar la precisión del OCR en facturas y tickets
 */

class ImageProcessor {
    constructor() {
        this.canvas = document.createElement('canvas');
        this.ctx = this.canvas.getContext('2d');
    }

    /**
     * Carga una imagen desde File, Blob o URL
     */
    loadImage(input) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = reject;

            if (input instanceof File || input instanceof Blob) {
                const reader = new FileReader();
                reader.onload = (e) => img.src = e.target.result;
                reader.onerror = reject;
                reader.readAsDataURL(input);
            } else if (typeof input === 'string') {
                img.src = input;
            } else {
                reject(new Error('Tipo de entrada no válido'));
            }
        });
    }

    /**
     * Redimensiona la imagen manteniendo la proporción
     */
    resizeImage(img, maxSize) {
        let width = img.width;
        let height = img.height;

        if (width > maxSize || height > maxSize) {
            if (width > height) {
                height = (height / width) * maxSize;
                width = maxSize;
            } else {
                width = (width / height) * maxSize;
                height = maxSize;
            }
        }

        this.canvas.width = width;
        this.canvas.height = height;
        this.ctx.drawImage(img, 0, 0, width, height);

        return this.canvas.toDataURL('image/jpeg', 0.85);
    }

    /**
     * Convierte imagen a escala de grises
     */
    async toGrayscale(imageData) {
        const img = new Image();
        img.src = imageData;

        return new Promise((resolve) => {
            img.onload = () => {
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.ctx.drawImage(img, 0, 0);

                const imgData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const data = imgData.data;

                for (let i = 0; i < data.length; i += 4) {
                    const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                    data[i] = data[i + 1] = data[i + 2] = gray;
                }

                this.ctx.putImageData(imgData, 0, 0);
                resolve(this.canvas.toDataURL('image/jpeg', 0.95));
            };
        });
    }

    /**
     * Corrección Gamma automática
     * Ajusta la exposición de la imagen
     */
    async autoGamma(imageData) {
        const img = new Image();
        img.src = imageData;

        return new Promise((resolve) => {
            img.onload = () => {
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.ctx.drawImage(img, 0, 0);

                const imgData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const data = imgData.data;

                // Calcular brillo promedio
                let sum = 0;
                for (let i = 0; i < data.length; i += 4) {
                    sum += (data[i] + data[i + 1] + data[i + 2]) / 3;
                }
                const avgBrightness = sum / (data.length / 4);

                // Calcular gamma óptimo
                const targetBrightness = 128;
                const gamma = Math.log(targetBrightness / 255) / Math.log(avgBrightness / 255);
                const clampedGamma = Math.max(0.5, Math.min(2.0, gamma));

                // Aplicar corrección gamma
                const lookupTable = new Uint8Array(256);
                for (let i = 0; i < 256; i++) {
                    lookupTable[i] = Math.min(255, Math.max(0,
                        Math.pow(i / 255, 1 / clampedGamma) * 255
                    ));
                }

                for (let i = 0; i < data.length; i += 4) {
                    data[i] = lookupTable[data[i]];
                    data[i + 1] = lookupTable[data[i + 1]];
                    data[i + 2] = lookupTable[data[i + 2]];
                }

                this.ctx.putImageData(imgData, 0, 0);
                resolve(this.canvas.toDataURL('image/jpeg', 0.95));
            };
        });
    }

    /**
     * Filtro Bilateral - Reduce ruido preservando bordes
     * Mejor que el desenfoque gaussiano para OCR
     */
    async bilateralFilter(imageData, sigmaColor = 75, sigmaSpace = 75) {
        const img = new Image();
        img.src = imageData;

        return new Promise((resolve) => {
            img.onload = () => {
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.ctx.drawImage(img, 0, 0);

                const imgData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const data = imgData.data;
                const width = this.canvas.width;
                const height = this.canvas.height;
                const output = new Uint8ClampedArray(data);

                const kernelRadius = 5;
                const sigmaColorSq = sigmaColor * sigmaColor;
                const sigmaSpaceSq = sigmaSpace * sigmaSpace;

                for (let y = kernelRadius; y < height - kernelRadius; y++) {
                    for (let x = kernelRadius; x < width - kernelRadius; x++) {
                        const idx = (y * width + x) * 4;
                        let sumR = 0, sumG = 0, sumB = 0, sumWeight = 0;

                        for (let ky = -kernelRadius; ky <= kernelRadius; ky++) {
                            for (let kx = -kernelRadius; kx <= kernelRadius; kx++) {
                                const nIdx = ((y + ky) * width + (x + kx)) * 4;

                                // Diferencia espacial
                                const spatialDist = kx * kx + ky * ky;

                                // Diferencia de color
                                const colorDist =
                                    Math.pow(data[idx] - data[nIdx], 2) +
                                    Math.pow(data[idx + 1] - data[nIdx + 1], 2) +
                                    Math.pow(data[idx + 2] - data[nIdx + 2], 2);

                                const weight = Math.exp(
                                    -spatialDist / (2 * sigmaSpaceSq) -
                                    colorDist / (2 * sigmaColorSq)
                                );

                                sumR += data[nIdx] * weight;
                                sumG += data[nIdx + 1] * weight;
                                sumB += data[nIdx + 2] * weight;
                                sumWeight += weight;
                            }
                        }

                        output[idx] = sumR / sumWeight;
                        output[idx + 1] = sumG / sumWeight;
                        output[idx + 2] = sumB / sumWeight;
                    }
                }

                const newImageData = new ImageData(output, width, height);
                this.ctx.putImageData(newImageData, 0, 0);
                resolve(this.canvas.toDataURL('image/jpeg', 0.95));
            };
        });
    }

    /**
     * CLAHE - Contrast Limited Adaptive Histogram Equalization
     * Mejora el contraste local adaptándose a diferentes regiones
     */
    async applyCLAHE(imageData, clipLimit = 2.0, tileSize = 8) {
        const img = new Image();
        img.src = imageData;

        return new Promise((resolve) => {
            img.onload = () => {
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.ctx.drawImage(img, 0, 0);

                const imgData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const data = imgData.data;
                const width = this.canvas.width;
                const height = this.canvas.height;

                // Convertir a escala de grises para procesamiento
                const gray = new Uint8Array(width * height);
                for (let i = 0; i < data.length; i += 4) {
                    const idx = i / 4;
                    gray[idx] = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                }

                // Dividir imagen en tiles
                const tilesX = Math.ceil(width / tileSize);
                const tilesY = Math.ceil(height / tileSize);

                // Calcular histograma para cada tile y aplicar ecualización
                for (let ty = 0; ty < tilesY; ty++) {
                    for (let tx = 0; tx < tilesX; tx++) {
                        const x0 = tx * tileSize;
                        const y0 = ty * tileSize;
                        const x1 = Math.min(x0 + tileSize, width);
                        const y1 = Math.min(y0 + tileSize, height);

                        // Calcular histograma del tile
                        const hist = new Array(256).fill(0);
                        for (let y = y0; y < y1; y++) {
                            for (let x = x0; x < x1; x++) {
                                hist[gray[y * width + x]]++;
                            }
                        }

                        // Aplicar clip limit
                        const pixelsPerBin = (x1 - x0) * (y1 - y0) / 256;
                        const clipValue = clipLimit * pixelsPerBin;
                        let excess = 0;

                        for (let i = 0; i < 256; i++) {
                            if (hist[i] > clipValue) {
                                excess += hist[i] - clipValue;
                                hist[i] = clipValue;
                            }
                        }

                        const redistribution = excess / 256;
                        for (let i = 0; i < 256; i++) {
                            hist[i] += redistribution;
                        }

                        // Crear lookup table
                        const lut = new Uint8Array(256);
                        let sum = 0;
                        const totalPixels = (x1 - x0) * (y1 - y0);

                        for (let i = 0; i < 256; i++) {
                            sum += hist[i];
                            lut[i] = Math.min(255, (sum / totalPixels) * 255);
                        }

                        // Aplicar transformación
                        for (let y = y0; y < y1; y++) {
                            for (let x = x0; x < x1; x++) {
                                const idx = (y * width + x) * 4;
                                const grayVal = gray[y * width + x];
                                const newVal = lut[grayVal];
                                const ratio = newVal / (grayVal || 1);

                                data[idx] = Math.min(255, data[idx] * ratio);
                                data[idx + 1] = Math.min(255, data[idx + 1] * ratio);
                                data[idx + 2] = Math.min(255, data[idx + 2] * ratio);
                            }
                        }
                    }
                }

                this.ctx.putImageData(imgData, 0, 0);
                resolve(this.canvas.toDataURL('image/jpeg', 0.95));
            };
        });
    }

    /**
     * Adaptive Thresholding usando método de Otsu
     * Encuentra el umbral óptimo automáticamente
     */
    async adaptiveThreshold(imageData) {
        const img = new Image();
        img.src = imageData;

        return new Promise((resolve) => {
            img.onload = () => {
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.ctx.drawImage(img, 0, 0);

                const imgData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const data = imgData.data;

                // Convertir a escala de grises y calcular histograma
                const histogram = new Array(256).fill(0);
                const gray = new Uint8Array(data.length / 4);

                for (let i = 0; i < data.length; i += 4) {
                    const grayVal = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                    gray[i / 4] = grayVal;
                    histogram[Math.floor(grayVal)]++;
                }

                // Método de Otsu para encontrar umbral óptimo
                const total = gray.length;
                let sum = 0;
                for (let i = 0; i < 256; i++) {
                    sum += i * histogram[i];
                }

                let sumB = 0;
                let wB = 0;
                let wF = 0;
                let maxVariance = 0;
                let threshold = 0;

                for (let t = 0; t < 256; t++) {
                    wB += histogram[t];
                    if (wB === 0) continue;

                    wF = total - wB;
                    if (wF === 0) break;

                    sumB += t * histogram[t];

                    const mB = sumB / wB;
                    const mF = (sum - sumB) / wF;

                    const variance = wB * wF * (mB - mF) * (mB - mF);

                    if (variance > maxVariance) {
                        maxVariance = variance;
                        threshold = t;
                    }
                }

                // Aplicar umbral
                for (let i = 0; i < data.length; i += 4) {
                    const value = gray[i / 4] > threshold ? 255 : 0;
                    data[i] = data[i + 1] = data[i + 2] = value;
                }

                this.ctx.putImageData(imgData, 0, 0);
                resolve(this.canvas.toDataURL('image/jpeg', 0.95));
            };
        });
    }

    /**
     * Operación morfológica: Dilatación
     * Expande regiones blancas
     */
    async dilate(imageData, kernelSize = 3) {
        const img = new Image();
        img.src = imageData;

        return new Promise((resolve) => {
            img.onload = () => {
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.ctx.drawImage(img, 0, 0);

                const imgData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const data = imgData.data;
                const width = this.canvas.width;
                const height = this.canvas.height;
                const output = new Uint8ClampedArray(data);

                const radius = Math.floor(kernelSize / 2);

                for (let y = radius; y < height - radius; y++) {
                    for (let x = radius; x < width - radius; x++) {
                        let maxVal = 0;

                        for (let ky = -radius; ky <= radius; ky++) {
                            for (let kx = -radius; kx <= radius; kx++) {
                                const idx = ((y + ky) * width + (x + kx)) * 4;
                                maxVal = Math.max(maxVal, data[idx]);
                            }
                        }

                        const idx = (y * width + x) * 4;
                        output[idx] = output[idx + 1] = output[idx + 2] = maxVal;
                    }
                }

                const newImageData = new ImageData(output, width, height);
                this.ctx.putImageData(newImageData, 0, 0);
                resolve(this.canvas.toDataURL('image/jpeg', 0.95));
            };
        });
    }

    /**
     * Operación morfológica: Erosión
     * Contrae regiones blancas
     */
    async erode(imageData, kernelSize = 3) {
        const img = new Image();
        img.src = imageData;

        return new Promise((resolve) => {
            img.onload = () => {
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.ctx.drawImage(img, 0, 0);

                const imgData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const data = imgData.data;
                const width = this.canvas.width;
                const height = this.canvas.height;
                const output = new Uint8ClampedArray(data);

                const radius = Math.floor(kernelSize / 2);

                for (let y = radius; y < height - radius; y++) {
                    for (let x = radius; x < width - radius; x++) {
                        let minVal = 255;

                        for (let ky = -radius; ky <= radius; ky++) {
                            for (let kx = -radius; kx <= radius; kx++) {
                                const idx = ((y + ky) * width + (x + kx)) * 4;
                                minVal = Math.min(minVal, data[idx]);
                            }
                        }

                        const idx = (y * width + x) * 4;
                        output[idx] = output[idx + 1] = output[idx + 2] = minVal;
                    }
                }

                const newImageData = new ImageData(output, width, height);
                this.ctx.putImageData(newImageData, 0, 0);
                resolve(this.canvas.toDataURL('image/jpeg', 0.95));
            };
        });
    }

    /**
     * Closing morfológico (dilatación seguida de erosión)
     * Conecta caracteres rotos
     */
    async morphologicalClose(imageData, kernelSize = 2) {
        let processed = await this.dilate(imageData, kernelSize);
        processed = await this.erode(processed, kernelSize);
        return processed;
    }

    /**
     * Opening morfológico (erosión seguida de dilatación)
     * Elimina ruido pequeño
     */
    async morphologicalOpen(imageData, kernelSize = 2) {
        let processed = await this.erode(imageData, kernelSize);
        processed = await this.dilate(processed, kernelSize);
        return processed;
    }

    /**
     * Mejora de bordes usando filtro Sobel
     */
    async enhanceEdges(imageData) {
        const img = new Image();
        img.src = imageData;

        return new Promise((resolve) => {
            img.onload = () => {
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.ctx.drawImage(img, 0, 0);

                const imgData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const data = imgData.data;
                const width = this.canvas.width;
                const height = this.canvas.height;

                // Kernels Sobel
                const sobelX = [-1, 0, 1, -2, 0, 2, -1, 0, 1];
                const sobelY = [-1, -2, -1, 0, 0, 0, 1, 2, 1];

                const output = new Uint8ClampedArray(data);

                for (let y = 1; y < height - 1; y++) {
                    for (let x = 1; x < width - 1; x++) {
                        let gx = 0, gy = 0;

                        for (let ky = -1; ky <= 1; ky++) {
                            for (let kx = -1; kx <= 1; kx++) {
                                const idx = ((y + ky) * width + (x + kx)) * 4;
                                const gray = 0.299 * data[idx] + 0.587 * data[idx + 1] + 0.114 * data[idx + 2];
                                const kernelIdx = (ky + 1) * 3 + (kx + 1);

                                gx += gray * sobelX[kernelIdx];
                                gy += gray * sobelY[kernelIdx];
                            }
                        }

                        const magnitude = Math.sqrt(gx * gx + gy * gy);
                        const idx = (y * width + x) * 4;

                        // Combinar con imagen original
                        const edgeStrength = 0.3;
                        output[idx] = Math.min(255, data[idx] + magnitude * edgeStrength);
                        output[idx + 1] = Math.min(255, data[idx + 1] + magnitude * edgeStrength);
                        output[idx + 2] = Math.min(255, data[idx + 2] + magnitude * edgeStrength);
                    }
                }

                const newImageData = new ImageData(output, width, height);
                this.ctx.putImageData(newImageData, 0, 0);
                resolve(this.canvas.toDataURL('image/jpeg', 0.95));
            };
        });
    }

    /**
     * Mejora de trazos de texto
     * Hace el texto más grueso y legible
     */
    async enhanceTextStrokes(imageData) {
        const img = new Image();
        img.src = imageData;

        return new Promise((resolve) => {
            img.onload = () => {
                this.canvas.width = img.width;
                this.canvas.height = img.height;
                this.ctx.drawImage(img, 0, 0);

                const imgData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                const data = imgData.data;

                // Aumentar contraste local para texto
                for (let i = 0; i < data.length; i += 4) {
                    const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];

                    // Si es texto oscuro, hacerlo más oscuro
                    // Si es fondo claro, hacerlo más claro
                    let enhanced;
                    if (gray < 128) {
                        enhanced = Math.max(0, gray - 20);
                    } else {
                        enhanced = Math.min(255, gray + 20);
                    }

                    const ratio = enhanced / (gray || 1);
                    data[i] = Math.min(255, Math.max(0, data[i] * ratio));
                    data[i + 1] = Math.min(255, Math.max(0, data[i + 1] * ratio));
                    data[i + 2] = Math.min(255, Math.max(0, data[i + 2] * ratio));
                }

                this.ctx.putImageData(imgData, 0, 0);
                resolve(this.canvas.toDataURL('image/jpeg', 0.95));
            };
        });
    }

    /**
     * Pipeline completo para facturas - OPTIMIZADO PARA OCR
     * Aplica todas las técnicas avanzadas en el orden óptimo
     */
    async processInvoice(imageInput) {
        console.log('🚀 Iniciando preprocesamiento avanzado de factura...');

        try {
            // 1. Cargar y redimensionar
            const img = await this.loadImage(imageInput);
            let processed = this.resizeImage(img, 2000);
            console.log('✓ Imagen redimensionada');

            // 2. Corrección gamma automática (mejora exposición)
            processed = await this.autoGamma(processed);
            console.log('✓ Gamma corregido');

            // 3. Filtro bilateral (reduce ruido preservando bordes)
            processed = await this.bilateralFilter(processed, 60, 60);
            console.log('✓ Ruido reducido con filtro bilateral');

            // 4. CLAHE (contraste adaptativo)
            processed = await this.applyCLAHE(processed, 2.5, 8);
            console.log('✓ Contraste adaptativo aplicado (CLAHE)');

            // 5. Mejora de bordes
            processed = await this.enhanceEdges(processed);
            console.log('✓ Bordes mejorados');

            // 6. Mejora de trazos de texto
            processed = await this.enhanceTextStrokes(processed);
            console.log('✓ Trazos de texto mejorados');

            // 7. Closing morfológico (conecta caracteres rotos)
            processed = await this.morphologicalClose(processed, 1);
            console.log('✓ Operación morfológica aplicada');

            console.log('✅ Preprocesamiento completado exitosamente');
            return processed;

        } catch (error) {
            console.error('❌ Error en preprocesamiento:', error);
            // Si falla, devolver imagen original redimensionada
            const img = await this.loadImage(imageInput);
            return this.resizeImage(img, 2000);
        }
    }

    /**
     * Pipeline para tickets térmicos
     * Optimizado para texto de baja calidad en papel térmico
     */
    async processThermalReceipt(imageInput) {
        console.log('🎫 Procesando ticket térmico...');

        try {
            const img = await this.loadImage(imageInput);
            let processed = this.resizeImage(img, 2000);

            // Gamma más agresivo para tickets térmicos
            processed = await this.autoGamma(processed);

            // Bilateral filter
            processed = await this.bilateralFilter(processed, 80, 80);

            // CLAHE más agresivo
            processed = await this.applyCLAHE(processed, 3.0, 8);

            // Adaptive threshold (binarización)
            processed = await this.adaptiveThreshold(processed);

            // Morphological operations
            processed = await this.morphologicalClose(processed, 2);

            console.log('✅ Ticket térmico procesado');
            return processed;

        } catch (error) {
            console.error('❌ Error procesando ticket:', error);
            const img = await this.loadImage(imageInput);
            return this.resizeImage(img, 2000);
        }
    }

    /**
     * Pipeline para imágenes de baja calidad
     * Máxima mejora para fotos borrosas o mal iluminadas
     */
    async processLowQuality(imageInput) {
        console.log('📸 Procesando imagen de baja calidad...');

        try {
            const img = await this.loadImage(imageInput);
            let processed = this.resizeImage(img, 2000);

            processed = await this.autoGamma(processed);
            processed = await this.bilateralFilter(processed, 100, 100);
            processed = await this.applyCLAHE(processed, 3.5, 6);
            processed = await this.enhanceEdges(processed);
            processed = await this.enhanceTextStrokes(processed);
            processed = await this.morphologicalClose(processed, 2);

            console.log('✅ Imagen de baja calidad mejorada');
            return processed;

        } catch (error) {
            console.error('❌ Error:', error);
            const img = await this.loadImage(imageInput);
            return this.resizeImage(img, 2000);
        }
    }
}

// Exportar para uso global
window.ImageProcessor = ImageProcessor;
