<?php
// teacher/teacher_subject_weeks.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$subject_id = $_GET['subject_id'] ?? 0;

// Verify teacher teaches this subject
$check = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id = ? AND subject_id = ?");
$check->execute([$teacher_id, $subject_id]);
if ($check->fetchColumn() == 0) {
    header("Location: teacher_subjects.php");
    exit;
}

// Get subject info
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$subject) {
    header("Location: teacher_subjects.php");
    exit;
}

// Get all weeks for this subject
$stmt = $pdo->prepare("SELECT * FROM weeks WHERE subject_id = ? ORDER BY sort_order");
$stmt->execute([$subject_id]);
$weeks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($subject['name']); ?> - Weeks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #E0F7FA;
            font-family: 'Nunito', sans-serif;
        }
        
        .timeline-container {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .timeline-line {
            position: absolute;
            width: 4px;
            background: linear-gradient(180deg, #4ECDC4 0%, #44A08D 100%);
            height: 100%;
            left: 50%;
            transform: translateX(-50%);
            z-index: 1;
        }
        
        .week-item {
            position: relative;
            margin-bottom: 60px;
            z-index: 10;
        }
        
        .week-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px;
            border: 3px solid #e9ecef;
            padding: 25px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            text-decoration: none;
            display: block;
        }
        
        .week-card:hover {
            border-color: #4ECDC4;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(78, 205, 196, 0.3);
        }
        
        .left { 
            text-align: right;
            padding-right: 60px;
            margin-right: 50%;
        }
        
        .right { 
            text-align: left;
            padding-left: 60px;
            margin-left: 50%;
            margin-left: calc(50% + 30px);
        }
        
        .timeline-badge {
            position: absolute;
            background: linear-gradient(135deg, #FF6B6B 0%, #EE5A6F 100%);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            border: 5px solid #E0F7FA;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            z-index: 20;
        }
        
        .left .timeline-badge {
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .right .timeline-badge {
            left: -25px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .game-btn-sm {
            background: #4ECDC4;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            border: none;
            transition: all 0.2s;
        }
        
        .game-btn-sm:hover {
            background: #45b8af;
            transform: scale(1.05);
        }
        
        .read-only-banner {
            background: linear-gradient(90deg, rgba(255,193,7,0.2) 0%, rgba(255,152,0,0.2) 100%);
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold"><?php echo htmlspecialchars($subject['name']); ?></h2>
                <p class="text-muted">View curriculum structure by week</p>
            </div>
            <a href="teacher_subjects.php" class="btn btn-outline-secondary rounded-pill">⬅ Back to Subjects</a>
        </div>

        <?php if (empty($weeks)): ?>
            <div class="text-center py-5">
                <h3>📭 No weeks found for this subject</h3>
                <p class="text-muted">The admin hasn't created any weeks yet.</p>
            </div>
        <?php else: ?>
            <div class="timeline-container py-4">
                <div class="timeline-line"></div>
                
                <?php foreach($weeks as $index => $week): ?>
                    <div class="week-item">
                        <a href="teacher_week_materials.php?week_id=<?php echo $week['week_id']; ?>" class="week-card <?php echo $index % 2 == 0 ? 'left' : 'right'; ?>">
                            <div class="timeline-badge"><?php echo $index + 1; ?></div>
                            <h4 class="fw-bold text-dark m-0 mb-2"><?php echo htmlspecialchars($week['title']); ?></h4>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Click to view materials</span>
                                <span class="game-btn-sm">VIEW 📂</span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
