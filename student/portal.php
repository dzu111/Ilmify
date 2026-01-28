<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyQuest</title>
    <link rel="icon" type="image/png" href="/tinytale/assets/img/favicon.png">
    
    <style>
        /* 1. Fix Black Screen Flash: Use light grey instead of black */
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; background: #f8f9fa; }
        
        iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        /* 2. The Draggable Widget */
        #widget-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            user-select: none; 
        }

        /* 3. The Interaction Menu (Feed/Pet) - Hidden by default */
        .pet-menu {
            display: none; /* Hidden */
            background: white;
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            margin-bottom: 5px;
            gap: 5px;
            animation: popUp 0.2s ease-out;
        }
        
        .btn-action {
            border: none;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.1s;
        }
        .btn-action:hover { transform: scale(1.1); }
        .btn-feed { background: #ff9f43; color: white; }
        .btn-love { background: #ff6b6b; color: white; }

        /* 4. The Pet Image */
        #pet-img {
            width: 100px;
            cursor: grab; /* Shows it can be dragged */
            transition: transform 0.1s;
        }
        #pet-img:active { cursor: grabbing; transform: scale(0.95); }

        /* 5. Music Controls (Always visible below pet) */
        .music-box {
            background: rgba(44, 62, 80, 0.8); /* Dark Blue semi-transparent */
            padding: 5px 15px;
            border-radius: 20px;
            margin-top: -5px; /* Tuck it slightly under the pet */
            backdrop-filter: blur(5px);
            display: flex;
            justify-content: center;
            border: 2px solid rgba(255,255,255,0.2);
        }

        .btn-music {
            border: none;
            background: none;
            color: #f1c40f; /* Yellow */
            font-size: 18px;
            cursor: pointer;
            padding: 0 5px;
        }
        .btn-music:hover { color: white; }

        @keyframes popUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <audio id="bg-music" loop>
        <source src="/tinytale/assets/audio/bg_music.mp3" type="audio/mpeg">
    </audio>

    <div id="widget-container">
        
        <div id="pet-menu" class="pet-menu">
            <button class="btn-action btn-feed" onclick="interact('feed')">🍎 Feed</button>
            <button class="btn-action btn-love" onclick="interact('pet')">👋 Pet</button>
        </div>

        <img id="pet-img" src="/tinytale/assets/img/pet_idle.gif" alt="Pet">
        
        <div class="music-box">
            <button id="play-btn" class="btn-music">▶</button>
            <button id="pause-btn" class="btn-music" style="display: none;">⏸</button>
        </div>
    </div>

    <iframe src="dashboard.php" name="content_frame" id="main-frame"></iframe>

    <script>
        // --- 1. DRAG LOGIC ---
        const widget = document.getElementById('widget-container');
        const petImg = document.getElementById('pet-img');
        let isDragging = false;
        let hasMoved = false; // To distinguish between Click and Drag
        let offset = { x: 0, y: 0 };

        petImg.addEventListener('mousedown', (e) => {
            isDragging = true;
            hasMoved = false;
            offset.x = e.clientX - widget.getBoundingClientRect().left;
            offset.y = e.clientY - widget.getBoundingClientRect().top;
            widget.style.cursor = 'grabbing';
        });

        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            hasMoved = true; // We are moving, so it's not a click
            e.preventDefault();
            widget.style.left = (e.clientX - offset.x) + 'px';
            widget.style.top = (e.clientY - offset.y) + 'px';
            widget.style.right = 'auto';
            widget.style.bottom = 'auto';
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
            widget.style.cursor = 'default';
        });

        // --- 2. PET INTERACTION LOGIC ---
        const petMenu = document.getElementById('pet-menu');
        
        // Show/Hide Menu on Click (Only if we didn't drag)
        petImg.addEventListener('click', () => {
            if (!hasMoved) {
                petMenu.style.display = (petMenu.style.display === 'flex') ? 'none' : 'flex';
            }
        });

        function interact(action) {
            petMenu.style.display = 'none'; // Hide menu

            if (action === 'feed') {
                petImg.src = "/tinytale/assets/img/pet_feed.gif";
            } else if (action === 'pet') {
                petImg.src = "/tinytale/assets/img/pet_happy.gif";
            }

            // Return to Idle after 3 seconds
            setTimeout(() => {
                petImg.src = "/tinytale/assets/img/pet_idle.gif";
            }, 3000);
        }

        // --- 3. MUSIC LOGIC ---
        const audio = document.getElementById('bg-music');
        const playBtn = document.getElementById('play-btn');
        const pauseBtn = document.getElementById('pause-btn');
        const iframe = document.getElementById('main-frame');

        playBtn.addEventListener('click', () => { audio.play(); showPause(); });
        pauseBtn.addEventListener('click', () => { audio.pause(); showPlay(); });

        function showPlay() { playBtn.style.display = 'inline-block'; pauseBtn.style.display = 'none'; }
        function showPause() { playBtn.style.display = 'none'; pauseBtn.style.display = 'inline-block'; }

        // Auto-pause for Videos
        iframe.addEventListener('load', function() {
            try {
                if (iframe.contentWindow.location.href.includes('view_video.php')) {
                    audio.pause();
                    showPlay();
                }
            } catch (e) {}
        });
    </script>
</body>
</html>