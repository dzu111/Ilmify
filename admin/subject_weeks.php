<?php
// admin/subject_weeks.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$subject_id = $_GET['subject_id'] ?? 0;

// Handle CRUD
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'delete') {
        $id = $_POST['week_id'];
        $stmt = $pdo->prepare("DELETE FROM weeks WHERE week_id = ?");
        $stmt->execute([$id]);
        $msg = "Week deleted!";
    } elseif ($_POST['action'] == 'edit') {
        $id = $_POST['week_id'];
        $title = $_POST['title'];
        $stmt = $pdo->prepare("UPDATE weeks SET title = ? WHERE week_id = ?");
        $stmt->execute([$title, $id]);
        $msg = "Week updated!";
    } elseif ($_POST['action'] == 'create') {
        $title = $_POST['title'];
        // Get max sort order
        $sortStmt = $pdo->prepare("SELECT MAX(sort_order) as max_order FROM weeks WHERE subject_id = ?");
        $sortStmt->execute([$subject_id]);
        $maxOrder = $sortStmt->fetchColumn() ?: 0;
        
        $stmt = $pdo->prepare("INSERT INTO weeks (subject_id, title, sort_order) VALUES (?, ?, ?)");
        $stmt->execute([$subject_id, $title, $maxOrder + 1]);
        $msg = "Week created successfully!";
    }
}

// Fetch Subject Details
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$subject) die("Subject not found!");

// Fetch Weeks
$stmt = $pdo->prepare("SELECT * FROM weeks WHERE subject_id = ? ORDER BY sort_order ASC");
$stmt->execute([$subject_id]);
$weeks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weeks - <?php echo htmlspecialchars($subject['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #E0F7FA;
            font-family: 'Nunito', sans-serif;
        }
        .timeline {
            position: relative;
            max-width: 800px;
            margin: 0 auto;
        }
        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background-color: #4ECDC4;
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
            border-radius: 10px;
        }
        .week-card {
            padding: 20px 30px;
            background-color: white;
            position: relative;
            border-radius: 20px;
            border: 4px solid #fff;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            width: 45%;
            transition: 0.3s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .week-card:hover {
            transform: scale(1.05);
            border-color: #FF6B6B;
            z-index: 10;
        }
        .left { left: 0; }
        .right { 
            left: 50%; 
            margin-left: 30px; /* Add spacing from center line */
        }
        
        .timeline-badge {
            position: absolute;
            width: 50px;
            height: 50px;
            right: -25px;
            background-color: #FF6B6B;
            border: 4px solid #fff;
            top: 20px;
            border-radius: 50%;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 1.2rem;
        }
        .right .timeline-badge { left: -25px; }

        .game-btn-sm {
            background: #4ECDC4;
            color: white;
            font-weight: bold;
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 0.9rem;
            display: inline-block;
            margin-top: 10px;
        }

        .action-actions {
            position: absolute;
            top: -15px;
            z-index: 20;
            display: flex;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .action-actions.left {
            left: 20px;
        }
        .action-actions.right {
            right: 20px;
        }
        .week-card-wrapper:hover .action-actions {
            opacity: 1;
        }
        .action-btn {
            background: white;
            border: 2px solid #dee2e6;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .action-btn.edit:hover {
            border-color: #4ECDC4;
            background: #4ECDC4;
            color: white;
        }
        .action-btn.delete:hover {
            border-color: #FF6B6B;
            background: #FF6B6B;
            color: white;
        }

        /* Mobile Responsive */
        @media screen and (max-width: 600px) {
            .timeline::after { left: 31px; }
            .week-card { width: 100%; padding-left: 70px; padding-right: 25px; }
            .week-card::before { left: 60px; border: medium solid white; border-width: 10px 10px 10px 0; border-color: transparent white transparent transparent; }
            .left::after, .right::after { left: 15px; }
            .right { left: 0%; }
            .timeline-badge { left: 6px; right: auto; }
        }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4" style="background-color: #E0F7FA;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($subject['name']); ?></h1>
                <p class="text-muted lead">Manage Weekly Content</p>
            </div>
            <a href="subjects.php" class="btn btn-outline-secondary rounded-pill">⬅ Back to Subjects</a>
        </div>

        <?php if(isset($msg)): ?>
            <div class="alert alert-success rounded-pill text-center fw-bold mb-4 w-50 mx-auto"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="timeline">
            <?php foreach($weeks as $index => $week): ?>
                <div class="week-card-wrapper" style="position: relative;">
                    <!-- Action Buttons Above Card -->
                    <div class="action-actions <?php echo $index % 2 == 0 ? 'left' : 'right'; ?>">
                        <button class="btn btn-sm action-btn edit" data-bs-toggle="modal" data-bs-target="#editWeek<?php echo $week['week_id']; ?>">✏️ Edit</button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete Week?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="week_id" value="<?php echo $week['week_id']; ?>">
                            <button class="btn btn-sm action-btn delete">🗑️ Delete</button>
                        </form>
                    </div>
                    
                    <a href="week_materials.php?week_id=<?php echo $week['week_id']; ?>" class="week-card <?php echo $index % 2 == 0 ? 'left' : 'right'; ?>">
                        <div class="timeline-badge"><?php echo $index + 1; ?></div>
                        <h4 class="fw-bold text-dark m-0"><?php echo htmlspecialchars($week['title']); ?></h4>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span class="text-muted small">Tap to Manage Content</span>
                            <span class="game-btn-sm">OPEN �</span>
                        </div>
                    </a>
                </div>

                <!-- Spacer -->
                <div style="height: 40px;"></div> 

                <!-- Edit Modal -->
                <div class="modal fade" id="editWeek<?php echo $week['week_id']; ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Week</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="edit">
                                    <input type="hidden" name="week_id" value="<?php echo $week['week_id']; ?>">
                                    <div class="mb-3">
                                        <label>Title</label>
                                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($week['title']); ?>" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Add Week Button -->
            <div class="text-center mt-5" style="position: relative; z-index: 2;">
                <button class="btn btn-warning btn-lg rounded-pill fw-bold shadow" data-bs-toggle="modal" data-bs-target="#addWeekModal">➕ Add New Week</button>
            </div>
        </div>
        
        <!-- Add Week Modal -->
        <div class="modal fade" id="addWeekModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Week</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Week Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g., Basic Shapes" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create Week</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
