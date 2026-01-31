<?php
// teacher/manage_weeks.php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$message = $error = "";

// 1. Fetch Subjects this teacher teaches
// (Assuming teacher_subjects table or direct class assignment. 
// For this MVP, we fetch subjects linked to classes taught by this teacher)
$subjects_sql = "SELECT DISTINCT s.subject_id, s.name 
                 FROM subjects s
                 JOIN classes c ON s.subject_id = c.subject_id
                 WHERE c.teacher_id = ?";
$stmt = $pdo->prepare($subjects_sql);
$stmt->execute([$teacher_id]);
$my_subjects = $stmt->fetchAll();

// Handle Subject Selection
$selected_subject_id = isset($_GET['subject_id']) ? $_GET['subject_id'] : ($my_subjects[0]['subject_id'] ?? null);

// 2. Handle Add Week
if (isset($_POST['add_week'])) {
    $title = $_POST['week_title'];
    $sub_id = $_POST['subject_id'];
    $order = $_POST['sort_order'];
    
    $pdo->prepare("INSERT INTO weeks (subject_id, title, sort_order, is_visible) VALUES (?, ?, ?, 1)")
        ->execute([$sub_id, $title, $order]);
    $message = "Week added successfully!";
}

// 3. Fetch Weeks for selected subject
$weeks = [];
if ($selected_subject_id) {
    $w_stmt = $pdo->prepare("SELECT * FROM weeks WHERE subject_id = ? ORDER BY sort_order ASC");
    $w_stmt->execute([$selected_subject_id]);
    $weeks = $w_stmt->fetchAll();
}

// 4. Fetch Global Content (Filtered by Subject ideally, but for now filtering by type)
// In a real app, materials would be linked to subjects. For now, we list all to let teacher pick.
$all_notes = $pdo->query("SELECT material_id, title FROM materials WHERE type='note'")->fetchAll();
$all_videos = $pdo->query("SELECT video_id, title FROM videos")->fetchAll();
$all_quizzes = $pdo->query("SELECT quiz_id, title FROM quizzes")->fetchAll();

// 5. Handle Content Assignment (Updating week_id in materials/videos/quizzes table)
if (isset($_POST['update_content_week'])) {
    $content_id = $_POST['content_id'];
    $type = $_POST['content_type']; // note, video, quiz
    $target_week_id = $_POST['week_id'];
    
    // We update the content item's `week_id`
    if ($type == 'note') {
        $pdo->prepare("UPDATE materials SET week_id = ? WHERE material_id = ?")->execute([$target_week_id, $content_id]);
    } elseif ($type == 'video') {
        $pdo->prepare("UPDATE videos SET week_id = ? WHERE video_id = ?")->execute([$target_week_id, $content_id]);
    } elseif ($type == 'quiz') {
        $pdo->prepare("UPDATE quizzes SET week_id = ? WHERE quiz_id = ?")->execute([$target_week_id, $content_id]);
    }
    $message = "Content assigned to week!";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Curriculum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <h2 class="fw-bold mb-4">📚 Curriculum Manager</h2>
        
        <?php if($message): ?><div class="alert alert-success"><?php echo $message; ?></div><?php endif; ?>

        <!-- Subject Selector -->
        <div class="card mb-4 shadow-sm border-0">
            <div class="card-body d-flex align-items-center">
                <label class="fw-bold me-3">Select Subject:</label>
                <select onchange="window.location.href='?subject_id='+this.value" class="form-select w-auto">
                    <?php foreach($my_subjects as $s): ?>
                        <option value="<?php echo $s['subject_id']; ?>" <?php if($s['subject_id'] == $selected_subject_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($s['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if(!$selected_subject_id): ?>
                    <span class="text-danger ms-3">You are not assigned to any classes/subjects yet.</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if($selected_subject_id): ?>
            <div class="row">
                <!-- LEFT: Weeks List -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-primary text-white fw-bold">
                            📅 Steps / Weeks
                        </div>
                        <div class="card-body">
                            <form method="POST" class="d-flex gap-2 mb-3">
                                <input type="hidden" name="subject_id" value="<?php echo $selected_subject_id; ?>">
                                <input type="text" name="week_title" class="form-control" placeholder="e.g. Week 1: Introduction" required>
                                <input type="number" name="sort_order" class="form-control" placeholder="Order" style="max-width: 80px;" value="<?php echo count($weeks)+1; ?>">
                                <button type="submit" name="add_week" class="btn btn-dark">Add</button>
                            </form>
                            
                            <div class="list-group">
                                <?php foreach($weeks as $w): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between">
                                            <strong><?php echo htmlspecialchars($w['title']); ?></strong>
                                            <span class="badge bg-secondary">ID: <?php echo $w['week_id']; ?></span>
                                        </div>
                                        <!-- Show Content in this week -->
                                        <?php 
                                            // Fetch content for this week
                                            $curr_notes = $pdo->prepare("SELECT title FROM materials WHERE week_id = ?"); $curr_notes->execute([$w['week_id']]);
                                            $curr_vids = $pdo->prepare("SELECT title FROM videos WHERE week_id = ?"); $curr_vids->execute([$w['week_id']]);
                                        ?>
                                        <div class="mt-2 text-muted small">
                                            📄 <?php echo $curr_notes->rowCount(); ?> Notes | 🎥 <?php echo $curr_vids->rowCount(); ?> Videos
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Content Pool -->
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white fw-bold">
                            📦 Assign Content to Weeks
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">Select content and choose which week it belongs to.</p>
                            
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Content Item</label>
                                    <select name="content_id" class="form-select" required onchange="updateContentType(this)">
                                        <optgroup label="📄 Notes">
                                            <?php foreach($all_notes as $n): echo "<option value='{$n['material_id']}' data-type='note'>{$n['title']}</option>"; endforeach; ?>
                                        </optgroup>
                                        <optgroup label="🎥 Videos">
                                            <?php foreach($all_videos as $v): echo "<option value='{$v['video_id']}' data-type='video'>{$v['title']}</option>"; endforeach; ?>
                                        </optgroup>
                                        <optgroup label="⚔️ Quizzes">
                                            <?php foreach($all_quizzes as $q): echo "<option value='{$q['quiz_id']}' data-type='quiz'>{$q['title']}</option>"; endforeach; ?>
                                        </optgroup>
                                    </select>
                                    <input type="hidden" name="content_type" id="real_content_type" value="note"> <!-- JS updates this -->
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Assign to Week</label>
                                    <select name="week_id" class="form-select" required>
                                        <?php foreach($weeks as $w): ?>
                                            <option value="<?php echo $w['week_id']; ?>"><?php echo htmlspecialchars($w['title']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="update_content_week" class="btn btn-success w-100">🔗 Link Content</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateContentType(select) {
    var selectedOption = select.options[select.selectedIndex];
    var type = selectedOption.getAttribute('data-type');
    document.getElementById('real_content_type').value = type;
}
// Init
updateContentType(document.querySelector('select[name="content_id"]'));
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
