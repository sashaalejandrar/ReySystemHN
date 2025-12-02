// ========================================
// REPORTES CAJA - JAVASCRIPT FUNCTIONS
// ========================================

let currentTable = '';
let currentId = 0;
let usuarios = [];

// Cargar usuarios al inicio
async function cargarUsuarios() {
    try {
        console.log('Cargando usuarios...');
        const response = await fetch('api/obtener_usuarios.php');
        const data = await response.json();
        console.log('Respuesta de usuarios:', data);

        if (data.success) {
            usuarios = data.usuarios;
            console.log('Usuarios cargados:', usuarios.length);
        } else {
            console.error('Error del servidor:', data.message);
            mostrarNotificacion(data.message || 'Error al cargar usuarios', 'error');
        }
    } catch (error) {
        console.error('Error al cargar usuarios:', error);
        mostrarNotificacion('Error de conexión al cargar usuarios', 'error');
    }
}

// Editar Registro
function editarRegistro(table, id, data) {
    currentTable = table;
    currentId = id;

    // Llenar formulario según la tabla
    document.getElementById('editTable').value = table;
    document.getElementById('editId').value = id;

    if (table === 'caja') {
        document.getElementById('editFormFields').innerHTML = `
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Monto Inicial</label>
                <input type="number" step="0.01" id="editMontoInicial" value="${data.monto_inicial}" 
                       class="form-input-christmas w-full" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nota</label>
                <textarea id="editNota" rows="3" class="form-input-christmas w-full">${data.Nota || ''}</textarea>
            </div>
        `;
    } else {
        // Para arqueo_caja y cierre_caja
        const notaField = table === 'arqueo_caja' ? 'Nota_justi' : 'Nota_Justifi';
        document.getElementById('editFormFields').innerHTML = `
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Efectivo</label>
                    <input type="number" step="0.01" id="editEfectivo" value="${data.Efectivo || 0}" 
                           class="form-input-christmas w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Transferencia</label>
                    <input type="number" step="0.01" id="editTransferencia" value="${data.Transferencia || 0}" 
                           class="form-input-christmas w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tarjeta</label>
                    <input type="number" step="0.01" id="editTarjeta" value="${data.Tarjeta || 0}" 
                           class="form-input-christmas w-full" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nota</label>
                <textarea id="editNota" rows="3" class="form-input-christmas w-full">${data[notaField] || ''}</textarea>
            </div>
        `;
    }

    // Mostrar modal
    document.getElementById('editModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('editModal').classList.add('show'), 10);
}

// Guardar Edición
async function guardarEdicion() {
    const table = document.getElementById('editTable').value;
    const id = parseInt(document.getElementById('editId').value);

    let data = {};

    if (table === 'caja') {
        data = {
            monto_inicial: parseFloat(document.getElementById('editMontoInicial').value),
            nota: document.getElementById('editNota').value
        };
    } else {
        data = {
            efectivo: parseFloat(document.getElementById('editEfectivo').value),
            transferencia: parseFloat(document.getElementById('editTransferencia').value),
            tarjeta: parseFloat(document.getElementById('editTarjeta').value),
            nota: document.getElementById('editNota').value
        };
    }

    try {
        const response = await fetch('api/editar_registro_caja.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table, id, data })
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Registro actualizado correctamente', 'success');
            cerrarModal('editModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacion(result.message || 'Error al actualizar', 'error');
        }
    } catch (error) {
        mostrarNotificacion('Error de conexión', 'error');
    }
}

// Confirmar Eliminación
function confirmarEliminacion(table, id) {
    currentTable = table;
    currentId = id;

    document.getElementById('confirmModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('confirmModal').classList.add('show'), 10);
}

// Eliminar Registro
async function eliminarRegistro() {
    try {
        const response = await fetch('api/eliminar_registro_caja.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table: currentTable, id: currentId })
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Registro eliminado correctamente', 'success');
            cerrarModal('confirmModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacion(result.message || 'Error al eliminar', 'error');
        }
    } catch (error) {
        mostrarNotificacion('Error de conexión', 'error');
    }
}

// Transferir Registro
function transferirRegistro(table, id) {
    currentTable = table;
    currentId = id;

    // Llenar select de usuarios
    const select = document.getElementById('transferUsuario');
    select.innerHTML = '<option value="">Selecciona un usuario</option>';
    usuarios.forEach(u => {
        select.innerHTML += `<option value="${u.usuario}">${u.nombre_completo} (${u.rol})</option>`;
    });

    document.getElementById('transferModal').classList.remove('hidden');
    setTimeout(() => document.getElementById('transferModal').classList.add('show'), 10);
}

// Confirmar Transferencia
async function confirmarTransferencia() {
    const nuevo_usuario = document.getElementById('transferUsuario').value;

    if (!nuevo_usuario) {
        mostrarNotificacion('Selecciona un usuario', 'error');
        return;
    }

    try {
        const response = await fetch('api/transferir_caja.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table: currentTable, id: currentId, nuevo_usuario })
        });

        const result = await response.json();

        if (result.success) {
            mostrarNotificacion('Responsabilidad transferida correctamente', 'success');
            cerrarModal('transferModal');
            setTimeout(() => location.reload(), 1000);
        } else {
            mostrarNotificacion(result.message || 'Error al transferir', 'error');
        }
    } catch (error) {
        mostrarNotificacion('Error de conexión', 'error');
    }
}

// Cerrar Modal
function cerrarModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
    setTimeout(() => modal.classList.add('hidden'), 300);
}

// Mostrar Notificación
function mostrarNotificacion(mensaje, tipo = 'info') {
    const notif = document.createElement('div');
    notif.className = `fixed top-4 right-4 z-[10000] px-6 py-4 rounded-xl shadow-2xl transform transition-all duration-300 ${tipo === 'success' ? 'bg-green-500' : tipo === 'error' ? 'bg-red-500' : 'bg-blue-500'
        } text-white font-semibold`;
    notif.textContent = mensaje;

    document.body.appendChild(notif);

    setTimeout(() => notif.classList.add('translate-x-0'), 10);
    setTimeout(() => {
        notif.classList.add('translate-x-full');
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    cargarUsuarios();

    // Cerrar modales al hacer click fuera
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                cerrarModal(modal.id);
            }
        });
    });
});
