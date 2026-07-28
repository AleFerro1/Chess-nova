<?php

require 'vendor/autoload.php';
require 'app/models/GameModel.php';
require_once __DIR__ . '/app/services/ChessServices.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Services\ChessServices;
use App\Models\GameModel;


/**
 * Legge una sessione PHP senza session_start().
 *
 * Il processo Ratchet è persistente, quindi session_start() non deve
 * essere utilizzato per ogni connessione.
 */
function readPhpSession(
    string $sessionId,
    string $savePath = '/var/lib/php/sessions'
): array {

    $file = rtrim($savePath, '/') . '/sess_' . $sessionId;

    if (!is_readable($file)) {
        return [];
    }

    $raw = file_get_contents($file);

    if ($raw === false || $raw === '') {
        return [];
    }

    $data = [];
    $offset = 0;
    $length = strlen($raw);

    while ($offset < $length) {

        if (!preg_match(
            '/^([a-zA-Z_][a-zA-Z0-9_]*)\|/',
            substr($raw, $offset),
            $m
        )) {
            break;
        }

        $key = $m[1];
        $offset += strlen($m[0]);

        $serialized = substr($raw, $offset);

        $result = unserialize(
            $serialized,
            ['allowed_classes' => false]
        );

        $data[$key] = $result;

        $offset += strlen(serialize($result));
    }

    return $data;
}


class ChessSocket implements MessageComponentInterface
{
    protected array $clients = [];
    protected array $gameRooms = [];
    protected array $connMeta = [];

    protected GameModel $gameModel;

    protected array $allowedOrigins = [
        'https://chessnova.win',
        'https://www.chessnova.win',
    ];


    public function __construct(GameModel $gameModel)
    {
        $this->gameModel = $gameModel;
    }


    /**
     * Autentica la connessione usando la sessione PHP.
     */
    public function onOpen(ConnectionInterface $conn)
    {
        /*
         * Origin check.
         */
        $originHeader = $conn->httpRequest->getHeader('Origin');
        $origin = $originHeader[0] ?? null;

        if (
            !$origin ||
            !in_array($origin, $this->allowedOrigins, true)
        ) {
            $conn->close();
            return;
        }


        /*
         * Recupera PHPSESSID.
         */
        $cookies = $conn->httpRequest->getHeader('Cookie');

        $sessionId = null;

        if (!empty($cookies)) {

            $pairs = explode(';', $cookies[0]);

            foreach ($pairs as $pair) {

                $parts = explode('=', trim($pair), 2);

                if (
                    count($parts) === 2 &&
                    $parts[0] === 'PHPSESSID'
                ) {
                    $sessionId = $parts[1];
                    break;
                }
            }
        }


        if (
            !$sessionId ||
            !preg_match(
                '/^[a-zA-Z0-9,\-]{22,250}$/',
                $sessionId
            )
        ) {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Sessione mancante'
            ]));

            $conn->close();
            return;
        }


        /*
         * Leggiamo la sessione direttamente dal file.
         */
        $sessionData = readPhpSession($sessionId);

        $username = $sessionData['username'] ?? null;

        if (
            !is_string($username) ||
            $username === ''
        ) {
            $conn->send(json_encode([
                'type' => 'error',
                'message' => 'Utente non autenticato'
            ]));

            $conn->close();
            return;
        }


        /*
         * La connessione è autenticata come questo username.
         *
         * NON prendiamo game_id dalla sessione come autorità.
         * Il game_id verrà fornito nel messaggio join e verificato
         * contro il DB.
         */
        $this->connMeta[$conn->resourceId] = [
            'authenticated' => true,
            'username' => $username,
            'game_id' => null,
        ];

        $this->clients[$conn->resourceId] = $conn;
    }


    public function onMessage(
        ConnectionInterface $from,
        $msg
    ) {

        /*
         * Recupera metadata autenticati.
         */
        $meta = $this->connMeta[$from->resourceId] ?? null;

        if (
            !$meta ||
            !($meta['authenticated'] ?? false)
        ) {
            $from->close();
            return;
        }


        /*
         * JSON rigoroso.
         */
        try {
            $data = json_decode(
                $msg,
                true,
                32,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable $e) {

            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Messaggio non valido'
            ]));

            return;
        }


        if (!is_array($data)) {
            return;
        }


        $type = $data['type'] ?? '';


        /*
         * JOIN
         *
         * Il client propone un game_id.
         * Il server verifica che l'utente autenticato
         * partecipi realmente a quella partita.
         */
        if ($type === 'join') {

            $requestedGameId = filter_var(
                $data['game_id'] ?? null,
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 1
                    ]
                ]
            );

            if ($requestedGameId === false) {

                $from->send(json_encode([
                    'type' => 'error',
                    'message' => 'Partita non valida'
                ]));

                $from->close();
                return;
            }


            $username = $meta['username'];

            /*
             * VERIFICA SERVER-SIDE.
             */
            $color = $this->gameModel->getPlayerColorForGame(
                (int)$requestedGameId,
                $username
            );

            if ($color === false) {

                $from->send(json_encode([
                    'type' => 'error',
                    'message' => 'Non partecipi a questa partita'
                ]));

                $from->close();
                return;
            }


            /*
             * Verifichiamo che la partita esista.
             */
            try {
                $game = $this->gameModel->getState(
                    (int)$requestedGameId
                );
            } catch (\Throwable $e) {

                $from->send(json_encode([
                    'type' => 'error',
                    'message' => 'Partita non trovata'
                ]));

                $from->close();
                return;
            }


            /*
             * Da questo momento il game_id viene preso
             * esclusivamente dai metadata server-side.
             */
            $meta['game_id'] = (int)$requestedGameId;
            $meta['color'] = $color;

            $this->connMeta[$from->resourceId] = $meta;


            $gameId = (int)$requestedGameId;

            if (!isset($this->gameRooms[$gameId])) {
                $this->gameRooms[$gameId] = [];
            }

            $this->gameRooms[$gameId][$from->resourceId] = $from;


            /*
             * Mandiamo subito al client lo stato AUTENTICO
             * del database.
             */
            $this->sendCurrentGameState(
                $gameId,
                $from
            );

            return;
        }


        /*
         * Da questo punto in poi è obbligatorio essere
         * dentro una partita autenticata.
         */
        $gameId = $meta['game_id'] ?? null;

        if (!is_int($gameId) || $gameId <= 0) {

            $from->send(json_encode([
                'type' => 'error',
                'message' => 'Partita non associata'
            ]));

            return;
        }


        /*
         * SYNC
         *
         * È il messaggio usato da dama.php dopo che
         * POST /dama ha aggiornato il DB.
         *
         * Il game_id contenuto nel messaggio viene ignorato.
         */
        if ($type === 'sync' || $type === 'game_state') {

            $this->broadcastCurrentGameState(
                $gameId
            );

            return;
        }


        /*
         * MOVE
         *
         * Manteniamo il supporto agli scacchi.
         *
         * ATTENZIONE:
         * il tipo di partita viene recuperato dal DB.
         * Non viene mai preso da $data['tipo'].
         */
        if ($type === 'move') {

            $this->handleChessMove(
                $from,
                $gameId,
                $meta['username'],
                $data
            );

            return;
        }


        /*
         * GAME OVER
         *
         * Non accettiamo winner/reason arbitrari dal client.
         * Prima leggiamo lo stato reale dal DB.
         */
        if ($type === 'game_over') {

            $this->broadcastCurrentGameState(
                $gameId
            );

            return;
        }
    }


    /**
     * Gestione delle mosse WebSocket degli scacchi.
     *
     * La partita e il tipo vengono presi dal server.
     */
    protected function handleChessMove(
        ConnectionInterface $from,
        int $gameId,
        string $username,
        array $data
    ): void {

        $fromSquare = $data['from'] ?? null;
        $toSquare   = $data['to'] ?? null;
        $piece      = $data['piece'] ?? null;


        /*
         * Validazione formato.
         */
        if (
            !is_string($fromSquare) ||
            !preg_match('/^[a-h][1-8]$/', $fromSquare)
        ) {
            $from->send(json_encode([
                'type' => 'illegal_move',
                'message' => 'Casella di partenza non valida'
            ]));
            return;
        }

        if (
            !is_string($toSquare) ||
            !preg_match('/^[a-h][1-8]$/', $toSquare)
        ) {
            $from->send(json_encode([
                'type' => 'illegal_move',
                'message' => 'Casella di arrivo non valida'
            ]));
            return;
        }

        if (
            !is_string($piece) ||
            strlen($piece) !== 1 ||
            !preg_match('/^[prnbqkPRNBQK]$/', $piece)
        ) {
            $from->send(json_encode([
                'type' => 'illegal_move',
                'message' => 'Pezzo non valido'
            ]));
            return;
        }


        /*
         * Recuperiamo lo stato REALE dal DB.
         */
        try {
            $game = $this->gameModel->getState($gameId);
        } catch (\Throwable $e) {
            return;
        }


        /*
         * Il client non può trasformare una partita di dama
         * in una partita di scacchi o viceversa.
         */
        $tipo = $game['tipo_partita'] ?? null;

        if ($tipo !== 'scacchi') {

            $from->send(json_encode([
                'type' => 'illegal_move',
                'message' => 'Tipo di partita non valido'
            ]));

            return;
        }


        $service = new ChessServices();

        $currentFen = $game['fen'] ?? null;

        if (!is_string($currentFen) || $currentFen === '') {
            return;
        }


        /*
         * Ownership verificata dal DB.
         */
        if (
            !$this->gameModel->canPlayerMakeMove(
                $gameId,
                $username,
                $piece,
                'scacchi'
            )
        ) {

            $from->send(json_encode([
                'type' => 'illegal_move',
                'message' => 'Non è il tuo pezzo o non è il tuo turno'
            ]));

            return;
        }


        /*
         * Validazione reale della mossa.
         */
        $result = $service->isValidMove(
            $currentFen,
            $piece,
            $fromSquare,
            $toSquare,
            'scacchi'
        );


        if ($result === false) {

            $from->send(json_encode([
                'type' => 'illegal_move',
                'message' => 'Mossa non valida'
            ]));

            return;
        }


        /*
         * Se arriva una promozione, non la gestiamo qui:
         * il flusso HTTP /promuovi resta responsabile.
         */
        if (
            is_array($result) &&
            isset($result['promozione'])
        ) {

            $from->send(json_encode([
                'type' => 'promotion_required'
            ]));

            return;
        }


        $newFen = null;
        $check = false;


        if (is_string($result)) {

            $newFen = $result;

        } elseif (is_array($result)) {

            $newFen = $result['fen'] ?? null;
            $check = $result['check'] ?? false;
        }


        if (!is_string($newFen) || $newFen === '') {
            return;
        }


        /*
         * Aggiornamento DB.
         */
        $this->gameModel->updateState(
            $gameId,
            $newFen
        );

        $this->gameModel->updateMoves(
            $gameId,
            $fromSquare . $toSquare,
            $newFen
        );


        /*
         * Stato derivato dal server.
         */
        $turn = $service->getTurn($newFen);


        $update = [
            'type' => 'game_update',
            'fen' => $newFen,
            'turn' => $turn,
            'lastMove' => [
                'from' => $this->squareToCoords($fromSquare),
                'to' => $this->squareToCoords($toSquare)
            ]
        ];


        if ($check) {
            $update['check'] = true;
        }


        $this->broadcast(
            $gameId,
            $update
        );
    }


    /**
     * Invia lo stato corrente a una singola connessione.
     */
    protected function sendCurrentGameState(
        int $gameId,
        ConnectionInterface $conn
    ): void {

        try {
            $game = $this->gameModel->getState($gameId);
        } catch (\Throwable $e) {
            return;
        }


        $fen = $game['fen'] ?? null;

        if (!is_string($fen) || $fen === '') {
            return;
        }


        $service = new ChessServices();

        $turn = $service->getTurn($fen);


        $conn->send(json_encode([
            'type' => 'game_update',
            'fen' => $fen,
            'turn' => $turn,
            'timers' => [
                'bianco' => (int)($game['tempo_bianco'] ?? 0),
                'nero' => (int)($game['tempo_nero'] ?? 0)
            ],
            'lastMove' => null
        ]));
    }


    /**
     * Recupera lo stato dal DB e lo manda a tutti
     * i giocatori della partita.
     */
    protected function broadcastCurrentGameState(
        int $gameId
    ): void {

        try {
            $game = $this->gameModel->getState($gameId);
        } catch (\Throwable $e) {
            return;
        }


        $fen = $game['fen'] ?? null;

        if (!is_string($fen) || $fen === '') {
            return;
        }


        $service = new ChessServices();

        $turn = $service->getTurn($fen);


        $payload = [
            'type' => 'game_update',
            'fen' => $fen,
            'turn' => $turn,
            'timers' => [
                'bianco' => (int)($game['tempo_bianco'] ?? 0),
                'nero' => (int)($game['tempo_nero'] ?? 0)
            ],
            'lastMove' => null
        ];


        $this->broadcast(
            $gameId,
            $payload
        );
    }


    /**
     * Broadcast esclusivamente nella stanza autenticata.
     */
    protected function broadcast(
        int $gameId,
        array $payload
    ): void {

        if (empty($this->gameRooms[$gameId])) {
            return;
        }


        $encoded = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );


        foreach (
            $this->gameRooms[$gameId]
            as $conn
        ) {

            $conn->send($encoded);
        }
    }


    /**
     * Converte una casella algebraica in coordinate
     * della matrice JavaScript.
     *
     * a8 => [0,0]
     * h1 => [7,7]
     */
    protected function squareToCoords(
        string $square
    ): array {

        $file = ord($square[0]) - ord('a');
        $rank = (int)$square[1];

        return [
            8 - $rank,
            $file
        ];
    }


    public function onClose(ConnectionInterface $conn)
    {
        $resourceId = $conn->resourceId;

        $gameId = $this->connMeta[$resourceId]['game_id'] ?? null;

        if (
            is_int($gameId) &&
            isset($this->gameRooms[$gameId][$resourceId])
        ) {
            unset(
                $this->gameRooms[$gameId][$resourceId]
            );
        }

        unset($this->connMeta[$resourceId]);
        unset($this->clients[$resourceId]);
    }


    public function onError(
        ConnectionInterface $conn,
        \Exception $e
    ) {

        error_log(
            'WebSocket error: ' . $e->getMessage()
        );

        $conn->close();
    }
}


// ═══════════════════════════════════════════
// Avvio server WebSocket
// ═══════════════════════════════════════════

$pdo = new PDO(
    'mysql:host=localhost;dbname=chessfeller;charset=utf8mb4',
    getenv('DB_USER'),
    getenv('DB_PASSWORD'),
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]
);

$gameModel = new GameModel($pdo);

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChessSocket($gameModel)
        )
    ),
    8080
);

echo "WebSocket server avviato sulla porta 8080\n";

$server->run();
