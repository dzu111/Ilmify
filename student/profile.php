<?php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}

$student_id = $_SESSION['user_id'];
$message = "";
$error = "";

// --- HANDLER: Update Profile (Name & Picture) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Update Name
    if (!empty($_POST['full_name'])) {
        $newName = trim($_POST['full_name']);
        $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $stmt->execute([$newName, $student_id]);
        $_SESSION['full_name'] = $newName; 
        $message = "Identity updated successfully!";
    }

    // 2. Update Profile Picture
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $newFilename = time() . "_" . $student_id . "." . $filetype;
            $uploadPath = "../uploads/" . $newFilename;

            if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $uploadPath)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $stmt->execute([$newFilename, $student_id]);
                $message = "New avatar equipped!";
            } else {
                $error = "Failed to upload image.";
            }
        } else {
            $error = "Invalid file type (JPG, PNG, GIF only).";
        }
    }
}

// --- DATA FETCHING ---
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$student_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM student_progress WHERE student_id = ?");
$stmt->execute([$student_id]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['current_level' => 1, 'current_xp' => 0];

// XP Calculation
$xp_needed = 100;
$current_xp = $progress['current_xp'];
$xp_percent = ($current_xp / $xp_needed) * 100;

// --- ONBOARDING CHECKS ---
$has_custom_avatar = ($user['profile_picture'] !== 'default_avatar.png');
$has_started_xp = ($current_xp > 0 || $progress['current_level'] > 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <title>My Profile - StudyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* General Styles */
        .profile-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            border-radius: 20px;
            color: white;
        }
        .avatar-preview {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border: 4px solid #f1c40f;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        .guide-card {
            border: 1px solid #e9ecef;
            transition: all 0.2s;
        }
        .guide-card.done {
            background-color: #f8f9fa;
            border-color: #27ae60;
            opacity: 0.7;
        }
        .check-icon {
            font-size: 1.5rem;
            color: #bdc3c7;
            min-width: 30px; /* Prevent shrinking on mobile */
        }
        .guide-card.done .check-icon {
            color: #27ae60;
        }

        /* Mobile Sidebar Overrides */
        .offcanvas-body .sidebar {
            width: 100% !important;
            min-height: auto !important;
            background-color: transparent !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark d-md-none p-3">
    <div class="container-fluid">
        <span class="navbar-brand fw-bold text-warning">🛡️ Hero Profile</span>
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

<div class="d-flex">
    
    <div class="d-none d-md-block">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="flex-grow-1 p-3 p-md-4 bg-light" style="height: 100vh; overflow-y: auto;">
        
        <?php if($message): ?>
            <div class="alert alert-success shadow-sm"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-danger shadow-sm"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body text-center p-4">
                        <h4 class="fw-bold mb-4 text-secondary">🛡️ Edit Profile</h4>
                        
                        <?php 
                        $pic = $user['profile_picture'];
                        $picPath = ($pic == 'default_avatar.png') ? "../assets/img/default_avatar.png" : "../uploads/" . $pic;
                        ?>
                        <img src="<?php echo $picPath; ?>" class="rounded-circle avatar-preview mb-3" alt="Avatar">
                        
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="mb-3 text-start">
                                <label class="form-label fw-bold small text-muted">Hero Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            </div>

                            <div class="mb-3 text-start">
                                <label class="form-label fw-bold small text-muted">Change Avatar</label>
                                <input type="file" name="profile_pic" class="form-control form-control-sm">
                            </div>

                            <button type="submit" class="btn btn-warning w-100 fw-bold">💾 Save Changes</button>
                        </form>
                        
                        <hr>
                        <div class="text-start small text-muted">
                            <p class="mb-1"><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="mb-1"><strong>Role:</strong> Student</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                
                <div class="card border-0 shadow-sm rounded-4 mb-4 profile-header p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="fw-bold mb-0">Level <?php echo $progress['current_level']; ?></h2>
                            <p class="mb-0 text-white-50">Current Rank</p>
                        </div>
                        <div class="text-end">
                            <h3 class="fw-bold text-warning mb-0"><?php echo $progress['current_xp']; ?> XP</h3>
                            <small>Total Experience</small>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 10px; background: rgba(255,255,255,0.2);">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $xp_percent; ?>%"></div>
                    </div>
                </div>

                <h4 class="fw-bold text-secondary mb-3">🚀 Getting Started Checklist</h4>
                
                <div class="card mb-3 shadow-sm guide-card <?php echo $has_custom_avatar ? 'done' : ''; ?>">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3 check-icon">
                            <?php echo $has_custom_avatar ? '✅' : '⬜'; ?>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1">Equip a Face</h5>
                            <p class="text-muted small mb-0">Upload a custom profile picture on the left panel to stand out.</p>
                        </div>
                        <?php if(!$has_custom_avatar): ?>
                            <span class="badge bg-danger ms-2">Incomplete</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-3 shadow-sm guide-card <?php echo $has_started_xp ? 'done' : ''; ?>">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3 check-icon">
                            <?php echo $has_started_xp ? '✅' : '⬜'; ?>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1">Begin the Adventure</h5>
                            <p class="text-muted small mb-0">Complete your first Quest or read a Note to earn your first XP.</p>
                        </div>
                        <?php if(!$has_started_xp): ?>
                            <a href="quest.php" class="btn btn-sm btn-primary ms-2">Quests</a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-3 shadow-sm guide-card">
                    <div class="card-body d-flex align-items-center">
                        <div class="me-3 check-icon">⬜</div>
                        <div class="flex-grow-1">
                            <h5 class="fw-bold mb-1">Visit the Library</h5>
                            <p class="text-muted small mb-0">Check the Notes section to prepare for your upcoming quizzes.</p>
                        </div>
                        <a href="notes.php" class="btn btn-sm btn-outline-secondary ms-2">Notes</a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>