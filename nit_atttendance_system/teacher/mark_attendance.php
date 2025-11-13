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
$class_query = "SELECT c.*, d.dept_name FROM classes c 
                LEFT JOIN departments d ON c.department_id = d.id
                WHERE c.id = $class_id AND c.teacher_id = $teacher_id";
$class_result = $conn->query($class_query);

if ($class_result->num_rows === 0) {
    header("Location: index.php?error=unauthorized");
    exit();
}

$class = $class_result->fetch_assoc();
$today = date('Y-m-d');

// Check if attendance already marked today
$check_query = "SELECT COUNT(*) as count FROM student_attendance 
                WHERE class_id = $class_id AND attendance_date = '$today'";
$check_result = $conn->query($check_query);
$already_marked = $check_result->fetch_assoc()['count'] > 0;

// Get students in this class
$students_query = "SELECT * FROM students 
                   WHERE class_id = $class_id AND is_active = 1 
                   ORDER BY roll_number";
$students = $conn->query($students_query);

// If already marked, get existing attendance
$existing_attendance = [];
if ($already_marked) {
    $existing_query = "SELECT student_id, status, remarks FROM student_attendance 
                       WHERE class_id = $class_id AND attendance_date = '$today'";
    $existing_result = $conn->query($existing_query);
    while ($row = $existing_result->fetch_assoc()) {
        $existing_attendance[$row['student_id']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - Teacher</title>
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .attendance-form {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .student-row {
            display: grid;
            grid-template-columns: 100px 1fr 150px 150px 150px 200px;
            gap: 15px;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            background: white;
        }
        
        .student-row:hover {
            background: #f8f9fa;
        }
        
        .student-row label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .student-row input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .student-row input[type="text"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .present-label:has(input:checked) {
            background: #d4edda;
            color: #155724;
        }
        
        .absent-label:has(input:checked) {
            background: #f8d7da;
            color: #721c24;
        }
        
        .late-label:has(input:checked) {
            background: #fff3cd;
            color: #856404;
        }
        
        .header-row {
            display: grid;
            grid-template-columns: 100px 1fr 150px 150px 150px 200px;
            gap: 15px;
            padding: 15px;
            background: #667eea;
            color: white;
            font-weight: bold;
            border-radius: 10px 10px 0 0;
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="dashboard-container">
    <nav class="navbar">
        <div>
            <h1>🎓 NIT College - Mark Attendance</h1>
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
            <p><strong>Department:</strong> <?php echo htmlspecialchars($class['dept_name']); ?></p>
            <p><strong>Date:</strong> <?php echo date('l, d F Y'); ?></p>
            
            <?php if ($already_marked): ?>
                <div class="alert alert-warning">
                    ⚠️ Attendance already marked for today. You can update it below.
                </div>
            <?php endif; ?>
        </div>

        <div class="quick-actions">
            <button type="button" onclick="markAll('present')" class="btn btn-success">✅ Mark All Present</button>
            <button type="button" onclick="markAll('absent')" class="btn btn-danger">❌ Mark All Absent</button>
        </div>

        <form method="POST" action="save_attendance.php" class="attendance-form" onsubmit="return confirm('Are you sure you want to save this attendance?');">
            <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
            <input type="hidden" name="attendance_date" value="<?php echo $today; ?>" id="attendance_date">
            
            <div class="table-container" style="padding: 0;">
                <div class="header-row">
                    <div>Roll No</div>
                    <div>Student Name</div>
                    <div>Present</div>
                    <div>Absent</div>
                    <div>Late</div>
                    <div>Remarks</div>
                </div>
                
                <?php while ($student = $students->fetch_assoc()): 
                    $student_id = $student['id'];
                    $existing_status = $existing_attendance[$student_id]['status'] ?? 'present';
                    $existing_remarks = $existing_attendance[$student_id]['remarks'] ?? '';
                ?>
                <div class="student-row">
                    <div><?php echo htmlspecialchars($student['roll_number']); ?></div>
                    <div><?php echo htmlspecialchars($student['full_name']); ?></div>
                    
                    <div>
                        <label class="present-label">
                            <input type="radio" 
                                   name="attendance[<?php echo $student_id; ?>]" 
                                   value="present" 
                                   <?php echo $existing_status === 'present' ? 'checked' : ''; ?>>
                            Present
                        </label>
                    </div>
                    
                    <div>
                        <label class="absent-label">
                            <input type="radio" 
                                   name="attendance[<?php echo $student_id; ?>]" 
                                   value="absent"
                                   <?php echo $existing_status === 'absent' ? 'checked' : ''; ?>>
                            Absent
                        </label>
                    </div>
                    
                    <div>
                        <label class="late-label">
                            <input type="radio" 
                                   name="attendance[<?php echo $student_id; ?>]" 
                                   value="late"
                                   <?php echo $existing_status === 'late' ? 'checked' : ''; ?>>
                            Late
                        </label>
                    </div>
                    
                    <div>
                        <input type="text" 
                               name="remarks[<?php echo $student_id; ?>]" 
                               placeholder="Optional remarks"
                               value="<?php echo htmlspecialchars($existing_remarks); ?>">
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <div style="margin-top: 20px; text-align: center;">
                <button type="submit" class="btn btn-primary" style="padding: 15px 50px; font-size: 16px;">
                    💾 Save Attendance
                </button>
            </div>
        </form>
    </div>

    <script>
    function markAll(status) {
        const radios = document.querySelectorAll(`input[type="radio"][value="${status}"]`);
        radios.forEach(radio => {
            radio.checked = true;
        });
    }
    </script>
</body>
</html>