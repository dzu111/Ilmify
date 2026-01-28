<?php
// auth/register.php
require_once '../config/db.php';

$success_msg = "";
$error_msg = "";
$parent_info = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $rawPassword = $_POST['password']; 
    $password = password_hash($rawPassword, PASSWORD_DEFAULT);
    $fullName = trim($_POST['full_name']);

    try {
        $pdo->beginTransaction(); 

        // 1. Create Student
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'student')");
        $stmt->execute([$username, $email, $password, $fullName]);
        $studentId = $pdo->lastInsertId();

        // 2. Create Parent automatically
        // Username logic: username_parent
        $parentUser = $username . '_parent';
        $parentEmail = 'parent_' . $email; // Kept for backend linking logic
        $parentHashPass = password_hash($rawPassword, PASSWORD_DEFAULT); 
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'parent')");
        $stmt->execute([$parentUser, $parentEmail, $parentHashPass, "Parent of $fullName"]);
        $parentId = $pdo->lastInsertId();

        // 3. Initialize Student Progress
        $stmt = $pdo->prepare("INSERT INTO student_progress (student_id, current_level, current_xp) VALUES (?, 1, 0)");
        $stmt->execute([$studentId]);
        
        // 4. Initialize Pet
        $stmt = $pdo->prepare("INSERT INTO user_pets (student_id, pet_name) VALUES (?, 'Buddy')");
        $stmt->execute([$studentId]);

        $pdo->commit(); 
        
        $success_msg = "Character Created Successfully!";
        
        // --- OUTPUT CHANGED HERE ---
        $parent_info = [
            'username' => $parentUser, // Now showing Username
            'password' => $rawPassword
        ];

    } catch (PDOException $e) {
        $pdo->rollBack(); 
        
        if ($e->getCode() == 23000) {
            $error_msg = "❌ That username or email is already taken. Try another!";
        } else {
            $error_msg = "❌ System Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Character - StudyQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 4px solid #f1c40f;
            max-width: 500px;
            width: 100%;
        }
        .btn-game {
            background-color: #27ae60;
            color: white;
            font-weight: bold;
            border: none;
            box-shadow: 0 4px 0 #1e8449;
            transition: all 0.1s;
        }
        .btn-game:active {
            transform: translateY(4px);
            box-shadow: none;
        }
        .form-label {
            font-weight: bold;
            color: #2c3e50;
        }
        .parent-alert {
            background-color: #fff3cd;
            border: 2px dashed #ffc107;
            color: #856404;
        }
    </style>
</head>
<body>

<div class="container p-3">
    <div class="register-card p-4 mx-auto">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-success">🛡️ Create New Character</h2>
            <p class="text-muted">Join the quest for knowledge!</p>
        </div>

        <?php if($success_msg): ?>
            <div class="alert alert-success text-center">
                <h4>✨ <?php echo $success_msg; ?></h4>
            </div>
            
            <?php if($parent_info): ?>
            <div class="alert parent-alert p-3 mb-4">
                <h5 class="fw-bold">👨‍👩‍👧 Parent Account Generated!</h5>
                <p class="mb-1">Your parent can login with these details:</p>
                <hr>
                <p><strong>Parent Username:</strong> <code><?php echo $parent_info['username']; ?></code></p>
                <p><strong>Password:</strong> <code><?php echo $parent_info['password']; ?></code></p>
            </div>
            <div class="text-center">
                <a href="login.php" class="btn btn-primary btn-lg w-100">➡️ Go to Login</a>
            </div>
            <?php endif; ?>

        <?php else: ?>

            <?php if($error_msg): ?>
                <div class="alert alert-danger"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label">📜 Hero Name (Full Name)</label>
                    <input type="text" name="full_name" class="form-control form-control-lg" placeholder="e.g. Arthur Pendragon" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">📧 Email Address</label>
                    <input type="email" name="email" class="form-control form-control-lg" placeholder="hero@email.com" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">👤 Username</label>
                    <input type="text" name="username" class="form-control form-control-lg" placeholder="Choose a unique ID" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">🔑 Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" placeholder="Keep it secret!" required>
                </div>

                <button type="submit" class="btn btn-game w-100 py-3 mt-2">✨ SUMMON HERO</button>
            </form>

            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none text-secondary">Already have a hero? <strong>Login here</strong></a>
            </div>

        <?php endif; ?>
    </div>
</div>

</body>
</html>