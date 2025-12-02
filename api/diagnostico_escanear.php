<?php
/**
 * API para escanear el sistema y detectar errores con IA
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../config_ai.php';

// Verificar que sea admin
$conexion = new mysqli("localhost", "root", "", "tiendasrey");
$stmt = $conexion->prepare("SELECT Rol FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conexion->close();

if (!$user || strtolower($user['Rol']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

try {
    // Aumentar tiempo de ejecución para el escaneo
    set_time_limit(120); // 2 minutos máximo
    
    logDiagnostic('Iniciando escaneo del sistema');

    // Obtener parámetros
    $input = json_decode(file_get_contents('php://input'), true);
    $modo = $input['modo'] ?? 'rapido';
    $lote = intval($input['lote'] ?? 0);
    $archivosPorLote = 10;

    $erroresDetectados = [];
    $archivosEscaneados = 0;
    $directorioBase = dirname(__DIR__);

    // Función recursiva para obtener todos los archivos PHP
    function obtenerArchivosPHP($dir, $excluir = []) {
        $archivos = [];
        $excluirDirs = ['vendor', 'node_modules', 'backups', 'uploads', 'fpdf', 'tcpdf', '.git', '.vscode'];
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $ruta = $file->getPathname();
                $excluir = false;
                
                foreach ($excluirDirs as $dirExcluido) {
                    if (strpos($ruta, DIRECTORY_SEPARATOR . $dirExcluido . DIRECTORY_SEPARATOR) !== false) {
                        $excluir = true;
                        break;
                    }
                }
                
                if (!$excluir) {
                    $archivos[] = $ruta;
                }
            }
        }
        
        return $archivos;
    }

    $todosLosArchivos = obtenerArchivosPHP($directorioBase);
    $totalArchivos = count($todosLosArchivos);
    
    // Priorizar archivos críticos
    $archivosPrioritarios = [
        'inventario.php',
        'nueva_venta.php',
        'productos_pendientes.php',
        'creacion_de_producto.php',
        'actualizar_producto.php',
        'agregar_stock_simple.php',
        'procesar_venta.php',
        'nueva_venta_v2.php'
    ];

    // Ordenar para que los prioritarios vayan primero
    usort($todosLosArchivos, function($a, $b) use ($archivosPrioritarios) {
        $aPrioritario = false;
        $bPrioritario = false;
        
        foreach ($archivosPrioritarios as $prioritario) {
            if (strpos($a, $prioritario) !== false) $aPrioritario = true;
            if (strpos($b, $prioritario) !== false) $bPrioritario = true;
        }
        
        if ($aPrioritario && !$bPrioritario) return -1;
        if (!$aPrioritario && $bPrioritario) return 1;
        return 0;
    });

    // Determinar límites según el modo
    if ($modo === 'completo') {
        // Procesar por lotes
        $inicio = $lote * $archivosPorLote;
        $fin = min($inicio + $archivosPorLote, $totalArchivos);
        $archivosAProcesar = array_slice($todosLosArchivos, $inicio, $archivosPorLote);
        $hayMas = $fin < $totalArchivos;
    } else {
        // Modo rápido: solo primeros 10 archivos (5 con IA)
        $archivosAProcesar = array_slice($todosLosArchivos, 0, 10);
        $hayMas = false;
        $inicio = 0;
        $fin = min(10, $totalArchivos);
    }

    foreach ($archivosAProcesar as $rutaCompleta) {

        if (!file_exists($rutaCompleta)) {
            continue;
        }

        $archivosEscaneados++;
        $archivo = str_replace($directorioBase . '/', '', $rutaCompleta);
        $contenido = file_get_contents($rutaCompleta);

        // Solo analizar con IA los archivos prioritarios (máximo 5)
        $analizarConIA = false;
        $esPrioritario = false;
        
        foreach ($archivosPrioritarios as $prioritario) {
            if (strpos($archivo, $prioritario) !== false) {
                $esPrioritario = true;
                $analizarConIA = true;
                break;
            }
        }

        if ($analizarConIA && $archivosEscaneados <= 5) {
            // Análisis con IA - SOLO archivos prioritarios
            $prompt = "Analiza este código PHP y detecta SOLO errores críticos:
1. Errores de compatibilidad Linux/Windows
2. Errores que causen fallos inmediatos
3. Problemas de seguridad graves

Archivo: {$archivo}

Código (primeros 4000 caracteres):
```php
" . substr($contenido, 0, 4000) . "
```

Responde en JSON:
{
  \"errores\": [
    {
      \"nivel\": \"critico|advertencia\",
      \"tipo\": \"compatibilidad|seguridad|sintaxis\",
      \"titulo\": \"Título breve\",
      \"descripcion\": \"Descripción\",
      \"solucion\": \"Solución\"
    }
  ]
}";

            $messages = [
                ['role' => 'system', 'content' => 'Eres un experto en PHP. Responde SOLO con JSON válido. Sé conciso.'],
                ['role' => 'user', 'content' => $prompt]
            ];

            // Pequeña pausa para no saturar
            usleep(500000); // 0.5 segundos

            $resultado = callAIWithFallback($messages, 0.1);

            if ($resultado['success']) {
                $respuesta = $resultado['content'];
                
                if (preg_match('/\{[\s\S]*\}/', $respuesta, $matches)) {
                    $jsonStr = $matches[0];
                    $analisis = json_decode($jsonStr, true);

                    if ($analisis && isset($analisis['errores'])) {
                        foreach ($analisis['errores'] as $error) {
                            $erroresDetectados[] = array_merge($error, [
                                'archivo' => $archivo,
                                'proveedor' => $resultado['provider']
                            ]);
                        }
                    }
                }

                logDiagnostic("Archivo {$archivo} analizado con {$resultado['provider']}");
            }
        }

        // Limitar según el modo
        if ($modo === 'rapido' && $archivosEscaneados >= 10) break;
    }

    // Análisis adicional de compatibilidad Linux/Windows
    $erroresCompatibilidad = analizarCompatibilidadSistema($directorioBase);
    $erroresDetectados = array_merge($erroresDetectados, $erroresCompatibilidad);

    logDiagnostic("Escaneo completado: " . count($erroresDetectados) . " errores detectados");

    echo json_encode([
        'success' => true,
        'errores' => $erroresDetectados,
        'archivos_escaneados' => $archivosEscaneados,
        'archivos_procesados' => $modo === 'completo' ? $fin : $archivosEscaneados,
        'total_archivos' => $modo === 'completo' ? $totalArchivos : $archivosEscaneados,
        'hay_mas' => $modo === 'completo' ? $hayMas : false,
        'modo' => $modo
    ]);

} catch (Exception $e) {
    logDiagnostic('Error en escaneo: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false,
        'message' => 'Error al escanear: ' . $e->getMessage()
    ]);
}

/**
 * Analiza problemas de compatibilidad Linux/Windows
 */
function analizarCompatibilidadSistema($dirBase) {
    $errores = [];

    // Verificar rutas con barras invertidas (problema Windows)
    $archivos = glob($dirBase . '/*.php');
    foreach (array_slice($archivos, 0, 20) as $archivo) {
        $contenido = file_get_contents($archivo);
        
        // Detectar rutas con backslash
        if (preg_match('/["\'].*\\\\.*["\']/', $contenido)) {
            $errores[] = [
                'nivel' => 'advertencia',
                'tipo' => 'compatibilidad',
                'titulo' => 'Rutas con backslash detectadas',
                'descripcion' => 'El archivo usa backslashes (\\) en rutas, lo cual puede causar problemas en Linux',
                'archivo' => basename($archivo),
                'solucion' => 'Usar forward slashes (/) o DIRECTORY_SEPARATOR para compatibilidad multiplataforma',
                'linea' => 0
            ];
        }

        // Detectar case-sensitivity en includes
        if (preg_match_all('/(?:include|require)(?:_once)?\s*["\']([^"\']+)["\']/', $contenido, $matches)) {
            foreach ($matches[1] as $ruta) {
                $rutaCompleta = dirname($archivo) . '/' . $ruta;
                if (!file_exists($rutaCompleta) && file_exists(strtolower($rutaCompleta))) {
                    $errores[] = [
                        'nivel' => 'critico',
                        'tipo' => 'compatibilidad',
                        'titulo' => 'Problema de case-sensitivity en include',
                        'descripcion' => "El archivo '{$ruta}' tiene problemas de mayúsculas/minúsculas. Linux es case-sensitive.",
                        'archivo' => basename($archivo),
                        'solucion' => 'Verificar que el nombre del archivo coincida exactamente con el include',
                        'linea' => 0
                    ];
                }
            }
        }
    }

    return $errores;
}
