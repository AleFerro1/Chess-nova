<?php 
namespace app\controllers;

use app\models\DamaModel;

class DamaController{
    private DamaModel $dama_model;
    function __construct($pdo){
        $this->dama_model = new DamaModel($pdo);
    }

    function printDama(){
        ob_start();
        require_once 'app/views/dama.php';
        ob_get_clean();
    }

}