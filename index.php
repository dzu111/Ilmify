<?php
// index.php
session_start();

// If user is already logged in, redirect them to their dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyQuest - Level Up Your Learning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* HERO SECTION */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
        }
        
        /* Floating Shapes for Background Decoration */
        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 15s infinite linear;
        }
        .shape-1 { top: 10%; left: 10%; font-size: 5rem; animation-duration: 20s; }
        .shape-2 { bottom: 20%; right: 10%; font-size: 8rem; animation-duration: 25s; }
        .shape-3 { top: 40%; right: 30%; font-size: 4rem; animation-duration: 18s; }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
            100% { transform: translateY(0) rotate(0deg); }
        }

        .hero-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }

        .btn-game {
            background-color: #ffd700;
            color: #333;
            font-weight: 800;
            border: 4px solid #fff;
            box-shadow: 0 5px 0 #d4b106;
            transition: all 0.1s;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .btn-game:hover {
            transform: translateY(-2px);
            background-color: #ffeb3b;
            color: #000;
        }
        .btn-game:active {
            transform: translateY(4px);
            box-shadow: none;
        }

        /* FEATURES SECTION */
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .feature-card {
            border: none;
            transition: transform 0.3s;
            background: white;
            border-radius: 15px;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<section class="hero-section">
    
    <div class="shape shape-1">🎮</div>
    <div class="shape shape-2">📚</div>
    <div class="shape shape-3">✨</div>

    <div class="container">
        <div class="row align-items-center">
            
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="animate__animated animate__fadeInLeft">
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-3 fw-bold">v1.0 Ready to Play</span>
                    <h1 class="display-3 fw-bold mb-3">Turn Studying into an <span style="color: #ffd700;">Adventure</span></h1>
                    <p class="lead mb-5 text-white-50">
                        Join StudyQuest today. Complete quests, earn XP, battle through quizzes, and become the ultimate scholar.
                    </p>
                    
                    <div class="d-flex gap-3">
                        <a href="auth/login.php" class="btn btn-game btn-lg px-5 py-3 rounded-pill">
                            🚀 Start Game (Login)
                        </a>
                        </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="hero-card animate__animated animate__fadeInRight">
                    <div class="text-center">
                        <img src="https://cdn-icons-png.flaticon.com/512/2936/2936757.png" alt="Hero" class="img-fluid mb-4" style="max-height: 250px;">
                        <h3 class="fw-bold">Your Legend Awaits</h3>
                        <p class="mb-4">"The only way to master the future is to study the past... and answer 10 quiz questions correctly."</p>
                        
                        <div class="progress mb-2" style="height: 10px; background: rgba(255,255,255,0.2);">
                            <div class="progress-bar bg-warning" style="width: 75%"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-white-50">
                            <span>Loading Wisdom...</span>
                            <span>75%</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-secondary">Why Play StudyQuest?</h2>
            <p class="text-muted">It's not just homework, it's a mission.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center">
                    <div class="feature-icon">⚔️</div>
                    <h4 class="fw-bold">Episodic Quests</h4>
                    <p class="text-muted">Turn boring assignments into epic battles. Complete tasks to unlock new content.</p>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center">
                    <div class="feature-icon">📈</div>
                    <h4 class="fw-bold">XP & Leveling</h4>
                    <p class="text-muted">Watch your character grow! Earn XP for every note you read and every quiz you ace.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card feature-card p-4 text-center">
                    <div class="feature-icon">🏆</div>
                    <h4 class="fw-bold">Mastery Badges</h4>
                    <p class="text-muted">Prove your skills. Get instant feedback on your quizzes and track your progress.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="bg-dark text-white py-4">
    <div class="container text-center">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> StudyQuest. All rights reserved.</p>
        <small class="text-white-50">Designed for Gamified Learning.</small>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>