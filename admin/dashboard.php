<?php
// admin/dashboard.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// 1. Fetch Key Stats
// Count Students
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_students = $stmt->fetchColumn();

// Count Parents
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'parent'");
$total_parents = $stmt->fetchColumn();

// Count Quizzes (Quests)
$stmt = $pdo->query("SELECT COUNT(*) FROM quizzes");
$total_quizzes = $stmt->fetchColumn();

// Count Materials (Scrolls)
$stmt = $pdo->query("SELECT COUNT(*) FROM materials");
$total_materials = $stmt->fetchColumn();

// --- NEW METRICS ---

// Total Revenue (Completed)
$stmt = $pdo->query("SELECT SUM(amount) FROM payments WHERE status = 'completed'");
$total_revenue = $stmt->fetchColumn() ?: 0;

// Active Live Classes
$stmt = $pdo->query("SELECT COUNT(*) FROM live_sessions WHERE ended_at IS NULL");
$active_classes = $stmt->fetchColumn();

// 2. Fetch Recent Users (Instead of Access Logs)
$stmt = $pdo->query("SELECT full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Game Master Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<div class="d-flex">
    
    <?php include 'sidebar.php'; ?>

    <div class="flex-grow-1 p-4">
        <h2 class="fw-bold text-primary mb-4">👑 Game Master Dashboard</h2>
        
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body">
                <h5 class="fw-bold mb-3">⚡ Quick Actions</h5>
                <div class="d-flex gap-3">
                    <a href="add_questions.php" class="btn btn-danger fw-bold">
                        ➕ Add Quiz Questions (New)
                    </a>
                    <a href="manage_users.php" class="btn btn-primary">
                        👥 Manage Users
                    </a>
                    <a href="payments.php" class="btn btn-warning text-dark fw-bold">
                        💰 Manage Finance
                    </a>
                    <a href="upload_material.php" class="btn btn-success">
                        📜 Upload Note
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Revenue -->
            <div class="col-md-3">
                <div class="card stat-card bg-success text-white p-3 border-0 shadow-sm">
                    <h3>RM <?php echo number_format($total_revenue, 2); ?></h3>
                    <h6>Total Revenue</h6>
                </div>
            </div>
            
            <!-- Active Classes -->
            <div class="col-md-3">
                <div class="card stat-card <?php echo ($active_classes > 0) ? 'bg-danger text-white' : 'bg-secondary text-white'; ?> p-3 border-0 shadow-sm">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3><?php echo $active_classes; ?></h3>
                        <?php if($active_classes > 0): ?>
                            <span class="spinner-grow spinner-grow-sm" role="status"></span>
                        <?php endif; ?>
                    </div>
                    <h6>Live Classes Now</h6>
                </div>
            </div>

            <!-- Students -->
            <div class="col-md-3">
                <div class="card stat-card bg-primary text-white p-3 border-0 shadow-sm">
                    <h3><?php echo $total_students; ?></h3>
                    <h6>Active Heroes (Students)</h6>
                </div>
            </div>
            
            <!-- Quizzes -->
            <div class="col-md-3">
                <div class="card stat-card bg-warning text-dark p-3 border-0 shadow-sm">
                    <h3><?php echo $total_quizzes; ?></h3>
                    <h6>Active Quests</h6>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold">🆕 Newest Members</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Role</th>
                            <th>Joined Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_users as $user): ?>
                            <tr>
                                <td class="ps-4 fw-bold"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td>
                                    <span class="badge <?php echo ($user['role']=='student')?'bg-success':'bg-secondary'; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="text-muted small"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>