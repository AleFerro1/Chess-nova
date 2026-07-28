<?php
session_start();
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Services\ChessServices;
use app\models\GameModel;

class ChessSocket implements MessageComponentInterface {

    protected array $clients       = [];
    protected array $gameRooms     = [];
    protected array $connMeta      = [];
    protected array $gameInstances = []; // gameId => ChessServices
    protected array $gameFens      = []; // gameId => FEN corrente (autorevole lato server)
    protected array $moveHistory   = []; // gameId => storico mosse (per repeatedMoves)

    // -----------------------------------------------------------------
    // FIX #4: dominio/i autorizzati per il controllo Origin (fix #3)
    // -----------------------------------------------------------------
    protected array $allowedOrigins = [
        'https://chessnova.win',
        'https://www.chessnova.win',
    ];

    protected GameModel $gameModel;

    public function __construct(GameModel $gameModel) {
        $this->gameModel = $gameModel;
    }

    public function onOpen(ConnectionInterface $conn) {

        // -------------------------------------------------------------
        // FIX #3: verifica dell'header Origin PRIMA di qualsiasi altra cosa.
        // Senza questo controllo, un sito esterno può aprire una connessione
        // WS verso chessnova.win dal browser della vittima: il cookie di
        // sessione parte comunque, quindi il sito esterno "eredita" la sessione
        // autenticata (Cross-Site WebSocket Hijacking).
        // -------------------------------------------------------------
        $originHeader = $conn->httpRequest->getHeader('Origin');
        $origin       = $originHeader[0] ?? null;

        if (!$origin || !in_array($origin, $this->allowedOrigins, true)) {
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

        // Piccola validazione di formato prima di passarlo a session_id()
        if (!$sessionId || !preg_match('/^[a-zA-Z0-9,\-]{22,250}$/', $sessionId)) {
            $conn->send('Sessione mancante');
            $conn->close();
            return;
        }

        // 2. Collega questa connessione alla sessione esistente
        session_id($sessionId);
        session_start();

        // 3. Verifica il token o qualsiasi dato di autenticazione
        $token = $_SESSION['token'] ?? null;
        if (!$token) {
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

        // 6. Rilascia il lock della sessione IMMEDIATAMENTE
        session_write_close();

        // -------------------------------------------------------------
        // FIX #1 (parte 1): recupera dal DB il colore assegnato a QUESTO
        // utente in QUESTA partita, e lo salva nella connMeta. Da qui in
        // avanti il server sa con certezza chi può muovere cosa, senza
        // doversi fidare del client.
        // -------------------------------------------------------------
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

        // -------------------------------------------------------------
        // FIX bug: $this->gameFens[$gameId] non veniva mai inizializzato,
        // quindi la prima mossa di ogni partita falliva sempre (il codice
        // faceva "break" su $currentFen nullo). Lo recuperiamo dal DB, che
        // è la fonte di verità dello stato partita.
        // -------------------------------------------------------------
        if (!isset($this->gameFens[$gameId])) {
            $state = $this->gameModel->getState((int) $gameId);
            $this->gameFens[$gameId] = $state['fen'] ?? null;
        }

        echo "Nuova connessione: {$conn->resourceId}\n";
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

                // -----------------------------------------------------
                // FIX #1 (parte 2): verifica che il pezzo dichiarato
                // appartenga davvero al giocatore che sta inviando la
                // mossa, usando il colore salvato in onOpen (dal DB) e
                // la logica già presente in GameModel::canPlayerMakeMove.
                // Senza questo controllo, isValidMove() da sola valida
                // solo "è il turno del colore corretto secondo il FEN",
                // ma non lega la mossa all'identità del mittente: un
                // client malevolo potrebbe inviare mosse per l'avversario.
                // -----------------------------------------------------
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

                // Aggiorna lo stato autorevole della partita
                $this->gameFens[$gameId] = $newFen;
                $this->moveHistory[$gameId][] = ['fen' => $newFen];
                $chessService->createBoard($newFen);

                // Persisti anche su DB, così è coerente con /board, /resign, ecc.
                $this->gameModel->updateState((int) $gameId, $newFen);
                $this->gameModel->updateMoves((int) $gameId, $fromSquare . $toSquare, $newFen);

                $update = [
                    'type'     => 'game_update',
                    'fen'      => $newFen,
                    'turn'     => $chessService->getTurn($newFen),
                    'moves'    => [],
                    // FIX #2b: i timer non vengono più ripresi ciecamente dal
                    // client (vedi anche i controlli fatti a monte in /board).
                    // Qui ci limitiamo a NON ripubblicare $data['timers'] come
                    // valore autorevole; se vuoi sincronizzarli, recuperali da
                    // GameModel::getTime() invece che dal payload del client.
                    'timers'   => [],
                    'notation' => $fromSquare . $toSquare,
                    'lastMove' => ['from' => $fromSquare, 'to' => $toSquare],
                ];
                if ($check) $update['check'] = true;

                foreach ($this->gameRooms[$gameId] as $conn) {
                    $conn->send(json_encode($update));
                }

                // Controlli fine partita — determinati SOLO dal server
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
                // -----------------------------------------------------
                // FIX #2: NON ci fidiamo più di $data['reason'] / $data['winner']
                // inviati dal client. Gestiamo solo i due casi che possono
                // legittimamente arrivare come notifica "a posteriori" da un
                // client (resign, timeout), e li verifichiamo contro lo stato
                // reale salvato su DB da /resign e /timeoutGame — che sono
                // endpoint HTTP autenticati via sessione, quindi affidabili.
                // Qualsiasi altro reason (checkmate, stalemate, draw, ecc.)
                // viene ignorato: quelli li determina SOLO il server nel
                // ramo 'move' qui sopra.
                // -----------------------------------------------------
                $gameId = $meta['game_id'] ?? null;
                if (!$gameId) break;

                $reason = $data['reason'] ?? null;
                if (!in_array($reason, ['resign_bianco', 'resign_nero', 'timeout'], true)) {
                    break; // reason non consentito da questo canale
                }

                $state = $this->gameModel->getState((int) $gameId);
                $stato = $state['stato_partita'] ?? '';

                // Il DB deve già riflettere un abbandono/timeout prima che
                // accettiamo di rilanciare l'evento agli altri client.
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

    /**
     * Determina reason/winner solo lato server, aggiorna il DB
     * (stato partita, vincitore/pareggio, elo) e notifica la stanza.
     */
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
// Bootstrap: istanzia PDO e GameModel e passali al costruttore.
// Adatta questa parte alla tua configurazione DB esistente
// (probabilmente hai già un file di bootstrap/config simile altrove).
// ---------------------------------------------------------------------
$pdo = new PDO(
    'mysql:host=localhost;dbname=chessnova;charset=utf8mb4',
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