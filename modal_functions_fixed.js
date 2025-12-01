<script>
setTimeout(() => { 
    if(typeof confetti !== "undefined") {
        confetti({ particleCount: 150, spread: 90, origin: { y: 0.6 } });
    }
}, 500);

    // ========================================
    // FUNCIONES DE NOTIFICACIÓN PERSONALIZADAS
    // ========================================

    function mostrarConfirmacion(mensaje, onConfirm) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm';
    modal.innerHTML = `
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-md w-full mx-4 transform transition-all">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-blue-500 text-3xl">help</span>
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">Confirmar Acción</h3>
            </div>
            <button class="btn-close-modal text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <p class="text-gray-700 dark:text-gray-300 mb-6 whitespace-pre-line">${mensaje}</p>
        <div class="flex gap-3 justify-end">
            <button class="btn-cancelar px-4 py-2 rounded-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                Cancelar
            </button>
            <button class="btn-confirmar px-4 py-2 rounded-lg bg-primary text-white font-medium hover:bg-primary/90 transition">
                Confirmar
            </button>
        </div>
    </div>
    `;
    document.body.appendChild(modal);
    
    modal.querySelector(".btn-close-modal").addEventListener("click", () => {
        modal.remove();
    });
    
    modal.querySelector(".btn-cancelar").addEventListener("click", () => {
        modal.remove();
    });
    
    modal.querySelector(".btn-confirmar").addEventListener("click", () => {
        modal.remove();
    if (onConfirm) onConfirm();
    });
    
    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
        modal.remove();
        }
    });
}

    function mostrarError(mensaje) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm';
    modal.innerHTML = `
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center gap-3 mb-4">
            <span class="material-symbols-outlined text-red-500 text-3xl">error</span>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Error</h3>
        </div>
        <p class="text-gray-700 dark:text-gray-300 mb-6">${mensaje}</p>
        <button onclick="this.closest('.fixed').remove()"
            class="w-full px-4 py-2 rounded-lg bg-red-500 text-white font-medium hover:bg-red-600 transition">
            Cerrar
        </button>
    </div>
    `;
    document.body.appendChild(modal);
}

    function mostrarAdvertencia(mensaje) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm';
    modal.innerHTML = `
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-md w-full mx-4">
        <div class="flex items-center gap-3 mb-4">
            <span class="material-symbols-outlined text-yellow-500 text-3xl">warning</span>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Advertencia</h3>
        </div>
        <p class="text-gray-700 dark:text-gray-300 mb-6">${mensaje}</p>
        <button onclick="this.closest('.fixed').remove()"
            class="w-full px-4 py-2 rounded-lg bg-yellow-500 text-white font-medium hover:bg-yellow-600 transition">
            Entendido
        </button>
    </div>
    `;
    document.body.appendChild(modal);
}
</script>
