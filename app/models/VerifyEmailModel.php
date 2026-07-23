<?php
namespace app\models;
use PDO;

class VerifyEmailModel{
    function __construct(private PDO $pdo){}

    function updateValidation($token){
        $stmt = $this->pdo->prepare("UPDATE utenti SET is_verified = 1, verify_token = NULL WHERE verify_token = :token");
        return $stmt->execute(['token' => $token]);
    }
}