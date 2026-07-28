<?php
require 'vendor/autoload.php';
require 'app/models/GameModel.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Services\ChessServices;
use App\Models\GameModel;

class ChessSocket implements MessageComponentInterface {

    protected array $clients       = [];
    protected array $gameRooms     = [];
    protected array $connMeta      = [];
    protected array $gameInstances = [];
    protected array $gameFens      = [];
    protected array $moveHistory   = [];
    protected array $pendingPromotions = [];  // Aggiunta mancante

    protected array $allowedOrigins = [
        'https://chessnova.win',
        'https://www.chessnova.win',
    ];

    protected GameModel $gameModel;

    public function __construct(GameModel $gameModel) {
        $this->gameModel = $gameModel;
    }

    public function onOpen(ConnectionInterface $conn) {

        // Controllo Origin
        $originHeader = $conn->httpRequest->getHeader('Origin');
        $origin       = $originHeader[0] ?? null;
        if (!$origin || !in_array($origin, $this->allowedOrigins, true)) {
            echo "Connessione rifiutata: Origin non consentito ({$origin})\n";
            $conn->close();
            return;
        }

        // Recupero PHPSESSID dai cookie
        $cookies = $conn->httpRequest->getHeader('Cookie');
        $sessionId = null;
        if (!empty($cookies)) {
            $pairs = explode(';', $cookies[0]);
            foreach ($pairs as $pair) {
                $parts = explode('=', trim($pair), 2);
                if (count($parts) === 2 && $parts[0] === 'PHPSESSID') {
                    $sessionId = $parts[1];
                    break;
                }
            }
        }

        if (!$sessionId || !preg_match('/^[a-zA-Z0-9,\-]{22,250}$/', $sessionId)) {
            echo "Connessione rifiutata: PHPSESSID mancante o malformato\n";
            $conn->send('Sessione mancante');
            $conn->close();
            return;
        }

        // Imposta l'ID di sessione e carica la sessione senza inviare header
        session_id($sessionId);
        ini_set('session.use_cookies', 0);
        ini_set('session.use_only_cookies', 0);
        ini_set('session.use_strict_mode', 0);
        session_start();
        error_log("WS SESSION: " . print_r($_SESSION, true));

        $gameId   = $_SESSION['id_partita'] ?? null;
        $username = $_SESSION['username'] ?? null;
        if (!$gameId || !$username) {
            session_write_close();
            $conn->send('Dati partita mancanti');
            $conn->close();
            return;
        }

        session_write_close();

        $colore = $this->gameModel->getPlayerColor($username);
        if (!$colore) {
            $conn->send('Impossibile determinare il colore del giocatore');
            $conn->close();
            return;
        }

        $this->connMeta[$conn->resourceId] = [
            'authenticated' => true,
            'game_id'  => $gameId,
            'username' => $username,
            'color'    => $colore === 'bianco' ? 'w' : 'b',
        ];

        $this->gameRooms[$gameId][$conn->resourceId] = $conn;
        $this->clients[$conn->resourceId] = $conn;

        if (!isset($this->gameInstances[$gameId])) {
            $this->gameInstances[$gameId] = new ChessServices();
        }
        if (!isset($this->gameFens[$gameId])) {
            $state = $this->gameModel->getState((int) $gameId);
            $this->gameFens[$gameId] = $state['fen'] ?? null;
        }

        echo "Nuova connessione: {$conn->resourceId} ({$username})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $type = $data['type'] ?? '';

        $meta = $this->connMeta[$from->resourceId] ?? null;
        if (!$meta || !($meta['authenticated'] ?? false)) {
            return;
        }

        $gameId   = $meta['game_id'];
        $username = $meta['username'];

        switch ($type) {

            case 'join':
                echo "{$username} si è unito alla partita {$gameId}\n";
                break;

            case 'move':
                $gameId = $meta['game_id'] ?? null;
                if (!$gameId) break;

                $fromSquare = $data['from'] ?? null;
                $toSquare   = $data['to']   ?? null;
                $piece      = $data['piece'] ?? null;
                if (!$fromSquare || !$toSquare || !$piece) break;

                $chessService = $this->gameInstances[$gameId] ?? null;
                $currentFen   = $this->gameFens[$gameId] ?? null;
                if (!$chessService || !$currentFen) break;

                if (!$this->gameModel->canPlayerMakeMove($gameId, $username, $piece, 'scacchi')) {
                    $from->send(json_encode(['type' => 'illegal_move', 'message' => 'Non è il tuo pezzo o non è il tuo turno']));
                    break;
                }

                $result = $chessService->isValidMove($currentFen, $piece, $fromSquare, $toSquare, 'scacchi');
                if ($result === false) {
                    $from->send(json_encode(['type' => 'illegal_move', 'message' => 'Mossa non valida']));
                    break;
                }

                $newFen = null;
                $check  = false;

                if (is_string($result)) {
                    $newFen = $result;
                } elseif (is_array($result)) {
                    if (isset($result['promozione'])) {
                        $this->pendingPromotions[$gameId] = [
                            'fen_base' => $result['fen_base'],
                            'from'     => $fromSquare,
                            'to'       => $toSquare,
                            'player'   => $from->resourceId,
                        ];
                        $from->send(json_encode(['type' => 'promotion_required', 'fen_base' => $result['fen_base']]));
                        break;
                    }
                    $newFen = $result['fen'] ?? null;
                    $check  = $result['check'] ?? false;
                }

                if (!$newFen) break;

                $this->gameFens[$gameId] = $newFen;
                $this->moveHistory[$gameId][] = ['fen' => $newFen];
                $chessService->createBoard($newFen);

                $this->gameModel->updateState((int) $gameId, $newFen);
                $this->gameModel->updateMoves((int) $gameId, $fromSquare . $toSquare, $newFen);

                $update = [
                    'type'     => 'game_update',
                    'fen'      => $newFen,
                    'turn'     => $chessService->getTurn($newFen),
                    'moves'    => [],
                    'timers'   => [],
                    'notation' => $fromSquare . $toSquare,
                    'lastMove' => ['from' => $fromSquare, 'to' => $toSquare],
                ];
                if ($check) $update['check'] = true;

                foreach ($this->gameRooms[$gameId] as $conn) {
                    $conn->send(json_encode($update));
                }

                $nextTurn = $chessService->getTurn($newFen);
                $board    = $chessService->board;

                if (!$chessService->hasLegalMoves($board, $nextTurn)) {
                    $winnerColor = ($nextTurn === 'w') ? 'nero' : 'bianco';
                    $reason      = $chessService->isKingInCheck($board, $nextTurn) ? 'checkmate' : 'stalemate';
                    $this->finalizeGameOver($gameId, $reason, $winnerColor);
                } elseif ($chessService->isFiftyMoveRule($newFen)
                    || $chessService->repeatedMoves($this->moveHistory[$gameId] ?? [], $newFen)
                    || !$chessService->hasEnoughPieces($newFen)) {
                    $this->finalizeGameOver($gameId, 'draw', null);
                }
                break;

            case 'game_over':
                $gameId = $meta['game_id'] ?? null;
                if (!$gameId) break;

                $reason = $data['reason'] ?? null;
                if (!in_array($reason, ['resign_bianco', 'resign_nero', 'timeout'], true)) {
                    break;
                }

                $state = $this->gameModel->getState((int) $gameId);
                $stato = $state['stato_partita'] ?? '';
                if (!str_starts_with($stato, 'Resign') && $stato !== 'timeout') {
                    break;
                }

                foreach ($this->gameRooms[$gameId] as $conn) {
                    $conn->send(json_encode([
                        'type'   => 'game_over',
                        'reason' => $reason,
                        'winner' => $data['winner'] ?? null,
                    ]));
                }
                break;
        }
    }

    protected function finalizeGameOver(string $gameId, string $reason, ?string $winnerColor): void {
        if ($winnerColor) {
            $this->gameModel->updateWinner($winnerColor, (int) $gameId);
        } else {
            $this->gameModel->updateDraw((int) $gameId);
        }
        $this->gameModel->updateState((int) $gameId, $this->gameFens[$gameId], 'terminata');

        foreach ($this->gameRooms[$gameId] as $conn) {
            $conn->send(json_encode([
                'type'   => 'game_over',
                'reason' => $reason,
                'winner' => $winnerColor,
            ]));
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

// ---------------------------------------------------------------------
// Avvio del server WebSocket
// ---------------------------------------------------------------------
$pdo = new PDO(
    'mysql:host=localhost;dbname=chessfeller;charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$gameModel = new \app\models\GameModel($pdo);

$server = IoServer::factory(
    new HttpServer(new WsServer(new ChessSocket($gameModel))),
    8080
);
echo "WebSocket server avviato sulla porta 8080\n";
$server->run();