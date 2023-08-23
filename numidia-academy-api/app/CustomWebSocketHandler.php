<?php

namespace App;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use Ratchet\WebSocket\MessageComponentInterface;

class CustomWebSocketHandler implements MessageComponentInterface
{

    private $connections = [];

    function onOpen(ConnectionInterface $conn)
    {
        $this->connections[] = $conn;
    }

    function onClose(ConnectionInterface $conn)
    {
        foreach ($this->connections as $key => $connection) {
            if ($connection->resourceId == $conn->resourceId) {
                $connection->close();
                unset($this->connections[$key]);
                break;
            }
        }
    }

    function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
        abort(500, $e->getMessage() . $e->getLine());
    }

    function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        switch ($data['action']) {
            case 'register':
                $connId = null;
                foreach ($this->connections as $key => $conn) {
                    if ($conn->resourceId == $from->resourceId) {
                        $connId = $key;
                        break;
                    }
                }

                $conn = $this->connections[$connId];
                $this->connections[$data['registred_client_id']] = $conn;
                unset($this->connections[$connId]);
                break;
            case 'message':
                if ($conn = $this->connections[$data['to']]) {
                    $conn->send(json_encode(['action' => 'message', 'message' => $data['message']]));
                } else {
                    $from->send(json_encode(['action' => 'message', 'message' => 'The user that you are trying to connect with is not online!']));
                }
                break;
            default:
                break;
        }
    }
}
