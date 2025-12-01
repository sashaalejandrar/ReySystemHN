<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Procesar el formulario
$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $conexion->real_escape_string($_POST["usuario"]);
    $clave = hash("sha256", $_POST["clave"]); // Encriptar con SHA-256

    $sql = "SELECT * FROM usuarios WHERE usuario = '$usuario' AND clave = '$clave'";
    $resultado = $conexion->query($sql);
    
    if ($resultado->num_rows == 1) {
        $row = $resultado->fetch_assoc();
        $id = $row['Id'];
        $rol = $row['rol'] ?? 'usuario';
        $perfil = $row['perfil'] ?? 'default';
        $nombre = $row['Nombre'] ?? $usuario;
        
        // Verificar métodos de autenticación adicionales
        require_once 'security_keys_helper.php';
        
        // Verificar si tiene 2FA habilitado
        $stmt_2fa = $conexion->prepare("SELECT enabled, secret FROM autenticacion_2fa WHERE idUsuario = ?");
        $stmt_2fa->bind_param("s", $usuario);
        $stmt_2fa->execute();
        $resultado_2fa = $stmt_2fa->get_result();
        $has2FA = false;
        
        if ($resultado_2fa->num_rows > 0) {
            $row_2fa = $resultado_2fa->fetch_assoc();
            if ($row_2fa['enabled'] == 1) {
                $has2FA = true;
                $_SESSION['temp_2fa_secret'] = $row_2fa['secret'];
            }
        }
        
        $hasSecurityKeys = hasSecurityKeys($conexion, $usuario);
        $hasPin = hasPinEnabled($conexion, $usuario);
        $isTrusted = isTrustedDevice($conexion, $usuario);
        
        // Si tiene CUALQUIER método de seguridad y NO es dispositivo de confianza
        if (($hasSecurityKeys || $hasPin || $has2FA) && !$isTrusted) {
            // Guardar datos en sesión temporal y redirigir a página de verificación unificada
            $_SESSION['temp_user_id'] = $id;
            $_SESSION['temp_usuario'] = $usuario;
            $_SESSION['temp_rol'] = $rol;
            $_SESSION['temp_perfil'] = $perfil;
            $_SESSION['temp_nombre'] = $nombre;
            $_SESSION['temp_security_check'] = true;
            
            header("Location: verify_login.php");
            exit();
        }
        
        // Si no tiene métodos de seguridad o es dispositivo de confianza, login directo
        $_SESSION["usuario"] = $usuario;
        $_SESSION["user_id"] = $id;
        $_SESSION['usuario_id'] = $id;
        $_SESSION['rol'] = $rol;
        $_SESSION['perfil'] = $perfil;
        $_SESSION['nombre'] = $nombre;
        
        header("Location: index.php");
        exit();
    } else {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Iniciar Sesión</title>
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
          borderRadius: {
            "DEFAULT": "0.25rem",
            "lg": "0.5rem",
            "xl": "0.75rem",
            "full": "9999px"
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
  </style>
<?php include "pwa-head.php"; ?>
<style>
.material-symbols-outlined {
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}

/* Animaciones suaves */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

@keyframes pulse {
  0%, 100% {
    box-shadow: 0 0 20px rgba(17, 82, 212, 0.2);
  }
  50% {
    box-shadow: 0 0 40px rgba(17, 82, 212, 0.4);
  }
}

.login-container {
  animation: fadeInUp 0.8s ease-out;
}

.logo-container {
  animation: fadeInUp 0.6s ease-out, float 3s ease-in-out infinite;
}

.input-field {
  transition: all 0.3s ease;
}

.input-field:focus {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(17, 82, 212, 0.2);
}

.btn-login {
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.btn-login::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
  transition: left 0.5s;
}

.btn-login:hover::before {
  left: 100%;
}

.btn-login:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(17, 82, 212, 0.4);
}

.form-card {
  animation: fadeInUp 1s ease-out 0.3s both;
}
</style>
</head>
<body class="font-display">
  <div class="relative flex h-auto min-h-screen w-full flex-col items-center justify-center bg-background-light dark:bg-background-dark p-6">
    <div class="login-container flex w-full max-w-md flex-col items-center">
      <div class="logo-container mb-8 flex items-center gap-3">
        <span class="material-symbols-outlined text-primary text-4xl">payments</span>
        <span class="text-2xl font-bold text-slate-800 dark:text-white">ReySystemApp</span>
      </div>
      
      <div class="form-card w-full rounded-xl border border-slate-200/80 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/50 sm:p-8">
        <div class="text-center">
          <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">Bienvenido de nuevo</h1>
          <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Inicia sesión en tu cuenta para continuar</p>
        </div>

        <?php if ($error): ?>
          <div class="mt-4 text-center text-sm text-red-600 dark:text-red-400 font-medium bg-red-50 dark:bg-red-900/20 p-3 rounded-lg">
            ⚠️ <?= $error ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="mt-8 flex flex-col gap-5">
          <div class="flex flex-col gap-1.5">
            <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="usuario">Usuario</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <span class="material-symbols-outlined text-slate-400 dark:text-slate-500">person</span>
              </div>
              <input name="usuario" id="usuario" type="text" required placeholder="username"
                class="input-field form-input block w-full rounded-lg border border-slate-300 bg-slate-50/50 py-3 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-background-dark dark:text-white dark:placeholder:text-slate-500 dark:focus:border-primary dark:focus:ring-primary" />
            </div>
          </div>

          <div class="flex flex-col gap-1.5">
            <label class="text-sm font-medium text-slate-700 dark:text-slate-300" for="clave">Contraseña</label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <span class="material-symbols-outlined text-slate-400 dark:text-slate-500">lock</span>
              </div>
              <input name="clave" id="clave" type="password" required placeholder="Introduce tu contraseña"
                class="input-field form-input block w-full rounded-lg border border-slate-300 bg-slate-50/50 py-3 pl-10 pr-10 text-sm text-slate-900 placeholder:text-slate-400 focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-background-dark dark:text-white dark:placeholder:text-slate-500 dark:focus:border-primary dark:focus:ring-primary" />
            </div>
          </div>

          <div class="flex items-center justify-end">
            <a class="text-sm font-medium text-primary hover:underline" href="#">¿Olvidaste tu contraseña?</a>
          </div>

          <button type="submit"
            class="btn-login flex w-full items-center justify-center rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary/90 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:pointer-events-none disabled:opacity-50">
            Iniciar Sesión
          </button>
        </form>
      </div>
      
      <div class="mt-6 text-center text-sm text-slate-600 dark:text-slate-400">
        <p>¿No tienes una cuenta? <a class="font-semibold text-primary hover:underline" href="#">Regístrate</a></p>
      </div>
    </div>
  </div>

  <script>
    // Efecto de brillo en inputs al escribir
    document.querySelectorAll('.input-field').forEach(input => {
      input.addEventListener('input', function() {
        this.style.borderColor = '#1152d4';
        setTimeout(() => {
          this.style.borderColor = '';
        }, 300);
      });
    });
  </script>
</body>
</html>
