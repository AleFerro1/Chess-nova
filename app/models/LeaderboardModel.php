<?php

namespace App\Models;

use PDO;

class LeaderboardModel {

    public function __construct(private PDO $pdo) {}
}