<?php
// admin/quiz_builder.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$week_id = $_GET['week_id'] ?? 0;
$edit_id = $_GET['edit_id'] ?? 0;
$msg = "";

// ----------------------
// 1. HANDLE QUIZ SAVE
// ----------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_quiz'])) {
    $title = $_POST['title'];
    $desc = $_POST['description'];
    
    // File Upload (Thumbnail)
    $thumb = 'default_quiz.png';
    if (!empty($_FILES['thumbnail']['name'])) {
        $ext = pathinfo($_FILES['thumbnail']['name'], PATHINFO_EXTENSION);
        $thumb = uniqid() . "_quiz." . $ext;
        move_uploaded_file($_FILES['thumbnail']['tmp_name'], "../uploads/quizzes/" . $thumb);
    }
    
    if ($edit_id) {
        // Update Existing
        $sql = "UPDATE quizzes SET title = ?, description = ? WHERE quiz_id = ?";
        $params = [$title, $desc, $edit_id];
        if (!empty($_FILES['thumbnail']['name'])) {
            $sql = "UPDATE quizzes SET title = ?, description = ?, thumbnail = ? WHERE quiz_id = ?";
            $params = [$title, $desc, $thumb, $edit_id];
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $msg = "Quiz updated successfully!";
    } else {
        // Create New (CLASS_ID IS NULL -> Global)
        $stmt = $pdo->prepare("INSERT INTO quizzes (week_id, title, description, thumbnail, created_by, file_path, class_id) VALUES (?, ?, ?, ?, ?, 'take_quiz.php', NULL)");
        $stmt->execute([$week_id, $title, $desc, $thumb, $_SESSION['user_id']]);
        $edit_id = $pdo->lastInsertId(); // Switch to edit mode immediately
        $msg = "Quiz created! Now add questions below.";
        // Refresh to set edit_id in URL
        header("Location: quiz_builder.php?week_id=$week_id&edit_id=$edit_id");
        exit;
    }
}

// ----------------------
// 2. HANDLE ADD QUESTION
// ----------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_question'])) {
    $quiz_id = $_POST['quiz_id_for_q'];
    $text = $_POST['question_text'];
    $type = $_POST['question_type']; // text, image, audio, input
    $correct = strtolower(trim($_POST['correct_option'])); 
    
    // Default nulls
    $media_file = NULL;
    $option_a = $_POST['option_a'] ?? null;
    $option_b = $_POST['option_b'] ?? null;
    $option_c = $_POST['option_c'] ?? null;
    $option_d = $_POST['option_d'] ?? null;

    // Helper Media (Audio/Image Helper)
    if (!empty($_FILES['media_file']['name'])) {
        $ext = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . "_media." . $ext;
        $target = ($type == 'audio') ? "../uploads/audio/" : "../uploads/images/";
        move_uploaded_file($_FILES['media_file']['tmp_name'], $target . $filename);
        $media_file = $filename;
    }

    // Image Options Logic
    if ($type == 'image') {
        function uploadOpt($file) {
            if(empty($file['name'])) return null;
            $name = uniqid() . "_opt." . pathinfo($file['name'], PATHINFO_EXTENSION);
            move_uploaded_file($file['tmp_name'], "../uploads/images/" . $name);
            return $name;
        }
        $option_a = uploadOpt($_FILES['file_a']);
        $option_b = uploadOpt($_FILES['file_b']);
        $option_c = uploadOpt($_FILES['file_c']);
        $option_d = uploadOpt($_FILES['file_d']);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO questions 
            (quiz_id, question_text, question_type, media_file, option_a, option_b, option_c, option_d, correct_option) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $text, $type, $media_file, $option_a, $option_b, $option_c, $option_d, $correct]);
        $msg = "Question added successfully!";
    } catch (PDOException $e) {
        $msg = "Error: " . $e->getMessage();
    }
}

// ----------------------
// 3. HANDLE DELETE QUESTION
// ----------------------
if (isset($_GET['del_q'])) {
    $del = $pdo->prepare("DELETE FROM questions WHERE question_id = ?");
    $del->execute([$_GET['del_q']]);
    header("Location: quiz_builder.php?week_id=$week_id&edit_id=$edit_id");
    exit;
}

// ----------------------
// 4. FETCH DATA
// ----------------------
$quiz_data = ['title' => '', 'description' => '', 'thumbnail' => 'default_quiz.png'];
$questions = [];

if ($edit_id) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE quiz_id = ?");
    $stmt->execute([$edit_id]);
    $quiz_data = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
    $stmt->execute([$edit_id]);
    $questions = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Unified Quiz Builder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background-color: #E0F7FA; font-family: 'Nunito', sans-serif; }
        .game-card { background: white; border-radius: 20px; border: 4px solid #fff; box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .btn-game { background: #FFD93D; color: #5a4a00; font-weight: 800; border-radius: 50px; padding: 10px 30px; border: 3px solid #ffcc00; }
        .btn-game:hover { background: #ffe066; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4" style="background-color: #E0F7FA;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">🛠 Quiz Builder</h2>
            <a href="week_materials.php?week_id=<?php echo $week_id; ?>" class="btn btn-outline-dark rounded-pill fw-bold">⬅ Back to Week</a>
        </div>

        <?php if($msg): ?>
            <div class="alert alert-info rounded-pill text-center fw-bold mb-4"><?php echo $msg; ?></div>
        <?php endif; ?>

    <div class="row g-4">
        <!-- LEFT: QUIZ SETTINGS -->
        <div class="col-lg-4">
            <div class="game-card p-4">
                <h5 class="fw-bold mb-3">📝 Quiz Details</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Title</label>
                        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($quiz_data['title']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($quiz_data['description']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Thumbnail</label>
                        <input type="file" name="thumbnail" class="form-control">
                        <?php if($edit_id): ?>
                            <img src="../uploads/quizzes/<?php echo $quiz_data['thumbnail']; ?>" class="mt-2 rounded" width="100">
                        <?php endif; ?>
                    </div>
                    <button type="submit" name="save_quiz" class="btn btn-primary w-100 rounded-pill fw-bold">💾 Save Quiz Info</button>
                </form>
            </div>
        </div>

        <!-- RIGHT: QUESTIONS LIST -->
        <div class="col-lg-8">
            <div class="game-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold">❓ Questions (<?php echo count($questions); ?>)</h5>
                    <?php if($edit_id): ?>
                        <button class="btn btn-game btn-sm" data-bs-toggle="modal" data-bs-target="#addQuestionModal">➕ Add Question</button>
                    <?php else: ?>
                        <small class="text-muted">Save Quiz first to add questions.</small>
                    <?php endif; ?>
                </div>

                <?php if(empty($questions)): ?>
                    <p class="text-muted text-center py-4">No questions yet. Click "Add Question" to start!</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach($questions as $index => $q): ?>
                            <div class="list-group-item border-0 mb-2 rounded shadow-sm d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-secondary me-2"><?php echo $index + 1; ?></span>
                                    <span class="fw-bold"><?php echo htmlspecialchars($q['question_text']); ?></span>
                                    <br>
                                    <small class="text-muted">Type: <?php echo strtoupper($q['question_type']); ?> | Correct: <?php echo strtoupper($q['correct_option']); ?></small>
                                </div>
                                <a href="quiz_builder.php?week_id=<?php echo $week_id; ?>&edit_id=<?php echo $edit_id; ?>&del_q=<?php echo $q['question_id']; ?>" 
                                   class="btn btn-outline-danger btn-sm rounded-circle"
                                   onclick="return confirm('Delete this question?');">🗑</a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ADD QUESTION MODAL -->
<?php if($edit_id): ?>
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">➕ Add New Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_question" value="1">
                    <input type="hidden" name="quiz_id_for_q" value="<?php echo $edit_id; ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Question Type</label>
                            <select name="question_type" id="qType" class="form-select" onchange="toggleFields()">
                                <option value="text">📝 Standard Text</option>
                                <option value="image">🖼️ Image Options</option>
                                <option value="audio">🔊 Audio Question</option>
                                <option value="input">✍️ Fill in the Blank</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Question Text</label>
                            <input type="text" name="question_text" class="form-control" placeholder="e.g. Which apple is red?" required>
                        </div>

                        <!-- Helper Media -->
                        <div class="col-md-12" id="mediaBox">
                            <label class="form-label fw-bold text-primary">Helper Media (Optional)</label>
                            <input type="file" name="media_file" class="form-control">
                            <small class="text-muted" id="mediaText">Upload Image for Text Type / MP3 for Audio Type.</small>
                        </div>

                        <!-- OPTIONS GROUPS -->
                        <div class="col-12"><hr></div>

                        <!-- TEXT OPTIONS -->
                        <div class="col-12" id="textOptions">
                            <label class="fw-bold mb-2">Answer Options</label>
                            <div class="row g-2">
                                <div class="col-6"><input type="text" name="option_a" class="form-control" placeholder="Option A"></div>
                                <div class="col-6"><input type="text" name="option_b" class="form-control" placeholder="Option B"></div>
                                <div class="col-6"><input type="text" name="option_c" class="form-control" placeholder="Option C"></div>
                                <div class="col-6"><input type="text" name="option_d" class="form-control" placeholder="Option D"></div>
                            </div>
                        </div>

                        <!-- IMAGE OPTIONS -->
                        <div class="col-12" id="imageOptions" style="display:none;">
                            <label class="fw-bold mb-2 text-success">Upload Image Options</label>
                            <div class="row g-2">
                                <div class="col-6"><input type="file" name="file_a" class="form-control"><small>Image A</small></div>
                                <div class="col-6"><input type="file" name="file_b" class="form-control"><small>Image B</small></div>
                                <div class="col-6"><input type="file" name="file_c" class="form-control"><small>Image C</small></div>
                                <div class="col-6"><input type="file" name="file_d" class="form-control"><small>Image D</small></div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">Correct Answer</label>
                            <input type="text" name="correct_option" class="form-control" placeholder="a, b, c, d OR exact word" required>
                        </div>

                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Save Question</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleFields() {
    let type = document.getElementById('qType').value;
    let textOpts = document.getElementById('textOptions');
    let imgOpts = document.getElementById('imageOptions');
    let mediaBox = document.getElementById('mediaBox');
    let mediaText = document.getElementById('mediaText');

    // Default State
    textOpts.style.display = 'block';
    imgOpts.style.display = 'none';
    mediaBox.style.display = 'block';
    mediaText.textContent = "Upload Helper Image (Optional).";

    if (type === 'image') {
        textOpts.style.display = 'none';
        imgOpts.style.display = 'block';
        mediaBox.style.display = 'none'; 
    } else if (type === 'input') {
        textOpts.style.display = 'none';
        imgOpts.style.display = 'none';
    } else if (type === 'audio') {
        mediaText.textContent = "Required: Upload MP3 Audio File.";
    }
}
</script>

</body>
</html>
