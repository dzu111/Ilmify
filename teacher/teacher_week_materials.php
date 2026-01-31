<?php
// teacher/week_materials.php
session_start();
require_once '../config/db.php';

// Security Check: Teachers Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}


$week_id = $_GET['week_id'] ?? 0;
// Allow class selection via URL parameter OR session
$class_id = $_GET['class_id'] ?? ($_SESSION['active_class_id'] ?? 0);

// Fetch Week Info
$stmt = $pdo->prepare("SELECT w.*, s.subject_id FROM weeks w JOIN subjects s ON w.subject_id = s.subject_id WHERE w.week_id = ?");
$stmt->execute([$week_id]);
$week = $stmt->fetch();

if (!$week) die("Week not found!");

// Fetch teacher's classes for this subject (for class selector dropdown)
$teacher_id = $_SESSION['user_id'];
$classes_stmt = $pdo->prepare("SELECT class_id, class_name FROM classes WHERE teacher_id = ? AND subject_id = ? ORDER BY class_name");
$classes_stmt->execute([$teacher_id, $week['subject_id']]);
$teacher_classes = $classes_stmt->fetchAll();

$msg = "";
$msg_type = "";

// ----------------------
// 1. HANDLE UPLOADS (Teacher Private)
// ----------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_content'])) {
    // Validate that teacher has selected a class
    if (!$class_id || $class_id == 0) {
        $msg = "⚠️ Please select a class from your Dashboard first before uploading materials!";
        $msg_type = "danger";
    } else {
        $type = $_POST['content_type']; 
        $title = $_POST['title'];
        $desc = $_POST['description'];
        
        if ($type == 'note' || $type == 'reading_session') {
            $file = "default.pdf";
            if (!empty($_FILES['file_upload']['name'])) {
                $ext = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
                $file = uniqid() . "_teacher_" . $class_id . "." . $ext;
                move_uploaded_file($_FILES['file_upload']['tmp_name'], "../uploads/materials/" . $file);
            }
            // Insert with class_id to make it PRIVATE to this teacher's class
            $stmt = $pdo->prepare("INSERT INTO materials (week_id, class_id, title, description, type, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$week_id, $class_id, $title, $desc, $type, $file, $_SESSION['user_id']]);
            $msg = "Private material added successfully!";
            $msg_type = "success";
            
        } elseif ($type == 'video') {
            $link = $_POST['youtube_link'];
            $stmt = $pdo->prepare("INSERT INTO videos (week_id, class_id, title, description, youtube_link, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$week_id, $class_id, $title, $desc, $link, $_SESSION['user_id']]);
            $msg = "Private video added!";
            $msg_type = "success";
        }
    }
}

// ----------------------
// 2. HANDLE HIDING / DELETION / RECOVERY
// ----------------------
if (isset($_POST['delete_item'])) {
    $type = $_POST['item_type'];
    $id = $_POST['item_id'];
    $origin = $_POST['item_origin']; // 'global' or 'custom'
    
    if ($origin == 'global') {
        // SOFT DELETE: Hide admin content for THIS class only
        $hide = $pdo->prepare("INSERT IGNORE INTO class_material_overrides (class_id, item_type, item_id) VALUES (?, ?, ?)");
        $hide->execute([$class_id, $type, $id]);
        $msg = "Master content hidden for your class.";
        $msg_type = "info";
    } else {
        // HARD DELETE: Teacher's own custom file
        $table = ($type=='video') ? 'videos' : (($type=='quiz')?'quizzes':'materials');
        $col = ($type=='video') ? 'video_id' : (($type=='quiz')?'quiz_id':'material_id');
        $del = $pdo->prepare("DELETE FROM $table WHERE $col = ? AND class_id = ?");
        $del->execute([$id, $class_id]);
        $msg = "Your custom item was deleted.";
        $msg_type = "warning";
    }
}

// RECOVER HIDDEN CONTENT
if (isset($_POST['recover_item'])) {
    $type = $_POST['item_type'];
    $id = $_POST['item_id'];
    
    $recover = $pdo->prepare("DELETE FROM class_material_overrides WHERE class_id = ? AND item_type = ? AND item_id = ?");
    $recover->execute([$class_id, $type, $id]);
    $msg = "Content recovered and visible again!";
    $msg_type = "success";
}

// ----------------------
// 3. FETCH HYBRID DATA (Global + Custom + Hidden with status)
// ----------------------
function getHybridContent($pdo, $table, $week_id, $class_id, $type_filter = null) {
    $type_query = $type_filter ? "AND type = '$type_filter'" : "";
    $id_col = ($table == 'videos') ? 'video_id' : (($table == 'quizzes') ? 'quiz_id' : 'material_id');
    $override_type = ($table == 'videos') ? 'video' : (($table == 'quizzes') ? 'quiz' : $type_filter);

    $sql = "SELECT *, 
            CASE WHEN class_id IS NULL THEN 'global' ELSE 'custom' END as origin,
            CASE WHEN $id_col IN (
                SELECT item_id FROM class_material_overrides 
                WHERE class_id = ? AND item_type = '$override_type'
            ) THEN 1 ELSE 0 END as is_hidden
            FROM $table 
            WHERE week_id = ? 
            $type_query
            AND (class_id IS NULL OR class_id = ?)
            ORDER BY is_hidden ASC, created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$class_id, $week_id, $class_id]);
    return $stmt->fetchAll();
}

$materials_notes = getHybridContent($pdo, 'materials', $week_id, $class_id, 'note');
$materials_reading = getHybridContent($pdo, 'materials', $week_id, $class_id, 'reading_session');
$materials = array_merge($materials_notes, $materials_reading);
$videos = getHybridContent($pdo, 'videos', $week_id, $class_id);
$quizzes = getHybridContent($pdo, 'quizzes', $week_id, $class_id);

$totalCount = count($materials) + count($videos) + count($quizzes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Class Prep - <?php echo htmlspecialchars($week['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Reusing your Kindergarten CSS styles */
        body { background-color: #E0F7FA; font-family: 'Nunito', sans-serif; }
        .content-card { background: white; border-radius: 20px; border: 3px solid #e9ecef; transition: all 0.3s ease; position: relative; height: 100%; min-height: 280px; }
        .content-card:hover { transform: translateY(-8px); border-color: #AB47BC; box-shadow: 0 15px 40px rgba(171, 71, 188, 0.2); }
        
        /* Hidden card styles */
        .content-card.hidden-card { filter: blur(3px) grayscale(50%); opacity: 0.6; border-color: #ccc; }
        .content-card.hidden-card:hover { filter: blur(2px) grayscale(30%); opacity: 0.8; transform: translateY(-5px); }
        
        .type-badge { position: absolute; top: 15px; right: 15px; padding: 5px 12px; border-radius: 15px; font-size: 0.7rem; font-weight: 800; }
        .origin-badge { position: absolute; bottom: 15px; left: 15px; font-size: 0.7rem; font-weight: 700; padding: 2px 8px; border-radius: 5px; }
        .badge-global { background: #E1F5FE; color: #0288D1; border: 1px solid #0288D1; }
        .badge-custom { background: #F3E5F5; color: #7B1FA2; border: 1px solid #7B1FA2; }
        
        .action-btn { position: absolute; top: 15px; left: 15px; opacity: 0; transition: opacity 0.2s; z-index: 10; }
        .content-card:hover .action-btn { opacity: 1; }
        
        /* Recover button for hidden items */
        .recover-btn { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 20; opacity: 0; transition: opacity 0.2s; }
        .hidden-card:hover .recover-btn { opacity: 1; }
        
        .game-btn { border-radius: 25px; font-weight: 800; transition: all 0.2s; }
        .game-btn:hover { transform: scale(1.05); }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <!-- Class Selector Section -->
        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-white">
                        <h5 class="fw-bold mb-2">🎨 Customize Materials for Your Class</h5>
                        <p class="mb-0 small opacity-75">Select a class below to add custom content or hide master items</p>
                    </div>
                    <div>
                        <select class="form-select form-select-lg" onchange="window.location.href='teacher_week_materials.php?week_id=<?php echo $week_id; ?>&class_id=' + this.value" style="min-width: 300px; border-radius: 25px;">
                            <option value="0" <?php echo (!$class_id || $class_id == 0) ? 'selected' : ''; ?>>👁️ View Only (No Class Selected)</option>
                            <?php foreach($teacher_classes as $tc): ?>
                                <option value="<?php echo $tc['class_id']; ?>" <?php echo $class_id == $tc['class_id'] ? 'selected' : ''; ?>>
                                    🎨 Customize: <?php echo htmlspecialchars($tc['class_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark">📚 <?php echo htmlspecialchars($week['title']); ?></h2>
                <p class="text-muted">
                    <?php if($class_id && $class_id > 0): ?>
                        <strong class="text-success">✓ Customizing for: <?php 
                            $selected_class = array_filter($teacher_classes, fn($c) => $c['class_id'] == $class_id);
                            echo htmlspecialchars(reset($selected_class)['class_name'] ?? 'Unknown');
                        ?></strong>
                    <?php else: ?>
                        Viewing master content only - Select a class above to customize
                    <?php endif; ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="teacher_subjects.php" class="btn btn-outline-secondary rounded-pill">⬅ Back</a>
                <?php if($class_id && $class_id > 0): ?>
                    <button class="btn btn-primary game-btn shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">➕ Add My Own</button>
                <?php else: ?>
                    <span class="text-muted small">↑ Select a class to add custom materials</span>
                <?php endif; ?>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> rounded-pill text-center fw-bold mb-4 shadow-sm"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <?php 
            $allContent = array_merge(
                array_map(function($i){ $i['cat']='material'; return $i; }, $materials),
                array_map(function($i){ $i['cat']='video'; return $i; }, $videos),
                array_map(function($i){ $i['cat']='quiz'; return $i; }, $quizzes)
            );

            foreach($allContent as $item): 
                $itemType = $item['type'] ?? $item['cat'];
                $itemId = $item['material_id'] ?? ($item['video_id'] ?? $item['quiz_id']);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="content-card <?php echo $item['is_hidden'] ? 'hidden-card' : ''; ?> p-4 d-flex flex-column text-center">
                        <span class="origin-badge <?php echo $item['origin'] == 'global' ? 'badge-global' : 'badge-custom'; ?>">
                            <?php echo $item['origin'] == 'global' ? '🌍 MASTER' : '👤 MY ITEM'; ?>
                        </span>

                        <?php if($item['is_hidden']): ?>
                            <!-- Recover Button for Hidden Items -->
                            <form method="POST" class="recover-btn">
                                <input type="hidden" name="item_type" value="<?php echo $itemType; ?>">
                                <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
                                <button type="submit" name="recover_item" class="btn btn-success btn-lg rounded-pill shadow-lg">
                                    ♻️ Recover Content
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Hide/Delete Button for Visible Items -->
                            <form method="POST" class="action-btn" onsubmit="return confirm('<?php echo $item['origin'] == 'global' ? 'Hide this from students?' : 'Permanently delete your file?'; ?>');">
                            <input type="hidden" name="item_type" value="<?php echo $itemType; ?>">
                            <input type="hidden" name="item_id" value="<?php echo $itemId; ?>">
                            <input type="hidden" name="item_origin" value="<?php echo $item['origin']; ?>">
                            <button type="submit" name="delete_item" class="btn btn-sm btn-danger rounded-circle shadow-sm">
                                <?php echo $item['origin'] == 'global' ? '🚫' : '🗑️'; ?>
                            </button>
                        </form>
                        <?php endif; ?>

                        <div class="flex-grow-1">
                            <span style="font-size: 3.5rem;">
                                <?php 
                                    if($itemType == 'video') echo '🎥';
                                    elseif($itemType == 'quiz') echo '⚔️';
                                    elseif($itemType == 'reading_session') echo '📖';
                                    else echo '📄';
                                ?>
                            </span>
                            <h5 class="fw-bold mt-2"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <p class="text-muted small px-2"><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 60)); ?>...</p>
                        </div>

                        <div class="mt-3">
                            <?php if($itemType == 'video'): ?>
                                <button class="btn btn-primary game-btn w-100" data-bs-toggle="modal" data-bs-target="#videoModal" onclick="loadVideo('<?php echo htmlspecialchars($item['youtube_link']); ?>')">
                                    ▶️ Watch Video
                                </button>
                            <?php elseif($itemType == 'quiz'): ?>
                                <button class="btn btn-warning game-btn w-100" disabled>
                                    ⚔️ View Quiz (Soon)
                                </button>
                            <?php else: ?>
                                <button class="btn btn-info game-btn text-white w-100" data-bs-toggle="modal" data-bs-target="#pdfModal" onclick="loadPDF('../uploads/materials/<?php echo htmlspecialchars($item['file_path']); ?>')">
                                    👁️ View PDF
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" enctype="multipart/form-data" class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold">➕ Add Custom Material</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="upload_content" value="1">
                <div class="mb-3">
                    <label class="form-label fw-bold">What are you adding?</label>
                    <select name="content_type" class="form-select rounded-pill" id="tType" onchange="toggleTUpload()">
                        <option value="note">📄 Note (PDF)</option>
                        <option value="reading_session">📖 Reading Session (PDF)</option>
                        <option value="video">🎥 Extra Video (YouTube Link)</option>
                        <option value="quiz">⚔️ Quiz (Coming Soon)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Title</label>
                    <input type="text" name="title" class="form-control rounded-pill" required>
                </div>
                <div id="tFile" class="mb-3">
                    <label class="form-label fw-bold">Select PDF</label>
                    <input type="file" name="file_upload" class="form-control" accept=".pdf">
                </div>
                <div id="tLink" class="mb-3" style="display:none;">
                    <label class="form-label fw-bold">YouTube Link</label>
                    <input type="url" name="youtube_link" class="form-control rounded-pill">
                </div>
                <div class="mb-3">
                    <label class="form-label">Quick Note (Description)</label>
                    <textarea name="description" class="form-control rounded-3" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100 game-btn mt-2">Add to My Class Only</button>
            </div>
        </form>
    </div>
</div>

<!-- PDF Viewer Modal -->
<div class="modal fade" id="pdfModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">📄 PDF Viewer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="pdfViewer" style="width:100%; height:80vh; border:none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Video Viewer Modal -->
<div class="modal fade" id="videoModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">🎥 Video Player</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="background: #000;">
                <div id="videoPlayer" style="width:100%; height:80vh;"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleTUpload() {
    let type = document.getElementById('tType').value;
    if(type === 'quiz') {
        document.getElementById('tFile').style.display = 'none';
        document.getElementById('tLink').style.display = 'none';
        alert('Quiz creation coming soon!');
    } else {
        document.getElementById('tFile').style.display = (type === 'video') ? 'none' : 'block';
        document.getElementById('tLink').style.display = (type === 'video') ? 'block' : 'none';
    }
}

function loadPDF(path) {
    document.getElementById('pdfViewer').src = path;
}

function loadVideo(link) {
    let videoId = '';
    let match = link.match(/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/);
    if (match && match[1]) {
        videoId = match[1];
    }
    document.getElementById('videoPlayer').innerHTML = 
        '<iframe width="100%" height="100%" src="https://www.youtube.com/embed/' + videoId + '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
}
</script>
</body>
</html>