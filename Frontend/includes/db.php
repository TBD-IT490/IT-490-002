<?php

$host = "100.112.153.128";
$dbname = "noetic";
$username = "noetic_user";
$password = "password123";

try {

    $pdo = new PDO(
        "mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4",$username,$password
    );

    $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

} catch(PDOException $e) {die("Connection failed: " . $e->getMessage());}