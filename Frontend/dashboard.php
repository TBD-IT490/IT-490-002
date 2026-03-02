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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .widget {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 20px;
            text-align: center;
            margin: 10px;
            cursor: pointer;
        }
        .widget:hover {
            background-color: #e9ecef;
        }
    </style>
</head>
<body class="bg-light">

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-primary">
        <div class="container-fluid">
            <span class="navbar-brand text-white">Welcome, <?php echo htmlspecialchars($username); ?></span>
            <button class="btn btn-outline-light" type="button" onclick="window.location.href='logout.php'">Log Out</button>
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

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
