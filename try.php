<?php
// --- CONFIGURATION ---
$host = "localhost";
$user = "root"; 
$pass = ""; 
$db   = "ilmify"; // Change to your DB name

$conn = new mysqli($host, $user, $pass, $db);
$subject_id = 101; // HARDCODED: Simulating we are managing 'Math'

// --- HANDLE FORM SUBMISSIONS (PHP LOGIC) ---

// 1. Logic to Add a New Week
if (isset($_POST['add_week'])) {
    $title = $_POST['week_title'];
    $conn->query("INSERT INTO weeks (subject_id, week_title) VALUES ('$subject_id', '$title')");
}

// 2. Logic to Add Content (Video/Note)
if (isset($_POST['add_content'])) {
    $week_id = $_POST['week_id'];
    $type = $_POST['type'];
    $title = $_POST['content_title'];
    $url = $_POST['content_url']; // In real life, handle file upload here
    
    $conn->query("INSERT INTO course_content (week_id, type, title, content_url) VALUES ('$week_id', '$type', '$title', '$url')");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Curriculum Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .week-header { background-color: #f8f9fa; cursor: pointer; }
        .content-icon { width: 25px; display: inline-block; text-align: center; margin-right: 10px; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📚 Mathematics (Form 4)</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addWeekModal">+ New Week</button>
    </div>

    <div class="accordion" id="curriculumAccordion">
        
        <?php
        // 1. Fetch all Weeks for this Subject
        $weeks = $conn->query("SELECT * FROM weeks WHERE subject_id = '$subject_id'");
        
        while($week = $weeks->fetch_assoc()): 
            $week_id = $week['id'];
        ?>
            <div class="accordion-item mb-3 shadow-sm border-0">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $week_id; ?>">
                        <?php echo $week['week_title']; ?>
                    </button>
                </h2>
                <div id="collapse<?php echo $week_id; ?>" class="accordion-collapse collapse" data-bs-parent="#curriculumAccordion">
                    <div class="accordion-body">
                        
                        <ul class="list-group list-group-flush mb-3">
                            <?php
                            // 2. Fetch Content for THIS specific Week
                            $contents = $conn->query("SELECT * FROM course_content WHERE week_id = '$week_id'");
                            while($item = $contents->fetch_assoc()):
                                // Assign an emoji based on type
                                $icon = ($item['type'] == 'video') ? '📺' : (($item['type'] == 'quiz') ? '❓' : '📄');
                            ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="content-icon"><?php echo $icon; ?></span>
                                        <?php echo $item['title']; ?>
                                    </div>
                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                </li>
                            <?php endwhile; ?>
                        </ul>

                        <button class="btn btn-sm btn-outline-success" 
                                onclick="openAddContentModal(<?php echo $week_id; ?>)">
                                + Add Resource
                        </button>

                    </div>
                </div>
            </div>
        <?php endwhile; ?>

    </div>
</div>

<div class="modal fade" id="addWeekModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5>Add New Week</h5></div>
            <div class="modal-body">
                <input type="text" name="week_title" class="form-control" placeholder="e.g. Week 4: Geometry" required>
                <input type="hidden" name="add_week" value="1">
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Create Week</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="addContentModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5>Add Material</h5></div>
            <div class="modal-body">
                <input type="hidden" name="week_id" id="modal_week_id">
                <input type="hidden" name="add_content" value="1">

                <div class="mb-3">
                    <label>Type</label>
                    <select name="type" class="form-select" id="contentTypeSelector" onchange="updateInputType()">
                        <option value="video">Video (YouTube)</option>
                        <option value="note">Note (PDF)</option>
                        <option value="quiz">Quiz Link</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label>Title</label>
                    <input type="text" name="content_title" class="form-control" placeholder="e.g. Intro Video" required>
                </div>

                <div class="mb-3">
                    <label id="urlLabel">Video URL</label>
                    <input type="text" name="content_url" id="urlInput" class="form-control" placeholder="https://..." required>
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success">Upload</button></div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // 1. Opens the modal and remembers WHICH week we are adding to
    function openAddContentModal(weekId) {
        document.getElementById('modal_week_id').value = weekId;
        var myModal = new bootstrap.Modal(document.getElementById('addContentModal'));
        myModal.show();
    }

    // 2. Changes the input label based on what the teacher selects
    function updateInputType() {
        var type = document.getElementById('contentTypeSelector').value;
        var label = document.getElementById('urlLabel');
        var input = document.getElementById('urlInput');

        if(type === 'video') {
            label.innerText = "YouTube URL";
            input.placeholder = "https://youtube.com/watch?v=...";
        } else if (type === 'note') {
            label.innerText = "PDF Filename (Simulation)";
            input.placeholder = "chapter1.pdf";
        } else {
            label.innerText = "Quiz Link";
            input.placeholder = "https://forms.google.com/...";
        }
    }
</script>

</body>
</html>