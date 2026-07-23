<?php
namespace app\models;
use PDO;

class EditProfileModel {

    function __construct(private PDO $pdo) {}

    /* ── lettura profilo ───────────────────────────────────────── */

    function getProfile(string $username): array|false
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM utenti WHERE username_utente = :username"
        );
        $stmt->execute(['username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* ── aggiornamento completo (profilo + paese + avatar) ─────── */

    /**
     * @param ?string $avatarPath   path relativo nuovo avatar | null = invariato | 'REMOVE' = cancella
     */
    public function updateProfile(
        string  $oldUsername,
        string  $newUsername,
        string  $email,
        string  $bio,
        string  $oldPassword,
        string  $newPassword,
        string  $country,
        ?string $avatarPath
    ): string {

        /* validazioni testo */
        if ($this->offensiveText($newUsername)) return 'username';
        if (!$this->checkEmail($email))         return 'email';
        if ($this->offensiveText($bio))         return 'bio';

        /* gestione password */
        $hashedPassword = $this->resolvePassword($oldPassword, $oldUsername, $newPassword);
        if ($hashedPassword === 'errorePassword')  return 'errorePassword';
        if ($hashedPassword === 'erroreLunghezza') return 'erroreLunghezza';
        if ($hashedPassword === 'erroreNumeri')    return 'erroreNumeri';

        /* costruisce la query dinamicamente */
        $sets = [
            'username_utente = :newUsername',
            'email           = :email',
            'biografia       = :bio',
            'password        = :password',
            'country         = :country',
        ];
        $params = [
            'newUsername' => $newUsername,
            'email'       => $email,
            'bio'         => $bio,
            'password'    => $hashedPassword,
            'country'     => $country,
            'oldUsername' => $oldUsername,
        ];

        if ($avatarPath === 'REMOVE') {
            /* cancella il file fisico dal disco */
            $this->deleteAvatarFile($oldUsername);
            $sets[]           = 'avatar = :avatar';
            $params['avatar'] = null;
        } elseif ($avatarPath !== null) {
            $sets[]           = 'avatar = :avatar';
            $params['avatar'] = $avatarPath;
        }
        /* se $avatarPath === null: nessuna modifica alla colonna avatar */

        $sql = "UPDATE utenti SET " . implode(', ', $sets)
             . " WHERE username_utente = :oldUsername";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return 'ok';
    }

    /* ── rimozione avatar (file fisico) ────────────────────────── */

    /**
     * Legge il path corrente dal DB e cancella il file dal disco.
     * Non tocca il DB — ci pensa updateProfile con avatar = NULL.
     */
    public function deleteAvatarFile(string $username): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT avatar FROM utenti WHERE username_utente = :username"
        );
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!empty($row['avatar'])) {
            $fullPath = __DIR__ . '/../../public/' . $row['avatar'];
            if (is_file($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    /* ── upload avatar ─────────────────────────────────────────── */

    /**
     * Sposta il file caricato in /uploads/avatars/ e restituisce il path
     * relativo da salvare nel DB, oppure null se non c'è nessun file.
     *
     * @param  array|null $file  $_FILES['avatar'] oppure null
     * @return string|null|'erroreAvatar'
     */
    public function handleAvatarUpload(?array $file, string $username): string|null
    {
        if ($file === null || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;   // nessun upload, nessun cambiamento
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'erroreAvatar';
        }

        /* controlla che sia davvero un'immagine */
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime    = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed)) {
            return 'erroreAvatar';
        }

        /* max 2 MB */
        if ($file['size'] > 2 * 1024 * 1024) {
            return 'erroreAvatar';
        }

        $ext     = match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        };

        $uploadDir = __DIR__ . '/../../public/uploads/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'avatar_' . preg_replace('/[^a-z0-9]/i', '_', $username)
                  . '_' . time() . '.' . $ext;

        $dest = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return 'erroreAvatar';
        }

        return 'uploads/avatars/' . $filename;   // path relativo per il DB
    }

    /* ── validazioni ────────────────────────────────────────────── */

    public function checkEmail(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public function offensiveText(string $text): bool
    {
        $bannedWords = [
            'idiot','stupid','dumb','moron','loser',
            'bitch','bastard','asshole','fuck','shit',
            'nazi','hitler',
            'kill','suicide','die',
            'cheat','hack','exploit',
        ];
        return $this->containsToxic($text, $bannedWords);
    }

    private function normalizeText(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[\s.\-_]+/', '', $text);
        $text = preg_replace('/(.)\1+/', '$1', $text);
        return $text;
    }

    private function containsToxic(string $text, array $bannedWords): bool
    {
        $text = $this->normalizeText($text);
        foreach ($bannedWords as $word) {
            if (preg_match('/' . preg_quote($word, '/') . '/i', $text)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Gestisce la password in modo flessibile:
     *  - Se entrambi i campi sono vuoti → mantiene la password attuale
     *  - Se oldPassword è fornita → verifica e aggiorna
     *
     * @return string  hash aggiornato | hash attuale | codice errore
     */
    private function resolvePassword(
        string $oldPassword,
        string $oldUsername,
        string $newPassword
    ): string {

        $stmt = $this->pdo->prepare(
            "SELECT password FROM utenti WHERE username_utente = :username"
        );
        $stmt->execute(['username' => $oldUsername]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        /* nessun cambio password richiesto */
        if ($oldPassword === '' && $newPassword === '') {
            return $row['password'];   // mantieni hash esistente
        }

        /* verifica password attuale */
        if (!password_verify($oldPassword, $row['password'])) {
            return 'errorePassword';
        }

        /* validazioni nuova password */
        if (strlen($newPassword) < 8)          return 'erroreLunghezza';
        if (!preg_match('/\d/', $newPassword)) return 'erroreNumeri';

        return password_hash($newPassword, PASSWORD_DEFAULT);
    }
}