<?php session_start(); ?>

<form action="save_book.php" method="POST">

    <input type="text"
           name="title"
           placeholder="Title"
           required>

    <input type="text"
           name="author"
           placeholder="Author">

    <input type="text"
           name="isbn"
           placeholder="ISBN">

    <select name="condition">
        <option>Like New</option>
        <option>Good</option>
        <option>Fair</option>
    </select>

    <button type="submit">
        Add Book
    </button>

</form>