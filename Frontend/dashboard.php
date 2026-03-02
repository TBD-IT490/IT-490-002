<?php
session_start();

// If user is NOT logged in, redirect to login page
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== TRUE) {
    header("Location: index.php");
    exit();
}

// Get username from session (NOT from $_GET)
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #202030;
            color: #DCBCCE;
            min-height: 100vh;
        }

        .navbar {
            background-color: #242E0F !important;
        }

        .navbar-brand {
            color: #DCBCCE !important;
            font-weight: bold;
        }

        .btn-logout {
            background-color: #86715B;
            border: none;
            color: #202030;
        }

        .btn-logout:hover {
            background-color: #242E0F;
            color: #DCBCCE;
        }

        .widget {
            background-color: #39304A;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin: 10px 0;
            cursor: pointer;
            color: #DCBCCE;
            transition: all 0.2s;
        }

        .widget:hover {
            background-color: #86715B;
            color: #202030;
        }

        h4 {
            margin-bottom: 10px;
        }

        p {
            margin: 0;
        }
    </style>
</head>
<body>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <span class="navbar-brand">Welcome, <?php echo htmlspecialchars($username); ?></span>
            <button class="btn btn-logout" type="button" onclick="window.location.href='logout.php'">Log Out</button>
        </div>
    </nav>

    <!-- Dashboard Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-4">
                <div class="widget" onclick="window.location.href='#'">
                    <h4>Widget 1</h4>
                    <p>Click here for something cool!</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="widget" onclick="window.location.href='#'">
                    <h4>Widget 2</h4>
                    <p>Click here for something else!</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="widget" onclick="window.location.href='#'">
                    <h4>Widget 3</h4>
                    <p>Click for more options.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
