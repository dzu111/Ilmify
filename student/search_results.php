<?php
// student/search_results.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$query = trim($_GET['q'] ?? '');
$results = [
    'notes' => [],
    'videos' => [],
    'quizzes' => []
];

if ($query) {
    $searchTerm = "%$query%";

    // 1. Search Notes
    $stmt = $pdo->prepare("SELECT * FROM materials WHERE title LIKE ? OR description LIKE ?");
    $stmt->execute([$searchTerm, $searchTerm]);
    $results['notes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Search Videos
    $stmt = $pdo->prepare("SELECT * FROM videos WHERE title LIKE ? OR description LIKE ?");
    $stmt->execute([$searchTerm, $searchTerm]);
    $results['videos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Search Quizzes
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE title LIKE ?");
    $stmt->execute([$searchTerm]);
    $results['quizzes'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results: <?php echo htmlspecialchars($query); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .result-section { margin-bottom: 40px; }
        .empty-state { text-align: center; padding: 50px; color: #6c757d; }
    </style>
</head>
<body>

<div class="d-flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4" style="height: 100vh; overflow-y: auto;">
        
        <div class="mb-4">
            <a href="dashboard.php" class="text-decoration-none text-muted small mb-2 d-inline-block">⬅ Back to Dashboard</a>
            <h2 class="fw-bold">
                🔍 Results for "<?php echo htmlspecialchars($query); ?>"
            </h2>
        </div>

        <div class="row mb-5">
            <div class="col-md-8">
                <form action="" method="GET">
                    <div class="input-group shadow-sm">
                        <input type="text" name="q" class="form-control py-2" value="<?php echo htmlspecialchars($query); ?>" placeholder="Try another keyword...">
                        <button class="btn btn-primary px-4">Search Again</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($results['notes']) && empty($results['videos']) && empty($results['quizzes'])): ?>
            <div class="empty-state">
                <h1 style="font-size: 4rem;">🦕</h1>
                <h3>No results found.</h3>
                <p>Try searching for specific topics like "History", "Science", or "Tutorial".</p>
            </div>
        <?php else: ?>

            <?php if (!empty($results['notes'])): ?>
                <div class="result-section">
                    <h4 class="fw-bold text-primary border-bottom pb-2 mb-3">📖 Found in Notes (<?php echo count($results['notes']); ?>)</h4>
                    <div class="list-group">
                        <?php foreach ($results['notes'] as $note): ?>
                            <a href="view_note.php?id=<?php echo $note['material_id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($note['title']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($note['description'], 0, 100)); ?>...</small>
                                </div>
                                <span class="badge bg-primary rounded-pill">Read</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($results['videos'])): ?>
                <div class="result-section">
                    <h4 class="fw-bold text-danger border-bottom pb-2 mb-3">🎥 Found in Videos (<?php echo count($results['videos']); ?>)</h4>
                    <div class="row g-3">
                        <?php foreach ($results['videos'] as $vid): ?>
                            <?php 
                                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $vid['youtube_link'], $matches);
                                $thumb = isset($matches[1]) ? "https://img.youtube.com/vi/$matches[1]/default.jpg" : "../assets/img/default_video.png";
                            ?>
                            <div class="col-md-4">
                                <a href="view_video.php?id=<?php echo $vid['video_id']; ?>" class="card text-decoration-none h-100 shadow-sm border-0">
                                    <div class="d-flex align-items-center p-2">
                                        <img src="<?php echo $thumb; ?>" width="80" height="60" class="rounded me-3" style="object-fit: cover;">
                                        <div>
                                            <h6 class="mb-0 text-dark fw-bold"><?php echo htmlspecialchars($vid['title']); ?></h6>
                                            <small class="text-danger">Watch Now</small>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($results['quizzes'])): ?>
                <div class="result-section">
                    <h4 class="fw-bold text-warning text-dark border-bottom pb-2 mb-3">⚔️ Found in Quests (<?php echo count($results['quizzes']); ?>)</h4>
                    <div class="list-group">
                        <?php foreach ($results['quizzes'] as $quiz): ?>
                            <a href="../uploads/quizzes/<?php echo $quiz['file_path']; ?>?id=<?php echo $quiz['quiz_id']; ?>" class="list-group-item list-group-item-action list-group-item-warning d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark"><?php echo htmlspecialchars($quiz['title']); ?></h6>
                                    <small class="text-muted">Test your knowledge!</small>
                                </div>
                                <span class="badge bg-dark rounded-pill">Start Battle</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>