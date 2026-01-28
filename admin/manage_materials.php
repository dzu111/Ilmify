<?php
session_start();
require_once '../config/db.php';

// Security Check: Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$error = "";

// --- HANDLER: DELETE NOTE ---
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    
    // 1. Get file paths to delete physical files
    $stmt = $pdo->prepare("SELECT file_path, thumbnail FROM materials WHERE material_id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetch();

    if ($file) {
        // Delete physical files if they exist
        if (file_exists("../uploads/materials/" . $file['file_path'])) {
            unlink("../uploads/materials/" . $file['file_path']);
        }
        if ($file['thumbnail'] !== 'default_note.png' && file_exists("../uploads/thumbnails/" . $file['thumbnail'])) {
            unlink("../uploads/thumbnails/" . $file['thumbnail']);
        }

        // 2. Delete DB Record
        $stmt = $pdo->prepare("DELETE FROM materials WHERE material_id = ?");
        $stmt->execute([$id]);
        $message = "Scroll (Note) destroyed successfully!";
    }
}

// --- HANDLER: UPLOAD NOTE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_note'])) {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $uploader = $_SESSION['user_id'];

    // File Handling
    $noteFile = $_FILES['note_file'];
    $thumbFile = $_FILES['thumbnail_file'];

    if ($noteFile['error'] == 0) {
        
        // 1. Process Note File (PDF/Doc)
        $ext = pathinfo($noteFile['name'], PATHINFO_EXTENSION);
        $noteName = time() . "_note." . $ext;
        $notePath = "../uploads/materials/" . $noteName;
        
        // 2. Process Thumbnail (Image)
        $thumbName = 'default_note.png'; // Default
        if ($thumbFile['error'] == 0) {
            $imgExt = pathinfo($thumbFile['name'], PATHINFO_EXTENSION);
            $thumbName = time() . "_thumb." . $imgExt;
            move_uploaded_file($thumbFile['tmp_name'], "../uploads/thumbnails/" . $thumbName);
        }

        // Create directories if missing
        if (!is_dir('../uploads/materials')) mkdir('../uploads/materials', 0777, true);
        if (!is_dir('../uploads/thumbnails')) mkdir('../uploads/thumbnails', 0777, true);

        if (move_uploaded_file($noteFile['tmp_name'], $notePath)) {
            $stmt = $pdo->prepare("INSERT INTO materials (title, description, file_path, thumbnail, uploaded_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $desc, $noteName, $thumbName, $uploader]);
            $message = "New knowledge added to the Library!";
        } else {
            $error = "Failed to upload the note file.";
        }
    } else {
        $error = "Please select a valid file.";
    }
}

// --- FETCH MATERIALS ---
$stmt = $pdo->query("SELECT * FROM materials ORDER BY created_at DESC");
$materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Library - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .thumb-preview {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        /* Mobile Sidebar Fix */
        .offcanvas-body .d-flex { width: 100% !important; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark d-md-none p-3">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold text-danger">⚡ Admin Panel</span>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
    </div>
</nav>

<div class="offcanvas offcanvas-start bg-dark text-white" tabindex="-1" id="mobileMenu">
    <div class="offcanvas-header border-bottom border-secondary">
        <h5 class="offcanvas-title">Menu</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <?php include 'sidebar.php'; ?>
    </div>
</div>

<div class="d-flex">
    
    <div class="d-none d-md-block">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="flex-grow-1 p-3 p-md-4 bg-light" style="height: 100vh; overflow-y: auto;">
        
        <h2 class="fw-bold mb-4 text-secondary pt-2 pt-md-0">📚 Library Manager</h2>

        <?php if($message): ?>
            <div class="alert alert-success shadow-sm"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-primary text-white fw-bold rounded-top-4">
                        ⬆️ Upload New Scroll
                    </div>
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Title</label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Chapter 1: PHP Basics" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Short summary for the card..."></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Note File (PDF/Doc)</label>
                                <input type="file" name="note_file" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Card Thumbnail (Image)</label>
                                <input type="file" name="thumbnail_file" class="form-control" accept="image/*">
                                <div class="form-text">This image will appear on the student dashboard.</div>
                            </div>

                            <button type="submit" name="upload_note" class="btn btn-primary w-100 fw-bold">🚀 Publish Note</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white fw-bold py-3">
                        📜 Existing Scrolls
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Thumb</th>
                                        <th>Title & File</th>
                                        <th>Date</th>
                                        <th class="text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(count($materials) > 0): ?>
                                        <?php foreach($materials as $item): ?>
                                            <?php 
                                                $thumbPath = ($item['thumbnail'] == 'default_note.png') 
                                                    ? '../assets/img/default_note.png' 
                                                    : '../uploads/thumbnails/' . $item['thumbnail'];
                                            ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <img src="<?php echo $thumbPath; ?>" class="thumb-preview" alt="Icon">
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($item['title']); ?></div>
                                                    <a href="../uploads/materials/<?php echo $item['file_path']; ?>" target="_blank" class="small text-decoration-none text-primary">
                                                        📄 View File
                                                    </a>
                                                </td>
                                                <td class="small text-muted">
                                                    <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this scroll?');">
                                                        <input type="hidden" name="delete_id" value="<?php echo $item['material_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">🗑️ Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">The library is empty.</td>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>