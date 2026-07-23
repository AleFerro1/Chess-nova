<?php
namespace app\controllers;

use app\models\HomeModel;
require_once __DIR__ . '/ProfileController.php';
require_once __DIR__ . '/../models/HomeModel.php';

class HomeController {
    private ProfileController $profile_controller;
    private HomeModel $homeModel;

    function __construct($pdo) {
        $this->profile_controller = new ProfileController($pdo);
        $this->homeModel = new HomeModel($pdo);
    }

    function heartbeat($username){
        $this->homeModel->heartbeat($username);
    }

    function printHome(string $username): string {
        $online = $this->homeModel->getUtentiOnline();
        $informazioni = $this->profile_controller->getInformazione($username);
        $partite = $this->profile_controller->getPartiteGiocate($username);
        ob_start();
        require_once "app/views/home.php";
        return ob_get_clean();
    }
}