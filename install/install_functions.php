<?php
/**
 * ReySystem Web Installer - Backend Functions
 * Handles installation detection, database setup, and initial configuration
 */

// Prevent direct access
if (!defined('INSTALLER_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Check if the system is already installed
 * @return bool True if installed, false otherwise
 */
function checkInstallation() {
    $config_file = dirname(__DIR__) . '/db_connect.php';
    
    // Check if config file exists
    if (!file_exists($config_file)) {
        return false;
    }
    
    // Try to connect to database
    try {
        require_once $config_file;
        
        // Check if configuracion_app table exists
        $result = $conexion->query("SHOW TABLES LIKE 'configuracion_app'");
        if ($result->num_rows == 0) {
            return false;
        }
        
        // Check if negocios table exists
        $result = $conexion->query("SHOW TABLES LIKE 'negocios'");
        if ($result->num_rows == 0) {
            return false;
        }
        
        // Check if there's at least one business
        $result = $conexion->query("SELECT COUNT(*) as count FROM negocios");
        $row = $result->fetch_assoc();
        if ($row['count'] == 0) {
            return false;
        }
        
        // Check if instalacion table exists and has a record
        $result = $conexion->query("SHOW TABLES LIKE 'instalacion'");
        if ($result->num_rows > 0) {
            $result = $conexion->query("SELECT COUNT(*) as count FROM instalacion");
            $row = $result->fetch_assoc();
            if ($row['count'] > 0) {
                return true;
            }
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Test database connection
 * @param string $host Database host
 * @param string $user Database user
 * @param string $pass Database password
 * @param string $dbname Database name
 * @return array ['success' => bool, 'message' => string, 'connection' => mysqli|null]
 */
function testDatabaseConnection($host, $user, $pass, $dbname) {
    try {
        $conn = new mysqli($host, $user, $pass);
        
        if ($conn->connect_error) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $conn->connect_error,
                'connection' => null
            ];
        }
        
        // Check if database exists, create if not
        $result = $conn->query("SHOW DATABASES LIKE '$dbname'");
        if ($result->num_rows == 0) {
            if (!$conn->query("CREATE DATABASE `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                return [
                    'success' => false,
                    'message' => 'No se pudo crear la base de datos: ' . $conn->error,
                    'connection' => null
                ];
            }
        }
        
        // Select database
        if (!$conn->select_db($dbname)) {
            return [
                'success' => false,
                'message' => 'No se pudo seleccionar la base de datos: ' . $conn->error,
                'connection' => null
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Conexión exitosa',
            'connection' => $conn
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'connection' => null
        ];
    }
}

/**
 * Create database schema from SQL file
 * @param mysqli $conn Database connection
 * @return array ['success' => bool, 'message' => string, 'errors' => array]
 */
function createDatabaseSchema($conn) {
    $schema_file = __DIR__ . '/schema.sql';
    
    if (!file_exists($schema_file)) {
        return [
            'success' => false,
            'message' => 'Archivo de esquema no encontrado',
            'errors' => []
        ];
    }
    
    $sql = file_get_contents($schema_file);
    $errors = [];
    
    // Split by semicolon and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if (!$conn->query($statement)) {
                $errors[] = 'Error en consulta: ' . $conn->error . ' | SQL: ' . substr($statement, 0, 100);
            }
        }
    }
    
    if (empty($errors)) {
        return [
            'success' => true,
            'message' => 'Esquema de base de datos creado exitosamente',
            'errors' => []
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Se encontraron errores al crear el esquema',
            'errors' => $errors
        ];
    }
}

/**
 * Create first business
 * @param mysqli $conn Database connection
 * @param array $data Business data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createFirstBusiness($conn, $data) {
    $nombre = $conn->real_escape_string($data['nombre']);
    $tipo = $conn->real_escape_string($data['tipo_negocio']);
    $direccion = $conn->real_escape_string($data['direccion'] ?? '');
    $telefono = $conn->real_escape_string($data['telefono'] ?? '');
    $email = $conn->real_escape_string($data['email'] ?? '');
    $rtn = $conn->real_escape_string($data['rtn'] ?? '');
    $impuesto = floatval($data['impuesto'] ?? 15.00);
    
    $sql = "INSERT INTO negocios (nombre, tipo_negocio, direccion, telefono, email, rtn, impuesto_default, activo) 
            VALUES ('$nombre', '$tipo', '$direccion', '$telefono', '$email', '$rtn', $impuesto, 1)";
    
    if ($conn->query($sql)) {
        $negocio_id = $conn->insert_id;
        
        // Create configuracion_app for this business
        $sql_config = "INSERT INTO configuracion_app (id_negocio, nombre_empresa, direccion_empresa, telefono_empresa, email_empresa, impuesto) 
                       VALUES ($negocio_id, '$nombre', '$direccion', '$telefono', '$email', $impuesto)";
        $conn->query($sql_config);
        
        return [
            'success' => true,
            'message' => 'Negocio creado exitosamente',
            'id' => $negocio_id
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al crear negocio: ' . $conn->error,
            'id' => null
        ];
    }
}

/**
 * Create first branch
 * @param mysqli $conn Database connection
 * @param int $id_negocio Business ID
 * @param array $data Branch data
 * @return array ['success' => bool, 'message' => string, 'id' => int|null]
 */
function createFirstBranch($conn, $id_negocio, $data) {
    $nombre = $conn->real_escape_string($data['nombre']);
    $codigo = $conn->real_escape_string($data['codigo'] ?? 'SUC001');
    $direccion = $conn->real_escape_string($data['direccion'] ?? '');
    $telefono = $conn->real_escape_string($data['telefono'] ?? '');
    $responsable = $conn->real_escape_string($data['responsable'] ?? '');
    
    $sql = "INSERT INTO sucursales (id_negocio, nombre, codigo, direccion, telefono, responsable, activo) 
            VALUES ($id_negocio, '$nombre', '$codigo', '$direccion', '$telefono', '$responsable', 1)";
    
    if ($conn->query($sql)) {
        return [
            'success' => true,
            'message' => 'Sucursal creada exitosamente',
            'id' => $conn->insert_id
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al crear sucursal: ' . $conn->error,
            'id' => null
        ];
    }
}

/**
 * Create admin user
 * @param mysqli $conn Database connection
 * @param int $id_negocio Business ID
 * @param int $id_sucursal Branch ID
 * @param array $data User data
 * @return array ['success' => bool, 'message' => string]
 */
function createAdminUser($conn, $id_negocio, $id_sucursal, $data) {
    $usuario = $conn->real_escape_string($data['usuario']);
    $password = hash('sha256', $data['password']);
    $nombre = $conn->real_escape_string($data['nombre']);
    $apellido = $conn->real_escape_string($data['apellido'] ?? '');
    $email = $conn->real_escape_string($data['email'] ?? '');
    
    // Check if user already exists
    $check = $conn->query("SELECT Id FROM usuarios WHERE Usuario = '$usuario'");
    if ($check->num_rows > 0) {
        // Update existing user
        $sql = "UPDATE usuarios SET 
                Password = '$password',
                Nombre = '$nombre',
                Apellido = '$apellido',
                Email = '$email',
                Rol = 'Admin',
                id_negocio_default = $id_negocio,
                id_sucursal_default = $id_sucursal,
                super_admin = 1,
                puede_cambiar_sucursal = 1,
                activo = 1
                WHERE Usuario = '$usuario'";
    } else {
        // Create new user
        $sql = "INSERT INTO usuarios (Usuario, Password, Nombre, Apellido, Email, Rol, id_negocio_default, id_sucursal_default, super_admin, puede_cambiar_sucursal, activo) 
                VALUES ('$usuario', '$password', '$nombre', '$apellido', '$email', 'Admin', $id_negocio, $id_sucursal, 1, 1, 1)";
    }
    
    if ($conn->query($sql)) {
        $user_id = $check->num_rows > 0 ? $check->fetch_assoc()['Id'] : $conn->insert_id;
        
        // Create user-business-branch assignment
        $sql_asign = "INSERT INTO usuarios_negocios (id_usuario, id_negocio, id_sucursal, rol_en_negocio, activo) 
                      VALUES ($user_id, $id_negocio, $id_sucursal, 'Admin', 1)
                      ON DUPLICATE KEY UPDATE activo = 1";
        $conn->query($sql_asign);
        
        return [
            'success' => true,
            'message' => 'Usuario administrador creado exitosamente'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al crear usuario: ' . $conn->error
        ];
    }
}

/**
 * Mark installation as complete
 * @param mysqli $conn Database connection
 * @param string $usuario Username who installed
 * @return array ['success' => bool, 'message' => string]
 */
function markInstallationComplete($conn, $usuario) {
    $version = '2.0.0';
    $usuario = $conn->real_escape_string($usuario);
    
    $sql = "INSERT INTO instalacion (version, instalado_por, database_version) 
            VALUES ('$version', '$usuario', '2.0.0')";
    
    if ($conn->query($sql)) {
        return [
            'success' => true,
            'message' => 'Instalación completada'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al marcar instalación: ' . $conn->error
        ];
    }
}

/**
 * Save database configuration to db_connect.php
 * @param string $host Database host
 * @param string $user Database user
 * @param string $pass Database password
 * @param string $dbname Database name
 * @return array ['success' => bool, 'message' => string]
 */
function saveDatabaseConfig($host, $user, $pass, $dbname) {
    $config_file = dirname(__DIR__) . '/db_connect.php';
    
    $config_content = "<?php
// Configura tu zona horaria para MySQL
date_default_timezone_set('America/Tegucigalpa');

\$host = \"$host\";
\$user = \"$user\"; // Tu usuario de MySQL
\$pass = \"$pass\";     // Tu contraseña de MySQL
\$db_name = \"$dbname\"; // El nombre de tu base de datos

\$conexion = new mysqli(\$host, \$user, \$pass, \$db_name);

if (\$conexion->connect_error) {
    die(\"Conexión fallida: \" . \$conexion->connect_error);
}

// Set charset
\$conexion->set_charset('utf8mb4');
?>";
    
    if (file_put_contents($config_file, $config_content)) {
        return [
            'success' => true,
            'message' => 'Configuración guardada'
        ];
    } else {
        return [
            'success' => false,
            'message' => 'Error al guardar configuración'
        ];
    }
}

/**
 * Check system requirements
 * @return array ['success' => bool, 'requirements' => array]
 */
function checkSystemRequirements() {
    $requirements = [
        'php_version' => [
            'name' => 'PHP Version >= 7.4',
            'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
            'value' => PHP_VERSION
        ],
        'mysqli' => [
            'name' => 'MySQLi Extension',
            'status' => extension_loaded('mysqli'),
            'value' => extension_loaded('mysqli') ? 'Instalado' : 'No instalado'
        ],
        'json' => [
            'name' => 'JSON Extension',
            'status' => extension_loaded('json'),
            'value' => extension_loaded('json') ? 'Instalado' : 'No instalado'
        ],
        'mbstring' => [
            'name' => 'Mbstring Extension',
            'status' => extension_loaded('mbstring'),
            'value' => extension_loaded('mbstring') ? 'Instalado' : 'No instalado'
        ],
        'writable_root' => [
            'name' => 'Directorio raíz escribible',
            'status' => is_writable(dirname(__DIR__)),
            'value' => is_writable(dirname(__DIR__)) ? 'Sí' : 'No'
        ],
        'writable_uploads' => [
            'name' => 'Directorio uploads escribible',
            'status' => is_writable(dirname(__DIR__) . '/uploads') || mkdir(dirname(__DIR__) . '/uploads', 0755, true),
            'value' => is_writable(dirname(__DIR__) . '/uploads') ? 'Sí' : 'No'
        ]
    ];
    
    $all_passed = true;
    foreach ($requirements as $req) {
        if (!$req['status']) {
            $all_passed = false;
            break;
        }
    }
    
    return [
        'success' => $all_passed,
        'requirements' => $requirements
    ];
}

/**
 * Migrate existing data to first business/branch
 * @param mysqli $conn Database connection
 * @param int $id_negocio Business ID
 * @param int $id_sucursal Branch ID
 * @return array ['success' => bool, 'message' => string, 'migrated' => array]
 */
function migrateExistingData($conn, $id_negocio, $id_sucursal) {
    $migrated = [];
    
    // Check and migrate productos
    $result = $conn->query("SHOW COLUMNS FROM productos LIKE 'id_negocio'");
    if ($result->num_rows > 0) {
        $conn->query("UPDATE productos SET id_negocio = $id_negocio, id_sucursal = $id_sucursal WHERE id_negocio IS NULL OR id_negocio = 0");
        $migrated['productos'] = $conn->affected_rows;
    }
    
    // Check and migrate ventas
    $result = $conn->query("SHOW COLUMNS FROM ventas LIKE 'id_negocio'");
    if ($result->num_rows > 0) {
        $conn->query("UPDATE ventas SET id_negocio = $id_negocio, id_sucursal = $id_sucursal WHERE id_negocio IS NULL OR id_negocio = 0");
        $migrated['ventas'] = $conn->affected_rows;
    }
    
    // Check and migrate usuarios
    $result = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'id_negocio_default'");
    if ($result->num_rows > 0) {
        $conn->query("UPDATE usuarios SET id_negocio_default = $id_negocio, id_sucursal_default = $id_sucursal WHERE id_negocio_default IS NULL OR id_negocio_default = 0");
        $migrated['usuarios'] = $conn->affected_rows;
    }
    
    // Check and migrate clientes
    $result = $conn->query("SHOW COLUMNS FROM clientes LIKE 'id_negocio'");
    if ($result->num_rows > 0) {
        $conn->query("UPDATE clientes SET id_negocio = $id_negocio WHERE id_negocio IS NULL OR id_negocio = 0");
        $migrated['clientes'] = $conn->affected_rows;
    }
    
    // Check and migrate proveedores
    $result = $conn->query("SHOW COLUMNS FROM proveedores LIKE 'id_negocio'");
    if ($result->num_rows > 0) {
        $conn->query("UPDATE proveedores SET id_negocio = $id_negocio WHERE id_negocio IS NULL OR id_negocio = 0");
        $migrated['proveedores'] = $conn->affected_rows;
    }
    
    return [
        'success' => true,
        'message' => 'Datos migrados exitosamente',
        'migrated' => $migrated
    ];
}
?>
