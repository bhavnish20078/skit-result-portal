<?php
/*
    admin_add_student.php
    ----------------------
    Purpose: Lets a logged-in ADMIN create a new student account -
    both the profile (name, email, branch, semester) and the login
    password, in one form.

    WHY THIS IS THE ONLY PLACE A NEW STUDENT CAN BE CREATED:
    Only an admin can reach this page (checked below with the same
    $_SESSION["admin_logged_in"] flag used everywhere else in the
    admin panel). A student can never create their own account or
    anyone else's - this mirrors how a real college portal works,
    where the institute's office creates accounts, not students.

    SECURITY STEPS USED HERE (same rules as the rest of the project):
    - Server-side validation of every field (never trust the form
      alone - a user could submit data without even opening the page).
    - A prepared statement checks the enrollment number isn't already
      taken, so we give a friendly error instead of a broken page.
    - password_hash() turns the admin-entered password into a bcrypt
      hash before it ever touches the database - never store it
      as plain text (see the mistake this often causes: editing the
      password column directly in phpMyAdmin as plain text breaks
      login, because password_verify() only understands a real hash).
    - A prepared statement (with ?) is used for the INSERT itself,
      so nothing typed into the form can be interpreted as SQL.
*/

session_start();
require "db.php";

// ----- 1. Protect this page: admin only -----
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: admin_login.html");
    exit();
}

// These hold whatever the admin typed, so the form can be
// re-displayed with their input still filled in if something
// is wrong (except the password - that is never redisplayed).
$enrollment_no = "";
$name          = "";
$email         = "";
$branch        = "";
$semester      = "";

$errors  = [];   // collects every validation problem found
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ----- 2. Read the form data -----
    $enrollment_no = trim($_POST["enrollment_no"] ?? "");
    $name          = trim($_POST["name"] ?? "");
    $email         = trim($_POST["email"] ?? "");
    $branch        = trim($_POST["branch"] ?? "");
    $semester      = trim($_POST["semester"] ?? "");
    $password      = trim($_POST["password"] ?? "");

    // ----- 3. Validate every field on the server -----
    // (The form also uses HTML5 "required" so most mistakes are
    // caught before this even runs, but server-side validation is
    // what actually protects the database - client-side checks can
    // always be bypassed.)
    if ($enrollment_no === "") {
        $errors[] = "Enrollment number is required.";
    }
    if ($name === "") {
        $errors[] = "Name is required.";
    }
    if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    }
    if ($branch === "") {
        $errors[] = "Branch is required.";
    }
    if ($semester === "" || !is_numeric($semester) || $semester < 1 || $semester > 8) {
        $errors[] = "Semester must be a number between 1 and 8.";
    }
    if ($password === "" || strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    // ----- 4. Make sure this enrollment number isn't already used -----
    // We check this ourselves (rather than only relying on the
    // database's UNIQUE constraint) so we can show a clear message
    // instead of a raw database error.
    if (empty($errors)) {
        $checkSql  = "SELECT id FROM students WHERE enrollment_no = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("s", $enrollment_no);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $errors[] = "A student with this enrollment number already exists.";
        }
        $checkStmt->close();
    }

    // ----- 5. If everything is valid, hash the password and insert -----
    if (empty($errors)) {
        // Never store the password as typed - always hash it first.
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $insertSql = "INSERT INTO students (enrollment_no, name, email, branch, semester, password)
                      VALUES (?, ?, ?, ?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        // Types in order: s=enrollment_no, s=name, s=email, s=branch, i=semester, s=hashed password
        $insertStmt->bind_param("ssssis", $enrollment_no, $name, $email, $branch, $semester, $hashedPassword);

        if ($insertStmt->execute()) {
            $success = true;
            // Clear the form fields after a successful save.
            $enrollment_no = $name = $email = $branch = $semester = "";
        } else {
            $errors[] = "Something went wrong while saving. Please try again.";
        }
        $insertStmt->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Student - Admin Panel</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- Navbar - identical pattern to admin_dashboard.php -->
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
    <h2 class="welcome-heading" style="margin-bottom:0;">Add New Student</h2>
    <a href="admin_dashboard.php" class="btn-secondary">&larr; All Students</a>
  </div>

  <div class="result-card" style="max-width:600px;">

    <?php if ($success): ?>
      <div class="success-box">Student added successfully. You can add another below, or go back to the student list.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
      <div class="error-box">
        <?php foreach ($errors as $error): ?>
          <div><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- novalidate is left OFF here on purpose, unlike the login forms -
         this lets the browser's own HTML5 validation (required, type="email",
         min/max on the number field) act as a first line of defence, in
         addition to the PHP validation above. -->
    <form action="admin_add_student.php" method="POST">

      <div class="form-group">
        <label for="enrollment_no">Enrollment Number</label>
        <input type="text" id="enrollment_no" name="enrollment_no"
               placeholder="e.g. 21EASKIT004"
               value="<?php echo htmlspecialchars($enrollment_no); ?>" required>
      </div>

      <div class="form-group">
        <label for="name">Full Name</label>
        <input type="text" id="name" name="name"
               placeholder="e.g. Kavya Singh"
               value="<?php echo htmlspecialchars($name); ?>" required>
      </div>

      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               placeholder="e.g. kavya.singh@skit.ac.in"
               value="<?php echo htmlspecialchars($email); ?>" required>
      </div>

      <div class="form-group">
        <label for="branch">Branch</label>
        <input type="text" id="branch" name="branch"
               placeholder="e.g. Computer Science Engineering"
               value="<?php echo htmlspecialchars($branch); ?>" required>
      </div>

      <div class="form-group">
        <label for="semester">Semester</label>
        <input type="number" id="semester" name="semester" min="1" max="8"
               placeholder="1 to 8"
               value="<?php echo htmlspecialchars($semester); ?>" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="password-wrapper">
          <!-- Same id="password" / id="togglePassword" pattern used on
               index.html and admin_login.html - script.js already
               handles the show/hide toggle for any page using these ids. -->
          <input type="password" id="password" name="password"
                 placeholder="At least 6 characters" required>
          <button type="button" id="togglePassword" class="toggle-btn">Show</button>
        </div>
      </div>

      <button type="submit" class="btn-primary">Add Student</button>
    </form>

  </div>
</div>

<footer class="login-footer">
  &copy; 2026 SKIT Result Viewer &mdash; A student academic project
</footer>

<script src="script.js"></script>
</body>
</html>
