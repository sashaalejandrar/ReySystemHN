/**
 * Interactive Onboarding Tour
 */

class OnboardingTour {
    constructor() {
        this.currentStep = 0;
        this.steps = [
            {
                target: 'body',
                title: '¡Bienvenido a ReySystem! 🎉',
                content: 'Te voy a mostrar las funciones principales del sistema. Este tour solo toma 2 minutos.',
                position: 'center'
            },
            {
                target: '#menu-lateral',
                title: 'Menú de Navegación',
                content: 'Aquí encontrarás todas las secciones principales: Ventas, Inventario, Reportes, etc.',
                position: 'right'
            },
            {
                target: 'body',
                title: 'Atajo Rápido ⌨️',
                content: 'Presiona <kbd>Ctrl+K</kbd> en cualquier momento para acceder rápidamente a cualquier función.',
                position: 'center'
            },
            {
                target: '#aiAssistantBtn',
                title: 'Asistente Virtual 🤖',
                content: 'Haz clic aquí si necesitas ayuda. El asistente puede responder tus preguntas.',
                position: 'left'
            },
            {
                target: 'body',
                title: '¡Listo para empezar! 🚀',
                content: 'Ya conoces lo básico. Explora el sistema y descubre todas las funcionalidades.',
                position: 'center'
            }
        ];

        this.init();
    }

    init() {
        // Verificar si ya se mostró el tour
        if (localStorage.getItem('onboarding_completed')) {
            return;
        }

        // Esperar a que el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.start());
        } else {
            // Esperar 2 segundos antes de mostrar
            setTimeout(() => this.start(), 2000);
        }
    }

    start() {
        this.createOverlay();
        this.showStep(0);
    }

    createOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'onboardingOverlay';
        overlay.className = 'fixed inset-0 bg-black/50 backdrop-blur-sm z-[9998]';
        document.body.appendChild(overlay);
    }

    showStep(stepIndex) {
        this.currentStep = stepIndex;
        const step = this.steps[stepIndex];

        // Remover tooltip anterior
        const oldTooltip = document.getElementById('onboardingTooltip');
        if (oldTooltip) oldTooltip.remove();

        // Crear nuevo tooltip
        const tooltip = document.createElement('div');
        tooltip.id = 'onboardingTooltip';
        tooltip.className = 'fixed bg-white dark:bg-slate-900 rounded-2xl shadow-2xl p-6 max-w-md z-[9999] border border-slate-200 dark:border-slate-700';

        tooltip.innerHTML = `
            <div class="mb-4">
                <h3 class="text-xl font-bold text-slate-900 dark:text-white mb-2">${step.title}</h3>
                <p class="text-slate-600 dark:text-slate-400">${step.content}</p>
            </div>
            <div class="flex items-center justify-between">
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    Paso ${stepIndex + 1} de ${this.steps.length}
                </div>
                <div class="flex gap-2">
                    ${stepIndex > 0 ? '<button onclick="onboardingTour.previousStep()" class="px-4 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition">Anterior</button>' : ''}
                    ${stepIndex < this.steps.length - 1 ?
                '<button onclick="onboardingTour.nextStep()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Siguiente</button>' :
                '<button onclick="onboardingTour.finish()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">¡Empezar!</button>'
            }
                </div>
            </div>
            <button onclick="onboardingTour.skip()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                <span class="material-symbols-outlined">close</span>
            </button>
        `;

        document.body.appendChild(tooltip);

        // Posicionar tooltip
        this.positionTooltip(tooltip, step);
    }

    positionTooltip(tooltip, step) {
        if (step.position === 'center') {
            tooltip.style.top = '50%';
            tooltip.style.left = '50%';
            tooltip.style.transform = 'translate(-50%, -50%)';
        } else {
            const target = document.querySelector(step.target);
            if (target) {
                const rect = target.getBoundingClientRect();

                switch (step.position) {
                    case 'right':
                        tooltip.style.top = rect.top + 'px';
                        tooltip.style.left = (rect.right + 20) + 'px';
                        break;
                    case 'left':
                        tooltip.style.top = rect.top + 'px';
                        tooltip.style.right = (window.innerWidth - rect.left + 20) + 'px';
                        break;
                    case 'bottom':
                        tooltip.style.top = (rect.bottom + 20) + 'px';
                        tooltip.style.left = rect.left + 'px';
                        break;
                }
            }
        }
    }

    nextStep() {
        if (this.currentStep < this.steps.length - 1) {
            this.showStep(this.currentStep + 1);
        }
    }

    previousStep() {
        if (this.currentStep > 0) {
            this.showStep(this.currentStep - 1);
        }
    }

    skip() {
        this.finish();
    }

    finish() {
        // Remover overlay y tooltip
        const overlay = document.getElementById('onboardingOverlay');
        const tooltip = document.getElementById('onboardingTooltip');

        if (overlay) overlay.remove();
        if (tooltip) tooltip.remove();

        // Marcar como completado
        localStorage.setItem('onboarding_completed', 'true');
    }
}

// Inicializar
window.onboardingTour = new OnboardingTour();
