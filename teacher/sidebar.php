<!-- teacher/sidebar.php -->
<style>
    .teacher-sidebar {
        width: 280px;
        min-height: 100vh;
        background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
        font-family: 'Nunito', sans-serif;
    }
    
    .teacher-sidebar .nav-link {
        border-radius: 15px;
        margin: 5px 0;
        transition: all 0.3s;
        font-weight: 600;
    }
    
    .teacher-sidebar .nav-link:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateX(5px);
    }
    
    .teacher-sidebar .nav-link.active {
        background: #4ECDC4 !important;
        color: white !important;
        box-shadow: 0 5px 15px rgba(78, 205, 196, 0.4);
    }
    
    .sidebar-logo {
        background: rgba(255, 255, 255, 0.1);
        padding: 15px;
        border-radius: 15px;
        margin-bottom: 10px;
    }
</style>

<div class="d-flex flex-column flex-shrink-0 p-3 text-white teacher-sidebar">
    <a href="dashboard.php" class="text-decoration-none">
        <div class="sidebar-logo text-center">
            <span class="fs-3">👨‍🏫</span>
            <div class="fs-5 fw-bold text-white mt-2">Teacher Portal</div>
        </div>
    </a>
    
    <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
    
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                🏠 My Classes
            </a>
        </li>
        
       
        <li class="nav-item">
            <a href="teacher_subjects.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'teacher_subjects.php' ? 'active' : ''; ?>">
                📚 My Subjects
            </a>
        </li>
        
        <?php if(isset($_SESSION['active_class_id'])): ?>
            <li class="nav-item mt-3">
                <small class="text-muted ms-3 text-uppercase" style="font-size: 0.75rem; opacity: 0.7;">Class Management</small>
            </li>
            <li class="nav-item">
                <a href="curriculum_view.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'curriculum_view.php' ? 'active' : ''; ?>">
                    🎨 Curriculum Remix
                </a>
            </li>
            
            <li class="nav-item">
                <a href="gradebook.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'gradebook.php' ? 'active' : ''; ?>">
                    📊 Gradebook
                </a>
            </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a href="live_class.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'live_class.php' ? 'active' : ''; ?>">
                🔴 Live Command Center
            </a>
        </li>
    </ul>
    
    <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
    
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="rounded-circle bg-white text-dark d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; font-size: 1.2rem; font-weight: 700;">
                <?php 
                $name = $_SESSION['full_name'] ?? 'T';
                echo strtoupper(substr($name, 0, 1)); 
                ?>
            </div>
            <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Teacher'); ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="../auth/logout.php">🚪 Sign Out</a></li>
        </ul>
    </div>
</div>
