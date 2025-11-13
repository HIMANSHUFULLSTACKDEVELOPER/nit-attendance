<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_student'])) {
        $roll_number = sanitize($_POST['roll_number']);
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $department_id = intval($_POST['department_id']);
        $class_id = intval($_POST['class_id']);
        $year = intval($_POST['year']);
        $semester = intval($_POST['semester']);
        $admission_year = sanitize($_POST['admission_year']);
        
        $stmt = $conn->prepare("INSERT INTO students (roll_number, full_name, email, phone, password, department_id, class_id, year, semester, admission_year) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssiiiis", $roll_number, $full_name, $email, $phone, $password, $department_id, $class_id, $year, $semester, $admission_year);
        
        if ($stmt->execute()) {
            $success = "Student added successfully!";
        } else {
            $error = "Error adding student: " . $conn->error;
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $student_id = intval($_POST['student_id']);
        $new_status = intval($_POST['new_status']);
        
        $conn->query("UPDATE students SET is_active = $new_status WHERE id = $student_id");
        $success = "Student status updated!";
    }
}

// Get all students
$students_query = "SELECT s.*, d.dept_name, c.class_name
                   FROM students s
                   LEFT JOIN departments d ON s.department_id = d.id
                   LEFT JOIN classes c ON s.class_id = c.id
                   ORDER BY s.roll_number";
$students = $conn->query($students_query);

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");

// Get classes
$classes = $conn->query("SELECT * FROM classes ORDER BY class_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT College - Manage Students</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="table-container" style="margin-bottom: 30px;">
            <h3>➕ Add New Student</h3>
            <form method="POST" style="max-width: 900px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Roll Number:</label>
                    <input type="text" name="roll_number" required placeholder="e.g., CSE2023001">
                </div>
                
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone" required>
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Department:</label>
                    <select name="department_id" required>
                        <option value="">-- Select Department --</option>
                        <?php while ($dept = $departments->fetch_assoc()): ?>
                            <option value="<?php echo $dept['id']; ?>">
                                <?php echo htmlspecialchars($dept['dept_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Class:</label>
                    <select name="class_id" required>
                        <option value="">-- Select Class --</option>
                        <?php while ($class = $classes->fetch_assoc()): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Year:</label>
                    <select name="year" required>
                        <option value="">-- Select --</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Semester:</label>
                    <select name="semester" required>
                        <option value="">-- Select --</option>
                        <?php for($i=1; $i<=8; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Admission Year:</label>
                    <input type="text" name="admission_year" required placeholder="e.g., 2023">
                </div>
                
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="add_student" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h3>👨‍🎓 All Students</h3>
            <table>
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Class</th>
                        <th>Year</th>
                        <th>Semester</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $students->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['phone']); ?></td>
                        <td><?php echo htmlspecialchars($student['dept_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                        <td><?php echo $student['year']; ?></td>
                        <td><?php echo $student['semester']; ?></td>
                        <td>
                            <?php if ($student['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $student['is_active'] ? 0 : 1; ?>">
                                <button type="submit" name="toggle_status" class="btn btn-warning btn-sm">
                                    <?php echo $student['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>