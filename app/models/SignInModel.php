<?php
namespace app\models;
require_once __DIR__ . '/EditProfileModel.php';
require_once __DIR__ . '/../services/MailService.php';
use app\services\MailService;
use PDO;

class SignInModel{
    private EditProfileModel $profile; 
    function __construct(private PDO $pdo){
        $this->profile = new EditProfileModel($pdo);
    }

    function checkUtente($username){
        $stmt = $this->pdo->prepare("SELECT * FROM utenti WHERE username_utente = :username");
        $stmt->execute(['username' => $username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    function checkPassword($password){
        if(strlen($password) < 8) return false;

        if (!preg_match('/\d/', $password)) return false;

        return true;
    }
    function checkEmailExists($email){
      $stmt = $this->pdo->prepare("SELECT 1 FROM utenti WHERE email = :email");
      $stmt->execute(['email' => $email]);
      return $stmt->fetch() !== false;
  }

    function hashPassword($password){
        return password_hash($password, PASSWORD_DEFAULT);
    }

    function signInUtente($username, $password, $email){
      if ($this->profile->offensiveText($username)) return 'username';
      if (!$this->profile->checkEmail($email)) return 'email';
      if (!$this->checkPassword($password)) return 'password';
      if ($this->checkEmailExists($email)) return 'email';
      if ($this->checkUtente($username)) return 'username';
      
      $token = bin2hex(random_bytes(32));
      
      $stmt = $this->pdo->prepare("INSERT INTO utenti(username_utente, elo, eloBullet, eloBlitz, eloClassical, checkersBullet, checkersBlitz, checkersRapid, checkersClassical, password, email, verify_token) VALUES (:username, 400, 400, 400, 400, 400, 400, 400, 400, :password, :email, :token)");
      
      try {
          $stmt->execute(["username" => $username, "password" => $this->hashPassword($password), 'email' => $email, 'token' => $token]);
          
          MailService::sendVerificationEmail($email, $token);

          return 'success';
      } catch (\PDOException $e) {
          if ($e->getCode() === '23000') {
              $msg = $e->getMessage();
              if (str_contains($msg, 'email')) return 'email';
              return 'username'; // duplicate PRIMARY key = username
          }
          return 'error';
      }
  }
}