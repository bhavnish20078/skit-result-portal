-- =========================================================
-- SKIT RESULT VIEWER - DATABASE SETUP
-- =========================================================
-- How to use this file:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Click the "Import" tab
-- 3. Choose this file (database.sql) and click "Go"
-- This single file creates the database, both tables, and
-- sample data so the project works immediately after import.
-- =========================================================

-- Create the database (only if it doesn't already exist)
CREATE DATABASE IF NOT EXISTS skit_result_viewer;
USE skit_result_viewer;

-- ---------------------------------------------------------
-- TABLE: students
-- Stores login details and basic profile of each student
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_no VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    branch VARCHAR(100) NOT NULL,
    semester INT NOT NULL,
    password VARCHAR(255) NOT NULL,   -- stores the HASHED password, never plain text
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- TABLE: results
-- Stores subject-wise marks for each student.
-- Linked to "students" using enrollment_no.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_no VARCHAR(20) NOT NULL,
    subject_code VARCHAR(20) NOT NULL,
    subject_name VARCHAR(150) NOT NULL,
    credits INT NOT NULL,
    max_marks INT NOT NULL,
    obtained_marks INT NOT NULL,
    grade VARCHAR(5) NOT NULL,
    grade_point DECIMAL(3,1) NOT NULL,
    semester INT NOT NULL,
    -- Foreign key: every result row must belong to a real student.
    -- ON DELETE CASCADE means if a student is ever removed, their
    -- result rows are automatically cleaned up too.
    FOREIGN KEY (enrollment_no) REFERENCES students(enrollment_no)
        ON DELETE CASCADE ON UPDATE CASCADE
);

-- ---------------------------------------------------------
-- TABLE: admins
-- A separate table for admin accounts, kept apart from
-- "students" on purpose - an admin is not a student, and this
-- also means a student account can NEVER accidentally be used
-- to log into the admin panel, and vice versa.
-- ---------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,   -- hashed, same as students.password
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------------
-- SAMPLE ADMIN ACCOUNT
-- Plain text password (for you to log in and test with): admin@123
-- ---------------------------------------------------------
INSERT INTO admins (username, password) VALUES
('admin', '$2y$10$YsYNYQb4iom1cF0yfAlgq.C8txiQZbPORRIYBcRvXa7YPyLhPIsiq');

-- ---------------------------------------------------------
-- SAMPLE STUDENTS
-- ---------------------------------------------------------
-- NOTE ON PASSWORDS:
-- The values below are already hashed using PHP's password_hash()
-- (bcrypt algorithm). You can NEVER see the real password just by
-- reading the database - that is the whole point of hashing.
--
-- The PLAIN TEXT passwords (for you to log in and test with) are
-- also listed in README.md, section "Sample Login Credentials":
--   21EASKIT001 / skit@123   (Aarav Sharma)
--   21EASKIT002 / skit@456   (Priya Verma)
--   21EASKIT003 / skit@789   (Rohan Mathur)
-- ---------------------------------------------------------
INSERT INTO students (enrollment_no, name, email, branch, semester, password) VALUES
('21EASKIT001', 'Aarav Sharma', 'aarav.sharma@skit.ac.in', 'Computer Science Engineering', 5, '$2y$10$A0MOfasjieFYulZB4Vvg..37zU2NU.dQmoYRq5j9zUg1yYfwRNfLi'),
('21EASKIT002', 'Priya Verma',  'priya.verma@skit.ac.in',  'Information Technology',       5, '$2y$10$A0MOfasjieFYulZB4Vvg..mv6vCvMVmiWQOOOZMtThUlPgyH/8ZY2'),
('21EASKIT003', 'Rohan Mathur', 'rohan.mathur@skit.ac.in', 'Electronics & Communication',  5, '$2y$10$A0MOfasjieFYulZB4Vvg..QErzrhUcrEyjD8UHNVtnFt.rY0/fiwi');

-- ---------------------------------------------------------
-- SAMPLE RESULTS (Semester 5 subjects for each student)
-- ---------------------------------------------------------

-- Results for Aarav Sharma (21EASKIT001) - all subjects passed
INSERT INTO results (enrollment_no, subject_code, subject_name, credits, max_marks, obtained_marks, grade, grade_point, semester) VALUES
('21EASKIT001', 'CS501', 'Database Management Systems', 4, 100, 88, 'A+', 10.0, 5),
('21EASKIT001', 'CS502', 'Operating Systems',           4, 100, 76, 'A',   9.0, 5),
('21EASKIT001', 'CS503', 'Computer Networks',           4, 100, 82, 'A+', 10.0, 5),
('21EASKIT001', 'CS504', 'Software Engineering',        3, 100, 71, 'B+',  8.0, 5),
('21EASKIT001', 'CS505', 'Web Technologies',             3, 100, 91, 'A+', 10.0, 5),
('21EASKIT001', 'CS506', 'Database Lab',                 2, 100, 95, 'A+', 10.0, 5);

-- Results for Priya Verma (21EASKIT002) - all subjects passed
INSERT INTO results (enrollment_no, subject_code, subject_name, credits, max_marks, obtained_marks, grade, grade_point, semester) VALUES
('21EASKIT002', 'IT501', 'Database Management Systems', 4, 100, 65, 'B',   7.0, 5),
('21EASKIT002', 'IT502', 'Operating Systems',           4, 100, 58, 'C+',  6.0, 5),
('21EASKIT002', 'IT503', 'Computer Networks',           4, 100, 73, 'B+',  8.0, 5),
('21EASKIT002', 'IT504', 'Software Engineering',        3, 100, 69, 'B',   7.0, 5),
('21EASKIT002', 'IT505', 'Web Technologies',             3, 100, 80, 'A',  9.0, 5),
('21EASKIT002', 'IT506', 'Database Lab',                 2, 100, 85, 'A',  9.0, 5);

-- Results for Rohan Mathur (21EASKIT003) - includes one FAILED subject
-- (EC501 has grade "F"), so you can see the FAIL status in action.
INSERT INTO results (enrollment_no, subject_code, subject_name, credits, max_marks, obtained_marks, grade, grade_point, semester) VALUES
('21EASKIT003', 'EC501', 'Digital Signal Processing', 4, 100, 30, 'F',  0.0, 5),
('21EASKIT003', 'EC502', 'Microprocessors',           4, 100, 60, 'B',  7.0, 5),
('21EASKIT003', 'EC503', 'Control Systems',           4, 100, 55, 'C',  6.0, 5),
('21EASKIT003', 'EC504', 'Communication Systems',     3, 100, 62, 'B',  7.0, 5),
('21EASKIT003', 'EC505', 'Electronic Devices',        3, 100, 68, 'B',  7.0, 5),
('21EASKIT003', 'EC506', 'DSP Lab',                    2, 100, 72, 'B+', 8.0, 5);
