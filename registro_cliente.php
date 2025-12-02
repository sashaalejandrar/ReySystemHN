<?php
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

if ($conexion->connect_error) {
    die("Error de conexión");
}

// Obtener y validar token
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Token inválido");
}

// Verificar que el token existe y no ha sido usado
$stmt = $conexion->prepare("SELECT * FROM tokens_registro WHERE token = ? AND usado = 0");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $token_invalido = true;
} else {
    $token_data = $result->fetch_assoc();
    $token_invalido = false;
}
$stmt->close();
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Registro de Cliente - BODEGA SILOE</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet"/>
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
                    },
                },
            },
        }
    </script>
    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-900 via-purple-900 to-indigo-900 font-display min-h-screen flex items-center justify-center p-4">

<?php if ($token_invalido): ?>
    <!-- Token Inválido -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md w-full text-center">
        <span class="material-symbols-outlined text-red-600 text-6xl mb-4">error</span>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">QR Inválido o Ya Usado</h1>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            Este código QR ya fue utilizado o no es válido. Por favor, solicita un nuevo código en la tienda.
        </p>
    </div>
<?php else: ?>
    <!-- Formulario de Registro -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md w-full">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="material-symbols-outlined text-white text-4xl">person_add</span>
            </div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-2">
                ¡Bienvenido!
            </h1>
            <p class="text-gray-600 dark:text-gray-300">
                Regístrate y obtén <strong class="text-green-600">10 puntos gratis</strong>
            </p>
        </div>

        <!-- Formulario -->
        <form id="formRegistro" class="space-y-6">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <!-- Nombre -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <span class="material-symbols-outlined text-sm align-middle">person</span>
                    Nombre Completo *
                </label>
                <input type="text" name="nombre" required
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="Juan Pérez">
            </div>

            <!-- Celular -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <span class="material-symbols-outlined text-sm align-middle">phone</span>
                    Celular *
                </label>
                <input type="tel" name="celular" required pattern="[0-9]{8}"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"
                       placeholder="98765432">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">8 dígitos</p>
            </div>

            <!-- Dirección -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                    <span class="material-symbols-outlined text-sm align-middle">location_on</span>
                    Dirección *
                </label>
                <textarea name="direccion" required rows="2"
                          class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary focus:border-transparent"
                          placeholder="Barrio, calle, número de casa"></textarea>
            </div>

            <!-- Beneficios -->
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
                <h3 class="font-bold text-green-800 dark:text-green-300 mb-2">🎁 Beneficios al registrarte:</h3>
                <ul class="text-sm text-green-700 dark:text-green-400 space-y-1">
                    <li>✓ 10 puntos de bienvenida</li>
                    <li>✓ Acumula puntos en cada compra</li>
                    <li>✓ Canjea puntos por descuentos</li>
                    <li>✓ Niveles de membresía exclusivos</li>
                </ul>
            </div>

            <!-- Botón -->
            <button type="submit" id="btnRegistro"
                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-4 rounded-lg font-bold text-lg hover:from-blue-700 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl">
                <span class="material-symbols-outlined align-middle">how_to_reg</span>
                Registrarme Ahora
            </button>
        </form>

        <p class="text-xs text-center text-gray-500 dark:text-gray-400 mt-6">
            Al registrarte aceptas nuestros términos y condiciones
        </p>
    </div>
<?php endif; ?>

<script>
document.getElementById('formRegistro')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const btn = document.getElementById('btnRegistro');
    btn.disabled = true;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin align-middle">progress_activity</span> Registrando...';
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('procesar_registro_qr.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Mostrar éxito
            document.body.innerHTML = `
                <div class="min-h-screen flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl p-8 max-w-md w-full text-center">
                        <div class="bg-green-100 dark:bg-green-900/30 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-green-600 text-5xl">check_circle</span>
                        </div>
                        <h1 class="text-3xl font-black text-gray-900 dark:text-white mb-4">
                            ¡Registro Exitoso!
                        </h1>
                        <p class="text-gray-600 dark:text-gray-300 mb-6">
                            Bienvenido <strong>${data.cliente}</strong><br>
                            Has recibido <strong class="text-green-600">${data.puntos} puntos</strong> de bienvenida
                        </p>
                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
                            <p class="text-sm text-blue-800 dark:text-blue-300">
                                🎉 Ya puedes acumular puntos en cada compra y canjearlos por descuentos
                            </p>
                        </div>
                    </div>
                </div>
            `;
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<span class="material-symbols-outlined align-middle">how_to_reg</span> Registrarme Ahora';
        }
    } catch (error) {
        alert('Error al procesar el registro');
        btn.disabled = false;
        btn.innerHTML = '<span class="material-symbols-outlined align-middle">how_to_reg</span> Registrarme Ahora';
    }
});
</script>
</body>
</html>
<?php $conexion->close(); ?>
