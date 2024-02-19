<?php

namespace App;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class WebSocketHandler  implements MessageComponentInterface
{
    public static $clients;

    public function __construct()
    {
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $queryParameters);

        if (isset($queryParameters['user_id'])) {
            $user_id = $queryParameters['user_id'];

            self::$clients[$user_id] = $conn;
        } else {
            echo "error 'user_id' query parameter is missing.\n";
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg);
        if ($data && $data->user_id) {
            self::send_message($data->user_id, $data->message);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        foreach (self::$clients as $user_id => $client) {
            if ($client == $conn) {
                unset(self::$clients[$user_id]);
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public static function send_message($user_id, $message)
    {
        if (isset(self::$clients[$user_id])) {
            $recipient = self::$clients[$user_id];
            $data = json_encode([
                'user_id' => $user_id,
                'message' => $message,
            ]);

            $recipient->send($data);

            return true;
        }

        return false;
    }
}
