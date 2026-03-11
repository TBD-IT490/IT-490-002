<?php
//REDIRECT TO LOGIN IF NOT LOGGED IN PROPERLY (so you can't access without signing in hehe)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
   header("Location: index.php");
   exit();
}
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<!--HTML FOR HEADER-->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Noetic — <?php echo ucfirst($current_page); ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
        <link rel="stylesheet" href="../styles.css">
    </head>
<body>

<nav class="noetic-nav">
    <!--buttons :)-->
    <div class="container d-flex align-items-center gap-4">
        <a href="dashboard.php" class="nav-brand">Noetic<span>.</span></a>
        <div class="d-flex align-items-center gap-1 ms-2 flex-grow-1">
            <!--navbar links at top of every page-->
            <a href="books.php" class="nav-link <?php echo $current_page==='books'?'active':''; ?>">Library</a>
            <a href="groups.php" class="nav-link <?php echo $current_page==='groups'?'active':''; ?>">Circles</a>
            <a href="schedule.php" class="nav-link <?php echo $current_page==='schedule'?'active':''; ?>">Gatherings</a>
            <a href="recommendations.php" class="nav-link <?php echo $current_page==='recommendations'?'active':''; ?>">Discoveries</a>
            <a href="discussions.php" class="nav-link <?php echo $current_page==='discussions'?'active':''; ?>">Discussions</a>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="profile.php" class="nav-link <?php echo $current_page==='profile'?'active':''; ?> d-flex align-items-center gap-2">
                <div class="avatar-ring"><?php echo strtoupper(substr($_SESSION['username'],0,1)); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </a>
        </div>
    </div>
</nav>

<div class="main-wrap">
<div class="container">