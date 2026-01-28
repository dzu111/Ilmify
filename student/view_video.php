<?php
// student/view_video.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

// 1. Fetch Video Details
$stmt = $pdo->prepare("SELECT * FROM videos WHERE video_id = ?");
$stmt->execute([$id]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) die("Video not found!");

// 2. Convert YouTube Link to Embed Code
// This regex extracts the ID from standard links (youtube.com) and short links (youtu.be)
preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video['youtube_link'], $matches);
$videoId = $matches[1] ?? '';

// If we found an ID, make the embed URL. Otherwise, keep original (though it likely won't play)
$embedUrl = $videoId ? "https://www.youtube.com/embed/" . $videoId . "?autoplay=1" : $video['youtube_link'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Watching: <?php echo htmlspecialchars($video['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }

        /* Cinema Viewer Container */
        .viewer-container {
            height: 75vh; /* Takes up 75% of the screen height */
            background: #000;
            border-radius: 20px;
            overflow: hidden;
            border: 4px solid #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            position: relative;
        }

        iframe { width: 100%; height: 100%; border: none; }
        
        /* Mobile Sidebar Overrides */
        .offcanvas-body .sidebar {
            width: 100% !important;
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
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="dashboard.php" class="text-decoration-none text-muted small">⬅ Back to Dashboard</a>
                <h3 class="fw-bold m-0 text-dark"><?php echo htmlspecialchars($video['title']); ?></h3>
            </div>
            <a href="videos.php" class="btn btn-outline-danger rounded-pill fw-bold">📺 More Videos</a>
        </div>

        <div class="viewer-container">
            <?php if ($videoId): ?>
                <iframe 
                    src="<?php echo $embedUrl; ?>" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
                </iframe>
            <?php else: ?>
                <div class="d-flex justify-content-center align-items-center h-100 text-white">
                    <div class="text-center">
                        <h1>😕</h1>
                        <h4>Video Unavailable</h4>
                        <p>Invalid YouTube Link provided.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-4 p-4 bg-white rounded-4 shadow-sm">
            <h5 class="fw-bold text-secondary">About this Video</h5>
            <p class="text-muted mb-0">
                <?php echo nl2br(htmlspecialchars($video['description'] ?? 'No description available for this video.')); ?>
            </p>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>