<?php
session_start();

require_once __DIR__ . "/vendor/autoload.php";

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

//connecting to matt's vm on the broker
define('RABBITMQ_HOST', '100.101.27.73');
define('RABBITMQ_PORT', 5672);
define('RABBITMQ_USER', 'broker');
define('RABBITMQ_PASS', 'test');

$error = "";

//registering a user
if (isset($_POST["register"])) {

    $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
    $password = $_POST["password"];

    if (empty($username) || empty($email) || empty($password)) {
        die("Please fill all fields!");
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    //stream connection
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
    $corr_id = uniqid(); //MATT made me remove this then told me to put it back again smh
    $onResponse = function ($rep) use ($corr_id, &$response) {
        if ($rep->get("correlation_id") === $corr_id) {
            $response = $rep->getBody();
        }
    };
    $channel->basic_consume($callback_queue,'',false,true,false,false, $onResponse); //this too
    $msg = new AMQPMessage(json_encode($message), [
        "delivery_mode" => 2,
        "correlation_id" => $corr_id,
        "reply_to" => $callback_queue,
    ]);

    //publishing the exchange and waiting for response
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
        header("Location: index.php"); //once done registering, go to login page (instead of the home page)

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
<!--stylesheets! and other things, i broke it so badly i'm scared to code in html ever again-->
    <meta charset="UTF-8">>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noetic — Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
<!--i left this in here because it completely BROKE when I removed it and i'd rather it be ugly code than broken
I'm sure you understand-->
</head>

<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5 col-xl-4" style="padding-top:150px">

        <!--registration card-->
            <div class="register-card shadow-lg">

                <div class="card-header-custom">
                    <div class="brand">Noetic<span>.</span></div>
                    <div class="heading">Create your account</div>
                </div>

                <div class="p-4 p-md-5">

                    <?php if (!empty($error)): ?>
                    <!--error message display-->
                        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form action="registration.php" method="POST">

                    <!--username and email inputs-->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                    <!--password and strength meter i wrote below-->
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password"
                                   required oninput="checkStrength(this.value)">
                            <div class="strength-bar" id="strengthBar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-label" id="strengthLabel"></div>
                        </div>

                    <!--button :)-->

                        <div class="d-grid">
                            <button type="submit" name="register" class="btn btn-theme btn-lg">
                                Register
                            </button>
                        </div>

                    </form>
                </div>
            </div>

            <!-- Already have an account?? Click here!!! Don't waste your time registering again :) -->
            <div class="footer-link">
                Already have an account? <a href="index.php">Log in here</a>
            </div>

        </div>
    </div>
</div>

<script>
    //strength bar for passwords, more visual than anything tbh I liked it
    function checkStrength(val) {
        const strengthBar = document.getElementById('strengthBar');
        const strengnthFill = document.getElementById('strengthFill');
        const strengthLabel = document.getElementById('strengthLabel');

    //showing password strength bar
        strengthBar.style.display = val.length ? 'block' : 'none';

        let strengthScore = 0;
    
        //checking password conditions
        if (password.length >= 8){
            strengthScore +=1;
       }

        if (/[A-Z]/.test(password)){
            strengthScore +=1;
        }

        if (/[0-9]/.test(password)){
            strengthScore+=1;
     }

        if (/[^A-Za-z0-9]/.test(password)){
            strengthScore+=1;
        }

        //default is is weak
        let width = "25%";
        let color = "red";
        let text = "Weak";

        //fair means orange
        if (strengthScore === 2) {
            width = "50%";
            color = "orange";
            text = "Fair";
        } 

        //yellow means good password, not horrible not amazing
        else if (strengthScore === 3) {
            width = "75%";
            color = "yellow";
            text = "Good";
        } 

        //AMAZING PASSWORD WOW YES 10/10 you won't get hacked
        else if (strengthScore >= 4) {
            width = "100%";
            color = "green";
            text = "Strong";
        }

        //styling for the bar
        strengthFill.style.width = width;
        strengthFill.style.background = color;
        strengthLabel.textContent = text;
        strengthLabel.style.color = color;
}
</script>
</body>
</html>