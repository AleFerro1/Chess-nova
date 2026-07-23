<?php 
namespace app\controllers;
require_once __DIR__ . '/../models/SignInModel.php';

use app\models\SignInModel;

class SignInController{
    private SignInModel $signIn_model;
    function __construct($pdo){
        $this->signIn_model = new SignInModel($pdo);
    }

    function signInUtente($username, $password, $email){
        return $this->signIn_model->signInUtente($username, $password, $email);
    }

    function printSignIn(){
        ob_start();
        require_once "app/views/signIn.php";
        return ob_get_clean();
    }
}