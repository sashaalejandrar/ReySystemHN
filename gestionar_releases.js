// Gestión de Releases - JavaScript

function openNewReleaseModal() {
    document.getElementById('newReleaseModal').classList.remove('hidden');
}

function closeNewReleaseModal() {
    document.getElementById('newReleaseModal').classList.add('hidden');
    document.getElementById('newReleaseForm').reset();
}

function closeViewReleaseModal() {
    document.getElementById('viewReleaseModal').classList.add('hidden');
}

// Crear nueva release
document.getElementById('newReleaseForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span> Creando...';
    
    try {
        const response = await fetch('api_releases.php?action=create', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Release creada exitosamente', 'success');
            closeNewReleaseModal();
            setTimeout(() => location.reload(), 1500);
        } else {
            throw new Error(data.message || 'Error al crear release');
        }
    } catch (error) {
        showNotification(error.message, 'error');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

// Ver detalles de release
function viewRelease(release) {
    const modal = document.getElementById('viewReleaseModal');
    const details = document.getElementById('releaseDetails');
    
    const changes = JSON.parse(release.changes_json || '[]');
    
    const typeColors = {
        major: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        minor: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        patch: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
    };
    
    const statusColors = {
        draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
        pending: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
        published: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        failed: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
    };
    
    details.innerHTML = `
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-2xl font-bold text-slate-900 dark:text-white">
                        v${release.version}
                        ${release.codename ? `<span class="text-lg text-slate-500 dark:text-slate-400">"${release.codename}"</span>` : ''}
                    </h4>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                        Build: ${release.build} | ${new Date(release.release_date).toLocaleDateString()}
                    </p>
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 rounded-lg text-sm font-medium ${typeColors[release.release_type]}">
                        ${release.release_type.toUpperCase()}
                    </span>
                    <span class="px-3 py-1 rounded-lg text-sm font-medium ${statusColors[release.status]}">
                        ${release.status.charAt(0).toUpperCase() + release.status.slice(1)}
                    </span>
                </div>
            </div>
            
            <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
                <h5 class="font-semibold text-slate-900 dark:text-white mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined">list</span>
                    Cambios
                </h5>
                <ul class="space-y-2">
                    ${changes.map(change => `
                        <li class="flex items-start gap-2 text-sm text-slate-600 dark:text-slate-400">
                            <span class="material-symbols-outlined text-green-500 text-sm mt-0.5">check_circle</span>
                            <span>${change}</span>
                        </li>
                    `).join('')}
                </ul>
            </div>
            
            <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
                <h5 class="font-semibold text-slate-900 dark:text-white mb-3">Información Técnica</h5>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-slate-500 dark:text-slate-400">Tipo de Archivo</p>
                        <p class="font-medium text-slate-900 dark:text-white">${release.file_type}</p>
                    </div>
                    <div>
                        <p class="text-slate-500 dark:text-slate-400">Tamaño</p>
                        <p class="font-medium text-slate-900 dark:text-white">${release.file_size || 'N/A'}</p>
                    </div>
                    ${release.github_tag ? `
                    <div>
                        <p class="text-slate-500 dark:text-slate-400">Tag de GitHub</p>
                        <p class="font-medium text-slate-900 dark:text-white">${release.github_tag}</p>
                    </div>
                    ` : ''}
                    ${release.github_release_url ? `
                    <div class="col-span-2">
                        <p class="text-slate-500 dark:text-slate-400 mb-1">URL de GitHub</p>
                        <a href="${release.github_release_url}" target="_blank" 
                           class="text-primary hover:underline flex items-center gap-1">
                            ${release.github_release_url}
                            <span class="material-symbols-outlined text-sm">open_in_new</span>
                        </a>
                    </div>
                    ` : ''}
                </div>
            </div>
            
            <div class="border-t border-slate-200 dark:border-slate-800 pt-4">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Creado por ${release.created_by} el ${new Date(release.created_at).toLocaleString()}
                </p>
            </div>
        </div>
    `;
    
    modal.classList.remove('hidden');
}

// Publicar release
async function publishRelease(id) {
    if (!confirm('¿Estás seguro de publicar esta release? Esto actualizará version.json y creará el commit en Git.')) {
        return;
    }
    
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span>';
    
    try {
        const response = await fetch(`api_releases.php?action=publish&id=${id}`, {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            let message = 'Release publicada exitosamente';
            
            if (data.version_updated) {
                message += ` - version.json actualizado a v${data.version_info.version}`;
            }
            
            showNotification(message, 'success');
            
            // Mostrar detalles en consola
            if (data.git_output && data.git_output.length > 0) {
                console.log('Git Output:', data.git_output);
            }
            
            setTimeout(() => location.reload(), 2000);
        } else {
            throw new Error(data.message || 'Error al publicar release');
        }
    } catch (error) {
        showNotification(error.message, 'error');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}

// Subir a GitHub
async function uploadToGitHub(id) {
    if (!confirm('¿Subir esta release a GitHub? Se creará el tag y la release automáticamente.')) {
        return;
    }
    
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span> Subiendo...';
    
    try {
        const response = await fetch(`api_releases.php?action=upload_github&id=${id}`, {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Subido a GitHub exitosamente', 'success');
            
            // Mostrar detalles del resultado
            if (data.git_output && data.git_output.length > 0) {
                console.log('Git Output:', data.git_output);
            }
            
            setTimeout(() => location.reload(), 2000);
        } else {
            throw new Error(data.message || 'Error al subir a GitHub');
        }
    } catch (error) {
        showNotification(error.message, 'error');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
}

// Eliminar release
async function deleteRelease(id) {
    if (!confirm('¿Estás seguro de eliminar esta release? Esta acción no se puede deshacer.')) {
        return;
    }
    
    try {
        const response = await fetch(`api_releases.php?action=delete&id=${id}`, {
            method: 'POST'
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Release eliminada exitosamente', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            throw new Error(data.message || 'Error al eliminar release');
        }
    } catch (error) {
        showNotification(error.message, 'error');
    }
}

// Sistema de notificaciones
function showNotification(message, type = 'info') {
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500',
        warning: 'bg-yellow-500'
    };
    
    const icons = {
        success: 'check_circle',
        error: 'error',
        info: 'info',
        warning: 'warning'
    };
    
    const notif = document.createElement('div');
    notif.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-4 rounded-lg shadow-2xl z-[9999] flex items-center gap-3 animate-slide-in`;
    notif.innerHTML = `
        <span class="material-symbols-outlined">${icons[type]}</span>
        <span class="font-medium">${message}</span>
    `;
    
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.opacity = '0';
        notif.style.transform = 'translateX(100%)';
        notif.style.transition = 'all 0.3s ease';
        setTimeout(() => notif.remove(), 300);
    }, 4000);
}

// Cerrar modales con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeNewReleaseModal();
        closeViewReleaseModal();
    }
});
