<?php
// admin/subjects.php
session_start();
require_once '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Handle CRUD
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'delete') {
        $id = $_POST['subject_id'];
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ?");
        $stmt->execute([$id]);
        $msg = "Subject deleted!";
    } elseif ($_POST['action'] == 'edit') {
        $id = $_POST['subject_id'];
        $name = $_POST['name'];
        $desc = $_POST['description'];
        $stmt = $pdo->prepare("UPDATE subjects SET name = ?, description = ? WHERE subject_id = ?");
        $stmt->execute([$name, $desc, $id]);
        $msg = "Subject updated!";
    } elseif ($_POST['action'] == 'create') {
        $name = $_POST['name'];
        $desc = $_POST['description'];
        
        $stmt = $pdo->prepare("INSERT INTO subjects (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $desc]);
        $msg = "Subject created successfully!";
    }
}

// Fetch All Subjects
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY created_at DESC");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curriculum Manager - Subjects</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #E0F7FA;
            font-family: 'Nunito', sans-serif;
        }
        .subject-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            border-radius: 20px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }
        .subject-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .game-btn {
            background: #FFD93D;
            border: 3px solid #ffcc00;
            color: #5a4a00;
            font-weight: 800;
            border-radius: 50px;
            padding: 8px 20px;
            text-decoration: none;
            display: inline-block;
            transition: 0.2s;
        }
        .game-btn:hover {
            background: #ffe066;
            transform: scale(1.05);
        }
        .action-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            display: none;
            z-index: 10;
        }
        .subject-card:hover .action-overlay {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4" style="background-color: #E0F7FA;">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h1 class="fw-bold text-dark mb-0">📚 Curriculum Engine</h1>
                <p class="text-muted lead">Manage Master Content by Subject</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-dark rounded-pill fw-bold">⬅ Back to Dashboard</a>
        </div>
        
        <?php if(isset($msg)): ?>
            <div class="alert alert-success rounded-pill text-center fw-bold mb-4"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Add New Subject Card -->
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100 subject-card bg-light border-2 border-dashed d-flex justify-content-center align-items-center text-center p-4" 
                     style="border-style: dashed !important; border-color: #cbd5e1 !important; cursor: pointer;"
                     data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                    <div class="text-muted">
                        <span class="fs-1 d-block mb-2">➕</span>
                        <span class="fw-bold">Add New Subject</span>
                    </div>
                </div>
            </div>

            <?php foreach($subjects as $subject): ?>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="card h-100 subject-card shadow-sm">
                    <!-- Action Buttons -->
                    <div class="action-overlay">
                        <button class="btn btn-sm btn-light rounded-circle shadow-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $subject['subject_id']; ?>">✏</button>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete subject and all its content?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
                            <button class="btn btn-sm btn-danger rounded-circle shadow-sm">🗑</button>
                        </form>
                    </div>

                    <a href="subject_weeks.php?subject_id=<?php echo $subject['subject_id']; ?>" class="text-decoration-none text-dark h-100 d-flex flex-column">
                        <div class="card-body text-center p-5 flex-grow-1 d-flex flex-column justify-content-center">
                            <div class="mb-4">
                                <span class="display-1">📚</span>
                            </div>
                            <h4 class="fw-bold mb-2"><?php echo htmlspecialchars($subject['name']); ?></h4>
                            <p class="text-muted small mb-3">
                                <?php echo htmlspecialchars(substr($subject['description'] ?? '', 0, 80)) . (strlen($subject['description'] ?? '') > 80 ? '...' : ''); ?>
                            </p>
                            <span class="game-btn mt-auto">Manage Curriculum ➡</span>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="editModal<?php echo $subject['subject_id']; ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Subject</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
                                <div class="mb-3">
                                    <label>Name</label>
                                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($subject['name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($subject['description']); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Add Subject Modal -->
        <div class="modal fade" id="addSubjectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Subject</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Subject Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Create Subject</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
