<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';
//require_once __DIR__ realpath(__DIR__ . '/vendor/autoload.php');
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$rabbit_host = $_ENV['BACKEND'];

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$error = "";

//connecting to rabbit and sending login request to nat
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {

        
    //connecting to rabbit
            $connection = new AMQPStreamConnection(
<<<<<<< HEAD
=======
                $self,
            //    'localhost',
>>>>>>> 221358cbaa614f5963786d384b4a29abcd103280
                $rabbit_host,
                5672,
                'broker',
                'test'
            );

        $channel = $connection->channel();
        $channel->exchange_declare('user_exchange', 'direct', false, true, false);
        $channel->queue_declare('user_events_queue', false, true, false, false);
        $channel->basic_qos(null, 1, null);

        $request = [
            "action"   => "login",
            "username" => $username,
            "password" => $password
        ];

        list($callback_queue,,) = $channel->queue_declare("", false, false, true, false);
        $response = null;
        $corr_id = uniqid();
        $onResponse = function($rep) use($corr_id, &$response) {
            if ($rep->get('correlation_id') === $corr_id) { //they had me remove this but I was right
                $response = $rep->getBody();
            }
        };
        $channel->basic_consume($callback_queue, '', false, true, false, false, $onResponse); //this too :(

        $msg = new AMQPMessage(
            json_encode($request),
            [
                'delivery_mode'  => 2,
                'correlation_id' => $corr_id,
                'reply_to'       => $callback_queue
            ]
        );

        $channel->basic_publish($msg, 'user_exchange', 'user.login');
        
        
        while ($response === null) {
            $channel->wait(null, false, 5); 
        }
        
        $result = json_decode($response, true);

        if (isset($result['success']) && $result['success'] == true) {
            session_regenerate_id(true);
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $username;
            $channel->close();
            $connection->close();
            header("Location: books.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }

        $channel->close();
        $connection->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!--i broke this so hopefully it works now -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noetic — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5" style="padding-top: 200px;">
            <div class="login-card shadow-lg p-4 p-md-5">

                <div class="brand">Noetic.</div>

                <!--from https://www.w3schools.com/php/func_string_htmlspecialchars.asp -->
                <?php if (!empty($error)) : ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post" action="index.php">
                <!--username and password inputs-->
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                <!--buttons :)-->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-theme btn-lg">Log In</button>
                        <button type="button" class="btn btn-outline-theme btn-lg"
                                onclick="window.location.href='registration.php'">Register</button>
                    </div>

                </form>
            </div>
                <!--register for our service :) please :)-->
            <div class="footer-link">
                New to Noetic? <a href="registration.php">Create an account</a>
            </div>

        </div>
    </div>
</div>
</body>
</html>