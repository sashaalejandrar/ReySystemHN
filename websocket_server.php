<?php
require 'vendor/autoload.php';

use Ratchet\Server\Server;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\MessageComponent;
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\Conn;

 $server = new HttpServer();
 $server->route('/chat/{id_chat}', function (ConnectionInterface $conn, Request $request, $id_chat) {
    $conn->on('open', function (ConnectionInterface $conn) {
        echo "¡Conexión WebSocket abierta para el chat {$id_chat}!\n";
    });

    $conn->on('message', function (ConnectionInterface $conn, MessageComponent $msg) {
        // Guardar el mensaje en la base de datos
        $mensaje_data = json_decode($msg->getPayload());
        
        // Insertar el mensaje en tu tabla
        $stmt = $conexion->prepare("INSERT INTO mensajes_chat (...) VALUES (...)");
        // ... bind_param y execute ...

        // Enviar el mensaje a todos los suscriptores del chat
        $conn->send(json_encode($mensaje_data));
    });
});

 $server->run();