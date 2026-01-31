<?php
// teacher/create_live_session.php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_POST['create_session'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$teacher_id = $_SESSION['user_id'];
$schedule_id = $_POST['schedule_id'];
$class_id = $_POST['class_id'];
$meeting_link = $_POST['meeting_link'];
$session_notes = $_POST['session_notes'] ?? '';

try {
    // Verify teacher owns this class
    $verify = $pdo->prepare("SELECT class_id FROM classes WHERE class_id = ? AND teacher_id = ?");
    $verify->execute([$class_id, $teacher_id]);
    
    if (!$verify->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Check if there's already an active session for this class
    $check = $pdo->prepare("SELECT session_id FROM live_sessions WHERE class_id = ? AND status = 'active'");
    $check->execute([$class_id]);
    
    if ($existing = $check->fetch()) {
        // End the existing session first
        $end = $pdo->prepare("UPDATE live_sessions SET status = 'ended', ended_at = NOW() WHERE session_id = ?");
        $end->execute([$existing['session_id']]);
    }
    
    // Create new live session with meeting link
    $insert = $pdo->prepare("
        INSERT INTO live_sessions (class_id, schedule_id, teacher_id, meeting_link, status, started_at) 
        VALUES (?, ?, ?, ?, 'active', NOW())
    ");
    $insert->execute([$class_id, $schedule_id, $teacher_id, $meeting_link]);
    
    $session_id = $pdo->lastInsertId();
    
    // Update class is_live status
    $update = $pdo->prepare("UPDATE classes SET is_live = 1 WHERE class_id = ?");
    $update->execute([$class_id]);
    
    echo json_encode([
        'success' => true,
        'session_id' => $session_id,
        'message' => 'Session created successfully!'
    ]);
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
