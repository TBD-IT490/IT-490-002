<?php
// Initialize variables
$email = $password = $repeat_password = "";
$email_err = $password_err = $repeat_password_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email address.";
    } else {
        $email = trim($_POST["email"]);
        // Check if email is valid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        }
    }

    // Validate password
    if (empty(trim($_POST["psw"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["psw"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["psw"]);
    }

    // Validate repeat password
    if (empty(trim($_POST["psw-repeat"]))) {
        $repeat_password_err = "Please repeat your password.";
    } else {
        $repeat_password = trim($_POST["psw-repeat"]);
        if ($password != $repeat_password) {
            $repeat_password_err = "Passwords do not match.";
        }
    }

    // If no errors, process form
    if (empty($email_err) && empty($password_err) && empty($repeat_password_err)) {
        // For example, you can hash the password here for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Normally, here you would save the user data to a database
        // Example: saving user data to a database (MySQL)
        /*
        $conn = new mysqli("localhost", "username", "password", "database_name");
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $email, $hashed_password);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        */

        // Here you could redirect the user to a success page or a login page
        echo "Account created successfully!";
    } else {
        // Display errors
        echo "<p><strong>Errors:</strong></p>";
        echo $email_err ? "<p>$email_err</p>" : "";
        echo $password_err ? "<p>$password_err</p>" : "";
        echo $repeat_password_err ? "<p>$repeat_password_err</p>" : "";
    }
}
?>

<!DOCTYPE html>
<!DOCTYPE html>
 <form action="action_page.php" style="border:1px solid #ccc">
<link rel="stylesheet" href="styles.css">
  <div class="container">
    <h1>Sign Up</h1>
    <p>Please fill in this form to create an account.</p>
    <hr>

    <label for="email"><b>Email</b></label>
    <input type="text" placeholder="Enter Email" name="email" required>

    <label for="psw"><b>Password</b></label>
    <input type="password" placeholder="Enter Password" name="psw" required>

    <label for="psw-repeat"><b>Repeat Password</b></label>
    <input type="password" placeholder="Repeat Password" name="psw-repeat" required>

    <label>
      <input type="checkbox" checked="checked" name="remember" style="margin-bottom:15px"> Remember me
    </label>

    <p>By creating an account you agree to our <a href="#" style="color:dodgerblue">Terms & Privacy</a>.</p>

    <div class="clearfix">
      <button type="button" class="cancelbtn">Cancel</button>
      <button type="submit" class="signupbtn">Sign Up</button>
    </div>
  </div>
</form> 
</html>

</html>
