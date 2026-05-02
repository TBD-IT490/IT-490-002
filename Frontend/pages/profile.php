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
$tab = $_GET['tab'] ?? 'books';


//ALL OF THIS MUST MATCH NAT'S BACKEND CODE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $result = rmq_rpc('user.update', [
        'display_name' => trim($_POST['display_name'] ?? ''), 
        'email'=> trim($_POST['email'] ?? ''),
        'bio'=> trim($_POST['bio'] ?? ''),
        'preferences' => $_POST['prefs'] ?? [],
    ]);
    $msg = ($result['success'] ?? false)
        ? 'Profile updated.'
        : 'Could not save changes. Please try again.';
}

?>


<!--HTML CODE-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Noetic — Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=IM+Fell+English:ital@0;1&family=Crimson+Text:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="container mt-5">
        <h1>Profile</h1>
        <?php if ($msg): ?>
            <div class="alert alert-info"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <form method="POST" action="profile.php">
            <div class="mb-3">
                <label for="display_name" class="form-label">Display Name</label>
                <input type="text" class="form-control" id="display_name" name="display_name" value="<?= htmlspecialchars($_SESSION['display_name'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="bio" class="form-label">Bio</label>
                <textarea class="form-control" id="bio" name="bio" rows="3"><?= htmlspecialchars($_SESSION['bio'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <!-- REMINDER: ask taryn ab preferences -->
                <label class="form-label">Preferences</label><br>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="pref1" name="prefs[]" value="pref1" <?= in_array('pref1', $_SESSION['preferences'] ?? []) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="pref1">Preference 1</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="pref2" name="prefs[]" value="pref2" <?= in_array('pref2', $_SESSION['preferences'] ?? []) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="pref2">Preference 2</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" id="pref3" name="prefs[]" value="pref3" <?= in_array('pref3', $_SESSION['preferences'] ?? []) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="pref3">Preference 3</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </div>
</body>
</html>

<!--footer code :) at least it stays consistent-->
<?php require_once '../includes/footer.php'; ?>