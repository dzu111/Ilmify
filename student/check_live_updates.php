<?php
// student/check_live_updates.php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

$session_id = $_GET['session_id'] ?? 0;
$last_log_id = $_GET['last_log_id'] ?? 0;

// Check if session is still active
$check_session = $pdo->prepare("SELECT session_id FROM live_sessions WHERE session_id = ? AND ended_at IS NULL");
$check_session->execute([$session_id]);

if (!$check_session->fetch()) {
    echo json_encode(['status' => 'ended']);
    exit;
}

// Get latest pushed content
$sql = "SELECT pcl.log_id, pcl.content_type, pcl.content_id, pcl.pushed_at,
               CASE 
                   WHEN pcl.content_type = 'material' OR pcl.content_type = 'reading_session' THEN m.title 
                   WHEN pcl.content_type = 'quiz' THEN q.title 
                   WHEN pcl.content_type = 'video' THEN v.title
               END as title,
               CASE
                   WHEN pcl.content_type = 'video' THEN v.youtube_link
                   ELSE NULL
               END as youtube_link
        FROM pushed_content_log pcl
        LEFT JOIN materials m ON pcl.content_type IN ('material', 'reading_session') AND pcl.content_id = m.material_id
        LEFT JOIN quizzes q ON pcl.content_type = 'quiz' AND pcl.content_id = q.quiz_id
        LEFT JOIN videos v ON pcl.content_type = 'video' AND pcl.content_id = v.video_id
        WHERE pcl.session_id = ? AND pcl.log_id > ?
        ORDER BY pcl.pushed_at DESC 
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$session_id, $last_log_id]);
$latest = $stmt->fetch(PDO::FETCH_ASSOC);

if ($latest) {
    echo json_encode([
        'status' => 'new_content',
        'data' => $latest
    ]);
} else {
    echo json_encode(['status' => 'no_update']);
}
