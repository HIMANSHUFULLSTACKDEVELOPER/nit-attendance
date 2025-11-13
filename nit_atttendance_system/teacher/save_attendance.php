<?php
require_once '../db.php';
checkRole(['teacher']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];
$class_id = intval($_POST['class_id']);
$subject_id = isset($_POST['subject_id']) ? intval($_POST['subject_id']) : NULL;
$attendance_date = $_POST['attendance_date'];
$attendance_data = isset($_POST['attendance']) ? $_POST['attendance'] : [];
$remarks_data = isset($_POST['remarks']) ? $_POST['remarks'] : [];

// Verify this class belongs to the teacher
$verify_query = "SELECT id FROM classes WHERE id = $class_id AND teacher_id = $teacher_id";
$verify_result = $conn->query($verify_query);

if ($verify_result->num_rows === 0) {
    header("Location: index.php?error=unauthorized");
    exit();
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $attendance_date)) {
    header("Location: mark_attendance.php?class_id=$class_id&error=invalid_date");
    exit();
}

// Check if attendance data exists
if (empty($attendance_data)) {
    header("Location: mark_attendance.php?class_id=$class_id&date=$attendance_date&error=no_data");
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Delete existing attendance for this class, subject and date
    if ($subject_id) {
        $delete_stmt = $conn->prepare("DELETE FROM student_attendance WHERE class_id = ? AND subject_id = ? AND attendance_date = ?");
        $delete_stmt->bind_param("iis", $class_id, $subject_id, $attendance_date);
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM student_attendance WHERE class_id = ? AND attendance_date = ?");
        $delete_stmt->bind_param("is", $class_id, $attendance_date);
    }
    $delete_stmt->execute();
    
    // Insert new attendance records
    if ($subject_id) {
        $insert_stmt = $conn->prepare("INSERT INTO student_attendance (student_id, class_id, subject_id, attendance_date, status, marked_by, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
    } else {
        $insert_stmt = $conn->prepare("INSERT INTO student_attendance (student_id, class_id, attendance_date, status, marked_by, remarks) VALUES (?, ?, ?, ?, ?, ?)");
    }
    
    $success_count = 0;
    foreach ($attendance_data as $student_id => $status) {
        $student_id = intval($student_id);
        $status = sanitize($status);
        $remarks = isset($remarks_data[$student_id]) ? sanitize($remarks_data[$student_id]) : NULL;
        
        // Validate status
        if (!in_array($status, ['present', 'absent', 'late'])) {
            continue;
        }
        
        if ($subject_id) {
            $insert_stmt->bind_param("iiissis", $student_id, $class_id, $subject_id, $attendance_date, $status, $teacher_id, $remarks);
        } else {
            $insert_stmt->bind_param("iissis", $student_id, $class_id, $attendance_date, $status, $teacher_id, $remarks);
        }
        
        if ($insert_stmt->execute()) {
            $success_count++;
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    // Redirect with success message
    header("Location: index.php?success=attendance_saved&count=$success_count&date=$attendance_date");
    exit();
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    // Log error
    error_log("Attendance save error: " . $e->getMessage());
    
    header("Location: mark_attendance.php?class_id=$class_id&date=$attendance_date&error=save_failed&message=" . urlencode($e->getMessage()));
    exit();
}
?>