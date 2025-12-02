<?php
session_start();
include 'funciones.php';

VerificarSiUsuarioYaInicioSesion();
// Conexión a la base de datos
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Opcional: puedes consultar la tabla usuarios si necesitas validar algo más
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

// --- INICIO DE LA LÓGICA DE PERMISOS ---
// Convertimos el rol a minúsculas para hacer la comparación insensible a mayúsculas/minúsculas.
 $rol_usuario = strtolower($Rol);
// --- FIN DE LA LÓGICA DE PERMISOS ---


?>

<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Crear Nuevo Usuario</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            primary: "#1152d4",
            "background-light": "#f6f6f8",
            "background-dark": "#101622",
            "sidebar-dark": "#161d2b",
            "sidebar-light": "#ffffff",
          },
          fontFamily: {
            display: ["Manrope", "sans-serif"],
          },
          borderRadius: {
            DEFAULT: "0.25rem",
            lg: "0.5rem",
            xl: "0.75rem",
            full: "9999px",
          },
        },
      },
    };
  </script>
<style>
    .material-symbols-outlined {
      font-variation-settings: "FILL" 0, "wght" 400, "GRAD" 0, "opsz" 24;
      font-size: 24px;
    }
    .material-symbols-outlined.fill {
        font-variation-settings: "FILL" 1;
    }
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 1000;
        transform: translateX(120%);
        transition: transform 0.3s ease-out;
    }
    .notification.show {
        transform: translateX(0);
    }
    .notification.success {
        background-color: #10b981;
        color: white;
    }
    .notification.error {
        background-color: #ef4444;
        color: white;
    }
    .notification.info {
        background-color: #3b82f6;
        color: white;
    }
    .field-error {
        border-color: #ef4444 !important;
    }
    .error-message {
        color: #ef4444;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    .loading {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 3px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: #fff;
        animation: spin 1s ease-in-out infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .profile-preview {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        margin-top: 1rem;
        border: 3px solid #e2e8f0;
    }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="relative flex h-auto min-h-screen w-full flex-col group/design-root overflow-x-hidden">
<div class="flex min-h-screen">
  <?php include 'menu_lateral.php'; ?>
<main class="flex flex-1 flex-col">
<header class="flex items-center justify-end whitespace-nowrap border-b border-solid border-gray-200 dark:border-b-[#232f48] px-10 py-3 sticky top-0 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm">
<div class="flex flex-1 justify-end gap-4 items-center">
<label class="flex flex-col min-w-40 !h-10 max-w-64">
<div class="flex w-full flex-1 items-stretch rounded-lg h-full">
<div class="text-[#92a4c9] flex border-none bg-white dark:bg-[#232f48] items-center justify-center pl-4 rounded-l-lg border-r-0">
<span class="material-symbols-outlined">search</span>
</div>
<input class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-0 border-none bg-white dark:bg-[#232f48] focus:border-none h-full placeholder:text-[#92a4c9] px-4 rounded-l-none border-l-0 pl-2 text-base font-normal leading-normal" placeholder="Search" value=""/>
</div>
</label>
<button class="flex max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 w-10 bg-white dark:bg-[#232f48] text-gray-800 dark:text-white gap-2 text-sm font-bold leading-normal tracking-[0.015em] min-w-0 px-2.5">
<span class="material-symbols-outlined">notifications</span>
</button>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Avatar de usuario" style='background-image: url("<?php echo $Perfil;?>");'></div>
</div>
</header>
<div class="flex-1 overflow-y-auto">
<div class="layout-container flex h-full grow flex-col">
<div class="flex flex-1 justify-center py-10 sm:py-12 md:py-16">
<div class="layout-content-container flex flex-col max-w-2xl flex-1 px-4 sm:px-6">
<div class="mt-8 sm:mt-10 flex flex-col items-center gap-6 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/50 p-6 sm:p-8">
<div class="flex w-full flex-col items-center gap-2">
<div class="flex flex-col items-center gap-6 rounded-lg border-2 border-dashed border-slate-300 dark:border-slate-700 px-6 py-10 w-full">
<div class="flex max-w-[480px] flex-col items-center gap-2 text-center">
<p class="text-slate-900 dark:text-white text-base font-bold leading-tight tracking-[-0.015em]">Subir foto de perfil (Opcional)</p>
<p class="text-slate-600 dark:text-slate-400 text-sm font-normal leading-normal">Arrastra y suelta una imagen o haz clic para seleccionarla.</p>
</div>
<input type="file" id="profileImage" accept="image/*" name="FotoPerfi" style="display: none;">
<button id="uploadButton" class="flex min-w-[84px] max-w-[480px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 px-5 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white text-sm font-bold leading-normal tracking-[0.015em] hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
<span class="truncate">Subir imagen</span>
</button>
<div id="imagePreview" class="mt-4"></div>
</div>
</div>
<form id="createUserForm" class="w-full" enctype="multipart/form-data">
<div class="flex w-full flex-col sm:flex-row flex-wrap items-end gap-4">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-slate-900 dark:text-white text-sm font-medium leading-normal pb-2">Nombre</p>
<input id="nombre" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-transparent h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 text-base font-normal leading-normal" placeholder="Ingrese el nombre" value=""/>
<div id="nombreError" class="error-message"></div>
</label>
<label class="flex flex-col min-w-40 flex-1">
<p class="text-slate-900 dark:text-white text-sm font-medium leading-normal pb-2">Apellido</p>
<input id="apellido" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-transparent h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 text-base font-normal leading-normal" placeholder="Ingrese el apellido" value=""/>
<div id="apellidoError" class="error-message"></div>
</label>
</div>
<!-- CAMPO CELULAR -->
<div class="flex w-full flex-wrap items-end gap-4">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-slate-900 dark:text-white text-sm font-medium leading-normal pb-2">Celular</p>
<input id="celular" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-transparent h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 text-base font-normal leading-normal" placeholder="Ingrese su número de celular" value=""/>
<div id="celularError" class="error-message"></div>
</label>
</div>
<!-- CAMPO CARGO AÑADIDO -->
<div class="flex w-full flex-wrap items-end gap-4">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-slate-900 dark:text-white text-sm font-medium leading-normal pb-2">Cargo</p>
<input id="cargo" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-transparent h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 text-base font-normal leading-normal" placeholder="Ej: Vendedor, Gerente, etc." value=""/>
<div id="cargoError" class="error-message"></div>
</label>
</div>
<!-- CAMPO FECHA DE NACIMIENTO -->
<div class="flex w-full flex-wrap items-end gap-4">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-slate-900 dark:text-white text-sm font-medium leading-normal pb-2">Fecha de Nacimiento</p>
<input id="fechaNacimiento" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-transparent h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 text-base font-normal leading-normal" type="date" value=""/>
<div id="fechaNacimientoError" class="error-message"></div>
</label>
</div>
<div class="flex w-full flex-wrap items-end gap-4">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-slate-900 dark:text-white text-sm font-medium leading-normal pb-2">Email</p>
<input id="email" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-transparent h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 text-base font-normal leading-normal" placeholder="ejemplo@correo.com" type="email" value=""/>
<div id="emailError" class="error-message"></div>
</label>
</div>
<!-- CAMPO USUARIO -->
<div class="flex w-full flex-wrap items-end gap-4">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-slate-900 dark:text-white text-sm font-medium leading-normal pb-2">Usuario</p>
<input id="usuario" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-transparent h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 text-base font-normal leading-normal" placeholder="Ingrese un nombre de usuario" value=""/>
<div id="usuarioError" class="error-message"></div>
</label>
</div>
<div class="flex w-full flex-wrap items-end gap-4">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-slate-900 dark:text-white text-sm font-medium leading-normal pb-2">Contraseña</p>
<div class="relative flex w-full flex-1 items-stretch rounded-lg">
<input id="password" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-transparent h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 text-base font-normal leading-normal pr-12" placeholder="Ingrese una contraseña segura" type="password" value=""/>
<div id="togglePassword" class="absolute right-0 top-0 h-full text-slate-400 dark:text-slate-500 flex items-center justify-center px-3 cursor-pointer">
<span class="material-symbols-outlined">visibility</span>
</div>
</div>
<div id="passwordError" class="error-message"></div>
</label>
</div>
<div class="flex w-full flex-wrap items-end gap-4">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-slate-900 dark:text-white text-sm font-medium leading-normal pb-2">Confirmar Contraseña</p>
<input id="confirmPassword" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-transparent h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 text-base font-normal leading-normal" placeholder="Repita la contraseña" type="password" value=""/>
<div id="confirmPasswordError" class="error-message"></div>
</label>
</div>
<div class="flex w-full flex-wrap items-end gap-4">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-slate-900 dark:text-white text-sm font-medium leading-normal pb-2">Rol de Usuario</p>
<div class="relative w-full">
<select id="rol" class="form-select appearance-none flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-slate-900 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-slate-300 dark:border-slate-700 bg-transparent h-12 placeholder:text-slate-400 dark:placeholder:text-slate-500 px-3 text-base font-normal leading-normal">
<option value="Admin">Administrador</option>
<option value="Cajero/Gerente">Cajero/Gerente</option>
</select>
<div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 dark:text-slate-500">
<span class="material-symbols-outlined">expand_more</span>
</div>
</div>
<div id="rolError" class="error-message"></div>
</label>
</div>
<div class="flex w-full flex-col-reverse sm:flex-row items-center justify-end gap-3 pt-4">
<button type="button" id="cancelButton" class="flex w-full sm:w-auto min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-11 px-6 bg-transparent text-slate-900 dark:text-white text-base font-bold leading-normal tracking-[0.015em] hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
<span class="truncate">Cancelar</span>
</button>
<button type="submit" id="createButton" class="flex w-full sm:w-auto min-w-[84px] cursor-pointer items-center justify-center overflow-hidden rounded-lg h-11 px-8 bg-primary text-white text-base font-bold leading-normal tracking-[0.015em] hover:bg-primary/90 transition-colors">
<span class="truncate">Crear Usuario</span>
</button>
</div>
</form>
</div>
</div>
</div>
</div>
</main>
</div>
</div>

<!-- Notification container -->
<div id="notification" class="notification">
    <span id="notificationIcon" class="material-symbols-outlined"></span>
    <div>
        <p id="notificationTitle" class="font-semibold"></p>
        <p id="notificationMessage" class="text-sm"></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const form = document.getElementById('createUserForm');
    const nombreInput = document.getElementById('nombre');
    const apellidoInput = document.getElementById('apellido');
    const celularInput = document.getElementById('celular');
    const cargoInput = document.getElementById('cargo'); // AÑADIDO
    const fechaNacimientoInput = document.getElementById('fechaNacimiento');
    const emailInput = document.getElementById('email');
    const usuarioInput = document.getElementById('usuario');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const rolSelect = document.getElementById('rol');
    const profileImageInput = document.getElementById('profileImage');
    const uploadButton = document.getElementById('uploadButton');
    const imagePreview = document.getElementById('imagePreview');
    const togglePassword = document.getElementById('togglePassword');
    const cancelButton = document.getElementById('cancelButton');
    const createButton = document.getElementById('createButton');
    
    // Error elements
    const nombreError = document.getElementById('nombreError');
    const apellidoError = document.getElementById('apellidoError');
    const celularError = document.getElementById('celularError');
    const cargoError = document.getElementById('cargoError'); // AÑADIDO
    const fechaNacimientoError = document.getElementById('fechaNacimientoError');
    const emailError = document.getElementById('emailError');
    const usuarioError = document.getElementById('usuarioError');
    const passwordError = document.getElementById('passwordError');
    const confirmPasswordError = document.getElementById('confirmPasswordError');
    const rolError = document.getElementById('rolError');
    
    // Notification elements
    const notification = document.getElementById('notification');
    const notificationIcon = document.getElementById('notificationIcon');
    const notificationTitle = document.getElementById('notificationTitle');
    const notificationMessage = document.getElementById('notificationMessage');
    
    // Profile image handling
    let profileImageUrl = null;
    
    uploadButton.addEventListener('click', function() {
        profileImageInput.click();
    });
    
    profileImageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            profileImageUrl = URL.createObjectURL(file);
            imagePreview.innerHTML = `<img src="${profileImageUrl}" alt="Profile Preview" class="profile-preview">`;
        } else {
            imagePreview.innerHTML = '';
            profileImageUrl = null;
        }
    });
    
    // Toggle password visibility
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('.material-symbols-outlined').textContent = type === 'password' ? 'visibility' : 'visibility_off';
    });
    
    // Form validation
    function validateForm() {
        let isValid = true;
        
        // Reset errors
        clearErrors();
        
        // Validate nombre
        if (!nombreInput.value.trim()) {
            showError(nombreInput, nombreError, 'El nombre es obligatorio');
            isValid = false;
        }
        
        // Validate apellido
        if (!apellidoInput.value.trim()) {
            showError(apellidoInput, apellidoError, 'El apellido es obligatorio');
            isValid = false;
        }
        
        // Validate celular
        if (!celularInput.value.trim()) {
            showError(celularInput, celularError, 'El número de celular es obligatorio');
            isValid = false;
        }

        // Validate cargo
        if (!cargoInput.value.trim()) { // AÑADIDO
            showError(cargoInput, cargoError, 'El cargo es obligatorio.');
            isValid = false;
        }

        // Validate fecha de nacimiento
        if (!fechaNacimientoInput.value) {
            showError(fechaNacimientoInput, fechaNacimientoError, 'La fecha de nacimiento es obligatoria.');
            isValid = false;
        }
        
        // Validate email
        if (!emailInput.value.trim()) {
            showError(emailInput, emailError, 'El email es obligatorio');
            isValid = false;
        } else if (!isValidEmail(emailInput.value)) {
            showError(emailInput, emailError, 'Ingrese un email válido');
            isValid = false;
        }
        
        // Validate usuario
        if (!usuarioInput.value.trim()) {
            showError(usuarioInput, usuarioError, 'El nombre de usuario es obligatorio');
            isValid = false;
        } else if (usuarioInput.value.length < 3) {
            showError(usuarioInput, usuarioError, 'El nombre de usuario debe tener al menos 3 caracteres');
            isValid = false;
        }
        
        // Validate password
        if (!passwordInput.value) {
            showError(passwordInput, passwordError, 'La contraseña es obligatoria');
            isValid = false;
        } else if (passwordInput.value.length < 6) {
            showError(passwordInput, passwordError, 'La contraseña debe tener al menos 6 caracteres');
            isValid = false;
        }
        
        // Validate confirm password
        if (!confirmPasswordInput.value) {
            showError(confirmPasswordInput, confirmPasswordError, 'Debe confirmar la contraseña');
            isValid = false;
        } else if (passwordInput.value !== confirmPasswordInput.value) {
            showError(confirmPasswordInput, confirmPasswordError, 'Las contraseñas no coinciden');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showError(input, errorElement, message) {
        input.classList.add('field-error');
        errorElement.textContent = message;
    }
    
    function clearErrors() {
        // AÑADIDO: cargoInput y cargoError a las listas
        const inputs = [nombreInput, apellidoInput, celularInput, cargoInput, fechaNacimientoInput, emailInput, usuarioInput, passwordInput, confirmPasswordInput, rolSelect];
        const errorElements = [nombreError, apellidoError, celularError, cargoError, fechaNacimientoError, emailError, usuarioError, passwordError, confirmPasswordError, rolError];
        
        inputs.forEach(input => input.classList.remove('field-error'));
        errorElements.forEach(error => error.textContent = '');
    }
    
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Show notification
    function showNotification(type, title, message) {
        notification.className = `notification ${type}`;
        notificationTitle.textContent = title;
        notificationMessage.textContent = message;
        
        // Set icon based on type
        if (type === 'success') {
            notificationIcon.textContent = 'check_circle';
        } else if (type === 'error') {
            notificationIcon.textContent = 'error';
        } else if (type === 'info') {
            notificationIcon.textContent = 'info';
        }
        
        // Show notification
        notification.classList.add('show');
        
        // Hide after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
        }, 5000);
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            // Show loading state
            createButton.disabled = true;
            createButton.innerHTML = '<span class="loading"></span> Creando...';
            
            // Crear FormData para poder enviar archivos
            const formData = new FormData();
            formData.append('nombre', nombreInput.value.trim());
            formData.append('apellido', apellidoInput.value.trim());
            formData.append('celular', celularInput.value.trim());
            formData.append('cargo', cargoInput.value.trim()); // AÑADIDO
            formData.append('fecha_nacimiento', fechaNacimientoInput.value);
            formData.append('email', emailInput.value.trim());
            formData.append('usuario', usuarioInput.value.trim());
            formData.append('contraseña', passwordInput.value);
            formData.append('rol', rolSelect.value);
            
            // Añadir el archivo de imagen si se ha seleccionado uno
            if (profileImageInput.files && profileImageInput.files[0]) {
                formData.append('foto', profileImageInput.files[0]);
            }
            
            // API call to save user to database
            fetch('guardar_usuario.php', {
                method: 'POST',
                body: formData 
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showNotification('success', 'Usuario Creado', 'El nuevo usuario ha sido creado exitosamente.');
                    
                    // Reset form
                    form.reset();
                    imagePreview.innerHTML = '';
                    profileImageUrl = null;
                    
                    // Redirect to users list after a delay
                    setTimeout(() => {
                        window.location.href = 'index.php'; // Redirigir a la lista de usuarios
                    }, 2000);
                } else {
                    showNotification('error', 'Error', data.message || 'No se pudo crear el usuario. Inténtelo de nuevo.');
                }
            })
            .catch(error => {
                showNotification('error', 'Error de Conexión', 'No se pudo conectar con el servidor. Inténtelo de nuevo.');
                console.error('Error:', error);
            })
            .finally(() => {
                createButton.disabled = false;
                createButton.innerHTML = '<span class="truncate">Crear Usuario</span>';
            });
        }
    });
    
    // Cancel button
    cancelButton.addEventListener('click', function() {
        // Redirect to users list page
        window.location.href = 'usuarios.php';
    });
});
</script>

</body></html>