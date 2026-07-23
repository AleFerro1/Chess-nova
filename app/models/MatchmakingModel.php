<?php
namespace app\models;
use PDO;

class MatchmakingModel {
    function __construct(private PDO $pdo) {}

    /* ── controlla se l'utente è già abbinato (stato = 'trovato') ── */
    function checkUser(string $username): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT stato FROM matchmaking
            WHERE username_utente = :username
        ");
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // true solo se è già stato abbinato → lo rimandiamo alla partita
        return $row !== false && $row['stato'] === 'trovato';
    }

    /* ── controlla se l'utente è in attesa (per il polling) ── */
    function isWaiting(string $username): bool
    {
        $stmt = $this->pdo->prepare("
            SELECT 1 FROM matchmaking
            WHERE username_utente = :username
              AND stato = 'in attesa'
        ");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch() !== false;
    }

    /* ── elo utente ── */
    function getElo(string $username, $timecontrol, $tipo): int
    {   
        if($tipo === 'scacchi'){
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
        

        $stmt = $this->pdo->prepare(
            "SELECT $eloCol as 'elo' FROM utenti WHERE username_utente = :username"
        );
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) ($result['elo'] ?? 1000);
    }

    /* ── inserisce in coda; se già presente aggiorna il timestamp ── */
    function putInMatchmaking(string $username, $timecontrol, $tipo): bool
    {   
        $stmt = $this->pdo->prepare("
            INSERT INTO matchmaking (timestamp_join, elo, username_utente, stato, timecontrol, tipo)
            VALUES (NOW(6), :elo, :username, 'in attesa', :timecontrol, :tipo)
            ON DUPLICATE KEY UPDATE
                timestamp_join = NOW(6),
                stato          = 'in attesa'
        ");
        return $stmt->execute([
            'elo'         => $this->getElo($username, $timecontrol, $tipo),
            'username'    => $username,
            'timecontrol' => $timecontrol,
            'tipo' => $tipo
        ]);
    }

    /* ── trova avversario: mai se stesso, mai ghost (>2 min) ── */
    function findOpponent(string $username, $timecontrol, $tipo): array|false
{
    $myElo = $this->getElo($username, $timecontrol, $tipo);

    $stmt = $this->pdo->prepare("
        SELECT * FROM matchmaking
        WHERE stato           = 'in attesa'
          AND username_utente != :username
          AND timestamp_join  >= NOW() - INTERVAL 2 MINUTE
          AND ABS(elo - :elo) <= 100
          AND timecontrol      = :time
          AND tipo             = :tipo
        ORDER BY ABS(elo - :elo2) ASC
        LIMIT 1
    ");
    $stmt->execute([
        'username' => $username,
        'elo'      => $myElo,
        'elo2'     => $myElo,
        'time'     => $timecontrol,
        'tipo'     => $tipo
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ?: false;
}
    /* ── assegna i giocatori alla partita ── */
    function assignPlayersToMatch(
        int    $id_partita,
        string $username,
        string $enemy,
        string $colore1,
        string $colore2
    ): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO collegamento_partite
                (id_partita, username_utente, colore_utente, created_at)
            VALUES (:id, :username, :colore, NOW())
        ");
        $r1 = $stmt->execute([
            'id' => $id_partita, 'username' => $username, 'colore' => $colore1,
        ]);
        $r2 = $stmt->execute([
            'id' => $id_partita, 'username' => $enemy, 'colore' => $colore2,
        ]);
        return $r1 && $r2;
    }

    /* ── rimuove dalla coda ── */
    function removeFromMatchmaking(string $username): bool
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM matchmaking WHERE username_utente = :username"
        );
        return $stmt->execute(['username' => $username]);
    }

    /* ── rimuove SOLO se ancora in attesa (usato da leaveMatchmaking) ── */
    function removeIfWaiting(string $username): bool
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM matchmaking
            WHERE username_utente = :username
              AND stato = 'in attesa'
        ");
        return $stmt->execute(['username' => $username]);
    }

    /* ── pulisce ghost (utenti che hanno abbandonato senza rimuoversi) ── */
    function cleanStale(): void
    {
        $this->pdo->exec("
            DELETE FROM matchmaking
            WHERE stato = 'in attesa'
              AND timestamp_join < NOW() - INTERVAL 2 MINUTE
        ");
    }
}