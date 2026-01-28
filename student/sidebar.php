<?php
// student/sidebar.php

// 1. IFRAME CHECK: If this page is loaded inside our Master Portal, DO NOT show this sidebar.
if (isset($_GET['framemode']) && $_GET['framemode'] == '1') {
    return; // Stop loading the file here
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="icon" type="image/png" href="/tinytale/assets/img/favicon.png">

<div class="sidebar d-flex flex-column flex-shrink-0 p-3 text-white" style="width: 250px; background-color: #2c3e50; min-height: 100vh;">
    
    <div class="text-center mb-4 pt-3">
        <?php 
        $pic = $user['profile_picture'] ?? 'default_avatar.jpg';
        
        // Image Path Logic
        if ($pic === 'default_avatar.jpg' || $pic === 'default_avatar.png') {
            $picPath = "/tinytale/assets/img/default_avatar.jpg";
        } else {
            $picPath = "../uploads/" . $pic;
        }
        ?>
        <img src="<?php echo $picPath; ?>" class="rounded-circle border border-3 border-warning shadow" width="90" height="90" style="object-fit: cover;" alt="Avatar">
        
        <h5 class="mt-3 fw-bold"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Hero'); ?></h5>
        <span class="badge bg-warning text-dark">Student Hero</span>
    </div>
    
    <hr style="border-color: rgba(255,255,255,0.1);">
    
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item mb-2">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active-game' : 'text-light'; ?>">
                🏠 Dashboard
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="profile.php" class="nav-link <?php echo ($current_page == 'profile.php') ? 'active-game' : 'text-light'; ?>">
                👤 My Hero Profile
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="quest.php" class="nav-link <?php echo ($current_page == 'quest.php') ? 'active-game' : 'text-light'; ?>">
                📜 Quest Logs 
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="notes.php" class="nav-link <?php echo ($current_page == 'notes.php') ? 'active-game' : 'text-light'; ?>">
                📖 Notes 
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="videos.php" class="nav-link <?php echo ($current_page == 'videos.php') ? 'active-game' : 'text-light'; ?>">
                🎥 Video Gallery
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="quiz.php" class="nav-link <?php echo ($current_page == 'quiz.php') ? 'active-game' : 'text-light'; ?>">
                ⚔️ Active Quests
            </a>
        </li>
    </ul>

    <hr style="border-color: rgba(255,255,255,0.1);">
    
    <a href="../auth/logout.php" target="_top" class="btn btn-danger w-100 fw-bold">🚪 Logout</a>
</div>

<style>
    .active-game {
        background: linear-gradient(90deg, #f1c40f 0%, #f39c12 100%);
        color: #000 !important;
        font-weight: bold;
        box-shadow: 0 4px 15px rgba(241, 196, 15, 0.4);
        transform: scale(1.02);
    }
    .nav-link { 
        transition: all 0.2s; 
        border-radius: 10px; 
        padding: 12px 20px; 
    }
    .nav-link:hover:not(.active-game) { 
        background-color: rgba(255,255,255,0.1); 
        padding-left: 25px; 
    }
</style>