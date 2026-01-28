<?php
// parent/progress.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: ../auth/login.php");
    exit;
}

$parent_id = $_SESSION['user_id'];

// --- 1. FETCH TARGET CHILD (STRICT) ---
$stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->execute([$parent_id]);
$parent_email = $stmt->fetchColumn();

// Logic: parent_ali@gmail.com -> ali@gmail.com
$target_email = str_replace("parent_", "", $parent_email);

// STRICT MATCH ONLY
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'student' LIMIT 1");
$stmt->execute([$target_email]);
$child = $stmt->fetch(PDO::FETCH_ASSOC);

// Stats Vars
$average_score = 0;
$total_quizzes = 0;
$letter_grade = "-";
$history = [];
$progress = ['current_level' => 1, 'current_xp' => 0];

if ($child) {
    $child_id = $child['user_id'];

    // Data 1: Progress
    $stmt = $pdo->prepare("SELECT * FROM student_progress WHERE student_id = ?");
    $stmt->execute([$child_id]);
    $fetched_progress = $stmt->fetch(PDO::FETCH_ASSOC);
    if($fetched_progress) $progress = $fetched_progress;

    // Data 2: Quiz History
    $stmt = $pdo->prepare("SELECT qr.*, q.title FROM quiz_results qr JOIN quizzes q ON qr.quiz_id = q.quiz_id WHERE qr.student_id = ? ORDER BY qr.attempt_date DESC");
    $stmt->execute([$child_id]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Data 3: Grades
    $total_quizzes = count($history);
    if ($total_quizzes > 0) {
        $total_score = array_sum(array_column($history, 'score'));
        $average_score = round($total_score / $total_quizzes);

        if ($average_score >= 90) $letter_grade = 'A';
        elseif ($average_score >= 80) $letter_grade = 'B';
        elseif ($average_score >= 70) $letter_grade = 'C';
        elseif ($average_score >= 50) $letter_grade = 'D';
        else $letter_grade = 'F';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - StudyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        
        .grade-circle {
            width: 100px; height: 100px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 3rem; font-weight: bold; color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .bg-grade-A { background: #2ecc71; }
        .bg-grade-B { background: #3498db; }
        .bg-grade-C { background: #f1c40f; }
        .bg-grade-D { background: #e67e22; }
        .bg-grade-F { background: #e74c3c; }
        .bg-grade-- { background: #95a5a6; }

        @media print {
            body * { visibility: hidden; }
            #printableArea, #printableArea * { visibility: visible; }
            #printableArea { position: absolute; left: 0; top: 0; width: 100%; margin: 0; padding: 0; background: white; }
            .btn-print, .sidebar, .navbar { display: none !important; }
            body { background-color: white !important; }
            .card { border: 1px solid #ddd !important; box-shadow: none !important; margin-bottom: 20px !important; break-inside: avoid; }
            .col-md-4, .col-md-8 { width: 100% !important; display: block !important; }
            .table { width: 100% !important; border-collapse: collapse !important; }
            .table th, .table td { border: 1px solid #000 !important; }
        }
    </style>
</head>
<body>

<div class="d-flex">
    
    <div class="d-print-none">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="flex-grow-1 p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
            <h2 class="fw-bold text-success">📊 Report Card</h2>
            <button onclick="window.print()" class="btn btn-outline-success btn-print fw-bold rounded-pill shadow-sm">
                🖨️ Print Report
            </button>
        </div>

        <?php if (!$child): ?>
            <div class="alert alert-danger p-4 shadow-sm rounded-4">
                <h4>⚠️ Student Not Found</h4>
                <p>Unable to retrieve student data. Please verify email linking.</p>
            </div>
        <?php else: ?>

            <div id="printableArea">
                
                <div class="d-none d-print-block text-center mb-4">
                    <h2 class="fw-bold">StudyQuest Official Report</h2>
                    <p class="text-muted">Generated on <?php echo date('F d, Y'); ?></p>
                    <hr>
                </div>

                <div class="card report-card mb-4 p-4 border-0 shadow-sm">
                    <div class="row align-items-center">
                        <div class="col-md-8 d-flex align-items-center">
                            <?php 
                                $pic = $child['profile_picture'] ?? 'default_avatar.jpg';
                                $picPath = ($pic === 'default_avatar.jpg' || $pic === 'default_avatar.png') 
                                    ? "/tinytale/assets/img/default_avatar.jpg" 
                                    : "../uploads/" . $pic;
                            ?>
                            <img src="<?php echo $picPath; ?>" class="rounded-circle border border-3 border-success me-4" width="100" height="100" style="object-fit: cover;">
                            <div>
                                <h5 class="text-muted text-uppercase small mb-1">Official Student Record</h5>
                                <h2 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($child['full_name']); ?></h2>
                                <p class="text-success fw-bold mb-0">Hero Level <?php echo $progress['current_level']; ?></p>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <h6 class="text-muted text-uppercase small">Overall Grade</h6>
                            <div class="d-flex justify-content-center mt-2">
                                <div class="grade-circle bg-grade-<?php echo $letter_grade; ?>">
                                    <?php echo $letter_grade; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-4">
                        <div class="card border-0 shadow-sm h-100 p-3 text-center" style="border: 1px solid #eee;">
                            <h3 class="fw-bold text-primary"><?php echo $average_score; ?>%</h3>
                            <span class="text-muted small fw-bold">Average</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card border-0 shadow-sm h-100 p-3 text-center" style="border: 1px solid #eee;">
                            <h3 class="fw-bold text-warning"><?php echo $progress['current_xp']; ?></h3>
                            <span class="text-muted small fw-bold">Total XP</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card border-0 shadow-sm h-100 p-3 text-center" style="border: 1px solid #eee;">
                            <h3 class="fw-bold text-info"><?php echo $total_quizzes; ?></h3>
                            <span class="text-muted small fw-bold">Quests Done</span>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-success text-white py-3 d-print-none">
                        <h5 class="mb-0 fw-bold">📜 Detailed Performance History</h5>
                    </div>
                    <div class="card-header bg-white py-3 d-none d-print-block border-bottom">
                        <h5 class="mb-0 fw-bold text-dark">Performance History</h5>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Quiz Title</th>
                                    <th>Date</th>
                                    <th>Score</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($history) > 0): ?>
                                    <?php foreach ($history as $record): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-dark"><?php echo htmlspecialchars($record['title']); ?></td>
                                            <td class="text-muted"><?php echo date('F d, Y', strtotime($record['attempt_date'])); ?></td>
                                            <td class="fw-bold fs-5"><?php echo $record['score']; ?>%</td>
                                            <td>
                                                <?php if($record['score'] >= 90): ?>
                                                    <span class="badge bg-success border border-success text-white">Outstanding</span>
                                                <?php elseif($record['score'] >= 75): ?>
                                                    <span class="badge bg-info border border-info text-dark">Good Job</span>
                                                <?php elseif($record['score'] >= 50): ?>
                                                    <span class="badge bg-warning border border-warning text-dark">Satisfactory</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger border border-danger text-white">Needs Study</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">No activity recorded yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="d-none d-print-block text-center mt-5 pt-5">
                    <p class="text-muted small">Parent Guardian Signature: __________________________</p>
                </div>

            </div>

        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>