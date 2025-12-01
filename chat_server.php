<?php
// chat_server.php
// ¡IMPORTANTE! Este script se debe ejecutar desde la línea de comandos, no en un navegador.
// Ejecuta: php chat_server.php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

// Suprimir warnings de deprecación de PHP 8.2 (Ratchet aún no está actualizado)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', 1);

echo "🚀 Iniciando servidor WebSocket en puerto 8080...\n";

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $users; // [resourceId => ['id' => userId, 'name' => userName, 'connection' => $conn]]
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->users = [];
        
        // Conexión a la base de datos
        $this->db = new mysqli("127.0.0.1", "root", "", "tiendasrey");
        if ($this->db->connect_error) {
            die("❌ Error de conexión a la BD: " . $this->db->connect_error . "\n");
        }
        $this->db->set_charset("utf8mb4");
        echo "✅ Conectado a la base de datos.\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "🔵 Nueva conexión: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if (!$data || !isset($data['type'])) {
            echo "⚠️ Mensaje inválido recibido de {$from->resourceId}\n";
            return;
        }
        
        echo "📩 Mensaje tipo '{$data['type']}' de {$from->resourceId}\n";

        switch ($data['type']) {
            case 'login':
                $this->handleLogin($from, $data);
                break;

            case 'new_message':
                $this->handleNewMessage($from, $data);
                break;

            case 'message_read':
                $this->handleMessageRead($data);
                break;

            case 'message_delivered':
                $this->handleMessageDelivered($data);
                break;

            case 'typing':
                $this->handleTyping($data);
                break;

            case 'heartbeat':
                $this->handleHeartbeat($data);
                break;

            case 'message_deleted':
                $this->broadcastToUser($data['receiverId'], $data);
                break;

            case 'message_edited':
                $this->broadcastToUser($data['receiverId'], $data);
                break;

            default:
                echo "⚠️ Tipo de mensaje desconocido: {$data['type']}\n";
        }
    }

    protected function handleLogin($from, $data) {
        $this->users[$from->resourceId] = [
            'id' => $data['userId'],
            'name' => $data['userName'],
            'connection' => $from
        ];
        echo "✅ Usuario {$data['userName']} (ID: {$data['userId']}) ha iniciado sesión.\n";
        
        // Actualizar estado en BD
        $userId = (int)$data['userId'];
        $this->db->query("UPDATE usuarios SET Ultima_Actividad = NOW() WHERE Id = $userId");
        
        // Enviar lista de usuarios conectados a todos
        $this->broadcastUserList();
    }

    protected function handleNewMessage($from, $data) {
        // El mensaje ya fue guardado en BD por send_message.php
        // Solo necesitamos retransmitirlo
        
        $receiverId = (int)$data['id_receptor'];
        $senderId = (int)$data['id_emisor'];
        
        echo "💬 Retransmitiendo mensaje ID: {$data['id']} de $senderId a $receiverId\n";
        
        // Enviar al receptor
        $sent = $this->broadcastToUser($receiverId, $data);
        
        // Si el receptor está conectado, marcar como entregado
        if ($sent && isset($data['id'])) {
            $messageId = (int)$data['id'];
            $this->db->query("UPDATE mensajes_chat SET Estado_Entrega = 'delivered' WHERE Id = $messageId");
            
            // Notificar al emisor que fue entregado
            $deliveredData = [
                'type' => 'message_delivered',
                'messageId' => $messageId
            ];
            $this->broadcastToUser($senderId, $deliveredData);
        }
    }

    protected function handleMessageRead($data) {
        $messageId = (int)$data['messageId'];
        $senderId = (int)$data['senderId'];
        
        // Actualizar en BD
        $this->db->query("UPDATE mensajes_chat SET leido = 1, Estado_Entrega = 'read' WHERE Id = $messageId");
        
        // Notificar al emisor
        $readData = [
            'type' => 'message_read',
            'messageId' => $messageId
        ];
        $this->broadcastToUser($senderId, $readData);
        
        echo "✓✓ Mensaje $messageId marcado como leído\n";
    }

    protected function handleMessageDelivered($data) {
        $messageId = (int)$data['messageId'];
        
        // Actualizar en BD
        $this->db->query("UPDATE mensajes_chat SET Estado_Entrega = 'delivered' WHERE Id = $messageId");
        
        echo "✓ Mensaje $messageId marcado como entregado\n";
    }

    protected function handleTyping($data) {
        $receiverId = (int)$data['receiverId'];
        $this->broadcastToUser($receiverId, $data);
    }

    protected function handleHeartbeat($data) {
        $userId = (int)$data['userId'];
        $this->db->query("UPDATE usuarios SET Ultima_Actividad = NOW() WHERE Id = $userId");
        
        // Enviar lista actualizada de usuarios en línea
        $this->broadcastUserList();
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        if (isset($this->users[$conn->resourceId])) {
            $userId = $this->users[$conn->resourceId]['id'];
            $userName = $this->users[$conn->resourceId]['name'];
            echo "🔴 Usuario desconectado: $userName (ID: $userId)\n";
            unset($this->users[$conn->resourceId]);
            
            // Notificar a todos que el usuario se ha desconectado
            $this->broadcastUserList();
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "❌ Error de conexión: {$e->getMessage()}\n";
        $conn->close();
    }

    // Función para enviar mensaje a un usuario específico
    protected function broadcastToUser($userId, $data) {
        foreach ($this->users as $resourceId => $user) {
            if ($user['id'] == $userId) {
                $user['connection']->send(json_encode($data));
                echo "📤 Mensaje enviado a usuario ID: $userId\n";
                return true;
            }
        }
        echo "⚠️ Usuario ID: $userId no está conectado.\n";
        return false;
    }

    // Función para enviar la lista de usuarios conectados a todos
    protected function broadcastUserList() {
        $onlineUsers = [];
        foreach ($this->users as $user) {
            $onlineUsers[] = $user['id'];
        }

        $data = [
            'type' => 'user_status',
            'onlineUsers' => $onlineUsers
        ];

        $message = json_encode($data);
        foreach ($this->users as $user) {
            $user['connection']->send($message);
        }
        echo "📡 Lista de usuarios en línea enviada: " . json_encode($onlineUsers) . "\n";
    }
}

// Crear y ejecutar el servidor
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    ),
    8080,
    '0.0.0.0' // Escuchar en todas las interfaces, no solo localhost
);

echo "✅ Servidor WebSocket escuchando en 0.0.0.0:8080\n";
echo "📝 Accesible desde localhost y dominios externos\n";
echo "📝 Esperando conexiones...\n\n";

$server->run();