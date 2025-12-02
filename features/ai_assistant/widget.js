/**
 * AI Assistant Widget - Asistente Virtual Flotante
 */

class AIAssistant {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.init();
    }

    init() {
        this.createWidgetHTML();
        this.attachEventListeners();
    }

    createWidgetHTML() {
        const html = `
            <!-- AI Assistant Button -->
            <button id="aiAssistantBtn" class="fixed bottom-6 right-6 w-14 h-14 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-full shadow-2xl hover:scale-110 transition-transform z-50 flex items-center justify-center">
                <span class="material-symbols-outlined text-2xl">smart_toy</span>
            </button>

            <!-- AI Assistant Chat -->
            <div id="aiAssistantChat" class="hidden fixed bottom-24 right-6 w-96 h-[500px] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col z-50">
                <!-- Header -->
                <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white p-4 rounded-t-2xl flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined">smart_toy</span>
                        <div>
                            <div class="font-semibold">Asistente IA</div>
                            <div class="text-xs opacity-90">Siempre listo para ayudar</div>
                        </div>
                    </div>
                    <button onclick="aiAssistant.close()" class="hover:bg-white/20 rounded-lg p-1">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Messages -->
                <div id="aiMessages" class="flex-1 overflow-y-auto p-4 space-y-3">
                    <div class="flex gap-2">
                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-purple-600 text-sm">smart_toy</span>
                        </div>
                        <div class="bg-slate-100 dark:bg-slate-800 rounded-2xl rounded-tl-none p-3 max-w-[80%]">
                            <p class="text-sm text-slate-900 dark:text-white">¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte hoy?</p>
                        </div>
                    </div>
                </div>

                <!-- Input -->
                <div class="p-4 border-t border-slate-200 dark:border-slate-700">
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            id="aiInput" 
                            placeholder="Escribe tu pregunta..."
                            class="flex-1 px-4 py-2 rounded-full border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white text-sm"
                        />
                        <button onclick="aiAssistant.sendMessage()" class="w-10 h-10 bg-purple-600 text-white rounded-full hover:bg-purple-700 transition flex items-center justify-center">
                            <span class="material-symbols-outlined">send</span>
                        </button>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button onclick="aiAssistant.quickAction('ventas')" class="px-3 py-1 text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700">
                            Ver ventas
                        </button>
                        <button onclick="aiAssistant.quickAction('stock')" class="px-3 py-1 text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700">
                            Stock bajo
                        </button>
                        <button onclick="aiAssistant.quickAction('ayuda')" class="px-3 py-1 text-xs bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700">
                            Ayuda
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
    }

    attachEventListeners() {
        document.getElementById('aiAssistantBtn').addEventListener('click', () => this.toggle());

        document.getElementById('aiInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.isOpen = true;
        document.getElementById('aiAssistantChat').classList.remove('hidden');
        document.getElementById('aiInput').focus();
    }

    close() {
        this.isOpen = false;
        document.getElementById('aiAssistantChat').classList.add('hidden');
    }

    sendMessage() {
        const input = document.getElementById('aiInput');
        const message = input.value.trim();

        if (!message) return;

        this.addMessage(message, 'user');
        input.value = '';

        // Simular respuesta de IA
        setTimeout(() => {
            const response = this.generateResponse(message);
            this.addMessage(response, 'ai');
        }, 1000);
    }

    addMessage(text, sender) {
        const messagesContainer = document.getElementById('aiMessages');

        const messageHTML = sender === 'user' ? `
            <div class="flex gap-2 justify-end">
                <div class="bg-purple-600 text-white rounded-2xl rounded-tr-none p-3 max-w-[80%]">
                    <p class="text-sm">${text}</p>
                </div>
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-blue-600 text-sm">person</span>
                </div>
            </div>
        ` : `
            <div class="flex gap-2">
                <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-purple-600 text-sm">smart_toy</span>
                </div>
                <div class="bg-slate-100 dark:bg-slate-800 rounded-2xl rounded-tl-none p-3 max-w-[80%]">
                    <p class="text-sm text-slate-900 dark:text-white">${text}</p>
                </div>
            </div>
        `;

        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    generateResponse(message) {
        const lowerMessage = message.toLowerCase();

        if (lowerMessage.includes('venta') || lowerMessage.includes('vender')) {
            return '📊 Puedes ver tus ventas en el Dashboard Analítico o crear una nueva venta desde el menú principal.';
        } else if (lowerMessage.includes('stock') || lowerMessage.includes('inventario')) {
            return '📦 Revisa el inventario completo en la sección de Inventario. También puedes ver productos con stock bajo en el Dashboard.';
        } else if (lowerMessage.includes('producto')) {
            return '🏷️ Para crear un producto nuevo, ve a Crear Producto en el menú. También puedes usar Ctrl+K y buscar "crear producto".';
        } else if (lowerMessage.includes('ayuda') || lowerMessage.includes('help')) {
            return '💡 Presiona Ctrl+K para acceder rápidamente a cualquier función. También puedes explorar el menú lateral para todas las opciones disponibles.';
        } else if (lowerMessage.includes('reporte')) {
            return '📈 Los reportes están disponibles en la sección de Reportes de Caja y Reporte de Ventas.';
        } else {
            return '🤔 Interesante pregunta. Actualmente estoy en modo demo. En producción, estaría conectado a una IA real para responder mejor. ¿Hay algo específico en lo que pueda ayudarte?';
        }
    }

    quickAction(action) {
        switch (action) {
            case 'ventas':
                this.addMessage('Ver ventas de hoy', 'user');
                this.addMessage('📊 Las ventas de hoy se pueden ver en el Dashboard Analítico. ¿Quieres que te lleve allí?', 'ai');
                break;
            case 'stock':
                this.addMessage('Productos con stock bajo', 'user');
                this.addMessage('⚠️ Revisa el Dashboard Analítico para ver los productos con stock bajo. También puedes ir al Inventario para más detalles.', 'ai');
                break;
            case 'ayuda':
                this.addMessage('Necesito ayuda', 'user');
                this.addMessage('💡 Estoy aquí para ayudarte. Puedes preguntarme sobre ventas, inventario, productos, reportes o cualquier función del sistema.', 'ai');
                break;
        }
    }
}

// Inicializar
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.aiAssistant = new AIAssistant();
    });
} else {
    window.aiAssistant = new AIAssistant();
}
