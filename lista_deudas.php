<?php
// lista_deudas.php (COMPLETO, AUTOSUFICIENTE Y CORREGIDO)
session_start();
date_default_timezone_set('America/Tegucigalpa');

// NOTA: La función strftime() está obsoleta en PHP 8.1+.
// La hemos reemplazado por IntlDateFormatter, que es el método moderno y recomendado.

// Incluye tus funciones si ahí está VerificarSiUsuarioYaInicioSesion()
require_once __DIR__ . '/funciones.php';

// Manejo de errores en desarrollo (puedes desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar sesión
if (!function_exists('VerificarSiUsuarioYaInicioSesion')) {
    // Fallback si no existe la función
    if (empty($_SESSION['usuario'])) {
        header("Location: login.php");
        exit();
    }
} else {
    VerificarSiUsuarioYaInicioSesion();
}

// Conexión DB
 $conexion = new mysqli("localhost", "root", "", "tiendasrey");
if ($conexion->connect_error) {
    http_response_code(500);
    die("Error de conexión: " . $conexion->connect_error);
}
 $conexion->set_charset("utf8mb4");

// Obtener info del usuario logueado (seguro con prepared)
 $usuarioLog = $_SESSION['usuario'] ?? null;
 $Nombre_Completo = '';
 $Perfil = '';
 $Rol = '';
if ($usuarioLog) {
    $stmtUser = $conexion->prepare("SELECT Rol, Usuario, Nombre, Apellido, Email, Celular, Perfil FROM usuarios WHERE Usuario = ?");
    $stmtUser->bind_param("s", $usuarioLog);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    if ($row = $resUser->fetch_assoc()) {
        $Rol = $row['Rol'];
        $Nombre_Completo = trim(($row['Nombre'] ?? '') . ' ' . ($row['Apellido'] ?? ''));
        $Perfil = $row['Perfil'] ?? '';
    } else {
        // Si no se encuentra el usuario en DB, forzar logout
        header("Location: logout.php");
        exit();
    }
    $stmtUser->close();
} else {
    header("Location: login.php");
    exit();
}

// Router AJAX
 $action = $_GET['action'] ?? '';
if ($action === 'detalle') {
    header('Content-Type: application/json');

    $id = $_GET['id'] ?? '';
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID inválido']);
        exit;
    }

    // Obtener nombre del cliente
    $stmtDeuda = $conexion->prepare("SELECT nombreCliente FROM deudas WHERE idDeuda = ?");
    $stmtDeuda->bind_param("s", $id);
    $stmtDeuda->execute();
    $resDeuda = $stmtDeuda->get_result();
    $deuda = $resDeuda->fetch_assoc();
    $stmtDeuda->close();

    // Obtener detalle
    $stmtDetalle = $conexion->prepare("SELECT idDetalle, productoVendido, cantidad, precio FROM deudas_detalle WHERE idDeuda = ?");
    $stmtDetalle->bind_param("s", $id);
    $stmtDetalle->execute();
    $resDetalle = $stmtDetalle->get_result();
    $detalle = [];
    while ($row = $resDetalle->fetch_assoc()) {
        $detalle[] = $row;
    }
    $stmtDetalle->close();

    echo json_encode([
        'success' => true,
        'nombreCliente' => $deuda['nombreCliente'] ?? 'Desconocido',
        'detalle' => $detalle
    ]);
    exit;
}

if ($action === 'actualizar') {
    header('Content-Type: application/json');

    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') === false) {
        echo json_encode(['success' => false, 'message' => 'Content-Type inválido. Usa application/json']);
        exit;
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        echo json_encode(['success' => false, 'message' => 'JSON inválido']);
        exit;
    }

    $idDeuda = $data['idDeuda'] ?? '';
    $items = $data['items'] ?? [];
    if (!$idDeuda || !is_array($items) || count($items) === 0) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
        exit;
    }

    $conexion->begin_transaction();
    try {
        // Actualizar detalle
        $stmtUpdate = $conexion->prepare("UPDATE deudas_detalle SET cantidad = ?, precio = ? WHERE idDetalle = ? AND idDeuda = ?");
        foreach ($items as $item) {
            $idDetalle = intval($item['idDetalle']);
            $cantidad = intval($item['cantidad']);
            $precio = floatval($item['precio']);
            if ($idDetalle <= 0 || $cantidad <= 0 || $precio < 0) {
                throw new Exception("Datos de detalle inválidos");
            }
            $stmtUpdate->bind_param("idis", $cantidad, $precio, $idDetalle, $idDeuda);
            if (!$stmtUpdate->execute()) {
                throw new Exception("Error al actualizar detalle: " . $stmtUpdate->error);
            }
        }
        $stmtUpdate->close();

        // Recalcular total de la deuda
        $stmtSum = $conexion->prepare("SELECT SUM(cantidad * precio) AS total FROM deudas_detalle WHERE idDeuda = ?");
        $stmtSum->bind_param("s", $idDeuda);
        $stmtSum->execute();
        $resSum = $stmtSum->get_result();
        $totalRow = $resSum->fetch_assoc();
        $stmtSum->close();

        $nuevoMonto = number_format(floatval($totalRow['total'] ?? 0), 2, '.', '');

        // Actualizar monto en deudas
        $stmtMonto = $conexion->prepare("UPDATE deudas SET monto = ? WHERE idDeuda = ?");
        $stmtMonto->bind_param("ss", $nuevoMonto, $idDeuda);
        if (!$stmtMonto->execute()) {
            throw new Exception("Error al actualizar monto de deuda: " . $stmtMonto->error);
        }
        $stmtMonto->close();

        $conexion->commit();
        echo json_encode(['success' => true, 'message' => 'Deuda actualizada correctamente', 'nuevoMonto' => $nuevoMonto]);
    } catch (Exception $e) {
        $conexion->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Listar deudas pendientes
 $deudas = [];
 $stmtList = $conexion->prepare("SELECT idDeuda, nombreCliente, monto, fechaRegistro, estado 
                                FROM deudas 
                                WHERE LOWER(estado) = 'pendiente' 
                                ORDER BY fechaRegistro DESC");
 $stmtList->execute();
 $resList = $stmtList->get_result();
while ($row = $resList->fetch_assoc()) {
    $deudas[] = $row;
}
 $stmtList->close();

// Permisos (si los usas)
 $rol_usuario = strtolower($Rol);
?>
<!DOCTYPE html>
<html class="dark" lang="es">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Lista de Créditos</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;900&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet"/>
<script>
  tailwind.config = {
    darkMode: "class",
    theme: {
      extend: {
        colors: {
          "primary": "#137fec",
          "background-light": "#f6f7f8",
          "background-dark": "#101922",
        },
        fontFamily: {
          "display": ["Inter", "sans-serif"]
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
    font-variation-settings:'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24;
  }
  .material-symbols-outlined.fill { font-variation-settings:'FILL' 1; }
</style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display">
<div class="flex min-h-screen w-full">
  <?php include 'menu_lateral.php'; ?>

<!-- CORRECCIÓN: Eliminado el div extra que causaba el problema de diseño -->
<main class="flex-1 p-8">
  <div class="mx-auto max-w-7xl">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-8">
      <div class="flex flex-col gap-2">
        <h1 class="text-white text-4xl font-black leading-tight tracking-[-0.033em]">Gestión de Créditos</h1>
        <p class="text-[#92adc9] text-base font-normal leading-normal">Visualiza, edita y paga créditos pendientes.</p>
      </div>
    </div>

    <!-- Grid de tarjetas de deudas -->
    <?php if (count($deudas) > 0): ?>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($deudas as $deuda): 
          // Formatear fecha
          $timestamp = strtotime($deuda['fechaRegistro']);
          $fechaFormateada = '';

          if (class_exists('IntlDateFormatter')) {
              $formatter = new IntlDateFormatter(
                  'es_HN',
                  IntlDateFormatter::MEDIUM,
                  IntlDateFormatter::NONE,
                  'America/Tegucigalpa',
                  IntlDateFormatter::GREGORIAN,
                  "d 'de' MMM, y"
              );
              $fechaFormateada = ucfirst($formatter->format($timestamp));
          } else {
              $fechaFormateada = date('d M, Y', $timestamp);
          }
          
          // Obtener detalles de productos de esta deuda
          $stmtDetalle = $conexion->prepare("SELECT productoVendido, cantidad, precio FROM deudas_detalle WHERE idDeuda = ?");
          $stmtDetalle->bind_param("s", $deuda['idDeuda']);
          $stmtDetalle->execute();
          $resDetalle = $stmtDetalle->get_result();
          $productos = [];
          $totalProductos = 0;
          while ($prod = $resDetalle->fetch_assoc()) {
              $productos[] = $prod;
              $totalProductos++;
          }
          $stmtDetalle->close();
      ?>
        <!-- Tarjeta de deuda -->
        <div class="group relative overflow-hidden rounded-xl border border-[#324d67] bg-gradient-to-br from-[#192633] to-[#111a22] p-6 transition-all hover:border-blue-500/50 hover:shadow-lg hover:shadow-blue-500/10">
          <!-- Badge de estado -->
          <div class="absolute top-4 right-4">
            <span class="inline-flex items-center gap-1 rounded-full bg-yellow-500/10 px-3 py-1 text-xs font-semibold text-yellow-400 ring-1 ring-yellow-500/20">
              <span class="material-symbols-outlined text-sm">schedule</span>
              Pendiente
            </span>
          </div>

          <!-- Icono de cliente -->
          <div class="mb-4 flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-500/10 ring-2 ring-blue-500/20">
              <span class="material-symbols-outlined text-2xl text-blue-400">person</span>
            </div>
            <div class="flex-1">
              <h3 class="text-lg font-bold text-white line-clamp-1" title="<?php echo htmlspecialchars($deuda['nombreCliente']); ?>">
                <?php echo htmlspecialchars($deuda['nombreCliente']); ?>
              </h3>
              <p class="text-xs text-[#92adc9]">
                <span class="material-symbols-outlined text-xs align-middle">calendar_today</span>
                <?php echo $fechaFormateada; ?>
              </p>
            </div>
          </div>

          <!-- Monto destacado -->
          <div class="mb-4 rounded-lg bg-[#0d1419] p-4 ring-1 ring-[#324d67]">
            <p class="mb-1 text-xs font-medium uppercase tracking-wider text-[#92adc9]">Monto Total</p>
            <p class="text-3xl font-black text-white">
              L <?php echo number_format((float)$deuda['monto'], 2); ?>
            </p>
          </div>

          <!-- Detalles de productos (colapsable) -->
          <div class="mb-4">
            <button 
              onclick="toggleDetalles('deuda-<?php echo $deuda['idDeuda']; ?>')"
              class="flex w-full items-center justify-between rounded-lg bg-[#0d1419] px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-[#324d67] transition-all hover:bg-[#111a22]">
              <span class="flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">shopping_cart</span>
                <?php echo $totalProductos; ?> producto<?php echo $totalProductos != 1 ? 's' : ''; ?>
              </span>
              <span class="material-symbols-outlined text-sm transition-transform" id="icon-deuda-<?php echo $deuda['idDeuda']; ?>">
                expand_more
              </span>
            </button>
            
            <!-- Lista de productos (oculta por defecto) -->
            <div id="deuda-<?php echo $deuda['idDeuda']; ?>" class="hidden mt-2 space-y-2 max-h-48 overflow-y-auto">
              <?php foreach ($productos as $prod): ?>
                <div class="rounded-lg bg-[#0d1419] p-3 ring-1 ring-[#324d67]">
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-white truncate" title="<?php echo htmlspecialchars($prod['productoVendido']); ?>">
                        <?php echo htmlspecialchars($prod['productoVendido']); ?>
                      </p>
                      <p class="text-xs text-[#92adc9] mt-1">
                        Cantidad: <span class="font-semibold"><?php echo $prod['cantidad']; ?></span>
                      </p>
                    </div>
                    <div class="text-right">
                      <p class="text-sm font-bold text-blue-400">
                        L <?php echo number_format((float)$prod['precio'], 2); ?>
                      </p>
                      <p class="text-xs text-[#92adc9]">c/u</p>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Botones de acción -->
          <div class="flex gap-2">
            <button 
              onclick="editarDeuda('<?php echo $deuda['idDeuda']; ?>')" 
              class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-blue-500/10 px-4 py-2.5 text-sm font-semibold text-blue-400 ring-1 ring-blue-500/20 transition-all hover:bg-blue-500/20 hover:ring-blue-500/40">
              <span class="material-symbols-outlined text-sm">edit</span>
              Editar
            </button>
            <a 
              href="pagar_deudas.php?id=<?php echo $deuda['idDeuda']; ?>" 
              class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-green-500/10 px-4 py-2.5 text-sm font-semibold text-green-400 ring-1 ring-green-500/20 transition-all hover:bg-green-500/20 hover:ring-green-500/40">
              <span class="material-symbols-outlined text-sm">payments</span>
              Pagar
            </a>
          </div>

          <!-- Efecto hover decorativo -->
          <div class="absolute inset-0 -z-10 bg-gradient-to-br from-blue-500/5 to-transparent opacity-0 transition-opacity group-hover:opacity-100"></div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <!-- Estado vacío mejorado -->
      <div class="flex min-h-[400px] items-center justify-center rounded-xl border border-[#324d67] bg-gradient-to-br from-[#192633] to-[#111a22] p-12">
        <div class="text-center">
          <div class="mb-6 inline-flex rounded-full bg-green-500/10 p-8 ring-2 ring-green-500/20">
            <span class="material-symbols-outlined text-green-400" style="font-size: 80px;">check_circle</span>
          </div>
          <h3 class="mb-3 text-3xl font-black text-white">¡Todo está al día! 🎉</h3>
          <p class="mb-2 text-lg text-[#92adc9]">No hay créditos pendientes en este momento</p>
          <p class="text-sm text-[#92adc9]/70">Todas las cuentas están saldadas</p>
          <div class="mt-8 inline-flex items-center gap-2 rounded-full bg-green-500/10 px-6 py-3 text-green-400 ring-1 ring-green-500/20">
            <span class="material-symbols-outlined text-sm">verified</span>
            <span class="text-sm font-semibold">Sistema sin deudas activas</span>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</main>

<script>
function toggleDetalles(id) {
  const detalles = document.getElementById(id);
  const icon = document.getElementById('icon-' + id);
  
  if (detalles.classList.contains('hidden')) {
    detalles.classList.remove('hidden');
    icon.style.transform = 'rotate(180deg)';
  } else {
    detalles.classList.add('hidden');
    icon.style.transform = 'rotate(0deg)';
  }
}
</script>


<!-- Modal de edición -->
<div id="modalEditarDeuda" class="fixed inset-0 z-50 hidden bg-black/50 flex items-center justify-center">
  <div class="bg-[#111a22] rounded-xl p-6 w-full max-w-3xl shadow-lg">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-white text-xl font-bold">Editar Deuda</h2>
      <button onclick="cerrarModalEditar()" class="text-[#92adc9] hover:text-white">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <p class="text-white mb-4">Cliente: <span id="nombreClienteEditar" class="text-[#92adc9]"></span></p>
    <table class="min-w-full divide-y divide-[#324d67] mb-4">
      <thead>
        <tr>
          <th class="px-4 py-2 text-left text-sm text-[#92adc9]">Producto</th>
          <th class="px-4 py-2 text-left text-sm text-[#92adc9]">Cantidad</th>
          <th class="px-4 py-2 text-left text-sm text-[#92adc9]">Precio</th>
        </tr>
      </thead>
      <tbody id="tablaProductosEditar" class="divide-y divide-[#324d67]"></tbody>
    </table>
    <div class="flex justify-end gap-4">
      <button onclick="cerrarModalEditar()" class="px-4 py-2 rounded-lg bg-slate-700 text-white">Cancelar</button>
      <button onclick="guardarCambiosDeuda()" class="px-4 py-2 rounded-lg bg-primary text-white">Guardar Cambios</button>
    </div>
  </div>
</div>

<script>
function editarDeuda(idDeuda) {
  fetch('<?php echo basename(__FILE__); ?>?action=detalle&id=' + encodeURIComponent(idDeuda))
    .then(res => res.json())
    .then(data => {
      if (!data.success) { mostrarError('Error al cargar la deuda'); return; }
      document.getElementById('nombreClienteEditar').textContent = data.nombreCliente;
      const tbody = document.getElementById('tablaProductosEditar');
      tbody.innerHTML = '';
      data.detalle.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td class="px-4 py-2 text-white">${item.productoVendido}</td>
          <td class="px-4 py-2">
            <input type="number" min="1" value="${item.cantidad}"
              class="form-input w-20 bg-[#192633] text-white border border-[#324d67]"
              data-id="${item.idDetalle}" data-campo="cantidad">
          </td>
          <td class="px-4 py-2">
            <input type="number" step="0.01" value="${item.precio}"
              class="form-input w-24 bg-[#192633] text-white border border-[#324d67]"
              data-id="${item.idDetalle}" data-campo="precio">
          </td>`;
        tbody.appendChild(row);
      });
      const modal = document.getElementById('modalEditarDeuda');
      modal.dataset.idDeuda = idDeuda;
      modal.classList.remove('hidden');
    })
    .catch(err => {
      console.error(err);
      mostrarError('Error al obtener el detalle de la deuda');
    });
}

function cerrarModalEditar() {
  document.getElementById('modalEditarDeuda').classList.add('hidden');
}

function guardarCambiosDeuda() {
  const modal = document.getElementById('modalEditarDeuda');
  const idDeuda = modal.dataset.idDeuda;
  const inputs = document.querySelectorAll('#tablaProductosEditar input');
  const map = new Map();
  inputs.forEach(input => {
    const idDetalle = input.dataset.id;
    const campo = input.dataset.campo;
    if (!map.has(idDetalle)) map.set(idDetalle, { idDetalle });
    map.get(idDetalle)[campo] = input.value;
  });
  const items = Array.from(map.values());

  fetch('<?php echo basename(__FILE__); ?>?action=actualizar', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ idDeuda, items })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      mostrarInfo('Deuda actualizada correctamente. Nuevo monto: L ' + parseFloat(data.nuevoMonto).toFixed(2));
      cerrarModalEditar();
      location.reload();
    } else {
      mostrarError('Error al actualizar: ' + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    mostrarError('Error al actualizar la deuda');
  });
}
</script>
<?php include 'modal_sistema.php'; ?>
</body>
</html>