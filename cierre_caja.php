<?php
session_start();
include 'funciones.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);
VerificarSiUsuarioYaInicioSesion();
date_default_timezone_set('America/Tegucigalpa');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Verificar conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Fecha de hoy
$fecha_hoy = date('Y-m-d');

// Verificar el estado de la caja del día de hoy
$consulta_caja = $conexion->query("SELECT * FROM caja WHERE DATE(Fecha) = '$fecha_hoy' ORDER BY id DESC LIMIT 1");

if (!$consulta_caja || $consulta_caja->num_rows == 0) {
    $_SESSION['mensaje_caja'] = "No hay apertura de caja para hoy. Debe abrir la caja antes de realizar el cierre.";
    $_SESSION['tipo_mensaje_caja'] = "warning";
    header("Location: index.php");
    exit();
}

$caja_hoy = $consulta_caja->fetch_assoc();
$estado_caja = $caja_hoy['Estado'];
$fondo_caja = floatval($caja_hoy['monto_inicial']); 
$id_caja = $caja_hoy['Id'];

if ($estado_caja === 'Cerrada') {
    $_SESSION['mensaje_caja'] = "La caja ya está cerrada hoy. No se puede realizar cierre.";
    $_SESSION['tipo_mensaje_caja'] = "error";
    header("Location: index.php");
    exit();
}

if ($estado_caja !== 'Abierta') {
    $_SESSION['mensaje_caja'] = "La caja debe estar abierta para realizar el cierre.";
    $_SESSION['tipo_mensaje_caja'] = "error";
    header("Location: index.php");
    exit();
}

// Obtener total de ventas del día directamente de la tabla ventas
$consulta_ventas = $conexion->query("SELECT COALESCE(SUM(Total), 0) as total_ventas FROM ventas WHERE DATE(Fecha_Venta) = '$fecha_hoy'");
$row_ventas = $consulta_ventas->fetch_assoc();
$total_ventas = floatval($row_ventas['total_ventas']);

// Obtener total de egresos de la tabla caja
$total_egresos = floatval($caja_hoy['total_egresos']);

// Obtener el último arqueo del día (si existe) para mostrar sobrante/faltante
$consulta_arqueo = $conexion->query("SELECT Total FROM arqueo_caja WHERE DATE(Fecha) = '$fecha_hoy' ORDER BY id DESC LIMIT 1");
$sobrante_arqueo = 0;
$hay_arqueo = false;
if ($consulta_arqueo && $consulta_arqueo->num_rows > 0) {
    $row_arqueo = $consulta_arqueo->fetch_assoc();
    $total_arqueo = floatval($row_arqueo['Total']);
    // Calcular el efectivo esperado en el momento del arqueo
    $efectivo_esperado_arqueo = $fondo_caja + $total_ventas - $total_egresos;
    // El sobrante es la diferencia entre lo contado en el arqueo y lo esperado
    $sobrante_arqueo = $total_arqueo - $efectivo_esperado_arqueo;
    $hay_arqueo = true;
}

// Total esperado en caja (incluye sobrante/faltante del arqueo si existe)
// Si hubo sobrante en el arqueo, hay más dinero en caja; si hubo faltante, hay menos
$efectivo_esperado = $fondo_caja + $total_ventas - $total_egresos + $sobrante_arqueo;

// Obtener datos del usuario
$resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
$Usuario = "";
$Nombre_Completo = "";
$Rol = "";
$Perfil = "";

while($row = $resultado->fetch_assoc()){
    $Rol = $row['Rol'];
    $Usuario = $row['Usuario'];
    $Nombre = $row['Nombre'];
    $Apellido = $row['Apellido'];
    $Nombre_Completo = $Nombre." ".$Apellido;
    $Perfil = $row['Perfil'];
}
$rol_usuario = strtolower($Rol);

// Procesar el formulario cuando se envía
$mensaje = "";
$tipo_mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_cierre'])) {
    $x500 = intval($_POST['x500']);
    $x200 = intval($_POST['x200']);
    $x100 = intval($_POST['x100']);
    $x50 = intval($_POST['x50']);
    $x20 = intval($_POST['x20']);
    $x10 = intval($_POST['x10']);
    $x5 = intval($_POST['x5']);
    $x2 = intval($_POST['x2']);
    $x1 = intval($_POST['x1']);
    
    // Calcular el total contado
    $efectivo_contado = ($x500 * 500) + ($x200 * 200) + ($x100 * 100) + ($x50 * 50) + 
                        ($x20 * 20) + ($x10 * 10) + ($x5 * 5) + ($x2 * 2) + ($x1 * 1);
    
    // Calcular diferencia
    $diferencia = $efectivo_contado - $efectivo_esperado;
    
    // Nota justificativa
    $nota_justi = "Cierre de caja - " . $Nombre_Completo;
    $nota_justi .= "\nFondo: L." . number_format($fondo_caja, 2);
    $nota_justi .= "\nVentas: L." . number_format($total_ventas, 2);
    $nota_justi .= "\nEgresos: L." . number_format($total_egresos, 2);
    $nota_justi .= "\nDiferencia: L." . number_format($diferencia, 2);
    
    // Agregar razón del faltante si se proporcionó (vía OTP)
    if (isset($_POST['razon_faltante']) && !empty($_POST['razon_faltante'])) {
        $nota_justi .= "\nRazón faltante: " . $_POST['razon_faltante'];
        $nota_justi .= "\nAutorizado vía OTP";
    }
    
    // Obtener fecha actual
    $fecha = date('Y-m-d');
    
    // Calcular sobrante y faltante
    $sobrante = ($diferencia > 0) ? $diferencia : 0;
    $faltante = ($diferencia < 0) ? abs($diferencia) : 0;
    
    $conexion->begin_transaction();
    
    try {
        // 1. Insertar en cierre_caja
        $sql = "INSERT INTO cierre_caja (X1, X2, X5, X10, X20, X50, X100, X200, X500, Total, Nota_Justifi, Efectivo, Transferencia, Tarjeta, Fecha, usuario, sobrante, faltante) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $transferencia = 0;
        $tarjeta = 0;
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iiiiiiiiisd ddssdd", $x1, $x2, $x5, $x10, $x20, $x50, $x100, $x200, $x500, 
                          $efectivo_contado, $nota_justi, $efectivo_contado, $transferencia, $tarjeta, $fecha, $Usuario, $sobrante, $faltante);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar cierre_caja: " . $stmt->error);
        }
        $stmt->close();
        
        // 2. Actualizar la tabla caja con el conteo y estado cerrada
        $sql_update = "UPDATE caja SET 
                       x1 = ?, x2 = ?, x5 = ?, x10 = ?, x20 = ?, x50 = ?, x100 = ?, x200 = ?, x500 = ?,
                       Total = ?, Nota = ?, Estado = 'Cerrada'
                       WHERE Id = ?";
        
        $stmt = $conexion->prepare($sql_update);
        $stmt->bind_param("iiiiiiiiidsi", 
            $x1, $x2, $x5, $x10, $x20, $x50, $x100, $x200, $x500,
            $efectivo_contado,
            $nota_justi,
            $id_caja
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar caja: " . $stmt->error);
        }
        $stmt->close();
        
        $conexion->commit();
        
        // Detectar si hay problemas en AMBOS (arqueo Y cierre)
        $hay_problema_arqueo = $hay_arqueo && abs($sobrante_arqueo) >= 0.01;
        $hay_problema_cierre = abs($diferencia) >= 0.01;
        
        if ($hay_problema_arqueo && $hay_problema_cierre) {
            // ¡CAGADA ÉPICA! Problemas en arqueo Y cierre
            $mensaje = "🔥💩 CAGADA MI LOCO, BUSCATE OTRO TRABAJO PORQUE NO SERVIS DE NADA 💩🔥<br>";
            $mensaje .= "Arqueo: " . ($sobrante_arqueo > 0 ? "Sobrante" : "Faltante") . " de L." . number_format(abs($sobrante_arqueo), 2) . "<br>";
            $mensaje .= "Cierre: " . ($diferencia > 0 ? "Sobrante" : "Faltante") . " de L." . number_format(abs($diferencia), 2);
            $tipo_mensaje = "epic_fail";
        } elseif ($diferencia == 0) {
            $mensaje = "✓ Cierre registrado exitosamente. Todo cuadra perfectamente. Total contado: L." . number_format($efectivo_contado, 2);
            $tipo_mensaje = "success";
            // Confeti cuando cuadra perfecto!
            $mostrar_confeti = true;
        } elseif ($diferencia > 0) {
            $mensaje = "Cierre registrado. SOBRANTE de L." . number_format($diferencia, 2) . ". Total contado: L." . number_format($efectivo_contado, 2);
            $tipo_mensaje = "warning";
        } else {
            $mensaje = "Cierre registrado. FALTANTE de L." . number_format(abs($diferencia), 2) . ". Total contado: L." . number_format($efectivo_contado, 2);
            $tipo_mensaje = "error";
        }
        
        // Preparar datos para el correo
        $datos_correo = [
            'nombre_usuario' => $Nombre_Completo,
            'rol_usuario' => $Rol,
            'fecha' => date('Y-m-d'),
            'hora' => date('H:i:s'),
            'fondo_caja' => $fondo_caja,
            'total_ventas' => $total_ventas,
            'total_egresos' => $total_egresos,
            'efectivo_esperado' => $efectivo_esperado,
            'efectivo_contado' => $efectivo_contado,
            'diferencia' => $diferencia,
            'x500' => $x500,
            'x200' => $x200,
            'x100' => $x100,
            'x50' => $x50,
            'x20' => $x20,
            'x10' => $x10,
            'x5' => $x5,
            'x2' => $x2,
            'x1' => $x1
        ];
        
        // Enviar correo de notificación
        try {
            enviarNotificacionCierreCaja($datos_correo);
            $mensaje .= " Se ha enviado una notificación por correo.";
        } catch (Exception $e) {
            error_log("Error al enviar correo de cierre de caja: " . $e->getMessage());
            $mensaje .= " No se pudo enviar la notificación por correo.";
        }
        
        // Redirigir después de 3 segundos
        header("refresh:3;url=index.php");
        
    } catch (Exception $e) {
        $conexion->rollback();
        $mensaje = "Error al registrar el cierre: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Función para enviar correo de notificación de cierre de caja
function enviarNotificacionCierreCaja($datos) {
    require 'vendor/autoload.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'reysystemnotificaciones@gmail.com';
        $mail->Password   = 'sbzl symo xbpt atoq';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        
        $mail->setFrom('reysystemnotificaciones@gmail.com', 'ReySystemAPP - Cierre de Caja');
        $mail->addAddress('sashaalejandrar24@gmail.com', 'Sasha');
        $mail->addAddress('jesushernan.ordo@gmail.com', 'Jesús');
        $mail->addReplyTo('no-reply@tiendasrey.com', 'No Responder');
        
        $mail->isHTML(true);
        $mail->Subject = '🔒 Cierre de Caja - ' . $datos['fecha'];
        
        if ($datos['diferencia'] > 0) {
            $color_diferencia = '#3b82f6';
            $texto_diferencia = 'Sobrante: +L.' . number_format($datos['diferencia'], 2);
            $icono_diferencia = '➕';
        } elseif ($datos['diferencia'] < 0) {
            $color_diferencia = '#ef4444';
            $texto_diferencia = 'Faltante: -L.' . number_format(abs($datos['diferencia']), 2);
            $icono_diferencia = '➖';
        } else {
            $color_diferencia = '#10b981';
            $texto_diferencia = 'Cuadra Perfecto';
            $icono_diferencia = '✅';
        }
        
        $mail->Body = '
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; padding: 20px; }
                .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 20px; }
                .header { background-color: #1152d4; color: white; padding: 15px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { padding: 20px 0; }
                .info-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                .info-table th { background-color: #f2f2f2; text-align: left; padding: 10px; font-weight: bold; }
                .info-table td { padding: 10px; border-bottom: 1px solid #ddd; }
                .footer { margin-top: 20px; text-align: center; font-size: 12px; color: #777; }
                .total-row td { font-weight: bold; background-color: #f9f9f9; }
                .diferencia { color: ' . $color_diferencia . '; font-weight: bold; }
                .resumen-card { background-color: #f9f9f9; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
                .resumen-item { display: flex; justify-content: space-between; margin-bottom: 10px; }
                .resumen-item:last-child { margin-bottom: 0; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔒 Cierre de Caja</h1>
                    <p>Se ha realizado un cierre de caja en el sistema</p>
                </div>
                <div class="content">
                    <h2>Información del Cierre</h2>
                    <table class="info-table">
                        <tr>
                            <th><strong>Fecha y Hora</strong></th>
                            <td>' . $datos['fecha'] . ' a las ' . $datos['hora'] . '</td>
                        </tr>
                        <tr>
                            <th><strong>Responsable</strong></th>
                            <td>' . htmlspecialchars($datos['nombre_usuario']) . '</td>
                        </tr>
                        <tr>
                            <th><strong>Rol</strong></th>
                            <td>' . htmlspecialchars($datos['rol_usuario']) . '</td>
                        </tr>
                    </table>
                    
                    <div class="resumen-card">
                        <h3 style="margin-top: 20px; margin-bottom: 15px;">Resumen de Caja</h3>
                        <div class="resumen-item">
                            <span>Fondo Inicial:</span>
                            <span>L. ' . number_format($datos['fondo_caja'], 2) . '</span>
                        </div>
                        <div class="resumen-item">
                            <span>Ventas del Día:</span>
                            <span>L. ' . number_format($datos['total_ventas'], 2) . '</span>
                        </div>
                        <div class="resumen-item">
                            <span>Egresos del Día:</span>
                            <span>L. ' . number_format($datos['total_egresos'], 2) . '</span>
                        </div>
                        <div class="resumen-item">
                            <span>Total Esperado:</span>
                            <span>L. ' . number_format($datos['efectivo_esperado'], 2) . '</span>
                        </div>
                        <div class="resumen-item">
                            <span>Total Contado:</span>
                            <span>L. ' . number_format($datos['efectivo_contado'], 2) . '</span>
                        </div>
                        <div class="resumen-item diferencia">
                            <span>' . $icono_diferencia . ' Diferencia:</span>
                            <span>' . $texto_diferencia . '</span>
                        </div>
                    </div>
                    
                    <h3 style="margin-top: 30px;">Desglose de Billetes y Monedas</h3>
                    <table class="info-table">
                        <tr>
                            <th><strong>Denominación</strong></th>
                            <th><strong>Cantidad</strong></th>
                            <th><strong>Subtotal</strong></th>
                        </tr>
                        <tr>
                            <td>L. 500</td>
                            <td>' . $datos['x500'] . '</td>
                            <td>L. ' . number_format($datos['x500'] * 500, 2) . '</td>
                        </tr>
                        <tr>
                            <td>L. 200</td>
                            <td>' . $datos['x200'] . '</td>
                            <td>L. ' . number_format($datos['x200'] * 200, 2) . '</td>
                        </tr>
                        <tr>
                            <td>L. 100</td>
                            <td>' . $datos['x100'] . '</td>
                            <td>L. ' . number_format($datos['x100'] * 100, 2) . '</td>
                        </tr>
                        <tr>
                            <td>L. 50</td>
                            <td>' . $datos['x50'] . '</td>
                            <td>L. ' . number_format($datos['x50'] * 50, 2) . '</td>
                        </tr>
                        <tr>
                            <td>L. 20</td>
                            <td>' . $datos['x20'] . '</td>
                            <td>L. ' . number_format($datos['x20'] * 20, 2) . '</td>
                        </tr>
                        <tr>
                            <td>L. 10</td>
                            <td>' . $datos['x10'] . '</td>
                            <td>L. ' . number_format($datos['x10'] * 10, 2) . '</td>
                        </tr>
                        <tr>
                            <td>L. 5</td>
                            <td>' . $datos['x5'] . '</td>
                            <td>L. ' . number_format($datos['x5'] * 5, 2) . '</td>
                        </tr>
                        <tr>
                            <td>L. 2</td>
                            <td>' . $datos['x2'] . '</td>
                            <td>L. ' . number_format($datos['x2'] * 2, 2) . '</td>
                        </tr>
                        <tr>
                            <td>L. 1</td>
                            <td>' . $datos['x1'] . '</td>
                            <td>L. ' . number_format($datos['x1'] * 1, 2) . '</td>
                        </tr>
                        <tr class="total-row">
                            <td colspan="2" style="text-align:right;"><strong>Total Contado:</strong></td>
                            <td>L. ' . number_format($datos['efectivo_contado'], 2) . '</td>
                        </tr>
                    </table>
                </div>
                <div class="footer">
                    <p>Este es un mensaje automático generado por ReySystemAPP. Por favor, no responda a este correo.</p>
                </div>
            </div>
</body>
        </html>';
        
        $mail->send();
    } catch (Exception $e) {
        throw new Exception("El mensaje no pudo ser enviado. Error de PHPMailer: {$mail->ErrorInfo}");
    }
}
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Cierre de Caja</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
    <!-- Confetti library -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
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
                    keyframes: {
                        shake: {
                            '0%, 100%': { transform: 'translateX(0)' },
                            '10%, 30%, 50%, 70%, 90%': { transform: 'translateX(-10px)' },
                            '20%, 40%, 60%, 80%': { transform: 'translateX(10px)' },
                        }
                    },
                    animation: {
                        shake: 'shake 0.5s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Manrope', sans-serif; }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] { -moz-appearance: textfield; }
    </style>
</head>
<body class="font-display bg-background-light dark:bg-background-dark">
<div class="flex h-screen w-full">
<!-- SideNavBar -->
<?php include 'menu_lateral.php'; ?>
<!-- Main Content -->
<div class="flex-1 flex flex-col overflow-y-auto">
<header class="flex-shrink-0 flex items-center justify-between whitespace-nowrap border-b border-gray-200 dark:border-gray-800 bg-background-light/80 dark:bg-background-dark/80 backdrop-blur-sm px-10 py-3">
<div class="flex items-center gap-4 text-gray-800 dark:text-white">
<span class="material-symbols-outlined text-gray-500 dark:text-gray-400">payments</span>
<h2 class="text-lg font-bold leading-tight tracking-[-0.015em]">Sistema de Cobros - Rey System</h2>
</div>
<div class="flex items-center gap-4">
<!-- SISTEMA DE NOTIFICACIONES REYSYSTEM -->
<script defer src="https://unpkg.com/alpinejs@3.12.0/dist/cdn.min.js"></script>
<?php 
// Cargar notificaciones para el componente
include_once 'generar_notificaciones.php';
$stmt_usuario = $conexion->prepare("SELECT Id FROM usuarios WHERE usuario = ?");
$stmt_usuario->bind_param("s", $_SESSION['usuario']);
$stmt_usuario->execute();
$resultado_usuario = $stmt_usuario->get_result();
$usuario_data = $resultado_usuario->fetch_assoc();
$Id = $usuario_data['Id'];
$stmt_usuario->close();

generarNotificacionesStock($conexion, $Id);
$notificaciones_pendientes = obtenerNotificacionesPendientes($conexion, $Id);
$total_notificaciones = contarNotificacionesPendientes($conexion, $Id);
include 'notificaciones_component.php'; 
?>
<button class="flex h-10 w-10 cursor-pointer items-center justify-center overflow-hidden rounded-lg bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-700">
<span class="material-symbols-outlined text-xl">help</span>
</button>
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Avatar de usuario" style='background-image: url("<?php echo $Perfil; ?>");'></div>
</div>
</header>
            
            <main class="flex-1 overflow-y-auto p-4 lg:p-6">
                <div class="max-w-6xl mx-auto">
                    <div class="flex flex-col gap-0.5 mb-4">
                        <p class="text-gray-900 dark:text-white text-2xl font-black">Cierre de Caja</p>
                        <p class="text-gray-500 dark:text-[#92a4c9] text-sm">Fecha: <?php echo date('l, d \d\e F \d\e Y'); ?></p>
                    </div>

                    <?php if ($mensaje): ?>
                    <div class="mb-3 p-3 rounded-lg <?php 
                        if ($tipo_mensaje === 'epic_fail') {
                            echo 'bg-gradient-to-r from-red-600 to-orange-600 border-4 border-yellow-400 animate-shake';
                        } elseif ($tipo_mensaje === 'success') {
                            echo 'bg-green-100 border-green-300 dark:bg-green-900/40';
                        } elseif ($tipo_mensaje === 'warning') {
                            echo 'bg-yellow-100 border-yellow-300';
                        } else {
                            echo 'bg-red-100 border-red-300 dark:bg-red-900/40';
                        }
                    ?> border">
                        <p class="<?php 
                            if ($tipo_mensaje === 'epic_fail') {
                                echo 'text-white text-xl font-black text-center animate-pulse';
                            } elseif ($tipo_mensaje === 'success') {
                                echo 'text-green-800 dark:text-green-200 font-medium text-sm';
                            } elseif ($tipo_mensaje === 'warning') {
                                echo 'text-yellow-800 font-medium text-sm';
                            } else {
                                echo 'text-red-800 dark:text-red-200 font-medium text-sm';
                            }
                        ?>"><?php echo $mensaje; ?></p>
                    </div>
                    
                    <?php 
                    // Mostrar confeti si cuadró perfecto
                    if (isset($mostrar_confeti) && $mostrar_confeti) {
                        echo '<script>setTimeout(() => { if(typeof confetti !== "undefined") confetti({particleCount: 150, spread: 90, origin: { y: 0.6 }}); }, 500);</script>';
                    }
                    ?>
                    <?php endif; ?>

                    <form method="POST" action="cierre_caja.php" class="bg-white dark:bg-[#111722] p-4 sm:p-5 rounded-xl border border-gray-200 dark:border-[#232f48]">
                        <!-- Hidden fields for OTP -->
                        <input type="hidden" name="razon_faltante" id="razonHiddenCierre" value="" />
                        <input type="hidden" name="otp_validado" id="otpValidadoCierre" value="0" />
                        
                        <div class="flex flex-col lg:flex-row gap-5">
                            <div class="flex-1">
                                <h2 class="text-gray-900 dark:text-white text-lg font-bold pb-3">Conteo de Efectivo</h2>
                                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-2.5">
                                    <?php
                                    $denominaciones = [1, 2, 5, 10, 20, 50, 100, 200, 500];
                                    foreach ($denominaciones as $denom): ?>
                                    <div class="flex items-center gap-2">
                                        <label class="w-16 text-gray-800 dark:text-white text-sm font-medium">L.<?php echo $denom; ?></label>
                                        <input name="x<?php echo $denom; ?>" class="bill-input form-input flex w-full rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-[#324467] bg-background-light dark:bg-[#192233] h-10 px-3 text-right text-sm" data-value="<?php echo $denom; ?>" placeholder="0" type="number" min="0" value="0"/>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Botones debajo de las denominaciones -->
                                <div class="mt-4 grid grid-cols-2 gap-2">
                                    <button type="submit" name="registrar_cierre" class="flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 bg-primary text-white text-sm font-bold hover:bg-primary/90 transition-colors">
                                        <span class="material-symbols-outlined text-lg mr-1.5">check_circle</span>
                                        Confirmar Cierre
                                    </button>
                                    <a href="index.php" class="flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 bg-gray-200 dark:bg-[#232f48] text-gray-800 dark:text-white text-sm font-bold hover:bg-gray-300 dark:hover:bg-[#2a3a5a] transition-colors">
                                        <span class="material-symbols-outlined text-lg mr-1.5">cancel</span>
                                        Cancelar
                                    </a>
                                </div>
                            </div>
                            
                            <div class="lg:w-80 flex flex-col">
                                <h2 class="text-gray-900 dark:text-white text-lg font-bold pb-3">Resumen</h2>
                                <div class="flex-grow flex flex-col gap-2">
                                    <!-- Grid compacto 2x2 para los datos principales -->
                                    <div class="grid grid-cols-2 gap-2">
                                        <div class="flex flex-col gap-0.5 p-2 rounded-lg bg-background-light dark:bg-black/20">
                                            <p class="text-gray-500 dark:text-[#92a4c9] text-[10px] font-medium uppercase">Fondo Inicial</p>
                                            <p class="text-gray-900 dark:text-white text-base font-bold">L.<?php echo number_format($fondo_caja, 2); ?></p>
                                        </div>
                                        <div class="flex flex-col gap-0.5 p-2 rounded-lg bg-background-light dark:bg-black/20">
                                            <p class="text-gray-500 dark:text-[#92a4c9] text-[10px] font-medium uppercase">Ventas del Día</p>
                                            <p class="text-gray-900 dark:text-white text-base font-bold">L.<?php echo number_format($total_ventas, 2); ?></p>
                                        </div>
                                        <div class="flex flex-col gap-0.5 p-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                                            <p class="text-red-600 dark:text-red-300 text-[10px] font-medium uppercase">Egresos</p>
                                            <p class="text-red-800 dark:text-red-200 text-base font-bold">L.<?php echo number_format($total_egresos, 2); ?></p>
                                        </div>
                                        <?php if ($hay_arqueo && abs($sobrante_arqueo) >= 0.01): ?>
                                        <div class="flex flex-col gap-0.5 p-2 rounded-lg <?php echo $sobrante_arqueo > 0 ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : 'bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800'; ?>">
                                            <p class="<?php echo $sobrante_arqueo > 0 ? 'text-blue-600 dark:text-blue-300' : 'text-orange-600 dark:text-orange-300'; ?> text-[10px] font-medium uppercase">
                                                <?php echo $sobrante_arqueo > 0 ? 'Arqueo Sobrante' : 'Arqueo Faltante'; ?>
                                            </p>
                                            <p class="<?php echo $sobrante_arqueo > 0 ? 'text-blue-800 dark:text-blue-200' : 'text-orange-800 dark:text-orange-200'; ?> text-base font-bold">
                                                <?php 
                                                if ($sobrante_arqueo > 0) {
                                                    echo '+L.' . number_format($sobrante_arqueo, 2);
                                                } else {
                                                    echo '-L.' . number_format(abs($sobrante_arqueo), 2);
                                                }
                                                ?>
                                            </p>
                                        </div>
                                        <?php elseif (!$hay_arqueo): ?>
                                        <div class="flex flex-col gap-0.5 p-2 rounded-lg bg-gray-50 dark:bg-gray-800/20">
                                            <p class="text-gray-400 dark:text-gray-500 text-[10px] font-medium uppercase">Arqueo</p>
                                            <p class="text-gray-400 dark:text-gray-500 text-base font-bold">Sin arqueo</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Total Esperado -->
                                    <div class="flex flex-col gap-0.5 p-2.5 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 mt-1">
                                        <p class="text-blue-600 dark:text-blue-300 text-xs font-medium uppercase">Total Esperado</p>
                                        <p class="text-blue-800 dark:text-blue-200 text-xl font-bold">L.<?php echo number_format($efectivo_esperado, 2); ?></p>
                                    </div>
                                    
                                    <!-- Total Contado -->
                                    <div class="flex flex-col gap-0.5 p-2.5 rounded-lg bg-background-light dark:bg-black/20 border-2 border-gray-300 dark:border-gray-600">
                                        <p class="text-gray-500 dark:text-[#92a4c9] text-xs font-medium uppercase">Total Contado</p>
                                        <p id="total-contado" class="text-gray-900 dark:text-white text-2xl font-bold">L.0.00</p>
                                    </div>
                                    
                                    <!-- Diferencia -->
                                    <div id="diferencia-box" class="flex flex-col gap-0.5 p-2.5 rounded-lg bg-gray-100 dark:bg-black/20">
                                        <p id="diferencia-label" class="text-gray-500 dark:text-[#92a4c9] text-xs font-medium uppercase">Diferencia</p>
                                        <p id="diferencia-valor" class="text-gray-900 dark:text-white text-2xl font-bold">L.0.00</p>
                                    </div>
                                </div>
                            </div>
                        </div>

<!-- Modal OTP Cierre (hidden by default) -->
<div id="otpModalCierre" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111722] rounded-xl shadow-2xl max-w-md w-full border-2 border-red-500 dark:border-red-400">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-orange-600 text-white p-6 rounded-t-xl border-b-4 border-yellow-400">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-4xl animate-pulse">warning</span>
                <div>
                    <h3 class="text-xl font-black">💩 ¡CAGADA DETECTADA! 💩</h3>
                    <p class="text-sm opacity-90 font-bold">Buscate otro trabajo mi loco</p>
                    
                </div>
            </div>
        </div>
        
        <!-- Body -->
        <div class="p-6 space-y-4">
            <div class="bg-gradient-to-r from-red-100 to-orange-100 dark:from-red-900/40 dark:to-orange-900/40 border-2 border-red-400 dark:border-red-600 rounded-lg p-4 animate-pulse">
                <p class="text-sm text-gray-800 dark:text-gray-200 font-bold text-center">
                    🔥 NO SERVIS DE NADA 🔥
                </p>
                <p class="text-xs text-gray-700 dark:text-gray-300 mt-2 text-center">
                    ⚠️ <strong>El monto contado es menor al esperado</strong>
                </p>
                <p id="otpMensajeFaltanteCierre" class="text-lg font-black text-red-600 dark:text-red-400 mt-2 text-center"></p>
            </div>
            
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    📧 Se ha enviado un código OTP a tu correo electrónico
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    ⏰ El código expira en 20 minutos
                </p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Motivo del Faltante *
                </label>
                <select id="razonSelectCierre" class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#192233] text-gray-800 dark:text-white focus:ring-2 focus:ring-primary p-3" onchange="toggleOtraRazonCierre()">
                    <option value="">-- Selecciona un motivo --</option>
                    <option value="Faltante por robos/pérdidas">Faltante por robos/pérdidas</option>
                    <option value="Gastos no registrados previamente">Gastos no registrados previamente</option>
                    <option value="Error en conteo">Error en conteo</option>
                    <option value="Retiro no autorizado">Retiro no autorizado</option>
                    <option value="Otra razón">Otra razón</option>
                </select>
            </div>
            
            <!-- Input para "Otra razón" (oculto por defecto) -->
            <div id="otraRazonContainerCierre" class="hidden">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Especifica la razón *
                </label>
                <textarea 
                    id="otraRazonInputCierre" 
                    rows="3"
                    placeholder="Describe la razón del faltante..."
                    class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#192233] text-gray-800 dark:text-white focus:ring-2 focus:ring-primary p-3 resize-none"
                ></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Código OTP *
                </label>
                <input 
                    id="otpInputCierre" 
                    type="text" 
                    placeholder="" 
                    maxlength="20"
                    class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#192233] text-gray-800 dark:text-white focus:ring-2 focus:ring-primary p-3 text-center font-mono text-lg tracking-wider uppercase"
                />
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Ingresa el código recibido por correo
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="p-6 bg-gray-50 dark:bg-[#0a0f1a] rounded-b-xl flex gap-3">
            <button 
                type="button"
                onclick="cerrarModalOTPCierre()" 
                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
            >
                Cancelar
            </button>
            <button 
                type="button"
                onclick="verificarYProcederCierre()" 
                class="flex-1 px-4 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition-colors"
            >
                Verificar y Continuar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Cierre -->
<div id="modalConfirmacionCierre" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111722] rounded-xl shadow-2xl max-w-md w-full border border-gray-300 dark:border-gray-700">
        <!-- Header -->
        <div class="bg-gradient-to-r from-primary to-blue-600 text-white p-6 rounded-t-xl">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-4xl">lock</span>
                <div>
                    <h3 class="text-xl font-black">Confirmar Cierre de Caja</h3>
                    <p class="text-sm opacity-90">Revisa los datos antes de cerrar</p>
                </div>
            </div>
        </div>
        
        <!-- Body -->
        <div class="p-6 space-y-4">
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">Efectivo Esperado:</span>
                    <span id="confirmEsperadoCierre" class="text-gray-900 dark:text-white font-bold text-lg">L.0.00</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">Efectivo Contado:</span>
                    <span id="confirmContadoCierre" class="text-gray-900 dark:text-white font-bold text-lg">L.0.00</span>
                </div>
            </div>
            
            <div id="confirmDiferenciaContainerCierre" class="p-4 rounded-lg text-center">
                <div id="confirmDiferenciaCierre"></div>
            </div>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                ¿Estás seguro de que deseas cerrar la caja?
            </p>
        </div>
        
        <!-- Footer -->
        <div class="p-6 bg-gray-50 dark:bg-[#0a0f1a] rounded-b-xl flex gap-3">
            <button 
                type="button"
                onclick="cerrarModalConfirmacionCierre()" 
                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
            >
                Cancelar
            </button>
            <button 
                type="button"
                onclick="confirmarYEnviarCierre()" 
                class="flex-1 px-4 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition-colors"
            >
                Confirmar Cierre
            </button>
        </div>
    </div>
</div>

                    </form>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
const efectivoEsperado = <?php echo $efectivo_esperado; ?>;

function calcularTotalContado() {
    let total = 0;
    const inputs = document.querySelectorAll('.bill-input');
    
    inputs.forEach(input => {
        const cantidad = parseInt(input.value) || 0;
        const valor = parseInt(input.dataset.value);
        total += cantidad * valor;
    });
    
    document.getElementById('total-contado').textContent = `L.${total.toFixed(2)}`;
    calcularDiferencia(total);
    return total;
}

function calcularDiferencia(totalContado) {
    const diferencia = totalContado - efectivoEsperado;
    
    const diferenciaBox = document.getElementById('diferencia-box');
    const diferenciaLabel = document.getElementById('diferencia-label');
    const diferenciaValor = document.getElementById('diferencia-valor');
    
    diferenciaBox.className = 'flex flex-col gap-1 p-4 rounded-lg';
    
    if (Math.abs(diferencia) < 0.01) {
        diferenciaBox.classList.add('bg-green-100', 'dark:bg-green-900/40');
        diferenciaLabel.className = 'text-green-600 dark:text-green-300 text-sm font-medium';
        diferenciaLabel.textContent = '✓ Todo Cuadra';
        diferenciaValor.className = 'text-green-800 dark:text-green-200 text-3xl font-bold';
        diferenciaValor.textContent = 'L.0.00';
    } else if (diferencia > 0) {
        diferenciaBox.classList.add('bg-blue-100', 'dark:bg-blue-900/40');
        diferenciaLabel.className = 'text-blue-600 dark:text-blue-300 text-sm font-medium';
        diferenciaLabel.textContent = 'Sobra';
        diferenciaValor.className = 'text-blue-800 dark:text-blue-200 text-3xl font-bold';
        diferenciaValor.textContent = `+L.${diferencia.toFixed(2)}`;
    } else {
        diferenciaBox.classList.add('bg-red-100', 'dark:bg-red-900/40');
        diferenciaLabel.className = 'text-red-600 dark:text-red-300 text-sm font-medium';
        diferenciaLabel.textContent = 'Falta';
        diferenciaValor.className = 'text-red-800 dark:text-red-200 text-3xl font-bold';
        diferenciaValor.textContent = `L.${Math.abs(diferencia).toFixed(2)}`;
    }
}

// Interceptar envío del formulario
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    const totalContado = calcularTotalContado();
    const diferencia = totalContado - efectivoEsperado;
    
    // Verificar si el OTP ya fue validado
    const otpValidado = document.getElementById('otpValidadoCierre').value;
    
    // Si hay faltante Y el OTP NO ha sido validado, requerir OTP
    if (diferencia < 0 && otpValidado !== '1') {
        solicitarOTPCierre(totalContado, efectivoEsperado);
        return false;
    }
    
    // Si el OTP ya fue validado o no hay faltante, mostrar modal de confirmación
    mostrarModalConfirmacionCierre(totalContado, efectivoEsperado, diferencia);
    return false;
});

// Solicitar código OTP para cierre cuando hay faltante
async function solicitarOTPCierre(montoReal, montoEsperado) {
    try {
        const response = await fetch('generar_otp.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                tipo: 'cierre',
                monto_esperado: montoEsperado,
                monto_real: montoReal,
                usuario: '<?php echo $Nombre_Completo; ?>',
                email: 'admin' // Backend envía a ambos: sashaalejandrar24@gmail.com y jesushernan.ordo@gmail.com
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('otpModalCierre').classList.remove('hidden');
            document.getElementById('otpMensajeFaltanteCierre').textContent = 
                'Faltante: L.' + Math.abs(montoReal - montoEsperado).toFixed(2);
        } else {
            mostrarError('Error al generar código OTP: ' + data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al solicitar código OTP. Intenta nuevamente.');
    }
}

// Mostrar/ocultar input de "Otra razón"
function toggleOtraRazonCierre() {
    const select = document.getElementById('razonSelectCierre');
    const container = document.getElementById('otraRazonContainerCierre');
    const input = document.getElementById('otraRazonInputCierre');
    
    if (select.value === 'Otra razón') {
        container.classList.remove('hidden');
        input.required = true;
    } else {
        container.classList.add('hidden');
        input.required = false;
        input.value = '';
    }
}

// Verificar OTP y proceder con cierre
async function verificarYProcederCierre() {
    const codigo = document.getElementById('otpInputCierre').value.trim();
    let razon = document.getElementById('razonSelectCierre').value;
    const otraRazon = document.getElementById('otraRazonInputCierre').value.trim();
    
    if (!codigo) {
        mostrarAdvertencia('Por favor ingresa el código OTP');
        return;
    }
    
    if (!razon) {
        mostrarAdvertencia('Por favor selecciona un motivo');
        return;
    }
    
    // Si seleccionó "Otra razón", usar el texto personalizado
    if (razon === 'Otra razón') {
        if (!otraRazon) {
            mostrarAdvertencia('Por favor especifica la razón');
            return;
        }
        razon = 'Otra razón: ' + otraRazon;
    }
    
    try {
        const response = await fetch('verificar_otp.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ 
                codigo: codigo, 
                tipo: 'cierre' 
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.valido) {
            document.getElementById('razonHiddenCierre').value = razon;
            document.getElementById('otpValidadoCierre').value = '1';
            document.getElementById('otpModalCierre').classList.add('hidden');
            
            // Quitar temporalmente el event listener para evitar bucle
            const form = document.querySelector('form');
            
            // Crear un nuevo elemento de botón submit oculto y hacer click
            const submitBtn = document.createElement('button');
            submitBtn.type = 'submit';
            submitBtn.name = 'registrar_cierre';
            submitBtn.style.display = 'none';
            form.appendChild(submitBtn);
            submitBtn.click();
        } else {
            mostrarError(data.mensaje || 'Código OTP inválido o expirado');
            document.getElementById('otpInputCierre').value = '';
            document.getElementById('otpInputCierre').focus();
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al verificar código OTP. Intenta nuevamente.');
    }
}

function cerrarModalOTPCierre() {
    document.getElementById('otpModalCierre').classList.add('hidden');
    document.getElementById('otpInputCierre').value = '';
    document.getElementById('razonSelectCierre').selectedIndex = 0;
}

function mostrarModalConfirmacionCierre(total, esperado, diferencia) {
    // Actualizar contenido del modal
    document.getElementById('confirmEsperadoCierre').textContent = 'L.' + esperado.toFixed(2);
    document.getElementById('confirmContadoCierre').textContent = 'L.' + total.toFixed(2);
    
    const diferenciaElement = document.getElementById('confirmDiferenciaCierre');
    const diferenciaContainer = document.getElementById('confirmDiferenciaContainerCierre');
    
    // Limpiar clases previas
    diferenciaContainer.className = 'p-4 rounded-lg text-center';
    
    if (Math.abs(diferencia) < 0.01) {
        // Cuadra perfectamente
        diferenciaContainer.classList.add('bg-green-100', 'dark:bg-green-900/40', 'border-2', 'border-green-500');
        diferenciaElement.innerHTML = '<span class="text-2xl">✅</span><br><span class="text-green-800 dark:text-green-200 font-bold text-lg">¡Todo cuadra perfectamente!</span>';
    } else if (diferencia > 0) {
        // Sobra dinero
        diferenciaContainer.classList.add('bg-blue-100', 'dark:bg-blue-900/40', 'border-2', 'border-blue-500');
        diferenciaElement.innerHTML = '<span class="text-blue-600 dark:text-blue-300 font-semibold">SOBRANTE</span><br><span class="text-blue-800 dark:text-blue-200 font-bold text-xl">+L.' + diferencia.toFixed(2) + '</span>';
    } else {
        // Falta dinero
        diferenciaContainer.classList.add('bg-red-100', 'dark:bg-red-900/40', 'border-2', 'border-red-500');
        diferenciaElement.innerHTML = '<span class="text-red-600 dark:text-red-300 font-semibold">FALTANTE</span><br><span class="text-red-800 dark:text-red-200 font-bold text-xl">-L.' + Math.abs(diferencia).toFixed(2) + '</span>';
    }
    
    // Mostrar modal
    document.getElementById('modalConfirmacionCierre').classList.remove('hidden');
}

function cerrarModalConfirmacionCierre() {
    document.getElementById('modalConfirmacionCierre').classList.add('hidden');
}

function confirmarYEnviarCierre() {
    // Cerrar modal
    document.getElementById('modalConfirmacionCierre').classList.add('hidden');
    
    // Enviar el formulario
    const form = document.querySelector('form');
    const submitBtn = document.createElement('button');
    submitBtn.type = 'submit';
    submitBtn.name = 'registrar_cierre';
    submitBtn.value = '1';
    submitBtn.style.display = 'none';
    form.appendChild(submitBtn);
    
    // Remover el event listener temporalmente
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    newForm.submit();
}

// Inicializar
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.bill-input').forEach(input => {
        input.addEventListener('input', calcularTotalContado);
    });
    
    // Calcular inicial
    calcularTotalContado();
});
</script>
<?php include 'modal_sistema.php'; ?>
</body>
</html>