<?php
session_start();

// Verificar que hay una sesión temporal
if (!isset($_SESSION['temp_usuario'])) {
    header("Location: login.php");
    exit();
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

require_once 'security_keys_helper.php';

$usuario = $_SESSION['temp_usuario'];

// Verificar métodos disponibles
$hasSecurityKeys = hasSecurityKeys($conexion, $usuario);
$hasPin = hasPinEnabled($conexion, $usuario);
$has2FA = false;

// Verificar 2FA
$stmt_2fa = $conexion->prepare("SELECT enabled FROM autenticacion_2fa WHERE idUsuario = ? AND enabled = 1");
$stmt_2fa->bind_param("s", $usuario);
$stmt_2fa->execute();
$result_2fa = $stmt_2fa->get_result();
$has2FA = $result_2fa->num_rows > 0;

$error = $_SESSION['pin_error'] ?? "";
unset($_SESSION['pin_error']); // Limpiar error después de mostrarlo

$success = "";
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Verificación de Seguridad - ReySystem</title>
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
  <style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
    
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .method-card {
      animation: fadeIn 0.5s ease-out forwards;
      transition: all 0.3s ease;
    }
    
    .method-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px rgba(17, 82, 212, 0.2);
    }
  </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark min-h-screen flex items-center justify-center p-6">
  <div class="w-full max-w-2xl">
    <!-- Header -->
    <div class="text-center mb-8">
      <div class="flex items-center justify-center gap-3 mb-4">
        <span class="material-symbols-outlined text-primary text-4xl">shield</span>
        <span class="text-2xl font-bold text-slate-800 dark:text-white">Verificación de Seguridad</span>
      </div>
      <p class="text-slate-600 dark:text-slate-400">Elige un método para verificar tu identidad</p>
    </div>

    <?php if ($error): ?>
      <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 text-red-700 dark:text-red-400">
        <div class="flex items-center gap-2">
          <span class="material-symbols-outlined">error</span>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      </div>
    <?php endif; ?>

    <?php if (!$hasSecurityKeys && !$hasPin && !$has2FA): ?>
      <!-- Sin métodos configurados -->
      <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-6 text-center">
        <span class="material-symbols-outlined text-yellow-600 dark:text-yellow-400 text-5xl mb-3">warning</span>
        <h3 class="text-lg font-bold text-yellow-800 dark:text-yellow-200 mb-2">No tienes métodos de seguridad configurados</h3>
        <p class="text-sm text-yellow-700 dark:text-yellow-300 mb-4">
          Para mayor seguridad, te recomendamos configurar al menos un método de verificación.
        </p>
        <div class="flex gap-3 justify-center">
          <a href="configuracion.php" 
             class="py-2 px-4 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium transition-colors">
            Configurar Ahora
          </a>
          <button onclick="skipVerification()" 
                  class="py-2 px-4 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg font-medium hover:opacity-90 transition-opacity">
            Continuar sin Verificar
          </button>
        </div>
      </div>
    <?php endif; ?>

    <!-- Métodos de Verificación -->
    <div class="grid gap-4 md:grid-cols-2">
      
      <?php if ($hasSecurityKeys): ?>
      <!-- Llave de Seguridad -->
      <div class="method-card bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6 cursor-pointer"
           onclick="verifyWithSecurityKey()">
        <div class="flex flex-col items-center text-center gap-4">
          <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center">
            <span class="material-symbols-outlined text-white text-3xl">key</span>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Llave de Seguridad</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Usa tu llave física o biométrica</p>
          </div>
          <button type="button" onclick="verifyWithSecurityKey()" 
                  class="w-full py-2 px-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-lg font-medium hover:opacity-90 transition-opacity">
            Verificar
          </button>
        </div>
      </div>
      <?php endif; ?>

      <!-- PIN de Seguridad -->
      <div class="method-card bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex flex-col items-center text-center gap-4">
          <div class="w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl flex items-center justify-center">
            <span class="material-symbols-outlined text-white text-3xl">pin</span>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">PIN de Seguridad</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">
              <?php echo $hasPin ? 'Ingresa tu código PIN' : 'Configura un PIN primero'; ?>
            </p>
          </div>
          <?php if ($hasPin): ?>
          <form method="POST" action="verify_pin_login.php" class="w-full">
            <input type="text" name="pin" maxlength="6" pattern="[0-9]{4,6}" required
                   placeholder="Ingresa tu PIN"
                   class="w-full mb-3 px-4 py-2 text-center text-2xl tracking-widest border border-slate-300 dark:border-slate-700 rounded-lg bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:border-green-500 focus:ring-2 focus:ring-green-500/20" />
            <button type="submit" 
                    class="w-full py-2 px-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white rounded-lg font-medium hover:opacity-90 transition-opacity">
              Verificar PIN
            </button>
          </form>
          <?php else: ?>
          <div class="w-full text-center text-sm text-slate-500 dark:text-slate-400">
            <p class="mb-3">No tienes un PIN configurado</p>
            <a href="configuracion.php" 
               class="inline-block py-2 px-4 bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg font-medium hover:opacity-90 transition-opacity">
              Ir a Configuración
            </a>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($has2FA): ?>
      <!-- 2FA -->
      <div class="method-card bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex flex-col items-center text-center gap-4">
          <div class="w-16 h-16 bg-gradient-to-br from-orange-500 to-red-600 rounded-2xl flex items-center justify-center">
            <span class="material-symbols-outlined text-white text-3xl">smartphone</span>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Autenticación 2FA</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Código de tu app autenticadora</p>
          </div>
          <a href="verify_2fa.php" 
             class="w-full py-2 px-4 bg-gradient-to-r from-orange-500 to-red-600 text-white rounded-lg font-medium hover:opacity-90 transition-opacity text-center block">
            Usar 2FA
          </a>
        </div>
      </div>
      <?php endif; ?>

      <!-- Dispositivo de Confianza -->
      <div class="method-card bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-6">
        <div class="flex flex-col items-center text-center gap-4">
          <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-2xl flex items-center justify-center">
            <span class="material-symbols-outlined text-white text-3xl">devices</span>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Dispositivo de Confianza</h3>
            <p class="text-sm text-slate-600 dark:text-slate-400">Registra este dispositivo</p>
          </div>
          <button type="button" onclick="registerTrustedDevice()" 
                  class="w-full py-2 px-4 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-lg font-medium hover:opacity-90 transition-opacity">
            Registrar Dispositivo
          </button>
        </div>
      </div>
    </div>

    <!-- Cancelar -->
    <div class="mt-6 text-center">
      <a href="logout.php" class="text-sm text-slate-600 dark:text-slate-400 hover:text-primary dark:hover:text-primary">
        Cancelar y cerrar sesión
      </a>
    </div>

    <!-- Status Message -->
    <div id="statusMessage" class="hidden mt-6 p-4 rounded-lg"></div>
  </div>

  <script>
    async function verifyWithSecurityKey() {
      const statusEl = document.getElementById('statusMessage');
      statusEl.className = 'mt-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400';
      statusEl.textContent = '🔐 Preparando verificación con llave de seguridad...';
      statusEl.classList.remove('hidden');

      try {
        // Obtener challenge del servidor
        const challengeResponse = await fetch('api_security_keys.php?action=get_challenge&usuario=<?= $usuario ?>');
        const challengeData = await challengeResponse.json();
        
        if (!challengeData.success) {
          throw new Error(challengeData.message || 'Error al obtener challenge');
        }

        statusEl.textContent = '👆 Usa tu llave de seguridad o biometría...';

        // Convertir challenge de base64 a ArrayBuffer
        const challenge = Uint8Array.from(atob(challengeData.challenge), c => c.charCodeAt(0));
        
        // Obtener credenciales del usuario
        const allowCredentials = challengeData.credentials.map(cred => ({
          type: 'public-key',
          id: Uint8Array.from(atob(cred.credential_id), c => c.charCodeAt(0))
        }));

        const publicKeyCredentialRequestOptions = {
          challenge: challenge,
          allowCredentials: allowCredentials,
          timeout: 60000,
          userVerification: 'preferred'
        };

        // Solicitar autenticación
        const assertion = await navigator.credentials.get({
          publicKey: publicKeyCredentialRequestOptions
        });

        if (!assertion) {
          throw new Error('No se pudo obtener la credencial');
        }

        statusEl.textContent = '✅ Verificando...';

        // Enviar al servidor para verificación
        const verifyResponse = await fetch('verify_security_key_login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            credentialId: btoa(String.fromCharCode(...new Uint8Array(assertion.rawId))),
            authenticatorData: btoa(String.fromCharCode(...new Uint8Array(assertion.response.authenticatorData))),
            clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(assertion.response.clientDataJSON))),
            signature: btoa(String.fromCharCode(...new Uint8Array(assertion.response.signature)))
          })
        });

        const verifyData = await verifyResponse.json();

        if (verifyData.success) {
          statusEl.className = 'mt-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400';
          statusEl.textContent = '✅ Verificación exitosa! Redirigiendo...';
          setTimeout(() => window.location.href = 'index.php', 1500);
        } else {
          throw new Error(verifyData.message || 'Verificación fallida');
        }

      } catch (error) {
        console.error('Error:', error);
        statusEl.className = 'mt-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400';
        statusEl.textContent = '❌ Error: ' + error.message;
      }
    }

    async function registerTrustedDevice() {
      const statusEl = document.getElementById('statusMessage');
      statusEl.className = 'mt-6 p-4 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 text-cyan-700 dark:text-cyan-400';
      statusEl.textContent = '🔐 Registrando dispositivo de confianza...';
      statusEl.classList.remove('hidden');

      try {
        const response = await fetch('register_trusted_device_login_v3.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' }
        });

        // Verificar si la respuesta es válida
        const text = await response.text();
        console.log('Response status:', response.status);
        console.log('Response text:', text);
        
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          throw new Error('Respuesta inválida del servidor: ' + text.substring(0, 100));
        }

        if (data.success) {
          statusEl.className = 'mt-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400';
          statusEl.textContent = '✅ Dispositivo registrado! Redirigiendo...';
          setTimeout(() => window.location.href = 'index.php', 1500);
        } else {
          throw new Error(data.message || 'Error al registrar dispositivo');
        }
      } catch (error) {
        console.error('Error:', error);
        statusEl.className = 'mt-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400';
        statusEl.textContent = '❌ Error: ' + error.message;
      }
    }

    async function skipVerification() {
      const statusEl = document.getElementById('statusMessage');
      statusEl.className = 'mt-6 p-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-400';
      statusEl.textContent = '⏭️ Saltando verificación...';
      statusEl.classList.remove('hidden');

      try {
        const response = await fetch('skip_verification_login.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' }
        });

        const data = await response.json();

        if (data.success) {
          statusEl.className = 'mt-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400';
          statusEl.textContent = '✅ Redirigiendo...';
          setTimeout(() => window.location.href = 'index.php', 1000);
        } else {
          throw new Error(data.message || 'Error al continuar');
        }
      } catch (error) {
        console.error('Error:', error);
        statusEl.className = 'mt-6 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400';
        statusEl.textContent = '❌ Error: ' + error.message;
      }
    }
  </script>
</body>
</html>
