<?php
// parent/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="icon" type="image/png" href="/tinytale/assets/img/favicon.png">

<div class="d-flex flex-column flex-shrink-0 p-3 text-white" style="width: 260px; min-height: 100vh; background-color: #198754;">
    
    <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <span class="fs-4 fw-bold">🌱 Parent Guardian</span>
    </a>
    <hr style="border-color: rgba(255,255,255,0.2);">
    
    <div class="d-flex align-items-center mb-3">
        <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center fw-bold me-2" style="width: 40px; height: 40px;">
            P
        </div>
        <div>
            <div class="fw-bold small"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Parent'); ?></div>
            <div class="small text-white-50" style="font-size: 0.8rem;">Monitoring Mode</div>
        </div>
    </div>

    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item mb-2">
            <a href="dashboard.php" class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active-parent' : 'text-white'; ?>">
                🏠 Overview
            </a>
        </li>
        <li class="nav-item mb-2">
            <a href="progress.php" class="nav-link <?php echo ($current_page == 'progress.php') ? 'active-parent' : 'text-white'; ?>">
                📊 Report Card
            </a>
        </li>
    </ul>
    
    <hr style="border-color: rgba(255,255,255,0.2);">
    
    <a href="../auth/logout.php" class="btn btn-outline-light w-100 fw-bold">🚪 Logout</a>
</div>

<style>
    /* Parent Specific Styles - Green Theme */
    .active-parent {
        background-color: #fff !important;
        color: #198754 !important; /* Green Text */
        font-weight: bold;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        transform: translateX(5px);
    }
    .nav-link { transition: all 0.2s; }
    .nav-link:hover:not(.active-parent) {
        background-color: rgba(255,255,255,0.1);
        padding-left: 20px;
    }
</style>