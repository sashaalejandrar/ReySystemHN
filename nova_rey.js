/**
 * Nova Rey - Asistente Virtual Inteligente
 * Frontend Widget
 */

class NovaReyAssistant {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.isTyping = false;
        this.init();
    }

    init() {
        this.createWidgetHTML();
        this.attachEventListeners();
        this.greet();
    }

    createWidgetHTML() {
        const html = `
            <!-- Nova Rey Button -->
            <button id="novaReyBtn" class="fixed bottom-6 right-6 w-16 h-16 bg-gradient-to-br from-purple-600 via-pink-600 to-blue-600 text-white rounded-full shadow-2xl hover:scale-110 transition-all duration-300 z-[9998] flex items-center justify-center group animate-pulse-slow">
                <span class="material-symbols-outlined text-3xl">psychology</span>
                <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full border-2 border-white animate-ping"></div>
                <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-500 rounded-full border-2 border-white"></div>
            </button>

            <!-- Nova Rey Chat -->
            <div id="novaReyChat" class="hidden fixed bottom-24 right-6 w-[420px] h-[600px] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 flex flex-col z-[9999] overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-purple-600 via-pink-600 to-blue-600 text-white p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <div class="w-10 h-10 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                                <span class="material-symbols-outlined text-2xl">psychology</span>
                            </div>
                            <div class="absolute bottom-0 right-0 w-3 h-3 bg-green-400 rounded-full border-2 border-white"></div>
                        </div>
                        <div>
                            <div class="font-bold text-lg">Nova Rey</div>
                            <div class="text-xs opacity-90">Asistente Virtual Inteligente</div>
                        </div>
                    </div>
                    <button onclick="novaRey.close()" class="hover:bg-white/20 rounded-lg p-2 transition">
                        <span class="material-symbols-outlined">close</span>
                    </button>
                </div>

                <!-- Messages -->
                <div id="novaMessages" class="flex-1 overflow-y-auto p-4 space-y-4 bg-gradient-to-b from-slate-50 to-white dark:from-slate-900 dark:to-slate-800">
                    <!-- Messages will be inserted here -->
                </div>

                <!-- Typing Indicator -->
                <div id="novaTyping" class="hidden px-4 py-2">
                    <div class="flex gap-2 items-center">
                        <div class="w-8 h-8 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-sm">psychology</span>
                        </div>
                        <div class="flex gap-1">
                            <div class="w-2 h-2 bg-purple-600 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                            <div class="w-2 h-2 bg-pink-600 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                            <div class="w-2 h-2 bg-blue-600 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div id="novaQuickActions" class="px-4 py-2 border-t border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50">
                    <div class="flex flex-wrap gap-2">
                        <button onclick="novaRey.quickAction('errores')" class="px-3 py-1.5 text-xs bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-full hover:bg-purple-100 dark:hover:bg-purple-900/30 transition border border-slate-200 dark:border-slate-600">
                            🔍 Revisar Sistema
                        </button>
                        <button onclick="novaRey.quickAction('inventario')" class="px-3 py-1.5 text-xs bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-full hover:bg-purple-100 dark:hover:bg-purple-900/30 transition border border-slate-200 dark:border-slate-600">
                            📦 Inventario
                        </button>
                        <button onclick="novaRey.quickAction('ventas')" class="px-3 py-1.5 text-xs bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-full hover:bg-purple-100 dark:hover:bg-purple-900/30 transition border border-slate-200 dark:border-slate-600">
                            💰 Ventas
                        </button>
                        <button onclick="novaRey.quickAction('caja')" class="px-3 py-1.5 text-xs bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-full hover:bg-purple-100 dark:hover:bg-purple-900/30 transition border border-slate-200 dark:border-slate-600">
                            💵 Caja
                        </button>
                        <button onclick="novaRey.quickAction('recordatorios')" class="px-3 py-1.5 text-xs bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-full hover:bg-purple-100 dark:hover:bg-purple-900/30 transition border border-slate-200 dark:border-slate-600">
                            🔔 Pendientes
                        </button>
                        <button onclick="novaRey.quickAction('compras')" class="px-3 py-1.5 text-xs bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-full hover:bg-purple-100 dark:hover:bg-purple-900/30 transition border border-slate-200 dark:border-slate-600">
                            🛒 Comprar
                        </button>
                    </div>
                </div>

                <!-- Input -->
                <div class="p-4 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            id="novaInput" 
                            placeholder="Pregúntame lo que necesites..."
                            class="flex-1 px-4 py-3 rounded-full border border-slate-300 dark:border-slate-600 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
                        />
                        <button onclick="novaRey.sendMessage()" class="w-12 h-12 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-full hover:shadow-lg transition flex items-center justify-center">
                            <span class="material-symbols-outlined">send</span>
                        </button>
                    </div>
                </div>
            </div>

            <style>
                @keyframes pulse-slow {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.8; }
                }
                .animate-pulse-slow {
                    animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
                }
            </style>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
    }

    attachEventListeners() {
        document.getElementById('novaReyBtn').addEventListener('click', () => this.toggle());

        const input = document.getElementById('novaInput');
        input.addEventListener('keypress', (e) => {
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
        document.getElementById('novaReyChat').classList.remove('hidden');
        document.getElementById('novaInput').focus();
    }

    close() {
        this.isOpen = false;
        document.getElementById('novaReyChat').classList.add('hidden');
    }

    greet() {
        setTimeout(() => {
            this.addMessage('¡Hola! Soy Nova Rey, tu asistente virtual. 👋\n\nEstoy aquí para ayudarte con el sistema. Puedo revisar errores, gestionar inventario, ventas, caja y mucho más.\n\n¿En qué puedo ayudarte hoy?', 'nova');
        }, 1000);
    }

    async sendMessage() {
        const input = document.getElementById('novaInput');
        const message = input.value.trim();

        if (!message) return;

        this.addMessage(message, 'user');
        input.value = '';

        // Mostrar typing indicator
        this.showTyping();

        try {
            const formData = new FormData();
            formData.append('action', 'chat');
            formData.append('message', message);

            const response = await fetch('nova_rey_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            this.hideTyping();

            if (data.error) {
                this.addMessage('❌ Error: ' + data.error, 'nova');
            } else {
                this.addMessage(data.message, 'nova', data.actions);
            }
        } catch (error) {
            this.hideTyping();
            console.error('Error:', error);
            this.addMessage('❌ Hubo un error al procesar tu solicitud. Por favor intenta de nuevo.', 'nova');
        }
    }

    quickAction(action) {
        const actions = {
            'errores': '¿Hay algún error en el sistema?',
            'inventario': 'Muéstrame el estado del inventario',
            'ventas': '¿Cuánto he vendido hoy?',
            'caja': '¿Cuál es el estado de la caja?',
            'recordatorios': '¿Qué tengo pendiente?',
            'compras': '¿Qué productos debo comprar?'
        };

        const message = actions[action];
        if (message) {
            document.getElementById('novaInput').value = message;
            this.sendMessage();
        }
    }

    addMessage(text, sender, actions = null) {
        const messagesContainer = document.getElementById('novaMessages');

        // Convertir markdown básico a HTML
        text = this.formatMessage(text);

        const messageHTML = sender === 'user' ? `
            <div class="flex gap-2 justify-end animate-fade-in">
                <div class="bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-2xl rounded-tr-none p-3 max-w-[80%] shadow-md">
                    <p class="text-sm whitespace-pre-wrap">${text}</p>
                </div>
                <div class="w-8 h-8 bg-gradient-to-br from-blue-100 to-purple-100 dark:from-blue-900/30 dark:to-purple-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-sm">person</span>
                </div>
            </div>
        ` : `
            <div class="flex gap-2 animate-fade-in">
                <div class="w-8 h-8 bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                    <span class="material-symbols-outlined text-purple-600 dark:text-purple-400 text-sm">psychology</span>
                </div>
                <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl rounded-tl-none p-3 max-w-[80%] shadow-md">
                    <div class="text-sm text-slate-900 dark:text-white whitespace-pre-wrap nova-message">${text}</div>
                    ${actions ? this.renderActions(actions) : ''}
                </div>
            </div>
        `;

        messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    formatMessage(text) {
        // Bold
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Code
        text = text.replace(/`(.*?)`/g, '<code class="px-1 py-0.5 bg-slate-100 dark:bg-slate-700 rounded text-xs">$1</code>');
        return text;
    }

    renderActions(actions) {
        if (!actions || actions.length === 0) return '';

        let html = '<div class="mt-3 flex flex-wrap gap-2">';
        actions.forEach(action => {
            html += `
                <a href="${action.url}" class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-lg text-xs hover:bg-purple-200 dark:hover:bg-purple-900/50 transition">
                    <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    ${action.label}
                </a>
            `;
        });
        html += '</div>';
        return html;
    }

    showTyping() {
        this.isTyping = true;
        document.getElementById('novaTyping').classList.remove('hidden');
        const messagesContainer = document.getElementById('novaMessages');
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    hideTyping() {
        this.isTyping = false;
        document.getElementById('novaTyping').classList.add('hidden');
    }
}

// Inicializar Nova Rey
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.novaRey = new NovaReyAssistant();
    });
} else {
    window.novaRey = new NovaReyAssistant();
}
