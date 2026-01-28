<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Check user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Set Session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];

        // Redirect based on Role
        if ($user['role'] === 'admin') header("Location: ../admin/dashboard.php");
        elseif ($user['role'] === 'parent') header("Location: ../parent/dashboard.php");
        elseif ($user['role'] === 'student') header("Location: ../student/portal.php");
        exit;
    } else {
        $error = "Invalid credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StudyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); /* Magical purple/blue bg */
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            border: 4px solid #FFD700; /* Gold border for game feel */
        }
        .btn-game {
            background-color: #ff6b6b;
            color: white;
            font-weight: bold;
            border: none;
            box-shadow: 0 4px 0 #c92a2a; /* 3D button effect */
        }
        .btn-game:active {
            transform: translateY(4px);
            box-shadow: none;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card login-card p-4">
                <div class="text-center mb-4">
                    <h2 class="fw-bold text-primary">⚔️ Enter World</h2>
                    <p class="text-muted">Resume your adventure</p>
                </div>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" name="username" class="form-control form-control-lg" placeholder="Hero Name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" class="form-control form-control-lg" placeholder="Secret Key" required>
                    </div>
                    <button type="submit" class="btn btn-game w-100 py-2 mt-3">START GAME</button>
                    <div class="text-center mt-3">
                        <a href="register.php" class="text-decoration-none">New Player? Sign Up</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>