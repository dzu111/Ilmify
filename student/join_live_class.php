<?php
// student/join_live_class.php
session_start();
require_once '../config/db.php';

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? 0;

// Fetch session details and verify student enrollment
$stmt = $pdo->prepare("
    SELECT 
        ls.session_id,
        ls.meeting_link,
        c.class_name,
        c.class_id,
        s.name as subject_name
    FROM live_sessions ls
    JOIN classes c ON ls.class_id = c.class_id
    JOIN subjects s ON c.subject_id = s.subject_id
    JOIN enrollments e ON c.class_id = e.class_id
    WHERE ls.session_id = ? AND e.student_id = ? AND ls.status = 'active'
");

$stmt->execute([$session_id, $student_id]);
$session = $stmt->fetch();

if (!$session) {
    header("Location: dashboard.php");
    exit;
}

// Log attendance
try {
    $attendance_check = $pdo->prepare("SELECT attendance_id FROM attendance WHERE session_id = ? AND student_id = ?");
    $attendance_check->execute([$session_id, $student_id]);
    
    if (!$attendance_check->fetch()) {
        // First time joining - log attendance
        $log = $pdo->prepare("INSERT INTO attendance (session_id, student_id, status) VALUES (?, ?, 'present')");
        $log->execute([$session_id, $student_id]);
    }
} catch(Exception $e) {
    // Continue even if attendance logging fails
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Live Class - Ilmify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Nunito', sans-serif; min-height: 100vh;">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg" style="border-radius: 25px; border: 4px solid #AB47BC;">
                <div class="card-body text-center p-5">
                    <h1 class="display-4 fw-bold mb-3">🎉 You're in Class!</h1>
                    <h3 class="mb-4"><?php echo htmlspecialchars($session['class_name']); ?></h3>
                    <p class="lead mb-4">
                        <span class="badge bg-success fs-5">🔴 LIVE NOW</span>
                    </p>
                    
                    <div class="alert alert-info rounded-3 mb-4">
                        <strong>📚 Subject:</strong> <?php echo htmlspecialchars($session['subject_name']); ?>
                    </div>
                    
                    <a 
                        href="focus_mode.php?session_id=<?php echo $session_id; ?>" 
                        class="btn btn-lg btn-success game-btn mb-3" 
                        id="joinMeetingBtn"
                        style="font-size: 1.5rem; padding: 20px 40px;">
                        🚀 JOIN CLASS NOW
                    </a>
                    
                    <p class="small text-muted">Meeting will open in a new tab automatically</p>
                    
                    <hr class="my-4">
                    
                    <div class="d-grid gap-2">
                        <a href="focus_mode.php?session_id=<?php echo $session_id; ?>" class="btn btn-primary">
                            📺 View Pushed Content
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            ← Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .game-btn {
        background: linear-gradient(135deg, #4CAF50, #45a049);
        color: white;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: 4px solid #388E3C;
        border-radius: 20px;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        box-shadow: 0 8px 15px rgba(76, 175, 80, 0.3);
    }
    
    .game-btn:hover {
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 15px 30px rgba(76, 175, 80, 0.5);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-redirect to focus mode after showing success message
setTimeout(function() {
    window.location.href = 'focus_mode.php?session_id=<?php echo $session_id; ?>';
}, 1500); // 1.5 seconds delay to show "You're in Class!" message
</script>
</body>
</html>
