# SKIT Result Viewer

A beginner-friendly student academic result portal built using
**HTML, CSS, JavaScript, PHP, and MySQL** — no frameworks.

---

## 1. Folder Structure

```
skit-result-viewer/
│
├── index.html        Login page (UI only)
├── style.css          All styling for the whole site
├── script.js           Client-side validation & UI interactions
│
├── db.php              Database connection (shared by all PHP files)
├── login.php          Checks credentials, starts the session
├── dashboard.php      Protected page: profile + result table
├── logout.php          Destroys the session
│
├── admin_login.html    Admin login page (UI only)
├── admin_login.php    Checks admin credentials, starts admin session
├── admin_dashboard.php Protected page: list of all students
├── admin_view_result.php  Protected page: one student's full result
├── admin_add_student.php  Protected page: form to create a new student
├── admin_logout.php    Destroys the admin session
│
└── database.sql        Creates the database, tables & sample data
```

### Why these extra files (`db.php`, `dashboard.php`, `logout.php`)?

- **`db.php`** — Every PHP page needs a database connection. Instead of
  repeating the same 4 lines of connection code in every file, it's
  written once here and pulled into other files with `require "db.php";`.
- **`dashboard.php`** — The protected page students land on after
  login; it needs PHP to check the session and query MySQL, so it
  can't be a plain `.html` file.
- **`logout.php`** — Destroying a session requires PHP, so it needs
  its own small file.

### Why the separate `admin_*` files?

The admin panel is a second, independent login system that reuses the
same techniques (sessions, prepared statements, password hashing) but
is kept in its own files rather than mixed into the student pages:

- **`admin_login.html` / `admin_login.php`** — A separate login form
  and checker, authenticating against the `admins` table instead of
  `students`. This means a student's credentials can never log
  them into the admin panel, and an admin's credentials can never
  log them into the student dashboard — they check different tables.
- **`admin_dashboard.php`** — Lists every student. Unlike the student
  `dashboard.php` (which only ever shows the logged-in student's own
  data), an admin is *authorized* to see everyone's basic details.
- **`admin_view_result.php`** — Shows one chosen student's full result
  sheet, reusing the exact same SGPA/percentage calculation as
  `dashboard.php`.
- **`admin_logout.php`** — Destroys the admin session specifically.

---

## 2. Installing on XAMPP

**Step 1 — Install XAMPP** (skip if already installed)
Download from https://www.apachefriends.org and install it.

**Step 2 — Copy the project into `htdocs`**
Find your XAMPP installation folder and copy the whole
`skit-result-viewer` folder into:

- Windows: `C:\xampp\htdocs\skit-result-viewer`
- macOS: `/Applications/XAMPP/htdocs/skit-result-viewer`
- Linux: `/opt/lampp/htdocs/skit-result-viewer`

**Step 3 — Start Apache and MySQL**
Open the **XAMPP Control Panel** and click **Start** next to both
`Apache` and `MySQL`. Both rows should turn green.

**Step 4 — Create the database using phpMyAdmin**
1. Open a browser and go to: `http://localhost/phpmyadmin`
2. Click **Import** in the top menu.
3. Click **Choose File** and select `database.sql` from the project folder.
4. Click **Go** at the bottom of the page.
5. You should now see a new database called **`skit_result_viewer`**
   in the left sidebar, containing the `students` and `results` tables.

**Step 5 — Open the project in your browser**
```
http://localhost/skit-result-viewer/index.html
```

---

## 3. Sample Login Credentials

| Enrollment Number | Password   | Name          |
|--------------------|-----------|---------------|
| 21EASKIT001         | skit@123 | Aarav Sharma  |
| 21EASKIT002         | skit@456 | Priya Verma   |
| 21EASKIT003         | skit@789 | Rohan Mathur  |

(Rohan Mathur's sample data includes one failed subject, so you can
see how the PASS/FAIL status changes.)

**Admin login** (open `admin_login.html`, or click "Admin Login" at
the bottom of the student login page):

| Username | Password  |
|----------|-----------|
| admin    | admin@123 |

---

## 4. How the Login System Works

1. `index.html` shows a form with two fields: `enrollment_no` and
   `password`. The form's `action="login.php"` means the browser
   sends this data to `login.php` when submitted.
2. `login.php` calls `session_start()`, then reads the posted data
   with `$_POST["enrollment_no"]` and `$_POST["password"]`.
3. It looks up the enrollment number in the `students` table using
   a **prepared statement** (`?` placeholder) — this protects
   against SQL Injection, because user input is never glued
   directly into the SQL string.
4. If a matching row is found, `password_verify($password, $hash)`
   checks the typed password against the **hashed** password stored
   in the database. Hashing is one-way, so even if someone stole the
   database, they could not read the real passwords.
5. On success, `$_SESSION["enrollment_no"]` is set, and the browser
   is redirected to `dashboard.php`.
6. On failure, the browser is redirected back to
   `index.html?error=invalid`, and `script.js` reads that URL
   parameter to display an error message box.

---

## 4b. How the Admin Panel Works

The admin panel is a second, independent login system living
alongside the student one. It follows the exact same steps as
above, with two differences:

1. `admin_login.php` checks the **`admins`** table, not `students`.
2. On success, it sets a **different** session flag —
   `$_SESSION["admin_logged_in"]` — instead of
   `$_SESSION["logged_in"]`. Every admin page checks for this
   specific flag, so a student session can never open an admin
   page, and an admin session can never open the student dashboard.

Once logged in as admin:

- **`admin_dashboard.php`** runs `SELECT ... FROM students` with no
  `WHERE` clause, listing every student, and prints a "View Result"
  link for each one pointing to
  `admin_view_result.php?enrollment_no=...`.
- **`admin_view_result.php`** reads that enrollment number from
  `$_GET`, looks it up with a prepared statement, and displays the
  same result table and SGPA/percentage/PASS-FAIL calculation used
  in the student dashboard.

**Why is it "safe" for `admin_view_result.php` to read the
enrollment number from the URL, when I was told never to do that
for students?** This is one of the more important ideas in the
whole project:

- **Authentication** = proving *who* you are (the login form).
- **Authorization** = what you're *allowed to do* once we know who
  you are.

A student is authenticated but only ever *authorized* to see their
own result — so `dashboard.php` deliberately ignores the URL and
only trusts `$_SESSION["enrollment_no"]`. An admin is authenticated
*and* authorized to see everyone's result, so it's correct for
`admin_view_result.php` to accept an enrollment number as input —
the permission to do so was already established by the
`admin_logged_in` session check at the top of the file, not by
anything in the URL. The URL only says *which* student to look up,
never *whether* the request is allowed.

### Adding a new student — `admin_add_student.php`

From the admin dashboard, click **"+ Add Student"** (top right of the
student list). This opens a form for enrollment number, name, email,
branch, semester, and a password.

What happens when it's submitted:

1. `admin_add_student.php` checks `$_SESSION["admin_logged_in"]` first,
   exactly like every other admin page — a student can never reach
   this form no matter what URL they type.
2. Every field is validated on the **server**, not just in the
   browser (email format, semester between 1–8, password at least
   6 characters). Client-side `required` attributes are only a
   convenience; they can always be bypassed, so the real checks
   happen in PHP.
3. A prepared `SELECT` checks the enrollment number isn't already
   taken, so a duplicate shows a clear message instead of a raw
   database error from the table's `UNIQUE` constraint.
4. The password is run through `password_hash()` **before** it is
   ever sent to the database — the same rule as everywhere else in
   this project. Never edit the `password` column in phpMyAdmin
   directly; always create or reset a password through code that
   calls `password_hash()`, otherwise `password_verify()` will fail
   and the account won't be able to log in (see the "Troubleshooting"
   section for what this looks like when it happens by accident).
5. On success, an `INSERT` (using a prepared statement, `?`
   placeholders) adds the row, and the new student can immediately
   log in at `index.html` with the enrollment number and password
   the admin just set.

---

## 5. How PHP Sessions Work in This Project

A PHP session lets the server "remember" that a particular browser
is logged in, across multiple page loads, without passing the
enrollment number in the URL (which would be insecure).

- `session_start()` is called at the top of `login.php`,
  `dashboard.php`, and `logout.php`. It must run before any HTML
  is sent to the browser.
- On successful login, `login.php` stores:
  - `$_SESSION["logged_in"] = true;`
  - `$_SESSION["enrollment_no"] = ...;`
  - `$_SESSION["name"] = ...;`
- PHP stores this data on the **server**, and gives the browser a
  small cookie (`PHPSESSID`) so it can find the right session data
  on the next request.
- `dashboard.php` checks `if (!isset($_SESSION["logged_in"]))` — if
  the session doesn't exist (nobody logged in, or session expired),
  it redirects back to `index.html`. This is what protects the
  dashboard from unauthenticated access.
- `logout.php` clears `$_SESSION` and calls `session_destroy()`,
  ending the session completely.

---

## 6. How PHP Connects to MySQL

`db.php` uses the **mysqli** extension (object-oriented style):

```php
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
```

**Why mysqli instead of PDO?** mysqli has a simpler, more direct
syntax for beginners working with a single MySQL database. PDO is
more powerful (it can work with different database systems), but
that flexibility isn't needed here, and mysqli's straightforward
`prepare()` / `bind_param()` / `execute()` pattern is easier to
explain in a 1st-year viva.

Every other PHP file that needs the database does:

```php
require "db.php";
```

which runs `db.php` and makes the `$conn` variable available.

---

## 7. How the Result Is Retrieved from MySQL

In `dashboard.php`, **only** the enrollment number stored in the
session is used — never anything from `$_GET` or the URL:

```php
$enrollment_no = $_SESSION["enrollment_no"];

$sql = "SELECT subject_code, subject_name, credits, max_marks,
               obtained_marks, grade, grade_point
        FROM results
        WHERE enrollment_no = ?
        ORDER BY subject_code ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $enrollment_no);
$stmt->execute();
```

This guarantees a logged-in student can only ever see rows from
the `results` table that belong to their own `enrollment_no` —
even if they try to guess or edit a URL, there's no URL parameter
controlling whose data is shown.

---

## 8. How Percentage, SGPA, and Result Status Are Calculated

All calculations happen in **PHP**, using the marks fetched from
MySQL (not hardcoded values), inside `dashboard.php`:

**Percentage:**
```
Percentage = (Total Obtained Marks / Total Max Marks) × 100
```

**SGPA** (credit-weighted average of grade points):
```
SGPA = Σ(credits × grade_point) / Σ(credits)
```
Example: if Subject A (4 credits, grade point 9) and Subject B
(3 credits, grade point 8):
```
SGPA = (4×9 + 3×8) / (4+3) = (36+24) / 7 = 8.57
```

**Result Status:**
A subject is treated as failed if its grade is `F`, or the obtained
marks are below 40% of the maximum marks. If any subject is failed,
the overall result status is `FAIL`; otherwise it's `PASS`.

---

## 9. Security Practices Used

- Passwords are stored using `password_hash()` and checked using
  `password_verify()` — never stored or compared as plain text.
- All SQL queries use **prepared statements** with `?` placeholders
  (protects against SQL Injection).
- `dashboard.php` checks `$_SESSION["logged_in"]` before showing
  any data (protects against unauthenticated access).
- The result query always filters by `$_SESSION["enrollment_no"]`,
  never by a value from `$_GET` or `$_POST` (protects against one
  student viewing another student's result).
- All dynamic values printed into HTML use `htmlspecialchars()`
  (protects against XSS — Cross-Site Scripting).
- `logout.php` fully destroys the session.

---

## 10. Likely Viva Questions & Simple Answers

**Q1. What is the difference between GET and POST?**
GET sends data through the URL (visible, limited size) — used for
simple navigation/links. POST sends data in the request body
(not visible in the URL) — used for forms, especially ones with
passwords, like our login form.

**Q2. Why do you use `password_hash()` instead of storing passwords directly?**
If the database is ever leaked or stolen, plain text passwords would
expose every student's real password. Hashing turns the password into
an irreversible string, so even the developer can't see the original
password — only `password_verify()` can confirm a match.

**Q3. What is a prepared statement and why is it used?**
A prepared statement separates the SQL query structure from the user
input. The `?` is a placeholder that PHP fills in safely afterward,
so malicious input (like `' OR '1'='1`) can never change the meaning
of the query. This prevents SQL Injection.

**Q4. What is a PHP session and how is it different from a cookie?**
A session stores data on the **server**; the browser only holds a
small ID (`PHPSESSID`) to identify which session belongs to it. A
cookie, by contrast, stores the actual data on the **client side**.
Sessions are safer for sensitive data like "is this user logged in".

**Q5. How do you prevent one student from viewing another student's result?**
By never trusting the enrollment number from the URL. The result
query always uses `$_SESSION["enrollment_no"]`, which is only set
by the server after a successful login and cannot be edited by the
user.

**Q6. What is `htmlspecialchars()` used for?**
It converts special characters like `<`, `>`, and `"` into safe HTML
entities before printing user-related data into the page. This
prevents XSS attacks, where someone could try to inject a `<script>`
tag through a name or other field.

**Q7. Why is `session_start()` called at the top of every protected page?**
PHP needs `session_start()` on every page that wants to read or
write `$_SESSION` data. Without it, `$_SESSION` would be empty, and
the page wouldn't know a user is logged in.

**Q8. What database engine/extension did you use to connect PHP to MySQL, and why?**
`mysqli`, because its syntax is simpler for a single-database beginner
project compared to PDO, which is more flexible but adds complexity
that isn't needed here.

**Q9. How is SGPA calculated in your project?**
SGPA = (sum of credits × grade_point for each subject) divided by
(sum of credits). This weights subjects with more credits more
heavily, matching how most universities calculate it.

**Q10. What happens if a student is not logged in and tries to open `dashboard.php` directly?**
`dashboard.php` checks `$_SESSION["logged_in"]` at the very top. If
it's not set, `header("Location: index.html")` immediately redirects
them back to the login page before any result data is shown.

**Q11. Why did you separate CSS/JS into their own files instead of writing them inline?**
It keeps HTML, styling, and behavior organized and easier to
maintain/debug, and allows the same `style.css` and `script.js` to
be reused across multiple pages (`index.html` and `dashboard.php`).

**Q12. What is the purpose of `db.php`?**
It centralizes the database connection code in one file so it isn't
repeated in every PHP script. If the database credentials ever
change, you only update them in one place.

**Q13. How does the admin login stay separate from the student login?**
They check two different tables (`admins` vs `students`) and set two
different session flags (`admin_logged_in` vs `logged_in`). Every
admin page checks specifically for `admin_logged_in`, so a student's
session — even a valid, logged-in one — does not grant access to any
`admin_*.php` page.

**Q14. `dashboard.php` never trusts the URL, but `admin_view_result.php` reads `enrollment_no` from `$_GET`. Isn't that a security flaw?**
No — this is the difference between authentication (proving who you
are) and authorization (what you're allowed to do). A student is
only authorized to see their own result, so their page ignores the
URL entirely. An admin is authorized to see every student's result,
so it's correct for their page to accept a URL parameter saying
*which* student to look up. The actual permission check still
happens first, via `$_SESSION["admin_logged_in"]` — without that
session flag, the page redirects to the login form before it even
looks at `$_GET`.

**Q15. Could a student guess the admin URL and get into the admin panel?**
They could open `admin_dashboard.php`, but the very first lines of
that file check `$_SESSION["admin_logged_in"]`. A student's session
never has that flag set — only `admin_login.php` sets it, and only
after verifying a username/password against the `admins` table — so
they would be redirected straight to `admin_login.html` instead.

---

## 11. Quick Project Flow Recap

```
index.html  (login form)
    │  (POST enrollment_no + password)
    ▼
login.php   (verify via MySQL, start session)
    │  (redirect on success)
    ▼
dashboard.php  (session check → fetch profile + results → calculate
                SGPA/percentage/status → display)
    │  (click Logout)
    ▼
logout.php  (destroy session)
    │
    ▼
index.html
```

**Admin flow:**

```
admin_login.html  (admin login form)
    │  (POST username + password)
    ▼
admin_login.php   (verify via "admins" table, start admin session)
    │  (redirect on success)
    ▼
admin_dashboard.php  (admin session check → list ALL students)
    │  (click "View Result" for any student)
    ▼
admin_view_result.php  (admin session check → fetch that student's
                        profile + results → calculate SGPA/percentage/
                        status → display)
    │  (click Logout)
    ▼
admin_logout.php  (destroy admin session)
    │
    ▼
admin_login.html
```
