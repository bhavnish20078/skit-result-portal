<?php
/*
    admin_view_result.php
    ----------------------
    Purpose: Lets a logged-in ADMIN view any one student's full
    result, chosen via admin_dashboard.php's "View Result" links.

    WHY IT IS SAFE FOR THIS PAGE TO READ enrollment_no FROM THE URL
    (unlike dashboard.php, which never does this for students):
    This page is only reachable after admin_dashboard.php has
    already confirmed $_SESSION["admin_logged_in"] === true below.
    A normal student login can NEVER set that session flag - only
    admin_login.php can, and only after checking the "admins"
    table. So even though the enrollment number comes from the
    URL, the PERMISSION to view any student's result still comes
    entirely from the server-side session, not from the URL.

    We also double-check with a prepared statement that the
    enrollment number in the URL actually exists in the database,
    so a typo or a made-up value just shows "student not found"
    instead of breaking the page.
*/

session_start();
require "db.php";

// ----- 1. Protect this page: admin only -----
if (!isset($_SESSION["admin_logged_in"]) || $_SESSION["admin_logged_in"] !== true) {
    header("Location: admin_login.html");
    exit();
}

// ----- 2. Read and validate the enrollment number from the URL -----
$enrollment_no = trim($_GET["enrollment_no"] ?? "");

if ($enrollment_no === "") {
    header("Location: admin_dashboard.php");
    exit();
}

// ----- 3. Fetch the student's profile (prepared statement) -----
$sql = "SELECT name, enrollment_no, email, branch, semester
        FROM students WHERE enrollment_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $enrollment_no);
$stmt->execute();
$studentResult = $stmt->get_result();
$student = $studentResult->fetch_assoc();
$stmt->close();

// If no such student exists, don't proceed further.
if (!$student) {
    $conn->close();
    $studentNotFound = true;
} else {
    $studentNotFound = false;

    // ----- 4. Fetch that student's results -----
    $sql = "SELECT subject_code, subject_name, credits, max_marks,
                   obtained_marks, grade, grade_point
            FROM results
            WHERE enrollment_no = ?
            ORDER BY subject_code ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $enrollment_no);
    $stmt->execute();
    $resultsData = $stmt->get_result();

    $subjects = [];
    while ($row = $resultsData->fetch_assoc()) {
        $subjects[] = $row;
    }
    $stmt->close();
    $conn->close();

    // ----- 5. Same SGPA/percentage/pass-fail calculation as dashboard.php -----
    $totalMaxMarks = 0;
    $totalObtainedMarks = 0;
    $totalCredits = 0;
    $totalCreditPoints = 0;
    $hasFailedSubject = false;

    foreach ($subjects as $subject) {
        $totalMaxMarks      += $subject["max_marks"];
        $totalObtainedMarks += $subject["obtained_marks"];
        $totalCredits       += $subject["credits"];
        $totalCreditPoints  += ($subject["credits"] * $subject["grade_point"]);

        if ($subject["grade"] === "F" || $subject["obtained_marks"] < ($subject["max_marks"] * 0.4)) {
            $hasFailedSubject = true;
        }
    }

    $percentage = $totalMaxMarks > 0
        ? round(($totalObtainedMarks / $totalMaxMarks) * 100, 2)
        : 0;

    $sgpa = $totalCredits > 0
        ? round($totalCreditPoints / $totalCredits, 2)
        : 0;

    $resultStatus = $hasFailedSubject ? "FAIL" : "PASS";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Result - Admin Panel</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
  <div class="brand">
    <div class="logo-mini">SKIT</div>
    <span>SKIT Result Viewer &mdash; Admin Panel</span>
  </div>
  <div style="display:flex; align-items:center; gap:14px;">
    <a href="admin_dashboard.php" style="color:var(--white); font-size:13px;">&larr; All Students</a>
    <a href="admin_logout.php" id="logoutBtn" class="btn-logout">Logout</a>
  </div>
</div>

<div class="dashboard-container">

  <?php if ($studentNotFound): ?>

    <div class="result-card">
      <p>No student was found with that enrollment number.</p>
      <p class="hint-text"><a href="admin_dashboard.php">&larr; Back to student list</a></p>
    </div>

  <?php else: ?>

    <h1 class="welcome-heading">Result: <?php echo htmlspecialchars($student["name"]); ?></h1>

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

  <?php endif; ?>

</div>

<footer class="login-footer">
  &copy; 2026 SKIT Result Viewer &mdash; A student academic project
</footer>

<script src="script.js"></script>
</body>
</html>
