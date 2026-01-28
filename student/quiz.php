<?php
// student/quiz.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// Search
$search = $_GET['q'] ?? '';
if ($search) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE title LIKE ? ORDER BY quiz_id DESC");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM quizzes ORDER BY quiz_id DESC");
}
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Quests - StudyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .card-quiz {
            transition: transform 0.2s;
            border: 2px solid #ffc107;
            background: white;
        }
        .card-quiz:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(255, 193, 7, 0.3);
            background: #fffdf5;
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
                <h2 class="fw-bold text-secondary">⚔️ Active Battles</h2>
                <p class="text-muted">Prove your knowledge and earn XP.</p>
            </div>
        
            
            <form class="d-flex" style="max-width: 300px;">
                <input class="form-control me-2 rounded-pill" type="search" name="q" placeholder="Find battle..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-warning rounded-pill fw-bold" type="submit">Search</button>
            </form>
        </div>

        <div class="row g-4">
            <?php if (count($quizzes) > 0): ?>
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-quiz h-100 shadow-sm">
                            <div class="card-body text-center p-4">
                                <div class="mb-3" style="font-size: 3rem;">🛡️</div>
                                <h5 class="card-title fw-bold text-dark"><?php echo htmlspecialchars($quiz['title']); ?></h5>
                                <p class="text-muted small">Challenge awaits you!</p>
                                
                                <div class="d-grid gap-2">
<a href="take_quiz.php?id=<?php echo $quiz['quiz_id']; ?>" class="btn btn-warning w-100 fw-bold">Start Battle</a>                                        Start Battle
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h3 class="text-muted">🏳️ No active battles right now.</h3>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>