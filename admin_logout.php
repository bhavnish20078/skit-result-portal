<?php
/*
    admin_logout.php
    -----------------
    Destroys the admin's session and sends them back to the
    admin login page. Same pattern as logout.php.
*/

session_start();

// Remove all session variables
$_SESSION = [];

// Delete the session cookie from the browser as well
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

// Send the admin back to the admin login page
header("Location: admin_login.html");
exit();
?>
