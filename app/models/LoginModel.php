<?php
namespace app\models;

use PDO;

class LoginModel {

    function __construct(private PDO $pdo) {}

    function loginUtente($username, $password) {

        $stmt = $this->pdo->prepare("
            SELECT * 
            FROM utenti 
            WHERE username_utente = :username
        ");

        $stmt->execute(['username' => $username]);
        $utente = $stmt->fetch(PDO::FETCH_ASSOC);

        // utente non esiste
        if (!$utente) {
            return 'credenziali errate';
        }

        // non verificato
        if ((int)$utente['is_verified'] === 0) {
            return 'not verified';
        }

        // password errata
        if (!password_verify($password, $utente['password'])) {
            return 'credenziali errate';
        }

        // login ok
        return 'success';
    }
}