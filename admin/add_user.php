<?php
// admin/add_user.php
session_start();
require_once '../config/db.php';

// Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $name = $_POST['full_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $expiry = ($_POST['expiry_date']) ? $_POST['expiry_date'] : NULL;
    
    // Check Email or Username
    $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? OR username = ?");
    $check->execute([$email, $username]);
    if ($check->fetch()) {
        $error = "User with this email or username already exists.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role, subscription_expiry, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if ($stmt->execute([$name, $username, $email, $password, $role, $expiry])) {
            $success = "User created successfully!";
        } else {
            $error = "Failed to create user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <h2 class="fw-bold mb-4">
            <a href="manage_users.php" class="text-decoration-none text-muted">Users</a>
            <span class="text-muted mx-2">/</span>
            Create New User
        </h2>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0" style="max-width: 600px;">
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" value="123456" required>
                        <div class="form-text">Default: 123456. User should change this after login.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" id="roleSelect" class="form-select" onchange="toggleExpiry()">
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="parent">Parent</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <div class="mb-4" id="expiryField">
                        <label class="form-label">Subscription Expiry (Students Only)</label>
                        <input type="date" name="expiry_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                    </div>

                    <button type="submit" name="create_user" class="btn btn-primary w-100 fw-bold">Create Account</button>
                    <a href="manage_users.php" class="btn btn-light w-100 mt-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleExpiry() {
        const role = document.getElementById('roleSelect').value;
        const field = document.getElementById('expiryField');
        if (role === 'student') {
            field.style.display = 'block';
        } else {
            field.style.display = 'none';
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
