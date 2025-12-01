<?php
/**
 * Función compartida para parsear facturas con 7 patrones diferentes
 * Usada por todos los métodos OCR (Mindee, OCR.space, Tesseract)
 */

function parsearFacturaUniversal($texto) {
    $productos = [];
    $lineas = explode("\n", $texto);
    
    foreach ($lineas as $i => $linea) {
        $linea = trim($linea);
        if (empty($linea)) continue;
        
        // ========================================
        // PATRÓN 1: FORMATO DE FILA COMPLETA
        // Código (13 dígitos) + Descripción + Cantidad + Precio
        // Ejemplo: 7501234567890 COCA COLA 2L 2 45.50
        // ========================================
        if (preg_match('/^(\d{13})\s+(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[1],
                'nombre' => $matches[1] . ' ' . trim($matches[2]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // ========================================
        // PATRÓN 2: FORMATO POR COLUMNAS (Código en línea separada)
        // Línea 1: 7501234567890
        // Línea 2: COCA COLA 2L
        // Línea 3: 2 45.50
        // ========================================
        if (preg_match('/^(\d{13})$/', $linea) && isset($lineas[$i + 1])) {
            $codigo = $linea;
            $siguienteLinea = trim($lineas[$i + 1]);
            
            // Buscar cantidad y precio en las siguientes líneas
            for ($j = $i + 1; $j < min($i + 4, count($lineas)); $j++) {
                $lineaBusqueda = trim($lineas[$j]);
                
                // Descripción + Cantidad + Precio en la misma línea
                if (preg_match('/^(.+?)\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $lineaBusqueda, $matches)) {
                    $productos[] = [
                        'codigo' => $codigo,
                        'nombre' => $codigo . ' ' . trim($matches[1]),
                        'cantidad' => intval($matches[2]),
                        'precio' => floatval($matches[3])
                    ];
                    break;
                }
                
                // Solo cantidad y precio (descripción en línea anterior)
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
        
        // ========================================
        // PATRÓN 3: FORMATO CON CÓDIGO EMBEBIDO
        // Descripción con código entre paréntesis
        // Ejemplo: COCA COLA 2L (7501234567890) 2 45.50
        // ========================================
        if (preg_match('/^(.+?)\(?(\d{13})\)?\s+(\d+)\s+(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[2],
                'nombre' => $matches[2] . ' ' . trim($matches[1]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // ========================================
        // PATRÓN 4: FORMATO DE TABLA CON SEPARADORES
        // Columnas separadas por | o /
        // Ejemplo: COCA COLA | 7501234567890 | 2 | 45.50
        // ========================================
        if (preg_match('/^(.+?)\s*[\|\/]\s*(\d{13})\s*[\|\/]\s*(\d+)\s*[\|\/]\s*(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[2],
                'nombre' => $matches[2] . ' ' . trim($matches[1]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // ========================================
        // PATRÓN 5: FORMATO INVERSO (Precio primero)
        // Ejemplo: 45.50 2 COCA COLA 2L 7501234567890
        // ========================================
        if (preg_match('/^(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)\s+(\d+)\s+(.+?)\s+(\d{13})$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[4],
                'nombre' => $matches[4] . ' ' . trim($matches[3]),
                'cantidad' => intval($matches[2]),
                'precio' => floatval($matches[1])
            ];
            continue;
        }
        
        // ========================================
        // PATRÓN 6: FORMATO CON TABULACIONES
        // Campos separados por múltiples espacios (simulando tabs)
        // Ejemplo: 7501234567890    COCA COLA 2L    2    45.50
        // ========================================
        if (preg_match('/^(\d{13})\s{2,}(.+?)\s{2,}(\d+)\s{2,}(?:L\.?|Lps\.?|HNL)?\s*(\d+\.?\d*)$/i', $linea, $matches)) {
            $productos[] = [
                'codigo' => $matches[1],
                'nombre' => $matches[1] . ' ' . trim($matches[2]),
                'cantidad' => intval($matches[3]),
                'precio' => floatval($matches[4])
            ];
            continue;
        }
        
        // ========================================
        // PATRÓN 7: FORMATO COMPACTO (sin espacios extras)
        // Ejemplo: 7501234567890COCACOLA2L2 45.50
        // ========================================
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
