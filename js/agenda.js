// ==================== TABS ====================
function switchTab(tab) {
    // Hide all content
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));

    // Remove active class from all tabs
    document.querySelectorAll('[id^="tab-"]').forEach(el => {
        el.classList.remove('tab-active', 'text-primary');
        el.classList.add('text-gray-500', 'dark:text-[#92a4c9]');
    });

    // Show selected content
    document.getElementById(`content-${tab}`).classList.remove('hidden');

    // Add active class to selected tab
    const activeTab = document.getElementById(`tab-${tab}`);
    activeTab.classList.add('tab-active', 'text-primary');
    activeTab.classList.remove('text-gray-500', 'dark:text-[#92a4c9]');

    // Load data for the tab
    if (tab === 'tareas') {
        loadTasks();
    } else if (tab === 'notas') {
        loadNotes();
    } else if (tab === 'correos') {
        loadEmailHistory();
    }
}

// ==================== TAREAS ====================
async function loadTasks() {
    const estado = document.getElementById('filter-estado').value;
    const prioridad = document.getElementById('filter-prioridad').value;

    try {
        const response = await fetch(`api/agenda_api.php?action=get_tasks&estado=${estado}&prioridad=${prioridad}`);
        const data = await response.json();

        if (data.success) {
            renderTasks(data.tasks);
        }
    } catch (error) {
        console.error('Error loading tasks:', error);
    }
}

function renderTasks(tasks) {
    // Clear all columns
    ['pendiente', 'en_progreso', 'completada'].forEach(estado => {
        document.getElementById(`tasks-${estado}`).innerHTML = '';
        document.getElementById(`count-${estado}`).textContent = '0';
    });

    // Group tasks by status
    const grouped = {
        pendiente: [],
        en_progreso: [],
        completada: []
    };

    tasks.forEach(task => {
        grouped[task.estado].push(task);
    });

    // Render each group
    Object.keys(grouped).forEach(estado => {
        const container = document.getElementById(`tasks-${estado}`);
        const count = document.getElementById(`count-${estado}`);

        count.textContent = grouped[estado].length;

        grouped[estado].forEach(task => {
            const taskCard = createTaskCard(task);
            container.appendChild(taskCard);
        });
    });
}

function createTaskCard(task) {
    const div = document.createElement('div');
    div.className = `priority-${task.prioridad} bg-white dark:bg-[#101622] rounded-lg p-4 border border-gray-200 dark:border-[#324467] hover:shadow-lg transition-all cursor-pointer`;

    const priorityColors = {
        baja: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        media: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        alta: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
        urgente: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
    };

    const statusIcons = {
        pendiente: 'schedule',
        en_progreso: 'play_circle',
        completada: 'check_circle'
    };

    div.innerHTML = `
        <div class="flex justify-between items-start mb-2">
            <h4 class="font-bold text-gray-900 dark:text-white">${task.titulo}</h4>
            <span class="px-2 py-1 text-xs rounded-full ${priorityColors[task.prioridad]}">${task.prioridad}</span>
        </div>
        ${task.descripcion ? `<p class="text-sm text-gray-600 dark:text-gray-400 mb-3">${task.descripcion}</p>` : ''}
        ${task.fecha_vencimiento ? `
            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 mb-3">
                <span class="material-symbols-outlined text-sm">calendar_today</span>
                ${new Date(task.fecha_vencimiento).toLocaleDateString('es-ES')}
            </div>
        ` : ''}
        ${task.etiquetas ? `
            <div class="flex flex-wrap gap-1 mb-3">
                ${task.etiquetas.split(',').map(tag => `
                    <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-[#324467] text-gray-700 dark:text-gray-300 rounded">${tag.trim()}</span>
                `).join('')}
            </div>
        ` : ''}
        <div class="flex gap-2">
            ${task.estado !== 'pendiente' ? `
                <button onclick="updateTaskStatus(${task.id}, 'pendiente')" class="flex-1 px-3 py-1 text-xs bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded hover:bg-yellow-200 dark:hover:bg-yellow-800 transition">
                    Pendiente
                </button>
            ` : ''}
            ${task.estado !== 'en_progreso' ? `
                <button onclick="updateTaskStatus(${task.id}, 'en_progreso')" class="flex-1 px-3 py-1 text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded hover:bg-blue-200 dark:hover:bg-blue-800 transition">
                    En Progreso
                </button>
            ` : ''}
            ${task.estado !== 'completada' ? `
                <button onclick="updateTaskStatus(${task.id}, 'completada')" class="flex-1 px-3 py-1 text-xs bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded hover:bg-green-200 dark:hover:bg-green-800 transition">
                    Completar
                </button>
            ` : ''}
            <button onclick="deleteTask(${task.id})" class="px-3 py-1 text-xs bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded hover:bg-red-200 dark:hover:bg-red-800 transition">
                <span class="material-symbols-outlined text-sm">delete</span>
            </button>
        </div>
    `;

    return div;
}

async function updateTaskStatus(id, estado) {
    try {
        const response = await fetch('api/agenda_api.php?action=update_task_status', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, estado })
        });

        const data = await response.json();
        if (data.success) {
            loadTasks();
        }
    } catch (error) {
        console.error('Error updating task:', error);
    }
}

async function deleteTask(id) {
    NotificationSystem.confirm(
        '¿Estás seguro de que deseas eliminar esta tarea? Esta acción no se puede deshacer.',
        async (confirmed) => {
            if (!confirmed) return;

            try {
                const response = await fetch(`api/agenda_api.php?action=delete_task&id=${id}`);
                const data = await response.json();

                if (data.success) {
                    NotificationSystem.success('La tarea ha sido eliminada correctamente.', '✅ Tarea Eliminada');
                    loadTasks();
                } else {
                    NotificationSystem.error(data.message || 'No se pudo eliminar la tarea.');
                }
            } catch (error) {
                console.error('Error deleting task:', error);
                NotificationSystem.error('Ocurrió un error al intentar eliminar la tarea.');
            }
        },
        '🗑️ Eliminar Tarea'
    );
}

function filterTasks() {
    loadTasks();
}

// ==================== NOTAS ====================
async function loadNotes() {
    try {
        const response = await fetch('api/agenda_api.php?action=get_notes');
        const data = await response.json();

        if (data.success) {
            renderNotes(data.notes);
        }
    } catch (error) {
        console.error('Error loading notes:', error);
    }
}

function renderNotes(notes) {
    const container = document.getElementById('notes-grid');
    container.innerHTML = '';

    if (notes.length === 0) {
        container.innerHTML = `
            <div class="col-span-full text-center py-12 text-gray-500 dark:text-gray-400">
                <span class="material-symbols-outlined text-6xl mb-4">note</span>
                <p>No hay notas. ¡Crea tu primera nota!</p>
            </div>
        `;
        return;
    }

    notes.forEach(note => {
        const noteCard = createNoteCard(note);
        container.appendChild(noteCard);
    });
}

function createNoteCard(note) {
    const div = document.createElement('div');
    div.className = 'bg-white dark:bg-[#192233] rounded-xl p-6 border border-gray-200 dark:border-[#324467] hover:shadow-lg transition-all';

    div.innerHTML = `
        <div class="flex justify-between items-start mb-3">
            <h4 class="font-bold text-gray-900 dark:text-white">${note.titulo}</h4>
            <button onclick="deleteNote(${note.id})" class="text-gray-400 hover:text-red-500 transition">
                <span class="material-symbols-outlined text-sm">delete</span>
            </button>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-4">${note.descripcion}</p>
        ${note.etiquetas ? `
            <div class="flex flex-wrap gap-1 mb-3">
                ${note.etiquetas.split(',').map(tag => `
                    <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-[#324467] text-gray-700 dark:text-gray-300 rounded">${tag.trim()}</span>
                `).join('')}
            </div>
        ` : ''}
        <div class="text-xs text-gray-500 dark:text-gray-400">
            ${new Date(note.fecha_creacion).toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' })}
        </div>
    `;

    return div;
}

async function deleteNote(id) {
    NotificationSystem.confirm(
        '¿Estás seguro de que deseas eliminar esta nota? Esta acción no se puede deshacer.',
        async (confirmed) => {
            if (!confirmed) return;

            try {
                const response = await fetch(`api/agenda_api.php?action=delete_note&id=${id}`);
                const data = await response.json();

                if (data.success) {
                    NotificationSystem.success('La nota ha sido eliminada correctamente.', '✅ Nota Eliminada');
                    loadNotes();
                } else {
                    NotificationSystem.error(data.message || 'No se pudo eliminar la nota.');
                }
            } catch (error) {
                console.error('Error deleting note:', error);
                NotificationSystem.error('Ocurrió un error al intentar eliminar la nota.');
            }
        },
        '🗑️ Eliminar Nota'
    );
}

// ==================== EMAIL TEMPLATES ====================
let selectedProducts = [];

function handleEmailTypeChange() {
    const tipo = document.getElementById('email-tipo').value;
    const productSelector = document.getElementById('product-selector');

    if (tipo === 'reabastecer_selectivo') {
        productSelector.classList.remove('hidden');
        loadLowStockProducts();
    } else {
        productSelector.classList.add('hidden');
    }

    // Auto-generar asunto
    const asuntoField = document.getElementById('email-asunto');
    if (tipo === 'reabastecer_stock') {
        asuntoField.value = '🔄 Solicitud de Reabastecimiento Completo de Stock';
    } else if (tipo === 'reabastecer_selectivo') {
        asuntoField.value = '📦 Solicitud de Reabastecimiento Selectivo de Productos';
    } else if (tipo === 'pedido') {
        asuntoField.value = 'Pedido de Productos';
    }
}

async function loadLowStockProducts() {
    try {
        const response = await fetch('api/agenda_api.php?action=get_low_stock_products');
        const data = await response.json();

        if (data.success) {
            renderProductList(data.products);
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

function renderProductList(products) {
    const container = document.getElementById('product-list');
    container.innerHTML = '';

    if (products.length === 0) {
        container.innerHTML = '<p class="text-sm text-gray-500 dark:text-gray-400">No hay productos con bajo stock</p>';
        return;
    }

    products.forEach(product => {
        const div = document.createElement('label');
        div.className = 'flex items-center gap-3 p-2 hover:bg-gray-100 dark:hover:bg-[#192233] rounded cursor-pointer';

        const stockClass = product.Stock === 0 ? 'text-red-500 animate-pulse' : 'text-orange-500';
        const stockIcon = product.Stock === 0 ? '🔴' : '🟡';

        div.innerHTML = `
            <input type="checkbox" 
                   class="product-checkbox w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary" 
                   data-code="${product.Codigo_Producto}"
                   data-name="${product.Nombre_Producto}"
                   data-stock="${product.Stock}"
                   ${product.Stock === 0 ? 'checked' : ''}>
            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <span class="${stockClass}">${stockIcon}</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">${product.Nombre_Producto}</span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Código: ${product.Codigo_Producto} | Stock: ${product.Stock} unidades
                </div>
            </div>
        `;

        container.appendChild(div);
    });

    // Update selected products when checkboxes change
    container.querySelectorAll('.product-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedProducts);
    });

    updateSelectedProducts();
}

function updateSelectedProducts() {
    selectedProducts = [];
    document.querySelectorAll('.product-checkbox:checked').forEach(checkbox => {
        selectedProducts.push({
            codigo: checkbox.dataset.code,
            nombre: checkbox.dataset.name,
            stock: parseInt(checkbox.dataset.stock)
        });
    });
}

async function generateTemplate() {
    const tipo = document.getElementById('email-tipo').value;
    const mensajeField = document.getElementById('email-mensaje');

    if (tipo === 'reabastecer_stock') {
        // Cargar todos los productos con bajo stock
        try {
            const response = await fetch('api/agenda_api.php?action=get_low_stock_products');
            const data = await response.json();

            if (data.success) {
                const template = generateStockTemplate(data.products, 'completo');
                mensajeField.value = template;
            }
        } catch (error) {
            console.error('Error generating template:', error);
        }
    } else if (tipo === 'reabastecer_selectivo') {
        if (selectedProducts.length === 0) {
            NotificationSystem.warning('Debes seleccionar al menos un producto para generar la plantilla.', '⚠️ Selección Requerida');
            return;
        }
        const template = generateStockTemplate(selectedProducts, 'selectivo');
        mensajeField.value = template;
    } else if (tipo === 'pedido') {
        mensajeField.value = `Estimado proveedor,

Por medio de la presente, solicitamos cotización y disponibilidad de los siguientes productos:

[LISTA DE PRODUCTOS]

Agradecemos su pronta respuesta.

Saludos cordiales,
Rey System
${generateUserSignature()}`;
    } else if (tipo === 'recordatorio') {
        mensajeField.value = `Estimado/a,

Le recordamos que:

[DETALLES DEL RECORDATORIO]

Gracias por su atención.

Saludos,
Rey System
${generateUserSignature()}`;
    } else if (tipo === 'nota') {
        mensajeField.value = `Estimado/a,

[CONTENIDO DE LA NOTA]

${generateUserSignature()}`;
    }
}

function generateStockTemplate(products, tipo) {
    const fecha = new Date().toLocaleDateString('es-HN', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });

    const hora = new Date().toLocaleTimeString('es-HN', {
        hour: '2-digit',
        minute: '2-digit'
    });

    let template = `Estimado proveedor,

Fecha: ${fecha}
Hora: ${hora}

${tipo === 'completo' ?
            'Solicitamos el reabastecimiento COMPLETO de los siguientes productos que se encuentran con stock bajo o agotado:' :
            'Solicitamos el reabastecimiento de los siguientes productos seleccionados:'}

═══════════════════════════════════════════════════════════════

`;

    // Agrupar por urgencia
    const sinStock = products.filter(p => p.Stock === 0 || p.stock === 0);
    const bajoStock = products.filter(p => (p.Stock > 0 || p.stock > 0) && (p.Stock < 10 || p.stock < 10));

    if (sinStock.length > 0) {
        template += `🔴 URGENTE - SIN STOCK (${sinStock.length} productos):\n`;
        template += `${'─'.repeat(63)}\n`;
        sinStock.forEach((p, index) => {
            const nombre = p.Nombre_Producto || p.nombre;
            const codigo = p.Codigo_Producto || p.codigo;
            const stock = p.Stock !== undefined ? p.Stock : p.stock;
            template += `${index + 1}. ${nombre}\n`;
            template += `   Código: ${codigo}\n`;
            template += `   Stock Actual: ${stock} unidades\n`;
            template += `   Cantidad Sugerida: 50 unidades\n\n`;
        });
    }

    if (bajoStock.length > 0) {
        template += `\n🟡 PRIORIDAD MEDIA - BAJO STOCK (${bajoStock.length} productos):\n`;
        template += `${'─'.repeat(63)}\n`;
        bajoStock.forEach((p, index) => {
            const nombre = p.Nombre_Producto || p.nombre;
            const codigo = p.Codigo_Producto || p.codigo;
            const stock = p.Stock !== undefined ? p.Stock : p.stock;
            const sugerido = Math.max(30, 50 - stock);
            template += `${index + 1}. ${nombre}\n`;
            template += `   Código: ${codigo}\n`;
            template += `   Stock Actual: ${stock} unidades\n`;
            template += `   Cantidad Sugerida: ${sugerido} unidades\n\n`;
        });
    }

    template += `═══════════════════════════════════════════════════════════════

RESUMEN:
• Total de productos: ${products.length}
• Sin stock: ${sinStock.length}
• Bajo stock: ${bajoStock.length}

Por favor, confirme disponibilidad y precios a la brevedad posible.

Agradecemos su pronta atención.

`;

    // Agregar firma electrónica personalizada
    template += generateUserSignature();

    return template;
}

/**
 * Genera la firma electrónica del usuario logueado
 */
function generateUserSignature() {
    const fecha = new Date().toLocaleDateString('es-HN', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });

    const hora = new Date().toLocaleTimeString('es-HN', {
        hour: '2-digit',
        minute: '2-digit'
    });

    let signature = `
${'═'.repeat(63)}
📧 FIRMA ELECTRÓNICA
${'─'.repeat(63)}

Atentamente,

${userData.nombre}
${userData.usuario}
Rey System APP

`;

    if (userData.email) {
        signature += `📧 Email: ${userData.email}\n`;
    }

    if (userData.telefono) {
        signature += `📱 Teléfono: ${userData.telefono}\n`;
    }

    signature += `
${'─'.repeat(63)}
🔐 Documento enviado digitalmente el ${fecha} a las ${hora}
✓ Este correo fue generado y enviado por ${userData.nombre}
${'═'.repeat(63)}

NOTA: Este es un correo oficial de Rey System APP.
Para cualquier consulta, responder a este correo o contactar
directamente con el remitente.`;

    return signature;
}

// ==================== CORREOS ====================
async function loadEmailHistory() {
    try {
        const response = await fetch('api/agenda_api.php?action=get_email_history');
        const data = await response.json();

        if (data.success) {
            renderEmailHistory(data.emails);
        }
    } catch (error) {
        console.error('Error loading emails:', error);
    }
}

function renderEmailHistory(emails) {
    const container = document.getElementById('email-history');
    container.innerHTML = '';

    if (emails.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <span class="material-symbols-outlined text-4xl mb-2">mail</span>
                <p>No hay correos enviados</p>
            </div>
        `;
        return;
    }

    emails.forEach(email => {
        const emailCard = document.createElement('div');
        emailCard.className = 'bg-white dark:bg-[#101622] rounded-lg p-4 border border-gray-200 dark:border-[#324467]';

        const typeColors = {
            pedido: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            nota: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            recordatorio: 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            otro: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
        };

        emailCard.innerHTML = `
            <div class="flex justify-between items-start mb-2">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="px-2 py-1 text-xs rounded ${typeColors[email.tipo]}">${email.tipo}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            ${new Date(email.fecha_envio).toLocaleString('es-ES')}
                        </span>
                    </div>
                    <h5 class="font-semibold text-gray-900 dark:text-white">${email.asunto}</h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Para: ${email.destinatario}</p>
                </div>
                <span class="material-symbols-outlined text-${email.estado === 'enviado' ? 'green' : 'red'}-500">
                    ${email.estado === 'enviado' ? 'check_circle' : 'error'}
                </span>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">${email.mensaje}</p>
        `;

        container.appendChild(emailCard);
    });
}

// ==================== MODALS ====================
function openNewTaskModal() {
    document.getElementById('modal-task').classList.remove('hidden');
    document.getElementById('task-form').reset();
    document.getElementById('task-id').value = '';
}

function closeTaskModal() {
    document.getElementById('modal-task').classList.add('hidden');
}

function openNewNoteModal() {
    document.getElementById('modal-note').classList.remove('hidden');
    document.getElementById('note-form').reset();
    document.getElementById('note-id').value = '';
}

function closeNoteModal() {
    document.getElementById('modal-note').classList.add('hidden');
}

// ==================== FORM SUBMISSIONS ====================
document.getElementById('task-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        id: document.getElementById('task-id').value,
        titulo: document.getElementById('task-titulo').value,
        descripcion: document.getElementById('task-descripcion').value,
        prioridad: document.getElementById('task-prioridad').value,
        fecha_vencimiento: document.getElementById('task-fecha').value || null,
        etiquetas: document.getElementById('task-etiquetas').value
    };

    const action = formData.id ? 'update_task' : 'create_task';

    try {
        const response = await fetch(`api/agenda_api.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const data = await response.json();
        if (data.success) {
            NotificationSystem.success(data.message || 'La tarea se ha guardado correctamente.');
            closeTaskModal();
            loadTasks();
        } else {
            NotificationSystem.error(data.message || 'No se pudo guardar la tarea.');
        }
    } catch (error) {
        console.error('Error saving task:', error);
        NotificationSystem.error('Ocurrió un error al intentar guardar la tarea. Por favor, intenta nuevamente.');
    }
});

document.getElementById('note-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        id: document.getElementById('note-id').value,
        titulo: document.getElementById('note-titulo').value,
        descripcion: document.getElementById('note-descripcion').value,
        etiquetas: document.getElementById('note-etiquetas').value
    };

    const action = formData.id ? 'update_note' : 'create_note';

    try {
        const response = await fetch(`api/agenda_api.php?action=${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const data = await response.json();
        if (data.success) {
            NotificationSystem.success(data.message || 'La nota se ha guardado correctamente.');
            closeNoteModal();
            loadNotes();
        } else {
            NotificationSystem.error(data.message || 'No se pudo guardar la nota.');
        }
    } catch (error) {
        console.error('Error saving note:', error);
        NotificationSystem.error('Ocurrió un error al intentar guardar la nota. Por favor, intenta nuevamente.');
    }
});

document.getElementById('email-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = {
        destinatario: document.getElementById('email-destinatario').value,
        tipo: document.getElementById('email-tipo').value,
        asunto: document.getElementById('email-asunto').value,
        mensaje: document.getElementById('email-mensaje').value
    };

    try {
        const response = await fetch('api/agenda_api.php?action=send_email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            NotificationSystem.success(data.message || 'El correo se ha enviado correctamente.', '✅ Correo Enviado');
            document.getElementById('email-form').reset();
            loadEmailHistory();
        } else {
            NotificationSystem.error(data.message || 'No se pudo enviar el correo.', '❌ Error de Envío');
        }
    } catch (error) {
        console.error('Error sending email:', error);
        NotificationSystem.error('Ocurrió un error al intentar enviar el correo. Verifica tu conexión e intenta nuevamente.', '❌ Error de Conexión');
    }
});

// ==================== INIT ====================
document.addEventListener('DOMContentLoaded', () => {
    loadTasks();
});
