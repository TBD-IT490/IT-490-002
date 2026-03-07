<?php
session_start();

require_once __DIR__ . "/vendor/autoload.php";

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
$error = "";

if (isset($_POST["register"])) {
    // Sanitize input
    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        die("Please fill all fields!");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $connection = new AMQPStreamConnection(
        "100.101.27.73",
        5672,
        "broker",
        "test",
    );
    $channel = $connection->channel();

    $channel->exchange_declare("user_exchange", "direct", false, true, false);
    $channel->queue_declare("user_events_queue", false, true, false, false);

    $message = [
        "username" => $username,
        "email" => $email,
        "password_hash" => $password_hash,
    ];
    list($callback_queue, ,) = $channel->queue_declare(
        "",
        false,
        false,
        true,
        false,
    );
    $response = null;
    $corr_id = uniqid();
    $onResponse = function ($rep) use ($corr_id, &$response) {
        if ($rep->get("correlation_id") === $corr_id) {
            $response = $rep->getBody();
        }
        $error = "fuck";
    };
    $channel->basic_consume($callback_queue,'',false,true,false,false, $onResponse);
    $msg = new AMQPMessage(json_encode($message), [
        "delivery_mode" => 2,
        "correlation_id" => $corr_id,
        "reply_to" => $callback_queue,
    ]);
    $channel->basic_publish($msg, "user_exchange", "user.register");
    while (!$response) {
        $error = "waiting for someone";
        $channel->wait();
    }

    $result = json_decode($response, true);
    if (isset($result["success"]) && $result["success"] == true) {
        session_regenerate_id(true);
        $_SESSION["loggedin"] = true;
        $_SESSION["username"] = $username;
        //$_SESSION["id"] = $insert_stmt->insert_id;
        $error = "it worked";
        header("Location: index.php");

        $channel->close();
	
	$connection->close();
    	exit();
    } else {
        $error = "Invalid username, email, or password";
    }

    $channel->close();
    $connection->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
	.form-label {
    		color: #DCBCCE; /* lighter color for labels */
	}
        body {
            background-color: #202030;
            color: #DCBCCE;
            height: 100vh;
        }

        .register-card {
            background-color: #39304A;
            border: none;
            border-radius: 15px;
        }

        .form-control {
            background-color: #202030;
            border: 1px solid #86715B;
            color: #DCBCCE;
        }

        .form-control:focus {
            background-color: #202030;
            color: #DCBCCE;
            border-color: #242E0F;
            box-shadow: 0 0 0 0.2rem rgba(36, 46, 15, 0.4);
        }

        .btn-theme {
            background-color: #242E0F;
            border: none;
            color: #DCBCCE;
        }

        .btn-theme:hover {
            background-color: #86715B;
            color: #202030;
        }

        .card-header {
            background-color: #242E0F;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            color: #DCBCCE;
        }

        .error-message {
            background-color: #86715B;
            color: #202030;
            border-radius: 8px;
            padding: 10px;
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">

            <div class="card register-card shadow-lg">
                <div class="card-header text-center">
                    <h4 class="mb-0">Create Account</h4>
                </div>

                <div class="card-body p-4">

                    <?php if (!empty($error)): ?>
                        <div class="error-message mb-3 text-center">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="registration.php" method="POST">

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="username" 
                                   name="username" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="register" class="btn btn-theme btn-lg">
                                Register
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <p class="text-center mt-3" style="color:#86715B;">
                Already have an account? 
                <a href="index.php" style="color:#DCBCCE; text-decoration:none;">
                    Login here
                </a>
            </p>

        </div>
    </div>
</div>

</body>
</html>
