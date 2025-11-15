<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_teacher'])) {
        $username = sanitize($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $department_id = intval($_POST['department_id']);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role, department_id) VALUES (?, ?, ?, ?, ?, 'teacher', ?)");
        $stmt->bind_param("sssssi", $username, $password, $full_name, $email, $phone, $department_id);
        
        if ($stmt->execute()) {
            $success = "Teacher added successfully!";
        } else {
            $error = "Error adding teacher: " . $conn->error;
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $teacher_id = intval($_POST['teacher_id']);
        $new_status = intval($_POST['new_status']);
        
        $conn->query("UPDATE users SET is_active = $new_status WHERE id = $teacher_id");
        $success = "Teacher status updated!";
    }
}

// Get all teachers
$teachers_query = "SELECT u.*, d.dept_name,
                   (SELECT COUNT(*) FROM classes WHERE teacher_id = u.id) as class_count
                   FROM users u
                   LEFT JOIN departments d ON u.department_id = d.id
                   WHERE u.role = 'teacher'
                   ORDER BY u.full_name";
$teachers = $conn->query($teachers_query);

// Get departments
$departments = $conn->query("SELECT * FROM departments ORDER BY dept_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Teachers - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>ðŸŽ“ NIT College - Manage Teachers</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">â† Back</a>
            <span>ðŸ‘¨â€ðŸ’¼ <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">ðŸšª Logout</a>
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
            <h3>âž• Add New Teacher</h3>
            <form method="POST" style="max-width: 800px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
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
                
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="add_teacher" class="btn btn-primary">Add Teacher</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h3>ðŸ‘¨â€ðŸ« All Teachers</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Department</th>
                        <th>Classes</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($teacher = $teachers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $teacher['id']; ?></td>
                        <td><?php echo htmlspecialchars($teacher['username']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['phone']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['dept_name'] ?? 'Not Assigned'); ?></td>
                        <td><span class="badge badge-info"><?php echo $teacher['class_count']; ?></span></td>
                        <td>
                            <?php if ($teacher['is_active']): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="teacher_id" value="<?php echo $teacher['id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $teacher['is_active'] ? 0 : 1; ?>">
                                <button type="submit" name="toggle_status" class="btn btn-warning btn-sm">
                                    <?php echo $teacher['is_active'] ? 'Deactivate' : 'Activate'; ?>
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