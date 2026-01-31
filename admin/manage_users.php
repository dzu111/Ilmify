<?php
// admin/manage_users.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$message = "";
$error = "";

// --- 1. HANDLE EDIT USER ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $id = $_POST['edit_user_id'];
    $name = trim($_POST['edit_name']);
    $email = trim($_POST['edit_email']);
    $password = trim($_POST['edit_password']);
    
    if ($name && $email) {
        $expiry = !empty($_POST['edit_expiry']) ? $_POST['edit_expiry'] : NULL;
        
        if (!empty($password)) {
            // Update with new password
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, password=?, subscription_expiry=? WHERE user_id=?");
            $stmt->execute([$name, $email, $hashed, $expiry, $id]);
        } else {
            // Update info only
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, email=?, subscription_expiry=? WHERE user_id=?");
            $stmt->execute([$name, $email, $expiry, $id]);
        }
        $message = "✅ User details updated successfully.";
    } else {
        $error = "❌ Name and Email cannot be empty.";
    }
}

// --- 2. HANDLE DELETE USER (FIXED WITH DEEP CLEAN) ---
if (isset($_POST['delete_id'])) {
    $id = $_POST['delete_id'];
    
    if ($id == $_SESSION['user_id']) {
        $error = "❌ You cannot delete your own account!";
    } else {
        try {
            // Start a Transaction (All or Nothing)
            $pdo->beginTransaction();

            // A. Delete Student Data (If they are a student)
            $pdo->prepare("DELETE FROM student_progress WHERE student_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM quiz_results WHERE student_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM student_task_claims WHERE student_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM student_reads WHERE student_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM user_pets WHERE student_id = ?")->execute([$id]);

            // B. Handle Content Creators (If they uploaded stuff)
            // Note: If an admin created quizzes, we usually DON'T want to delete them. 
            // But if you must, uncomment the lines below:
            // $pdo->prepare("DELETE FROM quizzes WHERE created_by = ?")->execute([$id]);
            // $pdo->prepare("DELETE FROM materials WHERE uploaded_by = ?")->execute([$id]);

            // C. Finally, Delete the User
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$id]);

            // Commit changes
            $pdo->commit();
            $message = "🗑️ User and all associated data deleted.";

        } catch (Exception $e) {
            // If anything fails, undo everything
            $pdo->rollBack();
            $error = "❌ Error deleting user: " . $e->getMessage();
        }
    }
}

// --- 3. FETCH USERS ---
$users = $pdo->query("SELECT * FROM users ORDER BY role ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .role-badge { font-size: 0.8rem; padding: 5px 10px; border-radius: 15px; min-width: 80px; display: inline-block; text-align: center; }
        .role-student { background: #0d6efd; color: white; }
        .role-parent { background: #198754; color: white; }
        .role-admin { background: #dc3545; color: white; }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4" style="height: 100vh; overflow-y: auto;">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-danger m-0">👥 User Command Center</h2>
            <a href="add_user.php" class="btn btn-dark fw-bold">➕ Create New User</a>
        </div>

        <?php if($message): ?><div class="alert alert-success alert-dismissible fade show"><?php echo $message; ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

        <div class="table-responsive bg-white rounded shadow-sm p-3">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Pic</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Subscription</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <?php 
                                    $pic = ($u['profile_picture'] == 'default_avatar.jpg' || $u['profile_picture'] == 'default_avatar.png') 
                                            ? "../assets/img/default_avatar.jpg" 
                                            : "../uploads/" . $u['profile_picture'];
                                ?>
                                <img src="<?php echo $pic; ?>" class="rounded-circle border" width="40" height="40" style="object-fit: cover;">
                            </td>
                            <td class="fw-bold"><?php echo htmlspecialchars($u['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <span class="role-badge role-<?php echo $u['role']; ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($u['role'] == 'student'): ?>
                                    <?php 
                                        $exp = $u['subscription_expiry']; 
                                        if($exp):
                                            $days_left = (strtotime($exp) - time()) / (60 * 60 * 24);
                                            $badge = ($days_left > 0) ? 'bg-success' : 'bg-danger';
                                    ?>
                                        <span class="badge <?php echo $badge; ?>">
                                            <?php echo date('M d, Y', strtotime($exp)); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">No Sub</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-outline-primary btn-sm rounded-pill px-3 me-1" 
                                    onclick="openEditModal(<?php echo $u['user_id']; ?>, '<?php echo addslashes($u['full_name']); ?>', '<?php echo $u['email']; ?>', '<?php echo $u['subscription_expiry']; ?>')">
                                    ✏️ Edit
                                </button>
                                
                                <form method="POST" class="d-inline" onsubmit="return confirm('⚠️ WARNING: This will delete the user AND all their progress, pets, and quiz results. Are you sure?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $u['user_id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">🗑️</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Edit User Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="edit_user_id" id="edit_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="edit_name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="edit_email" id="edit_email" class="form-control" required>
                        <div class="form-text text-muted">Ensure Parent emails match <code>parent_</code> + Student Email format for linking.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (Optional)</label>
                        <input type="text" name="edit_password" class="form-control" placeholder="Leave blank to keep current">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Subscription Expiry (Student Only)</label>
                        <input type="date" name="edit_expiry" id="edit_expiry" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_user" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function openEditModal(id, name, email, expiry) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_expiry').value = expiry ? expiry.split(' ')[0] : '';
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }
</script>
</body>
</html>