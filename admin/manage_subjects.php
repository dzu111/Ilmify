<?php
// admin/manage_subjects.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$error = "";

// --- 1. HANDLE ADD SUBJECT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $name = trim($_POST['name']);
    $desc = trim($_POST['description']);
    
    // Check if exists
    $check = $pdo->prepare("SELECT subject_id FROM subjects WHERE name = ?");
    $check->execute([$name]);
    if ($check->fetch()) {
        $error = "Subject '$name' already exists.";
    } else {
        // Thumbnail Upload
        $thumb = 'default_subject.png';
        if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] == 0) {
            $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
            $thumb = time() . "_sub." . $ext;
            move_uploaded_file($_FILES['thumbnail']['tmp_name'], "../uploads/thumbnails/" . $thumb);
        }

        $stmt = $pdo->prepare("INSERT INTO subjects (name, description, thumbnail) VALUES (?, ?, ?)");
        if ($stmt->execute([$name, $desc, $thumb])) {
            $message = "Subject '$name' created successfully!";
        } else {
            $error = "Failed to create subject.";
        }
    }
}

// --- 2. HANDLE DELETE SUBJECT ---
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    // Optional: Check if used in classes before deleting?
    // For now, let's just delete. Foreign keys might restrict it if set up that way.
    try {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
        $stmt->execute([$id]);
        $message = "Subject deleted.";
    } catch (PDOException $e) {
        $error = "Cannot delete: This subject is being used by Classes/Weeks.";
    }
}

// --- 3. FETCH SUBJECTS ---
$subjects = $pdo->query("SELECT * FROM subjects ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Subjects - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <h2 class="fw-bold mb-4 text-secondary">📚 Subject Manager</h2>

        <?php if($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>

        <div class="row g-4">
            <!-- CREATE FORM -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white fw-bold">
                        ➕ Create New Subject
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Subject Name</label>
                                <input type="text" name="name" class="form-control" placeholder="e.g. Mathematics" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Thumbnail (Optional)</label>
                                <input type="file" name="thumbnail" class="form-control" accept="image/*">
                            </div>
                            <button type="submit" name="add_subject" class="btn btn-primary w-100 fw-bold">Create Subject</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- LIST -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">
                        📋 Existing Subjects
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Thumb</th>
                                    <th>Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($subjects as $s): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <?php $path = ($s['thumbnail'] == 'default_subject.png') ? '../assets/img/default_subject.png' : '../uploads/thumbnails/'.$s['thumbnail']; ?>
                                            <img src="<?php echo $path; ?>" class="rounded" width="40" height="40" style="object-fit:cover;">
                                        </td>
                                        <td class="fw-bold"><?php echo htmlspecialchars($s['name']); ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Delete this subject?');">
                                                <input type="hidden" name="delete_id" value="<?php echo $s['subject_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">🗑️</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if(empty($subjects)): ?>
                                    <tr><td colspan="3" class="text-center py-4">No subjects found. Create one!</td></tr>
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
