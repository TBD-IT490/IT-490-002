<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Book</title>
</head>
<body>

<h1>Add a Book</h1>

<form action="save_book.php" method="POST">

    <label>Title</label><br>
    <input type="text" name="title" required><br><br>

    <label>Author</label><br>
    <input type="text" name="author"><br><br>

    <label>ISBN</label><br>
    <input type="text" name="isbn"><br><br>

    <label>Condition</label><br>
    <select name="condition">
        <option>Like New</option>
        <option>Good</option>
        <option>Fair</option>
        <option>Poor</option>
    </select><br><br>

    <button type="submit">
        Add Book
    </button>

</form>

</body>
</html>