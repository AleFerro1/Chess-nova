<?php
namespace app\models;

use PDO;

class ProfileModel{
    function __construct(private PDO $pdo){

    }
    function getPartiteGiocate($username){
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as 'conta' FROM collegamento_partite WHERE username_utente = :username GROUP BY username_utente ");
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if($result) return $result['conta'];
        return 0; 
    }
    function getVittorieGiocatore($username){
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as 'conta' FROM collegamento_partite WHERE username_utente = :username AND outcome = 'Winner' GROUP BY username_utente ");
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if($result) return $result['conta'];
        return 0; 
    }
    function getSconfitteGiocatore($username){
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as 'conta' FROM collegamento_partite WHERE username_utente = :username AND outcome = 'Loser' GROUP BY username_utente ");
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if($result) return $result['conta'];
        return 0; 
        
    }

    function lastGames($username, $tipo = 'scacchi')
    {
        $modes = ['bullet', 'blitz', 'rapid', 'classical'];
        $result = [];

        foreach ($modes as $mode) {
            $stmt = $this->pdo->prepare("
                SELECT cp.outcome, cp.colore_utente, p.timecontrol,
                    u2.username_utente AS opponent
                FROM collegamento_partite cp
                JOIN partite p ON p.id_partita = cp.id_partita
                JOIN collegamento_partite cp2
                    ON cp2.id_partita = cp.id_partita
                AND cp2.username_utente != cp.username_utente
                JOIN utenti u2 ON u2.username_utente = cp2.username_utente
                WHERE cp.username_utente = :username
                AND p.timecontrol LIKE :pattern
                AND p.tipo_partita = :tipo
                AND cp.outcome IS NOT NULL
                ORDER BY cp.id_partita DESC
                LIMIT 20
            ");
            $stmt->execute([
                'username' => $username,
                'pattern'  => "$mode%",
                'tipo'     => $tipo,
            ]);
            $result[$mode] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $result;
    }
    function getInformazione($username){
        $stmt = $this->pdo->prepare("SELECT * FROM utenti where username_utente = :username");
        $stmt->execute(['username' => $username]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }
}