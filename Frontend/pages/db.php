<?php
require_once __DIR__ . '/vendor/autoload.php';
//require_once __DIR__ realpath(__DIR__ . '/vendor/autoload.php');
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$rabbit_host = $_ENV['BACKEND'];

$host = 'localhost';
$db   = 'user_registration';
$user = 'taryn';
$pass = 'taryn490';


try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
