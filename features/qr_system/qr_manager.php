<?php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: ../../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Sistema QR - ReySystem</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body class="bg-slate-50 dark:bg-slate-900">

<!-- Header -->
<div class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700 px-6 py-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="../../index.php" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">📱 Sistema QR</h1>
                <p class="text-sm text-slate-600 dark:text-slate-400">Generar y escanear códigos QR</p>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="p-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Generador QR -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-blue-600">qr_code_2</span>
                Generar Código QR
            </h2>
            
            <div class="space-y-4">
                <!-- Tipo de QR -->
                <div>
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Tipo de QR</label>
                    <select id="qrType" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white">
                        <option value="product">Producto</option>
                        <option value="payment">Pago</option>
                    </select>
                </div>
                
                <!-- Producto QR -->
                <div id="productQRSection">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Buscar Producto</label>
                    <input type="text" id="productSearch" placeholder="Nombre o código del producto" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"/>
                    <div id="productResults" class="mt-2 max-h-48 overflow-y-auto"></div>
                </div>
                
                <!-- Payment QR -->
                <div id="paymentQRSection" class="hidden">
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Monto</label>
                    <input type="number" id="paymentAmount" placeholder="0.00" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"/>
                    
                    <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2 mt-3">Descripción</label>
                    <input type="text" id="paymentDescription" placeholder="Concepto del pago" class="w-full rounded-lg border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-900 dark:text-white"/>
                    
                    <button onclick="generatePaymentQR()" class="mt-4 w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        Generar QR de Pago
                    </button>
                </div>
                
                <!-- QR Display -->
                <div id="qrDisplay" class="hidden mt-6">
                    <div class="bg-slate-100 dark:bg-slate-700 rounded-lg p-4 text-center">
                        <img id="qrImage" src="" alt="QR Code" class="mx-auto max-w-full"/>
                        <button onclick="downloadQR()" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            <span class="material-symbols-outlined align-middle">download</span>
                            Descargar QR
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Escáner QR -->
        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg">
            <h2 class="text-xl font-bold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-green-600">qr_code_scanner</span>
                Escanear Código QR
            </h2>
            
            <div class="space-y-4">
                <!-- Scanner -->
                <div id="qrScanner" class="bg-slate-100 dark:bg-slate-700 rounded-lg overflow-hidden">
                    <div id="reader" class="w-full"></div>
                </div>
                
                <button id="startScanBtn" onclick="startScanning()" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                    <span class="material-symbols-outlined align-middle">photo_camera</span>
                    Iniciar Escaneo
                </button>
                
                <button id="stopScanBtn" onclick="stopScanning()" class="hidden w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    <span class="material-symbols-outlined align-middle">stop_circle</span>
                    Detener Escaneo
                </button>
                
                <!-- Scan Result -->
                <div id="scanResult" class="hidden mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <h3 class="font-semibold text-blue-900 dark:text-blue-100 mb-2">Resultado del Escaneo:</h3>
                    <div id="scanResultContent" class="text-sm text-blue-800 dark:text-blue-200"></div>
                </div>
            </div>
        </div>
        
    </div>
</div>

<script>
let html5QrcodeScanner = null;
let currentQRUrl = '';

// Cambiar tipo de QR
document.getElementById('qrType').addEventListener('change', function() {
    if (this.value === 'product') {
        document.getElementById('productQRSection').classList.remove('hidden');
        document.getElementById('paymentQRSection').classList.add('hidden');
    } else {
        document.getElementById('productQRSection').classList.add('hidden');
        document.getElementById('paymentQRSection').classList.remove('hidden');
    }
    document.getElementById('qrDisplay').classList.add('hidden');
});

// Buscar productos
let searchTimeout;
document.getElementById('productSearch').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value;
    
    if (query.length < 2) {
        document.getElementById('productResults').innerHTML = '';
        return;
    }
    
    searchTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`../../buscar_producto_api.php?q=${encodeURIComponent(query)}`);
            const products = await response.json();
            
            let html = '<div class="space-y-2">';
            products.forEach(product => {
                html += `
                    <div class="p-3 bg-slate-50 dark:bg-slate-700 rounded-lg cursor-pointer hover:bg-slate-100 dark:hover:bg-slate-600 transition" onclick="generateProductQR('${product.Id}')">
                        <div class="font-semibold text-slate-900 dark:text-white">${product.nombre}</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400">$${product.precio} - Stock: ${product.stock}</div>
                    </div>
                `;
            });
            html += '</div>';
            
            document.getElementById('productResults').innerHTML = html;
        } catch (error) {
            console.error('Error searching products:', error);
        }
    }, 300);
});

// Generar QR de producto
async function generateProductQR(productId) {
    try {
        const formData = new FormData();
        formData.append('action', 'generate_product_qr');
        formData.append('product_id', productId);
        
        const response = await fetch('../../api/qr/actions.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            currentQRUrl = result.qr_url;
            document.getElementById('qrImage').src = result.qr_url;
            document.getElementById('qrDisplay').classList.remove('hidden');
            document.getElementById('productResults').innerHTML = '';
            document.getElementById('productSearch').value = result.product.nombre;
        }
    } catch (error) {
        console.error('Error generating QR:', error);
        alert('Error al generar código QR');
    }
}

// Generar QR de pago
async function generatePaymentQR() {
    const amount = document.getElementById('paymentAmount').value;
    const description = document.getElementById('paymentDescription').value;
    
    if (!amount || amount <= 0) {
        alert('Ingresa un monto válido');
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'generate_payment_qr');
        formData.append('amount', amount);
        formData.append('description', description);
        
        const response = await fetch('../../api/qr/actions.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            currentQRUrl = result.qr_url;
            document.getElementById('qrImage').src = result.qr_url;
            document.getElementById('qrDisplay').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error generating payment QR:', error);
        alert('Error al generar código QR de pago');
    }
}

// Descargar QR
function downloadQR() {
    if (currentQRUrl) {
        window.open(currentQRUrl, '_blank');
    }
}

// Iniciar escaneo
function startScanning() {
    html5QrcodeScanner = new Html5Qrcode("reader");
    
    html5QrcodeScanner.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 250 },
        onScanSuccess,
        onScanError
    ).then(() => {
        document.getElementById('startScanBtn').classList.add('hidden');
        document.getElementById('stopScanBtn').classList.remove('hidden');
    }).catch(err => {
        console.error('Error starting scanner:', err);
        alert('Error al iniciar la cámara. Verifica los permisos.');
    });
}

// Detener escaneo
function stopScanning() {
    if (html5QrcodeScanner) {
        html5QrcodeScanner.stop().then(() => {
            document.getElementById('startScanBtn').classList.remove('hidden');
            document.getElementById('stopScanBtn').classList.add('hidden');
        });
    }
}

// Éxito al escanear
function onScanSuccess(decodedText, decodedResult) {
    console.log('QR Scanned:', decodedText);
    
    try {
        const data = JSON.parse(decodedText);
        
        let html = '<div class="space-y-2">';
        
        if (data.type === 'product') {
            html += `
                <div><strong>Tipo:</strong> Producto</div>
                <div><strong>Nombre:</strong> ${data.nombre}</div>
                <div><strong>Precio:</strong> $${data.precio}</div>
                <div><strong>Stock:</strong> ${data.stock}</div>
                <button onclick="addToSale('${data.id}')" class="mt-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition w-full">
                    Agregar a Venta
                </button>
            `;
        } else if (data.type === 'payment') {
            html += `
                <div><strong>Tipo:</strong> Pago</div>
                <div><strong>Monto:</strong> $${data.amount}</div>
                <div><strong>Descripción:</strong> ${data.description}</div>
            `;
        }
        
        html += '</div>';
        
        document.getElementById('scanResultContent').innerHTML = html;
        document.getElementById('scanResult').classList.remove('hidden');
        
        stopScanning();
    } catch (error) {
        document.getElementById('scanResultContent').innerHTML = `<div>Datos: ${decodedText}</div>`;
        document.getElementById('scanResult').classList.remove('hidden');
    }
}

// Error al escanear
function onScanError(errorMessage) {
    // Ignorar errores de escaneo continuo
}

// Agregar a venta
async function addToSale(productId) {
    alert('Producto agregado a la venta (integración pendiente)');
}
</script>

</body>
</html>
