<?php
// student/submit_quiz.php
session_start();
require_once '../config/db.php';
require_once '../config/gamification.php'; // <--- Import the Gamification Logic

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_SESSION['user_id'];
    $quiz_id = $_POST['quiz_id'];
    $answers = $_POST['answers'] ?? []; // Array of answers from form
    
    // 1. Fetch Correct Answers from Database
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_questions = count($questions);
    $correct_count = 0;
    
    foreach ($questions as $q) {
        $q_id = $q['question_id'];
        
        // Ensure the student actually answered this question
        if (isset($answers[$q_id])) {
            $user_answer = trim($answers[$q_id]); // Clean whitespace
            $correct_answer = trim($q['correct_option']);

            // LOGIC SPLIT:
            // A. Fill-in-the-Blank (Input): Case-Insensitive Check
            if ($q['question_type'] === 'input') {
                if (strtolower($user_answer) === strtolower($correct_answer)) {
                    $correct_count++;
                }
            } 
            // B. Multiple Choice (Text/Image/Audio): Exact Match (a, b, c, d)
            else {
                if ($user_answer === $correct_answer) {
                    $correct_count++;
                }
            }
        }
    }
    
    // 2. Calculate Percentage Score
    $score_percent = ($total_questions > 0) ? round(($correct_count / $total_questions) * 100) : 0;
    
    // 3. Get Rewards (XP & Rank) using gamification.php
    $reward = calculateReward($score_percent); // Returns array like ['xp' => 50, 'rank' => 'B']
    $earned_xp = $reward['xp'];
    $rank_achieved = $reward['rank']; // Capture rank for result page
    
    // 4. Save Result to History
    $stmt = $pdo->prepare("INSERT INTO quiz_results (student_id, quiz_id, score, attempt_date) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$student_id, $quiz_id, $score_percent]);
    
    // 5. Update Student Progress (Level & XP)
    // A. Fetch current stats
    $stmt = $pdo->prepare("SELECT * FROM student_progress WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $progress = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$progress) {
        // Create row if missing (Safety check)
        $pdo->prepare("INSERT INTO student_progress (student_id, current_level, current_xp) VALUES (?, 1, 0)")->execute([$student_id]);
        $progress = ['current_level' => 1, 'current_xp' => 0];
    }
    
    // B. Add new XP
    $new_total_xp = $progress['current_xp'] + $earned_xp;
    $current_level = $progress['current_level'];
    
    // C. Check for Level Up using gamification.php
    $level_data = checkLevelUp($current_level, $new_total_xp);
    
    // D. Update Database with new Level/XP
    $stmt = $pdo->prepare("UPDATE student_progress SET current_level = ?, current_xp = ? WHERE student_id = ?");
    $stmt->execute([$level_data['level'], $level_data['xp'], $student_id]);
    
    // 6. Redirect to Result Page
    // Pass info via URL to show "Level Up" animation or Rank
    $leveled_up = ($level_data['level'] > $current_level) ? 1 : 0;
    
    // Encode rank to be URL safe
    $safe_rank = urlencode($rank_achieved);
    
    header("Location: result.php?score=$score_percent&xp=$earned_xp&levelup=$leveled_up&rank=$safe_rank");
    exit;
}
?>