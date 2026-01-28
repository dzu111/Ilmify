<?php
// student/videos.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Search
$search = $_GET['q'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE title LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM videos ORDER BY created_at DESC");
}
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Gallery - StudyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .card-video {
            transition: transform 0.2s;
            border: none;
            overflow: hidden;
            border-radius: 15px;
        }
        .card-video:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 25px rgba(220, 53, 69, 0.2);
            z-index: 10;
        }
        .play-overlay {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.2s;
        }
        .card-video:hover .play-overlay { opacity: 1; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4" style="height: 100vh; overflow-y: auto;">

    <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="dashboard.php" class="text-decoration-none text-muted small mb-2 d-inline-block">⬅ Back to Dashboard</a>
                <h2 class="fw-bold text-secondary">🎥 Video Gallery</h2>
                <p class="text-muted">Watch tutorials and lore videos.</p>
            </div>
        
        
            
            <form class="d-flex" style="max-width: 300px;">
                <input class="form-control me-2 rounded-pill" type="search" name="q" placeholder="Find video..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-danger rounded-pill" type="submit">Search</button>
            </form>
        </div>

        <div class="row g-4">
            <?php if (count($videos) > 0): ?>
                <?php foreach ($videos as $vid): ?>
                    <?php 
                        // Thumbnail Generator
                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $vid['youtube_link'], $matches);
                        $yt_id = $matches[1] ?? '';
                        $thumb = $yt_id ? "https://img.youtube.com/vi/$yt_id/mqdefault.jpg" : "../assets/img/default_video.png";
                    ?>
                    <div class="col-md-6 col-lg-3">
                        <div class="card card-video shadow-sm h-100">
                            <div class="position-relative">
                                <img src="<?php echo $thumb; ?>" class="card-img-top" style="height: 160px; object-fit: cover;">
                                <div class="play-overlay">
                                    <span style="font-size: 3rem; color: white; text-shadow: 0 2px 10px rgba(0,0,0,0.5);">▶</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h6 class="card-title fw-bold text-truncate"><?php echo htmlspecialchars($vid['title']); ?></h6>
                                <a href="view_video.php?id=<?php echo $vid['video_id']; ?>" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h3 class="text-muted">📺 No videos available.</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>