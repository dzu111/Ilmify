<?php
// teacher/dashboard.php
session_start();
require_once '../config/db.php';

// Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];


// Handle Week Update
if (isset($_POST['week_id']) && isset($_POST['class_id'])) {
    $class_id = $_POST['class_id'];
    $week_id = $_POST['week_id'];
    
    // Skip if empty selection
    if (!empty($week_id)) {
        // Verify ownership
        $verify = $pdo->prepare("SELECT class_id FROM classes WHERE class_id = ? AND teacher_id = ?");
        $verify->execute([$class_id, $teacher_id]);
        
        if ($verify->fetch()) {
            $update = $pdo->prepare("UPDATE classes SET current_week_id = ? WHERE class_id = ?");
            $update->execute([$week_id, $class_id]);
            
            // Verify update worked
            $check = $pdo->prepare("SELECT current_week_id FROM classes WHERE class_id = ?");
            $check->execute([$class_id]);
            $result = $check->fetch();
            
            if ($result['current_week_id'] == $week_id) {
                $msg = "✅ Week updated successfully! (Class ID: {$class_id}, Week ID: {$week_id})";
            } else {
                $msg = "⚠️ Update may have failed. Please try again.";
            }
        } else {
            $msg = "❌ Class not found or access denied.";
        }
    }
}

// 1. Get Teacher Info
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch();

// 2. Get All Classes with current week info
$classes_sql = "SELECT c.class_id, c.class_name, c.current_week_id, 
                       s.name as subject_name, s.subject_id,
                       w.title as current_week_title,
                       sch.day_of_week, sch.start_time, sch.end_time, sch.schedule_id
                FROM classes c
                JOIN subjects s ON c.subject_id = s.subject_id
                LEFT JOIN weeks w ON c.current_week_id = w.week_id
                LEFT JOIN class_schedule sch ON c.class_id = sch.class_id
                WHERE c.teacher_id = ? 
                ORDER BY FIELD(sch.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), sch.start_time";
$stmt = $pdo->prepare($classes_sql);
$stmt->execute([$teacher_id]);
$all_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Get Active Live Session (if any)
$active_session = $pdo->prepare("SELECT ls.session_id, c.class_name 
                                 FROM live_sessions ls 
                                 JOIN classes c ON ls.class_id = c.class_id 
                                 WHERE c.teacher_id = ? AND ls.ended_at IS NULL");
$active_session->execute([$teacher_id]);
$current_live = $active_session->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - Ilmify</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <h2 class="fw-bold mb-4">Welcome back, <?php echo htmlspecialchars($teacher['full_name']); ?>! 👋</h2>

        <!-- Alert: Live Class in Progress -->
        <?php if($current_live): ?>
            <div class="alert alert-danger d-flex justify-content-between align-items-center shadow-sm">
                <div>
                    <strong class="fs-5">🔴 You are currently LIVE with <?php echo htmlspecialchars($current_live['class_name']); ?></strong>
                </div>
                <a href="live_class.php" class="btn btn-danger fw-bold pulse-btn">Resum Command Center</a>
            </div>
        <?php endif; ?>

        <?php if(!empty($msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $msg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left Col: Class Management -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center">
                        📚 My Classes & Week Management
                        <span class="badge bg-primary"><?php echo count($all_classes); ?> Classes</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Class Name</th>
                                        <th>Subject</th>
                                        <th>Current Week</th>
                                        <th>Schedule</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($all_classes as $class): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold"><?php echo htmlspecialchars($class['class_name']); ?></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($class['subject_name']); ?></span></td>
                                            <td>
                                                <form method="POST" class="d-inline-flex gap-2" id="weekForm<?php echo $class['class_id']; ?>">
                                                    <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                                    <select name="week_id" class="form-select form-select-sm" style="min-width: 180px;" onchange="this.form.submit()">
                                                        <option value="">📌 Select Week...</option>
                                                        <?php
                                                        // Fetch weeks for this subject
                                                        $weeks_stmt = $pdo->prepare("SELECT week_id, title, sort_order FROM weeks WHERE subject_id = ? ORDER BY sort_order");
                                                        $weeks_stmt->execute([$class['subject_id']]);
                                                        $weeks = $weeks_stmt->fetchAll();
                                                        foreach($weeks as $w):
                                                        ?>
                                                            <option value="<?php echo $w['week_id']; ?>" <?php echo ($class['current_week_id'] == $w['week_id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($w['title']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" name="update_week" class="btn btn-sm btn-outline-primary" style="display: none;">Update</button>
                                                </form>
                                                <?php if($class['current_week_id']): ?>
                                                    <small class="text-success d-block mt-1">✓ Active</small>
                                                <?php else: ?>
                                                    <small class="text-muted d-block mt-1">⚠️ Not set</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($class['day_of_week']): ?>
                                                    <?php echo $class['day_of_week']; ?><br>
                                                    <small class="text-muted"><?php echo date('h:i A', strtotime($class['start_time'])); ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">No schedule</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if($class['schedule_id']): ?>
                                                    <button 
                                                        class="btn btn-sm btn-success rounded-pill px-3 launch-session-btn" 
                                                        data-schedule-id="<?php echo $class['schedule_id']; ?>"
                                                        data-class-id="<?php echo $class['class_id']; ?>"
                                                        data-class-name="<?php echo htmlspecialchars($class['class_name']); ?>"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#launchSessionModal">
                                                        🚀 Launch Session
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary rounded-pill px-3" disabled>No Schedule</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($all_classes)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">No classes assigned yet.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Col: Quick Stats -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-3 bg-primary text-white">
                    <div class="card-body text-center p-4">
                        <h1 class="display-4 fw-bold"><?php echo count($all_classes); ?></h1>
                        <p class="mb-0">Total Classes</p>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">
                        📢 Quick Actions
                    </div>
                    <div class="card-body">
                        <a href="teacher_subjects.php" class="btn btn-outline-dark w-100 mb-2">📚 View Curriculum</a>
                        <a href="gradebook.php" class="btn btn-outline-dark w-100">📊 Grade Book</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Launch Session Modal -->
<div class="modal fade" id="launchSessionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 25px; border: 4px solid #AB47BC;">
            <div class="modal-header" style="background: linear-gradient(135deg, #AB47BC, #7B1FA2); color: white; border-radius: 21px 21px 0 0;">
                <h5 class="modal-title fw-bold">🚀 Launch Live Session</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" style="background-color: #F3E5F5;">
                <form id="launchSessionForm">
                    <input type="hidden" id="modal_schedule_id" name="schedule_id">
                    <input type="hidden" id="modal_class_id" name="class_id">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">📺 Meeting Link (Required)</label>
                        <input 
                            type="url" 
                            class="form-control form-control-lg" 
                            id="meeting_link" 
                            name="meeting_link" 
                            placeholder="https://meet.google.com/abc-defg-hij"
                            style="border-radius: 15px; border: 3px solid #AB47BC;"
                            required>
                        <small class="text-muted">Zoom, Google Meet, Jitsi, or any video conference link</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">📝 Session Notes (Optional)</label>
                        <textarea 
                            class="form-control" 
                            id="session_notes" 
                            name="session_notes" 
                            rows="3"
                            placeholder="e.g., Don't forget: Quiz today!"
                            style="border-radius: 15px; border: 3px solid #E1BEE7;"></textarea>
                    </div>
                    
                    <div class="alert alert-info" style="border-radius: 15px; border-left: 5px solid #2196F3;">
                        <strong>Class:</strong> <span id="modal_class_name"></span>
                    </div>
                    
                    <button type="submit" class="btn btn-lg w-100 game-btn-launch" id="launchBtn">
                        🎬 START SESSION NOW
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
    .pulse-btn { animation: pulse 1.5s infinite; }
    @keyframes pulse {
        0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
        70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    
    .game-btn-launch {
        background: linear-gradient(135deg, #4CAF50, #45a049);
        color: white;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
        border: 4px solid #388E3C;
        border-radius: 20px;
        padding: 15px;
        transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        box-shadow: 0 8px 15px rgba(76, 175, 80, 0.3);
    }
    
    .game-btn-launch:hover {
        transform: translateY(-5px) scale(1.05);
        box-shadow: 0 15px 30px rgba(76, 175, 80, 0.5);
    }
    
    .game-btn-launch:active {
        transform: translateY(0px) scale(0.98);
    }
</style>

<script>
// Populate modal with class data when Launch Session button is clicked
document.querySelectorAll('.launch-session-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('modal_schedule_id').value = this.dataset.scheduleId;
        document.getElementById('modal_class_id').value = this.dataset.classId;
        document.getElementById('modal_class_name').textContent = this.dataset.className;
    });
});

// Handle form submission with AJAX
document.getElementById('launchSessionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const launchBtn = document.getElementById('launchBtn');
    const originalText = launchBtn.innerHTML;
    launchBtn.disabled = true;
    launchBtn.innerHTML = '⏳ Creating Session...';
    
    const formData = new FormData(this);
    formData.append('create_session', '1');
    
    fetch('create_live_session.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Redirect to live class page with session_id
            window.location.href = 'live_class.php?session_id=' + data.session_id;
        } else {
            alert('Error: ' + data.message);
            launchBtn.disabled = false;
            launchBtn.innerHTML = originalText;
        }
    })
    .catch(error => {
        alert('Error creating session. Please try again.');
        launchBtn.disabled = false;
        launchBtn.innerHTML = originalText;
    });
});
</script>
        100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
</style>
</body>
</html>
