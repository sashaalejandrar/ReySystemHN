<?php
session_start();
include 'funciones.php';
ini_set('display_errors', 1);            // 0 en producción, 1 en desarrollo
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
VerificarSiUsuarioYaInicioSesion();
// Conexión a la base de datos
require_once 'db_connect.php';
include_once 'generar_notificaciones.php';
// Verificar el estado de la caja antes de permitir apertura (solo del día de hoy)
 $fecha_hoy = date('Y-m-d');
 $consulta_caja = $conexion->query("SELECT Estado, Fecha FROM caja WHERE DATE(Fecha) = '$fecha_hoy' ORDER BY id DESC LIMIT 1");

if ($consulta_caja && $consulta_caja->num_rows > 0) {
    $caja = $consulta_caja->fetch_assoc();
    $estado_caja = $caja['Estado'];
    
    if ($estado_caja === 'Abierta') {
        $_SESSION['mensaje_caja'] = "La caja ya está abierta hoy. No se puede abrir nuevamente hasta que se cierre.";
        $_SESSION['tipo_mensaje_caja'] = "error";
        header("Location: index.php");
        exit();
    } elseif ($estado_caja === 'Cerrada') {
        $_SESSION['mensaje_caja'] = "La caja ya fue cerrada hoy. No se puede realizar apertura hasta el próximo turno.";
        $_SESSION['tipo_mensaje_caja'] = "warning";
        header("Location: index.php");
        exit();
    }
}
// Si no hay registros del día de hoy o el estado es diferente, puede continuar con la apertura

// Obtener datos del usuario
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
    $Rol = $row['Rol'];
    $Id = $row['Id'];
}

// --- INICIO DE LA LÓGICA DE PERMISOS ---
// Convertimos el rol a minúsculas para hacer la comparación insensible a mayúsculas/minúsculas.
 $rol_usuario = strtolower($Rol);
// --- FIN DE LA LÓGICA DE PERMISOS ---

// Procesar el formulario cuando se envía
 $mensaje = "";
 $tipo_mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_apertura'])) {
    $x500 = intval($_POST['x500']);
    $x200 = intval($_POST['x200']);
    $x100 = intval($_POST['x100']);
    $x50 = intval($_POST['x50']);
    $x20 = intval($_POST['x20']);
    $x10 = intval($_POST['x10']);
    $x5 = intval($_POST['x5']);
    $x2 = intval($_POST['x2']);
    $x1 = intval($_POST['x1']);
    
    // Calcular el total
    $total = ($x500 * 500) + ($x200 * 200) + ($x100 * 100) + ($x50 * 50) + 
             ($x20 * 20) + ($x10 * 10) + ($x5 * 5) + ($x2 * 2) + ($x1 * 1);
    
    // Obtener fecha actual
    $fecha = date('Y-m-d');
    $estado = 'Abierta';
    
    // Nota por defecto
    $nota = "Apertura de caja - " . $Nombre_Completo;
    
    // Agregar razón del faltante si se proporcionó (vía OTP)
    if (isset($_POST['razon_faltante']) && !empty($_POST['razon_faltante'])) {
        $nota .= "\nRazón faltante: " . $_POST['razon_faltante'];
        $nota .= "\nAutorizado vía OTP";
    }
    
    // Insertar en la base de datos
    $sql = "INSERT INTO caja (x1, x2, x5, x10, x20, x50, x100, x200, x500, Total, Nota, Fecha, Estado, monto_inicial, usuario) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiiiiiiiidsssds", $x1, $x2, $x5, $x10, $x20, $x50, $x100, $x200, $x500, $total, $nota, $fecha, $estado, $total, $Usuario);
    
    if ($stmt->execute()) {
        $mensaje = "Apertura de caja registrada exitosamente. Total: L." . number_format($total, 2);
        $tipo_mensaje = "success";
        // Confeti para celebrar el inicio del día!
        $mostrar_confeti = true;
        
        // ✅ SINCRONIZAR LOGROS AUTOMÁTICAMENTE
        if (file_exists('auto_sincronizar_logros.php')) {
            require_once 'auto_sincronizar_logros.php';
            try {
                $logros_desbloqueados = autoSincronizarLogrosUsuario($Usuario);
                if (!empty($logros_desbloqueados)) {
                    $mensaje .= " ¡Has desbloqueado " . count($logros_desbloqueados) . " logro(s)!";
                }
            } catch (Exception $e) {
                error_log("Error al sincronizar logros: " . $e->getMessage());
            }
        }
        
        // Preparar datos para el correo
        $datos_correo = [
            'nombre_usuario' => $Nombre_Completo,
            'rol_usuario' => $Rol,
            'fecha' => $fecha,
            'hora' => date('H:i:s'),
            'total' => $total,
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
            enviarNotificacionAperturaCaja($datos_correo);
            $mensaje .= " Se ha enviado una notificación por correo.";
        } catch (Exception $e) {
            // Si el correo falla, registrar el error pero no mostrarlo al usuario
            error_log("Error al enviar correo de apertura de caja: " . $e->getMessage());
            $mensaje .= " No se pudo enviar la notificación por correo.";
        }
        
        // Redirigir después de 2 segundos
        header("refresh:2;url=caja_al_dia.php");
    } else {
        $mensaje = "Error al registrar la apertura: " . $stmt->error;
        $tipo_mensaje = "error";
    }
    
    $stmt->close();
}

// Función para enviar correo de notificación de apertura de caja
function enviarNotificacionAperturaCaja($datos) {
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
        $mail->Subject = '🔓 Apertura de Caja - ' . $datos['fecha'];
        
        // Calcular diferencia con el monto esperado
        $monto_esperado = 2000;
        $diferencia = $datos['total'] - $monto_esperado;
        
        // Determinar el color y texto de la diferencia
        if ($diferencia > 0) {
            $color_diferencia = '#10b981'; // verde
            $texto_diferencia = 'Sobrante: +L.' . number_format($diferencia, 2);
        } elseif ($diferencia < 0) {
            $color_diferencia = '#ef4444'; // rojo
            $texto_diferencia = 'Faltante: -L.' . number_format(abs($diferencia), 2);
        } else {
            $color_diferencia = '#3b82f6'; // azul
            $texto_diferencia = 'Cuadra Perfecto';
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
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🔓 Apertura de Caja</h1>
                    <p>Se ha realizado una apertura de caja en el sistema</p>
                </div>
                <div class="content">
                    <h2>Información de la Apertura</h2>
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
                            <td colspan="2" style="text-align:right;"><strong>Total de Apertura:</strong></td>
                            <td>L. ' . number_format($datos['total'], 2) . '</td>
                        </tr>
                    </table>
                    
                    <h3 style="margin-top: 30px;">Comparación con Monto Esperado</h3>
                    <table class="info-table">
                        <tr>
                            <th><strong>Monto Esperado</strong></th>
                            <td>L. ' . number_format($monto_esperado, 2) . '</td>
                        </tr>
                        <tr>
                            <th><strong>Monto Real</strong></th>
                            <td>L. ' . number_format($datos['total'], 2) . '</td>
                        </tr>
                        <tr>
                            <th><strong>Diferencia</strong></th>
                            <td class="diferencia">' . $texto_diferencia . '</td>
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
generarNotificacionesStock($conexion, $Id);

// OBTENER NOTIFICACIONES PARA MOSTRAR
$notificaciones_pendientes = obtenerNotificacionesPendientes($conexion, $Id);
$total_notificaciones = contarNotificacionesPendientes($conexion, $Id);
?>
<!DOCTYPE html>
<html class="dark" lang="es"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Apertura de Caja</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&amp;display=swap" rel="stylesheet"/>
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
            borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
          },
        },
      }
</script>
<script>
function calcularTotal() {
    const x500 = parseInt(document.getElementById('x500').value) || 0;
    const x200 = parseInt(document.getElementById('x200').value) || 0;
    const x100 = parseInt(document.getElementById('x100').value) || 0;
    const x50 = parseInt(document.getElementById('x50').value) || 0;
    const x20 = parseInt(document.getElementById('x20').value) || 0;
    const x10 = parseInt(document.getElementById('x10').value) || 0;
    const x5 = parseInt(document.getElementById('x5').value) || 0;
    const x2 = parseInt(document.getElementById('x2').value) || 0;
    const x1 = parseInt(document.getElementById('x1').value) || 0;
    
    const total = (x500 * 500) + (x200 * 200) + (x100 * 100) + (x50 * 50) + 
                  (x20 * 20) + (x10 * 10) + (x5 * 5) + (x2 * 2) + (x1 * 1);
    
    document.getElementById('totalApertura').textContent = 'L.' + total.toFixed(2);
    
    // Calcular diferencia con el monto esperado de L. 2000
    const montoEsperado = 2000;
    const diferencia = total - montoEsperado;
    const elementoDiferencia = document.getElementById('diferencia');
    const labelDiferencia = document.getElementById('labelDiferencia');
    
    if (diferencia > 0) {
        // Sobra dinero
        elementoDiferencia.textContent = '+L.' + diferencia.toFixed(2);
        elementoDiferencia.className = 'text-4xl md:text-5xl font-extrabold text-green-500 dark:text-green-400 mt-2';
        labelDiferencia.textContent = 'Sobrante';
    } else if (diferencia < 0) {
        // Falta dinero
        elementoDiferencia.textContent = '-L.' + Math.abs(diferencia).toFixed(2);
        elementoDiferencia.className = 'text-4xl md:text-5xl font-extrabold text-red-500 dark:text-red-400 mt-2';
        labelDiferencia.textContent = 'Faltante';
    } else {
        // Cuadra exacto
        elementoDiferencia.textContent = 'L.0.00';
        elementoDiferencia.className = 'text-4xl md:text-5xl font-extrabold text-blue-500 dark:text-blue-400 mt-2';
        labelDiferencia.textContent = '✓ Cuadra Perfecto';
    }
}



function validarFormulario() {
    const total = parseFloat(document.getElementById('totalApertura').textContent.replace('L.', ''));
    
    if (total <= 0) {
        mostrarAdvertencia('El total de apertura debe ser mayor a 0');
        return false;
    }
    
    const montoEsperado = 2000;
    const diferencia = total - montoEsperado;
    
    // Verificar si el OTP ya fue validado
    const otpValidado = document.getElementById('otpValidado').value;
    
    // Si hay faltante Y el OTP NO ha sido validado, requerir OTP
    if (diferencia < 0 && otpValidado !== '1') {
        event.preventDefault();
        solicitarOTP(total, montoEsperado);
        return false;
    }
    
    // Si el OTP ya fue validado o no hay faltante, continuar
    // Si sobra o cuadra perfecto, mostrar modal de confirmación
    let mensaje = '¿Confirmar apertura de caja por L.' + total.toFixed(2) + '?';
    let tipoMensaje = 'normal';
    let detalleExtra = '';
    
    if (diferencia > 0) {
        mensaje = '⚠️ SOBRANTE DETECTADO';
        detalleExtra = '+L.' + diferencia.toFixed(2);
        tipoMensaje = 'sobrante';
    } else if (diferencia === 0) {
        mensaje = '✓ MONTO PERFECTO';
        detalleExtra = 'El monto cuadra con los L.2,000 esperados';
        tipoMensaje = 'perfecto';
    }
    
    mostrarModalConfirmacion(total, mensaje, detalleExtra, tipoMensaje);
    return false; // Prevenir submit inmediato
}

// Función para mostrar modal de confirmación personalizado
function mostrarModalConfirmacion(total, mensaje, detalle, tipo) {
    // Actualizar contenido del modal
    document.getElementById('confirmMensaje').textContent = mensaje;
    document.getElementById('confirmTotal').textContent = 'L.' + total.toFixed(2);
    document.getElementById('confirmDetalle').textContent = detalle;
    
    // Cambiar estilos según el tipo
    const header = document.getElementById('confirmHeader');
    const icon = document.getElementById('confirmIcon');
    
    if (tipo === 'sobrante') {
        header.className = 'bg-gradient-to-r from-yellow-500 to-orange-500 text-white p-6 rounded-t-xl';
        icon.textContent = 'warning';
    } else if (tipo === 'perfecto') {
        header.className = 'bg-gradient-to-r from-green-500 to-emerald-500 text-white p-6 rounded-t-xl';
        icon.textContent = 'check_circle';
    } else {
        header.className = 'bg-gradient-to-r from-blue-500 to-primary text-white p-6 rounded-t-xl';
        icon.textContent = 'info';
    }
    
    // Mostrar modal
    document.getElementById('confirmModal').classList.remove('hidden');
}

// Confirmar y enviar formulario
function confirmarApertura() {
    document.getElementById('confirmModal').classList.add('hidden');
    
    // Obtener el formulario
    const form = document.querySelector('form');
    
    // Crear un botón submit temporal (invisible)
    const submitButton = document.createElement('button');
    submitButton.type = 'submit';
    submitButton.name = 'confirmar_apertura';
    submitButton.value = '1';
    submitButton.style.display = 'none';
    form.appendChild(submitButton);
    
    // Desactivar temporalmente la validación
    form.removeAttribute('onsubmit');
    
    // Hacer clic en el botón para enviar el formulario
    submitButton.click();
}

// Cancelar confirmación
function cancelarConfirmacion() {
    document.getElementById('confirmModal').classList.add('hidden');
}

// Solicitar código OTP cuando hay faltante
async function solicitarOTP(montoReal, montoEsperado) {
    try {
        const response = await fetch('generar_otp.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                tipo: 'apertura',
                monto_esperado: montoEsperado,
                monto_real: montoReal,
                usuario: '<?php echo $Nombre_Completo; ?>',
                email: 'admin' // Backend envía a ambos: sashaalejandrar24@gmail.com y jesushernan.ordo@gmail.com
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Mostrar modal OTP
            document.getElementById('otpModal').classList.remove('hidden');
            document.getElementById('otpMensajeFaltante').textContent = 
                'Faltante: L.' + Math.abs(montoReal - montoEsperado).toFixed(2);
        } else {
            mostrarError('Error al generar código OTP: ' + data.mensaje);
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al solicitar código OTP. Intenta nuevamente.');
    }
}

// Verificar OTP y proceder con apertura
// Mostrar/ocultar input de "Otra razón"
function toggleOtraRazon() {
    const select = document.getElementById('razonSelect');
    const container = document.getElementById('otraRazonContainer');
    const input = document.getElementById('otraRazonInput');
    
    if (select.value === 'Otra razón') {
        container.classList.remove('hidden');
        input.required = true;
    } else {
        container.classList.add('hidden');
        input.required = false;
        input.value = '';
    }
}

async function validarYProceder() {
    const codigo = document.getElementById('otpInput').value.trim();
    let razon = document.getElementById('razonSelect').value;
    const otraRazon = document.getElementById('otraRazonInput').value.trim();
    
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
                tipo: 'apertura' 
            })
        });
        
        const data = await response.json();
        
        if (data.success && data.valido) {
            // OTP válido - agregar razón y enviar formulario
            document.getElementById('razonHidden').value = razon;
            document.getElementById('otpValidado').value = '1';
            
            // Cerrar modal
            document.getElementById('otpModal').classList.add('hidden');
            
            // Obtener el formulario
            const form = document.querySelector('form');
            
            // Crear un botón submit temporal (invisible)
            const submitButton = document.createElement('button');
            submitButton.type = 'submit';
            submitButton.name = 'confirmar_apertura';
            submitButton.value = '1';
            submitButton.style.display = 'none';
            form.appendChild(submitButton);
            
            // Desactivar temporalmente la validación
            const originalOnsubmit = form.getAttribute('onsubmit');
            form.removeAttribute('onsubmit');
            
            // Hacer clic en el botón para enviar el formulario
            submitButton.click();
        } else {
            mostrarError(data.mensaje || 'Código OTP inválido o expirado');
            document.getElementById('otpInput').value = '';
            document.getElementById('otpInput').focus();
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarError('Error al verificar código OTP. Intenta nuevamente.');
    }
}

// Cerrar modal OTP
function cerrarModalOTP() {
    document.getElementById('otpModal').classList.add('hidden');
    document.getElementById('otpInput').value = '';
    document.getElementById('razonSelect').selectedIndex = 0;
    toggleOtraRazon(); // Reset "Otra razón" input visibility
}

</script>
<!-- head: incluir Alpine y estilo x-cloak -->
<script defer src="https://unpkg.com/alpinejs@3.12.0/dist/cdn.min.js"></script>
 
<style>
    /* ========================================
   ESTILOS PERSONALIZADOS PARA NOTIFICACIONES
   ======================================== */
 [x-cloak] { display: none !important; }
/* Scrollbar para navegadores webkit (Chrome, Safari, Edge) */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
    margin: 4px 0;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.3);
    border-radius: 10px;
    transition: background 0.3s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.5);
}

/* Tema oscuro */
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.2);
}

.dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.4);
}

/* Para Firefox */
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: rgba(148, 163, 184, 0.3) transparent;
    scroll-behavior: smooth;
}

.dark .custom-scrollbar {
    scrollbar-color: rgba(148, 163, 184, 0.2) transparent;
}

/* Ocultar scrollbar en estado inicial pero mantener funcionalidad */
.custom-scrollbar::-webkit-scrollbar-thumb {
    opacity: 0;
    transition: opacity 0.3s ease, background 0.3s ease;
}

.custom-scrollbar:hover::-webkit-scrollbar-thumb {
    opacity: 1;
}

/* Animación para las notificaciones */
@keyframes slideInNotification {
    from {
        opacity: 0;
        transform: translateX(10px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.notification-item {
    animation: slideInNotification 0.3s ease-out;
}
/* fallback si tu tailwind no creó bg-surface-light */
.bg-surface-light { background-color: #000000ff !important; }
.dark .bg-surface-dark { background-color: #ffffffff !important; }

</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-gray-800 dark:text-gray-200">
<div class="relative flex h-auto min-h-screen w-full flex-col">
<div class="flex flex-1">
<!-- SideNavBar -->
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
<!-- SISTEMA DE NOTIFICACIONES REYSYSTEM -->
<?php include 'notificaciones_component.php'; ?>

<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10" data-alt="Avatar de usuario" style='background-image: url("<?php echo $Perfil;?>");'></div>
</div>
</header>
<div class="p-6 md:p-10">
<div class="mx-auto max-w-4xl">
<?php if ($mensaje): ?>
<div class="mb-6 p-4 rounded-lg <?php 
    if ($tipo_mensaje === 'success') echo 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
    elseif ($tipo_mensaje === 'error') echo 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
?>">
    <?php echo $mensaje; ?>
</div>

<?php 
// Mostrar confeti si se registró exitosamente
if (isset($mostrar_confeti) && $mostrar_confeti) {
    echo '<script>setTimeout(() => { if(typeof confetti !== "undefined") confetti({particleCount: 150, spread: 90, origin: { y: 0.6 }}); }, 500);</script>';
}
?>
<?php endif; ?>

<div class="flex flex-wrap justify-between gap-3 mb-8">
<div class="flex min-w-72 flex-col gap-2">
<p class="text-gray-900 dark:text-white text-4xl font-black leading-tight tracking-[-0.033em]">Apertura de Caja</p>
<p class="text-gray-500 dark:text-[#92a4c9] text-base font-normal leading-normal">Introduce la cantidad de billetes y monedas para el conteo inicial.</p>
</div>
</div>
<form method="POST" action="apertura_caja.php" onsubmit="return validarFormulario();">
<!-- Hidden fields for OTP -->
<input type="hidden" name="razon_faltante" id="razonHidden" value="" />
<input type="hidden" name="otp_validado" id="otpValidado" value="0" />

<div class="bg-white dark:bg-[#111722] rounded-xl shadow-sm p-6 md:p-8">
<div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
<label class="flex flex-col">
<p class="text-gray-800 dark:text-white text-base font-medium leading-normal pb-2">Billetes de L. 1</p>
<input id="x1" name="x1" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-[#324467] bg-background-light dark:bg-[#192233] h-14 placeholder:text-gray-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Cantidad" type="number" value="0" min="0" oninput="calcularTotal()"/>
</label>
<label class="flex flex-col">
<p class="text-gray-800 dark:text-white text-base font-medium leading-normal pb-2">Billetes de L. 2</p>
<input id="x2" name="x2" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-[#324467] bg-background-light dark:bg-[#192233] h-14 placeholder:text-gray-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Cantidad" type="number" value="0" min="0" oninput="calcularTotal()"/>
</label>
<label class="flex flex-col">
<p class="text-gray-800 dark:text-white text-base font-medium leading-normal pb-2">Billetes de L. 5</p>
<input id="x5" name="x5" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-[#324467] bg-background-light dark:bg-[#192233] h-14 placeholder:text-gray-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Cantidad" type="number" value="0" min="0" oninput="calcularTotal()"/>
</label>
<label class="flex flex-col">
<p class="text-gray-800 dark:text-white text-base font-medium leading-normal pb-2">Billetes de L. 10</p>
<input id="x10" name="x10" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-[#324467] bg-background-light dark:bg-[#192233] h-14 placeholder:text-gray-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Cantidad" type="number" value="0" min="0" oninput="calcularTotal()"/>
</label>
<label class="flex flex-col">
<p class="text-gray-800 dark:text-white text-base font-medium leading-normal pb-2">Billetes de L. 20</p>
<input id="x20" name="x20" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-[#324467] bg-background-light dark:bg-[#192233] h-14 placeholder:text-gray-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Cantidad" type="number" value="0" min="0" oninput="calcularTotal()"/>
</label>
<label class="flex flex-col">
<p class="text-gray-800 dark:text-white text-base font-medium leading-normal pb-2">Billetes de L. 50</p>
<input id="x50" name="x50" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-[#324467] bg-background-light dark:bg-[#192233] h-14 placeholder:text-gray-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Cantidad" type="number" value="0" min="0" oninput="calcularTotal()"/>
</label>
<label class="flex flex-col">
<p class="text-gray-800 dark:text-white text-base font-medium leading-normal pb-2">Billetes de L. 100</p>
<input id="x100" name="x100" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-[#324467] bg-background-light dark:bg-[#192233] h-14 placeholder:text-gray-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Cantidad" type="number" value="0" min="0" oninput="calcularTotal()"/>
</label>
<label class="flex flex-col">
<p class="text-gray-800 dark:text-white text-base font-medium leading-normal pb-2">Billetes de L. 200</p>
<input id="x200" name="x200" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-[#324467] bg-background-light dark:bg-[#192233] h-14 placeholder:text-gray-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Cantidad" type="number" value="0" min="0" oninput="calcularTotal()"/>
</label>
<label class="flex flex-col">
<p class="text-gray-800 dark:text-white text-base font-medium leading-normal pb-2">Billetes de L. 500</p>
<input id="x500" name="x500" class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-lg text-gray-800 dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-gray-300 dark:border-[#324467] bg-background-light dark:bg-[#192233] h-14 placeholder:text-gray-400 dark:placeholder:text-[#92a4c9] p-[15px] text-base font-normal leading-normal" placeholder="Cantidad" type="number" value="0" min="0" oninput="calcularTotal()"/>
</label>
</div>
<div class="mt-8 border-t border-gray-200 dark:border-gray-700 pt-6">
<div class="flex flex-col sm:flex-row items-center justify-center text-center gap-6 sm:gap-12">
<div class="flex-1">
<p class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total de Apertura de Caja</p>
<p id="totalApertura" class="text-4xl md:text-5xl font-extrabold text-gray-900 dark:text-white mt-2">L.0.00</p>
</div>
<div class="h-16 w-px bg-gray-200 dark:bg-gray-700 hidden sm:block"></div>
<div class="flex-1">
<p id="labelDiferencia" class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Diferencia</p>
<p id="diferencia" class="text-4xl md:text-5xl font-extrabold text-gray-500 dark:text-gray-400 mt-2">L.0.00</p>
<p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Monto esperado: L.2,000.00</p>
</div>
</div>
</div>
<div class="mt-8 flex flex-col sm:flex-row justify-end gap-3">
<a href="caja_al_dia.php" class="flex items-center justify-center rounded-lg h-12 px-6 text-base font-bold bg-gray-200 dark:bg-[#232f48] text-gray-800 dark:text-white hover:bg-gray-300 dark:hover:bg-[#324467] transition-colors">Cancelar</a>
<button type="submit" name="confirmar_apertura" class="flex items-center justify-center rounded-lg h-12 px-6 text-base font-bold bg-primary text-white hover:bg-primary/90 transition-colors">Confirmar Apertura</button>
</div>
</div>

<!-- Modal OTP (hidden by default) -->
<div id="otpModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
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
                    ⚠️ <strong>El monto de apertura es menor al esperado</strong>
                </p>
                <p id="otpMensajeFaltante" class="text-lg font-black text-red-600 dark:text-red-400 mt-2 text-center"></p>
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
                <select id="razonSelect" class="form-select w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-[#192233] text-gray-800 dark:text-white focus:ring-2 focus:ring-primary p-3" onchange="toggleOtraRazon()">
                    <option value="">-- Selecciona un motivo --</option>
                    <option value="Faltante por robos/pérdidas">Faltante por robos/pérdidas</option>
                    <option value="Gastos previos no registrados">Gastos previos no registrados</option>
                    <option value="Error en conteo inicial">Error en conteo inicial</option>
                    <option value="Retiro no autorizado">Retiro no autorizado</option>
                    <option value="Otra razón">Otra razón</option>
                </select>
            </div>
            
            <!-- Input para "Otra razón" (oculto por defecto) -->
            <div id="otraRazonContainer" class="hidden">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Especifica la razón *
                </label>
                <textarea 
                    id="otraRazonInput" 
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
                    id="otpInput" 
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
                onclick="cerrarModalOTP()" 
                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
            >
                Cancelar
            </button>
            <button 
                type="button"
                onclick="validarYProceder()" 
                class="flex-1 px-4 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition-colors"
            >
                Verificar y Continuar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Confirmación de Apertura -->
<div id="confirmModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-[#111722] rounded-xl shadow-2xl max-w-md w-full">
        <!-- Header -->
        <div id="confirmHeader" class="bg-gradient-to-r from-blue-500 to-primary text-white p-6 rounded-t-xl">
            <div class="flex items-center gap-3">
                <span id="confirmIcon" class="material-symbols-outlined text-4xl">info</span>
                <div>
                    <h3 id="confirmMensaje" class="text-xl font-black">Confirmar Apertura</h3>
                    <p class="text-sm opacity-90 font-medium">Verifica los datos antes de continuar</p>
                </div>
            </div>
        </div>
        
        <!-- Body -->
        <div class="p-6 space-y-4">
            <div class="bg-gray-50 dark:bg-[#192233] rounded-lg p-4 text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Total de Apertura</p>
                <p id="confirmTotal" class="text-4xl font-black text-gray-900 dark:text-white">L.0.00</p>
            </div>
            
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-center">
                <p id="confirmDetalle" class="text-sm text-gray-700 dark:text-gray-300 font-medium"></p>
            </div>
            
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                ¿Deseas confirmar la apertura de caja con este monto?
            </p>
        </div>
        
        <!-- Footer -->
        <div class="p-6 bg-gray-50 dark:bg-[#0a0f1a] rounded-b-xl flex gap-3">
            <button 
                type="button"
                onclick="cancelarConfirmacion()" 
                class="flex-1 px-4 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg font-medium hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors"
            >
                Cancelar
            </button>
            <button 
                type="button"
                onclick="confirmarApertura()" 
                class="flex-1 px-4 py-3 bg-primary text-white rounded-lg font-bold hover:bg-primary/90 transition-colors"
            >
                ✓ Confirmar Apertura
            </button>
        </div>
    </div>
</div>

</form>
</div>
</div>
</main>
</div>
<?php include 'modal_sistema.php'; ?>
</body>
<script>
    marcarTodasLeidas() {
            fetch('marcar_notificaciones_leidas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    mostrarError('Error al marcar notificaciones: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Error:', err);
                mostrarError('Error al procesar la solicitud');
            });
        },

</script>
</html>