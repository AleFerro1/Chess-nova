<?php
namespace app\models;

use Exception;
use PDO;

class GameModel {

    public function __construct(private PDO $pdo) {}

    public function getGame(int $id_game): array|false { // Questa funziona returna tutto lo storico di una partita

        $gameStmt = $this->pdo->prepare("
            SELECT * FROM partite WHERE id_partita = :id
        ");
        $gameStmt->execute(['id' => $id_game]);
        $game = $gameStmt->fetch(PDO::FETCH_ASSOC);

        if (!$game) return false;

        $playersStmt = $this->pdo->prepare("
            SELECT username_utente, colore_utente 
            FROM collegamento_partite 
            WHERE id_partita = :id
        ");
        $playersStmt->execute(['id' => $id_game]);
        $players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);

        $movesStmt = $this->pdo->prepare("
            SELECT * FROM mosse 
            WHERE id_partita = :id 
            ORDER BY id_mossa ASC
        ");
        $movesStmt->execute(['id' => $id_game]);
        $moves = $movesStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'game' => $game,
            'players' => $players,
            'moves' => $moves
        ];
    }

    public function getState(int $id_game) { // Questa funziona riporta lo stato di una partita
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM partite 
            WHERE id_partita = :id
        ");

        $stmt->execute(['id' => $id_game]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            throw new Exception("Partita non trovata");
        }

        return $result;
    }

    public function updateState(int $id_game, string $fen, $stato_partita = 'in corso'): bool { // Questa funzione fa un update del fen
        $stmt = $this->pdo->prepare("
            UPDATE partite 
            SET fen = :fen, stato_partita = :stato
            WHERE id_partita = :id
        ");

        return $stmt->execute([
            'id' => $id_game,
            'fen' => $fen,
            'stato' => $stato_partita
        ]);
    }
    
    public function updateTime(int $id_game, int $time, $tempoColore){

        $stmt = $this->pdo->prepare("SELECT timecontrol FROM partite WHERE id_partita = :id");
        $stmt->execute(['id' => $id_game]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $map = [
            "bullet-1" => ["increment" => 0],
            "bullet-1-1" => ["increment" => 1],
            "bullet-2-1" => ["increment" => 1],
            "blitz-3" => ["increment" => 0],
            "bullet-3-2" => ["increment" => 2],
            "bullet-5" => ["increment" => 0],
            "bullet-5-3" => ["increment" => 3],
            "rapid-10" => ["increment" => 0],
            "rapid-10-5" => ["increment" => 5],
            "rapid-15-10" => ["increment" => 10],
            "classical-30" => ["increment" => 0],
            "classical-30-20" => ["increment" => 20],
        ];

        $increment = $map[$result['timecontrol']]['increment'] ?? 0;
        $time += $increment;
        $timestamp = time();

        if($tempoColore == 'w'){
            $stmt = $this->pdo->prepare("
            UPDATE partite 
            SET tempo_bianco = :time, tempo_ultima_mossa = :timestamp
            WHERE id_partita = :id
        ");
        }
        else{
            $stmt = $this->pdo->prepare("
            UPDATE partite 
            SET tempo_nero = :time, tempo_ultima_mossa = :timestamp
            WHERE id_partita = :id
        ");
        }
        

        return $stmt->execute([
            'id' => $id_game,
            'time' => $time,
            'timestamp' => $timestamp
        ]);
    }

    public function getTime(int $id_game, $tempoColore){
        if($tempoColore == 'w'){
            $stmt = $this->pdo->prepare("
            SELECT tempo_bianco
            FROM partite
            WHERE id_partita = :id
        ");
        }
        else{
            $stmt = $this->pdo->prepare("
            SELECT tempo_nero
            FROM partite
            WHERE id_partita = :id
        ");
        }
        $stmt->execute([
            'id' => $id_game
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    public function initializeGame(int $id_partita, string $username, string $color){ // Questa funzione collega gli utenti alla partita creata con createGame()

        // Controllo che utente esiste
        $stmt = $this->pdo->prepare("SELECT username_utente FROM utenti WHERE username_utente = :username");
        $stmt->execute([":username" => $username]);
        $utente = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$utente){
            throw new Exception("Utente non trovato");
        }
        else{
            $stmt = $this->pdo->prepare("INSERT INTO collegamento_partite(id_partita, username_utente, colore_utente) VALUES (:id, :username, :colore)");
            $stmt->execute(['id' => $id_partita, "username" => $username, "colore" => $color]);
        }
    }
    public function createGame($time, $timecontrol, $tipo){ // Questa funzione crea la partita
        $map = [
            'bullet-1'      => ['seconds' => 60,  'increment' => 0],
            'bullet-1-1'    => ['seconds' => 60,  'increment' => 1],
            'bullet-2-1'    => ['seconds' => 120,  'increment' => 1],
            'blitz-3'       => ['seconds' => 180,  'increment' => 0],
            'blitz-3-2'     => ['seconds' => 180,  'increment' => 2],
            'blitz-5'       => ['seconds' => 300,  'increment' => 0],
            'blitz-5-3'     => ['seconds' => 300,  'increment' => 3],
            'rapid-10'      => ['seconds' => 600, 'increment' => 0],
            'rapid-10-5'    => ['seconds' => 600, 'increment' => 5],
            'rapid-15-10'   => ['seconds' => 900, 'increment' => 10],
            'classical-30'  => ['seconds' => 1800, 'increment' => 0],
            'classical-30-20'=> ['seconds' => 1800, 'increment' => 20],
        ];

        $tc = $map[$timecontrol] ?? $map['rapid-10'];
        $fen = "";
        if($tipo == "scacchi") $fen = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
        else $fen = "1b1b1b1b/b1b1b1b1/1b1b1b1b/8/8/w1w1w1w1/1w1w1w1w/w1w1w1w1 w";
        $stmt = $this->pdo->prepare("INSERT INTO partite(stato_partita, fen, tempo_bianco, tempo_nero, tempo_ultima_mossa, timecontrol, tipo_partita) 
                                     VALUES ('in corso', :fen, :time, :time,:tempo, :timecontrol, :tipo)");
        $stmt->execute(['tempo' => $time, 'timecontrol' => $timecontrol, 'time' => $tc['seconds'], 'fen' => $fen, 'tipo' => $tipo] );

        return $this->pdo->lastInsertId();
    }

    public function updateMoves(int $id_partita, ?string $notazione, string $fen){ // Questa funzione aggiorna le mosse in una partita
        $stmt = $this->pdo->prepare("INSERT INTO mosse(id_partita, notazione, fen) VALUES(:id_partita, :notazione, :fen)");
        $stmt->execute(["id_partita" => $id_partita, "notazione" => $notazione, "fen" => $fen]);


    }
    public function canPlayerMakeMove(int $idPartita, string $username, string $piece, $tipo = 'scacchi'): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT colore_utente
            FROM collegamento_partite
            WHERE id_partita = :id_partita
            AND username_utente = :username
            LIMIT 1
        ");
        $stmt->execute([
            'id_partita' => $idPartita,
            'username' => $username
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return false;

        if($tipo === "scacchi"){
            $isWhitePiece = ctype_upper($piece);

            return (
            ($row['colore_utente'] === 'bianco' && $isWhitePiece) ||
            ($row['colore_utente'] === 'nero' && !$isWhitePiece)
            );
        }
        else{
            
            return(($row['colore_utente'] === 'bianco' && strtoupper($piece) == 'W') ||
                   ($row['colore_utente'] === 'nero' && strtoupper($piece) == 'B')
            );
        }
    }

    public function getIdPartita($username, $opponent){
        $stmt = $this->pdo->prepare("SELECT id_partita
                                    FROM collegamento_partite
                                    WHERE username_utente IN (:username, :usernameAvversario)
                                    GROUP BY id_partita
                                    HAVING COUNT(DISTINCT username_utente) = 2
                                    ORDER BY id_partita DESC 
                                    LIMIT 1");
        $stmt->execute(['username' => $username, 'usernameAvversario' => $opponent]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$result) return false;

        return $result['id_partita'];
    }

    public function getPlayerColor($username){
        $stmt = $this->pdo->prepare("SELECT colore_utente FROM collegamento_partite WHERE username_utente = :username ORDER BY id_partita DESC LIMIT 1");
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!$result) return false;

        return $result['colore_utente'];
    }

    public function reisgnFunction($id, $username){
        $colore = $this->getPlayerColorForGame((int)$id, $username);
        if ($colore === false) {
            throw new Exception("Non partecipi a questa partita");
        }
        $stmt = $this->pdo->prepare("UPDATE partite SET stato_partita = :stato WHERE id_partita = :id");
        return $stmt->execute(['stato' => "Resign $colore", 'id' => $id]);
    }

    public function getSimpleId($username) {

        $sql = "
            SELECT id_partita
            FROM collegamento_partite
            INNER JOIN partite USING(id_partita)
            WHERE username_utente = ?
            AND stato_partita = 'in corso'
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username]);

        return $stmt->fetchColumn();
    }

    public function updateWinner($winner, $id){
        $stmt = $this->pdo->prepare("UPDATE collegamento_partite SET outcome = 'Winner' WHERE colore_utente = :winner AND id_partita = :id");
        $stmt->execute(['winner' => $winner, 'id' => $id]);

        $stmt = $this->pdo->prepare("UPDATE collegamento_partite SET outcome = 'Loser' WHERE colore_utente != :winner AND id_partita = :id");
        $stmt->execute(['winner' => $winner, 'id' => $id]);

        // Recupera il timecontrol della partita
        $stmt = $this->pdo->prepare("SELECT timecontrol, tipo_partita FROM partite WHERE id_partita = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $timecontrol = $result['timecontrol'];

        if($result['tipo_partita'] == 'scacchi'){
            $eloCol = match(true) {
            str_starts_with($timecontrol, 'bullet')    => 'eloBullet',
            str_starts_with($timecontrol, 'blitz')     => 'eloBlitz',
            str_starts_with($timecontrol, 'rapid')     => 'elo',
            str_starts_with($timecontrol, 'classical') => 'eloClassical',
            default                                    => 'elo'
            };
        }
        else{
            $eloCol = match(true) {
            str_starts_with($timecontrol, 'bullet')    => 'checkersBullet',
            str_starts_with($timecontrol, 'blitz')     => 'checkersBlitz',
            str_starts_with($timecontrol, 'rapid')     => 'checkersRapid',
            str_starts_with($timecontrol, 'classical') => 'checkersClassical',
            default                                    => 'checkersRapid'
            };
        }
        

        $elo = rand(6, 10);

        $stmt = $this->pdo->prepare("
            UPDATE utenti JOIN collegamento_partite 
                ON utenti.username_utente = collegamento_partite.username_utente
            SET utenti.$eloCol = utenti.$eloCol + :elo
            WHERE collegamento_partite.id_partita = :id 
            AND collegamento_partite.colore_utente = :winner
        ");
        $stmt->execute(['elo' => $elo, 'id' => $id, 'winner' => $winner]);

        $stmt = $this->pdo->prepare("
            UPDATE utenti JOIN collegamento_partite 
                ON utenti.username_utente = collegamento_partite.username_utente
            SET utenti.$eloCol = utenti.$eloCol - :elo
            WHERE collegamento_partite.id_partita = :id 
            AND collegamento_partite.colore_utente != :winner
        ");
        $stmt->execute(['elo' => $elo, 'id' => $id, 'winner' => $winner]);
    }

    public function updateDraw($id){
        $stmt = $this->pdo->prepare("UPDATE collegamento_partite SET outcome = 'Draw' WHERE id_partita = :id");
        $stmt->execute([ 'id' => $id]);
    }
    public function getActiveMatchId(string $username): ?int
    {
        $stmt = $this->pdo->prepare("
            SELECT cp.id_partita
            FROM collegamento_partite cp
            JOIN partite p ON p.id_partita = cp.id_partita
            WHERE cp.username_utente = :username
            AND p.stato_partita = 'in corso'
            LIMIT 1
        ");

        $stmt->execute(['username' => $username]);

        $id = $stmt->fetchColumn();

        return $id ? (int)$id : null;
    }

    public function getPlayerColorForGame(int $id_partita, string $username): string|false
    {
        $stmt = $this->pdo->prepare("
            SELECT colore_utente
            FROM collegamento_partite
            WHERE id_partita = :id_partita
            AND username_utente = :username
            LIMIT 1
        ");
        $stmt->execute([
            'id_partita' => $id_partita,
            'username'   => $username,
        ]);
    
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['colore_utente'] ?? false;
    }

    public function getWinnerColor(int $id_partita): ?string
    {
        $stmt = $this->pdo->prepare("
            SELECT colore_utente FROM collegamento_partite
            WHERE id_partita = :id AND outcome = 'Winner'
            LIMIT 1
        ");
        $stmt->execute(['id' => $id_partita]);
        $c = $stmt->fetchColumn();
        return $c ?: null;
    }
}