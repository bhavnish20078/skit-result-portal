<?php
/*
    admin_login.php
    ----------------
    Purpose: Receives the admin login form (POST) from
    admin_login.html, checks the username and password against
    the "admins" table (NOT the "students" table), and starts a
    PHP session for the admin if correct.

    This file is almost identical to login.php on purpose - the
    same security rules apply to every login form in the project:
    prepared statements, password_verify(), and session_start()
    called first.

    IMPORTANT DIFFERENCE FROM STUDENT LOGIN:
    We store a DIFFERENT session flag here: $_SESSION["admin_logged_in"].
    This is deliberately not the same flag as the student's
    $_SESSION["logged_in"], so that a student session can never be
    mistaken for an admin session, or the other way round.
*/

session_start();
require "db.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: admin_login.html");
    exit();
}

// ----- 1. Get form data safely -----
$username = trim($_POST["username"] ?? "");
$password = trim($_POST["password"] ?? "");

// ----- 2. Basic server-side validation -----
if ($username === "" || $password === "") {
    header("Location: admin_login.html?error=empty");
    exit();
}

// ----- 3. Look up the admin by username (prepared statement) -----
$sql = "SELECT id, username, password FROM admins WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// ----- 4. Verify the password -----
if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();

    if (password_verify($password, $admin["password"])) {

        // Correct login! Use an ADMIN-SPECIFIC session flag so this
        // can never be confused with a student's session.
        $_SESSION["admin_logged_in"] = true;
        $_SESSION["admin_username"]  = $admin["username"];

        $stmt->close();
        $conn->close();

        header("Location: admin_dashboard.php");
        exit();
    }
}

// ----- 5. If we reach here, login failed -----
$stmt->close();
$conn->close();

header("Location: admin_login.html?error=invalid");
exit();
?>
