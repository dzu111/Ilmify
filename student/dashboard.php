<?php
// student/dashboard.php
session_start();
require_once '../config/db.php';
require_once '../config/gamification.php'; // <--- Import the Brain

// --- SECURITY CHECK ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$student_id = $_SESSION['user_id'];

// 1. FETCH STUDENT DETAILS
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$student_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. FETCH PROGRESS & CALCULATE LEVEL
$stmt = $pdo->prepare("SELECT * FROM student_progress WHERE student_id = ?");
$stmt->execute([$student_id]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle missing progress row
if (!$progress) {
    $pdo->prepare("INSERT INTO student_progress (student_id, current_level, current_xp) VALUES (?, 1, 0)")->execute([$student_id]);
    $progress = ['current_level' => 1, 'current_xp' => 0];
}

$current_level = $progress['current_level'];
$current_xp = $progress['current_xp'];

// GAMIFICATION MATH
$xp_needed = getXPNeeded($current_level); 
$xp_percent = ($xp_needed > 0) ? ($current_xp / $xp_needed) * 100 : 0;

// 3. FETCH RECENT NOTES
$stmt = $pdo->query("SELECT * FROM materials ORDER BY created_at DESC LIMIT 5");
$recent_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4. FETCH ACTIVE QUIZZES
$stmt = $pdo->query("SELECT * FROM quizzes ORDER BY quiz_id DESC LIMIT 5");
$active_quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5. FETCH LATEST VIDEOS
$stmt = $pdo->query("SELECT * FROM videos ORDER BY created_at DESC LIMIT 5");
$recent_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 6. FETCH LATEST ANNOUNCEMENT
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 1");
$announcement = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - StudyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        
        /* Level Card Design */
        .level-card {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            border: none;
            box-shadow: 0 10px 25px rgba(37, 117, 252, 0.3);
        }
        .level-circle {
            min-width: 80px;
            min-height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            border: 3px solid rgba(255,255,255,0.5);
        }
        
        /* Slider Container */
        .card-slider-container {
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 20px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
        
        /* Individual Cards */
        .game-card {
            display: inline-block;
            width: 280px;
            margin-right: 20px;
            white-space: normal;
            vertical-align: top;
            transition: transform 0.2s;
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .game-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        /* Mobile Sidebar Overrides */
        .offcanvas-body .sidebar {
            width: 100% !important;
            min-height: auto !important;
            background-color: transparent !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark d-md-none p-3">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold text-warning">⚡ StudyQuest</span>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="mobileMenu">
    <div class="offcanvas-header border-bottom border-secondary">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php include 'sidebar.php'; ?>
    </div>
</div>

<div class="d-flex">
    
    <div class="d-none d-md-block">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="flex-grow-1 p-3 p-md-4" style="height: 100vh; overflow-y: auto;">
        
        <div class="row mb-4">
            <div class="col-12">
                <form action="search_results.php" method="GET">
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-0 ps-3 ps-md-4">🔍</span>
                        <input type="text" name="q" class="form-control border-0 py-3" placeholder="Search quests..." style="border-radius: 0 15px 15px 0;">
                        <button class="btn btn-primary px-4 px-md-5 rounded-end" style="border-radius: 15px;">GO</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($announcement): ?>
            <?php 
                $alertClass = 'alert-info';
                $icon = '📢';
                if ($announcement['type'] == 'warning') { 
                    $alertClass = 'alert-warning border-warning'; 
                    $icon = '⚠️'; 
                } elseif ($announcement['type'] == 'quest') { 
                    $alertClass = 'alert-primary border-primary'; 
                    $icon = '⚔️'; 
                }
            ?>
            <div class="alert <?php echo $alertClass; ?> shadow-sm rounded-4 mb-4" role="alert">
                <div class="d-flex">
                    <div class="fs-1 me-3"><?php echo $icon; ?></div>
                    <div>
                        <h5 class="alert-heading fw-bold mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                        <small class="text-muted mt-1 d-block">
                            Posted: <?php echo date('M d, h:i A', strtotime($announcement['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-light border-0 shadow-sm rounded-4 d-flex align-items-center mb-4" role="alert">
                <span class="fs-2 me-3">👋</span>
                <div>
                    <h5 class="fw-bold mb-0">Welcome back, Hero!</h5>
                    <p class="mb-0 text-muted">Ready to continue your adventure?</p>
                </div>
            </div>
        <?php endif; ?>

        <div class="card level-card mb-5 p-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between">
                <div class="mb-3 mb-md-0">
                    <h6 class="text-uppercase text-white-50 letter-spacing-2 mb-1">Current Progress</h6>
                    <h2 class="fw-bold mb-0">Level <?php echo $current_level; ?></h2>
                    <p class="mb-0 mt-2 text-white-50">
                        <?php echo $current_xp; ?> / <?php echo $xp_needed; ?> XP 
                        (Need <?php echo $xp_needed - $current_xp; ?> more)
                    </p>
                </div>
                <div class="level-circle">
                    <?php echo $current_level; ?>
                </div>
            </div>
            
            <div class="progress mt-4" style="height: 12px; background: rgba(0,0,0,0.2); border-radius: 10px;">
                <div class="progress-bar bg-warning" role="progressbar" 
                     style="width: <?php echo $xp_percent; ?>%;" 
                     aria-valuenow="<?php echo $current_xp; ?>" aria-valuemin="0" aria-valuemax="<?php echo $xp_needed; ?>">
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-secondary">📖 Notes</h4>
            <a href="notes.php" class="btn btn-outline-primary rounded-pill btn-sm px-3">See All</a>
        </div>
        
        <div class="card-slider-container">
            <?php if(count($recent_notes) > 0): ?>
                <?php foreach($recent_notes as $note): ?>
                    <div class="card game-card p-3">
                        <div class="card-body p-0">
                            <h5 class="card-title fw-bold text-primary text-truncate"><?php echo htmlspecialchars($note['title']); ?></h5>
                            <p class="card-text text-muted small mb-3" style="height: 40px; overflow: hidden;">
                                <?php echo htmlspecialchars($note['description'] ?? 'No description.'); ?>
                            </p>
                           <a href="view_note.php?id=<?php echo $note['material_id']; ?>" class="btn btn-light w-100 text-primary fw-bold border">Read Scroll</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted ms-2">No notes available yet.</p>
            <?php endif; ?>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
            <h4 class="fw-bold text-secondary">🎥 Video Gallery</h4>
            <a href="videos.php" class="btn btn-outline-danger rounded-pill btn-sm px-3">See All</a>
        </div>

        <div class="card-slider-container">
            <?php if(count($recent_videos) > 0): ?>
                <?php foreach($recent_videos as $vid): ?>
                    <?php 
                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $vid['youtube_link'], $matches);
                        $yt_id = $matches[1] ?? '';
                        $thumb = "https://img.youtube.com/vi/$yt_id/mqdefault.jpg";
                    ?>
                    <div class="card game-card border-0 shadow-sm">
                        <img src="<?php echo $thumb; ?>" class="card-img-top" style="height: 140px; object-fit: cover;" alt="Video Thumbnail">
                        <div class="card-body p-3">
                            <h6 class="card-title fw-bold text-dark text-truncate mb-2"><?php echo htmlspecialchars($vid['title']); ?></h6>
                            <a href="view_video.php?id=<?php echo $vid['video_id']; ?>" class="btn btn-danger w-100 btn-sm fw-bold rounded-pill">▶ Watch</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted ms-2">No videos available yet.</p>
            <?php endif; ?>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
            <h4 class="fw-bold text-secondary">⚔️ Active Battles</h4>
            <a href="quiz.php" class="btn btn-outline-warning text-dark rounded-pill btn-sm px-3">See All</a>
        </div>

        <div class="card-slider-container">
            <?php if(count($active_quizzes) > 0): ?>
                <?php foreach($active_quizzes as $quiz): ?>
                    <div class="card game-card p-3 border-warning border-2">
                        <div class="card-body p-0">
                            <h5 class="card-title fw-bold text-dark text-truncate"><?php echo htmlspecialchars($quiz['title']); ?></h5>
                            <span class="badge bg-warning text-dark mb-3">Rewards Available</span>
                            <a href="take_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-warning w-100 fw-bold">Start Battle</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted ms-2">No active battles.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>