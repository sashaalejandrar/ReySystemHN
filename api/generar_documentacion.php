<?php
/**
 * API para generar documentación automática con Mixtral AI
 */

session_start();
require_once '../config_ai.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// Verificar que sea admin
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$stmt = $conexion->prepare("SELECT Rol FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || strtolower($user['Rol']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Solo administradores pueden generar documentación']);
    $conexion->close();
    exit;
}

$action = $_POST['action'] ?? 'scan';

/**
 * Escanea archivos PHP del sistema
 */
function escanearModulos() {
    $baseDir = dirname(__DIR__);
    $modulos = [];
    
    // Directorios a excluir
    $excluir = ['vendor', 'node_modules', 'backups', 'uploads', 'fpdf', 'tcpdf', '.git', 'sql'];
    
    $archivos = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($archivos as $archivo) {
        if ($archivo->isFile() && $archivo->getExtension() === 'php') {
            $ruta = $archivo->getPathname();
            
            // Verificar si está en directorio excluido
            $excluido = false;
            foreach ($excluir as $dir) {
                if (strpos($ruta, DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR) !== false) {
                    $excluido = true;
                    break;
                }
            }
            
            if (!$excluido) {
                $rutaRelativa = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $ruta);
                
                // Solo archivos principales (no includes, no config)
                if (!preg_match('/(config|funciones|db_connect|pwa-head|modal_sistema)\.php$/i', $rutaRelativa)) {
                    $modulos[] = [
                        'nombre' => basename($ruta, '.php'),
                        'ruta' => $rutaRelativa,
                        'ruta_completa' => $ruta
                    ];
                }
            }
        }
    }
    
    return $modulos;
}

/**
 * Genera documentación con Mixtral AI (optimizado)
 */
function generarDocumentacionIA($modulo) {
    $contenido = file_get_contents($modulo['ruta_completa']);
    
    // Extraer solo las primeras líneas relevantes (comentarios y estructura)
    $lineas = explode("\n", $contenido);
    $contenidoReducido = '';
    $lineCount = 0;
    
    foreach ($lineas as $linea) {
        // Incluir comentarios, declaraciones de clase/función, y primeras 100 líneas
        if ($lineCount < 100 || 
            preg_match('/^(\/\/|\/\*|\*|class|function|public|private|protected)/i', trim($linea))) {
            $contenidoReducido .= $linea . "\n";
        }
        $lineCount++;
        
        // Limitar a 3000 caracteres máximo
        if (strlen($contenidoReducido) > 3000) {
            break;
        }
    }
    
    // Prompt simplificado y más directo
    $prompt = "Analiza este código PHP y genera documentación en formato JSON.\n\n";
    $prompt .= "Archivo: {$modulo['nombre']}.php\n";
    $prompt .= "Ruta: {$modulo['ruta']}\n\n";
    $prompt .= "Código:\n```php\n{$contenidoReducido}\n```\n\n";
    $prompt .= "Responde SOLO con este JSON (sin markdown, sin explicaciones):\n";
    $prompt .= "{\n";
    $prompt .= '  "nombre_modulo": "Nombre descriptivo",'."\n";
    $prompt .= '  "categoria": "Ventas|Inventario|Caja|Reportes|Usuarios|Administración|IA|SAR|Otros",'."\n";
    $prompt .= '  "descripcion": "Breve descripción de 1-2 líneas",'."\n";
    $prompt .= '  "proposito": "Para qué sirve",'."\n";
    $prompt .= '  "como_usar": "Pasos básicos",'."\n";
    $prompt .= '  "ejemplos": "Ejemplo de uso",'."\n";
    $prompt .= '  "permisos_requeridos": "admin|cajero|todos"'."\n";
    $prompt .= "}";
    
    $messages = [
        ['role' => 'system', 'content' => 'Eres un asistente que genera documentación técnica en JSON. Responde SOLO con JSON válido, sin texto adicional.'],
        ['role' => 'user', 'content' => $prompt]
    ];
    
    try {
        $result = callAIWithFallback($messages, 0.3); // Temperatura baja para respuestas más consistentes
        
        if ($result['success']) {
            $content = trim($result['content']);
            
            // Limpiar markdown si existe
            $content = preg_replace('/```json\s*/i', '', $content);
            $content = preg_replace('/```\s*$/i', '', $content);
            $content = trim($content);
            
            // Intentar parsear JSON
            $json = json_decode($content, true);
            
            if ($json && isset($json['nombre_modulo'])) {
                $json['provider'] = $result['provider'];
                return $json;
            }
            
            // Si falla el parsing, crear estructura básica
            return crearDocumentacionBasica($modulo, $content);
        }
    } catch (Exception $e) {
        error_log("Error generando documentación para {$modulo['nombre']}: " . $e->getMessage());
    }
    
    return crearDocumentacionBasica($modulo, "Error al generar con IA");
}

/**
 * Crea documentación básica cuando la IA falla
 */
function crearDocumentacionBasica($modulo, $contenido = '') {
    $nombre = ucfirst(str_replace(['_', '-'], ' ', $modulo['nombre']));
    
    // Detectar categoría por nombre de archivo
    $categoria = 'Otros';
    $descripcionCategoria = '';
    
    if (preg_match('/(venta|ventas|cobro)/i', $modulo['nombre'])) {
        $categoria = 'Ventas';
        $descripcionCategoria = 'gestionar las ventas y transacciones';
    } elseif (preg_match('/(inventario|stock|producto)/i', $modulo['nombre'])) {
        $categoria = 'Inventario';
        $descripcionCategoria = 'administrar el inventario y productos';
    } elseif (preg_match('/(caja|apertura|cierre)/i', $modulo['nombre'])) {
        $categoria = 'Caja';
        $descripcionCategoria = 'controlar el flujo de caja';
    } elseif (preg_match('/(reporte|dashboard|analytic)/i', $modulo['nombre'])) {
        $categoria = 'Reportes';
        $descripcionCategoria = 'visualizar reportes y estadísticas';
    } elseif (preg_match('/(usuario|user|perfil)/i', $modulo['nombre'])) {
        $categoria = 'Usuarios';
        $descripcionCategoria = 'gestionar usuarios del sistema';
    } elseif (preg_match('/(admin|config|sistema)/i', $modulo['nombre'])) {
        $categoria = 'Administración';
        $descripcionCategoria = 'administrar configuraciones del sistema';
    } elseif (preg_match('/(ia|ai|nova|diagnostico)/i', $modulo['nombre'])) {
        $categoria = 'IA';
        $descripcionCategoria = 'utilizar funciones de inteligencia artificial';
    } elseif (preg_match('/(sar|fiscal|impuesto)/i', $modulo['nombre'])) {
        $categoria = 'SAR';
        $descripcionCategoria = 'generar reportes fiscales';
    } else {
        $descripcionCategoria = 'realizar operaciones del sistema';
    }
    
    // Crear descripción y propósito más humanos
    $descripcion = "Módulo del sistema ReySystem para {$descripcionCategoria}.";
    
    $proposito = "Este módulo te permite {$descripcionCategoria} de manera eficiente. ";
    $proposito .= "Es parte fundamental del sistema ReySystem y está diseñado para facilitar las operaciones diarias del negocio.";
    
    $comoUsar = "**Pasos para usar este módulo:**\n\n";
    $comoUsar .= "1. Accede al módulo desde el menú lateral\n";
    $comoUsar .= "2. Explora las opciones disponibles en la interfaz\n";
    $comoUsar .= "3. Utiliza los botones y formularios para realizar las acciones necesarias\n";
    $comoUsar .= "4. Guarda los cambios cuando sea necesario\n\n";
    $comoUsar .= "_Nota: Esta documentación fue generada automáticamente. Para más detalles, consulta con el administrador del sistema._";
    
    $ejemplos = "**Casos de uso comunes:**\n\n";
    $ejemplos .= "- Operaciones diarias relacionadas con {$descripcionCategoria}\n";
    $ejemplos .= "- Consulta de información y reportes\n";
    $ejemplos .= "- Gestión y actualización de datos\n\n";
    $ejemplos .= "_Esta sección será actualizada con ejemplos más específicos próximamente._";
    
    return [
        'nombre_modulo' => $nombre,
        'categoria' => $categoria,
        'descripcion' => $descripcion,
        'proposito' => $proposito,
        'como_usar' => $comoUsar,
        'ejemplos' => $ejemplos,
        'permisos_requeridos' => 'admin',
        'provider' => 'fallback'
    ];
}

/**
 * Guarda documentación en la base de datos
 */
function guardarDocumentacion($modulo, $doc, $conexion, $usuario) {
    $stmt = $conexion->prepare("
        INSERT INTO documentacion_modulos 
        (nombre_modulo, ruta_archivo, categoria, descripcion, proposito, como_usar, ejemplos, permisos_requeridos, creado_por, generado_por_ia)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
        nombre_modulo = VALUES(nombre_modulo),
        categoria = VALUES(categoria),
        descripcion = VALUES(descripcion),
        proposito = VALUES(proposito),
        como_usar = VALUES(como_usar),
        ejemplos = VALUES(ejemplos),
        permisos_requeridos = VALUES(permisos_requeridos),
        version = version + 1
    ");
    
    $stmt->bind_param("sssssssss",
        $doc['nombre_modulo'],
        $modulo['ruta'],
        $doc['categoria'],
        $doc['descripcion'],
        $doc['proposito'],
        $doc['como_usar'],
        $doc['ejemplos'],
        $doc['permisos_requeridos'],
        $usuario
    );
    
    return $stmt->execute();
}

// Procesar acción
try {
    if ($action === 'scan') {
        // Escanear módulos disponibles
        $modulos = escanearModulos();
        
        echo json_encode([
            'success' => true,
            'modulos' => $modulos,
            'total' => count($modulos)
        ]);
        
    } elseif ($action === 'generate') {
        // Generar documentación para un módulo específico
        $moduloJson = $_POST['modulo'] ?? null;
        
        if (!$moduloJson) {
            echo json_encode(['success' => false, 'message' => 'Módulo no especificado']);
            exit;
        }
        
        $modulo = json_decode($moduloJson, true);
        $doc = generarDocumentacionIA($modulo);
        
        if ($doc && guardarDocumentacion($modulo, $doc, $conexion, $_SESSION['usuario'])) {
            echo json_encode([
                'success' => true,
                'message' => 'Documentación generada',
                'documentacion' => $doc
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al generar documentación']);
        }
        
    } elseif ($action === 'generate_all') {
        // Borrar toda la documentación existente antes de regenerar
        $conexion->query("DELETE FROM documentacion_modulos");
        
        // Generar documentación para todos los módulos
        $modulos = escanearModulos();
        $generados = 0;
        $errores = 0;
        $total = count($modulos);
        
        // Limitar a primeros 50 módulos para evitar timeout
        $modulos = array_slice($modulos, 0, 50);
        
        foreach ($modulos as $index => $modulo) {
            try {
                $doc = generarDocumentacionIA($modulo);
                if ($doc && guardarDocumentacion($modulo, $doc, $conexion, $_SESSION['usuario'])) {
                    $generados++;
                } else {
                    $errores++;
                }
                
                // Pausa más corta
                usleep(200000); // 0.2 segundos
                
            } catch (Exception $e) {
                $errores++;
                error_log("Error en módulo {$modulo['nombre']}: " . $e->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true,
            'generados' => $generados,
            'errores' => $errores,
            'total' => $total,
            'procesados' => count($modulos),
            'message' => "✅ Generados: {$generados} | ❌ Errores: {$errores} | 📊 Total: " . count($modulos)
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
