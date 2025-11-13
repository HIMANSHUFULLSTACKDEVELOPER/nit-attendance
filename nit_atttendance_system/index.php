<?php
require_once 'db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    header("Location: $role/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIT College - Attendance System</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h1>🎓 NIT College</h1>
            <h2>Attendance Management System</h2>
        </div>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    if ($_GET['error'] === 'invalid') {
                        echo "❌ Invalid username or password!";
                    } elseif ($_GET['error'] === 'unauthorized') {
                        echo "⛔ Unauthorized access!";
                    } elseif ($_GET['error'] === 'inactive') {
                        echo "⚠️ Your account is inactive. Contact admin.";
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] === 'logout'): ?>
            <div class="alert alert-success">
                ✅ Logged out successfully!
            </div>
        <?php endif; ?>
        
        <form action="login_process.php" method="POST" class="login-form">
            <div class="form-group">
                <label for="role">Login As:</label>
                <select name="role" id="role" required>
                    <option value="">-- Select Role --</option>
                    <option value="admin">👨‍💼 Admin</option>
                    <option value="hod">👔 HOD</option>
                    <option value="teacher">👨‍🏫 Teacher</option>
                    <option value="student">👨‍🎓 Student</option>
                    <option value="parent">👨‍👩‍👦 Parent</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="username">Username / Roll Number / Email:</label>
                <input type="text" name="username" id="username" placeholder="Enter your username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="btn btn-primary btn-block">🔐 Login</button>
        </form>
        
        <div class="login-footer">
            <p>📧 Forgot password? Contact administrator</p>
            <p class="demo-info">
                <strong>Demo Credentials:</strong><br>
                Admin: admin / password<br>
                HOD: hod_cse / password<br>
                Teacher: teacher1 / password<br>
                Student: CSE2023001 / password<br>
                Parent: ramesh.sharma@gmail.com / password
            </p>
        </div>
    </div>
</body>
</html>