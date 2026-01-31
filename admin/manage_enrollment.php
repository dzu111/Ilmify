<?php
// admin/manage_enrollment.php
session_start();
require_once '../config/db.php';

// Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$class_id = $_GET['class_id'] ?? 0;
if (!$class_id) {
    header("Location: manage_classes.php"); // Fallback if no class selected
    exit;
}

// FETCH CLASS INFO
$stmt = $pdo->prepare("SELECT * FROM classes WHERE class_id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) die("Class not found.");

// HANDLE ADD STUDENT
if (isset($_POST['add_student'])) {
    $student_id = $_POST['student_id'];
    // Check if exists
    $check = $pdo->prepare("SELECT enrollment_id FROM enrollments WHERE class_id = ? AND student_id = ?");
    $check->execute([$class_id, $student_id]);
    if (!$check->fetch()) {
        $pdo->prepare("INSERT INTO enrollments (class_id, student_id) VALUES (?, ?)")->execute([$class_id, $student_id]);
        $success = "Student enrolled successfully!";
    } else {
        $error = "Student already in this class.";
    }
}

// HANDLE REMOVE STUDENT
if (isset($_GET['remove_student'])) {
    $sid = $_GET['remove_student'];
    $pdo->prepare("DELETE FROM enrollments WHERE class_id = ? AND student_id = ?")->execute([$class_id, $sid]);
    header("Location: manage_enrollment.php?class_id=$class_id&msg=removed");
    exit;
}

// FETCH ENROLLED STUDENTS
$enrolled_sql = "SELECT u.user_id, u.full_name, u.email, e.enrolled_at 
                 FROM enrollments e 
                 JOIN users u ON e.student_id = u.user_id 
                 WHERE e.class_id = ? 
                 ORDER BY u.full_name";
$stmt = $pdo->prepare($enrolled_sql);
$stmt->execute([$class_id]);
$enrolled_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FETCH AVAILABLE STUDENTS (Not enrolled)
$enrolled_ids = array_column($enrolled_students, 'user_id');
$enrolled_ids[] = 0; // Avoid empty array SQL error
$placeholders = str_repeat('?,', count($enrolled_ids) - 1) . '?';

$avail_sql = "SELECT user_id, full_name, email FROM users 
              WHERE role = 'student' 
              AND user_id NOT IN ($placeholders) 
              ORDER BY full_name";
$stmt = $pdo->prepare($avail_sql);
$stmt->execute($enrolled_ids);
$available_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Enrollment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">
                <a href="manage_classes.php" class="text-decoration-none text-muted">Classes</a>
                <span class="text-muted mx-2">/</span>
                <?php echo htmlspecialchars($class['class_name']); ?>
            </h2>
            <div class="btn-group">
                <a href="manage_classes.php" class="btn btn-outline-secondary">Back to List</a>
            </div>
        </div>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg']=='removed'): ?>
            <div class="alert alert-warning">Student removed from class.</div>
        <?php endif; ?>

        <div class="row">
            <!-- Left: Add Student -->
            <div class="col-md-5">
                <div class="card shadow-sm border-0 mb-4 h-100">
                    <div class="card-header bg-success text-white fw-bold">
                         ➕ Enroll Student
                    </div>
                    <div class="card-body">
                        <?php if (count($available_students) > 0): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Select Student</label>
                                    <select name="student_id" class="form-select" size="10" required>
                                        <?php foreach($available_students as $s): ?>
                                            <option value="<?php echo $s['user_id']; ?>">
                                                <?php echo htmlspecialchars($s['full_name']); ?> (<?php echo $s['email']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Select a student to add to this class.</div>
                                </div>
                                <button type="submit" name="add_student" class="btn btn-success w-100 fw-bold">Add to Class ➤</button>
                            </form>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <p>✅ All active students are enrolled.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Enrolled List -->
            <div class="col-md-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold d-flex justify-content-between">
                        <span>📋 Enrolled Students</span>
                        <span class="badge bg-primary rounded-pill"><?php echo count($enrolled_students); ?></span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Student Name</th>
                                    <th>Enrolled Date</th>
                                    <th class="text-end pe-3">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($enrolled_students as $e): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold"><?php echo htmlspecialchars($e['full_name']); ?></div>
                                            <small class="text-muted"><?php echo $e['email']; ?></small>
                                        </td>
                                        <td class="small text-muted">
                                            <?php echo date('M d, Y', strtotime($e['enrolled_at'])); ?>
                                        </td>
                                        <td class="text-end pe-3">
                                            <a href="manage_enrollment.php?class_id=<?php echo $class_id; ?>&remove_student=<?php echo $e['user_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Remove student from this class?');">
                                                ❌ Remove
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(count($enrolled_students) == 0): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                            No students yet.
                                        </td>
                                    </tr>
                                <?php endif; ?>
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
