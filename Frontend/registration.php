<?php
session_start();

require_once __DIR__ . "/vendor/autoload.php";

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// RabbitMQ Configuration
define('RABBITMQ_HOST', '100.101.27.73');
define('RABBITMQ_PORT', 5672);
define('RABBITMQ_USER', 'broker');
define('RABBITMQ_PASS', 'test');

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
        RABBITMQ_HOST,
        RABBITMQ_PORT,
        RABBITMQ_USER,
        RABBITMQ_PASS
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
    };
    $channel->basic_consume($callback_queue,'',false,true,false,false, $onResponse);
    $msg = new AMQPMessage(json_encode($message), [
        "delivery_mode" => 2,
        "correlation_id" => $corr_id,
        "reply_to" => $callback_queue,
    ]);
    $channel->basic_publish($msg, "user_exchange", "user.register");
    while (!$response) {
        $channel->wait();
    }

    $result = json_decode($response, true);
    if (isset($result["success"]) && $result["success"] == true) {
        session_regenerate_id(true);
        $_SESSION["loggedin"] = true;
        $_SESSION["username"] = $username;
        $_SESSION["id"] = $result["id"] ?? null;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noetic — Register</title>
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
                radial-gradient(ellipse at 80% 50%, rgba(57,48,74,0.4) 0%, transparent 60%),
                radial-gradient(ellipse at 20% 80%, rgba(36,46,15,0.2) 0%, transparent 50%);
        }

        .register-card {
            background-color: var(--card);
            border: 1px solid rgba(134,113,91,0.25);
            border-radius: 4px;
        }

        .card-header-custom {
            background-color: var(--moss);
            border-bottom: 1px solid rgba(134,113,91,0.25);
            border-radius: 4px 4px 0 0;
            padding: 1.2rem 1.5rem;
            text-align: center;
        }

        .card-header-custom .brand {
            font-family: 'IM Fell English', serif;
            font-size: 1.6rem;
            color: var(--blush);
            letter-spacing: 0.04em;
            margin: 0;
        }
        .card-header-custom .brand span { color: var(--umber); }

        .card-header-custom .heading {
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-top: 0.2rem;
            letter-spacing: 0.1em;
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

        /* Password strength meter */
        .strength-bar {
            height: 3px;
            border-radius: 2px;
            background: rgba(134,113,91,0.2);
            margin-top: 6px;
            overflow: hidden;
            display: none;
        }
        .strength-fill {
            height: 100%;
            border-radius: 2px;
            transition: width 0.3s, background 0.3s;
        }
        .strength-label {
            font-size: 0.72rem;
            color: var(--text-muted);
            margin-top: 3px;
        }
    </style>
</head>

<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4">

            <div class="register-card shadow-lg">

                <div class="card-header-custom">
                    <div class="brand">Noetic<span>.</span></div>
                    <div class="heading">Create your account</div>
                </div>

                <div class="p-4 p-md-5">

                    <?php if (!empty($error)): ?>
                        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form action="registration.php" method="POST">

                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   required oninput="checkStrength(this.value)">
                            <div class="strength-bar" id="strengthBar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-label" id="strengthLabel"></div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" name="register" class="btn btn-theme btn-lg">
                                Register
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <div class="footer-link">
                Already have an account? <a href="index.php">Log in here</a>
            </div>

        </div>
    </div>
</div>

<script>
function checkStrength(val) {
    const bar = document.getElementById('strengthBar');
    const fill = document.getElementById('strengthFill');
    const label = document.getElementById('strengthLabel');
    bar.style.display = val.length ? 'block' : 'none';
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        { w: '25%', color: '#86715B', text: 'Weak' },
        { w: '50%', color: '#a08060', text: 'Fair' },
        { w: '75%', color: '#b09050', text: 'Good' },
        { w: '100%', color: '#c9a87c', text: 'Strong' },
    ];
    const l = levels[Math.max(0, score - 1)];
    fill.style.width = l.w;
    fill.style.background = l.color;
    label.textContent = l.text;
    label.style.color = l.color;
}
</script>
</body>
</html>