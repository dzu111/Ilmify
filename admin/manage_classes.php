<?php
// admin/manage_classes.php
session_start();
require_once '../config/db.php';

// Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// HANDLE ADD CLASS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_class'])) {
    $name = $_POST['class_name'];
    $subject_id = $_POST['subject_id'];
    $teacher_id = $_POST['teacher_id'];
    $zoom = $_POST['zoom_link'] ?? '';
    
    $stmt = $pdo->prepare("INSERT INTO classes (class_name, subject_id, teacher_id, zoom_link) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $subject_id, $teacher_id, $zoom])) {
        // Add Schedule
        $class_id = $pdo->lastInsertId();
        $day = $_POST['day'];
        $start = $_POST['start_time'];
        $end = $_POST['end_time'];
        
        $pdo->prepare("INSERT INTO class_schedule (class_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)")
            ->execute([$class_id, $day, $start, $end]);
            
        $success = "Class created successfully!";
    } else {
        $error = "Failed to create class.";
    }
}

// HANDLE DELETE CLASS
if (isset($_POST['delete_class_id'])) {
    $del_id = $_POST['delete_class_id'];
    
    try {
        $pdo->beginTransaction();

        // 1. Delete Deep Dependencies (Attendance & Pushed Logs linked to Live Sessions)
        // We use a subquery to find sessions belonging to this class
        $pdo->prepare("DELETE FROM attendance WHERE session_id IN (SELECT session_id FROM live_sessions WHERE class_id = ?)")->execute([$del_id]);
        $pdo->prepare("DELETE FROM pushed_content_log WHERE session_id IN (SELECT session_id FROM live_sessions WHERE class_id = ?)")->execute([$del_id]);

        // 2. Delete Live Sessions
        $pdo->prepare("DELETE FROM live_sessions WHERE class_id = ?")->execute([$del_id]);

        // 3. Delete Schedule & Enrollments
        $pdo->prepare("DELETE FROM class_schedule WHERE class_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM enrollments WHERE class_id = ?")->execute([$del_id]);

        // 4. Delete Class
        $pdo->prepare("DELETE FROM classes WHERE class_id = ?")->execute([$del_id]);

        $pdo->commit();
        $success = "Class and all related data deleted successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to delete class: " . $e->getMessage(); // Display error safely
    }
}

// FETCH DATA
$classes = $pdo->query("SELECT c.*, s.name as subject_name, u.full_name as teacher_name, 
                        sch.day_of_week, sch.start_time, sch.end_time
                        FROM classes c
                        JOIN subjects s ON c.subject_id = s.subject_id
                        JOIN users u ON c.teacher_id = u.user_id
                        LEFT JOIN class_schedule sch ON c.class_id = sch.class_id
                        ORDER BY c.class_name")->fetchAll(PDO::FETCH_ASSOC);

$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$teachers = $pdo->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Classes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <h2 class="fw-bold mb-4">🏫 Class Management</h2>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Add Class Form -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-primary text-white fw-bold">
                         ➕ Create New Class
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Class Name</label>
                                <input type="text" name="class_name" class="form-control" placeholder="e.g. Math Form 4 Group A" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subject</label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">Select Subject...</option>
                                    <?php foreach($subjects as $s): ?>
                                        <option value="<?php echo $s['subject_id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Teacher</label>
                                <select name="teacher_id" class="form-select" required>
                                    <option value="">Select Teacher...</option>
                                    <?php foreach($teachers as $t): ?>
                                        <option value="<?php echo $t['user_id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <hr>
                            <h6 class="fw-bold text-secondary">📅 Weekly Schedule</h6>
                            <div class="mb-3">
                                <label class="form-label">Day</label>
                                <select name="day" class="form-select" required>
                                    <?php 
                                    $days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
                                    foreach($days as $d) echo "<option value='$d'>$d</option>";
                                    ?>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Start</label>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">End</label>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Zoom Link (Default)</label>
                                <input type="url" name="zoom_link" class="form-control" placeholder="https://zoom.us/...">
                            </div>

                            <button type="submit" name="add_class" class="btn btn-dark w-100">Create Class</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Class List -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">
                        📋 Existing Classes
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Class Name</th>
                                    <th>Subject & Teacher</th>
                                    <th>Schedule</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($classes as $c): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold"><?php echo htmlspecialchars($c['class_name']); ?></td>
                                        <td>
                                            <div class="badge bg-light text-dark border"><?php echo $c['subject_name']; ?></div>
                                            <div class="small text-muted mt-1">👨‍🏫 <?php echo $c['teacher_name']; ?></div>
                                        </td>
                                        <td>
                                            <?php if($c['day_of_week']): ?>
                                                <div class="text-primary fw-bold"><?php echo $c['day_of_week']; ?>s</div>
                                                <div class="small text-muted">
                                                    <?php echo date('h:i A', strtotime($c['start_time'])); ?> - 
                                                    <?php echo date('h:i A', strtotime($c['end_time'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">No schedule set</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="manage_enrollment.php?class_id=<?php echo $c['class_id']; ?>" class="btn btn-sm btn-outline-success">Students</a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this class? This will also remove the schedule.');">
                                                <input type="hidden" name="delete_class_id" value="<?php echo $c['class_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
