<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class ChessSocket implements MessageComponentInterface {

    protected array $clients   = [];
    protected array $gameRooms = [];
    protected array $connMeta  = [];

    public function onOpen(ConnectionInterface $conn) {
        $this->clients[$conn->resourceId] = $conn;
        echo "Nuova connessione: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $type = $data['type'] ?? '';

        switch ($type) {

            case 'join':
                $this->connMeta[$from->resourceId] = [
                    'game_id'  => $data['game_id'],
                    'username' => $data['username'],
                ];
                $this->gameRooms[$data['game_id']][$from->resourceId] = $from;
                echo "{$data['username']} si è unito alla partita {$data['game_id']}\n";
                break;

            case 'move':
                $gameId = $this->connMeta[$from->resourceId]['game_id'] ?? null;
                if (!$gameId) break;

                foreach ($this->gameRooms[$gameId] as $rid => $conn) {
                    if ($rid !== $from->resourceId) {
                        $conn->send(json_encode([
                            'type'     => 'opponent_move',
                            'fen'      => $data['fen'],
                            'turn'     => $data['turn'],
                            'moves'    => $data['moves']    ?? [],
                            'timers'   => $data['timers']   ?? [],
                            'notation' => $data['notation'] ?? null,
                        ]));
                    }
                }
                break;

            case 'game_over':
                $gameId = $this->connMeta[$from->resourceId]['game_id'] ?? null;
                if (!$gameId) break;

                foreach ($this->gameRooms[$gameId] as $conn) {
                    $conn->send(json_encode([
                        'type'   => 'game_over',
                        'reason' => $data['reason'],
                        'winner' => $data['winner'] ?? null,
                    ]));
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $gameId = $this->connMeta[$conn->resourceId]['game_id'] ?? null;
        if ($gameId) {
            unset($this->gameRooms[$gameId][$conn->resourceId]);
        }
        unset($this->connMeta[$conn->resourceId]);
        unset($this->clients[$conn->resourceId]);
        echo "Connessione chiusa: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Errore: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = IoServer::factory(
    new HttpServer(new WsServer(new ChessSocket())),
    8080
);
echo "WebSocket server avviato sulla porta 8080\n";
$server->run();