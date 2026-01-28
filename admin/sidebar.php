<?php
// admin/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark" style="width: 260px; min-height: 100vh;">
    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <span class="fs-4 fw-bold text-danger">⚡ Game Master</span>
    </a>
    <hr>
    
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active-admin' : 'text-white'; ?>">
                📊 Overview
            </a>
        </li>
        <li>
            <a href="manage_announcements.php" class="nav-link <?php echo ($current_page == 'manage_announcements.php') ? 'active-admin' : 'text-white'; ?>">
                📢 Announcements
            </a>
        </li>
        <li>
            <a href="manage_materials.php" class="nav-link <?php echo ($current_page == 'manage_materials.php') ? 'active-admin' : 'text-white'; ?>">
                📚 Library (Notes)
            </a>
        </li>
        <li>
            <a href="manage_videos.php" class="nav-link <?php echo ($current_page == 'manage_videos.php') ? 'active-admin' : 'text-white'; ?>">
                🎥 Video Gallery
            </a>
        </li>
        <li>
            <a href="manage_quizzes.php" class="nav-link <?php echo ($current_page == 'manage_quizzes.php') ? 'active-admin' : 'text-white'; ?>">
                ⚔️ Quests (Quizzes)
            </a>
        </li>
         <li>
            <a href="add_questions.php" class="nav-link <?php echo ($current_page == 'add_questions.php') ? 'active-admin' : 'text-white'; ?>">
                ⚔️ Quests (Questions)
            </a>
        </li>
        <li>
            <a href="manage_users.php" class="nav-link <?php echo ($current_page == 'manage_users.php') ? 'active-admin' : 'text-white'; ?>">
                👥 Players (Users)
            </a>
        </li>
        <li class="nav-item">
            <a href="add_admin.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'add_admin.php' ? 'active bg-danger' : ''; ?>">
                🛡️ Recruit Admin
            </a>
        </li>
    </ul>
    
    <hr>
    
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="../assets/img/default_avatar.jpg" alt="" width="32" height="32" class="rounded-circle me-2 bg-secondary">
            <strong>Admin</strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="../auth/logout.php">Sign out</a></li>
        </ul>
    </div>
</div>

<style>
    /* Admin Specific Sidebar Styles */
    .active-admin {
        background-color: #dc3545 !important; /* Red for Admin Power */
        color: white !important;
        font-weight: bold;
        box-shadow: 0 4px 10px rgba(220, 53, 69, 0.4);
    }
    .nav-link:hover:not(.active-admin) {
        background-color: rgba(255,255,255,0.1);
        transform: translateX(5px);
        transition: transform 0.2s;
    }
</style>