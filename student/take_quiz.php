<?php
// student/take_quiz.php
session_start();
require_once '../config/db.php';

if (!isset($_GET['id'])) die("❌ No battle selected!");
$quiz_id = $_GET['id'];

// 1. Fetch Quiz Info
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

$is_embedded = (isset($_GET['embedded']) && $_GET['embedded'] == 'true');

// 2. Fetch Questions
$stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_q = count($questions);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚔️ Battle: <?php echo htmlspecialchars($quiz['title']); ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body { 
            background: <?php echo $is_embedded ? 'transparent' : 'linear-gradient(135deg, #FFDEE9 0%, #B5FFFC 100%)'; ?>; 
            font-family: 'Fredoka', sans-serif;
            min-height: 100vh;
            padding-bottom: <?php echo $is_embedded ? '0' : '50px'; ?>;
        }

        /* Battle Header */
        .battle-header {
            background: white;
            border-radius: 0 0 30px 30px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            padding: 30px;
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
        }
        .battle-header::before {
            content: "⚔️";
            font-size: 10rem;
            position: absolute;
            top: -20px; right: -20px;
            opacity: 0.1;
            transform: rotate(20deg);
        }

        /* Question Card */
        .q-card {
            background: white;
            border-radius: 25px;
            border: none;
            box-shadow: 0 8px 0 #efefef; /* 3D effect */
            margin-bottom: 40px;
            transition: transform 0.3s;
            overflow: hidden;
        }
        .q-card:hover { transform: translateY(-5px); }
        
        .q-badge {
            background: #FF6B6B;
            color: white;
            font-weight: bold;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 1.1rem;
            margin-bottom: 15px;
            display: inline-block;
        }

        /* Options Styling */
        .option-btn {
            border: 3px solid #eee;
            border-radius: 15px;
            background: white;
            text-align: left;
            padding: 15px 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 1.2rem;
            color: #555;
            display: block;
            width: 100%;
            position: relative;
        }
        
        /* Hide Radio Buttons */
        .option-btn input { display: none; }

        /* Hover Effect */
        .option-btn:hover {
            border-color: #4ECDC4;
            background: #f0fffe;
            transform: scale(1.02);
        }

        /* Selected State */
        .option-btn input:checked + span {
            font-weight: bold;
            color: #1a535c;
        }
        .option-btn:has(input:checked) {
            border-color: #4ECDC4;
            background-color: #e0fffc;
            box-shadow: 0 4px 0 #4ECDC4;
        }

        /* Image Options */
        .img-option {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 12px;
            border: 4px solid transparent;
            transition: 0.2s;
        }
        .option-btn:has(input:checked) .img-option {
            border-color: #4ECDC4;
            transform: scale(0.95);
        }

        /* Audio Button */
        .audio-btn {
            width: 80px; height: 80px;
            border-radius: 50%;
            background: #FFE66D;
            border: 4px solid #FFD93D;
            font-size: 30px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            margin: 0 auto 20px auto;
        }
        .audio-btn:active { transform: scale(0.9); }

        /* Submit Button */
        .btn-finish {
            background: #1A535C;
            color: white;
            font-size: 1.5rem;
            padding: 15px 50px;
            border-radius: 50px;
            border: none;
            box-shadow: 0 6px 0 #0f3a42;
            transition: 0.2s;
        }
        .btn-finish:hover { background: #236e7a; transform: translateY(-3px); }
        .btn-finish:active { transform: translateY(2px); box-shadow: none; }

        /* Progress Bar */
        .progress-container {
            position: fixed; top: 0; left: 0; width: 100%; height: 10px; z-index: 100; background: rgba(255,255,255,0.5);
        }
        .progress-bar-fill {
            height: 100%; background: #FF6B6B; width: 0%; transition: width 0.5s;
        }
    </style>
</head>
<body>

<div class="progress-container">
    <div class="progress-bar-fill" id="progressBar"></div>
</div>

<div class="container">
    
    <?php if(!$is_embedded): ?>
    <div class="battle-header">
        <h1 class="fw-bold" style="color: #1A535C;"><?php echo htmlspecialchars($quiz['title']); ?></h1>
        <p class="mb-0 text-muted fs-5">
            🎯 <?php echo $total_q; ?> Questions • ⏳ Take your time!
        </p>
    </div>
    <?php else: ?>
    <div class="text-center mb-4 pt-3">
         <span class="badge bg-warning text-dark fs-5">⚔️ <?php echo htmlspecialchars($quiz['title']); ?></span>
    </div>
    <?php endif; ?>

    <form action="submit_quiz.php" method="POST" id="quizForm">
        <input type="hidden" name="quiz_id" value="<?php echo $quiz_id; ?>">

        <?php foreach($questions as $index => $q): ?>
            <div class="q-card p-4 p-md-5">
                <div class="text-center">
                    <span class="q-badge">Question <?php echo $index + 1; ?></span>
                    
                    <h2 class="fw-bold mb-4" style="color: #2D3436;">
                        <?php echo htmlspecialchars($q['question_text']); ?>
                    </h2>

                    <?php if ($q['question_type'] === 'audio'): ?>
                        <div class="d-flex justify-content-center">
                            <div class="audio-btn shadow-sm" onclick="playAudio('audio_<?php echo $q['question_id']; ?>')">
                                🔊
                            </div>
                            <audio id="audio_<?php echo $q['question_id']; ?>" src="../uploads/audio/<?php echo $q['media_file']; ?>"></audio>
                        </div>
                        <p class="text-muted small">Click button to listen!</p>
                        
                        <div class="row g-3 justify-content-center">
                            <?php foreach(['a', 'b', 'c', 'd'] as $opt): ?>
                                <div class="col-md-6">
                                    <label class="option-btn">
                                        <input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="<?php echo $opt; ?>" onchange="updateProgress()" required>
                                        <span><?php echo htmlspecialchars($q["option_$opt"]); ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($q['question_type'] === 'image'): ?>
                        <div class="row g-3">
                            <?php foreach(['a', 'b', 'c', 'd'] as $opt): ?>
                                <div class="col-6 col-md-3">
                                    <label class="option-btn p-2 text-center h-100">
                                        <input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="<?php echo $opt; ?>" onchange="updateProgress()" required>
                                        <img src="../uploads/images/<?php echo $q["option_$opt"]; ?>" class="img-option mb-2 shadow-sm">
                                        <span class="d-block small text-muted">Select Me</span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>

                    <?php elseif ($q['question_type'] === 'input'): ?>
                        <div class="col-md-8 mx-auto">
                            <input type="text" name="answers[<?php echo $q['question_id']; ?>]" 
                                   class="form-control form-control-lg text-center p-3 border-3 rounded-pill" 
                                   placeholder="✨ Type your answer here..." 
                                   style="border-color: #4ECDC4; background: #f9f9f9;"
                                   oninput="updateProgress()" required>
                        </div>

                    <?php else: ?>
                        <?php if(!empty($q['media_file'])): ?>
                            <img src="../uploads/images/<?php echo $q['media_file']; ?>" class="img-fluid rounded-4 shadow mb-4" style="max-height: 250px;">
                        <?php endif; ?>

                        <div class="row g-3 justify-content-center">
                            <?php foreach(['a', 'b', 'c', 'd'] as $opt): ?>
                                <div class="col-md-6">
                                    <label class="option-btn">
                                        <input type="radio" name="answers[<?php echo $q['question_id']; ?>]" value="<?php echo $opt; ?>" onchange="updateProgress()" required>
                                        <span><?php echo htmlspecialchars($q["option_$opt"]); ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        <?php endforeach; ?>

        <div class="text-center pb-5 pt-3">
            <button type="submit" class="btn-finish">
                🚀 Blast Off! (Submit)
            </button>
        </div>
    </form>
</div>

<script>
    function playAudio(id) {
        document.getElementById(id).play();
    }

    // Simple Progress Bar Logic
    function updateProgress() {
        const total = <?php echo $total_q; ?>;
        // Count how many radio groups have a checked input OR text inputs with value
        const radios = document.querySelectorAll('input[type="radio"]:checked').length;
        const texts = Array.from(document.querySelectorAll('input[type="text"]')).filter(i => i.value.trim() !== "").length;
        
        const answered = radios + texts;
        const pct = (answered / total) * 100;
        document.getElementById('progressBar').style.width = pct + "%";
    }
</script>

</body>
</html>