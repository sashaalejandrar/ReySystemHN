<?php
require __DIR__ . '/vendor/autoload.php';
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $users;
    protected $db;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        
        // Conexión a la base de datos
        $this->db = new mysqli("127.0.0.1", "root", "", "tiendasrey");
        
        if ($this->db->connect_error) {
            die("❌ Error BD: " . $this->db->connect_error . "\n");
        }
        
        $this->db->set_charset("utf8mb4");
        echo "✅ Servidor iniciado y conectado a BD\n";
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "🔵 Nueva conexión: {$conn->resourceId}\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data || !isset($data['type'])) {
            echo "⚠️ Mensaje inválido\n";
            return;
        }
        
        echo "📨 Tipo: {$data['type']}\n";
        
        switch($data['type']) {
            case 'login':
                $userId = (int)$data['userId'];
                $this->users[$from->resourceId] = [
                    'id' => $userId,
                    'name' => $data['userName'],
                    'profile' => $data['userProfile'],
                    'connection' => $from
                ];
                
                echo "✅ Usuario conectado: {$data['userName']} (ID: {$userId})\n";
                
                // Actualizar BD
                $stmt = $this->db->prepare("UPDATE usuarios SET Ultima_Actividad = NOW() WHERE Id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                
                $this->broadcastUserStatus($userId, 'online');
                break;
                
            case 'message':
                $senderId = (int)$data['senderId'];
                $receiverId = (int)$data['receiverId'];
                $message = htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8');
                
                echo "📤 Mensaje: $senderId → $receiverId\n";
                echo "📝 Contenido: $message\n";
                
                // Obtener datos del emisor
                $stmt = $this->db->prepare("SELECT Nombre, Apellido, Usuario, Perfil FROM usuarios WHERE Id = ?");
                $stmt->bind_param("i", $senderId);
                
                if (!$stmt->execute()) {
                    echo "❌ Error al obtener emisor: " . $stmt->error . "\n";
                    $stmt->close();
                    return;
                }
                
                $result = $stmt->get_result();
                $emisor = $result->fetch_assoc();
                $stmt->close();
                
                if (!$emisor) {
                    echo "❌ Emisor no encontrado: $senderId\n";
                    return;
                }
                
                echo "👤 Emisor: {$emisor['Nombre']} {$emisor['Apellido']} (@{$emisor['Usuario']})\n";
                
                // Insertar en BD - CORREGIDO CON PERFIL
                $nombre = $emisor['Nombre'];
                $apellido = $emisor['Apellido'];
                $usuario = $emisor['Usuario'];
                $perfil = $emisor['Perfil'];
                
                $query = "INSERT INTO mensajes_chat (Nombre, Apellido, Usuario, Perfil, Id_Emisor, Id_Receptor, Mensaje, Fecha_Mensaje, Leido) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), 0)";
                $stmt = $this->db->prepare($query);
                
                if (!$stmt) {
                    echo "❌ Error preparando query: " . $this->db->error . "\n";
                    return;
                }
                
                $stmt->bind_param("ssssiis", 
                    $nombre,
                    $apellido,
                    $usuario,
                    $perfil,
                    $senderId,
                    $receiverId,
                    $message
                );
                
                if ($stmt->execute()) {
                    $messageId = $this->db->insert_id;
                    echo "💾 ✅ Guardado en BD con ID: $messageId\n";
                    echo "   Nombre: $nombre\n";
                    echo "   Apellido: $apellido\n";
                    echo "   Usuario: $usuario\n";
                    echo "   Perfil: $perfil\n";
                } else {
                    echo "❌ Error al insertar: " . $stmt->error . "\n";
                    echo "   Query: $query\n";
                    $stmt->close();
                    return;
                }
                $stmt->close();
                
                // Preparar respuesta
                $messageData = json_encode([
                    'type' => 'new_message',
                    'id' => $messageId,
                    'id_emisor' => $senderId,
                    'id_receptor' => $receiverId,
                    'emisor_nombre' => $nombre,
                    'emisor_apellido' => $apellido,
                    'emisor_usuario' => $usuario,
                    'emisor_perfil' => $emisor['Perfil'],
                    'mensaje' => $message,
                    'fecha' => date('g:i A'),
                    'leido' => 0
                ], JSON_UNESCAPED_UNICODE);
                
                echo "📦 JSON preparado: " . strlen($messageData) . " bytes\n";
                
                // Enviar a emisor y receptor
                $sent = 0;
                foreach ($this->users as $resourceId => $user) {
                    if ($user['id'] == $senderId || $user['id'] == $receiverId) {
                        $user['connection']->send($messageData);
                        $sent++;
                        echo "✅ Enviado a: {$user['name']}\n";
                    }
                }
                
                if ($sent == 0) {
                    echo "⚠️ Ningún usuario conectado\n";
                }
                break;
                
            case 'mark_read':
                $messageId = (int)$data['messageId'];
                $userId = (int)$data['userId'];
                
                $stmt = $this->db->prepare("UPDATE mensajes_chat SET Leido = 1 WHERE Id = ? AND Id_Receptor = ?");
                $stmt->bind_param("ii", $messageId, $userId);
                $stmt->execute();
                $stmt->close();
                
                // Notificar al emisor
                $stmt = $this->db->prepare("SELECT Id_Emisor FROM mensajes_chat WHERE Id = ?");
                $stmt->bind_param("i", $messageId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                if ($row) {
                    $emisorId = $row['Id_Emisor'];
                    foreach ($this->users as $user) {
                        if ($user['id'] == $emisorId) {
                            $user['connection']->send(json_encode([
                                'type' => 'message_read',
                                'messageId' => $messageId
                            ]));
                            echo "✅ Lectura notificada\n";
                            break;
                        }
                    }
                }
                break;
                
            case 'typing':
                $receiverId = (int)$data['receiverId'];
                foreach ($this->users as $user) {
                    if ($user['id'] == $receiverId) {
                        $user['connection']->send(json_encode([
                            'type' => 'user_typing',
                            'userId' => $data['senderId'],
                            'userName' => $data['senderName'],
                            'isTyping' => $data['isTyping']
                        ]));
                        break;
                    }
                }
                break;
                
            case 'update_status':
                $userId = (int)$data['userId'];
                $stmt = $this->db->prepare("UPDATE usuarios SET Ultima_Actividad = NOW() WHERE Id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                break;
                
            case 'add_reaction':
                $messageId = (int)$data['messageId'];
                $emoji = $data['emoji'];
                $userId = (int)$data['userId'];
                
                $stmt = $this->db->prepare("SELECT Reacciones, Id_Emisor, Id_Receptor FROM mensajes_chat WHERE Id = ?");
                $stmt->bind_param("i", $messageId);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();
                
                if ($row) {
                    $reacciones = $row['Reacciones'] ? json_decode($row['Reacciones'], true) : [];
                    
                    if (!isset($reacciones[$emoji])) {
                        $reacciones[$emoji] = [];
                    }
                    
                    if (!in_array($userId, $reacciones[$emoji])) {
                        $reacciones[$emoji][] = $userId;
                    }
                    
                    $reaccionesJson = json_encode($reacciones);
                    
                    $stmt = $this->db->prepare("UPDATE mensajes_chat SET Reacciones = ? WHERE Id = ?");
                    $stmt->bind_param("si", $reaccionesJson, $messageId);
                    $stmt->execute();
                    $stmt->close();
                    
                    // Notificar
                    $notificationData = json_encode([
                        'type' => 'reaction_added',
                        'messageId' => $messageId,
                        'reacciones' => $reacciones
                    ]);
                    
                    foreach ($this->users as $user) {
                        if ($user['id'] == $row['Id_Emisor'] || $user['id'] == $row['Id_Receptor']) {
                            $user['connection']->send($notificationData);
                        }
                    }
                }
                break;
                
            default:
                echo "⚠️ Tipo desconocido: {$data['type']}\n";
        }
    }
    
    private function broadcastUserStatus($userId, $status) {
        $data = json_encode([
            'type' => $status == 'online' ? 'user_online' : 'user_offline',
            'userId' => $userId
        ]);
        
        foreach ($this->users as $user) {
            $user['connection']->send($data);
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        if (isset($this->users[$conn->resourceId])) {
            $userId = $this->users[$conn->resourceId]['id'];
            $userName = $this->users[$conn->resourceId]['name'];
            
            echo "🔴 Desconectado: $userName (ID: $userId)\n";
            
            $stmt = $this->db->prepare("UPDATE usuarios SET Ultima_Actividad = NOW() WHERE Id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->close();
            
            $this->broadcastUserStatus($userId, 'offline');
            
            unset($this->users[$conn->resourceId]);
        }
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "❌ Error: {$e->getMessage()}\n";
        $conn->close();
    }
    
    public function __destruct() {
        if ($this->db) {
            $this->db->close();
        }
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080
);

echo "\n========================================\n";
echo "🚀 WebSocket Server: Puerto 8080\n";
echo "⏳ Esperando conexiones...\n";
echo "========================================\n\n";

$server->run();