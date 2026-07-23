<?php
namespace app\controllers;
require_once __DIR__ . '/../models/ProfileModel.php';
require_once __DIR__ . '/../models/MatchmakingModel.php';
use app\models\ProfileModel;
class ProfileController{
    private ProfileModel $profile_model;
    private MatchmakingController $matchmaking_controller;
    function __construct($pdo){
        $this->profile_model = new ProfileModel($pdo);
        $this->matchmaking_controller = new MatchmakingController($pdo);
    }

    function getInformazione($username){
        return $this->profile_model->getInformazione($username);
    }

    function getPartiteGiocate($username){
        return $this->profile_model->getPartiteGiocate($username);
    }

    function printProfile($username){
        $partite = $this->profile_model->getPartiteGiocate($username);
        $vittorie = $this->profile_model->getVittorieGiocatore($username);
        $sconfitte = $this->profile_model->getSconfitteGiocatore($username);
        $informazioni = $this->profile_model->getInformazione($username);
        if($partite != 0){
            $media = round(($vittorie/$partite) * 100, 2);
        }
        else $media = 0;

        $gamesByMode = $this->profile_model->lastGames($username);
        $gamesByModeDama = $this->profile_model->lastGames($username, 'dama');
        
        ob_start();
        require_once "app/views/profile.php";
        return ob_get_clean();

    }
}