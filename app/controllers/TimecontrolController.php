<?php
namespace app\controllers;

use app\models\ProfileModel;

class TimecontrolController{

    private ProfileModel $profile_model;

    function __construct($pdo){
        $this->profile_model = new ProfileModel($pdo);
    }
    function printTimecontrol($username, $tipo){
        $informazioni = $this->profile_model->getInformazione($username);
        ob_start();
        require_once __DIR__ . '/../views/timecontrol.php';
        return ob_get_clean();
    }
}