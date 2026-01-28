<?php
// admin/add_questions.php
session_start();
require_once '../config/db.php';

// Security: Admin Only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$msg_type = ""; // success or danger

// --- PRE-SELECT QUIZ ID ---
// If we came from "Manage Quizzes", pre-select that quiz in the dropdown
$selected_quiz_id = $_GET['quiz_id'] ?? '';

// --- HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $quiz_id = $_POST['quiz_id'];
    $text = $_POST['question_text'];
    $type = $_POST['question_type']; // text, image, audio, input
    $correct = strtolower(trim($_POST['correct_option'])); // a, b, c, d OR word
    
    // Default nulls
    $media_file = NULL;
    $option_a = $_POST['option_a'] ?? null;
    $option_b = $_POST['option_b'] ?? null;
    $option_c = $_POST['option_c'] ?? null;
    $option_d = $_POST['option_d'] ?? null;

    // 1. Handle Helper Media (Audio/Image Helper)
    if (!empty($_FILES['media_file']['name'])) {
        $ext = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . "_media." . $ext;
        $target = ($type == 'audio') ? "../uploads/audio/" : "../uploads/images/";
        move_uploaded_file($_FILES['media_file']['tmp_name'], $target . $filename);
        $media_file = $filename;
    }

    // 2. Handle Image Options (If type is 'image', overwrite options with filenames)
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

    // 3. Insert into Database
    try {
        $stmt = $pdo->prepare("INSERT INTO questions 
            (quiz_id, question_text, question_type, media_file, option_a, option_b, option_c, option_d, correct_option) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$quiz_id, $text, $type, $media_file, $option_a, $option_b, $option_c, $option_d, $correct]);
        
        $message = "✅ Question added successfully! Add another?";
        $msg_type = "success";
        
        // Keep the quiz ID selected for the next question
        $selected_quiz_id = $quiz_id;

    } catch (PDOException $e) {
        $message = "❌ Error: " . $e->getMessage();
        $msg_type = "danger";
    }
}

// Fetch Quizzes for Dropdown
$quizzes = $pdo->query("SELECT * FROM quizzes ORDER BY quiz_id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Questions - Game Master</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-primary">➕ Add Question</h2>
            <a href="manage_quizzes.php" class="btn btn-outline-secondary">⬅️ Back to Quests</a>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?php echo $msg_type; ?> shadow-sm border-0 rounded-3 mb-4">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0 text-secondary">Question Details</h5>
            </div>
            <div class="card-body p-4">
                
                <form method="POST" enctype="multipart/form-data">
                    
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Quest</label>
                            <select name="quiz_id" class="form-select bg-light" required>
                                <option value="" disabled selected>-- Choose a Quest --</option>
                                <?php foreach($quizzes as $q): ?>
                                    <option value="<?php echo $q['quiz_id']; ?>" <?php echo ($q['quiz_id'] == $selected_quiz_id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($q['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Question Type</label>
                            <select name="question_type" id="qType" class="form-select bg-light" onchange="toggleFields()">
                                <option value="text">📝 Standard Text</option>
                                <option value="image">🖼️ Image Options (Choose Picture)</option>
                                <option value="audio">🔊 Audio Question (Listen & Choose)</option>
                                <option value="input">✍️ Fill in the Blank</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Question Text</label>
                            <input type="text" name="question_text" class="form-control form-control-lg" placeholder="e.g., Which animal makes this sound?" required>
                        </div>

                        <div class="col-12" id="mediaBox">
                            <label class="form-label fw-bold text-primary">Upload Helper Media (Optional)</label>
                            <input type="file" name="media_file" class="form-control">
                            <div class="form-text">
                                <span id="mediaText">For <b>Audio Type</b>: Upload MP3. For <b>Text Type</b>: Upload Helper Image.</span>
                            </div>
                        </div>

                        <div class="col-12"><hr class="text-muted"></div>

                        <div class="col-12" id="textOptions">
                            <label class="form-label fw-bold mb-3">Answer Options</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">A</span>
                                        <input type="text" name="option_a" class="form-control" placeholder="Option A">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">B</span>
                                        <input type="text" name="option_b" class="form-control" placeholder="Option B">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">C</span>
                                        <input type="text" name="option_c" class="form-control" placeholder="Option C">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text fw-bold">D</span>
                                        <input type="text" name="option_d" class="form-control" placeholder="Option D">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12" id="imageOptions" style="display:none;">
                            <label class="form-label fw-bold mb-3 text-success">Upload Image Options</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Image A</label>
                                    <input type="file" name="file_a" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Image B</label>
                                    <input type="file" name="file_b" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Image C</label>
                                    <input type="file" name="file_c" class="form-control">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Image D</label>
                                    <input type="file" name="file_d" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-danger">Correct Answer</label>
                            <input type="text" name="correct_option" class="form-control border-danger" placeholder="a, b, c, d OR the word for Fill-in-blank" required>
                            <div class="form-text">
                                For Multiple Choice: type <b>a</b>, <b>b</b>, <b>c</b>, or <b>d</b>.<br>
                                For Fill-in-Blank: type the exact word (e.g. <b>Apple</b>).
                            </div>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow-sm">💾 Save Question</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<script>
function toggleFields() {
    let type = document.getElementById('qType').value;
    let textOpts = document.getElementById('textOptions');
    let imgOpts = document.getElementById('imageOptions');
    let mediaBox = document.getElementById('mediaBox');
    let mediaText = document.getElementById('mediaText');

    // Reset Defaults
    textOpts.style.display = 'block';
    imgOpts.style.display = 'none';
    mediaBox.style.display = 'block';
    mediaText.innerHTML = "For <b>Text/Input Type</b>: Upload Helper Image (Optional).";

    if (type === 'image') {
        textOpts.style.display = 'none';
        imgOpts.style.display = 'block';
        mediaBox.style.display = 'none'; // Don't usually need a helper image if the options ARE images
    } 
    else if (type === 'input') {
        textOpts.style.display = 'none';
        imgOpts.style.display = 'none';
    } 
    else if (type === 'audio') {
        mediaBox.style.display = 'block';
        mediaText.innerHTML = "<b>Required:</b> Upload the MP3 file for this question.";
    }
}
</script>

</body>
</html>