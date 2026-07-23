<?php 
namespace app\controllers;

require_once __DIR__ . '/../models/VerifyEmailModel.php';

use app\models\VerifyEmailModel;

class VerifyEmailController{
    private VerifyEmailModel $verify_model;
    function __construct($pdo){
        $this->verify_model = new VerifyEmailModel($pdo);
    }

    function printVerifyEmail($token){
        $success = $this->verify_model->updateValidation($token);
        ob_start();
        require_once __DIR__ . '/../views/verifyEmail.php';
        return ob_get_clean();
    }
}