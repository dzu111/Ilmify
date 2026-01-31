<?php
// student/view_all.php
session_start();
require_once '../config/db.php';

// Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

// PARAMETERS
$type = $_GET['type'] ?? 'note'; // 'note', 'reading_session', 'video', 'quiz'
$subject_id = $_GET['subject_id'] ?? null;
$search = $_GET['q'] ?? '';

// HELPER: Get Page Title & Color
$page_config = [
    'note' => ['title' => '📜 Study Notes', 'color' => 'primary', 'icon' => '📄', 'btn_text' => 'Read Scroll', 'btn_link' => 'view_note.php?id='],
    'reading_session' => ['title' => '📑 Reading Sessions', 'color' => 'info', 'icon' => '📖', 'btn_text' => 'View Document', 'btn_link' => 'view_note.php?id='],
    'video' => ['title' => '🎥 Video Gallery', 'color' => 'danger', 'icon' => '📺', 'btn_text' => 'Watch Video', 'btn_link' => 'view_video.php?id='],
    'quiz' => ['title' => '⚔️ Active Battles', 'color' => 'warning', 'icon' => '⚔️', 'btn_text' => 'Start Battle', 'btn_link' => 'take_quiz.php?id='],
];

// Fallback if invalid type
if (!array_key_exists($type, $page_config)) {
    $type = 'note';
}

$conf = $page_config[$type];
$page_title = $conf['title'];
$theme_color = $conf['color'];

// BUILD QUERY
$params = [];
$sql = "";

if ($type == 'quiz') {
    $sql = "SELECT q.*, q.quiz_id as id FROM quizzes q";
    if ($subject_id) {
        $sql .= " JOIN weeks w ON q.week_id = w.week_id";
    }
} elseif ($type == 'video') {
    $sql = "SELECT v.*, v.video_id as id FROM videos v";
    if ($subject_id) {
        $sql .= " JOIN weeks w ON v.week_id = w.week_id";
    }
} else {
    // Materials (notes / reading sessions)
    // We treat them same table but filter by type column
    $sql = "SELECT m.*, m.material_id as id FROM materials m";
    if ($subject_id) {
        $sql .= " JOIN weeks w ON m.week_id = w.week_id";
    }
}

// WHERE CLAUSES
$clauses = [];

// 1. Filter by Type (only for materials table)
if ($type == 'note' || $type == 'reading_session') {
    $clauses[] = "m.type = ?";
    $params[] = $type;
}

// 2. Filter by Subject
if ($subject_id) {
    echo "<!-- Debug: Filtering by Subject ID: $subject_id -->"; // Debug comment
    $clauses[] = "w.subject_id = ?";
    $params[] = $subject_id;
}

// 3. Search
if ($search) {
    // Determine alias based on table
    $alias = ($type == 'quiz') ? 'q' : (($type == 'video') ? 'v' : 'm');
    $clauses[] = "($alias.title LIKE ? OR $alias.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Combine WHERE
if (count($clauses) > 0) {
    $sql .= " WHERE " . implode(' AND ', $clauses);
}

// Order
$created_col = ($type == 'quiz') ? 'quiz_id' : 'created_at'; // Quizzes don't always have created_at
$alias = ($type == 'quiz') ? 'q' : (($type == 'video') ? 'v' : 'm');
$sql .= " ORDER BY $alias.$created_col DESC";

// EXECUTE
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error loading content: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; }
        .card-item {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            /* Dynamic border color based on type */
            border-left: 5px solid var(--bs-<?php echo $theme_color; ?>);
        }
        .card-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="d-flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4" style="height: 100vh; overflow-y: auto;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <a href="dashboard.php<?php echo $subject_id ? '?subject_id='.$subject_id : ''; ?>" class="text-decoration-none text-muted small mb-2 d-inline-block">⬅ Back to Dashboard</a>
                <h2 class="fw-bold text-<?php echo $theme_color; ?>"><?php echo $page_title; ?></h2>
                <p class="text-muted">Currently viewing all content in this category.</p>
            </div>
            
            <form class="d-flex" style="max-width: 300px;">
                <!-- Preserve Type & Subject -->
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">
                <?php if($subject_id): ?>
                    <input type="hidden" name="subject_id" value="<?php echo htmlspecialchars($subject_id); ?>">
                <?php endif; ?>
                
                <input class="form-control me-2 rounded-pill" type="search" name="q" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                <button class="btn btn-<?php echo $theme_color; ?> rounded-pill text-white" type="submit">Search</button>
            </form>
        </div>

        <div class="row g-4">
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card card-item h-100 shadow-sm">
                            
                            <!-- Video Thumbnail Special Case -->
                            <?php if ($type == 'video'): ?>
                                <?php 
                                preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $item['youtube_link'], $matches);
                                $yt_id = $matches[1] ?? '';
                                $thumb = "https://img.youtube.com/vi/$yt_id/mqdefault.jpg";
                                ?>
                                <img src="<?php echo $thumb; ?>" class="card-img-top" style="height: 160px; object-fit: cover;" alt="Thumbnail">
                            <?php endif; ?>

                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title fw-bold text-<?php echo $theme_color; ?>"><?php echo htmlspecialchars($item['title']); ?></h5>
                                    <?php if ($type != 'video'): ?>
                                        <span class="fs-4"><?php echo $conf['icon']; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <h6 class="card-subtitle mb-2 text-muted small">
                                    <?php 
                                        // Show created_at if available
                                        echo isset($item['created_at']) ? date('M d, Y', strtotime($item['created_at'])) : 'No date';
                                    ?>
                                </h6>
                                
                                <p class="card-text text-secondary mb-4 flex-grow-1">
                                    <?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 80)) . '...'; ?>
                                </p>
                                
                                <a href="<?php echo $conf['btn_link'] . $item['id']; ?>" class="btn btn-outline-<?php echo $theme_color; ?> w-100 fw-bold rounded-pill mt-auto stretched-link">
                                    <?php echo $conf['btn_text']; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <h3 class="text-muted text-opacity-50">Empty Space...</h3>
                    <p class="text-muted">No content found matching these criteria.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div> </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
