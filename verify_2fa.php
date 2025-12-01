<?php
session_start();

// Verificar que el usuario viene del login
if (!isset($_SESSION['temp_usuario']) || !isset($_SESSION['temp_2fa_secret'])) {
    header("Location: login.php");
    exit();
}

require_once '2fa_helper.php';

$error = "";
$success = "";

// Procesar verificación del código 2FA
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['codigo_2fa'])) {
    $codigo = preg_replace('/\s+/', '', $_POST['codigo_2fa']); // Eliminar espacios
    $codigo = trim($codigo); // Eliminar espacios al inicio y final
    
    if (verify2FACode($_SESSION['temp_2fa_secret'], $codigo)) {
        // Código correcto - completar el login
        $_SESSION["usuario"] = $_SESSION['temp_usuario'];
        $_SESSION["user_id"] = $_SESSION['temp_user_id'];
        $_SESSION['usuario_id'] = $_SESSION['temp_user_id'];
        $_SESSION['rol'] = $_SESSION['temp_rol'];
        $_SESSION['perfil'] = $_SESSION['temp_perfil'];
        $_SESSION['nombre'] = $_SESSION['temp_nombre'];
        
        // Limpiar variables temporales
        unset($_SESSION['temp_usuario']);
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_rol']);
        unset($_SESSION['temp_perfil']);
        unset($_SESSION['temp_nombre']);
        unset($_SESSION['temp_2fa_secret']);
        
        header("Location: index.php");
        exit();
    } else {
        $error = "Código de verificación incorrecto. Intenta de nuevo.";
    }
}
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Verificación 2FA - ReySystemApp</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <link href="https://fonts.googleapis.com" rel="preconnect"/>
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
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
          },
        },
      },
    }
  </script>
  <style>
    .material-symbols-outlined {
      font-variation-settings:
      'FILL' 0,
      'wght' 400,
      'GRAD' 0,
      'opsz' 24
    }
    
    /* Animación para el input */
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-10px); }
      75% { transform: translateX(10px); }
    }
    
    .shake {
      animation: shake 0.5s;
    }
  </style>
</head>
<body class="font-display">
  <div class="relative flex h-auto min-h-screen w-full flex-col items-center justify-center bg-background-light dark:bg-background-dark p-6">
    <div class="flex w-full max-w-md flex-col items-center">
      <div class="mb-8 flex items-center gap-3">
        <span class="material-symbols-outlined text-primary text-4xl">shield</span>
        <span class="text-2xl font-bold text-slate-800 dark:text-white">ReySystemApp</span>
      </div>
      
      <div class="w-full rounded-xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/50 sm:p-8">
        <div class="text-center">
          <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 text-3xl">verified_user</span>
          </div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">Verificación en Dos Pasos</h1>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Ingresa el código de 6 dígitos de Google Authenticator</p>
        </div>

        <?php if ($error): ?>
          <div class="mt-4 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
            <div class="flex items-center gap-2">
              <span class="material-symbols-outlined text-red-600 dark:text-red-400">error</span>
              <p class="text-sm text-red-600 dark:text-red-400 font-medium"><?= $error ?></p>
            </div>
          </div>
        <?php endif; ?>

        <form method="POST" class="mt-8 flex flex-col gap-5" id="2faForm">
          <div class="flex flex-col gap-1.5">
            <label class="text-sm font-medium text-slate-700 dark:text-slate-300 text-center" for="codigo_2fa">
              Código de Verificación
            </label>
            <input 
              name="codigo_2fa" 
              id="codigo_2fa" 
              type="text" 
              required 
              maxlength="7"
              placeholder="000 000"
              autocomplete="off"
              autofocus
              class="form-input block w-full rounded-lg border border-slate-300 bg-slate-50/50 py-4 px-4 text-center text-2xl font-mono tracking-widest text-slate-900 placeholder:text-slate-400 focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-background-dark dark:text-white dark:placeholder:text-slate-500 dark:focus:border-primary dark:focus:ring-primary"
            />
            <p class="text-xs text-slate-500 dark:text-slate-400 text-center mt-1">
              <span class="material-symbols-outlined text-xs align-middle">info</span>
              Abre Google Authenticator en tu dispositivo
            </p>
          </div>

          <button type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">
            <span class="material-symbols-outlined">lock_open</span>
            Verificar y Acceder
          </button>
          
          <a href="verify_login.php" 
            class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-300 dark:border-slate-700 px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-300 transition-colors hover:bg-slate-50 dark:hover:bg-slate-800">
            <span class="material-symbols-outlined">arrow_back</span>
            Elegir Otro Método
          </a>
        </form>
        
        <div class="mt-6 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 p-4">
          <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-2 flex items-center gap-2">
            <span class="material-symbols-outlined text-lg">help</span>
            ¿Problemas para acceder?
          </h3>
          <p class="text-xs text-blue-700 dark:text-blue-300">
            Si perdiste acceso a tu dispositivo, contacta al administrador del sistema para recuperar tu cuenta.
          </p>
        </div>
      </div>
    </div>
  </div>
  
  <script>
    // Auto-formatear el código mientras se escribe
    const input = document.getElementById('codigo_2fa');
    const form = document.getElementById('2faForm');
    
    input.addEventListener('input', function(e) {
      let value = e.target.value.replace(/\s/g, '');
      if (value.length > 3) {
        value = value.slice(0, 3) + ' ' + value.slice(3);
      }
      e.target.value = value;
      
      // Auto-submit cuando se completen 6 dígitos
      if (value.replace(/\s/g, '').length === 6) {
        setTimeout(() => form.submit(), 300);
      }
    });
    
    // Solo permitir números
    input.addEventListener('keypress', function(e) {
      if (!/[0-9]/.test(e.key) && e.key !== 'Backspace') {
        e.preventDefault();
      }
    });
    
    // Animación de shake en error
    <?php if ($error): ?>
    input.classList.add('shake');
    setTimeout(() => input.classList.remove('shake'), 500);
    <?php endif; ?>
  </script>
</body>
</html>
