<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

// Verify teacher has access to this class
$verify_query = "SELECT c.*, d.dept_name FROM classes c 
                 JOIN departments d ON c.department_id = d.id
                 WHERE c.id = ? AND c.teacher_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $class_id, $user['id']);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();

if (!$class) {
    header("Location: index.php");
    exit();
}

// Get filter parameters
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Get attendance records for this class only
$attendance_query = "SELECT sa.*, s.roll_number, s.full_name as student_name
                     FROM student_attendance sa
                     JOIN students s ON sa.student_id = s.id
                     WHERE sa.class_id = ? 
                     AND sa.attendance_date BETWEEN ? AND ?
                     ORDER BY sa.attendance_date DESC, s.roll_number";

$stmt = $conn->prepare($attendance_query);
$stmt->bind_param("iss", $class_id, $filter_date_from, $filter_date_to);
$stmt->execute();
$attendance_records = $stmt->get_result();

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
                FROM student_attendance
                WHERE class_id = ? 
                AND attendance_date BETWEEN ? AND ?";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iss", $class_id, $filter_date_from, $filter_date_to);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get student-wise attendance summary
$summary_query = "SELECT s.roll_number, s.full_name,
                  COUNT(sa.id) as total_days,
                  SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_days,
                  SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                  SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late_days
                  FROM students s
                  LEFT JOIN student_attendance sa ON s.id = sa.student_id 
                      AND sa.class_id = ? 
                      AND sa.attendance_date BETWEEN ? AND ?
                  WHERE s.class_id = ? AND s.is_active = 1
                  GROUP BY s.id, s.roll_number, s.full_name
                  ORDER BY s.roll_number";

$stmt = $conn->prepare($summary_query);
$stmt->bind_param("issi", $class_id, $filter_date_from, $filter_date_to, $class_id);
$stmt->execute();
$student_summary = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - <?php echo htmlspecialchars($class['section']); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/teacher.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 Attendance Reports - <?php echo htmlspecialchars($class['section']); ?></h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back</a>
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="summary-card">
            <h2>📚 <?php echo htmlspecialchars($class['class_name']); ?></h2>
            <div class="summary-stats">
                <div class="summary-stat">
                    <div class="label">Section</div>
                    <div class="number"><?php echo htmlspecialchars($class['section']); ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">Department</div>
                    <div class="number"><?php echo htmlspecialchars($class['dept_name']); ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">Year</div>
                    <div class="number"><?php echo $class['year']; ?></div>
                </div>
                <div class="summary-stat">
                    <div class="label">Semester</div>
                    <div class="number"><?php echo $class['semester']; ?></div>
                </div>
            </div>
        </div>

        <div class="table-container">
            <h3>🔍 Filter Attendance</h3>
            <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                
                <div class="form-group">
                    <label>From Date:</label>
                    <input type="date" name="date_from" value="<?php echo $filter_date_from; ?>">
                </div>
                
                <div class="form-group">
                    <label>To Date:</label>
                    <input type="date" name="date_to" value="<?php echo $filter_date_to; ?>">
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 Total Records</h3>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>✅ Present</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $stats['present']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>❌ Absent</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $stats['absent']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>⏰ Late</h3>
                <div class="stat-value" style="color: #ffc107;"><?php echo $stats['late']; ?></div>
            </div>
        </div>

        <div class="table-container">
            <h3>👥 Student-wise Attendance Summary</h3>
            <p style="margin-bottom: 15px; color: #666;">
                Showing attendance for students in section: <strong><?php echo htmlspecialchars($class['section']); ?></strong>
            </p>
            
            <?php if ($student_summary->num_rows > 0): ?>
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
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $student_summary->fetch_assoc()): 
                            $percentage = $student['total_days'] > 0 
                                ? round(($student['present_days'] / $student['total_days']) * 100, 2) 
                                : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                            <td><?php echo $student['total_days']; ?></td>
                            <td><span class="badge badge-success"><?php echo $student['present_days']; ?></span></td>
                            <td><span class="badge badge-danger"><?php echo $student['absent_days']; ?></span></td>
                            <td><span class="badge badge-warning"><?php echo $student['late_days']; ?></span></td>
                            <td><strong><?php echo $percentage; ?>%</strong></td>
                            <td>
                                <?php if ($percentage >= 75): ?>
                                    <span class="badge badge-success">Good</span>
                                <?php elseif ($percentage >= 60): ?>
                                    <span class="badge badge-warning">Average</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Low ⚠️</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-info">
                    ℹ️ No students found in this section. Please contact the administrator.
                </div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <h3>📋 Detailed Attendance Records</h3>
            
            <?php if ($attendance_records->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
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
                            <td><?php echo date('d M Y', strtotime($record['attendance_date'])); ?></td>
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
            <?php else: ?>
                <div class="alert alert-info">No attendance records found for the selected date range.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>