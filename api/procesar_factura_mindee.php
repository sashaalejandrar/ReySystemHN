<?php
header('Content-Type: application/json');
require_once '../config_ai.php';
require_once '../vendor/autoload.php';

use Mindee\ClientV2;
use Mindee\Input\InferenceParameters;
use Mindee\Input\PathInput;

// Recibir la imagen en base64
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['imagen'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibió ninguna imagen'
    ]);
    exit;
}

$imagenBase64 = $input['imagen'];

try {
    // Extraer la parte base64 pura
    if (strpos($imagenBase64, 'base64,') !== false) {
        $imagenBase64 = explode('base64,', $imagenBase64)[1];
    }
    
    $imagenBinaria = base64_decode($imagenBase64);
    
    if ($imagenBinaria === false) {
        throw new Exception('Error al decodificar la imagen base64');
    }
    
    // Crear archivo temporal
    $tempFile = tempnam(sys_get_temp_dir(), 'invoice_') . '.jpg';
    file_put_contents($tempFile, $imagenBinaria);
    
    // Inicializar cliente de Mindee V2
    $mindeeClient = new ClientV2(MINDEE_API_KEY);
    
    // ID del modelo de facturas de Mindee
    $modelId = "d47b41fa-b203-4f3f-961f-42215a8e77bc";
    
    // Configurar parámetros de inferencia
    $inferenceParams = new InferenceParameters(
        $modelId,
        rag: false,
        rawText: true,
        polygon: false,
        confidence: true
    );
    
    // Cargar archivo
    $inputSource = new PathInput($tempFile);
    
    // Procesar con polling
    $response = $mindeeClient->enqueueAndGetInference(
        $inputSource,
        $inferenceParams
    );
    
    // Eliminar archivo temporal
    unlink($tempFile);
    
    // Acceder a los campos del resultado
    $fields = $response->inference->result->fields;
    
    $productos = [];
    $textoCompleto = '';
    
    // Extraer productos de los campos
    foreach ($fields as $fieldName => $fieldValue) {
        // Buscar campos que parezcan productos
        if (is_array($fieldValue)) {
            foreach ($fieldValue as $item) {
                if (is_object($item) && isset($item->value)) {
                    $textoCompleto .= $item->value . "\n";
                }
            }
        } elseif (is_object($fieldValue) && isset($fieldValue->value)) {
            $textoCompleto .= $fieldValue->value . "\n";
        }
    }
    
    // Si hay texto raw, usarlo también
    if (isset($response->inference->result->rawText)) {
        $textoCompleto .= "\n" . $response->inference->result->rawText;
    }
    
    // Parsear el texto para extraer productos
    $productos = parsearFactura($textoCompleto);
    
    // Verificar existencia en BD
    $conexion = new mysqli("localhost", "root", "", "tiendasrey");
    
    if ($conexion->connect_error) {
        throw new Exception("Error de conexión a la base de datos");
    }
    
    $productosExtraidos = [];
    
    foreach ($productos as $producto) {
        $codigo = $producto['codigo'];
        $nombre = $producto['nombre'];
        $cantidad = $producto['cantidad'];
        $precio = $producto['precio'];
        
        $existe = false;
        $stockActual = 0;
        
        if (!empty($codigo)) {
            $stmt = $conexion->prepare("SELECT Stock, Nombre_Producto FROM productos WHERE Codigo_Producto = ?");
            $stmt->bind_param("s", $codigo);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows > 0) {
                $existe = true;
                $row = $resultado->fetch_assoc();
                $stockActual = $row['Stock'];
                $nombre = $row['Nombre_Producto'];
            }
            $stmt->close();
        }
        
        $productosExtraidos[] = [
            'codigo' => $codigo,
            'nombre' => $nombre,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'marca' => '',
            'descripcion' => '',
            'existe' => $existe,
            'stockActual' => $stockActual
        ];
    }
    
    $conexion->close();
    
    echo json_encode([
        'success' => true,
        'productos' => $productosExtraidos,
        'texto_completo' => $textoCompleto,
        'message' => count($productosExtraidos) . ' productos detectados con Mindee',
        'debug' => [
            'campos_extraidos' => count($fields),
            'texto_length' => strlen($textoCompleto)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al procesar con Mindee: ' . $e->getMessage(),
        'debug' => [
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ]
    ]);
}

// Función de parseo con 7 patrones
function parsearFactura($texto) {
    $productos = [];
    $lineas = explode("\n", $texto);
    
    foreach ($lineas as $i => $linea) {
        $linea = trim($linea);
        if (empty($linea)) continue;
        
        // PATRÓN 1: FORMATO DE FILA COMPLETA
        if (preg_match('/^(\d{13})\s+(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[1],
                'nombre' => $matches[1] . ' ' . trim($matches[2]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // PATRÓN 2: FORMATO POR COLUMNAS
        if (preg_match('/^(\d{13})$/', $linea) && isset($lineas[$i + 1])) {
            $codigo = $linea;
            $siguienteLinea = trim($lineas[$i + 1]);
            
            for ($j = $i + 1; $j < min($i + 4, count($lineas)); $j++) {
                $lineaBusqueda = trim($lineas[$j]);
                
                if (preg_match('/^(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $lineaBusqueda, $matches)) {
                    $productos[] = [
                        'codigo' => $codigo,
                        'nombre' => $codigo . ' ' . trim($matches[1]),
                        'cantidad' => intval($matches[2]),
                        'precio' => floatval($matches[3])
                    ];
                    break;
                }
                
                if (preg_match('/^(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $lineaBusqueda, $matches)) {
                    $productos[] = [
                        'codigo' => $codigo,
                        'nombre' => $codigo . ' ' . $siguienteLinea,
                        'cantidad' => intval($matches[1]),
                        'precio' => floatval($matches[2])
                    ];
                    break;
                }
            }
        }
        
        // PATRÓN 3: CÓDIGO EMBEBIDO
        if (preg_match('/^(.+?)\(?(\d{13})\)?\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[2],
                'nombre' => $matches[2] . ' ' . trim($matches[1]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // PATRÓN 4: CON SEPARADORES
        if (preg_match('/^(.+?)\s*[\|\/]\s*(\d{13})\s*[\|\/]\s*(\d+)\s*[\|\/]\s*(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[2],
                'nombre' => $matches[2] . ' ' . trim($matches[1]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // PATRÓN 5: INVERSO
        if (preg_match('/^(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)\s+(\d+)\s+(.+?)\s+(\d{13})$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[4],
                'nombre' => $matches[4] . ' ' . trim($matches[3]),
                'cantidad' => intval($matches[2]),
                'precio' => floatval($matches[1])
            ];
            continue;
        }
        
        // PATRÓN 6: CON TABULACIONES
        if (preg_match('/^(\d{13})\s{2,}(.+?)\s{2,}(\d+)\s{2,}(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[1],
                'nombre' => $matches[1] . ' ' . trim($matches[2]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // PATRÓN 7: COMPACTO
        if (preg_match('/^(\d{13})([A-Z\s]+)(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[1],
                'nombre' => $matches[1] . ' ' . trim($matches[2]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
    }
    
    return $productos;
}

?>
