<?php
// teacher/live_class.php - Live Class Command Center
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$message = "";
$error = "";

// Get session_id from URL (new method)
$session_id = $_GET['session_id'] ?? 0;

// Get schedule_id from URL (legacy - no longer used)
$schedule_id = $_GET['schedule_id'] ?? 0;

// --- 1. HANDLE PUSH CONTENT (AJAX) ---
if (isset($_POST['push_content'])) {
    header('Content-Type: application/json');
    
    $session_id = $_POST['session_id'];
    $content_type = $_POST['content_type'];
    $content_id = $_POST['content_id'];
    
    try {
        // Log to pushed_content_log (students check this table)
        $stmt = $pdo->prepare("INSERT INTO pushed_content_log (session_id, content_type, content_id, pushed_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$session_id, $content_type, $content_id]);
        
        echo json_encode(['success' => true, 'message' => '🚀 Content pushed to student screens!']);
    } catch(Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// --- 2. HANDLE END SESSION ---
if (isset($_POST['end_session'])) {
    $session_id = $_POST['session_id'];
    $class_id = $_POST['class_id'];
    
    $pdo->prepare("UPDATE live_sessions SET status = 'ended', ended_at = NOW() WHERE session_id = ?")->execute([$session_id]);
    $pdo->prepare("UPDATE classes SET is_live = 0 WHERE class_id = ?")->execute([$class_id]);
    
    $message = "✅ Live session ended successfully!";
    header("Location: dashboard.php");
    exit;
}

// --- 3. FETCH SESSION DATA FROM session_id ---
$session_data = null;
$class_id = 0;
$current_week_id = 0;
$subject_id = 0;
$meeting_link = '';
$live_session_id = $session_id;

if ($session_id) {
    // Fetch session info with meeting link
    $stmt = $pdo->prepare("
        SELECT 
            ls.session_id,
            ls.class_id,
            ls.meeting_link,
            ls.started_at,
            c.class_name,
            c.current_week_id,
            c.subject_id,
            s.name as subject_name,
            w.title as week_title
        FROM live_sessions ls
        JOIN classes c ON ls.class_id = c.class_id
        JOIN subjects s ON c.subject_id = s.subject_id
        LEFT JOIN weeks w ON c.current_week_id = w.week_id
        WHERE ls.session_id = ? AND c.teacher_id = ? AND ls.status = 'active'
    ");
    $stmt->execute([$session_id, $teacher_id]);
    $session_data = $stmt->fetch();
    
    if ($session_data) {
        $class_id = $session_data['class_id'];
        $current_week_id = $session_data['current_week_id'] ?? 0;
        $subject_id = $session_data['subject_id'];
        $meeting_link = $session_data['meeting_link'];
        
        // Refresh week_id if needed
        if (!$current_week_id || $current_week_id == 0) {
            $refresh = $pdo->prepare("SELECT current_week_id FROM classes WHERE class_id = ?");
            $refresh->execute([$class_id]);
            $refresh_data = $refresh->fetch();
            $current_week_id = $refresh_data['current_week_id'] ?? 0;
        }
    } else {
        // Session not found or already ended
        header("Location: dashboard.php");
        exit;
    }
}

// Get active live session (verification)
$live_session = null;
if ($class_id) {
    $stmt = $pdo->prepare("SELECT * FROM live_sessions WHERE session_id = ? AND status = 'active'");
    $stmt->execute([$session_id]);
    $live_session = $stmt->fetch();
}

// --- 4. FETCH HYBRID CONTENT FOR CURRENT WEEK ---
$materials = [];
$videos = [];
$quizzes = [];

if ($live_session && $current_week_id) {
    // Simplified query - show ALL master + custom content for the week
    function getWeekContent($pdo, $table, $week_id, $class_id, $type_filter = null) {
        $type_query = $type_filter ? "AND type = '$type_filter'" : "";
        $id_col = ($table == 'videos') ? 'video_id' : (($table == 'quizzes') ? 'quiz_id' : 'material_id');

        $sql = "SELECT *, 
                CASE WHEN class_id IS NULL THEN 'global' ELSE 'custom' END as origin
                FROM $table 
                WHERE week_id = ? 
                $type_query
                AND (class_id IS NULL OR class_id = ?)
                ORDER BY created_at DESC";
                
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$week_id, $class_id]);
        return $stmt->fetchAll();
    }
    
    $materials_notes = getWeekContent($pdo, 'materials', $current_week_id, $class_id, 'note');
    $materials_reading = getWeekContent($pdo, 'materials', $current_week_id, $class_id, 'reading_session');
    $materials = array_merge($materials_notes, $materials_reading);
    $videos = getWeekContent($pdo, 'videos', $current_week_id, $class_id);
    $quizzes = getWeekContent($pdo, 'quizzes', $current_week_id, $class_id);
}

// Get active students (who are currently in the live session)
$active_students = [];
if ($live_session) {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.full_name, u.last_login_at
        FROM enrollments e
        JOIN users u ON e.student_id = u.user_id
        WHERE e.class_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$class_id]);
    $active_students = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🔴 Live Command Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            font-family: 'Nunito', sans-serif;
            color: white;
        }
        
        .live-badge {
            animation: pulse 1.5s infinite;
            background: #ff4444;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: 800;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(0.95); }
        }
        
        .content-item {
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .content-item:hover {
            background: rgba(255,255,255,0.15);
            border-color: rgba(255,255,255,0.4);
            transform: translateX(5px);
        }
        
        .push-btn {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            color: white;
            font-weight: 800;
            padding: 10px 25px;
            border-radius: 25px;
            transition: all 0.2s;
        }
        
        .push-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(245, 87, 108, 0.5);
        }
        
        .student-list {
            background: rgba(0,0,0,0.3);
            border-radius: 15px;
            padding: 20px;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .student-item {
            background: rgba(255,255,255,0.1);
            padding: 10px 15px;
            border-radius: 10px;
            margin-bottom: 10px;
        }
        
        .origin-badge {
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 10px;
            font-weight: 700;
        }
        
        .badge-global { background: #4ECDC4; }
        .badge-custom { background: #f093fb; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <?php if(!$session_data): ?>
            <!-- No Active Session -->
            <div class="text-center py-5">
                <h1 class="display-4 mb-4">🔴 Live Command Center</h1>
                <p class="lead mb-4">No active live session. Start a class from your dashboard to begin.</p>
                <a href="dashboard.php" class="btn btn-light btn-lg rounded-pill">Go to Dashboard</a>
            </div>
        <?php else: ?>
            <!-- Active Session Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-2">
                        <span class="live-badge">🔴 LIVE</span>
                        <?php echo htmlspecialchars($session_data['class_name']); ?>
                    </h2>
                    <p class="mb-0 opacity-75">
                        📚 <?php echo htmlspecialchars($session_data['subject_name']); ?> 
                        • Week: <?php echo htmlspecialchars($session_data['week_title'] ?? 'Not Set'); ?>
                        <?php if(!$current_week_id || $current_week_id == 0): ?>
                            <span class="badge bg-warning text-dark ms-2">⚠️ No Week Selected - Go to Dashboard to set week!</span>
                        <?php else: ?>
                            <span class="badge bg-success ms-2">✓ Week ID: <?php echo $current_week_id; ?></span>
                        <?php endif; ?>
                    </p>
                    
                    <!-- Meeting Link Display -->
                    <?php if($meeting_link): ?>
                        <div class="mt-3 p-3 rounded-3" style="background: rgba(255,255,255,0.2); border: 2px solid rgba(255,255,255,0.3);">
                            <div class="d-flex align-items-center gap-2">
                                <strong class="opacity-75">📺 Meeting Link:</strong>
                                <input 
                                    type="text" 
                                    id="meetingLinkInput" 
                                    value="<?php echo htmlspecialchars($meeting_link); ?>" 
                                    readonly 
                                    class="form-control form-control-sm d-inline-block" 
                                    style="max-width: 400px; background: rgba(255,255,255,0.9);">
                                <button 
                                    class="btn btn-sm btn-light" 
                                    onclick="copyMeetingLink()" 
                                    id="copyBtn">
                                    📋 Copy Link
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="session_id" value="<?php echo $live_session['session_id']; ?>">
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                        <button type="submit" name="end_session" class="btn btn-danger btn-lg rounded-pill" onclick="return confirm('End this live session?')">
                            ⏹️ End Session
                        </button>
                    </form>
                </div>
            </div>

            <?php if($message): ?>
                <div class="alert alert-success rounded-pill text-center fw-bold mb-4"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Main Content Area -->
            <div class="row g-4">
                <!-- Left: Content Launcher -->
                <div class="col-md-8">
                    <h4 class="fw-bold mb-3">🚀 Content Launcher</h4>
                    
                    <?php if(empty($materials) && empty($videos) && empty($quizzes)): ?>
                        <div class="alert alert-warning">
                            No content available for this week. Add materials in Curriculum Remix.
                        </div>
                    <?php else: ?>
                        <!-- Materials -->
                        <?php foreach($materials as $m): ?>
                            <div class="content-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="origin-badge badge-<?php echo $m['origin']; ?>">
                                            <?php echo $m['origin'] == 'global' ? '🌍 MASTER' : '👤 MY ITEM'; ?>
                                        </span>
                                        <h5 class="mb-1 mt-2">
                                            <?php echo $m['type'] == 'reading_session' ? '📖' : '📄'; ?>
                                            <?php echo htmlspecialchars($m['title']); ?>
                                        </h5>
                                        <p class="mb-0 small opacity-75"><?php echo htmlspecialchars(substr($m['description'] ?? '', 0, 80)); ?></p>
                                    </div>
                                    <button class="push-btn" onclick="pushContent(<?php echo $live_session['session_id']; ?>, 'material', <?php echo $m['material_id']; ?>)">
                                        🚀 PUSH
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Videos -->
                        <?php foreach($videos as $v): ?>
                            <div class="content-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="origin-badge badge-<?php echo $v['origin']; ?>">
                                            <?php echo $v['origin'] == 'global' ? '🌍 MASTER' : '👤 MY ITEM'; ?>
                                        </span>
                                        <h5 class="mb-1 mt-2">
                                            🎥 <?php echo htmlspecialchars($v['title']); ?>
                                        </h5>
                                        <p class="mb-0 small opacity-75"><?php echo htmlspecialchars(substr($v['description'] ?? '', 0, 80)); ?></p>
                                    </div>
                                    <button class="push-btn" onclick="pushContent(<?php echo $live_session['session_id']; ?>, 'video', <?php echo $v['video_id']; ?>)">
                                        🚀 PUSH
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Quizzes -->
                        <?php foreach($quizzes as $q): ?>
                            <div class="content-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="origin-badge badge-<?php echo $q['origin']; ?>">
                                            <?php echo $q['origin'] == 'global' ? '🌍 MASTER' : '👤 MY ITEM'; ?>
                                        </span>
                                        <h5 class="mb-1 mt-2">
                                            ⚔️ <?php echo htmlspecialchars($q['title']); ?>
                                        </h5>
                                        <p class="mb-0 small opacity-75"><?php echo htmlspecialchars(substr($q['description'] ?? '', 0, 80)); ?></p>
                                    </div>
                                    <button class="push-btn" onclick="pushContent(<?php echo $live_session['session_id']; ?>, 'quiz', <?php echo $q['quiz_id']; ?>)">
                                        🚀 PUSH
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Right: Live Attendance -->
                <div class="col-md-4">
                    <h4 class="fw-bold mb-3">👥 Enrolled Students (<?php echo count($active_students); ?>)</h4>
                    <div class="student-list">
                        <?php if(empty($active_students)): ?>
                            <p class="text-center opacity-50">No students enrolled</p>
                        <?php else: ?>
                            <?php foreach($active_students as $student): ?>
                                <div class="student-item">
                                    <div class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                    <small class="opacity-75">
                                        Last seen: <?php 
                                            if($student['last_login_at']) {
                                                $diff = time() - strtotime($student['last_login_at']);
                                                echo $diff < 300 ? '🟢 Online' : '⚪ ' . date('M j, g:i A', strtotime($student['last_login_at']));
                                            } else {
                                                echo 'Never';
                                            }
                                        ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function pushContent(sessionId, contentType, contentId) {
    // Send AJAX request
    fetch('live_class.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `push_content=1&session_id=${sessionId}&content_type=${contentType}&content_id=${contentId}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Visual feedback
            alert(data.message);
            // Optionally highlight the pushed item
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to push content');
    });
}

function copyMeetingLink() {
    const linkInput = document.getElementById('meetingLinkInput');
    const copyBtn = document.getElementById('copyBtn');
    
    linkInput.select();
    linkInput.setSelectionRange(0, 99999); // For mobile devices
    
    navigator.clipboard.writeText(linkInput.value).then(() => {
        // Success feedback
        const originalText = copyBtn.innerHTML;
        copyBtn.innerHTML = '✅ Copied!';
        copyBtn.classList.add('btn-success');
        copyBtn.classList.remove('btn-light');
        
        setTimeout(() => {
            copyBtn.innerHTML = originalText;
            copyBtn.classList.remove('btn-success');
            copyBtn.classList.add('btn-light');
        }, 2000);
    }).catch(err => {
        alert('Failed to copy link: ' + err);
    });
}
</script>
</body>
</html>
