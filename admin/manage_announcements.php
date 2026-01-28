<?php
// admin/manage_announcements.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$error = "";

// --- 1. HANDLE FORM SUBMISSION (ADD) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_announcement'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $type = $_POST['type']; // info, warning, quest

    if ($title && $content) {
        try {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content, type, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $content, $type, $_SESSION['user_id']]);
            $message = "📢 Announcement broadcasted successfully!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    } else {
        $error = "Title and Content are required.";
    }
}

// --- 2. HANDLE DELETION ---
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE announcement_id = ?");
    $stmt->execute([$id]);
    $message = "Announcement removed from the board.";
}

// --- 3. FETCH ANNOUNCEMENTS ---
$stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Town Crier - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
    <style>
        body { background-color: #f8f9fa; }
        .card-header { background-color: #dc3545; color: white; font-weight: bold; }
        .type-badge { font-size: 0.8rem; padding: 5px 10px; border-radius: 15px; }
        .type-info { background: #0dcaf0; color: black; }
        .type-warning { background: #ffc107; color: black; }
        .type-quest { background: #6610f2; color: white; }
    </style>
</head>
<body>

<div class="d-flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4" style="height: 100vh; overflow-y: auto;">
        
        <h2 class="fw-bold text-danger mb-4">📢 Town Crier (Announcements)</h2>

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
                📣 Broadcast New Message
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label fw-bold">Headline</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Server Maintenance or New Quest Available" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-bold">Type</label>
                            <select name="type" class="form-select">
                                <option value="info">ℹ️ General Info</option>
                                <option value="warning">⚠️ Urgent / Warning</option>
                                <option value="quest">⚔️ Quest Update</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Message Details</label>
                        <textarea name="content" class="form-control" rows="3" placeholder="Write your announcement here..." required></textarea>
                    </div>

                    <button type="submit" name="add_announcement" class="btn btn-danger px-4">Broadcast Now</button>
                </form>
            </div>
        </div>

        <h4 class="fw-bold text-secondary mb-3">📜 Announcement History</h4>
        
        <div class="table-responsive bg-white rounded shadow-sm p-3">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="15%">Date</th>
                        <th width="10%">Type</th>
                        <th width="65%">Message</th>
                        <th width="10%">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($announcements) > 0): ?>
                        <?php foreach ($announcements as $ann): ?>
                            <tr>
                                <td class="text-muted small">
                                    <?php echo date('M d, Y', strtotime($ann['created_at'])); ?><br>
                                    <?php echo date('h:i A', strtotime($ann['created_at'])); ?>
                                </td>
                                <td>
                                    <?php 
                                        $badges = [
                                            'info' => '<span class="badge type-info">General</span>',
                                            'warning' => '<span class="badge type-warning">Urgent</span>',
                                            'quest' => '<span class="badge type-quest">Quest</span>'
                                        ];
                                        echo $badges[$ann['type']] ?? '<span class="badge bg-secondary">Other</span>';
                                    ?>
                                </td>
                                <td>
                                    <strong class="d-block text-dark"><?php echo htmlspecialchars($ann['title']); ?></strong>
                                    <small class="text-muted"><?php echo htmlspecialchars($ann['content']); ?></small>
                                </td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Delete this announcement?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $ann['announcement_id']; ?>">
                                        <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill">🗑</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <h5>🔕 No announcements yet.</h5>
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