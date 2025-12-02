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
    // No hay apertura de caja para hoy
    $_SESSION['mensaje_caja'] = "No hay apertura de caja para hoy. Debe abrir la caja antes de realizar el arqueo.";
    $_SESSION['tipo_mensaje_caja'] = "warning";
    header("Location: index.php");
    exit();
}

 $caja_hoy = $consulta_caja->fetch_assoc();
 $estado_caja = $caja_hoy['Estado'];
 $fondo_caja = floatval($caja_hoy['Total']); // Asumo que 'Total' es el monto inicial
 $id_caja = $caja_hoy['Id'];

if ($estado_caja === 'Cerrada') {
    $_SESSION['mensaje_caja'] = "La caja ya está cerrada hoy. No se puede realizar arqueo.";
    $_SESSION['tipo_mensaje_caja'] = "error";
    header("Location: index.php");
    exit();
}

if ($estado_caja !== 'Abierta') {
    $_SESSION['mensaje_caja'] = "La caja debe estar abierta para realizar el arqueo.";
    $_SESSION['tipo_mensaje_caja'] = "error";
    header("Location: index.php");
    exit();
}

// Calcular total de ventas del día
 $consulta_ventas = $conexion->query("SELECT COALESCE(SUM(Total), 0) as total_ventas FROM ventas WHERE DATE(Fecha_Venta) = '$fecha_hoy'");
 $total_ventas = 0;
if ($consulta_ventas && $consulta_ventas->num_rows > 0) {
    $ventas_data = $consulta_ventas->fetch_assoc();
    $total_ventas = floatval($ventas_data['total_ventas']);
}

// Calcular total de egresos del día
 $stmt_egresos = $conexion->prepare("SELECT SUM(monto) as total_egresos FROM egresos_caja WHERE caja_id = ?");
 $stmt_egresos->bind_param("i", $id_caja);
 $stmt_egresos->execute();
 $resultado_egresos = $stmt_egresos->get_result();
 $total_egresos = 0;
if ($resultado_egresos->num_rows > 0) {
    $egresos_data = $resultado_egresos->fetch_assoc();
    $total_egresos = floatval($egresos_data['total_egresos']);
}
 $stmt_egresos->close();

// Total esperado en caja
 $efectivo_esperado = $fondo_caja + $total_ventas - $total_egresos;

// Obtener datos del usuario
 $resultado = $conexion->query("SELECT * FROM usuarios WHERE usuario = '" . $_SESSION['usuario'] . "'");
 $Usuario = "";
 $Nombre_Completo = "";
 $Rol = "";
 $Perfil = "";
 $Id = "";

while($row = $resultado->fetch_assoc()){
    $Id = $row['Id'];
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_arqueo'])) {
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
    $nota_justi = isset($_POST['nota_justi']) ? $_POST['nota_justi'] : "Arqueo de caja - " . $Nombre_Completo;
    $nota_justi .= "\nVentas: L." . number_format($total_ventas, 2);
    $nota_justi .= "\nEgresos: L." . number_format($total_egresos, 2);
    $nota_justi .= "\nDiferencia: L." . number_format($diferencia, 2);
    
    // Agregar razón de la diferencia si se proporcionó (vía OTP)
    if (isset($_POST['razon_faltante']) && !empty($_POST['razon_faltante'])) {
        $nota_justi .= "\nRazón diferencia: " . $_POST['razon_faltante'];
        $nota_justi .= "\nAutorizado vía OTP";
    }
    
    // Obtener fecha actual
    $fecha = date('Y-m-d H:i:s');
    
    // Calcular sobrante y faltante
    $sobrante = ($diferencia > 0) ? $diferencia : 0;
    $faltante = ($diferencia < 0) ? abs($diferencia) : 0;
    
    // Insertar en la base de datos arqueo_caja
    $sql = "INSERT INTO arqueo_caja (X1, X2, X5, X10, X20, X50, X100, X200, X500, Total, Nota_justi, Efectivo, Transferencia, Tarjeta, Fecha, usuario, sobrante, faltante) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $transferencia = 0;
    $tarjeta = 0;
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiiiiiiiidsdddssdd", $x1, $x2, $x5, $x10, $x20, $x50, $x100, $x200, $x500, 
                      $efectivo_contado, $nota_justi, $efectivo_contado, $transferencia, $tarjeta, $fecha, $Usuario, $sobrante, $faltante);
    
    if ($stmt->execute()) {
        if ($diferencia == 0) {
            $mensaje = "✓ Arqueo registrado exitosamente. Todo cuadra perfectamente. Total contado: L." . number_format($efectivo_contado, 2);
            $tipo_mensaje = "success";
            // Confeti cuando cuadra perfecto!
            $mostrar_confeti = true;
        } elseif ($diferencia > 0) {
            $mensaje = "Arqueo registrado. SOBRANTE de L." . number_format($diferencia, 2) . ". Total contado: L." . number_format($efectivo_contado, 2);
            $tipo_mensaje = "warning";
        } else {
            $mensaje = "Arqueo registrado. FALTANTE de L." . number_format(abs($diferencia), 2) . ". Total contado: L." . number_format($efectivo_contado, 2);
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
            enviarNotificacionArqueoCaja($datos_correo);
            $mensaje .= " Se ha enviado una notificación por correo.";
        } catch (Exception $e) {
            // Si el correo falla, registrar el error pero no mostrarlo al usuario
            error_log("Error al enviar correo de arqueo de caja: " . $e->getMessage());
            $mensaje .= " No se pudo enviar la notificación por correo.";
        }
        
        // Redirigir después de 3 segundos
        header("refresh:3;url=caja_al_dia.php");
    } else {
        $mensaje = "Error al registrar el arqueo: " . $stmt->error;
        $tipo_mensaje = "error";
    }
    
    $stmt->close();
}

// Función para enviar correo de notificación de arqueo de caja
function enviarNotificacionArqueoCaja($datos) {
    // Cargar el autoloader de Composer
    require 'vendor/autoload.php';
    
    // Crear una instancia de PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'reysystemnotificaciones@gmail.com';
        $mail->Password   = 'sbzl symo xbpt atoq'; // Tu Contraseña de Aplicación
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';
        
        // Remitentes y destinatarios
        $mail->setFrom('reysystemnotificaciones@gmail.com', 'ReySystemAPP - Notificación de Caja');
        $mail->addAddress('sashaalejandrar24@gmail.com', 'Sasha');
        $mail->addAddress('jesushernan.ordo@gmail.com', 'Jesús');
        $mail->addReplyTo('no-reply@tiendasrey.com', 'No Responder');
        
        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = '🔍 Arqueo de Caja - ' . $datos['fecha'];
        
        // Determinar el color y texto de la diferencia
        if ($datos['diferencia'] > 0) {
            $color_diferencia = '#3b82f6'; // azul
            $texto_diferencia = 'Sobrante: +L.' . number_format($datos['diferencia'], 2);
            $icono_diferencia = '➕';
        } elseif ($datos['diferencia'] < 0) {
            $color_diferencia = '#ef4444'; // rojo
            $texto_diferencia = 'Faltante: -L.' . number_format(abs($datos['diferencia']), 2);
            $icono_diferencia = '➖';
        } else {
            $color_diferencia = '#10b981'; // verde
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
                    <h1>🔍 Arqueo de Caja</h1>
                    <p>Se ha realizado un arqueo de caja en el sistema</p>
                </div>
                <div class="content">
                    <h2>Información del Arqueo</h2>
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
<title>Arqueo de Caja</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<!-- Confetti library -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<style>
    .material-symbols-outlined {
      font-variation-settings:
      'FILL' 0,
      'wght' 400,
      'GRAD' 0,
      'opsz' 24
    }
    /* Ocultar las flechas de los inputs numéricos */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    input[type="number"] {
        -moz-appearance: textfield;
    }
</style>
<script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            "primary": "#2563EB",
            "background-light": "#f6f6f8",
            "background-dark": "#101622",
          },
          fontFamily: {
            "display": ["Manrope", "sans-serif"]
          },
          borderRadius: {"DEFAULT": "0.5rem", "lg": "0.75rem", "xl": "1rem", "full": "9999px"},
        },
      },
    }
</script>
<script>
// Constantes pasadas desde PHP
const totalVentas = <?php echo $total_ventas; ?>;
const montoInicial = <?php echo $fondo_caja; ?>;
const totalEgresos = <?php echo $total_egresos; ?>;
const efectivoEsperado = <?php echo $efectivo_esperado; ?>;

// Función para calcular totales y diferencia
function calcularTotales() {
    const x500 = parseInt(document.getElementById('x500').value) || 0;
    const x200 = parseInt(document.getElementById('x200').value) || 0;
    const x100 = parseInt(document.getElementById('x100').value) || 0;
    const x50 = parseInt(document.getElementById('x50').value) || 0;
    const x20 = parseInt(document.getElementById('x20').value) || 0;
    const x10 = parseInt(document.getElementById('x10').value) || 0;
    const x5 = parseInt(document.getElementById('x5').value) || 0;
    const x2 = parseInt(document.getElementById('x2').value) || 0;
    const x1 = parseInt(document.getElementById('x1').value) || 0;
    
    // Calcular subtotales
    document.getElementById('subtotal500').textContent = 'L.' + (x500 * 500).toFixed(2);
    document.getElementById('subtotal200').textContent = 'L.' + (x200 * 200).toFixed(2);
    document.getElementById('subtotal100').textContent = 'L.' + (x100 * 100).toFixed(2);
    document.getElementById('subtotal50').textContent = 'L.' + (x50 * 50).toFixed(2);
    document.getElementById('subtotal20').textContent = 'L.' + (x20 * 20).toFixed(2);
    document.getElementById('subtotal10').textContent = 'L.' + (x10 * 10).toFixed(2);
    document.getElementById('subtotal5').textContent = 'L.' + (x5 * 5).toFixed(2);
    document.getElementById('subtotal2').textContent = 'L.' + (x2 * 2).toFixed(2);
    document.getElementById('subtotal1').textContent = 'L.' + (x1 * 1).toFixed(2);
    
    const totalContado = (x500 * 500) + (x200 * 200) + (x100 * 100) + (x50 * 50) + 
                         (x20 * 20) + (x10 * 10) + (x5 * 5) + (x2 * 2) + (x1 * 1);
    
    document.getElementById('totalContado').textContent = 'L.' + totalContado.toFixed(2);
    
    // Calcular diferencia
    const diferencia = totalContado - efectivoEsperado;
    
    const elementoDiferencia = document.getElementById('diferencia');
    const labelDiferencia = document.getElementById('labelDiferencia');
    const cardDiferencia = document.getElementById('cardDiferencia');
    
    // Limpiar clases previas
    cardDiferencia.className = 'flex flex-col gap-1 p-4 rounded-lg';
    
    if (Math.abs(diferencia) < 0.01) {
        // Cuadra perfectamente
        cardDiferencia.classList.add('bg-green-100', 'dark:bg-green-900/40');
        labelDiferencia.className = 'text-green-600 dark:text-green-300 text-sm font-medium';
        labelDiferencia.textContent = '✓ Todo Cuadra';
        elementoDiferencia.className = 'text-green-800 dark:text-green-200 text-3xl font-bold';
        elementoDiferencia.textContent = 'L.0.00';
    } else if (diferencia > 0) {
        // Sobra dinero
        cardDiferencia.classList.add('bg-blue-100', 'dark:bg-blue-900/40');
        labelDiferencia.className = 'text-blue-600 dark:text-blue-300 text-sm font-medium';
        labelDiferencia.textContent = 'Sobra';
        elementoDiferencia.className = 'text-blue-800 dark:text-blue-200 text-3xl font-bold';
        elementoDiferencia.textContent = '+L.' + diferencia.toFixed(2);
    } else {
        // Falta dinero
        cardDiferencia.classList.add('bg-red-100', 'dark:bg-red-900/40');
        labelDiferencia.className = 'text-red-600 dark:text-red-300 text-sm font-medium';
        labelDiferencia.textContent = 'Falta';
        elementoDiferencia.className = 'text-red-800 dark:text-red-200 text-3xl font-bold';
        elementoDiferencia.textContent = 'L.' + Math.abs(diferencia).toFixed(2);
    }
}

function validarFormulario(event) {
    const total = parseFloat(document.getElementById('totalContado').textContent.replace('L.', '')) || 0;
    // efectivoEsperado ya es una constante global
    const diferencia = total - efectivoEsperado;
    
    if (total <= 0) {
        mostrarAdvertencia('El total de arqueo debe ser mayor a 0');
        return false;
    }
    
    // Verificar si el OTP ya fue validado
    const otpValidado = document.getElementById('otpValidadoArqueo').value;
    
    // Si hay diferencia (sobrante o faltante) Y el OTP NO ha sido validado, requerir OTP
    if (Math.abs(diferencia) >= 0.01 && otpValidado !== '1') {
        event.preventDefault();
        solicitarOTPArqueo(total, efectivoEsperado, diferencia);
        return false;
    }
    
    // Si el OTP ya fue validado o no hay diferencia, mostrar modal de confirmación
    event.preventDefault();
    mostrarModalConfirmacion(total, efectivoEsperado, diferencia);
    return false;
}

function mostrarModalConfirmacion(total, esperado, diferencia) {
    // Actualizar contenido del modal
    document.getElementById('confirmEsperado').textContent = 'L.' + esperado.toFixed(2);
    document.getElementById('confirmContado').textContent = 'L.' + total.toFixed(2);
    
    const diferenciaElement = document.getElementById('confirmDiferencia');
    const diferenciaContainer = document.getElementById('confirmDiferenciaContainer');
    
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
    document.getElementById('modalConfirmacionArqueo').classList.remove('hidden');
}

function cerrarModalConfirmacion() {
    document.getElementById('modalConfirmacionArqueo').classList.add('hidden');
}

function confirmarYEnviarArqueo() {
    // Cerrar modal
    document.getElementById('modalConfirmacionArqueo').classList.add('hidden');
    
    // Enviar el formulario
    const form = document.querySelector('form');
    const submitBtn = document.createElement('button');
    submitBtn.type = 'submit';
    submitBtn.name = 'registrar_arqueo';
    submitBtn.value = '1';
    submitBtn.style.display = 'none';
    form.appendChild(submitBtn);
    
    // Desactivar validación temporalmente para evitar loop
    form.removeAttribute('onsubmit');
    submitBtn.click();
}

// Solicitar código OTP para arqueo cuando hay diferencia
async function solicitarOTPArqueo(montoReal, montoEsperado, diferencia) {
    try {
        const response = await fetch('generar_otp.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                tipo: 'arqueo',
                monto_esperado: montoEsperado,
                monto_real: montoReal,
                usuario: '<?php echo $Nombre_Completo; ?>',
                email: 'admin' // Backend envía a ambos correos
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('otpModalArqueo').classList.remove('hidden');
            const tipoDiferencia = diferencia > 0 ? 'Sobrante' : 'Faltante';
            document.getElementById('otpMensajeDiferenciaArqueo').textContent = 
                tipoDiferencia + ': L.' + Math.abs(diferencia).toFixed(2);
        } else {
            mostrarError('Error al generar código OTP: ' + data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al solicitar código OTP. Intenta nuevamente.');
    }
}

// Mostrar/ocultar input de "Otra razón"
function toggleOtraRazonArqueo() {
    const select = document.getElementById('razonSelectArqueo');
    const container = document.getElementById('otraRazonContainerArqueo');
    const input = document.getElementById('otraRazonInputArqueo');
    
    if (select.value === 'Otra razón') {
        container.classList.remove('hidden');
        input.required = true;
    } else {
        container.classList.add('hidden');
        input.required = false;
        input.value = '';
    }
}

async function validarYProcederArqueo() {
    const codigo = document.getElementById('otpInputArqueo').value.trim();
    let razon = document.getElementById('razonSelectArqueo').value;
    const otraRazon = document.getElementById('otraRazonInputArqueo').value.trim();
    
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
                tipo: 'arqueo' 
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.valido) {
            document.getElementById('razonHiddenArqueo').value = razon;
            document.getElementById('otpValidadoArqueo').value = '1';
            document.getElementById('otpModalArqueo').classList.add('hidden');
            
            // Crear botón submit temporal y hacer clic
            const form = document.querySelector('form');
            const submitBtn = document.createElement('button');
            submitBtn.type = 'submit';
            submitBtn.name = 'registrar_arqueo';
            submitBtn.value = '1';
            submitBtn.style.display = 'none';
            form.appendChild(submitBtn);
            
            // Desactivar validación temporalmente
            form.removeAttribute('onsubmit');
            submitBtn.click();
        } else {
            mostrarError(data.mensaje || 'Código OTP inválido o expirado');
            document.getElementById('otpInputArqueo').value = '';
            document.getElementById('otpInputArqueo').focus();
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al verificar código OTP. Intenta nuevamente.');
    }
}

function cerrarModalOTPArqueo() {
    document.getElementById('otpModalArqueo').classList.add('hidden');
    document.getElementById('otpInputArqueo').value = '';
    document.getElementById('razonSelectArqueo').selectedIndex = 0;
}


function configurarFechaActual() {
    const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const fecha = new Date().toLocaleDateString('es-ES', opciones);
    document.getElementById('fecha-actual').textContent = `Fecha: ${fecha.charAt(0).toUpperCase() + fecha.slice(1)}`;
}

// Se ejecuta cuando la página carga
document.addEventListener('DOMContentLoaded', () => {
    configurarFechaActual();
    calcularTotales(); // Calcula con valores iniciales (0)
});
</script>
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
include 'generar_notificaciones.php';
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
<main class="flex-1 overflow-y-auto p-6 lg:p-10">
<div class="max-w-7xl mx-auto">
<div class="flex flex-col gap-1 mb-8">
<p class="text-gray-900 dark:text-white text-3xl font-black leading-tight tracking-[-0.033em]">Arqueo de Caja</p>
<p class="text-gray-500 dark:text-gray-400 text-base font-normal leading-normal" id="fecha-actual">Fecha: Cargando...</p>
</div>

<?php if ($mensaje): ?>
<div class="mb-6 p-4 rounded-lg <?php 
    if ($tipo_mensaje === 'success') echo 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
    elseif ($tipo_mensaje === 'warning') echo 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200';
    else echo 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
?>">
    <?php echo $mensaje; ?>
</div>

<?php 
// Mostrar confeti si cuadró perfecto
if (isset($mostrar_confeti) && $mostrar_confeti) {
    echo '<script>setTimeout(() => { if(typeof confetti !== "undefined") confetti({particleCount: 150, spread: 90, origin: { y: 0.6 }}); }, 500);</script>';
}
?>
<?php endif; ?>

<div class="bg-white dark:bg-gray-900 p-4 sm:p-6 rounded-xl border border-gray-200 dark:border-gray-800">
<form method="POST" action="arqueo_caja.php" onsubmit="return validarFormulario(event);">
<!-- Hidden fields for OTP -->
<input type="hidden" name="razon_faltante" id="razonHiddenArqueo" value="" />
<input type="hidden" name="otp_validado" id="otpValidadoArqueo" value="0" />

<div class="flex flex-col lg:flex-row gap-6">
<div class="flex-1">
<h2 class="text-gray-900 dark:text-white text-lg font-bold leading-tight tracking-[-0.015em] pb-3">Efectivo Contado</h2>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-2">
<!-- Inputs de billetes ordenados de menor a mayor -->
<div class="flex items-center gap-2">
<label class="w-12 text-gray-800 dark:text-white text-sm font-medium" for="x1">L.1</label>
<input id="x1" name="x1" class="bill-input form-input flex w-full min-w-0 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 h-10 placeholder:text-gray-400 dark:placeholder-gray-500 px-3 text-sm font-normal leading-normal text-right" placeholder="0" type="number" min="0" value="0" oninput="calcularTotales()"/>
<p id="subtotal1" class="w-16 text-right text-xs text-gray-800 dark:text-gray-300 font-mono">L.0.00</p>
</div>
<div class="flex items-center gap-2">
<label class="w-12 text-gray-800 dark:text-white text-sm font-medium" for="x2">L.2</label>
<input id="x2" name="x2" class="bill-input form-input flex w-full min-w-0 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 h-10 placeholder:text-gray-400 dark:placeholder-gray-500 px-3 text-sm font-normal leading-normal text-right" placeholder="0" type="number" min="0" value="0" oninput="calcularTotales()"/>
<p id="subtotal2" class="w-16 text-right text-xs text-gray-800 dark:text-gray-300 font-mono">L.0.00</p>
</div>
<div class="flex items-center gap-2">
<label class="w-12 text-gray-800 dark:text-white text-sm font-medium" for="x5">L.5</label>
<input id="x5" name="x5" class="bill-input form-input flex w-full min-w-0 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 h-10 placeholder:text-gray-400 dark:placeholder-gray-500 px-3 text-sm font-normal leading-normal text-right" placeholder="0" type="number" min="0" value="0" oninput="calcularTotales()"/>
<p id="subtotal5" class="w-16 text-right text-xs text-gray-800 dark:text-gray-300 font-mono">L.0.00</p>
</div>
<div class="flex items-center gap-2">
<label class="w-12 text-gray-800 dark:text-white text-sm font-medium" for="x10">L.10</label>
<input id="x10" name="x10" class="bill-input form-input flex w-full min-w-0 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 h-10 placeholder:text-gray-400 dark:placeholder-gray-500 px-3 text-sm font-normal leading-normal text-right" placeholder="0" type="number" min="0" value="0" oninput="calcularTotales()"/>
<p id="subtotal10" class="w-16 text-right text-xs text-gray-800 dark:text-gray-300 font-mono">L.0.00</p>
</div>
<div class="flex items-center gap-2">
<label class="w-12 text-gray-800 dark:text-white text-sm font-medium" for="x20">L.20</label>
<input id="x20" name="x20" class="bill-input form-input flex w-full min-w-0 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 h-10 placeholder:text-gray-400 dark:placeholder-gray-500 px-3 text-sm font-normal leading-normal text-right" placeholder="0" type="number" min="0" value="0" oninput="calcularTotales()"/>
<p id="subtotal20" class="w-16 text-right text-xs text-gray-800 dark:text-gray-300 font-mono">L.0.00</p>
</div>
<div class="flex items-center gap-2">
<label class="w-12 text-gray-800 dark:text-white text-sm font-medium" for="x50">L.50</label>
<input id="x50" name="x50" class="bill-input form-input flex w-full min-w-0 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 h-10 placeholder:text-gray-400 dark:placeholder-gray-500 px-3 text-sm font-normal leading-normal text-right" placeholder="0" type="number" min="0" value="0" oninput="calcularTotales()"/>
<p id="subtotal50" class="w-16 text-right text-xs text-gray-800 dark:text-gray-300 font-mono">L.0.00</p>
</div>
<div class="flex items-center gap-2">
<label class="w-12 text-gray-800 dark:text-white text-sm font-medium" for="x100">L.100</label>
<input id="x100" name="x100" class="bill-input form-input flex w-full min-w-0 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 h-10 placeholder:text-gray-400 dark:placeholder-gray-500 px-3 text-sm font-normal leading-normal text-right" placeholder="0" type="number" min="0" value="0" oninput="calcularTotales()"/>
<p id="subtotal100" class="w-16 text-right text-xs text-gray-800 dark:text-gray-300 font-mono">L.0.00</p>
</div>
<div class="flex items-center gap-2">
<label class="w-12 text-gray-800 dark:text-white text-sm font-medium" for="x200">L.200</label>
<input id="x200" name="x200" class="bill-input form-input flex w-full min-w-0 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 h-10 placeholder:text-gray-400 dark:placeholder-gray-500 px-3 text-sm font-normal leading-normal text-right" placeholder="0" type="number" min="0" value="0" oninput="calcularTotales()"/>
<p id="subtotal200" class="w-16 text-right text-xs text-gray-800 dark:text-gray-300 font-mono">L.0.00</p>
</div>
<div class="flex items-center gap-2">
<label class="w-12 text-gray-800 dark:text-white text-sm font-medium" for="x500">L.500</label>
<input id="x500" name="x500" class="bill-input form-input flex w-full min-w-0 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 h-10 placeholder:text-gray-400 dark:placeholder-gray-500 px-3 text-sm font-normal leading-normal text-right" placeholder="0" type="number" min="0" value="0" oninput="calcularTotales()"/>
<p id="subtotal500" class="w-16 text-right text-xs text-gray-800 dark:text-gray-300 font-mono">L.0.00</p>
</div>
</div>
</div>
<div class="lg:w-72 flex flex-col">
<h2 class="text-gray-900 dark:text-white text-lg font-bold leading-tight tracking-[-0.015em] pb-3">Resumen</h2>
<div class="flex-grow flex flex-col gap-2">
<div class="flex flex-col gap-0.5 p-2 rounded-lg bg-gray-50 dark:bg-black/20">
<p class="text-gray-500 dark:text-gray-400 text-xs font-medium">Fondo Inicial</p>
<p id="monto-inicial" class="text-gray-900 dark:text-white text-base font-bold">L.<?php echo number_format($fondo_caja, 2); ?></p>
</div>
<div class="flex flex-col gap-0.5 p-2 rounded-lg bg-gray-50 dark:bg-black/20">
<p class="text-gray-500 dark:text-gray-400 text-xs font-medium">Ventas del Día</p>
<p id="ventas-dia" class="text-gray-900 dark:text-white text-base font-bold">L.<?php echo number_format($total_ventas, 2); ?></p>
</div>
<div class="flex flex-col gap-0.5 p-2 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
<p class="text-red-600 dark:text-red-300 text-xs font-medium">Egresos del Día</p>
<p id="egresos-dia" class="text-red-800 dark:text-red-200 text-base font-bold">L.<?php echo number_format($total_egresos, 2); ?></p>
</div>
<div class="flex flex-col gap-0.5 p-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
<p class="text-blue-600 dark:text-blue-300 text-xs font-medium">Total Esperado</p>
<p id="total-esperado" class="text-blue-800 dark:text-blue-200 text-lg font-bold">L.<?php echo number_format($efectivo_esperado, 2); ?></p>
</div>
<div class="flex flex-col gap-0.5 p-2 rounded-lg bg-gray-50 dark:bg-black/20 border-2 border-gray-300 dark:border-gray-600">
<p class="text-gray-500 dark:text-gray-400 text-xs font-medium">Total Contado</p>
<p id="totalContado" class="text-gray-900 dark:text-white text-xl font-bold">L.0.00</p>
</div>
<div id="cardDiferencia" class="flex flex-col gap-0.5 p-2 rounded-lg bg-gray-100 dark:bg-black/20">
<p id="labelDiferencia" class="text-gray-500 dark:text-gray-400 text-xs font-medium">Diferencia</p>
<p id="diferencia" class="text-gray-900 dark:text-white text-xl font-bold">L.0.00</p>
</div>
</div>
<div class="mt-4 flex flex-col gap-2">
<button type="submit" name="registrar_arqueo" class="flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 bg-primary text-white text-sm font-bold leading-normal tracking-wide hover:bg-primary/90">Registrar Arqueo</button>
<a href="caja_al_dia.php" class="flex w-full cursor-pointer items-center justify-center overflow-hidden rounded-lg h-10 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 gap-2 text-sm font-bold leading-normal tracking-wide hover:bg-gray-300 dark:hover:bg-gray-600">Volver a Caja</a>
</div>
</div>
</div>

<!-- Modal OTP Arqueo (hidden by default) -->
<div id="otpModalArqueo" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111722] rounded-xl shadow-2xl max-w-md w-full border-2 border-yellow-500 dark:border-yellow-400">
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
                    ⚠️ <strong>Hay diferencia en el arqueo de caja</strong>
                </p>
                <p id="otpMensajeDiferenciaArqueo" class="text-lg font-black text-red-600 dark:text-red-400 mt-2 text-center"></p>
            </div>
            
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <p class="text-xs text-gray-600 dark:text-gray-400">
                    📧 Se ha enviado un código OTP a los correos autorizados
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                    ⏰ El código expira en 20 minutos
                </p>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Motivo de la Diferencia *
                </label>
                <select id="razonSelectArqueo" class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#192233] text-gray-800 dark:text-white focus:ring-2 focus:ring-primary p-3" onchange="toggleOtraRazonArqueo()">
                    <option value="">-- Selecciona un motivo --</option>
                    <option value="Diferencia por robos/pérdidas">Diferencia por robos/pérdidas</option>
                    <option value="Gastos/ingresos no registrados">Gastos/ingresos no registrados</option>
                    <option value="Error en conteo">Error en conteo</option>
                    <option value="Transacciones pendientes">Transacciones pendientes</option>
                    <option value="Otra razón">Otra razón</option>
                </select>
            </div>
            
            <!-- Input para "Otra razón" (oculto por defecto) -->
            <div id="otraRazonContainerArqueo" class="hidden">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Especifica la razón *
                </label>
                <textarea 
                    id="otraRazonInputArqueo" 
                    rows="3"
                    placeholder="Describe la razón de la diferencia..."
                    class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#192233] text-gray-800 dark:text-white focus:ring-2 focus:ring-primary p-3 resize-none"
                ></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Código OTP *
                </label>
                <input 
                    id="otpInputArqueo" 
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
                onclick="cerrarModalOTPArqueo()" 
                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
            >
                Cancelar
            </button>
            <button 
                type="button"
                onclick="validarYProcederArqueo()" 
                class="flex-1 px-4 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition-colors"
            >
                Verificar y Continuar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Arqueo -->
<div id="modalConfirmacionArqueo" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111722] rounded-xl shadow-2xl max-w-md w-full border border-gray-300 dark:border-gray-700">
        <!-- Header -->
        <div class="bg-gradient-to-r from-primary to-blue-600 text-white p-6 rounded-t-xl">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-4xl">check_circle</span>
                <div>
                    <h3 class="text-xl font-black">Confirmar Arqueo de Caja</h3>
                    <p class="text-sm opacity-90">Revisa los datos antes de continuar</p>
                </div>
            </div>
        </div>
        
        <!-- Body -->
        <div class="p-6 space-y-4">
            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">Efectivo Esperado:</span>
                    <span id="confirmEsperado" class="text-gray-900 dark:text-white font-bold text-lg">L.0.00</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400 font-medium">Efectivo Contado:</span>
                    <span id="confirmContado" class="text-gray-900 dark:text-white font-bold text-lg">L.0.00</span>
                </div>
            </div>
            
            <div id="confirmDiferenciaContainer" class="p-4 rounded-lg text-center">
                <div id="confirmDiferencia"></div>
            </div>
            
            <p class="text-sm text-gray-600 dark:text-gray-400 text-center">
                ¿Estás seguro de que deseas registrar este arqueo?
            </p>
        </div>
        
        <!-- Footer -->
        <div class="p-6 bg-gray-50 dark:bg-[#0a0f1a] rounded-b-xl flex gap-3">
            <button 
                type="button"
                onclick="cerrarModalConfirmacion()" 
                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
            >
                Cancelar
            </button>
            <button 
                type="button"
                onclick="confirmarYEnviarArqueo()" 
                class="flex-1 px-4 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition-colors"
            >
                Confirmar Arqueo
            </button>
        </div>
    </div>
</div>

</form>
</div>
</div>
</main>
</div>
</div>
<?php include 'modal_sistema.php'; ?>
</body>
</html>