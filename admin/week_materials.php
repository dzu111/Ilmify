<?php
// admin/week_materials.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$week_id = $_GET['week_id'] ?? 0;

// Fetch Week Info
$stmt = $pdo->prepare("SELECT * FROM weeks WHERE week_id = ?");
$stmt->execute([$week_id]);
$week = $stmt->fetch();

if (!$week) die("Week not found!");

$msg = "";
$msg_type = "";

// ----------------------
// 1. HANDLE UPLOADS
// ----------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_content'])) {
    $type = $_POST['content_type']; // note, reading_session, video
    $title = $_POST['title'];
    $desc = $_POST['description'];
    
    // Notes & Reading Sessions (File Upload)
    if ($type == 'note' || $type == 'reading_session') {
        $file = "default.pdf";
        if (!empty($_FILES['file_upload']['name'])) {
            $ext = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
            $file = uniqid() . "_master." . $ext;
            move_uploaded_file($_FILES['file_upload']['tmp_name'], "../uploads/materials/" . $file);
        }
        $stmt = $pdo->prepare("INSERT INTO materials (week_id, class_id, title, description, type, file_path, uploaded_by) VALUES (?, NULL, ?, ?, ?, ?, ?)");
        $stmt->execute([$week_id, $title, $desc, $type, $file, $_SESSION['user_id']]);
        $msg = "Material uploaded successfully!";
        $msg_type = "success";
        
    } elseif ($type == 'video') {
        $link = $_POST['youtube_link'];
        $stmt = $pdo->prepare("INSERT INTO videos (week_id, class_id, title, description, youtube_link, created_by) VALUES (?, NULL, ?, ?, ?, ?)");
        $stmt->execute([$week_id, $title, $desc, $link, $_SESSION['user_id']]);
        $msg = "Video added successfully!";
        $msg_type = "success";
    }
}

// ----------------------
// 2. HANDLE DELETION
// ----------------------
if (isset($_POST['delete_item'])) {
    $type = $_POST['item_type'];
    $id = $_POST['item_id'];
    
    $table = ($type=='video') ? 'videos' : (($type=='quiz')?'quizzes':'materials');
    $col = ($type=='video') ? 'video_id' : (($type=='quiz')?'quiz_id':'material_id');
    
    $del = $pdo->prepare("DELETE FROM $table WHERE $col = ? AND class_id IS NULL");
    $del->execute([$id]);
    $msg = "Item deleted permanently.";
    $msg_type = "warning";
}

// ----------------------
// 3. FETCH DATA
// ----------------------
// Notes & Reading Sessions
$materials = $pdo->prepare("SELECT * FROM materials WHERE week_id = ? AND class_id IS NULL ORDER BY created_at DESC");
$materials->execute([$week_id]);
$materials = $materials->fetchAll();

$videos = $pdo->prepare("SELECT * FROM videos WHERE week_id = ? AND class_id IS NULL ORDER BY created_at DESC");
$videos->execute([$week_id]);
$videos = $videos->fetchAll();

$quizzes = $pdo->prepare("SELECT * FROM quizzes WHERE week_id = ? AND class_id IS NULL ORDER BY created_by DESC");
$quizzes->execute([$week_id]);
$quizzes = $quizzes->fetchAll();

$totalCount = count($materials) + count($videos) + count($quizzes);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Content - <?php echo htmlspecialchars($week['title']); ?></title>
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
        
        .type-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-note { background: #667eea; color: white; }
        .badge-reading { background: #f093fb; color: white; }
        .badge-video { background: #fa709a; color: white; }
        .badge-quiz { background: #feca57; color: #2d3436; }
        
        .view-btn {
            background: #4ECDC4;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 700;
            transition: all 0.2s;
        }
        
        .view-btn:hover {
            background: #45b8af;
            transform: scale(1.05);
            color: white;
        }
        
        .delete-btn {
            position: absolute;
            top: 15px;
            left: 15px;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .content-card:hover .delete-btn {
            opacity: 1;
        }
        
        .content-count {
            background: white;
            padding: 15px 30px;
            border-radius: 15px;
            display: inline-block;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4" style="background-color: #E0F7FA;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold"><?php echo htmlspecialchars($week['title']); ?></h2>
                <p class="text-muted mb-2">Manage all content for this week</p>
                <div class="content-count">
                    <span class="fw-bold text-primary"><?php echo $totalCount; ?></span>
                    <span class="text-muted"> / 6 items</span>
                    <?php if($totalCount >= 6): ?>
                        <span class="badge bg-warning text-dark ms-2">Full</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="subject_weeks.php?subject_id=<?php echo $week['subject_id']; ?>" class="btn btn-outline-secondary rounded-pill">⬅ Back to Weeks</a>
                <?php if($totalCount < 6): ?>
                    <button class="btn btn-dark rounded-pill fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadModal">➕ Add Content</button>
                    <a href="quiz_builder.php?week_id=<?php echo $week_id; ?>" class="btn btn-warning rounded-pill fw-bold shadow-sm border border-3 border-warning text-dark">⚔️ New Quiz</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-<?php echo $msg_type; ?> rounded-pill text-center fw-bold mb-4"><?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- All Content Grid -->
        <div class="row g-4">
            <!-- Render Materials -->
            <?php foreach($materials as $m): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="content-card p-4 d-flex flex-column">
                        <span class="type-badge badge-<?php echo $m['type'] == 'reading_session' ? 'reading' : 'note'; ?>">
                            <?php echo $m['type'] == 'reading_session' ? 'Reading' : 'Note'; ?>
                        </span>
                        
                        <form method="POST" class="delete-btn" onsubmit="return confirm('Delete this item?');">
                            <input type="hidden" name="item_type" value="<?php echo $m['type']; ?>">
                            <input type="hidden" name="item_id" value="<?php echo $m['material_id']; ?>">
                            <button type="submit" name="delete_item" class="btn btn-sm btn-danger rounded-circle">🗑</button>
                        </form>
                        
                        <div class="text-center flex-grow-1 d-flex flex-column justify-content-between">
                            <div>
                                <span class="content-icon"><?php echo $m['type'] == 'reading_session' ? '📖' : '📄'; ?></span>
                                <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($m['title']); ?></h5>
                                <p class="text-muted small"><?php echo htmlspecialchars(substr($m['description'] ?? 'No description', 0, 80)); ?></p>
                            </div>
                            <div class="mt-3">
                                <button class="btn view-btn" data-bs-toggle="modal" data-bs-target="#viewPDF<?php echo $m['material_id']; ?>">
                                    👁️ View PDF
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- PDF Viewer Modal -->
                    <div class="modal fade" id="viewPDF<?php echo $m['material_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold"><?php echo htmlspecialchars($m['title']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-0">
                                    <iframe src="../uploads/materials/<?php echo $m['file_path']; ?>" style="width: 100%; height: 80vh; border: none;"></iframe>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
             
            <!-- Render Videos -->
            <?php foreach($videos as $v): 
                // Extract YouTube video ID
                preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $v['youtube_link'], $matches);
                $videoId = $matches[1] ?? '';
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="content-card p-4 d-flex flex-column">
                        <span class="type-badge badge-video">Video</span>
                        
                        <form method="POST" class="delete-btn" onsubmit="return confirm('Delete this video?');">
                            <input type="hidden" name="item_type" value="video">
                            <input type="hidden" name="item_id" value="<?php echo $v['video_id']; ?>">
                            <button type="submit" name="delete_item" class="btn btn-sm btn-danger rounded-circle">🗑</button>
                        </form>
                        
                        <div class="text-center flex-grow-1 d-flex flex-column justify-content-between">
                            <div>
                                <span class="content-icon">🎥</span>
                                <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($v['title']); ?></h5>
                                <p class="text-muted small"><?php echo htmlspecialchars(substr($v['description'] ?? 'No description', 0, 80)); ?></p>
                            </div>
                            <div class="mt-3">
                                <button class="btn view-btn" data-bs-toggle="modal" data-bs-target="#viewVideo<?php echo $v['video_id']; ?>">
                                    ▶️ Watch Video
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Video Viewer Modal -->
                    <div class="modal fade" id="viewVideo<?php echo $v['video_id']; ?>" tabindex="-1">
                        <div class="modal-dialog modal-xl modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold"><?php echo htmlspecialchars($v['title']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-0" style="background: #000;">
                                    <div class="ratio ratio-16x9">
                                        <iframe src="https://www.youtube.com/embed/<?php echo $videoId; ?>?autoplay=0" allowfullscreen></iframe>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
             
            <!-- Render Quizzes -->
            <?php foreach($quizzes as $q): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="content-card p-4 d-flex flex-column">
                        <span class="type-badge badge-quiz">Quiz</span>
                        
                        <form method="POST" class="delete-btn" onsubmit="return confirm('Delete this quiz?');">
                            <input type="hidden" name="item_type" value="quiz">
                            <input type="hidden" name="item_id" value="<?php echo $q['quiz_id']; ?>">
                            <button type="submit" name="delete_item" class="btn btn-sm btn-danger rounded-circle">🗑</button>
                        </form>
                        
                        <div class="text-center flex-grow-1 d-flex flex-column justify-content-between">
                            <div>
                                <span class="content-icon">⚔️</span>
                                <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($q['title']); ?></h5>
                                <p class="text-muted small"><?php echo htmlspecialchars(substr($q['description'] ?? 'No description', 0, 80)); ?></p>
                            </div>
                            <div class="mt-3 d-flex gap-2 justify-content-center">
                                <a href="quiz_builder.php?week_id=<?php echo $week_id; ?>&edit_id=<?php echo $q['quiz_id']; ?>" class="btn view-btn">
                                    ✏️ Edit Quiz
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($materials) && empty($videos) && empty($quizzes)): ?>
                <div class="col-12 text-center py-5">
                    <div class="content-card p-5">
                        <h3 class="mb-3">📭 This week is empty!</h3>
                        <p class="text-muted">Click "Add Content" or "New Quiz" to get started.</p>
                        <p class="text-muted small">Maximum 6 items per week (1-2 hour class)</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- UPLOAD MODAL -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">📤 Upload Master Content</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="upload_content" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Content Type</label>
                        <select name="content_type" class="form-select" id="contentType" onchange="toggleUploadType()">
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
                        <input type="url" name="youtube_link" class="form-control" placeholder="https://youtube.com/watch?v=...">
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Upload to Master Library</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleUploadType() {
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
