<?php
namespace app\models;

use PDO;

class LoginModel {

    private const MAX_ATTEMPTS_PER_USERNAME = 5;   // per finestra di 15 min
    private const MAX_ATTEMPTS_PER_IP       = 20;  // per finestra di 15 min
    private const WINDOW_MINUTES            = 15;

    function __construct(private PDO $pdo) {}

    function loginUtente($username, $password, string $ip) {

        $this->cleanOldAttempts();

        if ($this->isRateLimited($username, $ip)) {
            return 'rate limited';
        }

        $stmt = $this->pdo->prepare("
            SELECT * 
            FROM utenti 
            WHERE username_utente = :username
        ");

        $stmt->execute(['username' => $username]);
        $utente = $stmt->fetch(PDO::FETCH_ASSOC);

        // utente non esiste o password sbagliata
        if (!$utente || !password_verify($password, $utente['password'])) {
            $this->recordFailedAttempt($username, $ip);
            return 'credenziali errate';
        }
        if ((int)$utente['is_verified'] === 0) {
            return 'not verified';
        }

        // login ok: pulisce lo storico dei tentativi falliti per questo utente
        $this->clearAttempts($username);

        return 'success';
    }

    private function isRateLimited(string $username, string $ip): bool
    {
        $stmtUser = $this->pdo->prepare("
            SELECT COUNT(*) FROM tentativi_logins
            WHERE username_utente = :username
              AND attempted_at >= NOW() - INTERVAL " . self::WINDOW_MINUTES . " MINUTE
        ");
        $stmtUser->execute(['username' => $username]);
        if ((int) $stmtUser->fetchColumn() >= self::MAX_ATTEMPTS_PER_USERNAME) {
            return true;
        }

        $stmtIp = $this->pdo->prepare("
            SELECT COUNT(*) FROM tentativi_logins
            WHERE ip_address = :ip
              AND attempted_at >= NOW() - INTERVAL " . self::WINDOW_MINUTES . " MINUTE
        ");
        $stmtIp->execute(['ip' => $ip]);
        if ((int) $stmtIp->fetchColumn() >= self::MAX_ATTEMPTS_PER_IP) {
            return true;
        }

        return false;
    }

    private function recordFailedAttempt(string $username, string $ip): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO tentativi_logins (username_utente, ip_address, attempted_at)
            VALUES (:username, :ip, NOW())
        ");
        $stmt->execute(['username' => $username, 'ip' => $ip]);
    }

    private function clearAttempts(string $username): void
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM tentativi_logins WHERE username_utente = :username
        ");
        $stmt->execute(['username' => $username]);
    }

    /* tiene la tabella piccola: rimuove tentativi più vecchi della finestra */
    private function cleanOldAttempts(): void
    {
        $this->pdo->exec("
            DELETE FROM tentativi_logins
            WHERE attempted_at < NOW() - INTERVAL " . self::WINDOW_MINUTES . " MINUTE
        ");
    }
}