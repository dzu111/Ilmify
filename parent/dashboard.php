<?php
// parent/dashboard.php
session_start();
require_once '../config/db.php';
require_once '../config/gamification.php'; 

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../auth/login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];
$child = null; 

// --- 1. FETCH PARENT DETAILS ---
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$parent_id]);
$parent_user = $stmt->fetch(PDO::FETCH_ASSOC);

// Zombie Check
if (!$parent_user) {
    session_destroy();
    header("Location: ../auth/login.php?error=session_expired");
    exit;
}

$parent_email = $parent_user['email'];

// --- 2. FIND THE STUDENT (STRICT MATCH ONLY) ---
// Logic: Removes 'parent_' from the email to find the child.
// Example: parent_ali@gmail.com -> ali@gmail.com
$target_email = str_replace("parent_", "", $parent_email);

$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'student' LIMIT 1");
$stmt->execute([$target_email]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

// --- 3. FETCH CHILD DATA (IF FOUND) ---
if ($child) {
    $child_id = $child['user_id'];

    // Progress
    $stmt = $pdo->prepare("SELECT * FROM student_progress WHERE student_id = ?");
    $stmt->execute([$child_id]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['current_level' => 1, 'current_xp' => 0];

    // Math for Level Bar
    if (function_exists('getXPNeeded')) {
        $xp_needed = getXPNeeded($progress['current_level']);
    } else {
        $xp_needed = 100 * $progress['current_level']; 
    }

    // Recent Activity
    $stmt = $pdo->prepare("
        SELECT qr.*, q.title 
        FROM quiz_results qr 
        JOIN quizzes q ON qr.quiz_id = q.quiz_id 
        WHERE qr.student_id = ? 
        ORDER BY qr.attempt_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$child_id]);
    $recent_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- 4. ANNOUNCEMENTS ---
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 1");
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - StudyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="/tinytale/assets/img/favicon.png">
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .stat-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4" style="height: 100vh; overflow-y: auto;">
        
        <h2 class="fw-bold text-success mb-4">👋 Welcome, <?php echo htmlspecialchars($parent_user['full_name']); ?>!</h2>

        <?php if ($announcement): ?>
            <?php 
                $alertClass = 'alert-info';
                $icon = '📢';
                if ($announcement['type'] == 'warning') { $alertClass = 'alert-warning border-warning'; $icon = '⚠️'; }
                elseif ($announcement['type'] == 'quest') { $alertClass = 'alert-primary border-primary'; $icon = '⚔️'; }
            ?>
            <div class="alert <?php echo $alertClass; ?> shadow-sm rounded-4 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <div class="fs-2 me-3"><?php echo $icon; ?></div>
                    <div>
                        <h6 class="alert-heading fw-bold mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                        <small><?php echo htmlspecialchars($announcement['content']); ?></small>
                        <div class="text-muted" style="font-size: 0.75rem; margin-top: 4px;">
                            Posted: <?php echo date('M d, h:i A', strtotime($announcement['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$child): ?>
            <div class="alert alert-danger border-0 shadow-sm rounded-4 p-4">
                <h4 class="fw-bold mb-3">⚠️ Connection Error: Student Not Found</h4>
                <p>We searched for a student account, but found nothing linked to you.</p>
                
                <div class="bg-white p-3 rounded border text-muted small">
                    <strong>Debugging Info:</strong><br>
                    Your Email: <code><?php echo htmlspecialchars($parent_email); ?></code><br>
                    Looking for Student Email: <code><?php echo htmlspecialchars($target_email); ?></code><br>
                    <br>
                    <em>Fix: Go to Admin Panel > Manage Users. Ensure the student's email matches exactly what is shown above.</em>
                </div>
            </div>
        <?php else: ?>

            <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(to right, #198754, #20c997); color: white;">
                <div class="card-body p-4 d-flex align-items-center">
                    <?php 
                        $pic = $child['profile_picture'] ?? 'default_avatar.jpg';
                        $picPath = ($pic === 'default_avatar.jpg' || $pic === 'default_avatar.png') 
                            ? "/tinytale/assets/img/default_avatar.jpg" 
                            : "../uploads/" . $pic;
                    ?>
                    <img src="<?php echo $picPath; ?>" class="rounded-circle border border-3 border-white shadow-sm me-4" width="80" height="80" style="object-fit: cover;">
                    <div>
                        <h5 class="mb-0 text-white-50 text-uppercase small ls-1">Monitoring Progress For</h5>
                        <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($child['full_name']); ?></h2>
                        <span class="badge bg-white text-success mt-2">Student Hero</span>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card bg-white p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold">Current Level</h6>
                                <h2 class="fw-bold text-dark mb-0"><?php echo $progress['current_level']; ?></h2>
                            </div>
                            <div class="bg-light rounded-circle p-3 text-primary fs-4">🏆</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-white p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold">XP Progress</h6>
                                <h2 class="fw-bold text-dark mb-0">
                                    <?php echo $progress['current_xp']; ?> 
                                    <span class="text-muted fs-6">/ <?php echo $xp_needed; ?></span>
                                </h2>
                            </div>
                            <div class="bg-light rounded-circle p-3 text-warning fs-4">⭐</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-white p-3 h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted text-uppercase small fw-bold">Quizzes Done</h6>
                                <h2 class="fw-bold text-dark mb-0"><?php echo count($recent_activities); ?></h2>
                            </div>
                            <div class="bg-light rounded-circle p-3 text-danger fs-4">📝</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="fw-bold mb-0 text-secondary">📉 Recent Activity</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Activity Name</th>
                                    <th>Date</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recent_activities) > 0): ?>
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($activity['title']); ?></td>
                                            <td class="text-muted small"><?php echo date('M d, Y', strtotime($activity['attempt_date'])); ?></td>
                                            <td class="fw-bold"><?php echo $activity['score']; ?>%</td>
                                            <td>
                                                <?php 
                                                    if($activity['score'] >= 80) echo '<span class="badge bg-success rounded-pill">Master</span>';
                                                    elseif($activity['score'] >= 50) echo '<span class="badge bg-warning text-dark rounded-pill">Apprentice</span>';
                                                    else echo '<span class="badge bg-danger rounded-pill">Novice</span>';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No recent activity found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>