<?php
/**
 * Script de Migración: Sistema de Precios Personalizados
 * Ejecuta la creación de tablas y migración de datos
 */

// Configuración de la base de datos
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'tiendasrey';

try {
    // Conectar a la base de datos
    $mysqli = new mysqli($host, $user, $pass, $db);
    
    if ($mysqli->connect_error) {
        throw new Exception('Error de conexión: ' . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    
    echo "<h2>🚀 Iniciando Migración del Sistema de Precios</h2>\n";
    echo "<pre>\n";
    
    // Leer el archivo SQL
    $sqlFile = __DIR__ . '/sql/crear_tablas_precios.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception('Archivo SQL no encontrado: ' . $sqlFile);
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Dividir en statements individuales
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && 
                   !preg_match('/^--/', $stmt) && 
                   !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $success = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        // Limpiar comentarios
        $statement = preg_replace('/--.*$/m', '', $statement);
        $statement = trim($statement);
        
        if (empty($statement)) continue;
        
        try {
            if ($mysqli->query($statement)) {
                $success++;
                
                // Mostrar resultados de SELECT
                if (stripos($statement, 'SELECT') === 0) {
                    $result = $mysqli->store_result();
                    if ($result) {
                        echo "\n📊 Verificación:\n";
                        while ($row = $result->fetch_assoc()) {
                            echo "   - " . implode(': ', $row) . "\n";
                        }
                        $result->free();
                    }
                }
            } else {
                // Ignorar errores de "tabla ya existe"
                if (strpos($mysqli->error, 'already exists') === false &&
                    strpos($mysqli->error, 'Duplicate entry') === false) {
                    throw new Exception($mysqli->error);
                }
            }
        } catch (Exception $e) {
            $errors++;
            echo "❌ Error: " . $e->getMessage() . "\n";
            echo "   SQL: " . substr($statement, 0, 100) . "...\n\n";
        }
    }
    
    echo "\n✅ Migración completada\n";
    echo "   - Statements ejecutados exitosamente: $success\n";
    echo "   - Errores: $errors\n\n";
    
    // Verificar tablas creadas
    echo "📋 Verificando tablas creadas:\n";
    $result = $mysqli->query("SHOW TABLES LIKE 'tipos_precios'");
    echo "   - tipos_precios: " . ($result->num_rows > 0 ? "✓ Creada" : "✗ No existe") . "\n";
    
    $result = $mysqli->query("SHOW TABLES LIKE 'producto_precios'");
    echo "   - producto_precios: " . ($result->num_rows > 0 ? "✓ Creada" : "✗ No existe") . "\n";
    
    // Contar registros
    $result = $mysqli->query("SELECT COUNT(*) as total FROM tipos_precios");
    $row = $result->fetch_assoc();
    echo "\n📊 Tipos de precios: " . $row['total'] . "\n";
    
    $result = $mysqli->query("SELECT COUNT(*) as total FROM producto_precios");
    $row = $result->fetch_assoc();
    echo "📊 Precios migrados: " . $row['total'] . "\n";
    
    echo "\n</pre>";
    echo "<h3 style='color: green;'>✅ Migración completada exitosamente</h3>";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "</pre>";
    echo "<h3 style='color: red;'>❌ Error en la migración</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    exit(1);
}
?>
