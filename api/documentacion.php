<?php
/**
 * API CRUD para documentación de módulos
 */

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$conexion = new mysqli("localhost", "root", "", "tiendasrey");

// Obtener rol del usuario
$stmt = $conexion->prepare("SELECT Rol FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $_SESSION['usuario']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$esAdmin = $user && strtolower($user['Rol']) === 'admin';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    if ($method === 'GET') {
        if ($action === 'list') {
            // Listar toda la documentación
            $categoria = $_GET['categoria'] ?? null;
            $busqueda = $_GET['busqueda'] ?? null;
            
            $sql = "SELECT * FROM documentacion_modulos WHERE 1=1";
            $params = [];
            $types = "";
            
            if ($categoria) {
                $sql .= " AND categoria = ?";
                $params[] = $categoria;
                $types .= "s";
            }
            
            if ($busqueda) {
                $sql .= " AND (nombre_modulo LIKE ? OR descripcion LIKE ? OR proposito LIKE ?)";
                $searchTerm = "%{$busqueda}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $types .= "sss";
            }
            
            $sql .= " ORDER BY categoria, nombre_modulo";
            
            $stmt = $conexion->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $documentacion = [];
            while ($row = $result->fetch_assoc()) {
                $documentacion[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'documentacion' => $documentacion,
                'total' => count($documentacion)
            ]);
            
        } elseif ($action === 'get') {
            // Obtener documentación específica
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('ID requerido');
            }
            
            $stmt = $conexion->prepare("SELECT * FROM documentacion_modulos WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                echo json_encode([
                    'success' => true,
                    'documentacion' => $row
                ]);
            } else {
                throw new Exception('Documentación no encontrada');
            }
            
        } elseif ($action === 'categorias') {
            // Obtener lista de categorías
            $sql = "SELECT categoria, COUNT(*) as total 
                    FROM documentacion_modulos 
                    WHERE categoria IS NOT NULL AND categoria != '' 
                    GROUP BY categoria 
                    ORDER BY categoria";
            
            $result = $conexion->query($sql);
            
            $categorias = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $categorias[] = $row;
                }
            }
            
            echo json_encode([
                'success' => true,
                'categorias' => $categorias
            ]);
        }
        
    } elseif ($method === 'POST') {
        if (!$esAdmin) {
            throw new Exception('Solo administradores pueden modificar documentación');
        }
        
        if ($action === 'create' || $action === 'update') {
            $id = $_POST['id'] ?? null;
            $nombre_modulo = $_POST['nombre_modulo'] ?? '';
            $ruta_archivo = $_POST['ruta_archivo'] ?? '';
            $categoria = $_POST['categoria'] ?? '';
            $descripcion = $_POST['descripcion'] ?? '';
            $proposito = $_POST['proposito'] ?? '';
            $como_usar = $_POST['como_usar'] ?? '';
            $ejemplos = $_POST['ejemplos'] ?? '';
            $permisos_requeridos = $_POST['permisos_requeridos'] ?? '';
            
            if ($id) {
                // Actualizar
                $stmt = $conexion->prepare("
                    UPDATE documentacion_modulos SET
                    nombre_modulo = ?,
                    categoria = ?,
                    descripcion = ?,
                    proposito = ?,
                    como_usar = ?,
                    ejemplos = ?,
                    permisos_requeridos = ?,
                    generado_por_ia = 0,
                    version = version + 1
                    WHERE id = ?
                ");
                $stmt->bind_param("sssssssi",
                    $nombre_modulo, $categoria, $descripcion, $proposito,
                    $como_usar, $ejemplos, $permisos_requeridos, $id
                );
            } else {
                // Crear
                $stmt = $conexion->prepare("
                    INSERT INTO documentacion_modulos
                    (nombre_modulo, ruta_archivo, categoria, descripcion, proposito, como_usar, ejemplos, permisos_requeridos, creado_por, generado_por_ia)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
                ");
                $stmt->bind_param("sssssssss",
                    $nombre_modulo, $ruta_archivo, $categoria, $descripcion,
                    $proposito, $como_usar, $ejemplos, $permisos_requeridos,
                    $_SESSION['usuario']
                );
            }
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => $id ? 'Documentación actualizada' : 'Documentación creada',
                    'id' => $id ?? $conexion->insert_id
                ]);
            } else {
                throw new Exception('Error al guardar documentación');
            }
        }
        
    } elseif ($method === 'DELETE') {
        if (!$esAdmin) {
            throw new Exception('Solo administradores pueden eliminar documentación');
        }
        
        parse_str(file_get_contents("php://input"), $_DELETE);
        $id = $_DELETE['id'] ?? null;
        
        if (!$id) {
            throw new Exception('ID requerido');
        }
        
        $stmt = $conexion->prepare("DELETE FROM documentacion_modulos WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Documentación eliminada'
            ]);
        } else {
            throw new Exception('Error al eliminar documentación');
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conexion->close();
