<?php
// modal_sistema.php
// Sistema de modales reutilizable para toda la aplicación
?>

<!-- Modal Sistema - Overlay y Container -->
<div id="modalOverlay" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 transition-opacity duration-300" onclick="cerrarModal()"></div>

<div id="modalContainer" class="fixed inset-0 flex items-center justify-center z-50 hidden pointer-events-none">
    <div id="modalBox" class="bg-white dark:bg-[#111722] rounded-xl shadow-2xl max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0 pointer-events-auto">
        <!-- Modal Header -->
        <div id="modalHeader" class="flex items-center gap-3 p-6 border-b border-gray-200 dark:border-[#232f48]">
            <div id="modalIconContainer" class="flex-shrink-0">
                <span id="modalIcon" class="material-symbols-outlined text-4xl"></span>
            </div>
            <h3 id="modalTitle" class="text-xl font-bold text-gray-900 dark:text-white flex-1"></h3>
            <button onclick="cerrarModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6">
            <p id="modalMessage" class="text-gray-700 dark:text-gray-300 text-base leading-relaxed"></p>
        </div>
        
        <!-- Modal Footer -->
        <div id="modalFooter" class="flex gap-3 p-6 border-t border-gray-200 dark:border-[#232f48] justify-end">
            <button id="modalBtnSecondary" onclick="cerrarModal()" class="hidden px-5 py-2.5 rounded-lg font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-[#232f48] hover:bg-gray-200 dark:hover:bg-[#2a3f5f] transition-colors">
                Cancelar
            </button>
            <button id="modalBtnPrimary" onclick="cerrarModal()" class="px-5 py-2.5 rounded-lg font-medium text-white transition-colors">
                Aceptar
            </button>
        </div>
    </div>
</div>

<style>
    /* Animaciones para el modal */
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: scale(0.95) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    @keyframes modalFadeOut {
        from {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
        to {
            opacity: 0;
            transform: scale(0.95) translateY(-20px);
        }
    }
    
    .modal-show {
        animation: modalFadeIn 0.3s ease-out forwards;
    }
    
    .modal-hide {
        animation: modalFadeOut 0.2s ease-in forwards;
    }
</style>

<script>
// Variables globales para el sistema de modales
let modalAutoCloseTimeout = null;
let modalCallbackConfirm = null;
let modalCallbackCancel = null;

// Configuraciones de estilos por tipo de modal
const modalStyles = {
    success: {
        headerBg: 'bg-green-50 dark:bg-green-900/20',
        iconColor: 'text-green-500',
        icon: 'check_circle',
        btnBg: 'bg-green-500 hover:bg-green-600',
        title: 'Éxito'
    },
    error: {
        headerBg: 'bg-red-50 dark:bg-red-900/20',
        iconColor: 'text-red-500',
        icon: 'error',
        btnBg: 'bg-red-500 hover:bg-red-600',
        title: 'Error'
    },
    warning: {
        headerBg: 'bg-yellow-50 dark:bg-yellow-900/20',
        iconColor: 'text-yellow-500',
        icon: 'warning',
        btnBg: 'bg-yellow-500 hover:bg-yellow-600',
        title: 'Advertencia'
    },
    info: {
        headerBg: 'bg-blue-50 dark:bg-blue-900/20',
        iconColor: 'text-blue-500',
        icon: 'info',
        btnBg: 'bg-blue-500 hover:bg-blue-600',
        title: 'Información'
    },
    confirm: {
        headerBg: 'bg-purple-50 dark:bg-purple-900/20',
        iconColor: 'text-purple-500',
        icon: 'help',
        btnBg: 'bg-purple-500 hover:bg-purple-600',
        title: 'Confirmación'
    }
};

function mostrarModal(tipo, mensaje, opciones = {}) {
    const overlay = document.getElementById('modalOverlay');
    const container = document.getElementById('modalContainer');
    const box = document.getElementById('modalBox');
    const header = document.getElementById('modalHeader');
    const icon = document.getElementById('modalIcon');
    const iconContainer = document.getElementById('modalIconContainer');
    const title = document.getElementById('modalTitle');
    const messageEl = document.getElementById('modalMessage');
    const btnPrimary = document.getElementById('modalBtnPrimary');
    const btnSecondary = document.getElementById('modalBtnSecondary');
    
    // Limpiar timeout anterior si existe
    if (modalAutoCloseTimeout) {
        clearTimeout(modalAutoCloseTimeout);
        modalAutoCloseTimeout = null;
    }
    
    // Obtener estilos según el tipo
    const style = modalStyles[tipo] || modalStyles.info;
    
    // Configurar header
    header.className = `flex items-center gap-3 p-6 border-b border-gray-200 dark:border-[#232f48] ${style.headerBg}`;
    icon.className = `material-symbols-outlined text-4xl ${style.iconColor}`;
    icon.textContent = style.icon;
    title.textContent = opciones.titulo || style.title;
    
    // Configurar mensaje
    messageEl.textContent = mensaje;
    
    // Configurar botones
    btnPrimary.className = `px-5 py-2.5 rounded-lg font-medium text-white transition-colors ${style.btnBg}`;
    btnPrimary.textContent = opciones.btnPrimary || 'Aceptar';
    
    if (opciones.btnSecondary) {
        btnSecondary.textContent = opciones.btnSecondary;
        btnSecondary.classList.remove('hidden');
    } else {
        btnSecondary.classList.add('hidden');
    }
    
    // Configurar callbacks
    modalCallbackConfirm = opciones.onConfirm || null;
    modalCallbackCancel = opciones.onCancel || null;
    
    // Mostrar modal
    overlay.classList.remove('hidden');
    container.classList.remove('hidden');
    
    setTimeout(() => {
        overlay.classList.add('opacity-100');
        box.classList.remove('scale-95', 'opacity-0');
        box.classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Auto-cerrar si está configurado
    if (opciones.autoClose && opciones.autoClose > 0) {
        modalAutoCloseTimeout = setTimeout(() => {
            cerrarModal();
        }, opciones.autoClose);
    }
}

function cerrarModal(confirmar = false) {
    const overlay = document.getElementById('modalOverlay');
    const container = document.getElementById('modalContainer');
    const box = document.getElementById('modalBox');
    
    // Limpiar timeout si existe
    if (modalAutoCloseTimeout) {
        clearTimeout(modalAutoCloseTimeout);
        modalAutoCloseTimeout = null;
    }
    
    // Ejecutar callback si existe
    if (confirmar && modalCallbackConfirm) {
        modalCallbackConfirm();
    } else if (!confirmar && modalCallbackCancel) {
        modalCallbackCancel();
    }
    
    // Animar cierre
    overlay.classList.remove('opacity-100');
    box.classList.remove('scale-100', 'opacity-100');
    box.classList.add('scale-95', 'opacity-0');
    
    setTimeout(() => {
        overlay.classList.add('hidden');
        container.classList.add('hidden');
        modalCallbackConfirm = null;
        modalCallbackCancel = null;
    }, 300);
}

// Funciones helper para diferentes tipos de modales
function mostrarExito(mensaje, opciones = {}) {
    mostrarModal('success', mensaje, {
        autoClose: opciones.autoClose !== false ? 3000 : 0,
        ...opciones
    });
}

function mostrarError(mensaje, opciones = {}) {
    mostrarModal('error', mensaje, opciones);
}

function mostrarAdvertencia(mensaje, opciones = {}) {
    mostrarModal('warning', mensaje, opciones);
}

function mostrarInfo(mensaje, opciones = {}) {
    mostrarModal('info', mensaje, opciones);
}

function mostrarConfirmacion(mensaje, onConfirm, onCancel = null) {
    mostrarModal('confirm', mensaje, {
        btnPrimary: 'Sí, continuar',
        btnSecondary: 'Cancelar',
        onConfirm: onConfirm,
        onCancel: onCancel
    });
    
    // Configurar botón secundario para cancelar
    const btnSecondary = document.getElementById('modalBtnSecondary');
    btnSecondary.onclick = () => {
        if (onCancel) {
            onCancel();
        }
        cerrarModal(false);
    };
    
    // Configurar botón primario para confirmar
    const btnPrimary = document.getElementById('modalBtnPrimary');
    btnPrimary.onclick = () => {
        if (onConfirm) {
            onConfirm();
        }
        cerrarModal(true);
    };
}

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const container = document.getElementById('modalContainer');
        if (container && !container.classList.contains('hidden')) {
            cerrarModal(false);
        }
    }
});
</script>
