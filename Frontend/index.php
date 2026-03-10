<?php
session_start();

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {

        
            $connection = new AMQPStreamConnection(
                '100.101.27.73',
            //    'localhost',
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
            if ($rep->get('correlation_id') === $corr_id) {
                $response = $rep->getBody();
            }
        };
        $channel->basic_consume($callback_queue, '', false, true, false, false, $onResponse);

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
            header("Location: dashboard.php");
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noetic — Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --deep: #202030;
            --card: #39304A;
            --blush: #DCBCCE;
            --moss: #242E0F;
            --umber: #86715B;
            --dim: #2d2840;
            --text-muted: #a89aac;
        }

        body {
            background-color: var(--deep);
            color: var(--blush);
            font-family: 'Crimson Text', Georgia, serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(57,48,74,0.4) 0%, transparent 60%);
        }

        .login-card {
            background-color: var(--card);
            border: 1px solid rgba(134,113,91,0.25);
            border-radius: 4px;
        }

        .brand {
            font-family: 'IM Fell English', serif;
            font-size: 2.4rem;
            color: var(--blush);
            text-align: center;
            letter-spacing: 0.04em;
            margin-bottom: 0.2rem;
        }
        .brand span { color: var(--umber); }

        .brand-sub {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 0.95rem;
            color: var(--text-muted);
            text-align: center;
            margin-bottom: 2rem;
            letter-spacing: 0.08em;
        }

        .form-label {
            color: var(--text-muted);
            font-size: 0.78rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .form-control {
            background-color: var(--deep);
            border: 1px solid rgba(134,113,91,0.35);
            color: var(--blush);
            border-radius: 2px;
            font-family: 'Crimson Text', serif;
            font-size: 1rem;
        }

        .form-control:focus {
            background-color: var(--dim);
            color: var(--blush);
            border-color: var(--umber);
            box-shadow: 0 0 0 2px rgba(134,113,91,0.2);
        }

        .form-control::placeholder { color: var(--text-muted); }

        .btn-theme {
            background-color: var(--moss);
            border: 1px solid rgba(134,113,91,0.3);
            color: var(--blush);
            font-family: 'Crimson Text', serif;
            font-size: 0.9rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border-radius: 2px;
            padding: 0.55rem 1.4rem;
            transition: background 0.2s, color 0.2s;
        }

        .btn-theme:hover {
            background-color: var(--umber);
            color: var(--deep);
        }

        .btn-outline-theme {
            background: transparent;
            border: 1px solid var(--umber);
            color: var(--blush);
            font-family: 'Crimson Text', serif;
            font-size: 0.9rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            border-radius: 2px;
            padding: 0.55rem 1.4rem;
            transition: background 0.2s, color 0.2s;
        }

        .btn-outline-theme:hover {
            background-color: var(--umber);
            color: var(--deep);
        }

        .error-message {
            background-color: rgba(134,113,91,0.2);
            border: 1px solid var(--umber);
            color: var(--blush);
            border-radius: 2px;
            padding: 0.7rem 1rem;
            text-align: center;
            margin-bottom: 1.2rem;
            font-size: 0.95rem;
        }

        .ornament {
            text-align: center;
            color: var(--umber);
            letter-spacing: 0.5rem;
            margin: 1.2rem 0;
            font-size: 0.75rem;
        }

        .footer-link {
            color: var(--text-muted);
            font-size: 0.9rem;
            text-align: center;
            margin-top: 1.2rem;
            font-family: 'Crimson Text', serif;
        }
        .footer-link a {
            color: var(--umber);
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-link a:hover { color: var(--blush); }
    </style>
</head>

<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">

            <div class="login-card shadow-lg p-4 p-md-5">

                <div class="brand">Noetic</div>

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