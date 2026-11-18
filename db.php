<?php
/*
    db.php
    -------
    Purpose: Central place to connect to the MySQL database.

    WHY THIS FILE EXISTS:
    Every PHP page (login.php, dashboard.php, logout.php) needs
    a database connection. Instead of writing the same connection
    code again and again, we write it once here and "include" it
    wherever it is needed. This keeps the code DRY (Don't Repeat
    Yourself) and easy to update (e.g. if the password changes,
    we only change it in ONE place).

    WHY mysqli (and not PDO):
    mysqli is chosen here because its syntax is simpler and more
    beginner-friendly for procedural PHP, and it is commonly
    taught in 1st-year college courses. PDO is more flexible and
    supports multiple databases, but mysqli is enough for this
    single-database MySQL project.
*/

// ----- Database connection settings -----
// Default XAMPP/WAMP settings: host = localhost, user = root, password = ""
$db_host = "localhost";
$db_user = "root";
$db_pass = "";              // Default XAMPP MySQL password is empty
$db_name = "skit_result_viewer";

// Create the connection using mysqli (object-oriented style)
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// If connection fails, stop the script and show an error.
// die() stops execution immediately - useful for fatal errors.
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set character set to utf8mb4 so names/subjects with special
// characters are stored and displayed correctly.
$conn->set_charset("utf8mb4");
?>
