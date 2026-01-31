<?php
// admin/schedule.php
session_start();
require_once '../config/db.php';

// Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// FETCH SCHEDULE
$sql = "SELECT c.class_name, c.zoom_link, s.name as subject_name, u.full_name as teacher_name, 
               sch.day_of_week, sch.start_time, sch.end_time
        FROM classes c
        JOIN subjects s ON c.subject_id = s.subject_id
        JOIN users u ON c.teacher_id = u.user_id
        JOIN class_schedule sch ON c.class_id = sch.class_id
        ORDER BY sch.start_time";
$classes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// ORGANIZE BY DAY
$schedule = [
    'Monday' => [], 'Tuesday' => [], 'Wednesday' => [], 'Thursday' => [], 'Friday' => [], 'Saturday' => [], 'Sunday' => []
];

foreach ($classes as $c) {
    $schedule[$c['day_of_week']][] = $c;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Master Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .class-card {
            border-left: 4px solid #0d6efd;
            background: #fff;
            transition: transform 0.2s;
        }
        .class-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .day-column {
            min-height: 200px;
            background: #f8f9fa;
            border-radius: 10px;
            padding: 10px;
        }
    </style>
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">📅 Master Schedule</h2>
            <a href="manage_classes.php" class="btn btn-primary fw-bold">➕ Add Class / Slot</a>
        </div>

        <div class="row g-3" style="overflow-x: auto; flex-wrap: nowrap;">
            <?php foreach ($schedule as $day => $slots): ?>
                <div class="col-md-3" style="min-width: 250px;">
                    <div class="day-column shadow-sm">
                        <h5 class="text-center fw-bold mb-3 border-bottom pb-2"><?php echo $day; ?></h5>
                        
                        <?php if (empty($slots)): ?>
                            <div class="text-center text-muted small py-4">No classes</div>
                        <?php else: ?>
                            <?php foreach ($slots as $slot): ?>
                                <div class="class-card p-3 mb-3 rounded shadow-sm border-0">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="badge bg-primary"><?php echo $slot['subject_name']; ?></span>
                                        <small class="text-dark fw-bold">
                                            <?php echo date('g:i A', strtotime($slot['start_time'])); ?>
                                        </small>
                                    </div>
                                    <h6 class="fw-bold mb-1 text-truncate"><?php echo htmlspecialchars($slot['class_name']); ?></h6>
                                    <div class="text-muted small mb-2">👨‍🏫 <?php echo htmlspecialchars($slot['teacher_name']); ?></div>
                                    <div class="small text-secondary">
                                        clock: <?php echo date('H:i', strtotime($slot['start_time'])); ?> - <?php echo date('H:i', strtotime($slot['end_time'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
