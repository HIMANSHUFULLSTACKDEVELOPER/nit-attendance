<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();
$teacher_id = $_SESSION['user_id'];

// Get teacher's classes
$classes_query = "SELECT c.*, d.dept_name,
                  (SELECT COUNT(*) FROM students WHERE class_id = c.id AND is_active = 1) as student_count
                  FROM classes c
                  LEFT JOIN departments d ON c.department_id = d.id
                  WHERE c.teacher_id = $teacher_id
                  ORDER BY c.class_name";
$classes = $conn->query($classes_query);

// Get today's attendance stats
$today = date('Y-m-d');
$today_stats_query = "SELECT 
                      COUNT(DISTINCT sa.student_id) as marked_students,
                      SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present,
                      SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent,
                      SUM(CASE WHEN sa.status = 'late' THEN 1 ELSE 0 END) as late
                      FROM student_attendance sa
                      JOIN classes c ON sa.class_id = c.id
                      WHERE c.teacher_id = $teacher_id AND sa.attendance_date = '$today'";
$today_stats_result = $conn->query($today_stats_query);
$today_stats = $today_stats_result->fetch_assoc();

// Get recent activities
$recent_query = "SELECT sa.*, s.full_name as student_name, s.roll_number, c.class_name
                 FROM student_attendance sa
                 JOIN students s ON sa.student_id = s.id
                 JOIN classes c ON sa.class_id = c.id
                 WHERE sa.marked_by = $teacher_id
                 ORDER BY sa.marked_at DESC LIMIT 10";
$recent_activities = $conn->query($recent_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - NIT College</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT College - Teacher Panel</h1>
        </div>
        <div class="user-info">
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <?php if (isset($_GET['success']) && $_GET['success'] === 'attendance_saved'): ?>
            <div class="alert alert-success">
                ✅ Attendance saved successfully! 
                <?php if (isset($_GET['count'])): ?>
                    (<?php echo intval($_GET['count']); ?> students marked)
                <?php endif; ?>
                <?php if (isset($_GET['date'])): ?>
                    for date: <?php echo date('d M Y', strtotime($_GET['date'])); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php 
                    if ($_GET['error'] === 'save_failed') {
                        echo "❌ Failed to save attendance. Please try again.";
                        if (isset($_GET['message'])) {
                            echo "<br>Error: " . htmlspecialchars($_GET['message']);
                        }
                    } elseif ($_GET['error'] === 'no_data') {
                        echo "❌ No attendance data to save!";
                    } elseif ($_GET['error'] === 'invalid_date') {
                        echo "❌ Invalid date format!";
                    }
                ?>
            </div>
        <?php endif; ?>
        
        <h2>📊 Dashboard Overview</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📚 My Classes</h3>
                <div class="stat-value"><?php echo $classes->num_rows; ?></div>
                <a href="select_class.php" class="btn btn-info btn-sm">View Classes</a>
            </div>
            
            <div class="stat-card">
                <h3>📝 Today's Marked</h3>
                <div class="stat-value"><?php echo $today_stats['marked_students'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>✅ Present Today</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $today_stats['present'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>❌ Absent Today</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $today_stats['absent'] ?? 0; ?></div>
            </div>
        </div>

        <div class="table-container" style="margin-bottom: 30px;">
            <h3>📚 My Classes</h3>
            <table>
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>Section</th>
                        <th>Semester</th>
                        <th>Students</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $classes->data_seek(0);
                    while ($class = $classes->fetch_assoc()): 
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                        <td><?php echo htmlspecialchars($class['dept_name']); ?></td>
                        <td><?php echo $class['year']; ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($class['section']); ?></span></td>
                        <td><?php echo $class['semester']; ?></td>
                        <td><span class="badge badge-success"><?php echo $class['student_count']; ?></span></td>
                        <td>
                            <a href="mark_attendance.php?class_id=<?php echo $class['id']; ?>" class="btn btn-primary btn-sm">
                                Mark Attendance
                            </a>
                            <a href="view_attendance.php?class_id=<?php echo $class['id']; ?>" class="btn btn-info btn-sm">
                                View History
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="table-container">
            <h3>🕒 Recent Attendance Activities</h3>
            <table>
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Student</th>
                        <th>Roll Number</th>
                        <th>Class</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent_activities->num_rows > 0): ?>
                        <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('d M Y H:i', strtotime($activity['marked_at'])); ?></td>
                            <td><?php echo htmlspecialchars($activity['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($activity['roll_number']); ?></td>
                            <td><?php echo htmlspecialchars($activity['class_name']); ?></td>
                            <td>
                                <?php
                                $status_class = '';
                                if ($activity['status'] === 'present') $status_class = 'badge-success';
                                elseif ($activity['status'] === 'absent') $status_class = 'badge-danger';
                                else $status_class = 'badge-warning';
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo strtoupper($activity['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">No recent activities</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>