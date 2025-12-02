<?php
/**
 * Nova Rey - Asistente Virtual Inteligente Avanzada
 * Con Entrenamiento Profundo en Base de Datos
 * Versión 3.0 - Powered by Mixtral AI
 */
session_start();
require_once 'db_connect.php';
require_once 'config_ai.php'; // Usar configuración centralizada de IA

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$action = $_POST['action'] ?? '';
$message = $_POST['message'] ?? '';
$usuario = $_SESSION['usuario'];

/**
 * Función mejorada para consultar Mixtral AI con contexto avanzado
 */
function askMixtralAdvanced($userMessage, $systemContext = '', $dbSchema = '') {
    // Prompt del sistema mejorado con conocimiento profundo
    $systemPrompt = "Eres Nova Rey, una asistente virtual de IA altamente inteligente y entrenada para el sistema ReySystem.\n\n";
    
    $systemPrompt .= "**Tu Personalidad:**\n";
    $systemPrompt .= "- Eres profesional, amable y extremadamente útil\n";
    $systemPrompt .= "- Hablas en español de Honduras con un tono cercano pero profesional\n";
    $systemPrompt .= "- Eres proactiva: no solo respondes, también sugieres mejoras\n";
    $systemPrompt .= "- Usas emojis de forma moderada para hacer la conversación más amigable\n\n";
    
    $systemPrompt .= "**Tu Conocimiento:**\n";
    $systemPrompt .= "- Sistema de gestión de ventas, inventario y caja\n";
    $systemPrompt .= "- Análisis de datos y reportes\n";
    $systemPrompt .= "- Mejores prácticas de negocio retail\n";
    $systemPrompt .= "- Estrategias de ventas y marketing\n";
    $systemPrompt .= "- Gestión de inventario y stock\n";
    $systemPrompt .= "- Control de caja y finanzas básicas\n\n";
    
    if ($dbSchema) {
        $systemPrompt .= "**Esquema de la Base de Datos:**\n";
        $systemPrompt .= $dbSchema . "\n\n";
    }
    
    if ($systemContext) {
        $systemPrompt .= "**Estado Actual del Sistema:**\n";
        $systemPrompt .= $systemContext . "\n\n";
    }
    
    $systemPrompt .= "**Instrucciones:**\n";
    $systemPrompt .= "- Responde de forma concisa pero completa (máximo 900 palabras)\n";
    $systemPrompt .= "- Si detectas problemas, menciónalos y da soluciones\n";
    $systemPrompt .= "- Si el usuario pide consejos, sé específico y práctico\n";
    $systemPrompt .= "- Usa formato markdown para mejor legibilidad (**negrita**, listas, etc.)\n";
    $systemPrompt .= "- Si no tienes información suficiente, pide más detalles\n";
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ];
    
    // Usar la función de config_ai.php con fallback automático
    $result = callAIWithFallback($messages, 0.7);
    
    if ($result['success']) {
        return [
            'content' => $result['content'],
            'provider' => $result['provider']
        ];
    }
    
    return null;
}

class NovaReyAdvanced {
    private $conexion;
    private $usuario;
    private $dbSchema = '';
    
    public function __construct($conexion, $usuario) {
        $this->conexion = $conexion;
        $this->usuario = $usuario;
        $this->learnDatabaseSchema();
    }
    
    /**
     * Aprende el esquema de la base de datos automáticamente
     */
    private function learnDatabaseSchema() {
        try {
            $schema = "Tablas disponibles en el sistema:\n\n";
            
            // Obtener todas las tablas
            $result = $this->conexion->query("SHOW TABLES");
            if ($result) {
                $tables = [];
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                
                // Para cada tabla, obtener sus columnas
                foreach ($tables as $table) {
                    $schema .= "**Tabla: {$table}**\n";
                    $columnsResult = $this->conexion->query("DESCRIBE {$table}");
                    if ($columnsResult) {
                        $schema .= "Columnas: ";
                        $columns = [];
                        while ($col = $columnsResult->fetch_assoc()) {
                            $columns[] = $col['Field'] . " (" . $col['Type'] . ")";
                        }
                        $schema .= implode(", ", $columns) . "\n\n";
                    }
                }
                
                $this->dbSchema = $schema;
            }
        } catch (Exception $e) {
            // Si falla, usar esquema básico conocido
            $this->dbSchema = $this->getBasicSchema();
        }
    }
    
    /**
     * Esquema básico de respaldo
     */
    private function getBasicSchema() {
        return "Tablas principales del sistema:
        
**stock**: Inventario de productos
- Codigo_Producto, Nombre_Producto, Stock, Precio_Venta, Precio_Compra, etc.

**ventas**: Registro de ventas
- id, Fecha_Venta, total, cantidad, usuario, id_producto, etc.

**usuarios**: Usuarios del sistema
- usuario, nombre, rol, etc.

**aperturas/cierres de caja**: Control de caja
- fecha, monto_inicial, usuario, estado, etc.";
    }
    
    public function processMessage($message) {
        $originalMessage = $message;
        
        // Construir contexto ligero del sistema (sin DB schema para reducir tokens)
        $systemContext = $this->buildAdvancedContext();
        
        // Prompt del sistema simplificado
        $systemPrompt = "Eres Nova Rey, asistente virtual de ReySystem.\n\n";
        $systemPrompt .= "**Personalidad:** Amable, profesional y proactiva.\n";
        $systemPrompt .= "**Idioma:** Español de Honduras.\n\n";
        $systemPrompt .= "**Funciones disponibles:**\n";
        $systemPrompt .= "- Buscar productos en inventario\n";
        $systemPrompt .= "- Consultar ventas (hoy, ayer, semana, mes)\n";
        $systemPrompt .= "- Ver stock bajo o agotado\n";
        $systemPrompt .= "- Top productos más vendidos\n";
        $systemPrompt .= "- Estado de caja\n";
        $systemPrompt .= "- Resumen del día\n\n";
        
        // Llamar a la IA con el contexto completo
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt . "**Estado Actual:**\n" . $systemContext],
            ['role' => 'user', 'content' => $originalMessage]
        ];
        
        $aiResult = callAIWithFallback($messages, 0.7);
        
        if ($aiResult && $aiResult['success']) {
            $aiResponse = $aiResult['content'];
            $provider = $aiResult['provider'] ?? 'Mixtral AI';
            
            // Detectar si la IA solicita ejecutar funciones
            $executedFunctions = $this->detectAndExecuteFunctions($aiResponse, $originalMessage);
            
            // Si se ejecutaron funciones, combinar resultados
            if ($executedFunctions) {
                $finalResponse = $executedFunctions['message'];
                $actions = $executedFunctions['actions'] ?? [];
            } else {
                $finalResponse = $aiResponse;
                $actions = $this->extractSuggestedActions($aiResponse);
            }
            
            return [
                'type' => 'ai_response',
                'message' => "🤖 **Nova Rey** (powered by {$provider})\n\n" . $finalResponse,
                'actions' => $actions,
                'powered_by' => $provider
            ];
        }
        
        // Mensaje de error más detallado
        $errorDetail = isset($aiResult['error']) ? $aiResult['error'] : 'Error desconocido';
        error_log("Nova Rey Error: " . $errorDetail);
        
        return [
            'type' => 'error',
            'message' => "🤔 Lo siento, tuve un problema técnico.\n\n**Error:** " . $errorDetail . "\n\nPor favor intenta de nuevo en unos momentos."
        ];
    }
    
    /**
     * Construye el prompt del sistema con funciones disponibles
     */
    private function buildSystemPromptWithFunctions($functions) {
        $prompt = "Eres Nova Rey, una asistente virtual de IA para ReySystem.\n\n";
        $prompt .= "**Tu Personalidad:**\n";
        $prompt .= "- Profesional, amable y extremadamente útil\n";
        $prompt .= "- Hablas en español de Honduras con tono cercano\n";
        $prompt .= "- Proactiva: sugieres mejoras y soluciones\n";
        $prompt .= "- Usas emojis moderadamente 😊\n\n";
        
        $prompt .= "**Funciones Disponibles:**\n";
        $prompt .= "Tienes acceso a estas funciones para ayudar al usuario:\n\n";
        
        foreach ($functions as $func) {
            $prompt .= "- **{$func['name']}**: {$func['description']}\n";
            if (isset($func['examples'])) {
                $prompt .= "  Ejemplos: " . implode(", ", $func['examples']) . "\n";
            }
        }
        
        $prompt .= "\n**Instrucciones:**\n";
        $prompt .= "1. Analiza la intención del usuario\n";
        $prompt .= "2. Si necesitas datos específicos (productos, ventas, etc.), menciona qué función usar\n";
        $prompt .= "3. Responde de forma natural y conversacional\n";
        $prompt .= "4. Si el usuario saluda, responde amablemente\n";
        $prompt .= "5. Usa markdown para mejor formato\n";
        $prompt .= "6. Sé concisa pero completa\n\n";
        
        $prompt .= "**Esquema de Base de Datos:**\n" . $this->dbSchema . "\n";
        
        return $prompt;
    }
    
    /**
     * Define las funciones disponibles para la IA
     */
    private function getAvailableFunctions() {
        return [
            [
                'name' => 'buscar_producto',
                'description' => 'Busca productos en el inventario por nombre o código',
                'examples' => ['"busca coca cola"', '"producto 12345"', '"dame info de galletas"'],
                'pattern' => '/(?:busca(?:r)?|encuentra|dame|muestra|info(?:rmación)?|ver).*?(?:producto|item)/i'
            ],
            [
                'name' => 'ventas_hoy',
                'description' => 'Muestra las ventas del día actual',
                'examples' => ['"cuánto vendí hoy"', '"ventas de hoy"', '"reporte de hoy"'],
                'pattern' => '/(?:venta|vendido|cuánto).*?(?:hoy|día)/i'
            ],
            [
                'name' => 'ventas_periodo',
                'description' => 'Muestra ventas de un período (ayer, semana, mes)',
                'examples' => ['"ventas de ayer"', '"ventas del mes"', '"ventas de la semana"'],
                'pattern' => '/(?:venta|vendido).*?(?:ayer|semana|mes)/i'
            ],
            [
                'name' => 'stock_bajo',
                'description' => 'Lista productos con stock bajo o agotados',
                'examples' => ['"productos con poco stock"', '"qué está agotado"', '"stock bajo"'],
                'pattern' => '/(?:stock|inventario).*?(?:bajo|poco|agotado|crítico)/i'
            ],
            [
                'name' => 'top_productos',
                'description' => 'Muestra los productos más vendidos',
                'examples' => ['"top 10 productos"', '"más vendidos"', '"mejores productos"'],
                'pattern' => '/(?:top|mejor|más vendido)/i'
            ],
            [
                'name' => 'estado_caja',
                'description' => 'Muestra el estado actual de la caja',
                'examples' => ['"estado de caja"', '"caja abierta?"', '"cómo está la caja"'],
                'pattern' => '/(?:estado|cómo está).*?caja/i'
            ],
            [
                'name' => 'resumen_dia',
                'description' => 'Resumen completo del día (ventas, caja, stock)',
                'examples' => ['"resumen del día"', '"cómo va el día"', '"reporte diario"'],
                'pattern' => '/(?:resumen|reporte|cómo va).*?(?:día|hoy)/i'
            ]
        ];
    }
    
    /**
     * Detecta y ejecuta funciones basándose en el mensaje del usuario
     */
    private function detectAndExecuteFunctions($aiResponse, $userMessage) {
        $messageLower = strtolower($userMessage);
        
        // Buscar productos - SOLO si menciona explícitamente producto/buscar
        if (preg_match('/(?:busca(?:r)?|busca(?:me)?|encuentra|dame|muestra|info|ver).*?(?:producto|item|artículo)/i', $userMessage)) {
            // Extraer el término de búsqueda
            $searchTerm = $this->extractSearchTerm($userMessage);
            if ($searchTerm) {
                return $this->searchProducts($searchTerm);
            }
        }
        
        // Ventas de hoy
        if (preg_match('/(?:venta|vendido|cuánto).*?(?:hoy|día)/i', $userMessage)) {
            return $this->getSalesInfo();
        }
        
        // Ventas de período
        if (preg_match('/(?:venta|vendido).*?ayer/i', $userMessage)) {
            return $this->getSalesByDate('yesterday');
        }
        if (preg_match('/(?:venta|vendido).*?semana/i', $userMessage)) {
            return $this->getSalesByDate('week');
        }
        if (preg_match('/(?:venta|vendido).*?mes/i', $userMessage)) {
            return $this->getSalesByDate('month');
        }
        
        // Stock bajo
        if (preg_match('/(?:stock|inventario).*?(?:bajo|poco|agotado|crítico)/i', $userMessage)) {
            return $this->getInventoryInfo();
        }
        
        // Top productos
        if (preg_match('/(?:top|mejor|más vendido)/i', $userMessage)) {
            preg_match('/(\d+)/', $userMessage, $matches);
            $limit = isset($matches[1]) ? (int)$matches[1] : 10;
            return $this->getTopProducts($limit);
        }
        
        // Estado de caja
        if (preg_match('/(?:estado|cómo está).*?caja/i', $userMessage) || 
            preg_match('/caja.*?(?:abierta|cerrada)/i', $userMessage)) {
            return $this->getCashRegisterStatus();
        }
        
        // Resumen del día
        if (preg_match('/(?:resumen|reporte|cómo va).*?(?:día|hoy)/i', $userMessage)) {
            return $this->getDailySummary();
        }
        
        // Si no coincide con ningún patrón, dejar que la IA responda naturalmente
        return null;
    }
    
    /**
     * Extrae el término de búsqueda del mensaje
     */
    private function extractSearchTerm($message) {
        // Patrones para extraer el término
        $patterns = [
            '/(?:busca(?:r)?|busca(?:me)?|encuentra|dame|muestra).*?(?:producto|item)?[:\s]+(.+)/i',
            '/(?:info(?:rmación)?|ver).*?(?:de|del|sobre)[:\s]+(.+)/i',
            '/^(.+)$/i' // Fallback: todo el mensaje
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, trim($message), $matches)) {
                $term = trim($matches[1]);
                // Limpiar palabras comunes
                $term = preg_replace('/^(?:el|la|los|las|un|una|producto|item)\s+/i', '', $term);
                if (strlen($term) >= 3) {
                    return $term;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Detectar búsquedas específicas (LEGACY - mantenido para compatibilidad)
     */
    private function detectAndExecuteSearch($originalMessage, $messageLower) {
        // Búsqueda de productos - Patrones más flexibles
        if (preg_match('/(?:busca(?:r)?|busca(?:me)?|encuentra(?:me)?|dame|muestra(?:me)?|ver)\s+(?:el\s+|la\s+|los\s+)?producto(?:s)?\s+(.+)/i', $originalMessage, $matches)) {
            return $this->searchProducts($matches[1]);
        }
        
        // Búsqueda simple: cualquier palabra que no sea comando
        if (preg_match('/^(?!.*(?:venta|usuario|top|lista|ayuda|error|inventario|caja|egreso|compra|reporte|resumen))(.{3,})$/i', $messageLower) && 
            !preg_match('/\?|cómo|qué|cuál|dame\s+consejo/i', $originalMessage)) {
            // Si es una palabra simple sin comandos especiales, buscar como producto
            return $this->searchProducts($originalMessage);
        }
        
        // Búsqueda de usuarios
        if (preg_match('/(?:busca(?:r)?|encuentra(?:me)?|info(?:rmación)?(?:\s+de)?|dame\s+info)\s+(?:del?\s+)?usuario\s+(.+)/i', $originalMessage, $matches) ||
            preg_match('/quién\s+es\s+(?:el\s+usuario\s+)?(.+)/i', $originalMessage, $matches)) {
            return $this->searchUsers($matches[1]);
        }
        
        // Ventas por fecha - Mejorado
        if (preg_match('/(?:ventas?|vendido|cuánto\s+(?:he\s+)?vend[ií])\s+(?:de\s+|del?\s+)?(.+)/i', $originalMessage, $matches)) {
            $dateQuery = strtolower($matches[1]);
            
            // Evitar confusión con ventas de usuario
            if (preg_match('/^(?:hoy|ayer|semana|mes)$/i', $dateQuery)) {
                if (stripos($dateQuery, 'hoy') !== false) {
                    return $this->getSalesInfo();
                } elseif (stripos($dateQuery, 'ayer') !== false) {
                    return $this->getSalesByDate('yesterday');
                } elseif (stripos($dateQuery, 'semana') !== false) {
                    return $this->getSalesByDate('week');
                } elseif (stripos($dateQuery, 'mes') !== false) {
                    return $this->getSalesByDate('month');
                }
            }
        }
        
        // Ventas por usuario específico
        if (preg_match('/ventas?\s+(?:de|del|hechas\s+por)\s+(?:el\s+usuario\s+)?(.+)/i', $originalMessage, $matches)) {
            $username = trim($matches[1]);
            // Verificar que no sea una fecha
            if (!preg_match('/^(?:hoy|ayer|semana|mes)$/i', $username)) {
                return $this->getSalesByUser($username);
            }
        }
        
        // Top productos
        if (preg_match('/(?:top|mejores?|más\s+vendidos?)\s+(\d+)?\s*productos?/i', $originalMessage, $matches)) {
            $limit = isset($matches[1]) && !empty($matches[1]) ? (int)$matches[1] : 10;
            return $this->getTopProducts($limit);
        }
        
        // Productos por precio
        if (preg_match('/productos?\s+(?:de\s+)?(?:menores?|menos|más\s+baratos?|baratos?)\s+(?:de\s+|a\s+)?(?:L\s*)?(\d+)/i', $originalMessage, $matches)) {
            $price = (int)$matches[1];
            return $this->getProductsByPrice($price, 'less');
        }
        
        if (preg_match('/productos?\s+(?:de\s+)?(?:mayores?|más|más\s+caros?|caros?)\s+(?:de\s+|a\s+)?(?:L\s*)?(\d+)/i', $originalMessage, $matches)) {
            $price = (int)$matches[1];
            return $this->getProductsByPrice($price, 'more');
        }
        
        // Lista de usuarios
        if (preg_match('/(?:lista|muestra(?:me)?|ver|dame)\s+(?:de\s+|los\s+)?usuarios?/i', $originalMessage)) {
            return $this->listUsers();
        }
        
        return null;
    }
    
    /**
     * Busca productos en el inventario
     */
    private function searchProducts($query) {
        $query = trim($query);
        $response = "🔍 **Búsqueda de Productos: \"{$query}\"**\n\n";
        
        // Buscar por nombre o código con JOIN a creacion_de_productos
        $searchTerm = "%{$query}%";
        $sql = "SELECT s.Codigo_Producto, s.Nombre_Producto, s.Stock, s.Precio_Unitario, s.Precio_Mayoreo,
                       COALESCE(cp.CostoPorEmpaque, 0) as Precio_Compra
                FROM stock s
                LEFT JOIN creacion_de_productos cp ON s.Codigo_Producto = cp.CodigoProducto
                WHERE s.Nombre_Producto LIKE ? OR s.Codigo_Producto LIKE ?
                LIMIT 10";
        
        try {
            if (!$this->conexion) {
                $response .= "❌ No hay conexión a la base de datos.\n";
                $response .= "💡 Verifica que MySQL esté corriendo.";
                return [
                    'type' => 'product_search',
                    'message' => $response
                ];
            }
            
            $stmt = $this->conexion->prepare($sql);
            
            if (!$stmt) {
                $response .= "❌ Error al preparar la consulta.\n";
                $response .= "💡 Error: " . $this->conexion->error;
                return [
                    'type' => 'product_search',
                    'message' => $response
                ];
            }
            
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $response .= "**Encontrados {$result->num_rows} producto(s):**\n\n";
                
                while ($row = $result->fetch_assoc()) {
                    $stockIcon = $row['Stock'] == 0 ? "🔴" : ($row['Stock'] < 10 ? "🟡" : "🟢");
                    
                    // Asegurar que los precios sean números válidos
                    $precioUnitario = floatval($row['Precio_Unitario'] ?? 0);
                    $precioCompra = floatval($row['Precio_Compra'] ?? 0);
                    
                    $response .= "{$stockIcon} **{$row['Nombre_Producto']}**\n";
                    $response .= "   • Código: `{$row['Codigo_Producto']}`\n";
                    $response .= "   • Stock: {$row['Stock']} unidades\n";
                    $response .= "   • Precio Unitario: L" . number_format($precioUnitario, 2) . "\n";
                    $response .= "   • Costo Compra: L" . number_format($precioCompra, 2) . "\n\n";
                }
            } else {
                $response .= "❌ No se encontraron productos con ese criterio.\n\n";
                $response .= "💡 **Sugerencias:**\n";
                $response .= "• Intenta buscar con otro término\n";
                $response .= "• Verifica la ortografía\n";
                $response .= "• Usa términos más cortos (ej: 'Coca' en vez de 'Coca Cola')";
            }
        } catch (Exception $e) {
            $response .= "❌ Error al buscar productos.\n\n";
            $response .= "**Detalles del error:**\n";
            $response .= "• " . $e->getMessage() . "\n\n";
            $response .= "💡 **Posibles causas:**\n";
            $response .= "• MySQL no está corriendo\n";
            $response .= "• La tabla 'stock' o 'creacion_de_productos' no existe\n";
            $response .= "• Problema de permisos en la base de datos";
        }
        
        return [
            'type' => 'product_search',
            'message' => $response,
            'actions' => [
                ['label' => 'Ver Inventario Completo', 'url' => 'inventario.php']
            ]
        ];
    }
    
    /**
     * Busca usuarios
     */
    private function searchUsers($query) {
        $query = trim($query);
        $response = "👤 **Búsqueda de Usuario: \"{$query}\"**\n\n";
        
        $searchTerm = "%{$query}%";
        $sql = "SELECT usuario, nombre, rol FROM usuarios WHERE usuario LIKE ? OR nombre LIKE ? LIMIT 5";
        
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("ss", $searchTerm, $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $rolIcon = $row['rol'] == 'admin' ? "👑" : "👤";
                    $response .= "{$rolIcon} **{$row['nombre']}**\n";
                    $response .= "   • Usuario: `{$row['usuario']}`\n";
                    $response .= "   • Rol: {$row['rol']}\n\n";
                }
            } else {
                $response .= "❌ No se encontró el usuario.";
            }
        } catch (Exception $e) {
            $response .= "❌ Error al buscar usuario.";
        }
        
        return [
            'type' => 'user_search',
            'message' => $response
        ];
    }
    
    /**
     * Obtiene ventas por fecha
     */
    private function getSalesByDate($period) {
        $response = "📊 **Reporte de Ventas**\n\n";
        
        $dateCondition = "";
        $periodName = "";
        
        switch ($period) {
            case 'yesterday':
                $dateCondition = "DATE(Fecha_Venta) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
                $periodName = "Ayer";
                break;
            case 'week':
                $dateCondition = "Fecha_Venta >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                $periodName = "Últimos 7 días";
                break;
            case 'month':
                $dateCondition = "MONTH(Fecha_Venta) = MONTH(NOW()) AND YEAR(Fecha_Venta) = YEAR(NOW())";
                $periodName = "Este mes";
                break;
            default:
                $dateCondition = "DATE(Fecha_Venta) = CURDATE()";
                $periodName = "Hoy";
        }
        
        $sql = "SELECT COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total, 
                       MIN(total) as min_venta, MAX(total) as max_venta
                FROM ventas WHERE {$dateCondition}";
        
        $result = $this->safeQuery($sql);
        if ($result) {
            $data = $result->fetch_assoc();
            $response .= "**📅 Período: {$periodName}**\n\n";
            $response .= "💰 **Total vendido:** L" . number_format($data['total'], 2) . "\n";
            $response .= "📦 **Transacciones:** {$data['cantidad']}\n";
            
            if ($data['cantidad'] > 0) {
                $promedio = $data['total'] / $data['cantidad'];
                $response .= "📈 **Ticket promedio:** L" . number_format($promedio, 2) . "\n";
                $response .= "🔽 **Venta mínima:** L" . number_format($data['min_venta'], 2) . "\n";
                $response .= "🔼 **Venta máxima:** L" . number_format($data['max_venta'], 2) . "\n";
            }
        }
        
        return [
            'type' => 'sales_report',
            'message' => $response,
            'actions' => [
                ['label' => 'Ver Reportes Completos', 'url' => 'reporte_ventas.php']
            ]
        ];
    }
    
    /**
     * Obtiene ventas por usuario
     */
    private function getSalesByUser($username) {
        $username = trim($username);
        $response = "👤 **Ventas de: {$username}**\n\n";
        
        $sql = "SELECT COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total
                FROM ventas WHERE usuario LIKE ? AND MONTH(Fecha_Venta) = MONTH(NOW())";
        
        try {
            $searchTerm = "%{$username}%";
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("s", $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result) {
                $data = $result->fetch_assoc();
                $response .= "📊 **Este mes:**\n";
                $response .= "• Ventas: {$data['cantidad']}\n";
                $response .= "• Total: L" . number_format($data['total'], 2) . "\n";
            }
        } catch (Exception $e) {
            $response .= "❌ Error al consultar ventas del usuario.";
        }
        
        return [
            'type' => 'user_sales',
            'message' => $response
        ];
    }
    
    /**
     * Obtiene top productos más vendidos
     */
    private function getTopProducts($limit = 10) {
        $response = "🏆 **Top {$limit} Productos Más Vendidos**\n\n";
        
        $sql = "SELECT Codigo_Producto, COUNT(*) as veces, SUM(cantidad) as total_vendido
                FROM ventas 
                WHERE MONTH(Fecha_Venta) = MONTH(NOW())
                GROUP BY Codigo_Producto 
                ORDER BY veces DESC 
                LIMIT ?";
        
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $position = 1;
                while ($row = $result->fetch_assoc()) {
                    $medal = $position == 1 ? "🥇" : ($position == 2 ? "🥈" : ($position == 3 ? "🥉" : "#{$position}"));
                    $response .= "{$medal} **{$row['Codigo_Producto']}**\n";
                    $response .= "   • Vendido: {$row['veces']} veces\n";
                    $response .= "   • Cantidad total: {$row['total_vendido']} unidades\n\n";
                    $position++;
                }
            } else {
                $response .= "❌ No hay datos de ventas este mes.";
            }
        } catch (Exception $e) {
            $response .= "❌ Error al consultar productos.";
        }
        
        return [
            'type' => 'top_products',
            'message' => $response,
            'actions' => [
                ['label' => 'Ver Reportes', 'url' => 'reporte_ventas.php']
            ]
        ];
    }
    
    /**
     * Obtiene productos por rango de precio
     */
    private function getProductsByPrice($price, $comparison = 'less') {
        $operator = $comparison == 'less' ? '<' : '>';
        $comparisonText = $comparison == 'less' ? 'menores a' : 'mayores a';
        
        $response = "💰 **Productos {$comparisonText} L{$price}**\n\n";
        
        $sql = "SELECT Nombre_Producto, Codigo_Producto, Precio_Venta, Stock 
                FROM stock 
                WHERE Precio_Venta {$operator} ? 
                ORDER BY Precio_Venta " . ($comparison == 'less' ? 'ASC' : 'DESC') . "
                LIMIT 15";
        
        try {
            $stmt = $this->conexion->prepare($sql);
            $stmt->bind_param("d", $price);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $response .= "**Encontrados {$result->num_rows} producto(s):**\n\n";
                
                while ($row = $result->fetch_assoc()) {
                    $stockIcon = $row['Stock'] == 0 ? "🔴" : ($row['Stock'] < 10 ? "🟡" : "🟢");
                    $response .= "{$stockIcon} **{$row['Nombre_Producto']}**\n";
                    $response .= "   • Precio: L" . number_format($row['Precio_Venta'], 2) . "\n";
                    $response .= "   • Stock: {$row['Stock']} unidades\n\n";
                }
            } else {
                $response .= "❌ No se encontraron productos en ese rango de precio.";
            }
        } catch (Exception $e) {
            $response .= "❌ Error al buscar productos.";
        }
        
        return [
            'type' => 'price_search',
            'message' => $response,
            'actions' => [
                ['label' => 'Ver Inventario', 'url' => 'inventario.php']
            ]
        ];
    }
    
    /**
     * Lista todos los usuarios
     */
    private function listUsers() {
        $response = "👥 **Lista de Usuarios del Sistema**\n\n";
        
        $sql = "SELECT usuario, nombre, rol FROM usuarios ORDER BY rol DESC, nombre ASC";
        
        $result = $this->safeQuery($sql);
        if ($result && $result->num_rows > 0) {
            $admins = [];
            $users = [];
            
            while ($row = $result->fetch_assoc()) {
                $userInfo = "• **{$row['nombre']}** (`{$row['usuario']}`)";
                
                if ($row['rol'] == 'admin') {
                    $admins[] = $userInfo;
                } else {
                    $users[] = $userInfo;
                }
            }
            
            if (!empty($admins)) {
                $response .= "**👑 Administradores:**\n" . implode("\n", $admins) . "\n\n";
            }
            
            if (!empty($users)) {
                $response .= "**👤 Usuarios:**\n" . implode("\n", $users) . "\n";
            }
        } else {
            $response .= "❌ No se pudieron cargar los usuarios.";
        }
        
        return [
            'type' => 'user_list',
            'message' => $response
        ];
    }
    
    private function safeQuery($sql, $defaultValue = null) {
        try {
            $result = $this->conexion->query($sql);
            if ($result) {
                return $result;
            }
        } catch (Exception $e) {
            // Silenciar error
        }
        return $defaultValue;
    }
    
    /**
     * Construye contexto avanzado del sistema
     */
    private function buildAdvancedContext() {
        $context = "";
        
        // Inventario
        $result = $this->safeQuery("SELECT COUNT(*) as total FROM stock");
        if ($result) {
            $total = $result->fetch_assoc()['total'];
            $context .= "📦 Inventario: {$total} productos totales\n";
        }
        
        $result = $this->safeQuery("SELECT COUNT(*) as count FROM stock WHERE Stock < 10");
        if ($result) {
            $lowStock = $result->fetch_assoc()['count'];
            $context .= "⚠️ Stock bajo: {$lowStock} productos\n";
        }
        
        $result = $this->safeQuery("SELECT COUNT(*) as count FROM stock WHERE Stock = 0");
        if ($result) {
            $noStock = $result->fetch_assoc()['count'];
            $context .= "🔴 Sin stock: {$noStock} productos\n";
        }
        
        // Ventas de hoy
        $result = $this->safeQuery("SELECT COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total FROM ventas WHERE DATE(Fecha_Venta) = CURDATE()");
        if ($result) {
            $data = $result->fetch_assoc();
            $context .= "💰 Ventas hoy: {$data['cantidad']} transacciones, L" . number_format($data['total'], 2) . "\n";
        }
        
        // Ventas del mes
        $result = $this->safeQuery("SELECT COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total FROM ventas WHERE MONTH(Fecha_Venta) = MONTH(NOW())");
        if ($result) {
            $data = $result->fetch_assoc()['total'];
            $context .= "📊 Ventas del mes: L" . number_format($data, 2) . "\n";
        }
        
        // Producto más vendido
        $result = $this->safeQuery("SELECT Codigo_Producto, COUNT(*) as veces FROM ventas WHERE DATE(Fecha_Venta) = CURDATE() GROUP BY Codigo_Producto ORDER BY veces DESC LIMIT 1");
        if ($result && $result->num_rows > 0) {
            $data = $result->fetch_assoc();
            $context .= "🏆 Producto más vendido hoy: {$data['Codigo_Producto']} ({$data['veces']} veces)\n";
        }
        
        // Top 3 productos con stock bajo
        $result = $this->safeQuery("SELECT Nombre_Producto, Stock FROM stock WHERE Stock < 10 AND Stock > 0 ORDER BY Stock ASC LIMIT 3");
        if ($result && $result->num_rows > 0) {
            $context .= "\n⚠️ Productos críticos:\n";
            while ($row = $result->fetch_assoc()) {
                $context .= "  - {$row['Nombre_Producto']}: {$row['Stock']} unidades\n";
            }
        }
        
        return $context;
    }
    
    /**
     * Respuesta con IA avanzada
     */
    private function getAIResponse($message) {
        $context = $this->buildAdvancedContext();
        $aiResult = askMixtralAdvanced($message, $context, $this->dbSchema);
        
        if ($aiResult && isset($aiResult['content'])) {
            // Detectar si la IA sugiere acciones
            $actions = $this->extractSuggestedActions($aiResult['content']);
            
            $provider = $aiResult['provider'] ?? 'Mixtral AI';
            
            return [
                'type' => 'ai_advanced',
                'message' => "🤖 **Nova Rey AI**\n\n" . $aiResult['content'],
                'actions' => $actions,
                'powered_by' => $provider
            ];
        }
        
        return [
            'type' => 'default',
            'message' => "🤔 Lo siento, no pude procesar tu pregunta en este momento. ¿Podrías reformularla?"
        ];
    }
    
    /**
     * Extrae acciones sugeridas de la respuesta de IA
     */
    private function extractSuggestedActions($aiResponse) {
        $actions = [];
        
        // Detectar menciones de módulos y sugerir acciones
        if (stripos($aiResponse, 'inventario') !== false || stripos($aiResponse, 'stock') !== false) {
            $actions[] = ['label' => 'Ver Inventario', 'url' => 'inventario.php'];
        }
        
        if (stripos($aiResponse, 'venta') !== false || stripos($aiResponse, 'vender') !== false) {
            $actions[] = ['label' => 'Nueva Venta', 'url' => 'nueva_venta.php'];
        }
        
        if (stripos($aiResponse, 'producto') !== false && stripos($aiResponse, 'agregar') !== false) {
            $actions[] = ['label' => 'Agregar Producto', 'url' => 'creacion_de_producto.php'];
        }
        
        if (stripos($aiResponse, 'reporte') !== false || stripos($aiResponse, 'análisis') !== false) {
            $actions[] = ['label' => 'Ver Dashboard', 'url' => 'features/dashboard/analytics.php'];
        }
        
        return $actions;
    }
    
    // Métodos predefinidos optimizados
    
    private function checkSystemErrors() {
        $errors = [];
        $suggestions = [];
        $aiInsights = "";
        
        // Verificar stock bajo
        $result = $this->safeQuery("SELECT COUNT(*) as count FROM stock WHERE Stock < 10 AND Stock > 0");
        if ($result) {
            $lowStock = $result->fetch_assoc()['count'];
            if ($lowStock > 0) {
                $errors[] = "⚠️ {$lowStock} productos con stock bajo";
                $suggestions[] = "Revisar inventario y hacer pedidos";
            }
        }
        
        // Verificar productos sin stock
        $result = $this->safeQuery("SELECT COUNT(*) as count FROM stock WHERE Stock = 0");
        if ($result) {
            $noStock = $result->fetch_assoc()['count'];
            if ($noStock > 0) {
                $errors[] = "🔴 {$noStock} productos sin stock";
                $suggestions[] = "Reabastecer urgentemente";
            }
        }
        
        // Verificar ventas de hoy
        $result = $this->safeQuery("SELECT COUNT(*) as count FROM ventas WHERE DATE(Fecha_Venta) = CURDATE()");
        if ($result) {
            $ventasHoy = $result->fetch_assoc()['count'];
            if ($ventasHoy == 0) {
                $errors[] = "📊 No hay ventas registradas hoy";
                $suggestions[] = "Revisar estrategias de venta";
            }
        }
        
        // Obtener insights de IA
        if (!empty($errors)) {
            $context = "Problemas detectados:\n" . implode("\n", $errors);
            $aiInsights = askGroqAdvanced(
                "Analiza estos problemas del sistema y dame 2-3 recomendaciones específicas y accionables para solucionarlos.",
                $context,
                ""
            );
        }
        
        $response = "🔍 **Diagnóstico del Sistema**\n\n";
        
        if (empty($errors)) {
            $response .= "✅ **Todo está funcionando correctamente**\n\n";
            $response .= "No he detectado problemas críticos en el sistema.\n";
            $response .= "El inventario está bien y las operaciones funcionan normalmente.";
        } else {
            $response .= "**⚠️ Problemas Detectados:**\n";
            foreach ($errors as $error) {
                $response .= "• " . $error . "\n";
            }
            
            if ($aiInsights) {
                $response .= "\n**🤖 Recomendaciones de IA:**\n" . $aiInsights;
            }
        }
        
        return [
            'type' => 'system_check',
            'message' => $response,
            'actions' => [
                ['label' => 'Ver Inventario', 'url' => 'inventario.php'],
                ['label' => 'Ver Dashboard', 'url' => 'features/dashboard/analytics.php']
            ]
        ];
    }
    
    private function getInventoryInfo() {
        $response = "📦 **Análisis de Inventario**\n\n";
        
        $result = $this->safeQuery("SELECT COUNT(*) as total FROM stock");
        if ($result) {
            $total = $result->fetch_assoc()['total'];
            $response .= "📊 **Total de productos:** {$total}\n\n";
        }
        
        $result = $this->safeQuery("SELECT COUNT(*) as count FROM stock WHERE Stock < 10");
        if ($result) {
            $lowStock = $result->fetch_assoc()['count'];
            $response .= "⚠️ **Stock bajo:** {$lowStock} productos\n";
            
            if ($lowStock > 0) {
                $response .= "\n**Productos críticos:**\n";
                $result2 = $this->safeQuery("SELECT Nombre_Producto, Stock, Precio_Venta FROM stock WHERE Stock < 10 AND Stock > 0 ORDER BY Stock ASC LIMIT 5");
                if ($result2) {
                    while ($row = $result2->fetch_assoc()) {
                        $urgencia = $row['Stock'] <= 3 ? "🔴" : "🟡";
                        $response .= "{$urgencia} {$row['Nombre_Producto']}: {$row['Stock']} unidades (L{$row['Precio_Venta']})\n";
                    }
                }
            }
        }
        
        return [
            'type' => 'inventory',
            'message' => $response,
            'actions' => [
                ['label' => 'Ver Inventario Completo', 'url' => 'inventario.php'],
                ['label' => 'Agregar Producto', 'url' => 'creacion_de_producto.php']
            ]
        ];
    }
    
    private function getSalesInfo() {
        $response = "💰 **Análisis de Ventas**\n\n";
        
        $result = $this->safeQuery("SELECT COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total FROM ventas WHERE DATE(Fecha_Venta) = CURDATE()");
        if ($result) {
            $data = $result->fetch_assoc();
            $response .= "**📅 Hoy:**\n";
            $response .= "• Transacciones: {$data['cantidad']}\n";
            $response .= "• Total: L" . number_format($data['total'], 2) . "\n\n";
        }
        
        $result = $this->safeQuery("SELECT COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total FROM ventas WHERE MONTH(Fecha_Venta) = MONTH(NOW())");
        if ($result) {
            $data = $result->fetch_assoc();
            $response .= "**📊 Este mes:**\n";
            $response .= "• Transacciones: {$data['cantidad']}\n";
            $response .= "• Total: L" . number_format($data['total'], 2) . "\n";
        }
        
        return [
            'type' => 'sales',
            'message' => $response,
            'actions' => [
                ['label' => 'Nueva Venta', 'url' => 'nueva_venta.php'],
                ['label' => 'Ver Reportes', 'url' => 'reporte_ventas.php']
            ]
        ];
    }
    
    /**
     * Obtiene el estado actual de la caja (Abierta/Cerrada)
     */
    private function getCashRegisterStatus() {
        $response = "💵 **Estado de Caja**\n\n";
        
        // Consultar el estado de la caja de hoy
        $sql = "SELECT Estado, Fecha, Total, monto_inicial 
                FROM caja 
                WHERE DATE(Fecha) = CURDATE() 
                ORDER BY id DESC 
                LIMIT 1";
        
        $result = $this->safeQuery($sql);
        
        if ($result && $result->num_rows > 0) {
            $caja = $result->fetch_assoc();
            $estado = ucfirst(strtolower($caja['Estado']));
            $fecha = date('d/m/Y H:i', strtotime($caja['Fecha']));
            
            if (strtolower($caja['Estado']) === 'abierta') {
                $response .= "🟢 **Estado: ABIERTA**\n\n";
                $response .= "📅 **Fecha de apertura:** {$fecha}\n";
                $response .= "💰 **Monto inicial:** L" . number_format($caja['monto_inicial'], 2) . "\n\n";
                
                // Obtener ventas del día
                $ventasResult = $this->safeQuery("SELECT COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total FROM ventas WHERE DATE(Fecha_Venta) = CURDATE()");
                if ($ventasResult) {
                    $ventas = $ventasResult->fetch_assoc();
                    $response .= "📊 **Ventas de hoy:**\n";
                    $response .= "• Transacciones: {$ventas['cantidad']}\n";
                    $response .= "• Total vendido: L" . number_format($ventas['total'], 2) . "\n\n";
                }
                
                // Obtener egresos del día
                $egresosResult = $this->safeQuery("SELECT COALESCE(SUM(Monto), 0) as total FROM egresos WHERE DATE(Fecha) = CURDATE()");
                if ($egresosResult) {
                    $egresos = $egresosResult->fetch_assoc();
                    if ($egresos['total'] > 0) {
                        $response .= "📤 **Egresos de hoy:** L" . number_format($egresos['total'], 2) . "\n\n";
                    }
                }
                
                $response .= "💡 **Acciones disponibles:**\n";
                $response .= "• Realizar arqueo de caja\n";
                $response .= "• Cerrar caja al final del día\n";
                
            } else {
                $response .= "🔴 **Estado: CERRADA**\n\n";
                $response .= "📅 **Fecha de cierre:** {$fecha}\n";
                $response .= "💰 **Monto final:** L" . number_format($caja['Total'], 2) . "\n\n";
                $response .= "⚠️ La caja ya fue cerrada hoy. No se pueden registrar más ventas hasta la próxima apertura.\n";
            }
            
        } else {
            $response .= "⚪ **Estado: SIN APERTURA**\n\n";
            $response .= "❌ No se ha registrado apertura de caja para el día de hoy.\n\n";
            $response .= "💡 **Acción requerida:**\n";
            $response .= "• Debes abrir la caja antes de realizar ventas\n";
            $response .= "• Ve a 'Apertura de Caja' para iniciar el día\n";
        }
        
        return [
            'type' => 'cash_register',
            'message' => $response,
            'actions' => [
                ['label' => 'Apertura de Caja', 'url' => 'apertura_caja.php'],
                ['label' => 'Arqueo de Caja', 'url' => 'arqueo_caja.php'],
                ['label' => 'Cierre de Caja', 'url' => 'cierre_caja.php'],
                ['label' => 'Caja al Día', 'url' => 'caja_al_dia.php']
            ]
        ];
    }
    
    private function getReminders() {
        $response = "🔔 **Recordatorios**\n\n";
        $reminders = [];
        
        $result = $this->safeQuery("SELECT COUNT(*) as count FROM stock WHERE Stock = 0");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            if ($count > 0) {
                $reminders[] = "🔴 {$count} productos sin stock";
            }
        }
        
        $result = $this->safeQuery("SELECT COUNT(*) as count FROM stock WHERE Stock < 10 AND Stock > 0");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            if ($count > 0) {
                $reminders[] = "⚠️ {$count} productos con stock bajo";
            }
        }
        
        if (empty($reminders)) {
            $response .= "✅ **Todo al día**\n\nNo tienes recordatorios pendientes.";
        } else {
            foreach ($reminders as $reminder) {
                $response .= "• " . $reminder . "\n";
            }
        }
        
        return [
            'type' => 'reminders',
            'message' => $response
        ];
    }
    
    private function getPurchasesSuggestions() {
        $response = "🛒 **Sugerencias de Compra**\n\n";
        
        $result = $this->safeQuery("SELECT Nombre_Producto, Stock, Codigo_Producto FROM stock WHERE Stock < 10 ORDER BY Stock ASC LIMIT 8");
        
        if ($result && $result->num_rows > 0) {
            $response .= "**Productos prioritarios:**\n\n";
            while ($row = $result->fetch_assoc()) {
                $urgencia = $row['Stock'] == 0 ? "🔴 URGENTE" : ($row['Stock'] <= 3 ? "🟠 Alta" : "🟡 Media");
                $response .= "{$urgencia} - {$row['Nombre_Producto']}\n";
                $response .= "  Stock: {$row['Stock']} | Código: {$row['Codigo_Producto']}\n\n";
            }
        } else {
            $response .= "✅ No hay productos que necesiten reabastecimiento urgente.";
        }
        
        return [
            'type' => 'purchases',
            'message' => $response,
            'actions' => [
                ['label' => 'Ver Inventario', 'url' => 'inventario.php']
            ]
        ];
    }
    
    private function getDailySummary() {
        $response = "📊 **Resumen del Día**\n\n";
        
        $result = $this->safeQuery("SELECT COUNT(*) as cantidad, COALESCE(SUM(total), 0) as total FROM ventas WHERE DATE(Fecha_Venta) = CURDATE()");
        if ($result) {
            $ventas = $result->fetch_assoc();
            $response .= "💰 **Ventas:** {$ventas['cantidad']} transacciones\n";
            $response .= "💵 **Total:** L" . number_format($ventas['total'], 2) . "\n\n";
            
            if ($ventas['cantidad'] > 0) {
                $promedio = $ventas['total'] / $ventas['cantidad'];
                $response .= "📈 **Ticket promedio:** L" . number_format($promedio, 2) . "\n";
            }
        }
        
        return [
            'type' => 'summary',
            'message' => $response,
            'actions' => [
                ['label' => 'Ver Dashboard', 'url' => 'features/dashboard/analytics.php']
            ]
        ];
    }
    
    private function getHelp() {
        $response = "👋 **¡Hola! Soy Nova Rey**\n\n";
        $response .= "Soy tu asistente de IA entrenada específicamente para ReySystem. 🤖\n\n";
        $response .= "**Puedo ayudarte con:**\n\n";
        $response .= "🔍 Diagnóstico del sistema\n";
        $response .= "📦 Análisis de inventario\n";
        $response .= "💰 Reportes de ventas\n";
        $response .= "🛒 Sugerencias de compra\n";
        $response .= "📊 Estadísticas y tendencias\n";
        $response .= "💡 Consejos de negocio\n";
        $response .= "🎯 Estrategias de venta\n\n";
        
        $response .= "**🔎 Búsquedas Avanzadas:**\n\n";
        $response .= "**Productos:**\n";
        $response .= "• \"Buscar producto [nombre]\"\n";
        $response .= "• \"Productos menores de L100\"\n";
        $response .= "• \"Productos más caros\"\n";
        $response .= "• \"Top 10 productos\"\n\n";
        
        $response .= "**Ventas:**\n";
        $response .= "• \"Ventas de hoy/ayer/semana/mes\"\n";
        $response .= "• \"Ventas de [usuario]\"\n";
        $response .= "• \"Top productos más vendidos\"\n\n";
        
        $response .= "**Usuarios:**\n";
        $response .= "• \"Buscar usuario [nombre]\"\n";
        $response .= "• \"Lista de usuarios\"\n";
        $response .= "• \"Quién es [nombre]\"\n\n";
        
        $response .= "**Ejemplos generales:**\n";
        $response .= "• \"¿Cómo puedo mejorar mis ventas?\"\n";
        $response .= "• \"¿Qué productos debo comprar?\"\n";
        $response .= "• \"Analiza mi inventario\"\n";
        $response .= "• \"Dame consejos para el negocio\"\n\n";
        $response .= "💬 **Pregúntame lo que necesites!**";
        
        return [
            'type' => 'help',
            'message' => $response
        ];
    }
}

// Procesar solicitud
if ($action === 'chat') {
    $nova = new NovaReyAdvanced($conexion, $usuario);
    $response = $nova->processMessage($message);
    echo json_encode($response);
} else {
    echo json_encode(['error' => 'Acción no válida']);
}
?>
