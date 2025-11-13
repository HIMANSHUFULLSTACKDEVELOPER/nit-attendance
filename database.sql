-- NIT College Attendance System Database
-- Database: nitcollege_attendance_system

CREATE DATABASE IF NOT EXISTS nitcollege_attendance_system;
USE nitcollege_attendance_system;

-- 1. Departments Table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dept_name VARCHAR(100) NOT NULL,
    dept_code VARCHAR(20) UNIQUE NOT NULL,
    hod_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Users Table (Admin, HOD, Teachers)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    role ENUM('admin', 'hod', 'teacher') NOT NULL,
    department_id INT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
);

-- 3. Classes Table
CREATE TABLE classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    department_id INT NOT NULL,
    year INT NOT NULL,
    section VARCHAR(10) NOT NULL,
    teacher_id INT NOT NULL,
    semester INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_class (department_id, year, section, semester, academic_year)
);

-- 4. Students Table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roll_number VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    department_id INT NOT NULL,
    class_id INT NOT NULL,
    year INT NOT NULL,
    semester INT NOT NULL,
    admission_year VARCHAR(10),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- 5. Parents Table
CREATE TABLE parents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parent_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    student_id INT NOT NULL,
    relationship ENUM('father', 'mother', 'guardian') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- 6. Student Attendance Table
CREATE TABLE student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present', 'absent', 'late') NOT NULL,
    marked_by INT NOT NULL,
    remarks TEXT,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (student_id, class_id, attendance_date)
);

-- 7. Attendance Summary Table (for quick reports)
CREATE TABLE attendance_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    total_days INT DEFAULT 0,
    present_days INT DEFAULT 0,
    absent_days INT DEFAULT 0,
    late_days INT DEFAULT 0,
    attendance_percentage DECIMAL(5,2) DEFAULT 0.00,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_summary (student_id, class_id, month, year)
);

-- Insert Sample Data

-- Insert Admin
INSERT INTO users (username, password, full_name, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin@nitcollege.edu', 'admin');
-- Password: password

-- Insert Departments
INSERT INTO departments (dept_name, dept_code) VALUES
('Computer Science & Engineering', 'CSE'),
('Electronics & Communication', 'ECE'),
('Mechanical Engineering', 'ME'),
('Civil Engineering', 'CE'),
('Electrical Engineering', 'EE');

-- Insert HODs
INSERT INTO users (username, password, full_name, email, role, department_id) VALUES
('hod_cse', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Rajesh Kumar', 'hod.cse@nitcollege.edu', 'hod', 1),
('hod_ece', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Priya Sharma', 'hod.ece@nitcollege.edu', 'hod', 2);

-- Update departments with HOD
UPDATE departments SET hod_id = 2 WHERE id = 1;
UPDATE departments SET hod_id = 3 WHERE id = 2;

-- Insert Teachers
INSERT INTO users (username, password, full_name, email, phone, role, department_id) VALUES
('teacher1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Prof. Amit Verma', 'amit.verma@nitcollege.edu', '9876543210', 'teacher', 1),
('teacher2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Prof. Sneha Patel', 'sneha.patel@nitcollege.edu', '9876543211', 'teacher', 1),
('teacher3', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Prof. Rahul Singh', 'rahul.singh@nitcollege.edu', '9876543212', 'teacher', 2);

-- Insert Classes
INSERT INTO classes (class_name, department_id, year, section, teacher_id, semester, academic_year) VALUES
('CSE - 2nd Year - Section A', 1, 2, 'A', 4, 3, '2024-25'),
('CSE - 2nd Year - Section B', 1, 2, 'B', 5, 3, '2024-25'),
('ECE - 3rd Year - Section A', 2, 3, 'A', 6, 5, '2024-25');

-- Insert Students
INSERT INTO students (roll_number, full_name, email, phone, password, department_id, class_id, year, semester, admission_year) VALUES
('CSE2023001', 'Rahul Sharma', 'rahul.sharma@student.nitcollege.edu', '9876543220', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 2, 3, '2023'),
('CSE2023002', 'Priya Gupta', 'priya.gupta@student.nitcollege.edu', '9876543221', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 2, 3, '2023'),
('CSE2023003', 'Amit Kumar', 'amit.kumar@student.nitcollege.edu', '9876543222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 2, 3, '2023'),
('CSE2023004', 'Sneha Singh', 'sneha.singh@student.nitcollege.edu', '9876543223', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 2, 2, 3, '2023'),
('ECE2022001', 'Vikram Rao', 'vikram.rao@student.nitcollege.edu', '9876543224', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 3, 3, 5, '2022');

-- Insert Parents
INSERT INTO parents (parent_name, email, phone, password, student_id, relationship) VALUES
('Mr. Ramesh Sharma', 'ramesh.sharma@gmail.com', '9876543230', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'father'),
('Mrs. Sunita Gupta', 'sunita.gupta@gmail.com', '9876543231', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'mother'),
('Mr. Vijay Kumar', 'vijay.kumar@gmail.com', '9876543232', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'father');

-- Sample Attendance Data
INSERT INTO student_attendance (student_id, class_id, attendance_date, status, marked_by) VALUES
(1, 1, CURDATE(), 'present', 4),
(2, 1, CURDATE(), 'present', 4),
(3, 1, CURDATE(), 'absent', 4),
(4, 2, CURDATE(), 'late', 5),
(5, 3, CURDATE(), 'present', 6);