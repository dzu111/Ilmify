<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Music Debugger</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #222; color: #fff; padding: 50px; font-family: monospace; }
        #console-log { background: #000; border: 1px solid #444; padding: 20px; height: 300px; overflow-y: scroll; color: #0f0; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-warning">🎵 Audio Diagnostic Tool</h2>
    <p>Click the button below to force the browser to find and play the file.</p>

    <audio id="test-audio" controls style="width: 100%; margin-bottom: 20px;">
        <source src="/tinytale/assets/audio/bg_music.mp3" type="audio/mpeg">
    </audio>

    <button id="btn-test" class="btn btn-lg btn-primary mb-3">▶ RUN DIAGNOSTIC TEST</button>
    
    <h4>Log Output:</h4>
    <div id="console-log">Waiting for test...</div>
</div>

<script>
    const audio = document.getElementById('test-audio');
    const logBox = document.getElementById('console-log');
    const btn = document.getElementById('btn-test');

    function log(msg, color = '#0f0') {
        const time = new Date().toLocaleTimeString();
        logBox.innerHTML += `<div style="color:${color}">[${time}] ${msg}</div>`;
        logBox.scrollTop = logBox.scrollHeight;
    }

    // 1. Check path immediately
    log("Page Loaded. Checking path...");
    log("Target Source: " + audio.querySelector('source').src);

    // 2. Event Listeners for Errors
    audio.addEventListener('error', (e) => {
        const err = audio.error;
        let codeMsg = "Unknown";
        if (err.code === 1) codeMsg = "MEDIA_ERR_ABORTED (Fetch aborted)";
        if (err.code === 2) codeMsg = "MEDIA_ERR_NETWORK (Network error - File not found?)";
        if (err.code === 3) codeMsg = "MEDIA_ERR_DECODE (Corrupt file or wrong format)";
        if (err.code === 4) codeMsg = "MEDIA_ERR_SRC_NOT_SUPPORTED (Format not supported)";
        
        log(`❌ ERROR DETECTED: Code ${err.code} - ${codeMsg}`, '#f55');
        log("👉 CHECK: Does the file exist at 'tinytale/assets/audio/bg_music.mp3'?", '#ff0');
    });

    audio.addEventListener('canplaythrough', () => {
        log("✅ SUCCESS: File found and buffered!", '#fff');
    });

    // 3. Test Button
    btn.addEventListener('click', () => {
        log("Attempting to play...", '#aaa');
        audio.play()
            .then(() => {
                log("✅ PLAYING: Audio is playing successfully.", '#fff');
            })
            .catch((e) => {
                log("⚠️ PLAY FAILED: " + e.message, '#f55');
                log("This might be a browser policy blocking auto-play.", '#aaa');
            });
    });
</script>

</body>
</html>