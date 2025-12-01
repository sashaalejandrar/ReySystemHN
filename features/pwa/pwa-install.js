/**
 * PWA Install Prompt
 * Maneja la instalación de la PWA
 */

let deferredPrompt;
let isInstalled = false;

// Detectar si ya está instalada
window.addEventListener('beforeinstallprompt', (e) => {
    // Prevenir el prompt automático
    e.preventDefault();
    deferredPrompt = e;

    // Mostrar banner de instalación
    showInstallBanner();
});

// Detectar cuando se instala
window.addEventListener('appinstalled', () => {
    console.log('[PWA] App installed');
    isInstalled = true;
    hideInstallBanner();
    showNotification('¡App instalada exitosamente!', 'success');
});

// Registrar Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/ReySystemDemo/service-worker.js')
            .then((registration) => {
                console.log('[PWA] Service Worker registered:', registration.scope);
            })
            .catch((error) => {
                console.error('[PWA] Service Worker registration failed:', error);
            });
    });
}

// Mostrar banner de instalación
function showInstallBanner() {
    // Verificar si ya se mostró antes
    if (localStorage.getItem('pwa_install_dismissed')) {
        return;
    }

    const banner = document.createElement('div');
    banner.id = 'pwaInstallBanner';
    banner.className = 'fixed bottom-4 left-4 right-4 md:left-auto md:right-4 md:w-96 bg-white dark:bg-slate-800 rounded-xl shadow-2xl border border-slate-200 dark:border-slate-700 p-4 z-50 transform transition-all';
    banner.innerHTML = `
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-2xl">install_mobile</span>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="font-semibold text-slate-900 dark:text-white mb-1">Instalar ReySystem</h4>
                <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">
                    Instala la app para acceso rápido y funcionalidad offline
                </p>
                <div class="flex gap-2">
                    <button onclick="installPWA()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition">
                        Instalar
                    </button>
                    <button onclick="dismissInstallBanner()" class="px-4 py-2 bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 transition">
                        Ahora no
                    </button>
                </div>
            </div>
            <button onclick="dismissInstallBanner()" class="flex-shrink-0 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
    `;

    document.body.appendChild(banner);

    // Animación de entrada
    setTimeout(() => {
        banner.style.transform = 'translateY(0)';
    }, 100);
}

// Ocultar banner
function hideInstallBanner() {
    const banner = document.getElementById('pwaInstallBanner');
    if (banner) {
        banner.remove();
    }
}

// Descartar banner
function dismissInstallBanner() {
    hideInstallBanner();
    localStorage.setItem('pwa_install_dismissed', 'true');
}

// Instalar PWA
async function installPWA() {
    if (!deferredPrompt) {
        console.log('[PWA] No install prompt available');
        return;
    }

    // Mostrar el prompt
    deferredPrompt.prompt();

    // Esperar la respuesta del usuario
    const { outcome } = await deferredPrompt.userChoice;
    console.log('[PWA] User choice:', outcome);

    if (outcome === 'accepted') {
        console.log('[PWA] User accepted install');
    } else {
        console.log('[PWA] User dismissed install');
    }

    // Limpiar el prompt
    deferredPrompt = null;
    hideInstallBanner();
}

// Verificar si está en modo standalone (instalada)
function isRunningStandalone() {
    return window.matchMedia('(display-mode: standalone)').matches ||
        window.navigator.standalone === true;
}

// Mostrar indicador si está instalada
if (isRunningStandalone()) {
    console.log('[PWA] Running in standalone mode');
    isInstalled = true;
}

// Función auxiliar para notificaciones (si existe)
function showNotification(message, type) {
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
    } else {
        console.log(`[PWA] ${type}: ${message}`);
    }
}
