<?php
/*
    logout.php
    ----------
    Purpose: Destroys the current PHP session and sends the
    student back to the login page.

    A session must be "started" (resumed) before it can be
    destroyed, so we call session_start() first.
*/

session_start();

// Remove all session variables
$_SESSION = [];

// Delete the session cookie from the browser as well
// (extra safety, not strictly required for a beginner project)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session data on the server
session_destroy();

// Send the user back to the login page
header("Location: index.html");
exit();
?>
