<?php
// student/focus_mode.php
session_start();
require_once '../config/db.php';

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'] ?? 0;

// 1. FETCH SESSION & CLASS DETAILS
$sql = "SELECT ls.*, c.class_name, ls.meeting_link 
        FROM live_sessions ls 
        JOIN classes c ON ls.class_id = c.class_id
        WHERE ls.session_id = ? AND ls.status = 'active'";
$stmt = $pdo->prepare($sql);
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    die("<h3>Class Invalid or Ended</h3><a href='dashboard.php'>Return to Dashboard</a>");
}

// 2. LOG ATTENDANCE
// Check if already logged
$chk = $pdo->prepare("SELECT attendance_id FROM attendance WHERE session_id = ? AND student_id = ?");
$chk->execute([$session_id, $student_id]);
if (!$chk->fetch()) {
    $ins = $pdo->prepare("INSERT INTO attendance (session_id, student_id, status, joined_at) VALUES (?, ?, 'present', NOW())");
    $ins->execute([$session_id, $student_id]);
}

// 3. FETCH PUSHED CONTENT
// We join with materials, quizzes, and videos to get titles
$pushed_sql = "
    SELECT pcl.*, 
           CASE 
               WHEN pcl.content_type = 'material' OR pcl.content_type = 'reading_session' THEN m.title 
               WHEN pcl.content_type = 'quiz' THEN q.title 
               WHEN pcl.content_type = 'video' THEN v.title
           END as title
    FROM pushed_content_log pcl
    LEFT JOIN materials m ON pcl.content_type IN ('material', 'reading_session') AND pcl.content_id = m.material_id
    LEFT JOIN quizzes q ON pcl.content_type = 'quiz' AND pcl.content_id = q.quiz_id
    LEFT JOIN videos v ON pcl.content_type = 'video' AND pcl.content_id = v.video_id
    WHERE pcl.session_id = ?
    ORDER BY pcl.pushed_at DESC
";
$stmt = $pdo->prepare($pushed_sql);
$stmt->execute([$session_id]);
$pushed_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fallback for Meeting Link if empty
$embed_link = $session['meeting_link'];
if (empty($embed_link)) {
    // Demo link if none provided
    $embed_link = "https://meet.jit.si/IlmifyClass_" . $session['class_id']; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live: <?php echo htmlspecialchars($session['class_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { 
            background-color: #0d1117; 
            color: #fff; 
            height: 100vh;
            overflow: hidden; 
        }
        .focus-header {
            height: 70px;
            background: #161b22;
            border-bottom: 1px solid #30363d;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
        }
        .main-stage {
            height: calc(100vh - 70px);
            display: flex;
        }
        .content-area {
            flex: 1;
            background: #0d1117;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0; /* Removed padding */
            position: relative;
        }
        /* Removed Sidebar Styles */
        .live-pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .placeholder-icon { font-size: 5rem; opacity: 0.5; margin-bottom: 20px; }
        
        /* Iframe for PDF/Content */
        .content-frame {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: 8px;
            background: white;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="focus-header">
    <div class="d-flex align-items-center gap-3">
        <span class="badge bg-danger live-pulse px-3 py-2">🔴 LIVE</span>
        <div>
            <h5 class="m-0 fw-bold text-white"><?php echo htmlspecialchars($session['class_name']); ?></h5>
            <small class="text-muted">Focus Mode Active</small>
        </div>
    </div>
    <div class="d-flex gap-3">
        <?php if(!empty($session['meeting_link'])): ?>
            <a href="<?php echo htmlspecialchars($session['meeting_link']); ?>" 
               target="_blank" 
               class="btn btn-success btn-lg fw-bold d-flex align-items-center gap-2 pulse-button"
               style="animation: pulse 2s infinite;">
                📺 OPEN MEETING
            </a>
        <?php endif; ?>
        <a href="dashboard.php" class="btn btn-outline-danger btn-sm d-flex align-items-center">Exit Class</a>
    </div>
</div>

<!-- Main Content -->
<div class="main-stage">
    
    <!-- CENTER STAGE: Shows LATEST pushed item -->
    <div class="content-area" id="contentArea">
        <?php 
        $latest = $pushed_items[0] ?? null; 
        ?>

        <?php if ($latest): ?>
            <!-- SHOW LATEST CONTENT -->
            <div class="w-100 h-100 d-flex flex-column" id="contentDisplay">
                <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                    <h5 class="text-white m-0">
                        <span class="text-info" id="contentTitle"><?php echo htmlspecialchars($latest['title']); ?></span>
                    </h5>
                    <span class="badge bg-primary" id="contentTypeBadge"><?php echo strtoupper($latest['content_type']); ?></span>
                </div>

                <div class="flex-grow-1 bg-dark" id="contentFrame">
                    <?php if($latest['content_type'] == 'material' || $latest['content_type'] == 'reading_session'): ?>
                        <!-- Embed PDF/Material -->
                        <iframe src="view_note.php?id=<?php echo $latest['content_id']; ?>&embedded=true" class="content-frame"></iframe>
                    <?php elseif($latest['content_type'] == 'quiz'): ?>
                        <!-- Embed Quiz Inline -->
                        <iframe src="take_quiz.php?id=<?php echo $latest['content_id']; ?>&embedded=true" class="content-frame"></iframe>
                    <?php elseif($latest['content_type'] == 'video'): ?>
                        <!-- Embed Video -->
                        <iframe src="view_video.php?id=<?php echo $latest['content_id']; ?>&embedded=true" class="content-frame"></iframe>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- WAITING STATE -->
            <div class="text-center" id="waitingState">
                <div class="placeholder-icon">📡</div>
                <h2 class="text-white">Waiting for Teacher...</h2>
                <p class="text-muted">The teacher has not pushed any content to the screen yet.</p>
                <div class="spinner-border text-primary mt-3" role="status"></div>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Real-time Content Polling
const sessionId = <?php echo $session_id; ?>;
let lastLogId = <?php echo $latest['log_id'] ?? 0; ?>;

function checkForUpdates() {
    fetch(`check_live_updates.php?session_id=${sessionId}&last_log_id=${lastLogId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ended') {
                // Class ended, redirect to dashboard
                alert('Class has ended!');
                window.location.href = 'dashboard.php';
            } else if (data.status === 'new_content') {
                // Update the content display
                lastLogId = data.data.log_id;
                updateContentDisplay(data.data);
            }
            // else: no_update, do nothing
        })
        .catch(error => console.error('Polling error:', error));
}

function updateContentDisplay(content) {
    const contentArea = document.getElementById('contentArea');
    const waitingState = document.getElementById('waitingState');
    
    // Hide waiting state if it exists
    if (waitingState) {
        waitingState.style.display = 'none';
    }
    
    // Update title and badge
    document.getElementById('contentTitle').textContent = content.title;
    document.getElementById('contentTypeBadge').textContent = content.content_type.toUpperCase();
    
    // Update iframe based on content type
    const contentFrame = document.getElementById('contentFrame');
    let iframeSrc = '';
    
    if (content.content_type === 'material' || content.content_type === 'reading_session') {
        iframeSrc = `view_note.php?id=${content.content_id}&embedded=true`;
    } else if (content.content_type === 'quiz') {
        iframeSrc = `take_quiz.php?id=${content.content_id}&embedded=true`;
    } else if (content.content_type === 'video') {
        iframeSrc = `view_video.php?id=${content.content_id}&embedded=true`;
    }
    
    contentFrame.innerHTML = `<iframe src="${iframeSrc}" class="content-frame"></iframe>`;
    
    // Show content display if it was hidden
    const contentDisplay = document.getElementById('contentDisplay');
    if (contentDisplay) {
        contentDisplay.style.display = 'flex';
    } else {
        // Create content display if it doesn't exist
        contentArea.innerHTML = `
            <div class="w-100 h-100 d-flex flex-column" id="contentDisplay">
                <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                    <h5 class="text-white m-0">
                        <span class="text-info" id="contentTitle">${content.title}</span>
                    </h5>
                    <span class="badge bg-primary" id="contentTypeBadge">${content.content_type.toUpperCase()}</span>
                </div>
                <div class="flex-grow-1 bg-dark" id="contentFrame">
                    <iframe src="${iframeSrc}" class="content-frame"></iframe>
                </div>
            </div>
        `;
    }
}

// Poll every 3 seconds
setInterval(checkForUpdates, 3000);
</script>
</body>
</html>
