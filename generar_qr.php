<?php
session_start();
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
    $Perfil = $row['Perfil'];
}

$rol_usuario = strtolower($Rol);

// Generar nuevo token si se solicita
if (isset($_POST['generar'])) {
    $token = bin2hex(random_bytes(32));
    $stmt = $conexion->prepare("INSERT INTO tokens_registro (token) VALUES (?)");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->close();
    
    $mensaje_exito = "Token generado exitosamente";
}

// Obtener tokens existentes
$tokens = $conexion->query("SELECT * FROM tokens_registro ORDER BY fecha_creacion DESC LIMIT 20");
?>

<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Generar QR - Rey System APP</title>
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
    <script src="nova_rey.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
    <!-- SideNavBar -->
    <?php include 'menu_lateral.php'; ?>
    
    <!-- Main Content -->
    <main class="flex-1 flex flex-col">
        <div class="flex-1 p-6 lg:p-10">
            <!-- Page Heading -->
            <div class="flex flex-wrap justify-between gap-4 mb-8">
                <div class="flex flex-col gap-2">
                    <h1 class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">
                        📱 Registro por QR
                    </h1>
                    <p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">
                        Genera códigos QR para que nuevos clientes se registren y obtengan 10 puntos de bienvenida
                    </p>
                </div>
                <div class="flex gap-3">
                    <button onclick="window.location.href='puntos_fidelidad.php'" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        <span class="material-symbols-outlined">arrow_back</span>
                        Volver a Puntos
                    </button>
                </div>
            </div>

            <?php if (isset($mensaje_exito)): ?>
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-6">
                <p class="text-green-800 dark:text-green-300">✅ <?php echo $mensaje_exito; ?></p>
            </div>
            <?php endif; ?>

            <!-- Generar Nuevo QR -->
            <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">🎯 Generar Nuevo QR</h2>
                <p class="text-gray-500 dark:text-gray-400 mb-6">
                    Crea un código QR único para que los clientes se registren escaneándolo con su celular
                </p>
                <form method="POST">
                    <button type="submit" name="generar" class="flex items-center gap-2 px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors">
                        <span class="material-symbols-outlined">qr_code_2</span>
                        Generar Nuevo QR
                    </button>
                </form>
            </div>

            <!-- Lista de QR Generados -->
            <div class="bg-white dark:bg-[#192233] rounded-xl border border-gray-200 dark:border-[#324467] p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">📋 QR Generados</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php while($token = $tokens->fetch_assoc()): ?>
                    <div class="bg-gray-50 dark:bg-[#0d1420] rounded-lg p-4 border border-gray-200 dark:border-[#324467]">
                        <!-- QR Code -->
                        <div class="bg-white p-4 rounded-lg mb-4 flex items-center justify-center">
                            <img src="generar_qr_imagen.php?token=<?php echo $token['token']; ?>" 
                                 alt="QR Code" 
                                 class="max-w-full"
                                 id="qr-img-<?php echo $token['id']; ?>">
                        </div>
                        
                        <!-- Info -->
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 dark:text-gray-400">Estado:</span>
                                <?php if ($token['usado']): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                    <span class="material-symbols-outlined text-xs">check_circle</span>
                                    Usado
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                    <span class="material-symbols-outlined text-xs">pending</span>
                                    Disponible
                                </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($token['usado']): ?>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Cliente:</span>
                                <p class="font-semibold text-gray-900 dark:text-white"><?php echo htmlspecialchars($token['cliente_registrado']); ?></p>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Usado:</span>
                                <p class="text-gray-900 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($token['fecha_uso'])); ?></p>
                            </div>
                            <?php else: ?>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Creado:</span>
                                <p class="text-gray-900 dark:text-white"><?php echo date('d/m/Y H:i', strtotime($token['fecha_creacion'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Acciones -->
                        <div class="mt-4 flex gap-2">
                            <button onclick="descargarQR('qr-<?php echo $token['id']; ?>', 'QR-<?php echo $token['id']; ?>')" 
                                    class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition-colors">
                                <span class="material-symbols-outlined text-sm align-middle">download</span>
                                Descargar
                            </button>
                            <button onclick="copiarURL('<?php echo $token['token']; ?>')" 
                                    class="flex-1 px-3 py-2 bg-purple-600 text-white rounded-lg text-sm font-semibold hover:bg-purple-700 transition-colors">
                                <span class="material-symbols-outlined text-sm align-middle">link</span>
                                Copiar URL
                            </button>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="flex flex-wrap items-center justify-between gap-4 px-6 py-4 border-t border-gray-200 dark:border-white/10 text-sm">
            <p class="text-gray-500 dark:text-[#92a4c9]">Sistema QR v1.0</p>
            <a class="text-primary hover:underline" href="puntos_fidelidad.php">← Volver a Puntos</a>
        </footer>
    </main>
</div>
</div>

<script>
function descargarQR(imgId, filename) {
    const img = document.getElementById('qr-img-' + imgId.replace('qr-', ''));
    const link = document.createElement('a');
    link.download = filename + '.png';
    link.href = img.src;
    link.click();
}

function copiarURL(token) {
    const url = `http://localhost/ReySystemDemo/registro_cliente.php?token=${token}`;
    
    // Intentar copiar con clipboard API
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            Swal.fire({
                icon: 'success',
                title: '¡Copiado!',
                text: 'URL copiada al portapapeles',
                timer: 2000,
                showConfirmButton: false
            });
        }).catch(err => {
            // Fallback si falla
            copiarFallback(url);
        });
    } else {
        // Fallback para navegadores antiguos
        copiarFallback(url);
    }
}

function copiarFallback(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
        document.execCommand('copy');
        Swal.fire({
            icon: 'success',
            title: '¡Copiado!',
            text: 'URL copiada al portapapeles',
            timer: 2000,
            showConfirmButton: false
        });
    } catch (err) {
        Swal.fire({
            icon: 'info',
            title: 'URL del QR',
            html: `<input type="text" value="${text}" class="w-full px-4 py-2 border rounded" onclick="this.select()">`,
            text: 'Selecciona y copia manualmente',
            confirmButtonText: 'Cerrar'
        });
    }
    
    document.body.removeChild(textarea);
}
</script>
</body>
</html>
<?php $conexion->close(); ?>
