<?php

$host = "100.91.21.90";
$dbname = "noetic";
$username = "noetic_user";
$password = "password123";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",$username,$password
    );

    $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

} catch(PDOException $e) {die("Connection failed: " . $e->getMessage());}