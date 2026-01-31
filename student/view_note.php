<?php
// student/view_note.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$id = $_GET['id'] ?? 0;
$student_id = $_SESSION['user_id'];
$is_embedded = (isset($_GET['embedded']) && $_GET['embedded'] == 'true');

// --- [NEW] QUEST TRACKING LOGIC ---
$stmt = $pdo->prepare("INSERT IGNORE INTO student_reads (student_id, material_id) VALUES (?, ?)");
$stmt->execute([$student_id, $id]);

// Fetch Note Details
$stmt = $pdo->prepare("SELECT * FROM materials WHERE material_id = ?");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) die("Scroll not found!");

// check path - ensure this folder exists in your uploads directory!
$file_url = "../uploads/materials/" . $note['file_path']; 
$ext = strtolower(pathinfo($note['file_path'], PATHINFO_EXTENSION));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading: <?php echo htmlspecialchars($note['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf_viewer.min.css">

    <style>
        .viewer-container {
            height: <?php echo $is_embedded ? '100vh' : '85vh'; ?>; /* Full height if embedded */
            background: #525659; /* PDF Viewer Grey */
            border-radius: <?php echo $is_embedded ? '0' : '15px'; ?>;
            overflow: hidden; /* Hide scrollbars if content fits */
            overflow-y: auto; /* Allow scrolling inside the container */
            border: <?php echo $is_embedded ? 'none' : '4px solid #fff'; ?>;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
        }
        
        /* PDF Canvas Styling */
        #pdf-render-container {
            text-align: center;
            padding: 20px 0;
        }
        canvas {
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            margin-bottom: 20px;
            max-width: 100%;
        }

        /* Mobile Sidebar Overrides */
        .offcanvas-body .d-flex { width: 100% !important; }
        
        /* Loading Spinner */
        #loading-msg {
            color: white;
            text-align: center;
            padding-top: 50px;
        }
    </style>
</head>
<body>

<?php if(!$is_embedded): ?>
<nav class="navbar navbar-dark bg-dark d-md-none p-3">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold text-warning">📖 Library</span>
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
<?php endif; ?>

<div class="d-flex">
    
    <?php if(!$is_embedded): ?>
    <div class="d-none d-md-block">
        <?php include 'sidebar.php'; ?>
    </div>
    <?php endif; ?>

    <div class="flex-grow-1 <?php echo $is_embedded ? 'p-0' : 'p-3 p-md-4'; ?> bg-light" style="height: 100vh; overflow-y: hidden;">
        
        <?php if(!$is_embedded): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="dashboard.php" class="text-decoration-none text-muted small">⬅ Back to Dashboard</a>
                <h4 class="fw-bold m-0"><?php echo htmlspecialchars($note['title']); ?></h4>
            </div>
            <a href="<?php echo $file_url; ?>" download class="btn btn-outline-primary btn-sm rounded-pill">⬇️ Download File</a>
        </div>
        <?php endif; ?>

        <div class="viewer-container">
            <?php if ($ext == 'pdf'): ?>
                <div id="loading-msg">Loading Document...</div>
                <div id="pdf-render-container"></div>
                
                <script>
                    var url = '<?php echo $file_url; ?>';

                    // Loaded via <script> tag, create shortcut to access PDF.js exports.
                    var pdfjsLib = window['pdfjs-dist/build/pdf'];

                    // The workerSrc property shall be specified.
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

                    var loadingTask = pdfjsLib.getDocument(url);
                    
                    loadingTask.promise.then(function(pdf) {
                        document.getElementById('loading-msg').style.display = 'none';
                        var container = document.getElementById('pdf-render-container');

                        // Loop through all pages to render them vertically
                        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
                            pdf.getPage(pageNum).then(function(page) {
                                var scale = 1.5; // Zoom level (Adjust as needed)
                                var viewport = page.getViewport({scale: scale});

                                // Create Canvas for each page
                                var canvas = document.createElement('canvas');
                                var context = canvas.getContext('2d');
                                canvas.height = viewport.height;
                                canvas.width = viewport.width;
                                
                                // Make canvas responsive
                                canvas.style.width = '100%';
                                canvas.style.maxWidth = viewport.width + 'px';
                                canvas.style.height = 'auto';

                                container.appendChild(canvas);

                                var renderContext = {
                                    canvasContext: context,
                                    viewport: viewport
                                };
                                page.render(renderContext);
                            });
                        }
                    }, function (reason) {
                        // PDF loading error
                        console.error(reason);
                        document.getElementById('loading-msg').innerHTML = "Error loading PDF: " + reason;
                    });
                </script>

            <?php elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                <div class="d-flex justify-content-center align-items-center h-100 bg-dark" style="overflow: auto;">
                    <img src="<?php echo $file_url; ?>" style="max-width: 100%; height: auto; display: block; margin: auto;">
                </div>

            <?php else: ?>
                <div class="d-flex flex-column justify-content-center align-items-center h-100 text-white">
                    <h3 class="mb-3">📁 File Ready</h3>
                    <p>Previews are limited for .<?php echo $ext; ?> files.</p>
                    <a href="<?php echo $file_url; ?>" class="btn btn-warning fw-bold px-4 rounded-pill">Download to View</a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>