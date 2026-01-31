<?php
// teacher/students.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];

// 1. Fetch Students in ANY of this teacher's classes
// Group by student to avoid duplicates if they are in multiple classes with same teacher
$sql = "SELECT u.user_id, u.full_name, u.email, u.profile_picture, u.role, 
               p.current_level, p.current_xp,
               GROUP_CONCAT(DISTINCT c.class_name ORDER BY c.class_name SEPARATOR ', ') as classes
        FROM users u
        JOIN enrollments e ON u.user_id = e.student_id
        JOIN classes c ON e.class_id = c.class_id
        LEFT JOIN student_progress p ON u.user_id = p.student_id
        WHERE c.teacher_id = ?
        GROUP BY u.user_id";
$stmt = $pdo->prepare($sql);
$stmt->execute([$teacher_id]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Handle Parent Alert (Simulation)
if (isset($_POST['send_alert'])) {
    // In a real app, this would insert into parent_alerts table
    $msg = "Alert sent to parent of " . $_POST['student_name'];
    echo "<script>alert('$msg');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Students</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <h2 class="fw-bold mb-4">🎓 My Students</h2>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Student</th>
                                <th>Class(es)</th>
                                <th>Level / XP</th>
                                <th>Subscription</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $s): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <?php $pic = "../uploads/".$s['profile_picture']; ?>
                                            <img src="<?php echo $pic; ?>" class="rounded-circle me-3 border" width="40" height="40" style="object-fit:cover;">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($s['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($s['classes']); ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-warning text-dark me-2">Lv.<?php echo $s['current_level'] ?? 1; ?></span>
                                            <small class="text-muted"><?php echo $s['current_xp'] ?? 0; ?> XP</small>
                                        </div>
                                    </td>
                                    <td>
                                        <!-- Optional: Check expiry if passed in query, for now placeholder -->
                                        <span class="badge bg-success">Active</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="student_name" value="<?php echo htmlspecialchars($s['full_name']); ?>">
                                            <button type="submit" name="send_alert" class="btn btn-sm btn-outline-danger" title="Send Parent Alert">
                                                🚨 Alert
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(empty($students)): ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No students assigned to your classes yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
