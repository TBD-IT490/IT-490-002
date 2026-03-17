<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

//data functions and header file
require_once 'includes/data.php';
require_once 'includes/header.php';

$view_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$msg = '';
?>