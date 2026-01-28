<?php
// student/quests.php
session_start();
require_once '../config/db.php';
require_once '../config/gamification.php'; // <--- Brain for Level Up Logic

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$flash = ""; // Initialize flash message variable

// --- 1. GATHER USER STATS (REAL DATA) ---
// A. Count Quizzes Taken
$qStmt = $pdo->prepare("SELECT COUNT(*) FROM quiz_results WHERE student_id = ?");
$qStmt->execute([$student_id]);
$stats['quiz'] = $qStmt->fetchColumn();

// B. Count Notes Read (FIXED: NOW REAL)
$nStmt = $pdo->prepare("SELECT COUNT(*) FROM student_reads WHERE student_id = ?");
$nStmt->execute([$student_id]);
$stats['note_read'] = $nStmt->fetchColumn(); 

// --- 2. HANDLE CLAIM ACTION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_task_id'])) {
    $task_id = $_POST['claim_task_id'];
    
    // Fetch task info
    $tStmt = $pdo->prepare("SELECT * FROM tasks WHERE task_id = ?");
    $tStmt->execute([$task_id]);
    $task = $tStmt->fetch();

    // Check if task exists and user meets requirements
    $criteria_type = $task['criteria_type'];
    $user_progress = $stats[$criteria_type] ?? 0;
    
    // Anti-Cheat: Check if already claimed
    $checkClaim = $pdo->prepare("SELECT * FROM student_task_claims WHERE student_id = ? AND task_id = ?");
    $checkClaim->execute([$student_id, $task_id]);
    $already_claimed = $checkClaim->fetch();

    if ($task && $user_progress >= $task['criteria_count'] && !$already_claimed) {
        try {
            $pdo->beginTransaction();
            
            // 1. Give XP Reward
            // Use Gamification Logic (Brain) to calculate new Level if needed
            // Fetch current progress first
            $progStmt = $pdo->prepare("SELECT * FROM student_progress WHERE student_id = ?");
            $progStmt->execute([$student_id]);
            $prog = $progStmt->fetch();
            
            $current_xp = $prog['current_xp'];
            $current_level = $prog['current_level'];
            
            $new_xp_total = $current_xp + $task['xp_reward'];
            
            // Calculate Level Up using Brain function
            $level_data = checkLevelUp($current_level, $new_xp_total);
            
            $stmt = $pdo->prepare("UPDATE student_progress SET current_level = ?, current_xp = ? WHERE student_id = ?");
            $stmt->execute([$level_data['level'], $level_data['xp'], $student_id]);

            // 2. Mark as Claimed
            $stmt = $pdo->prepare("INSERT INTO student_task_claims (student_id, task_id) VALUES (?, ?)");
            $stmt->execute([$student_id, $task_id]);
            
            $pdo->commit();
            $flash = "🎉 Quest Completed! +{$task['xp_reward']} XP";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $flash = "❌ Error claiming reward.";
        }
    } else {
        $flash = "⚠️ You cannot claim this yet.";
    }
}

// --- 3. FETCH AVAILABLE QUESTS ---
// Fetch all tasks, mark which are claimed
$sql = "SELECT * FROM tasks ORDER BY xp_reward ASC"; 
$stmt = $pdo->query($sql);
$all_quests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get list of claimed IDs
$cStmt = $pdo->prepare("SELECT task_id FROM student_task_claims WHERE student_id = ?");
$cStmt->execute([$student_id]);
$claimed_ids = $cStmt->fetchAll(PDO::FETCH_COLUMN);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> 
    <title>Quest Board - StudyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Fredoka', sans-serif; background-color: #f4f6f9; }
        .quest-container { max-width: 900px; margin: 0 auto; }
        
        .quest-card {
            border: none;
            border-left: 6px solid #bdc3c7;
            background: white;
            transition: all 0.3s ease;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .quest-card:hover { transform: translateY(-3px); }
        
        /* Ready to Claim State */
        .quest-card.ready {
            border-left-color: #f1c40f;
            background: linear-gradient(to right, #fff, #fffdf5);
            box-shadow: 0 8px 15px rgba(241, 196, 15, 0.2);
        }
        
        /* Claimed State */
        .quest-card.claimed {
            border-left-color: #2ecc71;
            opacity: 0.7;
            background: #f8fff9;
        }

        .xp-badge {
            background-color: #2c3e50;
            color: #f1c40f;
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 20px;
            border: 2px solid #f1c40f;
            white-space: nowrap;
        }
        
        .progress-bar-custom {
            height: 10px; border-radius: 5px; background-color: #eee; overflow: hidden; margin-top: 10px;
        }
        .progress-fill {
            height: 100%; background-color: #3498db; transition: width 0.5s ease;
        }
        .quest-card.ready .progress-fill { background-color: #f1c40f; }
        .quest-card.claimed .progress-fill { background-color: #2ecc71; }
        
        /* Mobile Sidebar Overrides */
        .offcanvas-body .sidebar { width: 100% !important; min-height: auto !important; background-color: transparent !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark d-md-none p-3">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold text-warning">⚔️ Quest Board</span>
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

    <div class="flex-grow-1 p-3 p-md-4">
        
        <div class="quest-container">
            <div class="text-center mb-5 pt-3 pt-md-0">
                <h1 class="fw-bold text-secondary">⚔️ Quest Board</h1>
                <p class="text-muted">Complete tasks to earn XP and level up!</p>
            </div>

            <?php if($flash): ?>
                <div class="alert alert-info shadow-sm rounded-pill text-center mb-4 fw-bold">
                    <?php echo $flash; ?>
                </div>
            <?php endif; ?>

            <?php foreach($all_quests as $quest): ?>
                <?php 
                    $type = $quest['criteria_type'];
                    $target = $quest['criteria_count'];
                    $current = $stats[$type] ?? 0;
                    
                    // Logic checks
                    $is_claimed = in_array($quest['task_id'], $claimed_ids);
                    $is_ready = ($current >= $target) && !$is_claimed;
                    
                    // Display math
                    $display_current = ($current > $target) ? $target : $current;
                    $percent = ($display_current / $target) * 100;
                    
                    // CSS Classes
                    $cardClass = "";
                    if ($is_claimed) $cardClass = "claimed";
                    elseif ($is_ready) $cardClass = "ready";
                ?>

                <div class="card quest-card p-4 <?php echo $cardClass; ?>">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center">
                        
                        <div class="flex-grow-1 pe-md-4 w-100 mb-3 mb-md-0">
                            <div class="d-flex align-items-center mb-1">
                                <h4 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($quest['task_name']); ?></h4>
                                <?php if($is_claimed): ?>
                                    <span class="badge bg-success ms-3">COMPLETED</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted mb-2"><?php echo htmlspecialchars($quest['description']); ?></p>
                            
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 progress-bar-custom me-3">
                                    <div class="progress-fill" style="width: <?php echo $percent; ?>%;"></div>
                                </div>
                                <span class="fw-bold text-secondary small"><?php echo $display_current; ?>/<?php echo $target; ?></span>
                            </div>
                        </div>

                        <div class="d-flex flex-row flex-md-column align-items-center justify-content-between w-100 w-md-auto gap-3 gap-md-0">
                            <div class="xp-badge mb-md-2">+<?php echo $quest['xp_reward']; ?> XP</div>
                            
                            <?php if($is_claimed): ?>
                                <button class="btn btn-light text-success border w-100" disabled>✅ Done</button>
                            <?php elseif($is_ready): ?>
                                <form method="POST" class="w-100">
                                    <input type="hidden" name="claim_task_id" value="<?php echo $quest['task_id']; ?>">
                                    <button type="submit" class="btn btn-warning fw-bold shadow-sm w-100">✨ CLAIM</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-light text-muted border w-100" disabled>Locked</button>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>