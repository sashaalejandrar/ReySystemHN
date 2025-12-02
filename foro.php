<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Email = $row['Email'];
    $Celular = $row['Celular'];
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);
?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Foro - Rey System APP</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script>
    tailwind.config = {
        darkMode: "class",
        theme: {
            extend: {
                colors: {
                    "primary": "#1152d4",
                    "background-light": "#f6f6f8",
                    "background-dark": "#101622",
                },
                fontFamily: {
                    "display": ["Manrope", "sans-serif"]
                }
            }
        }
    }
</script>
<link rel="stylesheet" href="foro_premium.css">
<style>
    .material-symbols-outlined {
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
    }
</style>
<script src="nova_rey.js"></script>
<script src="chat_foro_shared.js"></script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-screen w-full">
<div class="flex flex-1">
<?php include 'menu_lateral.php'; ?>

<main class="flex-1 overflow-y-auto">
<div class="foro-container py-8">
    
<!-- Page Heading -->
<div class="mb-8 text-center">
    <h1 class="text-5xl font-black mb-3 bg-gradient-to-r from-primary via-purple-600 to-pink-600 bg-clip-text text-transparent">
        💬 Foro Interno
    </h1>
    <p class="text-gray-500 dark:text-gray-400 text-lg">Comparte ideas, novedades y conecta con el equipo</p>
</div>

<!-- New Post Form -->
<div class="new-post-card">
    <div class="flex gap-4">
        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-14 avatar-ring ring" style='background-image: url("<?php echo $Perfil;?>");'></div>
        <div class="flex-1">
            <textarea id="post-content" rows="4" placeholder="¿Qué quieres compartir con el equipo?" 
                      class="post-textarea w-full outline-none"></textarea>
            <div class="flex justify-between items-center mt-4">
                <div class="flex gap-2">
                    <input type="file" id="foro-image-input" class="hidden" accept="image/*" onchange="handleForoImageSelect(event)">
                    <button onclick="document.getElementById('foro-image-input').click()" class="text-gray-500 hover:text-primary transition p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" title="Agregar imagen">
                        <span class="material-symbols-outlined">image</span>
                    </button>
                    <input type="file" id="foro-file-input" class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx" onchange="handleForoFileSelect(event)">
                    <button onclick="document.getElementById('foro-file-input').click()" class="text-gray-500 hover:text-primary transition p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" title="Adjuntar archivo">
                        <span class="material-symbols-outlined">attach_file</span>
                    </button>
                    <button onclick="toggleEmojiPicker('foro')" class="text-gray-500 hover:text-primary transition p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800" title="Agregar emoji">
                        <span class="material-symbols-outlined">mood</span>
                    </button>
                </div>
                <button onclick="crearPost()" class="publish-button flex items-center gap-2">
                    <span>Publicar</span>
                    <span class="material-symbols-outlined">send</span>
                </button>
            </div>
            <!-- Emoji Picker -->
            <div id="foro-emoji-picker" class="hidden mt-2 p-3 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-8 gap-2 max-h-48 overflow-y-auto">
                    <!-- Emojis will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Posts Feed -->
<div id="posts-feed" class="space-y-6">
    <div class="text-center py-12">
        <div class="loading-spinner mx-auto mb-4"></div>
        <p class="text-gray-500 font-medium">Cargando posts...</p>
    </div>
</div>

</div>
</main>
</div>
</div>

<script>
let offset = 0;
let loading = false;
let hasMore = true;

document.addEventListener('DOMContentLoaded', function() {
    cargarPosts();
    
    // Infinite scroll
    window.addEventListener('scroll', () => {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
            if (!loading && hasMore) {
                cargarPosts();
            }
        }
    });
    
    // Auto-refresh every 10 seconds
    setInterval(() => {
        if (offset === 0) {
            cargarPosts(true);
        }
    }, 10000);
});

async function cargarPosts(refresh = false) {
    if (loading) return;
    loading = true;
    
    if (refresh) {
        offset = 0;
    }
    
    try {
        const response = await fetch(`api/get_posts.php?offset=${offset}&limit=20`);
        const result = await response.json();
        
        if (result.success) {
            const feed = document.getElementById('posts-feed');
            
            if (refresh) {
                feed.innerHTML = '';
            }
            
            // Remove loading message if it exists
            const loadingMsg = feed.querySelector('.text-center.py-12');
            if (loadingMsg) {
                loadingMsg.remove();
            }
            
            if (result.data.length === 0) {
                if (offset === 0) {
                    feed.innerHTML = `
                        <div class="empty-state-foro">
                            <span class="material-symbols-outlined empty-state-icon-foro">forum</span>
                            <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-2">No hay posts aún</h3>
                            <p class="text-gray-500 dark:text-gray-400">¡Sé el primero en compartir algo con el equipo!</p>
                        </div>
                    `;
                }
                hasMore = false;
            } else {
                result.data.forEach(post => mostrarPost(post));
                offset += result.data.length;
            }
        } else {
            throw new Error(result.message || 'Error al cargar posts');
        }
    } catch (error) {
        console.error('Error:', error);
        const feed = document.getElementById('posts-feed');
        feed.innerHTML = `
            <div class="empty-state-foro">
                <span class="material-symbols-outlined empty-state-icon-foro text-red-400">error</span>
                <h3 class="text-2xl font-bold text-gray-700 dark:text-gray-300 mb-2">No se pudieron cargar los posts</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">Verifica que el servidor esté funcionando correctamente</p>
                <button onclick="location.reload()" class="publish-button">
                    <span class="material-symbols-outlined">refresh</span>
                    <span>Reintentar</span>
                </button>
            </div>
        `;
    } finally {
        loading = false;
    }
}

function mostrarPost(post) {
    const feed = document.getElementById('posts-feed');
    const postDiv = document.createElement('div');
    postDiv.className = 'post-card';
    postDiv.id = `post-${post.id}`;
    
    const timeAgo = calcularTiempo(post.created_at);
    const likeIcon = post.user_liked ? 'favorite' : 'favorite_border';
    const likeColor = post.user_liked ? 'text-red-500' : 'text-gray-500 dark:text-gray-400';
    
    let commentsHtml = '';
    if (post.comments && post.comments.length > 0) {
        commentsHtml = '<div class="comment-section mt-4">';
        post.comments.forEach(comment => {
            const commentTime = calcularTiempo(comment.created_at);
            commentsHtml += `
                <div class="comment-item">
                    <div class="flex gap-3">
                        <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-9 ring-2 ring-gray-200 dark:ring-gray-700 flex-shrink-0" style='background-image: url("${comment.usuario_avatar || 'default-avatar.png'}");'></div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-bold text-sm">${escapeHtml(comment.usuario_nombre)}</span>
                                <span class="text-xs text-gray-400">${commentTime}</span>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">${escapeHtml(comment.contenido)}</p>
                        </div>
                    </div>
                </div>
            `;
        });
        commentsHtml += '</div>';
    }
    
    postDiv.innerHTML = `
        <div class="flex gap-4">
            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-14 ring-2 ring-gray-200 dark:ring-gray-700 flex-shrink-0 avatar-ring" style='background-image: url("${post.usuario_avatar || 'default-avatar.png'}");'></div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-3">
                    <span class="font-bold text-lg">${escapeHtml(post.usuario_nombre)}</span>
                    <span class="text-sm text-gray-400">${timeAgo}</span>
                </div>
                <p class="text-base text-gray-800 dark:text-gray-200 mb-4 leading-relaxed">${escapeHtml(post.contenido)}</p>
                
                ${post.imagen ? `
                    <div class="mb-4">
                        <img src="${post.imagen}" alt="Imagen del post" class="max-w-full rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:border-primary transition cursor-pointer" onclick="window.open('${post.imagen}', '_blank')">
                    </div>
                ` : ''}
                
                <div class="flex items-center gap-6 mb-2">
                    <button onclick="toggleLike(${post.id})" class="action-button like-button ${likeColor} ${post.user_liked ? 'liked' : ''}">
                        <span class="material-symbols-outlined" id="like-icon-${post.id}">${likeIcon}</span>
                        <span id="like-count-${post.id}" class="font-semibold">${post.likes_count}</span>
                    </button>
                    <button onclick="toggleComments(${post.id})" class="action-button text-gray-500 dark:text-gray-400 hover:text-primary">
                        <span class="material-symbols-outlined">chat_bubble_outline</span>
                        <span class="font-semibold">${post.comments_count}</span>
                    </button>
                </div>
                
                <div id="comments-${post.id}" class="hidden">
                    ${commentsHtml}
                    <div class="mt-4 flex gap-2">
                        <input type="text" id="comment-input-${post.id}" placeholder="Escribe un comentario..." 
                               class="comment-input flex-1 outline-none"
                               onkeypress="if(event.key==='Enter') crearComentario(${post.id})">
                        <button onclick="crearComentario(${post.id})" class="bg-primary hover:bg-primary/90 text-white px-5 py-2 rounded-lg font-semibold transition">
                            Enviar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    feed.appendChild(postDiv);
}

// Función para escapar HTML (reutilizar del chat)
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

async function crearPost() {
    const contenido = document.getElementById('post-content').value.trim();
    
    // Allow posting if there's content OR files
    if (!contenido && !selectedForoImage && !selectedForoFile) {
        showNotification('Escribe algo o adjunta un archivo para publicar', 'warning');
        return;
    }
    
    try {
        let response;
        
        // If there are files, use FormData
        if (selectedForoImage || selectedForoFile) {
            const formData = new FormData();
            formData.append('contenido', contenido || '');
            if (selectedForoImage) formData.append('image', selectedForoImage);
            if (selectedForoFile) formData.append('file', selectedForoFile);
            
            response = await fetch('api/create_post.php', {
                method: 'POST',
                body: formData
            });
        } else {
            // Text-only post
            response = await fetch('api/create_post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ contenido: contenido })
            });
        }
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('post-content').value = '';
            document.getElementById('post-content').placeholder = '¿Qué quieres compartir con el equipo?';
            
            // Clear file selections
            selectedForoImage = null;
            selectedForoFile = null;
            const imageInput = document.getElementById('foro-image-input');
            const fileInput = document.getElementById('foro-file-input');
            if (imageInput) imageInput.value = '';
            if (fileInput) fileInput.value = '';
            
            // Remove image preview if exists
            const preview = document.querySelector('.image-preview');
            if (preview) preview.remove();
            
            showNotification('Post publicado exitosamente', 'success');
            offset = 0;
            cargarPosts(true);
        } else {
            showNotification('Error al crear post: ' + result.message, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Error al crear post', 'error');
    }
}

async function toggleLike(postId) {
    try {
        const response = await fetch('api/like_post.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const icon = document.getElementById(`like-icon-${postId}`);
            const count = document.getElementById(`like-count-${postId}`);
            
            if (result.action === 'liked') {
                icon.textContent = 'favorite';
                icon.parentElement.classList.add('text-red-500');
                icon.parentElement.classList.remove('text-gray-500');
            } else {
                icon.textContent = 'favorite_border';
                icon.parentElement.classList.add('text-gray-500');
                icon.parentElement.classList.remove('text-red-500');
            }
            
            count.textContent = result.likes_count;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function toggleComments(postId) {
    const commentsDiv = document.getElementById(`comments-${postId}`);
    commentsDiv.classList.toggle('hidden');
}

async function crearComentario(postId) {
    const input = document.getElementById(`comment-input-${postId}`);
    const contenido = input.value.trim();
    
    if (!contenido) return;
    
    try {
        const response = await fetch('api/create_comment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ post_id: postId, contenido: contenido })
        });
        
        const result = await response.json();
        
        if (result.success) {
            input.value = '';
            offset = 0;
            cargarPosts(true);
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

function calcularTiempo(datetime) {
    const now = new Date();
    const then = new Date(datetime);
    const diff = Math.floor((now - then) / 1000);
    
    if (diff < 60) return 'hace ' + diff + 's';
    if (diff < 3600) return 'hace ' + Math.floor(diff / 60) + 'm';
    if (diff < 86400) return 'hace ' + Math.floor(diff / 3600) + 'h';
    return 'hace ' + Math.floor(diff / 86400) + 'd';
}

// Note: Emoji picker and file handling functions are loaded from chat.php
// The functions toggleEmojiPicker(), insertEmoji(), handleForoImageSelect(), 
// handleForoFileSelect(), removeForoImage(), and showNotification() are shared
</script>
</body>
</html>
