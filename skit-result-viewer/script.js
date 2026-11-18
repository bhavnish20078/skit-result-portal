/* =============================================================
   SKIT Result Viewer - script.js
   Handles small client-side interactions:
   1. Show/Hide password toggle
   2. Basic form validation before submitting the login form
   3. Reading error messages from the URL (sent by login.php)
   4. Confirmation popup before logout
   ============================================================= */

// Run this code only after the whole page has loaded
document.addEventListener("DOMContentLoaded", function () {

  // ---------- 1. SHOW / HIDE PASSWORD ----------
  const togglePasswordBtn = document.getElementById("togglePassword");
  const passwordInput = document.getElementById("password");

  if (togglePasswordBtn && passwordInput) {
    togglePasswordBtn.addEventListener("click", function () {
      // If the field is currently a password field, switch it to text
      if (passwordInput.type === "password") {
        passwordInput.type = "text";
        togglePasswordBtn.textContent = "Hide";
      } else {
        passwordInput.type = "password";
        togglePasswordBtn.textContent = "Show";
      }
    });
  }

  // ---------- 2. BASIC LOGIN FORM VALIDATION ----------
  const loginForm = document.getElementById("loginForm");

  if (loginForm) {
    loginForm.addEventListener("submit", function (event) {
      let isValid = true;

      const enrollmentInput = document.getElementById("enrollment_no");
      const enrollmentError = document.getElementById("enrollmentError");
      const passwordError = document.getElementById("passwordError");

      // Clear old error messages first
      enrollmentError.textContent = "";
      passwordError.textContent = "";

      // Enrollment number should not be empty
      if (enrollmentInput.value.trim() === "") {
        enrollmentError.textContent = "Enrollment number is required.";
        isValid = false;
      }

      // Password should not be empty
      if (passwordInput.value.trim() === "") {
        passwordError.textContent = "Password is required.";
        isValid = false;
      }

      // If validation fails, stop the form from submitting to login.php
      if (!isValid) {
        event.preventDefault();
      }
      // If valid, the form submits normally to login.php (server-side
      // checks the credentials against the database - client-side
      // validation is only a first line of convenience, NOT security).
    });
  }

  // ---------- 2b. BASIC ADMIN LOGIN FORM VALIDATION ----------
  // Same idea as the student form above, just for admin_login.html.
  const adminLoginForm = document.getElementById("adminLoginForm");

  if (adminLoginForm) {
    adminLoginForm.addEventListener("submit", function (event) {
      let isValid = true;

      const usernameInput = document.getElementById("username");
      const usernameError = document.getElementById("usernameError");
      const adminPasswordError = document.getElementById("adminPasswordError");

      usernameError.textContent = "";
      adminPasswordError.textContent = "";

      if (usernameInput.value.trim() === "") {
        usernameError.textContent = "Username is required.";
        isValid = false;
      }

      if (passwordInput.value.trim() === "") {
        adminPasswordError.textContent = "Password is required.";
        isValid = false;
      }

      if (!isValid) {
        event.preventDefault();
      }
    });
  }

  // ---------- 3. SHOW SERVER-SIDE LOGIN ERROR (if any) ----------
  // login.php redirects back to index.html?error=invalid on failure.
  // We read that from the URL and display a friendly message.
  const errorBox = document.getElementById("errorBox");

  if (errorBox) {
    const params = new URLSearchParams(window.location.search);
    const errorType = params.get("error");

    if (errorType) {
      let message = "";

      switch (errorType) {
        case "invalid":
          message = "Invalid enrollment number or password. Please try again.";
          break;
        case "empty":
          message = "Please fill in both enrollment number and password.";
          break;
        default:
          message = "Something went wrong. Please try again.";
      }

      errorBox.textContent = message;
      errorBox.style.display = "block";
    }
  }

  // ---------- 4. CONFIRM BEFORE LOGOUT ----------
  const logoutBtn = document.getElementById("logoutBtn");

  if (logoutBtn) {
    logoutBtn.addEventListener("click", function (event) {
      const confirmLogout = confirm("Are you sure you want to logout?");
      if (!confirmLogout) {
        // Stop the link from navigating to logout.php
        event.preventDefault();
      }
    });
  }

});
