<?php
namespace App\Controllers;
require_once __DIR__ . '/../models/LeaderboardModel.php';


use App\Models\LeaderboardModel;

class LeaderboardController {

    private LeaderboardModel $leaderboard_model;

    public function __construct($pdo){
        $this->leaderboard_model = new LeaderboardModel($pdo);
    }

    public function printLeaderboard(){
        ob_start();
        require __DIR__ . '/../views/leaderboard.php';
        return ob_get_clean();
    }
}