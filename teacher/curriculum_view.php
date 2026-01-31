<?php
// teacher/curriculum_view.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_SESSION['active_class_id'])) {
    header("Location: dashboard.php");
    exit;
}

$teacher_id = $_SESSION['user_id'];
$class_id = $_SESSION['active_class_id'];

// Get class info
$stmt = $pdo->prepare("SELECT c.*, s.name as subject_name FROM classes c JOIN subjects s ON c.subject_id = s.subject_id WHERE c.class_id = ?");
$stmt->execute([$class_id]);
$class = $stmt->fetch();

if (!$class || $class['teacher_id'] != $teacher_id) {
    header("Location: dashboard.php");
    exit;
}

// Get current week
$current_week_id = $class['current_week_id'] ?? null;
if (!$current_week_id) {
    // Get first week
    $stmt = $pdo->prepare("SELECT week_id FROM weeks WHERE subject_id = ? ORDER BY sort_order LIMIT 1");
    $stmt->execute([$class['subject_id']]);
    $week = $stmt->fetch();
    $current_week_id = $week['week_id'] ?? 0;
}

// Get week info
$stmt = $pdo->prepare("SELECT * FROM weeks WHERE week_id = ?");
$stmt->execute([$current_week_id]);
$week = $stmt->fetch();

$msg = "";
$msg_type = "";

// Handle Upload Custom Content
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_custom'])) {
    $type = $_POST['content_type'];
    $title = $_POST['title'];
    $desc = $_POST['description'];
    
    if ($type == 'note' || $type == 'reading_session') {
        $file = "default.pdf";
        if (!empty($_FILES['file_upload']['name'])) {
            $ext = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
            $file = uniqid() . "_class" . $class_id . "." . $ext;
            move_uploaded_file($_FILES['file_upload']['tmp_name'], "../uploads/materials/" . $file);
        }
        $stmt = $pdo->prepare("INSERT INTO materials (week_id, class_id, title, description, type, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$current_week_id, $class_id, $title, $desc, $type, $file, $teacher_id]);
        $msg = "Custom material uploaded!";
        $msg_type = "success";
    } elseif ($type == 'video') {
        $link = $_POST['youtube_link'];
        $stmt = $pdo->prepare("INSERT INTO videos (week_id, class_id, title, description, youtube_link, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$current_week_id, $class_id, $title, $desc, $link, $teacher_id]);
        $msg = "Custom video added!";
        $msg_type = "success";
    }
}

// Handle Hide Master Content
if (isset($_POST['hide_master'])) {
    $item_type = $_POST['item_type'];
    $item_id = $_POST['item_id'];
    
    $stmt = $pdo->prepare("INSERT INTO class_material_overrides (class_id, material_type, material_id) VALUES (?, ?, ?)");
    $stmt->execute([$class_id, $item_type, $item_id]);
    $msg = "Content hidden for this class!";
    $msg_type = "warning";
}

// Handle Delete Custom Content
if (isset($_POST['delete_custom'])) {
    $item_type = $_POST['item_type'];
    $item_id = $_POST['item_id'];
    
    $table = ($item_type=='video') ? 'videos' : 'materials';
    $col = ($item_type=='video') ? 'video_id' : 'material_id';
    
    $stmt = $pdo->prepare("DELETE FROM $table WHERE $col = ? AND class_id = ?");
    $stmt->execute([$item_id, $class_id]);
    $msg = "Custom content deleted!";
    $msg_type = "danger";
}

// HYBRID QUERY - Fetch Materials
$materials_query = "
    (SELECT m.*, 'master' as origin
     FROM materials m
     WHERE m.week_id = ? AND m.class_id IS NULL
       AND m.material_id NOT IN (
         SELECT material_id FROM class_material_overrides 
         WHERE class_id = ? AND material_type = 'material'
       ))
    UNION
    (SELECT m.*, 'custom' as origin
     FROM materials m
     WHERE m.week_id = ? AND m.class_id = ?)
    ORDER BY created_at DESC
";
$stmt = $pdo->prepare($materials_query);
$stmt->execute([$current_week_id, $class_id, $current_week_id, $class_id]);
$materials = $stmt->fetchAll();

// HYBRID QUERY - Fetch Videos
$videos_query = "
    (SELECT v.*, 'master' as origin
     FROM videos v
     WHERE v.week_id = ? AND v.class_id IS NULL
       AND v.video_id NOT IN (
         SELECT material_id FROM class_material_overrides 
         WHERE class_id = ? AND material_type = 'video'
       ))
    UNION
    (SELECT v.*, 'custom' as origin
     FROM videos v
     WHERE v.week_id = ? AND v.class_id = ?)
    ORDER BY created_at DESC
";
$stmt = $pdo->prepare($videos_query);
$stmt->execute([$current_week_id, $class_id, $current_week_id, $class_id]);
$videos = $stmt->fetchAll();

// Fetch Quizzes (similar pattern)
$quizzes_query = "
    (SELECT q.*, 'master' as origin
     FROM quizzes q
     WHERE q.week_id = ? AND q.class_id IS NULL
       AND q.quiz_id NOT IN (
         SELECT material_id FROM class_material_overrides 
         WHERE class_id = ? AND material_type = 'quiz'
       ))
    UNION
    (SELECT q.*, 'custom' as origin
     FROM quizzes q
     WHERE q.week_id = ? AND q.class_id = ?)
    ORDER BY created_by DESC
";
$stmt = $pdo->prepare($quizzes_query);
$stmt->execute([$current_week_id, $class_id, $current_week_id, $class_id]);
$quizzes = $stmt->fetchAll();

$totalCount = count($materials) + count($videos) + count($quizzes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Curriculum Remix - <?php echo htmlspecialchars($class['class_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #E0F7FA; font-family: 'Nunito', sans-serif; }
        
        .content-card { 
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 20px; 
            border: 3px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative; 
            overflow: hidden;
            height: 100%;
            min-height: 280px;
        }
        
        .content-card:hover { 
            transform: translateY(-8px); 
            border-color: #4ECDC4; 
            box-shadow: 0 15px 40px rgba(78, 205, 196, 0.3); 
        }
        
        .content-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .origin-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .badge-master { background: #667eea; color: white; }
        .badge-custom { background: #f093fb; color: white; }
        
        .type-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .badge-note { background: #667eea; color: white; }
        .badge-reading { background: #f093fb; color: white; }
        .badge-video { background: #fa709a; color: white; }
        .badge-quiz { background: #feca57; color: #2d3436; }
        
        .action-btn {
            opacity: 0;
            transition: opacity 0.2s;
            position: absolute;
            top: 60px;
            right: 15px;
        }
        
        .content-card:hover .action-btn {
            opacity: 1;
        }
        
        .game-btn {
            background: #FFD93D;
            border: 3px solid #ffcc00;
            color: #5a4a00;
            font-weight: 800;
            border-radius: 50px;
            padding: 12px 30px;
        }
        
        .game-btn:hover {
            background: #ffcc00;
            transform: scale(1.05);
            color: #5a4a00;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">My Classes</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($class['class_name']); ?></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($week['title'] ?? 'Week'); ?></li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold"><?php echo htmlspecialchars($class['class_name']); ?> - <?php echo htmlspecialchars($week['title']); ?></h2>
                <p class="text-muted">Customize curriculum for your class</p>
            </div>
            <div>
                <button class="btn game-btn shadow" data-bs-toggle="modal" data-bs-target="#uploadModal">➕ Upload Extra</button>
                <a href="live_class.php" class="btn btn-danger fw-bold">🚀 Go Live</a>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> rounded-pill text-center fw-bold mb-4"><?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- Content Grid -->
        <div class="row g-4">
            <!-- Materials -->
            <?php foreach($materials as $m): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="content-card p-4 d-flex flex-column">
                        <span class="origin-badge badge-<?php echo $m['origin']; ?>">
                            <?php echo $m['origin'] == 'master' ? '🌍 Master' : '👤 My Upload'; ?>
                        </span>
                        
                        <span class="type-badge badge-<?php echo $m['type'] == 'reading_session' ? 'reading' : 'note'; ?>">
                            <?php echo $m['type'] == 'reading_session' ? 'Reading' : 'Note'; ?>
                        </span>
                        
                        <?php if($m['origin'] == 'master'): ?>
                            <form method="POST" class="action-btn">
                                <input type="hidden" name="item_type" value="material">
                                <input type="hidden" name="item_id" value="<?php echo $m['material_id']; ?>">
                                <button type="submit" name="hide_master" class="btn btn-sm btn-warning rounded-pill" title="Hide for this class">
                                    👁️🗨️ Hide
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="action-btn" onsubmit="return confirm('Delete your custom upload?');">
                                <input type="hidden" name="item_type" value="material">
                                <input type="hidden" name="item_id" value="<?php echo $m['material_id']; ?>">
                                <button type="submit" name="delete_custom" class="btn btn-sm btn-danger rounded-circle">🗑</button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center flex-grow-1 d-flex flex-column justify-content-center">
                            <span class="content-icon"><?php echo $m['type'] == 'reading_session' ? '📖' : '📄'; ?></span>
                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($m['title']); ?></h5>
                            <p class="text-muted small"><?php echo htmlspecialchars(substr($m['description'] ?? '', 0, 60)); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Videos -->
            <?php foreach($videos as $v): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="content-card p-4 d-flex flex-column">
                        <span class="origin-badge badge-<?php echo $v['origin']; ?>">
                            <?php echo $v['origin'] == 'master' ? '🌍 Master' : '👤 My Upload'; ?>
                        </span>
                        
                        <span class="type-badge badge-video">Video</span>
                        
                        <?php if($v['origin'] == 'master'): ?>
                            <form method="POST" class="action-btn">
                                <input type="hidden" name="item_type" value="video">
                                <input type="hidden" name="item_id" value="<?php echo $v['video_id']; ?>">
                                <button type="submit" name="hide_master" class="btn btn-sm btn-warning rounded-pill">👁️🗨️ Hide</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="action-btn" onsubmit="return confirm('Delete your custom video?');">
                                <input type="hidden" name="item_type" value="video">
                                <input type="hidden" name="item_id" value="<?php echo $v['video_id']; ?>">
                                <button type="submit" name="delete_custom" class="btn btn-sm btn-danger rounded-circle">🗑</button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center flex-grow-1 d-flex flex-column justify-content-center">
                            <span class="content-icon">🎥</span>
                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($v['title']); ?></h5>
                            <p class="text-muted small"><?php echo htmlspecialchars(substr($v['description'] ?? '', 0, 60)); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <!-- Quizzes -->
            <?php foreach($quizzes as $q): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="content-card p-4 d-flex flex-column">
                        <span class="origin-badge badge-<?php echo $q['origin']; ?>">
                            <?php echo $q['origin'] == 'master' ? '🌍 Master' : '👤 My Upload'; ?>
                        </span>
                        
                        <span class="type-badge badge-quiz">Quiz</span>
                        
                        <?php if($q['origin'] == 'master'): ?>
                            <form method="POST" class="action-btn">
                                <input type="hidden" name="item_type" value="quiz">
                                <input type="hidden" name="item_id" value="<?php echo $q['quiz_id']; ?>">
                                <button type="submit" name="hide_master" class="btn btn-sm btn-warning rounded-pill">👁️🗨️ Hide</button>
                            </form>
                        <?php endif; ?>
                        
                        <div class="text-center flex-grow-1 d-flex flex-column justify-content-center">
                            <span class="content-icon">⚔️</span>
                            <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($q['title']); ?></h5>
                            <p class="text-muted small"><?php echo htmlspecialchars(substr($q['description'] ?? '', 0, 60)); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if($totalCount == 0): ?>
                <div class="col-12 text-center py-5">
                    <h4>No content for this week yet!</h4>
                    <p class="text-muted">Upload custom materials or wait for admin to add master content.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">📤 Upload Custom Content</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="upload_custom" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Content Type</label>
                        <select name="content_type" class="form-select" id="contentType" onchange="toggleType()">
                            <option value="note">📄 Note (PDF)</option>
                            <option value="reading_session">📖 Reading Session (PDF)</option>
                            <option value="video">🎥 Video (YouTube)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="mb-3" id="fileField">
                        <label class="form-label fw-bold">File (PDF)</label>
                        <input type="file" name="file_upload" class="form-control" accept=".pdf">
                    </div>

                    <div class="mb-3" id="linkField" style="display:none;">
                        <label class="form-label fw-bold">YouTube Link</label>
                        <input type="url" name="youtube_link" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Upload for This Class Only</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleType() {
    let type = document.getElementById('contentType').value;
    if (type === 'video') {
        document.getElementById('fileField').style.display = 'none';
        document.getElementById('linkField').style.display = 'block';
    } else {
        document.getElementById('fileField').style.display = 'block';
        document.getElementById('linkField').style.display = 'none';
    }
}
</script>
</body>
</html>
