<?php
// teacher/gradebook.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['active_class_id'])) {
    header("Location: dashboard.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$class_id = $_SESSION['active_class_id'];

// Get class info
$stmt = $pdo->prepare("SELECT c.*, s.name as subject_name FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.class_id = ? AND c.teacher_id = ?");
$stmt->execute([$class_id, $teacher_id]);
$class = $stmt->fetch();

if (!$class) {
    header("Location: dashboard.php");
    exit;
}

// Get all enrolled students with details
$students_query = "
    SELECT 
        u.user_id,
        u.full_name,
        u.email,
        u.last_login_at,
        u.parent_phone,
        e.enrolled_at,
        (SELECT COUNT(*) FROM quiz_attempts qa 
         JOIN quizzes q ON qa.quiz_id = q.quiz_id 
         WHERE qa.student_id = u.user_id AND q.week_id IN (
            SELECT week_id FROM weeks WHERE subject_id = ?
         )) as total_attempts,
        (SELECT AVG(qa.score) FROM quiz_attempts qa 
         JOIN quizzes q ON qa.quiz_id = q.quiz_id 
         WHERE qa.student_id = u.user_id AND q.week_id IN (
            SELECT week_id FROM weeks WHERE subject_id = ?
         )) as avg_score
    FROM enrollments e
    JOIN users u ON e.student_id = u.user_id
    WHERE e.class_id = ?
    ORDER BY u.full_name
";
$stmt = $pdo->prepare($students_query);
$stmt->execute([$class['subject_id'], $class['subject_id'], $class_id]);
$students = $stmt->fetchAll();

// Function to get recent quiz scores for a student
function getRecentScores($pdo, $student_id, $subject_id) {
    $stmt = $pdo->prepare("
        SELECT qa.score, q.title, qa.completed_at
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.quiz_id
        JOIN weeks w ON q.week_id = w.week_id
        WHERE qa.student_id = ? AND w.subject_id = ?
        ORDER BY qa.completed_at DESC
        LIMIT 3
    ");
    $stmt->execute([$student_id, $subject_id]);
    return $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gradebook - <?php echo htmlspecialchars($class['class_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #E0F7FA;
            font-family: 'Nunito', sans-serif;
        }
        
        .student-row {
            transition: all 0.2s;
        }
        
        .student-row:hover {
            background-color: #f8f9fa;
            transform: scale(1.01);
        }
        
        .score-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-block;
            margin: 2px;
        }
        
        .score-high { background: #d4edda; color: #155724; }
        .score-medium { background: #fff3cd; color: #856404; }
        .score-low { background: #f8d7da; color: #721c24; }
        
        .login-status {
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .login-recent { color: #28a745; }
        .login-old { color: #dc3545; }
        
        .whatsapp-btn {
            background: #25D366;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .whatsapp-btn:hover {
            background: #128C7E;
            transform: scale(1.05);
            color: white;
        }
        
        .game-btn {
            background: #FFD93D;
            border: 3px solid #ffcc00;
            color: #5a4a00;
            font-weight: 800;
            border-radius: 50px;
            padding: 8px 20px;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">My Classes</a></li>
                <li class="breadcrumb-item"><a href="curriculum_view.php"><?php echo htmlspecialchars($class['class_name']); ?></a></li>
                <li class="breadcrumb-item active">Gradebook</li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold">📊 Gradebook: <?php echo htmlspecialchars($class['class_name']); ?></h2>
                <p class="text-muted">Track student progress and communicate with parents</p>
            </div>
            <div>
                <span class="badge bg-primary fs-5"><?php echo count($students); ?> Students</span>
            </div>
        </div>

        <?php if (empty($students)): ?>
            <div class="alert alert-info text-center py-5">
                <h4>No students enrolled yet</h4>
                <p class="mb-0">Students will appear here once they're enrolled in this class.</p>
            </div>
        <?php else: ?>
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4" style="width: 25%;">Student Name</th>
                                    <th style="width: 20%;">Last Login</th>
                                    <th style="width: 30%;">Recent Quiz Scores</th>
                                    <th style="width: 15%;">Average</th>
                                    <th style="width: 10%;">Contact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($students as $student): 
                                    $recentScores = getRecentScores($pdo, $student['user_id'], $class['subject_id']);
                                    
                                    // Calculate time since last login
                                    $lastLogin = $student['last_login_at'];
                                    $loginText = "Never";
                                    $loginClass = "login-old";
                                    
                                    if ($lastLogin) {
                                        $diff = time() - strtotime($lastLogin);
                                        if ($diff < 3600) {
                                            $loginText = floor($diff / 60) . " min ago";
                                            $loginClass = "login-recent";
                                        } elseif ($diff < 86400) {
                                            $loginText = floor($diff / 3600) . " hrs ago";
                                            $loginClass = "login-recent";
                                        } elseif ($diff < 604800) {
                                            $loginText = floor($diff / 86400) . " days ago";
                                            $loginClass = "login-old";
                                        } else {
                                            $loginText = date('M j', strtotime($lastLogin));
                                            $loginClass = "login-old";
                                        }
                                    }
                                ?>
                                    <tr class="student-row">
                                        <td class="ps-4">
                                            <div class="fw-bold"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($student['email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="login-status <?php echo $loginClass; ?>">
                                                🕒 <?php echo $loginText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($recentScores)): ?>
                                                <?php foreach($recentScores as $score): 
                                                    $scoreValue = $score['score'];
                                                    $scoreClass = $scoreValue >= 80 ? 'score-high' : ($scoreValue >= 60 ? 'score-medium' : 'score-low');
                                                ?>
                                                    <span class="score-badge <?php echo $scoreClass; ?>" title="<?php echo htmlspecialchars($score['title']); ?>">
                                                        <?php echo $scoreValue; ?>%
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">No attempts</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['avg_score']): ?>
                                                <strong class="fs-5"><?php echo number_format($student['avg_score'], 1); ?>%</strong>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($student['parent_phone']): 
                                                $phone = preg_replace('/[^0-9]/', '', $student['parent_phone']);
                                                $teacherName = $_SESSION['full_name'] ?? 'Teacher';
                                                $studentName = $student['full_name'];
                                                $className = $class['class_name'];
                                                
                                                $message = urlencode("Hello! This is $teacherName from $className. I wanted to discuss $studentName's progress in class.");
                                                $whatsappLink = "https://wa.me/$phone?text=$message";
                                            ?>
                                                <a href="<?php echo $whatsappLink; ?>" target="_blank" class="whatsapp-btn">
                                                    📱 Alert
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">No contact</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Summary Stats -->
            <div class="row g-4 mt-4">
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-success text-white">
                        <div class="card-body text-center p-4">
                            <h3 class="display-6 fw-bold">
                                <?php 
                                $activeCount = 0;
                                foreach($students as $s) {
                                    if ($s['last_login_at'] && (time() - strtotime($s['last_login_at']) < 604800)) {
                                        $activeCount++;
                                    }
                                }
                                echo $activeCount;
                                ?>
                            </h3>
                            <p class="mb-0">Active This Week</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-primary text-white">
                        <div class="card-body text-center p-4">
                            <h3 class="display-6 fw-bold">
                                <?php 
                                $totalAttempts = array_sum(array_column($students, 'total_attempts'));
                                echo $totalAttempts;
                                ?>
                            </h3>
                            <p class="mb-0">Total Quiz Attempts</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 bg-warning">
                        <div class="card-body text-center p-4">
                            <h3 class="display-6 fw-bold">
                                <?php 
                                $avgScores = array_filter(array_column($students, 'avg_score'));
                                $classAvg = !empty($avgScores) ? array_sum($avgScores) / count($avgScores) : 0;
                                echo number_format($classAvg, 1);
                                ?>%
                            </h3>
                            <p class="mb-0">Class Average</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
