// ===================================
// SISTEMA DE ALERTAS VISUALES
// ===================================

class AlertSystem {
    constructor() {
        this.alertas = [];
        this.intervalo = null;
        this.inicializar();
    }

    inicializar() {
        // Crear contenedor de toasts si no existe
        if (!document.getElementById('toast-container')) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-4 right-4 z-[10000] space-y-3 max-w-sm';
            document.body.appendChild(container);
        }

        // Iniciar polling cada 5 segundos
        this.iniciarPolling();

        console.log('✅ Sistema de Alertas inicializado');
    }

    iniciarPolling() {
        // Verificar alertas inmediatamente
        this.verificarAlertas();

        // Luego cada 5 segundos
        this.intervalo = setInterval(() => {
            this.verificarAlertas();
        }, 5000);
    }

    detenerPolling() {
        if (this.intervalo) {
            clearInterval(this.intervalo);
            this.intervalo = null;
        }
    }

    async verificarAlertas() {
        try {
            const response = await fetch('api/verificar_alertas.php');
            const data = await response.json();

            if (data.success && data.alertas && data.alertas.length > 0) {
                data.alertas.forEach(alerta => {
                    this.mostrarAlerta(alerta);
                });
            }
        } catch (error) {
            console.error('Error verificando alertas:', error);
        }
    }

    mostrarAlerta(alerta) {
        // Evitar duplicados
        const id = `alerta-${alerta.tipo}-${alerta.id || Date.now()}`;
        if (document.getElementById(id)) return;

        const container = document.getElementById('toast-container');
        if (!container) return;

        // Configuración de colores por tipo
        const config = {
            'sin_stock': {
                bg: 'bg-red-500',
                icon: 'error',
                iconColor: 'text-white'
            },
            'por_vencer': {
                bg: 'bg-orange-500',
                icon: 'schedule',
                iconColor: 'text-white'
            },
            'cliente_frecuente': {
                bg: 'bg-blue-500',
                icon: 'star',
                iconColor: 'text-white'
            },
            'poco_efectivo': {
                bg: 'bg-yellow-500',
                icon: 'warning',
                iconColor: 'text-white'
            },
            'meta_alcanzada': {
                bg: 'bg-green-500',
                icon: 'celebration',
                iconColor: 'text-white'
            },
            'default': {
                bg: 'bg-slate-500',
                icon: 'info',
                iconColor: 'text-white'
            }
        };

        const cfg = config[alerta.tipo] || config.default;

        // Crear toast
        const toast = document.createElement('div');
        toast.id = id;
        toast.className = `${cfg.bg} text-white rounded-lg shadow-2xl p-4 transform transition-all duration-300 ease-out translate-x-full opacity-0 flex items-start gap-3 min-w-[320px] max-w-sm`;

        toast.innerHTML = `
            <div class="flex-shrink-0">
                <span class="material-symbols-outlined ${cfg.iconColor} text-3xl">${cfg.icon}</span>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="font-bold text-sm mb-1">${alerta.titulo || 'Alerta'}</h4>
                <p class="text-xs opacity-90 break-words">${alerta.mensaje}</p>
                ${alerta.accion ? `
                    <button onclick="${alerta.accion}" class="mt-2 text-xs font-bold underline hover:no-underline">
                        ${alerta.accion_texto || 'Ver más'}
                    </button>
                ` : ''}
            </div>
            <button onclick="cerrarAlerta('${id}')" class="flex-shrink-0 hover:bg-white/20 rounded p-1 transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        `;

        container.appendChild(toast);

        // Animar entrada
        setTimeout(() => {
            toast.classList.remove('translate-x-full', 'opacity-0');
        }, 10);

        // Sonido (opcional)
        if (alerta.sonido !== false) {
            this.reproducirSonido(alerta.tipo);
        }

        // Auto-cerrar después de 8 segundos
        setTimeout(() => {
            this.cerrarAlerta(id);
        }, 8000);
    }

    cerrarAlerta(id) {
        const toast = document.getElementById(id);
        if (!toast) return;

        toast.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }

    reproducirSonido(tipo) {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        // Diferentes tonos según el tipo
        const tonos = {
            'sin_stock': 300,
            'por_vencer': 500,
            'cliente_frecuente': 800,
            'poco_efectivo': 400,
            'meta_alcanzada': 1000
        };

        oscillator.frequency.value = tonos[tipo] || 600;
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.2);
    }

    // Método para mostrar alerta manual
    alerta(titulo, mensaje, tipo = 'default', opciones = {}) {
        this.mostrarAlerta({
            titulo,
            mensaje,
            tipo,
            ...opciones
        });
    }
}

// Función global para cerrar alertas
function cerrarAlerta(id) {
    if (window.alertSystem) {
        window.alertSystem.cerrarAlerta(id);
    }
}

// Inicializar sistema de alertas
document.addEventListener('DOMContentLoaded', function () {
    window.alertSystem = new AlertSystem();
});

// Funciones de utilidad para mostrar alertas desde cualquier parte
function mostrarExito(mensaje, titulo = '¡Éxito!') {
    if (window.alertSystem) {
        window.alertSystem.alerta(titulo, mensaje, 'meta_alcanzada');
    }
}

function mostrarError(mensaje, titulo = 'Error') {
    if (window.alertSystem) {
        window.alertSystem.alerta(titulo, mensaje, 'sin_stock');
    }
}

function mostrarAdvertencia(mensaje, titulo = 'Advertencia') {
    if (window.alertSystem) {
        window.alertSystem.alerta(titulo, mensaje, 'poco_efectivo');
    }
}

function mostrarInfo(mensaje, titulo = 'Información') {
    if (window.alertSystem) {
        window.alertSystem.alerta(titulo, mensaje, 'cliente_frecuente');
    }
}

console.log('✅ Sistema de Alertas Visuales cargado');
