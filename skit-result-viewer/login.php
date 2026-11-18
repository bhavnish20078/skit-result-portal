<?php
/*
    login.php
    ---------
    Purpose: Receives the login form data (POST request) from
    index.html, checks the enrollment number and password against
    the "students" table in MySQL, and starts a PHP session if
    the login is correct.

    SECURITY NOTES FOR VIVA:
    - We use a prepared statement (with "?" placeholder) to prevent
      SQL Injection. The enrollment number typed by the user is
      NEVER directly inserted into the SQL query string.
    - We use password_verify() to compare the typed password with
      the HASHED password stored in the database. We never store
      or compare plain text passwords.
    - session_start() must be called before any HTML output, so
      it is the very first thing this script does.
*/

session_start();       // Start (or resume) a PHP session
require "db.php";      // Get the $conn (database connection) variable

// This script only makes sense when the form is submitted using POST.
// If someone tries to open login.php directly in the browser (GET
// request), send them back to the login page.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.html");
    exit();
}

// ----- 1. Get form data safely -----
// trim() removes extra spaces the student might accidentally type.
$enrollment_no = trim($_POST["enrollment_no"] ?? "");
$password      = trim($_POST["password"] ?? "");

// ----- 2. Basic server-side validation -----
// Client-side JS already checks this, but we NEVER trust the
// browser alone - the server must validate again.
if ($enrollment_no === "" || $password === "") {
    header("Location: index.html?error=empty");
    exit();
}

// ----- 3. Look up the student by enrollment number -----
// Using a PREPARED STATEMENT ("?" placeholder) instead of putting
// $enrollment_no directly into the SQL string. This prevents
// SQL Injection attacks.
$sql = "SELECT id, enrollment_no, name, password FROM students WHERE enrollment_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $enrollment_no); // "s" = the parameter is a string
$stmt->execute();
$result = $stmt->get_result();

// ----- 4. Verify the password -----
if ($result->num_rows === 1) {
    $student = $result->fetch_assoc();

    // password_verify() compares the typed password with the
    // hashed password stored in the database. It automatically
    // handles the salt that was used when the hash was created.
    if (password_verify($password, $student["password"])) {

        // Correct login! Store the enrollment number in the
        // session so other pages (dashboard.php) know who is
        // logged in, WITHOUT trusting anything from the URL.
        $_SESSION["logged_in"] = true;
        $_SESSION["enrollment_no"] = $student["enrollment_no"];
        $_SESSION["name"] = $student["name"];

        $stmt->close();
        $conn->close();

        header("Location: dashboard.php");
        exit();
    }
}

// ----- 5. If we reach here, login failed -----
// (Either enrollment number not found, or password did not match)
$stmt->close();
$conn->close();

header("Location: index.html?error=invalid");
exit();
?>
