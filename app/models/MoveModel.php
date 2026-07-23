<?php
namespace app\models;
use PDO;
class MoveModel{
    public function __construct(private $pdo){}
    
    public function addMove(int $id_game, string $from, string $to, string $notation){
        $stmt = $this -> pdo -> prepare("
            INSERT INTO mosse (id_partita, da_casella, a_casella, notazione)
            VALUES (:id, :from, :to, :notation)
        ");
        return $stmt->execute([
            'id' => $id_game,
            'from' => $from,
            'to' => $to,
            'notation' => $notation
        ]);
    }
    public function getMoves(int $id_game): array {
        $stmt = $this->pdo->prepare("
            SELECT * 
            FROM mosse 
            WHERE id_partita = :id
            ORDER BY id ASC
        ");

        $stmt->execute(['id' => $id_game]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}