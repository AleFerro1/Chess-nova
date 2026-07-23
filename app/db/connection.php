<?php
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASSWORD'];
$db = new PDO('mysql:host=localhost;dbname=chessfeller', $user, $pass);
