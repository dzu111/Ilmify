<?php
// teacher/teacher_subjects.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// Fetch subjects this teacher teaches (distinct subjects from their classes)
$stmt = $pdo->prepare("
    SELECT DISTINCT s.subject_id, s.name, s.description
    FROM subjects s
    JOIN classes c ON s.subject_id = c.subject_id
    WHERE c.teacher_id = ?
    ORDER BY s.name
");
$stmt->execute([$teacher_id]);
$subjects = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Subjects - Teacher Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #E0F7FA;
            font-family: 'Nunito', sans-serif;
        }
        
        .subject-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            border: 3px solid #e9ecef;
            transition: all 0.3s ease;
            cursor: pointer;
            min-height: 250px;
            position: relative;
            overflow: hidden;
        }
        
        .subject-card:hover {
            transform: translateY(-10px);
            border-color: #4ECDC4;
            box-shadow: 0 20px 40px rgba(78, 205, 196, 0.3);
        }
        
        .subject-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .game-btn {
            background: #FFD93D;
            border: 3px solid #ffcc00;
            color: #5a4a00;
            font-weight: 800;
            border-radius: 50px;
            padding: 10px 25px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }
        
        .game-btn:hover {
            background: #ffcc00;
            transform: scale(1.05);
            color: #5a4a00;
        }
        
        .read-only-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(0,0,0,0.1);
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.75rem;
            color: #666;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <div class="mb-4">
            <h2 class="fw-bold display-5 mb-2">📚 My Subjects</h2>
            <p class="text-muted">Subjects you teach - Click to view curriculum structure</p>
        </div>

        <?php if (empty($subjects)): ?>
            <div class="text-center py-5">
                <div class="subject-card p-5 mx-auto" style="max-width: 500px;">
                    <h3 class="mb-3">📭 No Subjects Found</h3>
                    <p class="text-muted">You are not assigned to teach any subjects yet.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach($subjects as $subject): ?>
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="subject-card p-4" onclick="window.location.href='teacher_subject_weeks.php?subject_id=<?php echo $subject['subject_id']; ?>'">
                            
                            <div class="text-center h-100 d-flex flex-column justify-content-center">
                                <div class="subject-icon">📚</div>
                                <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($subject['name']); ?></h4>
                                <p class="text-muted small mb-4">
                                    <?php echo htmlspecialchars(substr($subject['description'] ?? 'No description', 0, 80)); ?>
                                </p>
                                <span class="game-btn mt-auto">View Curriculum →</span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
