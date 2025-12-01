<?php
/**
 * Servidor WebSocket para Red Social en Tiempo Real
 * Maneja: publicaciones, reacciones, comentarios, stories, presencia
 */

require dirname(__DIR__) . '/ReySystemDemo/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

// Suprimir warnings de deprecación de PHP 8.2
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

class SocialServer implements MessageComponentInterface {
    protected $clients;
    protected $users; // userId => connection
    protected $conn;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        
        // Conexión a base de datos (usar 127.0.0.1 en lugar de localhost para XAMPP)
        $this->conn = new mysqli("127.0.0.1", "root", "", "tiendasrey");
        if ($this->conn->connect_error) {
            die("Error de conexión: " . $this->conn->connect_error);
        }
        $this->conn->set_charset("utf8mb4");
        
        echo "🚀 Servidor Social WebSocket iniciado\n";
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nueva conexión: {$conn->resourceId}\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                return;
            }
            
            echo "📨 Mensaje recibido: {$data['type']}\n";
            
            switch ($data['type']) {
                case 'login':
                    $this->handleLogin($from, $data);
                    break;
                    
                case 'new_post':
                    $this->handleNewPost($from, $data);
                    break;
                    
                case 'new_reaction':
                    $this->handleNewReaction($from, $data);
                    break;
                    
                case 'new_comment':
                    $this->handleNewComment($from, $data);
                    break;
                    
                case 'new_story':
                    $this->handleNewStory($from, $data);
                    break;
                    
                case 'typing':
                    $this->handleTyping($from, $data);
                    break;
                    
                case 'heartbeat':
                    $this->handleHeartbeat($from, $data);
                    break;
            }
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        // Encontrar y remover usuario
        $userId = null;
        foreach ($this->users as $uid => $connection) {
            if ($connection === $conn) {
                $userId = $uid;
                break;
            }
        }
        
        if ($userId) {
            unset($this->users[$userId]);
            
            // Broadcast user offline
            $this->broadcast([
                'type' => 'user_status',
                'userId' => $userId,
                'status' => 'offline',
                'onlineUsers' => array_keys($this->users)
            ]);
            
            echo "Usuario {$userId} desconectado\n";
        }
        
        $this->clients->detach($conn);
        echo "Conexión cerrada: {$conn->resourceId}\n";
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
    
    private function handleLogin($conn, $data) {
        $userId = (int)$data['userId'];
        $this->users[$userId] = $conn;
        
        // Actualizar última actividad
        $stmt = $this->conn->prepare("UPDATE usuarios SET Ultima_Actividad = NOW() WHERE Id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
        
        // Enviar lista de usuarios en línea
        $conn->send(json_encode([
            'type' => 'user_status',
            'onlineUsers' => array_keys($this->users)
        ]));
        
        // Broadcast a todos que este usuario está en línea
        $this->broadcast([
            'type' => 'user_status',
            'userId' => $userId,
            'status' => 'online',
            'onlineUsers' => array_keys($this->users)
        ], $userId);
        
        echo "✅ Usuario {$userId} conectado\n";
    }
    
    private function handleNewPost($conn, $data) {
        // Broadcast nueva publicación a todos los usuarios
        $this->broadcast([
            'type' => 'new_post',
            'post' => $data['post']
        ]);
        
        echo "📝 Nueva publicación de usuario {$data['post']['usuario_id']}\n";
    }
    
    private function handleNewReaction($conn, $data) {
        $publicacionId = (int)$data['publicacionId'];
        $userId = (int)$data['userId'];
        $reactionType = $data['reactionType'];
        
        // Obtener el dueño de la publicación
        $stmt = $this->conn->prepare("SELECT usuario_id FROM publicaciones WHERE id = ?");
        $stmt->bind_param("i", $publicacionId);
        $stmt->execute();
        $result = $stmt->get_result();
        $publicacion = $result->fetch_assoc();
        $stmt->close();
        
        if ($publicacion && $publicacion['usuario_id'] != $userId) {
            // Crear notificación solo si no es el mismo usuario
            $stmt = $this->conn->prepare("INSERT INTO notificaciones_red (usuario_id, tipo, emisor_id, publicacion_id) VALUES (?, 'reaction', ?, ?)");
            $stmt->bind_param("iii", $publicacion['usuario_id'], $userId, $publicacionId);
            $stmt->execute();
            $notifId = $stmt->insert_id;
            $stmt->close();
            
            // Enviar notificación en tiempo real al dueño de la publicación
            if (isset($this->users[$publicacion['usuario_id']])) {
                $this->users[$publicacion['usuario_id']]->send(json_encode([
                    'type' => 'new_notification',
                    'notification' => [
                        'id' => $notifId,
                        'tipo' => 'reaction',
                        'mensaje' => $data['userName'] . ' reaccionó a tu publicación'
                    ]
                ]));
            }
        }
        
        // Broadcast reacción a todos
        $this->broadcast([
            'type' => 'new_reaction',
            'publicacionId' => $publicacionId,
            'userId' => $userId,
            'reactionType' => $reactionType,
            'userName' => $data['userName'] ?? 'Usuario'
        ]);
        
        echo "❤️ Nueva reacción '{$reactionType}' en publicación {$publicacionId}\n";
    }
    
    private function handleNewComment($conn, $data) {
        $publicacionId = (int)$data['publicacionId'];
        
        // Broadcast comentario a todos
        $this->broadcast([
            'type' => 'new_comment',
            'publicacionId' => $publicacionId,
            'comment' => $data['comment']
        ]);
        
        echo "💬 Nuevo comentario en publicación {$publicacionId}\n";
    }
    
    private function handleNewStory($conn, $data) {
        // Broadcast nueva story a todos
        $this->broadcast([
            'type' => 'new_story',
            'story' => $data['story']
        ]);
        
        echo "📸 Nueva story de usuario {$data['story']['usuario_id']}\n";
    }
    
    private function handleTyping($conn, $data) {
        $publicacionId = (int)$data['publicacionId'];
        $userId = (int)$data['userId'];
        $isTyping = $data['isTyping'];
        
        // Broadcast typing indicator
        $this->broadcast([
            'type' => 'typing',
            'publicacionId' => $publicacionId,
            'userId' => $userId,
            'userName' => $data['userName'] ?? 'Usuario',
            'isTyping' => $isTyping
        ], $userId);
    }
    
    private function handleHeartbeat($conn, $data) {
        $userId = (int)$data['userId'];
        
        // Actualizar última actividad
        $stmt = $this->conn->prepare("UPDATE usuarios SET Ultima_Actividad = NOW() WHERE Id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    private function broadcast($data, $excludeUserId = null) {
        $message = json_encode($data);
        
        foreach ($this->users as $userId => $client) {
            if ($excludeUserId && $userId == $excludeUserId) {
                continue;
            }
            $client->send($message);
        }
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SocialServer()
        )
    ),
    8081 // Puerto diferente al chat (8080)
);

echo "🌐 Servidor Social escuchando en 0.0.0.0:8081\n";
$server->run();
