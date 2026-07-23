<?php
namespace app\models;
use PDO;
class HomeModel{
    function __construct(private PDO $pdo){}

    function heartbeat($username){
        $stmt = $this->pdo->prepare("
            UPDATE utenti 
            SET lastseen = NOW() 
            WHERE username_utente = :username
        ");
        $stmt->execute(['username' => $username]);
    }
    function getUtentiOnline(){
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as 'conta'
            FROM utenti 
            WHERE lastseen >= NOW() - INTERVAL 3 MINUTE
        ");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['conta'];
    }
    
}