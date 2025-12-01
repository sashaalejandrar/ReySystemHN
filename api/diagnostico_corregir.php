<?php
/**
 * API para corregir errores automáticamente con IA
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
    $input = json_decode(file_get_contents('php://input'), true);
    
    $archivo = $input['archivo'] ?? '';
    $titulo = $input['titulo'] ?? '';
    $descripcion = $input['descripcion'] ?? '';
    $solucion = $input['solucion'] ?? '';
    $codigoCorregido = $input['codigo_corregido'] ?? '';

    if (empty($archivo)) {
        throw new Exception('Archivo no especificado');
    }

    $directorioBase = dirname(__DIR__);
    $rutaArchivo = $directorioBase . '/' . $archivo;

    if (!file_exists($rutaArchivo)) {
        throw new Exception('Archivo no encontrado');
    }

    logDiagnostic("Iniciando corrección de: {$titulo} en {$archivo}");

    // Crear backup antes de modificar
    $backupDir = DIAGNOSTIC_BACKUP_DIR . date('Y-m-d');
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }

    $backupFile = $backupDir . '/' . basename($archivo) . '_' . time() . '.backup';
    copy($rutaArchivo, $backupFile);
    logDiagnostic("Backup creado: {$backupFile}");

    // Leer contenido actual
    $contenidoActual = file_get_contents($rutaArchivo);

    // Si no hay código corregido, pedirle a la IA que lo genere
    if (empty($codigoCorregido)) {
        $prompt = "Necesito que corrijas el siguiente error en un archivo PHP:

Archivo: {$archivo}
Error: {$titulo}
Descripción: {$descripcion}
Solución sugerida: {$solucion}

Contenido actual del archivo (primeros 10000 caracteres):
```php
" . substr($contenidoActual, 0, 10000) . "
```

Por favor, proporciona el código PHP completo corregido. Responde SOLO con el código PHP corregido, sin explicaciones adicionales.";

        $messages = [
            ['role' => 'system', 'content' => 'Eres un experto en PHP. Proporcionas código corregido sin explicaciones adicionales.'],
            ['role' => 'user', 'content' => $prompt]
        ];

        $resultado = callAIWithFallback($messages, 0.1);

        if (!$resultado['success']) {
            throw new Exception('La IA no pudo generar la corrección');
        }

        $codigoCorregido = $resultado['content'];
        $proveedor = $resultado['provider'];

        // Extraer solo el código PHP si viene con markdown
        if (preg_match('/```php\s*([\s\S]*?)\s*```/', $codigoCorregido, $matches)) {
            $codigoCorregido = $matches[1];
        } else if (preg_match('/```\s*([\s\S]*?)\s*```/', $codigoCorregido, $matches)) {
            $codigoCorregido = $matches[1];
        }
    } else {
        $proveedor = 'Manual';
    }

    // Aplicar la corrección
    $resultado = file_put_contents($rutaArchivo, $codigoCorregido);

    if ($resultado === false) {
        // Restaurar backup si falla
        copy($backupFile, $rutaArchivo);
        throw new Exception('No se pudo escribir el archivo corregido');
    }

    // Verificar que el archivo corregido es válido PHP
    $output = [];
    $returnCode = 0;
    exec("php -l " . escapeshellarg($rutaArchivo) . " 2>&1", $output, $returnCode);

    if ($returnCode !== 0) {
        // Restaurar backup si hay error de sintaxis
        copy($backupFile, $rutaArchivo);
        logDiagnostic("Corrección falló - sintaxis inválida. Backup restaurado", 'WARNING');
        throw new Exception('La corrección generó errores de sintaxis. Backup restaurado.');
    }

    logDiagnostic("Corrección aplicada exitosamente en {$archivo} usando {$proveedor}");

    // Guardar en historial de base de datos
    try {
        $conexion = new mysqli("localhost", "root", "", "tiendasrey");
        $stmt = $conexion->prepare("INSERT INTO diagnostico_historial (titulo, descripcion, archivo, nivel, tipo, solucion, proveedor, usuario, backup_archivo) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $nivel = $input['nivel'] ?? 'info';
        $tipo = $input['tipo'] ?? 'logica';
        $usuario = $_SESSION['usuario'];
        
        $stmt->bind_param("sssssssss", 
            $titulo, 
            $descripcion, 
            $archivo, 
            $nivel, 
            $tipo, 
            $solucion, 
            $proveedor, 
            $usuario, 
            $backupFile
        );
        
        $stmt->execute();
        $stmt->close();
        $conexion->close();
    } catch (Exception $dbError) {
        logDiagnostic("Error al guardar en historial: " . $dbError->getMessage(), 'WARNING');
    }

    echo json_encode([
        'success' => true,
        'message' => "Error corregido exitosamente en {$archivo}",
        'proveedor' => $proveedor,
        'backup' => $backupFile
    ]);

} catch (Exception $e) {
    logDiagnostic('Error en corrección: ' . $e->getMessage(), 'ERROR');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
