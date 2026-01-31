<?php
// teacher/set_class_session.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$class_id = $_GET['class_id'] ?? 0;

// Verify this class belongs to this teacher
$stmt = $pdo->prepare("SELECT class_id FROM classes WHERE class_id = ? AND teacher_id = ?");
$stmt->execute([$class_id, $_SESSION['user_id']]);

if ($stmt->fetch()) {
    $_SESSION['active_class_id'] = $class_id;
    header("Location: curriculum_view.php");
} else {
    header("Location: dashboard.php");
}
exit;
