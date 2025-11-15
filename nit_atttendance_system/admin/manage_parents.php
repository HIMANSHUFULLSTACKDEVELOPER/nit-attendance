<?php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_parent'])) {
        $parent_name = sanitize($_POST['parent_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $student_id = intval($_POST['student_id']);
        $relationship = sanitize($_POST['relationship']);
        
        $stmt = $conn->prepare("INSERT INTO parents (parent_name, email, phone, password, student_id, relationship) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $parent_name, $email, $phone, $password, $student_id, $relationship);
        
        if ($stmt->execute()) {
            $success = "Parent added successfully!";
        } else {
            $error = "Error adding parent: " . $conn->error;
        }
    }
    
    if (isset($_POST['delete_parent'])) {
        $parent_id = intval($_POST['parent_id']);
        
        if ($conn->query("DELETE FROM parents WHERE id = $parent_id")) {
            $success = "Parent deleted successfully!";
        } else {
            $error = "Error deleting parent: " . $conn->error;
        }
    }
}

// Get all parents
$parents_query = "SELECT p.*, s.roll_number, s.full_name as student_name, d.dept_name
                  FROM parents p
                  JOIN students s ON p.student_id = s.id
                  LEFT JOIN departments d ON s.department_id = d.id
                  ORDER BY p.parent_name";
$parents = $conn->query($parents_query);

// Get students for dropdown
$students = $conn->query("SELECT id, roll_number, full_name FROM students WHERE is_active = 1 ORDER BY roll_number");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Parents - Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1> NIT College - Manage Parents</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary"> Back</a>
            <span><?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger"> Logout</a>
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
            <h3> Add New Parent</h3>
            <form method="POST" style="max-width: 800px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label>Parent Name:</label>
                    <input type="text" name="parent_name" required>
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
                    <label>Student:</label>
                    <select name="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php while ($student = $students->fetch_assoc()): ?>
                            <option value="<?php echo $student['id']; ?>">
                                <?php echo htmlspecialchars($student['roll_number'] . ' - ' . $student['full_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Relationship:</label>
                    <select name="relationship" required>
                        <option value="">-- Select --</option>
                        <option value="father">Father</option>
                        <option value="mother">Mother</option>
                        <option value="guardian">Guardian</option>
                    </select>
                </div>
                
                <div style="grid-column: 1 / -1;">
                    <button type="submit" name="add_parent" class="btn btn-primary">Add Parent</button>
                </div>
            </form>
        </div>

        <div class="table-container">
            <h3> All Parents</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Parent Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Student</th>
                        <th>Roll Number</th>
                        <th>Department</th>
                        <th>Relationship</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($parent = $parents->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $parent['id']; ?></td>
                        <td><?php echo htmlspecialchars($parent['parent_name']); ?></td>
                        <td><?php echo htmlspecialchars($parent['email']); ?></td>
                        <td><?php echo htmlspecialchars($parent['phone']); ?></td>
                        <td><?php echo htmlspecialchars($parent['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($parent['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($parent['dept_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo ucfirst($parent['relationship']); ?></span></td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this parent?');">
                                <input type="hidden" name="parent_id" value="<?php echo $parent['id']; ?>">
                                <button type="submit" name="delete_parent" class="btn btn-danger btn-sm">Delete</button>
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