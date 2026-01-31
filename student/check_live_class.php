<?php
// student/check_live_class.php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['is_live' => false, 'error' => 'Unauthorized']);
    exit;
}

$student_id = $_SESSION['user_id'];

try {
    // Check if any of the student's classes are currently live
    $stmt = $pdo->prepare("
        SELECT 
            ls.session_id,
            ls.meeting_link,
            c.class_name,
            c.class_id,
            s.name as subject_name,
            ls.started_at
        FROM live_sessions ls
        JOIN classes c ON ls.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        JOIN enrollments e ON c.class_id = e.class_id
        WHERE e.student_id = ? AND ls.status = 'active'
        ORDER BY ls.started_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$student_id]);
    $live_class = $stmt->fetch();
    
    if ($live_class) {
        echo json_encode([
            'is_live' => true,
            'session_id' => $live_class['session_id'],
            'class_id' => $live_class['class_id'],
            'class_name' => $live_class['class_name'],
            'subject_name' => $live_class['subject_name'],
            'meeting_link' => $live_class['meeting_link'],
            'started_at' => $live_class['started_at']
        ]);
    } else {
        echo json_encode(['is_live' => false]);
    }
    
} catch(Exception $e) {
    echo json_encode(['is_live' => false, 'error' => $e->getMessage()]);
}
