<?php
require_once '../db.php';
checkRole(['teacher']);

$user = getCurrentUser();

// Get all classes assigned to this teacher (grouped by section)
$classes_query = "SELECT c.id, c.class_name, c.section, c.year, c.semester, c.academic_year,
                  d.dept_name, d.id as dept_id,
                  COUNT(DISTINCT s.id) as student_count
                  FROM classes c
                  JOIN departments d ON c.department_id = d.id
                  LEFT JOIN students s ON (s.class_id = c.id OR 
                                          (c.section = 'Civil' AND s.class_id IN (SELECT id FROM classes WHERE section = 'Civil')) OR
                                          (c.section = 'IT' AND s.class_id IN (SELECT id FROM classes WHERE section = 'IT')) OR
                                          (c.section = 'Mechanical' AND s.class_id IN (SELECT id FROM classes WHERE section = 'Mechanical')) OR
                                          (c.section = 'Electrical' AND s.class_id IN (SELECT id FROM classes WHERE section = 'Electrical')) OR
                                          (c.section = 'CSE-A' AND s.class_id IN (SELECT id FROM classes WHERE section = 'CSE-A')) OR
                                          (c.section = 'CSE-B' AND s.class_id IN (SELECT id FROM classes WHERE section = 'CSE-B'))
                                          ) AND s.is_active = 1
                  WHERE c.teacher_id = ?
                  GROUP BY c.id, c.class_name, c.section, c.year, c.semester, c.academic_year, d.dept_name, d.id
                  ORDER BY c.section, c.year, c.semester";

$stmt = $conn->prepare($classes_query);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$classes = $stmt->get_result();

// Get today's attendance stats
$today = date('Y-m-d');
$stats_query = "SELECT 
                COUNT(DISTINCT sa.student_id) as marked_today,
                SUM(CASE WHEN sa.status = 'present' THEN 1 ELSE 0 END) as present_today,
                SUM(CASE WHEN sa.status = 'absent' THEN 1 ELSE 0 END) as absent_today
                FROM student_attendance sa
                WHERE sa.marked_by = ? AND sa.attendance_date = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("is", $user['id'], $today);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - NIT College</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="../assets/teacher.css">
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT College - Teacher Portal</h1>
        </div>
        <div class="user-info">
            <span>👨‍🏫 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <h2>📊 Today's Summary - <?php echo date('d M Y'); ?></h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>📝 ATTENDANCE MARKED TODAY</h3>
                <div class="stat-value"><?php echo $stats['marked_today'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>✅ PRESENT</h3>
                <div class="stat-value" style="color: #28a745;"><?php echo $stats['present_today'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>❌ ABSENT</h3>
                <div class="stat-value" style="color: #dc3545;"><?php echo $stats['absent_today'] ?? 0; ?></div>
            </div>
        </div>

        <div class="table-container">
            <h3>📚 Select Class to Mark Attendance</h3>
            
            <?php if ($classes->num_rows > 0): ?>
                <div class="class-selection-grid">
                    <?php while ($class = $classes->fetch_assoc()): ?>
                        <div class="class-card">
                            <h3><?php echo htmlspecialchars($class['section']); ?></h3>
                            <div class="class-info">
                                <div class="info-item">
                                    <span>📖 Class:</span>
                                    <strong><?php echo htmlspecialchars($class['class_name']); ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>🏢 Department:</span>
                                    <strong><?php echo htmlspecialchars($class['dept_name']); ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>📅 Year:</span>
                                    <strong><?php echo $class['year']; ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>📆 Semester:</span>
                                    <strong><?php echo $class['semester']; ?></strong>
                                </div>
                                <div class="info-item">
                                    <span>👥 Students:</span>
                                    <strong><?php echo $class['student_count']; ?></strong>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <a href="mark_attendance.php?class_id=<?php echo $class['id']; ?>&section=<?php echo urlencode($class['section']); ?>" 
                                   class="btn btn-primary" style="flex: 1;">
                                    📝 Mark Attendance
                                </a>
                                <a href="view_attendance.php?class_id=<?php echo $class['id']; ?>&section=<?php echo urlencode($class['section']); ?>" 
                                   class="btn btn-info" style="flex: 1;">
                                    📊 View Reports
                                </a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    ⚠️ No classes assigned to you yet. Please contact the administrator.
                </div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <h3>ℹ️ Instructions</h3>
            <div style="background: #e3f2fd; padding: 20px; border-radius: 10px;">
                <ul style="list-style-position: inside; line-height: 2;">
                    <li>Select a class/section to mark attendance for today</li>
                    <li>You can see students from all sections you teach</li>
                    <li>Each section card shows the number of enrolled students</li>
                    <li>Mark attendance before the end of the day</li>
                    <li>You can view and edit attendance reports anytime</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>