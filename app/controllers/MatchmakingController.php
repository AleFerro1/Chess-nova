<?php 
namespace app\controllers;
require_once __DIR__ . '/../models/MatchmakingModel.php';
use app\models\MatchmakingModel;

class MatchmakingController{
    private MatchmakingModel $matchmaking_model;
    function __construct($pdo){
        $this->matchmaking_model = new MatchmakingModel($pdo);
    }
    function getElo($username, $timecontrol){
        return $this->matchmaking_model->getElo($username, $timecontrol);
    }
    function putInMatchmaking($username, $timecontrol, $tipo){
        if($this->matchmaking_model->putInMatchmaking($username, $timecontrol, $tipo)) return true;
        return false;
    }

    function findOpponent($username, $timecontrol, $tipo){
        return $this->matchmaking_model->findOpponent($username, $timecontrol, $tipo);
    }

    function assignPlayersToMatch($id_partita, $username, $enemy, $colore1, $colore2){
        return $this->matchmaking_model->assignPlayersToMatch($id_partita, $username, $enemy, $colore1, $colore2);
    }

    function removeFromMatchmaking($username){
        return $this->matchmaking_model->removeFromMatchmaking($username);
    }

    function checkUser($username){
        return $this->matchmaking_model->checkUser($username);
    }
    function cleanStale(){
        $this->matchmaking_model->cleanStale();
    }
    function isWaiting($username){
        return $this->matchmaking_model->isWaiting($username);
    }
    function removeIfWaiting($username){
        return $this->matchmaking_model->removeIfWaiting($username);
    }
}