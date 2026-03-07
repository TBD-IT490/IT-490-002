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
                'localhost',
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

	    list($callback_queue,,)=$channel->queue_declare(
		"",
		false,
		false,
		true,
		false
	    );
	    $response = null;
	    $corr_id = uniqid();
	    $onResponse = function($rep) use($corr_id, &$response) {
                    if ($rep->get('correlation_id')=== $corr_id) {
                        $response = $rep->getBody();
		    }
		    $error = "fuck";
            };
	    $channel->basic_consume($callback_queue,'',false,true,false,false, $onResponse);
	    $error = "daf";
	    $msg = new AMQPMessage(
                json_encode($request),
                [
                   'delivery_mode'  => 2,
		   'correlation_id' => $corr_id,
                   'reply_to'=>$callback_queue
	        ]
            );
	  
            $channel->basic_publish($msg, 'user_exchange', 'user.login');
	    while (!$response) {
		    $error = "waiting for someone";
		    $channel->wait();

	    }
	    $result=json_decode($response,true);
	
            if (isset($result['success']) && $result['success'] == true) {

                session_regenerate_id(true);

                $_SESSION['loggedin'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['id']       = $result['id'];

                $channel->close();
                $connection->close();
		$error = "it worked";
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
    <title>Login Page</title>

    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #202030;
            color: #DCBCCE;
            height: 100vh;
        }

        .login-card {
            background-color: #39304A;
            border: none;
            border-radius: 15px;
        }

        .form-label {
            color: #DCBCCE; /* lighter label color */
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

        h1 {
            color: #DCBCCE;
            text-align: center;
            margin-bottom: 20px;
        }

        .error-message {
            background-color: #86715B;
            color: #202030;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">

            <div class="card login-card shadow-lg p-4">

                <h1>Noetic</h1>

                <?php if (!empty($error)) : ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="index.php">

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-theme btn-lg">Log In</button>
                        <button type="button" class="btn btn-theme btn-lg"
                                onclick="window.location.href='registration.php'">
                            Register
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

</body>
</html>
