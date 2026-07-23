<?php
namespace app\controllers;
require_once __DIR__ . '/../models/GameModel.php';
require_once __DIR__ . '/../models/ProfileModel.php';

use app\models\GameModel;
use app\models\ProfileModel;

class GameController {
    private GameModel $game_model;
    private ProfileModel $profile_model;
    public function __construct($pdo) {
        $this->game_model = new GameModel($pdo);
        $this->profile_model = new ProfileModel($pdo);
    }

    public function createMatch($time, $timecontrol, $tipo){
        return $this->game_model->createGame($time, $timecontrol, $tipo);
        
    }

    public function joinMatch(){
        $id_partita = $_POST['id_partita'];
        $username = $_POST['username'];
        $color = $_POST['color'];

        $this->game_model->initializeGame($id_partita, $username, $color);
    }
    
    public function stateMatch($id){
        $state = $this->game_model->getState($id);
        return $state;
    }

    public function updateMatch($id, $fen, $move, $state){
        $this->game_model->updateState($id, $fen, $state);
        $this->game_model->updateMoves($id, $move, $fen);
    }
    
    public function updateTime($id, $tempo, $tempoColore){
        $this->game_model->updateTime($id, $tempo, $tempoColore);
    }
    public function getTime($id, $tempoColore){
        $time = $this->game_model->getTime($id, $tempoColore);
        return $time;
    }

    public function getFullMatch($id){
        $game = $this->game_model->getGame($id);
        return $game;
    }

    public function canPlayerMakeMove(int $idPartita, string $username, string $piece, $tipo = 'scacchi'){
        return $this->game_model->canPlayerMakeMove($idPartita, $username, $piece, $tipo);
    }

    public function getIdPartita($username, $opponent){
        return $this->game_model->getIdPartita($username, $opponent);
    }
    
    public function getPlayerColor($username){
        return $this->game_model->getPlayerColor($username);
    }

    public function reisgnFunction($id, $username){
        return $this->game_model->reisgnFunction($id, $username);
    }
    public function getSimpleId($username){
        return $this->game_model->getSimpleId($username);
    }

    public function updateWinner($winner, $id){
        return $this->game_model->updateWinner($winner, $id);
    }
    public function updateDraw( $id){
        return $this->game_model->updateDraw($id);
    }
    public function getActiveMatchId(string $username){
        return $this->game_model->getActiveMatchId($username);
    }

    public function printBoard($board,
        $coloreGiocatore,
        $tempo_bianco,
        $tempo_nero,
        $tempo_ultima_mossa,
        $username,
        $id_partita, $tipo,
        $fen = ''){
        $informazioni = $this->getFullMatch($id_partita);

        $nomeBianco = "";
        $nomeNero = "";

        $players = $informazioni["players"] ?? [];

        foreach ($players as $player) {

            if ($player["colore_utente"] === "bianco") {
                $nomeBianco = $player["username_utente"];
            }

            if ($player["colore_utente"] === "nero") {
                $nomeNero = $player["username_utente"];
            }
        }
        $tmp = $informazioni['game']['timecontrol'];
        if($tipo == 'scacchi'){
            $timecontrol = match(true) {
            str_starts_with($tmp, 'bullet')    => 'eloBullet',
            str_starts_with($tmp, 'blitz')     => 'eloBlitz',
            str_starts_with($tmp, 'rapid')     => 'elo',
            str_starts_with($tmp, 'classical') => 'eloClassical',
            default                                    => 'elo'
            };
        }
        else{
            $timecontrol = match(true) {
            str_starts_with($tmp, 'bullet')    => 'checkersBullet',
            str_starts_with($tmp, 'blitz')     => 'checkersBlitz',
            str_starts_with($tmp, 'rapid')     => 'checkersRapid',
            str_starts_with($tmp, 'classical') => 'checkersClassical',
            default                                    => 'checkersRapid'
            };
        }
        $elo_bianco = $this->profile_model->getInformazione($nomeBianco);
        $elo_nero = $this->profile_model->getInformazione($nomeNero);
        $turn = 'w';
        if($fen){
            $parts = explode(' ', $fen);
            $turn  = isset($parts[1]) ? $parts[1] : 'w';
        }
        $path = null;
        if($tipo == "scacchi") $path = "app/views/board.php";
        if($tipo == "dama") $path = "app/views/dama.php";
        ob_start();
        require $path;
        return ob_get_clean();
        
    }
}