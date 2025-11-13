<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $_SESSION['user_id'];

if (!isset($_GET['class_id'])) {
    header("Location: index.php");
    exit();
}

$class_id = intval($_GET['class_id']);

// Verify this class belongs to the teacher
$class_query = "SELECT c.*, d.dept_name, s.subject_name, s.subject_code
                FROM classes c 
                LEFT JOIN departments d ON c.department_id = d.id
                LEFT JOIN subjects s ON c.subject_id = s.id
                WHERE c.id = $class_id AND c.teacher_id = $teacher_id";
$class_result = $conn->query($class_query);

if ($class_result->num_rows === 0) {
    header("Location: index.php?error=unauthorized");
    exit();
}

$class = $class_result->fetch_assoc();

// Get filter parameters
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filter_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// Get attendance records for selected date
$attendance_query = "SELECT sa.*, s.roll_number, s.full_name as student_name
                     FROM student_attendance sa
                     JOIN students s ON sa.student_id = s.id
                     WHERE sa.class_id = $class_id AND sa.attendance_date = '$filter_date'";

if ($class['subject_id']) {
    $attendance_query .= " AND sa.subject_id = {$class['subject_id']}";
}

$attendance_query .= " ORDER BY s.roll_number";
$attendance_records = $conn->query($attendance_query);

// Get monthly summary
$monthly_query = "SELECT 
                  s.id, s.roll_number, s.full_name,
                  COUNT(sa.id) as total_days,
                  SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present,
                  SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent,
                  SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late
                  FROM students s
                  LEFT JOIN student_attendance sa ON s.id = sa.student_id 
                  AND sa.class_id = $class_id 
                  AND DATE_FORMAT(sa.attendance_date, '%Y-%m') = '$filter_month'";

if ($class['subject_id']) {
    $monthly_query .= " AND sa.subject_id = {$class['subject_id']}";
}

$monthly_query .= " WHERE s.class_id = $class_id AND s.is_active = 1
                    GROUP BY s.id
                    ORDER BY s.roll_number";
$monthly_summary = $conn->query($monthly_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Attendance - Teacher</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div>
            <h1>🎓 NIT College - View Attendance</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div style="background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h2><?php echo htmlspecialchars($class['class_name']); ?></h2>
            <p><strong>Subject:</strong> <?php echo htmlspecialchars($class['subject_name']); ?> (<?php echo htmlspecialchars($class['subject_code']); ?>)</p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($class['dept_name']); ?></p>
        </div>

        <div class="table-container" style="margin-bottom: 30px;">
            <h3>📅 View Daily Attendance</h3>
            <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                
                <div class="form-group">
                    <label>Select Date:</label>
                    <input type="date" name="date" value="<?php echo $filter_date; ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">View</button>
            </form>
        </div>

        <?php if ($attendance_records->num_rows > 0): ?>
            <div class="table-container" style="margin-bottom: 30px;">
                <h3>📝 Attendance for <?php echo date('d M Y', strtotime($filter_date)); ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th>Roll Number</th>
                            <th>Student Name</th>
                            <th>Status</th>
                            <th>Remarks</th>
                            <th>Marked At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($record = $attendance_records->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($record['student_name']); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                if ($record['status'] === 'present') $status_class = 'badge-success';
                                elseif ($record['status'] === 'absent') $status_class = 'badge-danger';
                                else $status_class = 'badge-warning';
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo strtoupper($record['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($record['remarks'] ?? '-'); ?></td>
                            <td><?php echo date('H:i', strtotime($record['marked_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No attendance records found for <?php echo date('d M Y', strtotime($filter_date)); ?></div>
        <?php endif; ?>

        <div class="table-container">
            <h3>📊 Monthly Attendance Summary (<?php echo htmlspecialchars($class['subject_name']); ?>)</h3>
            <form method="GET" style="display: flex; gap: 15px; align-items: flex-end; margin-bottom: 20px;">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                
                <div class="form-group">
                    <label>Select Month:</label>
                    <input type="month" name="month" value="<?php echo $filter_month; ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">View Summary</button>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Roll Number</th>
                        <th>Student Name</th>
                        <th>Total Days</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($summary = $monthly_summary->fetch_assoc()): 
                        $total = $summary['total_days'];
                        $percentage = $total > 0 ? round(($summary['present'] / $total) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($summary['roll_number']); ?></td>
                        <td><?php echo htmlspecialchars($summary['full_name']); ?></td>
                        <td><?php echo $total; ?></td>
                        <td><span class="badge badge-success"><?php echo $summary['present']; ?></span></td>
                        <td><span class="badge badge-danger"><?php echo $summary['absent']; ?></span></td>
                        <td><span class="badge badge-warning"><?php echo $summary['late']; ?></span></td>
                        <td>
                            <strong style="color: <?php echo $percentage >= 75 ? '#28a745' : '#dc3545'; ?>">
                                <?php echo $percentage; ?>%
                            </strong>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>