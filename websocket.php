<?php
require 'vendor/autoload.php';
require 'app/models/GameModel.php';
// FIX #1: senza questo, getenv('DB_USER')/getenv('DB_PASSWORD') più sotto
// restituiscono false e il new PDO() crasha il processo all'avvio.

// FIX #2: rimosso il session_start() che stava qui in cima al file.
// Avviava una sessione "vuota" del processo CLI ancora prima che
// qualunque client si connettesse, il che impediva a session_id($sessionId)
// dentro onOpen() di funzionare (non si può cambiare l'id di una sessione
// già attiva). Risultato: $_SESSION['token'] non veniva mai popolato e
// ogni connessione finiva rigettata come "Non autenticato".
// La gestione della sessione ora avviene SOLO per-connessione dentro onOpen().

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
    protected array $gameInstances = []; // gameId => ChessServices
    protected array $gameFens      = []; // gameId => FEN corrente (autorevole lato server)
    protected array $moveHistory   = []; // gameId => storico mosse (per repeatedMoves)

    protected array $allowedOrigins = [
        'https://chessnova.win',
        'https://www.chessnova.win',
    ];

    protected GameModel $gameModel;

    public function __construct(GameModel $gameModel) {
        $this->gameModel = $gameModel;
    }

    public function onOpen(ConnectionInterface $conn) {

        $originHeader = $conn->httpRequest->getHeader('Origin');
        $origin       = $originHeader[0] ?? null;

        if (!$origin || !in_array($origin, $this->allowedOrigins, true)) {
            echo "Connessione rifiutata: Origin non consentito ({$origin})\n";
            $conn->close();
            return;
        }

        // 1. Recupera l'ID sessione dal cookie inviato durante l'upgrade
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

        // 2. Collega questa connessione alla sessione esistente.
        // Qui non c'è più nessuna sessione già attiva dal top-level, quindi
        // session_id() può impostare correttamente l'id ricevuto dal cookie.
        session_id($sessionId);

        // Forza la disabilitazione della strict mode (spesso colpevole)
ini_set('session.use_strict_mode', 0);

$started = session_start();

$logData = date('Y-m-d H:i:s') . " ID: " . var_export($sessionId, true) . PHP_EOL;
$logData .= "session_status: " . session_status() . PHP_EOL;
$logData .= "session_start returned: " . var_export($started, true) . PHP_EOL;
$logData .= "session_save_path: " . session_save_path() . PHP_EOL;
$logData .= "SESSION: " . var_export($_SESSION, true) . PHP_EOL . PHP_EOL;
file_put_contents('/tmp/ws_session_debug.log', $logData, FILE_APPEND);

        $logData = date('Y-m-d H:i:s') . " ID: " . var_export($sessionId, true) . PHP_EOL;
        $logData .= "session_status: " . session_status() . PHP_EOL;
        $logData .= "session_save_path: " . session_save_path() . PHP_EOL;
        $logData .= "SESSION: " . var_export($_SESSION, true) . PHP_EOL . PHP_EOL;
        file_put_contents('/tmp/ws_session_debug.log', $logData, FILE_APPEND);
        // 3. Verifica il token o qualsiasi dato di autenticazione
        $token = $_SESSION['token'] ?? null;
        if (!$token) {
            echo "Connessione rifiutata: nessun token in sessione (sid={$sessionId})\n";
            session_write_close();
            $conn->send('Non autenticato');
            $conn->close();
            return;
        }

        // 4. Salva dati utente nella connessione per uso futuro
        $gameId   = $_SESSION['id_partita'] ?? null;
        $username = $_SESSION['username'] ?? null;
        $conn->token = $token;

        // 5. Se i dati della partita o dell'utente non sono presenti, chiudi la connessione
        if (!$gameId || !$username) {
            session_write_close();
            $conn->send('Dati partita mancanti');
            $conn->close();
            return;
        }

        // 6. Rilascia il lock della sessione IMMEDIATAMENTE, così le altre
        // richieste HTTP dello stesso utente non restano bloccate in attesa.
        session_write_close();

        $colore = $this->gameModel->getPlayerColor($username); // 'bianco' | 'nero'
        if (!$colore) {
            $conn->send('Impossibile determinare il colore del giocatore');
            $conn->close();
            return;
        }

        // 7. Salva le informazioni della connessione
        $this->connMeta[$conn->resourceId] = [
            'authenticated' => true,
            'game_id'  => $gameId,
            'username' => $username,
            'color'    => $colore === 'bianco' ? 'w' : 'b',
        ];

        // 8. Registra la connessione
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
            return; // connessione non autenticata: ignora tutto
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
