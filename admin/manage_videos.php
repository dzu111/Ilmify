<?php
// admin/manage_videos.php
session_start();
require_once '../config/db.php';

// Security Check: Only Admins Allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$error = "";

// --- 1. HANDLE FORM SUBMISSION (ADD VIDEO) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_video'])) {
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $link = trim($_POST['youtube_link']);
    $creator = $_SESSION['user_id'];

    if (empty($title) || empty($link)) {
        $error = "Title and YouTube Link are required!";
    } else {
        // Simple validation: Check if it looks like a URL
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            $error = "Invalid YouTube URL format.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO videos (title, description, youtube_link, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $desc, $link, $creator]);
                $message = "Video added successfully!";
            } catch (PDOException $e) {
                $error = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// --- 2. HANDLE DELETION ---
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM videos WHERE video_id = ?");
    $stmt->execute([$id]);
    $message = "Video deleted from database.";
}

// --- 3. FETCH ALL VIDEOS ---
$stmt = $pdo->query("SELECT * FROM videos ORDER BY created_at DESC");
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Videos - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <style>
        body { background-color: #f8f9fa; }
        .card-header { background-color: #dc3545; color: white; font-weight: bold; }
        .btn-primary { background-color: #dc3545; border-color: #dc3545; }
        .btn-primary:hover { background-color: #bb2d3b; }
    </style>
</head>
<body>

<div class="d-flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4" style="height: 100vh; overflow-y: auto;">
        
        <h2 class="fw-bold text-danger mb-4">🎥 Video Command Center</h2>

        <?php if($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-5">
            <div class="card-header">
                ➕ Upload New Video Link
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Video Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Introduction to Science" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">YouTube Link</label>
                            <input type="url" name="youtube_link" class="form-control" placeholder="https://www.youtube.com/watch?v=..." required>
                            <div class="form-text">Copy the full link from your browser.</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description (Optional)</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="What is this video about?"></textarea>
                    </div>

                    <button type="submit" name="add_video" class="btn btn-primary px-4">Save to Gallery</button>
                </form>
            </div>
        </div>

        <h4 class="fw-bold text-secondary mb-3">📚 Video Library</h4>
        
        <div class="table-responsive bg-white rounded shadow-sm p-3">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="10%">Thumbnail</th>
                        <th width="25%">Title</th>
                        <th width="35%">Link & Description</th>
                        <th width="15%">Date Added</th>
                        <th width="15%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($videos) > 0): ?>
                        <?php foreach ($videos as $vid): ?>
                            <?php 
                                // Helper to extract Thumbnail from Link
                                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $vid['youtube_link'], $matches);
                                $yt_id = $matches[1] ?? '';
                                $thumb = $yt_id ? "https://img.youtube.com/vi/$yt_id/default.jpg" : "../assets/img/default_video.png";
                            ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $thumb; ?>" class="rounded border" width="100" height="60" style="object-fit: cover;">
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($vid['title']); ?></td>
                                <td>
                                    <small class="text-primary d-block text-truncate" style="max-width: 250px;">
                                        <a href="<?php echo htmlspecialchars($vid['youtube_link']); ?>" target="_blank" class="text-decoration-none">
                                            🔗 <?php echo htmlspecialchars($vid['youtube_link']); ?>
                                        </a>
                                    </small>
                                    <small class="text-muted"><?php echo htmlspecialchars($vid['description']); ?></small>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($vid['created_at'])); ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this video?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $vid['video_id']; ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill">🗑 Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <h5>No videos uploaded yet.</h5>
                                <p>Use the form above to add your first video.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>