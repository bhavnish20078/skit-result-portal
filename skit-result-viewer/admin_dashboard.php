<?php
/*
    admin_dashboard.php
    --------------------
    Purpose: The admin's home page after logging in. Lists every
    student in the database, with a link to view each student's
    full result.

    WHY THIS PAGE IS ALLOWED TO SHOW EVERYONE'S DATA:
    dashboard.php (the STUDENT page) deliberately ignores any
    enrollment number typed in the URL and only ever shows the
    result belonging to $_SESSION["enrollment_no"] - a normal
    student is only authorized to see their OWN result.
    An admin is a different kind of user with different
    permissions, so this page is allowed to list every student.
    This is the difference between AUTHENTICATION (proving who
    you are) and AUTHORIZATION (what you're allowed to do once
    we know who you are) - a common viva question.
*/

session_start();
require "db.php";

// ----- Protect this page: only a logged-in ADMIN may see it -----
// Note the different session flag from the student dashboard -
// a student session can never open this page, and an admin
// session can never open the student dashboard.
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: admin_login.html");
    exit();
}

// ----- Fetch every student for the list -----
// No user input is involved in this query, so a plain query is
// fine here (nothing to inject).
$sql = "SELECT enrollment_no, name, branch, semester, email FROM students ORDER BY enrollment_no";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - SKIT Result Viewer</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
  <div class="brand">
    <div class="logo-mini">SKIT</div>
    <span>SKIT Result Viewer &mdash; Admin Panel</span>
  </div>
  <div style="display:flex; align-items:center; gap:14px;">
    <span style="font-size:13px;">
      Logged in as <strong><?php echo htmlspecialchars($_SESSION["admin_username"]); ?></strong>
    </span>
    <a href="admin_logout.php" id="logoutBtn" class="btn-logout">Logout</a>
  </div>
</div>

<div class="dashboard-container">

  <div class="toolbar-row">
    <h2 class="welcome-heading" style="margin-bottom:0;">All Students</h2>
    <a href="admin_add_student.php" class="btn-secondary">+ Add Student</a>
  </div>

  <div class="result-card">
    <h2>Registered Students (<?php echo $result->num_rows; ?>)</h2>

    <table class="result-table">
      <thead>
        <tr>
          <th>Enrollment No.</th>
          <th>Name</th>
          <th>Branch</th>
          <th>Semester</th>
          <th>Email</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // Loop through each student row and print a table row.
        // htmlspecialchars() is used on every value before printing
        // it, so that even if a name contained HTML/script tags,
        // it would be displayed as plain text, not executed. This
        // protects against stored XSS (Cross-Site Scripting).
        while ($student = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($student["enrollment_no"]) . "</td>";
            echo "<td>" . htmlspecialchars($student["name"]) . "</td>";
            echo "<td>" . htmlspecialchars($student["branch"]) . "</td>";
            echo "<td>" . htmlspecialchars($student["semester"]) . "</td>";
            echo "<td>" . htmlspecialchars($student["email"]) . "</td>";

            // urlencode() keeps the URL safe even if the enrollment
            // number ever contained unusual characters.
            $link = "admin_view_result.php?enrollment_no=" . urlencode($student["enrollment_no"]);
            echo "<td><a href='" . $link . "' class='btn-logout' style='background:var(--accent-blue); border-color:var(--accent-blue);'>View Result</a></td>";
            echo "</tr>";
        }
        ?>
      </tbody>
    </table>
  </div>

</div>

<footer class="login-footer">
  &copy; 2026 SKIT Result Viewer &mdash; A student academic project
</footer>

<script src="script.js"></script>
</body>
</html>
<?php
// Always close the connection once the page is fully built.
$conn->close();
?>
