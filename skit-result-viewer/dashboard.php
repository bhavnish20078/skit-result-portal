<?php
/*
    dashboard.php
    -------------
    Purpose: The main page a student sees after logging in.
    Shows their profile details and their own result table.

    SECURITY NOTE (IMPORTANT FOR VIVA):
    We NEVER take the enrollment number from the URL (like
    dashboard.php?enrollment_no=21EASKIT002) to decide whose
    result to show. If we did that, any student could change
    the number in the address bar and see someone else's marks.

    Instead, we ONLY use $_SESSION["enrollment_no"], which was
    set by login.php after verifying the password. This value
    cannot be edited by the user from the browser.
*/

session_start();
require "db.php";

// ----- 1. Protect this page from unauthenticated access -----
// If there is no active session, send the user back to login.
if (!isset($_SESSION["logged_in"]) || $_SESSION["logged_in"] !== true) {
    header("Location: index.html");
    exit();
}

// This is the ONLY source of truth for "who is logged in".
$enrollment_no = $_SESSION["enrollment_no"];

// ----- 2. Fetch the student's profile info -----
$sql = "SELECT name, enrollment_no, email, branch, semester
        FROM students WHERE enrollment_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $enrollment_no);
$stmt->execute();
$studentResult = $stmt->get_result();
$student = $studentResult->fetch_assoc();
$stmt->close();

// Safety check: if somehow the student record was deleted after
// login, destroy the session and send back to login page.
if (!$student) {
    session_destroy();
    header("Location: index.html");
    exit();
}

// ----- 3. Fetch this student's results (own data only) -----
$sql = "SELECT subject_code, subject_name, credits, max_marks,
               obtained_marks, grade, grade_point
        FROM results
        WHERE enrollment_no = ?
        ORDER BY subject_code ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $enrollment_no);
$stmt->execute();
$resultsData = $stmt->get_result();

// Store all result rows in an array so we can loop over them
// twice: once to print the table, once to calculate totals.
$subjects = [];
while ($row = $resultsData->fetch_assoc()) {
    $subjects[] = $row;
}
$stmt->close();
$conn->close();

// ----- 4. Calculate totals, percentage, SGPA, pass/fail -----
// SGPA formula used here (standard weighted average):
//   SGPA = (sum of (credits x grade_point) for all subjects)
//          / (sum of credits for all subjects)
$totalMaxMarks = 0;
$totalObtainedMarks = 0;
$totalCredits = 0;
$totalCreditPoints = 0; // sum of (credits x grade_point)
$hasFailedSubject = false;

foreach ($subjects as $subject) {
    $totalMaxMarks      += $subject["max_marks"];
    $totalObtainedMarks += $subject["obtained_marks"];
    $totalCredits       += $subject["credits"];
    $totalCreditPoints  += ($subject["credits"] * $subject["grade_point"]);

    // A subject is considered failed if its grade is "F"
    // or the obtained marks are less than 40% of max marks.
    if ($subject["grade"] === "F" || $subject["obtained_marks"] < ($subject["max_marks"] * 0.4)) {
        $hasFailedSubject = true;
    }
}

// Avoid division by zero if a student has no results yet
$percentage = $totalMaxMarks > 0
    ? round(($totalObtainedMarks / $totalMaxMarks) * 100, 2)
    : 0;

$sgpa = $totalCredits > 0
    ? round($totalCreditPoints / $totalCredits, 2)
    : 0;

$resultStatus = $hasFailedSubject ? "FAIL" : "PASS";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - SKIT Result Viewer</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ============================================= -->
<!-- NAVBAR                                         -->
<!-- ============================================= -->
<nav class="navbar">
  <div class="brand">
    <div class="logo-mini">SKIT</div>
    <span>SKIT Result Viewer</span>
  </div>
  <!--
    htmlspecialchars() converts special characters (like <, >, ")
    into safe HTML entities. This prevents XSS (Cross-Site
    Scripting) attacks if a name ever contained HTML/JS code.
  -->
  <span>Welcome, <?php echo htmlspecialchars($student["name"]); ?></span>
  <a href="logout.php" id="logoutBtn" class="btn-logout">Logout</a>
</nav>

<div class="dashboard-container">

  <h1 class="welcome-heading">Student Dashboard</h1>

  <!-- ============================================= -->
  <!-- PROFILE CARD                                   -->
  <!-- ============================================= -->
  <div class="profile-card">
    <div class="profile-item">
      <div class="label">Full Name</div>
      <div class="value"><?php echo htmlspecialchars($student["name"]); ?></div>
    </div>
    <div class="profile-item">
      <div class="label">Enrollment Number</div>
      <div class="value"><?php echo htmlspecialchars($student["enrollment_no"]); ?></div>
    </div>
    <div class="profile-item">
      <div class="label">Branch</div>
      <div class="value"><?php echo htmlspecialchars($student["branch"]); ?></div>
    </div>
    <div class="profile-item">
      <div class="label">Semester</div>
      <div class="value"><?php echo htmlspecialchars($student["semester"]); ?></div>
    </div>
    <div class="profile-item">
      <div class="label">Email</div>
      <div class="value"><?php echo htmlspecialchars($student["email"]); ?></div>
    </div>
  </div>

  <!-- ============================================= -->
  <!-- RESULT CARD                                    -->
  <!-- ============================================= -->
  <div class="result-card">
    <h2>Semester Result</h2>

    <?php if (count($subjects) === 0): ?>
      <p>No result data has been published yet.</p>
    <?php else: ?>

      <table class="result-table">
        <thead>
          <tr>
            <th>Subject Code</th>
            <th>Subject Name</th>
            <th>Credits</th>
            <th>Max Marks</th>
            <th>Obtained Marks</th>
            <th>Grade</th>
            <th>Grade Point</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($subjects as $subject): ?>
            <tr>
              <td><?php echo htmlspecialchars($subject["subject_code"]); ?></td>
              <td><?php echo htmlspecialchars($subject["subject_name"]); ?></td>
              <td><?php echo htmlspecialchars($subject["credits"]); ?></td>
              <td><?php echo htmlspecialchars($subject["max_marks"]); ?></td>
              <td><?php echo htmlspecialchars($subject["obtained_marks"]); ?></td>
              <td class="<?php echo $subject["grade"] === "F" ? "grade-fail" : "grade-pass"; ?>">
                <?php echo htmlspecialchars($subject["grade"]); ?>
              </td>
              <td><?php echo htmlspecialchars($subject["grade_point"]); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Summary: totals, percentage, SGPA, pass/fail -->
      <div class="summary-strip">
        <div class="summary-box">
          <div class="label">Total Marks</div>
          <div class="value"><?php echo $totalMaxMarks; ?></div>
        </div>
        <div class="summary-box">
          <div class="label">Marks Obtained</div>
          <div class="value"><?php echo $totalObtainedMarks; ?></div>
        </div>
        <div class="summary-box">
          <div class="label">Percentage</div>
          <div class="value"><?php echo $percentage; ?>%</div>
        </div>
        <div class="summary-box">
          <div class="label">SGPA</div>
          <div class="value"><?php echo $sgpa; ?></div>
        </div>
        <div class="summary-box">
          <div class="label">Result Status</div>
          <div class="value <?php echo $resultStatus === "PASS" ? "status-pass" : "status-fail"; ?>">
            <?php echo $resultStatus; ?>
          </div>
        </div>
      </div>

    <?php endif; ?>
  </div>

</div>

<footer class="login-footer">
  &copy; 2026 SKIT Result Viewer &mdash; A student academic project
</footer>

<script src="script.js"></script>
</body>
</html>
