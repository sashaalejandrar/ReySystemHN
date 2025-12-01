<?php
/**
 * Script para sincronizar el progreso de logros de usuarios existentes
 * Ejecutar una vez para usuarios que ya tienen actividad pero no tienen registros de logros
 */

include 'funciones.php';
require_once 'db_connect.php';
require_once 'verificar_logros.php';

VerificarSiUsuarioYaInicioSesion();

// Verificar que sea admin
if (!isset($_SESSION['usuario'])) {
    die('Debes iniciar sesión');
}

$resultado = $conexion->query("SELECT Rol FROM usuarios WHERE Usuario = '" . $_SESSION['usuario'] . "'");
$row = $resultado->fetch_assoc();
if (strtolower($row['Rol']) !== 'admin') {
    die('Solo administradores pueden ejecutar este script');
}

echo "<!DOCTYPE html>";
echo "<html><head><title>Sincronizar Progreso de Logros</title>";
echo "<style>body { font-family: Arial; padding: 20px; background: #f5f5f5; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; } .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }</style>";
echo "</head><body><div class='container'>";
echo "<h1>🏆 Sincronización de Progreso de Logros</h1>";

// Obtener todos los usuarios (sin filtro de activo porque esa columna no existe)
$usuarios_query = "SELECT DISTINCT Usuario FROM usuarios";
$usuarios_result = $conexion->query($usuarios_query);

if (!$usuarios_result) {
    echo "<p class='error'>❌ Error al obtener usuarios: " . $conexion->error . "</p>";
    die();
}

echo "<p class='info'>👥 Usuarios encontrados: " . $usuarios_result->num_rows . "</p>";

// Obtener todos los logros activos
$logros_query = "SELECT id, nombre, tipo_condicion, valor_objetivo FROM logros WHERE activo = 1";
$logros_result = $conexion->query($logros_query);

if (!$logros_result) {
    echo "<p class='error'>❌ Error al obtener logros: " . $conexion->error . "</p>";
    die();
}

$logros = [];
while ($logro = $logros_result->fetch_assoc()) {
    $logros[] = $logro;
}

echo "<p class='info'>🏅 Logros activos: " . count($logros) . "</p>";

if (count($logros) == 0) {
    echo "<p class='warning'>⚠️ No hay logros activos. Ejecuta primero <a href='inicializar_logros.php'>inicializar_logros.php</a></p>";
    echo "</div></body></html>";
    die();
}

echo "<hr>";

$total_inicializados = 0;
$total_actualizados = 0;
$total_completados = 0;

// Para cada usuario, verificar y crear/actualizar registros de logros
while ($usuario = $usuarios_result->fetch_assoc()) {
    $username = $usuario['Usuario'];
    echo "<h3>👤 Usuario: <strong>$username</strong></h3>";
    
    foreach ($logros as $logro) {
        // Verificar si ya existe el registro
        $check_query = "SELECT * FROM usuarios_logros WHERE usuario = ? AND logro_id = ?";
        $stmt = $conexion->prepare($check_query);
        $stmt->bind_param("si", $username, $logro['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $existe = $result->num_rows > 0;
        $registro_existente = $result->fetch_assoc();
        $stmt->close();
        
        // Calcular progreso actual
        $progreso_actual = calcularProgreso($username, $logro['tipo_condicion'], $conexion);
        $completado = ($progreso_actual >= $logro['valor_objetivo']) ? 1 : 0;
        
        if (!$existe) {
            // Insertar registro nuevo
            $fecha_desbloqueo = $completado ? date('Y-m-d H:i:s') : null;
            $insert_query = "INSERT INTO usuarios_logros (usuario, logro_id, progreso_actual, completado, fecha_desbloqueo) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($insert_query);
            $stmt->bind_param("siiis", $username, $logro['id'], $progreso_actual, $completado, $fecha_desbloqueo);
            
            if ($stmt->execute()) {
                $total_inicializados++;
                if ($completado) {
                    $total_completados++;
                    echo "<p class='success'>✅ <strong>{$logro['nombre']}</strong> - ¡COMPLETADO! ({$progreso_actual}/{$logro['valor_objetivo']})</p>";
                } else {
                    echo "<p class='info'>➕ Inicializado: <strong>{$logro['nombre']}</strong> - Progreso: {$progreso_actual}/{$logro['valor_objetivo']}</p>";
                }
            } else {
                echo "<p class='error'>❌ Error al inicializar: {$logro['nombre']} - " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            // Actualizar progreso existente
            $fecha_desbloqueo = $completado ? date('Y-m-d H:i:s') : null;
            $update_query = "UPDATE usuarios_logros SET progreso_actual = ?, completado = ?, fecha_desbloqueo = ? WHERE usuario = ? AND logro_id = ?";
            $stmt = $conexion->prepare($update_query);
            $stmt->bind_param("iissi", $progreso_actual, $completado, $fecha_desbloqueo, $username, $logro['id']);
            
            if ($stmt->execute()) {
                $total_actualizados++;
                if ($completado && !$registro_existente['completado']) {
                    $total_completados++;
                    echo "<p class='success'>🎉 <strong>{$logro['nombre']}</strong> - ¡RECIÉN COMPLETADO! ({$progreso_actual}/{$logro['valor_objetivo']})</p>";
                } elseif ($completado) {
                    echo "<p class='success'>✅ <strong>{$logro['nombre']}</strong> - Ya completado ({$progreso_actual}/{$logro['valor_objetivo']})</p>";
                } else {
                    echo "<p class='info'>🔄 Actualizado: <strong>{$logro['nombre']}</strong> - Progreso: {$progreso_actual}/{$logro['valor_objetivo']}</p>";
                }
            } else {
                echo "<p class='error'>❌ Error al actualizar: {$logro['nombre']}</p>";
            }
            $stmt->close();
        }
    }
    
    echo "<hr>";
}

echo "<h2 class='success'>✅ Sincronización completada exitosamente</h2>";
echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
echo "<p><strong>📊 Resumen:</strong></p>";
echo "<ul>";
echo "<li>🆕 Logros inicializados: <strong>$total_inicializados</strong></li>";
echo "<li>🔄 Logros actualizados: <strong>$total_actualizados</strong></li>";
echo "<li>🏆 Logros completados: <strong>$total_completados</strong></li>";
echo "</ul>";
echo "</div>";
echo "<p><a href='logros.php' style='background: #1152d4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Ver Logros</a></p>";
echo "</div></body></html>";

$conexion->close();
?>
