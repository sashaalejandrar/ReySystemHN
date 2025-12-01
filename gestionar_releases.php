<?php
 
include 'funciones.php';
VerificarSiUsuarioYaInicioSesion();

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Crear tabla si no existe
$sql_create = file_get_contents('create_updates_table.sql');
$conexion->multi_query($sql_create);
while ($conexion->next_result()) {;}

// Obtener usuario
$usuario = $_SESSION['usuario'];
$stmt = $conexion->prepare("SELECT Id, Nombre, Apellido, Perfil, Rol FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$Nombre_Completo = $row['Nombre'] . " " . $row['Apellido'];
$rol = strtolower($row['rol'] ?? '');
$Rol = $row['Rol'];
$Perfil = $row['Perfil'];
 $rol_usuario = strtolower($Rol);
// Solo admin puede acceder

// Leer versión actual
$current_version = json_decode(file_get_contents('version.json'), true);

// Obtener releases de la BD
$releases = [];
$result = $conexion->query("SELECT * FROM updates ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $releases[] = $row;
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Gestionar Releases - ReySystem</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#1152d4",
          },
          fontFamily: {
            "display": ["Manrope", "sans-serif"]
          }
        }
      }
    }
  </script>
  <style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
  </style>
</head>
<body class="font-display bg-slate-50 dark:bg-[#0d1117]">
  
<div class="relative flex h-auto min-h-screen w-full flex-col">
  <div class="flex flex-1">
    <?php include 'menu_lateral.php'; ?>
    
    <div class="flex flex-1 flex-col">
      <!-- Header -->
      <header class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-[#0d1117] px-6 py-4">
        <div>
          <h1 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-3">
            <span class="material-symbols-outlined text-3xl text-primary">rocket_launch</span>
            Gestionar Releases
          </h1>
          <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
            Versión actual: <span class="font-semibold text-primary">v<?= $current_version['version'] ?></span> 
            "<?= $current_version['codename'] ?>"
          </p>
        </div>
        <button onclick="openNewReleaseModal()" 
          class="px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-lg font-medium transition-all flex items-center gap-2 shadow-lg">
          <span class="material-symbols-outlined">add</span>
          Nueva Release
        </button>
      </header>

      <!-- Content -->
      <main class="flex-1 p-6">
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between mb-2">
              <span class="material-symbols-outlined text-3xl opacity-80">inventory</span>
              <span class="text-2xl font-bold"><?= count($releases) ?></span>
            </div>
            <p class="text-sm opacity-90">Total Releases</p>
          </div>
          
          <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between mb-2">
              <span class="material-symbols-outlined text-3xl opacity-80">check_circle</span>
              <span class="text-2xl font-bold">
                <?= count(array_filter($releases, fn($r) => $r['status'] === 'published')) ?>
              </span>
            </div>
            <p class="text-sm opacity-90">Publicadas</p>
          </div>
          
          <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between mb-2">
              <span class="material-symbols-outlined text-3xl opacity-80">pending</span>
              <span class="text-2xl font-bold">
                <?= count(array_filter($releases, fn($r) => $r['status'] === 'draft')) ?>
              </span>
            </div>
            <p class="text-sm opacity-90">Borradores</p>
          </div>
          
          <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between mb-2">
              <span class="material-symbols-outlined text-3xl opacity-80">update</span>
              <span class="text-xl font-bold"><?= $current_version['version'] ?></span>
            </div>
            <p class="text-sm opacity-90">Versión Actual</p>
          </div>
        </div>

        <!-- Releases List -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
          <div class="p-6 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <span class="material-symbols-outlined">history</span>
              Historial de Releases
            </h2>
          </div>
          
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-slate-50 dark:bg-slate-800">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Versión</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Tipo</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Estado</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Fecha</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Archivo</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                <?php if (empty($releases)): ?>
                <tr>
                  <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                    <span class="material-symbols-outlined text-5xl mb-2 opacity-50">inbox</span>
                    <p>No hay releases creadas aún</p>
                    <button onclick="openNewReleaseModal()" class="mt-4 text-primary hover:underline">
                      Crear primera release
                    </button>
                  </td>
                </tr>
                <?php else: ?>
                <?php foreach ($releases as $release): ?>
                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                  <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                      <span class="font-semibold text-slate-900 dark:text-white">v<?= $release['version'] ?></span>
                      <?php if ($release['codename']): ?>
                      <span class="text-xs text-slate-500 dark:text-slate-400">"<?= $release['codename'] ?>"</span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td class="px-6 py-4">
                    <?php
                    $typeColors = [
                      'major' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                      'minor' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                      'patch' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                    ];
                    ?>
                    <span class="px-2 py-1 rounded text-xs font-medium <?= $typeColors[$release['release_type']] ?>">
                      <?= strtoupper($release['release_type']) ?>
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <?php
                    $statusColors = [
                      'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-400',
                      'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                      'published' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                      'failed' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                    ];
                    ?>
                    <span class="px-2 py-1 rounded text-xs font-medium <?= $statusColors[$release['status']] ?>">
                      <?= ucfirst($release['status']) ?>
                    </span>
                  </td>
                  <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                    <?= date('d/m/Y', strtotime($release['release_date'])) ?>
                  </td>
                  <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">
                    <?= $release['file_type'] ?>
                  </td>
                  <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                      <button onclick='viewRelease(<?= json_encode($release) ?>)' 
                        class="p-2 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                        title="Ver detalles">
                        <span class="material-symbols-outlined text-sm">visibility</span>
                      </button>
                      <?php if ($release['status'] === 'draft'): ?>
                      <button onclick="publishRelease(<?= $release['id'] ?>)" 
                        class="p-2 text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition-colors"
                        title="Publicar">
                        <span class="material-symbols-outlined text-sm">publish</span>
                      </button>
                      <?php endif; ?>
                      <?php if ($release['status'] === 'published' && !empty($release['file_path'])): ?>
                      <button onclick="uploadToGitHub(<?= $release['id'] ?>)" 
                        class="p-2 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors"
                        title="Subir a GitHub">
                        <span class="material-symbols-outlined text-sm">cloud_upload</span>
                      </button>
                      <?php endif; ?>
                      <button onclick="deleteRelease(<?= $release['id'] ?>)" 
                        class="p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                        title="Eliminar">
                        <span class="material-symbols-outlined text-sm">delete</span>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </main>
    </div>
  </div>
</div>

<!-- Modal Nueva Release -->
<div id="newReleaseModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
    <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-purple-600 p-6 flex items-center justify-between">
      <h3 class="text-2xl font-bold text-white flex items-center gap-2">
        <span class="material-symbols-outlined">add_circle</span>
        Nueva Release
      </h3>
      <button onclick="closeNewReleaseModal()" class="text-white hover:bg-white/20 rounded-lg p-2">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    
    <form id="newReleaseForm" class="p-6 space-y-6">
      <!-- Información Básica -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Versión *
          </label>
          <input type="text" name="version" required placeholder="2.6.0"
            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary">
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Formato: MAJOR.MINOR.PATCH</p>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Nombre Código
          </label>
          <input type="text" name="codename" placeholder="Supernova"
            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Tipo de Release *
          </label>
          <select name="release_type" required
            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary">
            <option value="patch">Patch - Correcciones</option>
            <option value="minor" selected>Minor - Nuevas características</option>
            <option value="major">Major - Cambios importantes</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Fecha de Release *
          </label>
          <input type="date" name="release_date" required value="<?= date('Y-m-d') ?>"
            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary">
        </div>
        
        <div>
          <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
            Tipo de Archivo *
          </label>
          <select name="file_type" required
            class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary">
            <option value="tar.gz">TAR.GZ</option>
            <option value="zip">ZIP</option>
            <option value="both">Ambos</option>
          </select>
        </div>
      </div>
      
      <!-- Cambios -->
      <div>
        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
          Cambios (uno por línea) *
        </label>
        <textarea name="changes" required rows="6" placeholder="Nueva característica X&#10;Mejora en Y&#10;Fix en Z"
          class="w-full px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-primary"></textarea>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Cada línea será un cambio en el changelog</p>
      </div>
      
      <!-- Acciones -->
      <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
        <div class="flex items-start gap-2">
          <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">info</span>
          <div class="text-sm text-blue-900 dark:text-blue-100">
            <p class="font-semibold mb-1">Al publicar, el sistema automáticamente:</p>
            <ul class="list-disc list-inside space-y-1 text-blue-800 dark:text-blue-200">
              <li>Actualiza version.json</li>
              <li>Hace commit y push a GitHub</li>
              <li>Crea tag (v2.6.0)</li>
              <li>Crea release en GitHub (si tienes gh CLI instalado)</li>
              <li>Sube el archivo comprimido</li>
            </ul>
          </div>
        </div>
      </div>
      
      <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="create_file" checked
              class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary">
            <span class="text-sm text-slate-700 dark:text-slate-300">Crear archivo comprimido</span>
          </label>
          
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="checkbox" name="git_commit" checked
              class="w-4 h-4 text-primary border-slate-300 rounded focus:ring-primary">
            <span class="text-sm text-slate-700 dark:text-slate-300">Publicar en GitHub</span>
          </label>
        </div>
        
        <div class="flex gap-2">
          <button type="button" onclick="closeNewReleaseModal()"
            class="px-4 py-2 border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800">
            Cancelar
          </button>
          <button type="submit"
            class="px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 flex items-center gap-2">
            <span class="material-symbols-outlined">save</span>
            Crear Release
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Modal Ver Release -->
<div id="viewReleaseModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
    <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-purple-600 p-6 flex items-center justify-between">
      <h3 class="text-2xl font-bold text-white flex items-center gap-2">
        <span class="material-symbols-outlined">info</span>
        Detalles de Release
      </h3>
      <button onclick="closeViewReleaseModal()" class="text-white hover:bg-white/20 rounded-lg p-2">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    
    <div id="releaseDetails" class="p-6">
      <!-- Se llenará dinámicamente -->
    </div>
  </div>
</div>

<!-- Modal de Confirmación -->
<div id="confirmModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full shadow-2xl">
    <div class="p-6">
      <div class="flex items-center gap-4 mb-4">
        <div id="confirmIcon" class="w-12 h-12 rounded-full flex items-center justify-center">
          <span class="material-symbols-outlined text-2xl"></span>
        </div>
        <div>
          <h3 id="confirmTitle" class="text-xl font-bold text-slate-900 dark:text-white"></h3>
          <p id="confirmMessage" class="text-sm text-slate-600 dark:text-slate-400 mt-1"></p>
        </div>
      </div>
      <div class="flex gap-3 justify-end">
        <button onclick="closeConfirmModal()" 
          class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
          Cancelar
        </button>
        <button id="confirmButton" 
          class="px-4 py-2 rounded-lg text-white font-medium transition-colors">
          Confirmar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Gestión de Releases - JavaScript Inline

// Sistema de confirmación con modal
let confirmCallback = null;

function showConfirm(title, message, type = 'warning') {
    return new Promise((resolve) => {
        const modal = document.getElementById('confirmModal');
        const icon = document.getElementById('confirmIcon');
        const iconSpan = icon.querySelector('.material-symbols-outlined');
        const titleEl = document.getElementById('confirmTitle');
        const messageEl = document.getElementById('confirmMessage');
        const confirmBtn = document.getElementById('confirmButton');
        
        // Configurar según tipo
        const configs = {
            warning: {
                iconBg: 'bg-yellow-100 dark:bg-yellow-900/30',
                iconColor: 'text-yellow-600 dark:text-yellow-400',
                icon: 'warning',
                btnColor: 'bg-yellow-600 hover:bg-yellow-700'
            },
            danger: {
                iconBg: 'bg-red-100 dark:bg-red-900/30',
                iconColor: 'text-red-600 dark:text-red-400',
                icon: 'delete',
                btnColor: 'bg-red-600 hover:bg-red-700'
            },
            info: {
                iconBg: 'bg-blue-100 dark:bg-blue-900/30',
                iconColor: 'text-blue-600 dark:text-blue-400',
                icon: 'info',
                btnColor: 'bg-blue-600 hover:bg-blue-700'
            },
            success: {
                iconBg: 'bg-green-100 dark:bg-green-900/30',
                iconColor: 'text-green-600 dark:text-green-400',
                icon: 'check_circle',
                btnColor: 'bg-green-600 hover:bg-green-700'
            }
        };
        
        const config = configs[type] || configs.warning;
        
        // Aplicar estilos
        icon.className = `w-12 h-12 rounded-full flex items-center justify-center ${config.iconBg}`;
        iconSpan.className = `material-symbols-outlined text-2xl ${config.iconColor}`;
        iconSpan.textContent = config.icon;
        confirmBtn.className = `px-4 py-2 rounded-lg text-white font-medium transition-colors ${config.btnColor}`;
        
        // Configurar contenido
        titleEl.textContent = title;
        messageEl.textContent = message;
        
        // Configurar callback
        confirmCallback = () => {
            resolve(true);
            closeConfirmModal();
        };
        
        // Mostrar modal
        modal.classList.remove('hidden');
        
        // Manejar cancelación
        const cancelHandler = () => {
            resolve(false);
            closeConfirmModal();
        };
        
        // Agregar event listener temporal
        modal.dataset.cancelHandler = 'true';
    });
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.add('hidden');
    confirmCallback = null;
}

// Ejecutar callback de confirmación
document.getElementById('confirmButton').addEventListener('click', () => {
    if (confirmCallback) confirmCallback();
});

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
            let msg = 'Release creada exitosamente';
            if (data.file_path) {
                msg += ` - Archivo: ${data.file_size}`;
            }
            if (data.file_result && !data.file_result.success) {
                msg += ` (Advertencia: ${data.file_result.message})`;
            }
            showNotification(msg, 'success');
            console.log('Release creada:', data);
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
    const confirmed = await showConfirm(
        '¿Publicar Release?',
        'Esto actualizará version.json, creará el commit en Git y lo subirá a GitHub.',
        'warning'
    );
    
    if (!confirmed) return;
    
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
            
            console.log('Release publicada:', data);
            if (data.git_output) {
                console.log('Git output:', data.git_output);
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
    const confirmed = await showConfirm(
        '¿Subir a GitHub?',
        'Se creará el tag y la release automáticamente con el archivo adjunto.',
        'info'
    );
    
    if (!confirmed) return;
    
    const btn = event.target;
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span>';
    
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
    const confirmed = await showConfirm(
        '¿Eliminar Release?',
        'Esta acción no se puede deshacer. Se eliminará la release y su archivo.',
        'danger'
    );
    
    if (!confirmed) return;
    
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
    notif.className = `fixed top-4 right-4 ${colors[type]} text-white px-6 py-4 rounded-lg shadow-2xl z-[9999] flex items-center gap-3`;
    notif.style.animation = 'slideIn 0.3s ease-out';
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

// Log para debug
console.log('Gestionar Releases JS cargado correctamente');
</script>
</body>
</html>
