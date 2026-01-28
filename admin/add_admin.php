<?php
// admin/add_admin.php
session_start();
require_once '../config/db.php';

// --- SECURITY SETTINGS ---
// 🔒 CHANGE THIS TO YOUR SECRET KEY!
$MASTER_PASSKEY = "asep besar"; 

// Security Check: Current User must be Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['full_name']);
    $username = trim($_POST['username']); // <--- [NEW] Get Username
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $input_key = trim($_POST['passkey']);

    // 1. Verify Master Passkey
    if ($input_key !== $MASTER_PASSKEY) {
        $message = "⛔ ACCESS DENIED: Invalid Master Passkey.";
        $msg_type = "danger";
    } 
    // 2. Validate Inputs
    elseif (empty($name) || empty($username) || empty($email) || empty($password)) {
        $message = "⚠️ All fields are required.";
        $msg_type = "warning";
    } 
    else {
        // 3. Check if Email OR Username Exists
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        
        if ($stmt->fetch()) {
            $message = "❌ Error: Email or Username is already taken.";
            $msg_type = "danger";
        } else {
            // 4. Create New Admin
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $default_pic = "default_avatar.jpg";
            $role = "admin";

            try {
                // [UPDATED] Insert Query now includes 'username'
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role, profile_picture) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $username, $email, $hashed_pass, $role, $default_pic]);
                
                $message = "✅ New Game Master (Admin) added successfully!";
                $msg_type = "success";
            } catch (PDOException $e) {
                $message = "❌ Database Error: " . $e->getMessage();
                $msg_type = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Admin - Game Master</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-danger">🛡️ Recruit New Game Master</h2>
            <a href="manage_users.php" class="btn btn-outline-secondary">⬅ Back to Users</a>
        </div>

        <?php if($message): ?>
            <div class="alert alert-<?php echo $msg_type; ?> shadow-sm rounded-3">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="card shadow border-0 rounded-4" style="max-width: 600px; margin: 0 auto;">
            <div class="card-header bg-danger text-white py-3 rounded-top-4">
                <h5 class="mb-0 fw-bold">🔐 Admin Registration Form</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Master Wayne" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="e.g. admin_wayne" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="admin@tinytales.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Create a strong password" required>
                    </div>

                    <hr class="my-4">

                    <div class="mb-4 bg-light p-3 rounded border border-danger border-opacity-25">
                        <label class="form-label fw-bold text-danger">🔑 Master Authorization Key</label>
                        <input type="password" name="passkey" class="form-control border-danger" placeholder="Enter the secret Passkey to authorize this action" required>
                        <div class="form-text text-muted">Only authorized personnel have this key.</div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger btn-lg fw-bold">
                            🚀 Grant Admin Powers
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>