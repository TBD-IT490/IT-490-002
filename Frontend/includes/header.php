
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
            <a href="../pages/books.php" class="nav-link <?php echo $current_page==='books'?'active':''; ?>">Library</a>
            <a href="../pages/groups.php" class="nav-link <?php echo $current_page==='groups'?'active':''; ?>">Circles</a>
            <a href="../pages/schedule.php" class="nav-link <?php echo $current_page==='schedule'?'active':''; ?>">Gatherings</a>
            <a href="../shop_files/cart.php" class="nav-link <?php echo $current_page==='cart'?'active':''; ?>">Marketplace</a>
            <a href="../pagesrecommendations.php" class="nav-link <?php echo $current_page==='recommendations'?'active':''; ?>">Discoveries</a>
            <a href="../pages/discussions.php" class="nav-link <?php echo $current_page==='discussions'?'active':''; ?>">Discussions</a>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href= "../shop_files/checkout.php" class="nav-link <?php echo $current_page==='checkout'?'active':''; ?>">
                <i class="bi bi-cart3"></i>
            <a href="../pages/profile.php" class="nav-link <?php echo $current_page==='profile'?'active':''; ?> d-flex align-items-center gap-2">
                <div class="avatar-ring"><?php echo strtoupper(substr($_SESSION['username'],0,1)); ?></div>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </a>
        </div>
    </div>
</nav>

<div class="main-wrap">
<div class="container">