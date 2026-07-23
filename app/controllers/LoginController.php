<?php 
namespace app\controllers;
require_once __DIR__ . '/../models/LoginModel.php';
use app\models\LoginModel;

class LoginController{
    private LoginModel $login_model;
    function __construct($pdo){
        $this->login_model = new LoginModel($pdo);
    }

    function login($username, $password){
        return $this->login_model->loginUtente($username, $password);
    }

    function printLogin($verify){
        ob_start();
        require "app/views/logIn.php";
        return ob_get_clean();
    }
}