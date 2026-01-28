<?php
// admin/manage_quizzes.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$msg_type = ""; // success or danger

// --- 1. HANDLE ADD QUIZ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_quiz'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $created_by = $_SESSION['user_id'];
    
    // [FIX] PREVENT DUPLICATE QUIZZES
    $check = $pdo->prepare("SELECT quiz_id FROM quizzes WHERE title = ?");
    $check->execute([$title]);
    $exists = $check->fetch();

    if ($exists) {
        $message = "⚠️ A quest with this title already exists! Please edit the existing one.";
        $msg_type = "warning";
    } else {
        // Handle Thumbnail Upload
        $thumbnail = 'default_quiz.png';
        if (!empty($_FILES['thumbnail']['name'])) {
            $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . "_qthumb." . $ext;
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], "../uploads/" . $filename);
            $thumbnail = $filename;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO quizzes (title, description, file_path, thumbnail, created_by) VALUES (?, ?, 'take_quiz.php', ?, ?)");
            $stmt->execute([$title, $description, $thumbnail, $created_by]);
            
            $new_id = $pdo->lastInsertId();
            
            // Redirect to Add Questions immediately
            header("Location: add_questions.php?quiz_id=$new_id"); 
            exit;

        } catch (PDOException $e) {
            $message = "❌ Error: " . $e->getMessage();
            $msg_type = "danger";
        }
    }
}

// --- 2. HANDLE DELETE QUIZ (FIXED) ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // [FIX] DELETE RESULTS FIRST (To prevent Foreign Key Error)
        $pdo->prepare("DELETE FROM quiz_results WHERE quiz_id = ?")->execute([$id]);
        
        // Questions usually delete via Cascade, but let's be safe
        $pdo->prepare("DELETE FROM questions WHERE quiz_id = ?")->execute([$id]);

        // NOW delete the quiz
        $pdo->prepare("DELETE FROM quizzes WHERE quiz_id = ?")->execute([$id]);
        
        $message = "🗑️ Quest deleted successfully.";
        $msg_type = "success";
    } catch (PDOException $e) {
        $message = "❌ Could not delete: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// --- 3. FETCH EXISTING QUIZZES ---
$stmt = $pdo->query("SELECT * FROM quizzes ORDER BY quiz_id DESC");
$quizzes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Quests - Game Master</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
        <h2 class="fw-bold text-primary mb-4">⚔️ Manage Quests</h2>

        <?php if($message): ?>
            <div class="alert alert-<?php echo $msg_type ?: 'info'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-5">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-success">➕ Create New Quest</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Quest Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Animal Sounds" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Cover Image</label>
                        <input type="file" name="thumbnail" class="form-control">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="What will the hero learn?"></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" name="add_quiz" class="btn btn-success fw-bold px-4">
                            Create Quest & Add Questions ➡️
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <h4 class="fw-bold text-secondary mb-3">📜 Active Quests</h4>
        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Quest</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($quizzes as $q): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="d-flex align-items-center">
                                        <img src="../uploads/<?php echo $q['thumbnail']; ?>" class="rounded me-3" width="50" height="50" style="object-fit:cover;">
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($q['title']); ?></div>
                                            <small class="text-muted">ID: <?php echo $q['quiz_id']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-muted small"><?php echo htmlspecialchars(substr($q['description'], 0, 50)); ?>...</td>
                                <td>
                                    <a href="add_questions.php?quiz_id=<?php echo $q['quiz_id']; ?>" class="btn btn-sm btn-primary">
                                        ✏️ Add/Edit Questions
                                    </a>
                                    <a href="?delete=<?php echo $q['quiz_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('⚠️ WARNING: This will delete the quiz AND all student results for it. Are you sure?');">
                                        🗑️ Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

</body>
</html>