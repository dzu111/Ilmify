<?php
// student/sidebar.php

// 1. IFRAME CHECK
if (isset($_GET['framemode']) && $_GET['framemode'] == '1') {
    return;
}

$current_page = basename($_SERVER['PHP_SELF']);
$student_id = $_SESSION['user_id'];

// 2. FETCH SUBJECTS FOR DROPDOWN
// We need to check if $conn exists. If this file is included, it usually does.
// If not, ensure your dashboard.php includes db_connect.php BEFORE this sidebar.
$enrolled_subjects = [];
if (isset($conn)) {
    $sub_sql = "SELECT DISTINCT s.subject_id, s.name 
                FROM subjects s
                JOIN classes c ON s.subject_id = c.subject_id
                JOIN enrollments e ON c.class_id = e.class_id
                WHERE e.student_id = '$student_id'";
    $sub_result = mysqli_query($conn, $sub_sql);
    if ($sub_result) {
        while ($row = mysqli_fetch_assoc($sub_result)) {
            $enrolled_subjects[] = $row;
        }
    }
}
?>

<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">

<div class="sidebar-container d-flex flex-column flex-shrink-0 p-3" style="width: 260px; min-height: 100vh;">
    
    <div class="profile-card text-center mb-4 pt-3 pb-3">
        <?php 
        $pic = $user['profile_picture'] ?? 'default_avatar.jpg';
        $picPath = ($pic === 'default_avatar.jpg' || $pic === 'default_avatar.png') 
            ? "/tinytale/assets/img/default_avatar.jpg" 
            : "../uploads/" . $pic;
        ?>
        <div class="avatar-wrapper">
            <img src="<?php echo $picPath; ?>" class="hero-avatar" alt="Avatar">
            <div class="level-badge">⭐ Lv. <?php echo $_SESSION['level'] ?? 1; ?></div>
        </div>
        
        <h5 class="mt-3 fw-black text-dark"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Hero'); ?></h5>
        <div class="xp-bar-container mt-2">
            <div class="xp-bar-fill" style="width: 70%;"></div>
        </div>
    </div>
    
    <!-- Live Class Alert (Hidden by default, shown via AJAX) -->
    <div id="liveClassAlert" style="display: none;" class="alert alert-danger text-center mb-3 pulse-alert" role="alert">
        <h6 class="fw-bold mb-2">🔴 CLASS IS LIVE!</h6>
        <p class="small mb-2" id="liveClassName"></p>
        <button id="joinClassBtn" class="btn btn-success btn-sm game-btn-join w-100">
            🚀 JOIN NOW
        </button>
    </div>
    
    <ul class="nav nav-pills flex-column mb-auto gap-2">
        
        <li class="nav-item">
            <a href="dashboard.php" class="game-btn <?php echo ($current_page == 'dashboard.php' && !isset($_GET['subject_id'])) ? 'active-btn blue-theme' : 'white-theme'; ?>">
                <span class="icon">🏠</span> 
                <span class="text">Base Camp</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="profile.php" class="game-btn <?php echo ($current_page == 'profile.php') ? 'active-btn purple-theme' : 'white-theme'; ?>">
                <span class="icon">🦸</span> 
                <span class="text">My Hero</span>
            </a>
        </li>

        <li class="nav-item">
            <a href="quest.php" class="game-btn <?php echo ($current_page == 'quest.php') ? 'active-btn orange-theme' : 'white-theme'; ?>">
                <span class="icon">📜</span> 
                <span class="text">Quest Logs</span>
            </a>
        </li>

        <hr class="dashed-line">

        <li class="nav-item">
            <div class="game-btn yellow-theme dropdown-trigger" onclick="toggleSubjects()">
                <span class="icon">📚</span> 
                <span class="text">My Classes ▼</span>
            </div>
            
            <div class="subject-list" id="subjectMenu" style="display: none;">
                <?php if (empty($enrolled_subjects)): ?>
                    <div class="empty-msg">No classes yet!</div>
                <?php else: ?>
                    <?php foreach($enrolled_subjects as $sub): 
                        // Check if this subject is currently active in URL
                        $isActive = (isset($_GET['subject_id']) && $_GET['subject_id'] == $sub['subject_id']);
                    ?>
                        <a href="dashboard.php?subject_id=<?php echo $sub['subject_id']; ?>" 
                           class="sub-item <?php echo $isActive ? 'active-sub' : ''; ?>">
                           ✨ <?php echo htmlspecialchars($sub['name']); ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </li>

    </ul>

    <div class="mt-auto pt-3">
        <a href="../auth/logout.php" target="_top" class="game-btn red-theme w-100 text-center justify-content-center">
            🚪 Bye Bye!
        </a>
    </div>
</div>

<script>
function toggleSubjects() {
    var menu = document.getElementById("subjectMenu");
    if (menu.style.display === "none") {
        menu.style.display = "block";
        menu.classList.add("bounce-in"); // Add animation class
    } else {
        menu.style.display = "none";
    }
}

// Live Class Polling
function checkLiveClass() {
    fetch('check_live_class.php')
        .then(response => response.json())
        .then(data => {
            const alert = document.getElementById('liveClassAlert');
            const joinBtn = document.getElementById('joinClassBtn');
            
            if (data.is_live) {
                // Show alert with class info
                document.getElementById('liveClassName').textContent = data.class_name + ' - ' + data.subject_name;
                
                // Simply navigate to focus mode
                joinBtn.onclick = function() {
                    window.location.href = 'focus_mode.php?session_id=' + data.session_id;
                };
                
                alert.style.display = 'block';
            } else {
                alert.style.display = 'none';
            }
        })
        .catch(error => console.error('Error checking live class:', error));
}

// Check immediately on load
checkLiveClass();

// Then check every 10 seconds
setInterval(checkLiveClass, 10000);
</script>

<style>
/* 1. Base Container */
.sidebar-container {
    background-color: #E0F7FA; /* Light Sky Blue */
    background-image: radial-gradient(#B2EBF2 15%, transparent 16%), radial-gradient(#B2EBF2 15%, transparent 16%);
    background-size: 20px 20px;
    background-position: 0 0, 10px 10px;
    border-right: 5px solid #4DD0E1;
    font-family: 'Nunito', sans-serif;
}

/* 2. Profile Card */
.profile-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 8px 0px rgba(0,0,0,0.1);
    border: 3px solid #4DD0E1;
}
.hero-avatar {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    border: 4px solid #FFD700; /* Gold */
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    object-fit: cover;
}
.level-badge {
    position: absolute;
    bottom: -5px;
    right: 70px; /* Adjust based on centering */
    background: #FF5252;
    color: white;
    padding: 2px 10px;
    border-radius: 15px;
    font-weight: 900;
    font-size: 0.8rem;
    border: 2px solid white;
}
.fw-black { font-weight: 900; }

/* 3. Game Buttons (The Menu Items) */
.game-btn {
    display: flex;
    align-items: center;
    padding: 12px 15px;
    border-radius: 15px;
    text-decoration: none;
    font-weight: 800;
    font-size: 1.1rem;
    transition: transform 0.1s, box-shadow 0.1s;
    border: 2px solid rgba(0,0,0,0.05);
    cursor: pointer;
}
.game-btn:active {
    transform: translateY(4px);
    box-shadow: none !important;
}
.game-btn .icon {
    font-size: 1.5rem;
    margin-right: 15px;
}

/* Color Themes */
.white-theme {
    background: white;
    color: #455A64;
    box-shadow: 0 5px 0px #CFD8DC;
}
.white-theme:hover {
    background: #FFF9C4; /* Light yellow on hover */
    transform: translateY(-2px);
}

.active-btn {
    color: white !important;
    transform: translateY(2px);
    box-shadow: 0 2px 0px rgba(0,0,0,0.2) !important;
}

.blue-theme { background: #29B6F6; box-shadow: 0 5px 0px #0288D1; border: 2px solid #0288D1; }
.purple-theme { background: #AB47BC; box-shadow: 0 5px 0px #7B1FA2; border: 2px solid #7B1FA2; }
.orange-theme { background: #FF7043; box-shadow: 0 5px 0px #E64A19; border: 2px solid #E64A19; }
.yellow-theme { background: #FFCA28; color: #5D4037; box-shadow: 0 5px 0px #FFA000; border: 2px solid #FFA000; }
.red-theme { background: #EF5350; color: white; box-shadow: 0 5px 0px #D32F2F; border: 2px solid #D32F2F; }

/* 4. Subject Dropdown */
.subject-list {
    margin-top: 10px;
    background: rgba(255,255,255,0.6);
    border-radius: 15px;
    padding: 10px;
    border: 2px dashed #4DD0E1;
}
.sub-item {
    display: block;
    padding: 8px;
    margin-bottom: 5px;
    background: white;
    border-radius: 10px;
    text-decoration: none;
    color: #00838F;
    font-weight: 700;
    transition: all 0.2s;
}
.sub-item:hover {
    background: #B2EBF2;
    padding-left: 15px;
}
.active-sub {
    background: #00BCD4;
    color: white;
    box-shadow: 0 3px 0 rgba(0,0,0,0.1);
}

.dashed-line {
    border-top: 3px dashed #B2EBF2;
    opacity: 1;
}

/* Animation */
@keyframes bounceIn {
    0% { transform: scale(0.8); opacity: 0; }
    60% { transform: scale(1.1); opacity: 1; }
    100% { transform: scale(1); }
}
.bounce-in {
    animation: bounceIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

/* Live Class Alert Pulse */
@keyframes pulse {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
}

.pulse-alert {
    animation: pulse 2s infinite;
    border-radius: 15px !important;
    border: 3px solid #dc3545 !important;
}

.game-btn-join {
    background: linear-gradient(135deg, #4CAF50, #45a049);
    color: white;
    font-weight: 800;
    text-transform: uppercase;
    border: 3px solid #388E3C;
    border-radius: 15px;
    padding: 10px;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    box-shadow: 0 5px 10px rgba(76, 175, 80, 0.3);
}

.game-btn-join:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 10px 20px rgba(76, 175, 80, 0.5);
    text-decoration: none;
}
</style>