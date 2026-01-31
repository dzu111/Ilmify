<?php
// student/reading_sessions.php
session_start();
require_once '../config/db.php';

// Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Search Logic
$search = $_GET['q'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM materials WHERE type='reading_session' AND (title LIKE ? OR description LIKE ?) ORDER BY created_at DESC");
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM materials WHERE type='reading_session' ORDER BY created_at DESC");
}
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading Sessions - StudyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .card-note {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            border-left: 5px solid #17a2b8; /* Info color for Reading Sessions */
        }
        .card-note:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="d-flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4" style="height: 100vh; overflow-y: auto;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="dashboard.php" class="text-decoration-none text-muted small mb-2 d-inline-block">⬅ Back to Dashboard</a>
                <h2 class="fw-bold text-secondary">📑 Reading Sessions</h2>
                <p class="text-muted">Access your PDF and PPTX learning materials.</p>
            </div>
            
            <form class="d-flex" style="max-width: 300px;">
                <input class="form-control me-2 rounded-pill" type="search" name="q" placeholder="Search sessions..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-info text-white rounded-pill" type="submit">Search</button>
            </form>
        </div>

        <div class="row g-4">
            <?php if (count($sessions) > 0): ?>
                <?php foreach ($sessions as $rs): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-note h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title fw-bold text-info"><?php echo htmlspecialchars($rs['title']); ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted small">
                                    Added: <?php echo date('M d, Y', strtotime($rs['created_at'])); ?>
                                </h6>
                                <p class="card-text text-secondary mb-4">
                                    <?php echo htmlspecialchars(substr($rs['description'] ?? '', 0, 80)) . '...'; ?>
                                </p>
                                <a href="view_note.php?id=<?php echo $rs['material_id']; ?>" class="btn btn-outline-info w-100 fw-bold rounded-pill stretched-link">
                                    View Document
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h3 class="text-muted">📭 No reading sessions found.</h3>
                </div>
            <?php endif; ?>
        </div>
        
    </div> </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
