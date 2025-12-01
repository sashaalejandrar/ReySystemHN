<?php
session_start();
require_once 'db_connect.php';
require_once 'security_keys_helper.php';

// Verificar que hay una sesión temporal de verificación
if (!isset($_SESSION['temp_security_check'])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

// Procesar verificación
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pin = $_POST['pin'] ?? '';
    $user_id = $_SESSION['temp_usuario'];
    
    if (verifySecurityPIN($conexion, $user_id, $pin)) {
        // PIN correcto - completar login
        $_SESSION["usuario"] = $_SESSION['temp_usuario'];
        $_SESSION["user_id"] = $_SESSION['temp_user_id'];
        $_SESSION['usuario_id'] = $_SESSION['temp_user_id'];
        $_SESSION['rol'] = $_SESSION['temp_rol'];
        $_SESSION['perfil'] = $_SESSION['temp_perfil'];
        $_SESSION['nombre'] = $_SESSION['temp_nombre'];
        
        // Limpiar sesión temporal
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_usuario']);
        unset($_SESSION['temp_rol']);
        unset($_SESSION['temp_perfil']);
        unset($_SESSION['temp_nombre']);
        unset($_SESSION['temp_security_check']);
        
        header("Location: index.php");
        exit();
    } else {
        $error = "PIN incorrecto";
    }
}

// Obtener tipos de llaves habilitadas
$security_types = getEnabledSecurityKeyTypes($conexion, $_SESSION['temp_usuario']);
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Verificación de Seguridad</title>
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
  <style>
    .material-symbols-outlined {
      font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
    }
  </style>
</head>
<body class="bg-gradient-to-br from-slate-900 via-blue-900 to-slate-900 min-h-screen flex items-center justify-center p-4">
  
  <div class="w-full max-w-md">
    <!-- Logo/Header -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-full mb-4">
        <span class="material-symbols-outlined text-white text-3xl">security</span>
      </div>
      <h1 class="text-3xl font-bold text-white mb-2">Verificación de Seguridad</h1>
      <p class="text-slate-300">Ingresa tu PIN de seguridad para continuar</p>
    </div>

    <!-- Card -->
    <div class="bg-white/10 backdrop-blur-lg rounded-2xl shadow-2xl p-8 border border-white/20">
      
      <?php if ($error): ?>
      <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 rounded-lg flex items-center gap-3">
        <span class="material-symbols-outlined text-red-400">error</span>
        <p class="text-red-200 text-sm"><?php echo $error; ?></p>
      </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-6">
          <label class="block text-sm font-medium text-slate-200 mb-2">
            <span class="material-symbols-outlined text-lg align-middle mr-1">pin</span>
            PIN de Seguridad
          </label>
          <input 
            type="password" 
            name="pin"
            maxlength="6" 
            pattern="[0-9]{6}"
            placeholder="000000"
            class="w-full px-4 py-3 bg-white/10 border border-white/20 rounded-lg text-white text-center text-2xl font-mono tracking-widest focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            required
            autofocus
          />
          <p class="text-xs text-slate-400 mt-2">Ingresa el PIN de 6 dígitos que configuraste</p>
        </div>

        <button 
          type="submit"
          class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-200 flex items-center justify-center gap-2"
        >
          <span class="material-symbols-outlined">lock_open</span>
          Verificar
        </button>
      </form>

      <div class="mt-6 pt-6 border-t border-white/10">
        <a href="login.php" class="text-sm text-slate-300 hover:text-white flex items-center justify-center gap-1">
          <span class="material-symbols-outlined text-sm">arrow_back</span>
          Volver al inicio de sesión
        </a>
      </div>
    </div>

    <!-- Info adicional -->
    <div class="mt-6 text-center">
      <p class="text-xs text-slate-400">
        ¿Problemas para acceder? Contacta al administrador
      </p>
    </div>
  </div>

  <script>
    // Auto-submit cuando se completan 6 dígitos
    const pinInput = document.querySelector('input[name="pin"]');
    pinInput.addEventListener('input', function(e) {
      if (this.value.length === 6) {
        setTimeout(() => {
          this.form.submit();
        }, 300);
      }
    });
  </script>

</body>
</html>
