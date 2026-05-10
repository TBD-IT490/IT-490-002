<?php
session_start();

//REDIRECT TO LOGIN IF NOT LOGGED IN PROPERLY (so you can't access without signing in hehe)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: index.php");
    exit();
}

//functions and headers
require_once '../includes/data.php';
require_once '../includes/header.php';

$msg = '';
//$tab = $_GET['tab'] ?? 'books';


//ALL OF THIS MUST MATCH NAT'S BACKEND CODE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $display = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    $result = rmq_rpc('user.update', [
        'username' => $display, 
        'email'=> $email,
        'bio'=> $bio,
    ]);
    if($result['success'] ?? false) {
        //updating sesh values so they show up on profile immediately 
        $_SESSION['username'] = $display;
        $_SESSION['email'] = $email;
        $_SESSION['bio'] = $bio;

        $msg = 'Profile updated.';
    } else {
        $msg = 'Could not save changes. Please try again.';
    }
   
}

?>


<!--HTML CODE-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noetic — Update Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
<!-- cant c stuff rn so it looks basic i think, forgive me taryn :( -->
    <div class="brand">
        <h1>Update Profile</h1>
        <?php if ($msg): ?>
            <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <form method="POST" action="updateProfile.php">
            <div class="mb-3">
                <label for="username" class="form-label">Display Name</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="bio" class="form-label">Bio</label>
                <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($_SESSION['bio'] ?? '') ?></textarea>
            </div>

            <a href="../pages/profile.php" class="btn-n btn" style="text-decoration: none; color: inherit;">Back</a>
            <button type="submit" class="btn-n btn">Save Changes</button>
        </form>
    </div>
</body>
</html>

<!--footer code :) at least it stays consistent-->
<?php require_once '../includes/footer.php'; ?>