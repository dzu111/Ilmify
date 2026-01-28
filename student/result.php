<?php 
// student/result.php
session_start(); 

// Security: If accessed directly without data, just go back
if (empty($_GET)) {
    header("Location: dashboard.php");
    exit;
}

$score = $_GET['score'] ?? 0;
$xp = $_GET['xp'] ?? 0;
$rank = $_GET['rank'] ?? 'D';
$levelup = $_GET['levelup'] ?? 0;

// Determine Color & Message based on Rank
$color = 'text-danger';
$message = "Keep Training!";
$bg_class = "bg-light";

if ($rank == 'S (Legendary)') {
    $color = 'text-warning'; 
    $message = "LEGENDARY PERFORMANCE!";
    $bg_class = "bg-warning-subtle";
} elseif ($rank == 'A (Master)') {
    $color = 'text-success';
    $message = "Outstanding Work!";
    $bg_class = "bg-success-subtle";
} elseif ($rank == 'B (Expert)') {
    $color = 'text-primary';
    $message = "Great Job!";
    $bg_class = "bg-primary-subtle";
} elseif ($rank == 'C (Apprentice)') {
    $color = 'text-info';
    $message = "You Passed!";
    $bg_class = "bg-info-subtle";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quest Complete!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #f0f2f5 0%, #e2e6ea 100%); 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }
        .result-card {
            max-width: 500px;
            width: 90%;
            border: none;
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            background: white;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        .rank-badge {
            font-size: 5rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 10px;
            text-shadow: 2px 2px 0px rgba(0,0,0,0.1);
        }
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 20px auto;
            border: 5px solid currentColor;
        }
        .confetti {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            top: 0; left: 0;
            background-image: url('data:image/svg+xml;utf8,<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="%23FFD700"/></svg>');
            animation: pop 1s ease-out;
            opacity: 0;
        }
        @keyframes pop {
            0% { transform: scale(0); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }
        .levelup-banner {
            background: #ffd700;
            color: #000;
            font-weight: bold;
            padding: 10px;
            letter-spacing: 2px;
            text-transform: uppercase;
            animation: slideDown 0.5s ease-out;
        }
        @keyframes slideDown {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="result-card p-5">
        
        <?php if($levelup): ?>
            <div class="levelup-banner mb-4 shadow-sm">
                🎉 LEVEL UP! 🎉
            </div>
            <div class="confetti"></div>
        <?php endif; ?>

        <h5 class="text-muted text-uppercase fw-bold letter-spacing-1 mb-3">Quest Complete</h5>
        
        <div class="rank-badge <?php echo $color; ?>">
            <?php echo htmlspecialchars($rank); ?>
        </div>
        
        <h2 class="fw-bold mb-4"><?php echo $message; ?></h2>

        <div class="score-circle <?php echo $color; ?> bg-light">
            <?php echo $score; ?>%
        </div>

        <div class="<?php echo $bg_class; ?> p-3 rounded-4 mb-4 border border-opacity-10 border-dark">
            <h4 class="mb-0 fw-bold text-dark">
                +<?php echo $xp; ?> XP <span class="fs-6 text-muted">Earned</span>
            </h4>
        </div>
        
        <div class="d-grid gap-2">
            <a href="dashboard.php" class="btn btn-dark btn-lg rounded-pill fw-bold shadow-sm">
                🏠 Return to HQ
            </a>
            <a href="javascript:history.back()" class="btn btn-link text-muted text-decoration-none">
                Try Again
            </a>
        </div>

    </div>

</body>
</html>