<?php
// admin/payments.php
session_start();
require_once '../config/db.php';

// Security
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// HANDLE MANUAL PAYMENT ADDITION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $student_id = $_POST['student_id'];
    $amount = $_POST['amount'];
    $method = $_POST['method']; // cash, transfer, etc
    $ref = $_POST['ref'] ?? 'MANUAL-' . time();
    
    // Insert Payment
    $stmt = $pdo->prepare("INSERT INTO payments (student_id, amount, status, payment_method, transaction_ref, payment_date) VALUES (?, ?, 'completed', ?, ?, NOW())");
    
    if ($stmt->execute([$student_id, $amount, $method, $ref])) {
        // Find Student to optionally update expiry (Placeholder for now until User table schema is confirmed)
        // $pdo->prepare("UPDATE users SET expiry_date = DATE_ADD(expiry_date, INTERVAL 1 MONTH) WHERE user_id = ?")->execute([$student_id]);
        
        $success = "Payment recorded successfully!";
    } else {
        $error = "Failed to record payment.";
    }
}

// FETCH PAYMENTS
$stmt = $pdo->query("SELECT p.*, u.full_name, u.email 
                     FROM payments p 
                     JOIN users u ON p.student_id = u.user_id 
                     ORDER BY p.payment_date DESC");
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// FETCH STUDENTS FOR DROPDOWN
$students = $pdo->query("SELECT user_id, full_name, email FROM users WHERE role = 'student' ORDER BY full_name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Finance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow-1 p-4">
        <h2 class="fw-bold mb-4">💰 Financials</h2>

        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Manual Entry Form -->
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-warning text-dark fw-bold">
                         ➕ Record Manual Payment
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Student</label>
                                <select name="student_id" class="form-select" required>
                                    <option value="">Select Student...</option>
                                    <?php foreach($students as $s): ?>
                                        <option value="<?php echo $s['user_id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?> (<?php echo $s['email']; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount (RM)</label>
                                <input type="number" step="0.01" name="amount" class="form-control" placeholder="50.00" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Method</label>
                                <select name="method" class="form-select">
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cdm">CDM (Cash Machine)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reference ID (Optional)</label>
                                <input type="text" name="ref" class="form-control" placeholder="Ref No.">
                            </div>
                            <button type="submit" name="add_payment" class="btn btn-dark w-100">Record Transaction</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Transaction History -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white fw-bold">
                        📜 Transaction History
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Date</th>
                                    <th>Student</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($payments as $p): ?>
                                    <tr>
                                        <td class="ps-3 small text-muted"><?php echo date('M d, H:i', strtotime($p['payment_date'])); ?></td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($p['full_name']); ?></div>
                                            <small class="text-muted"><?php echo $p['email']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?php echo strtoupper(str_replace('_', ' ', $p['payment_method'])); ?>
                                            </span>
                                            <div class="small text-muted mt-1"><?php echo $p['transaction_ref']; ?></div>
                                        </td>
                                        <td class="fw-bold text-success">
                                            RM <?php echo number_format($p['amount'], 2); ?>
                                        </td>
                                        <td>
                                            <?php if($p['status'] == 'completed'): ?>
                                                <span class="badge bg-success">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><?php echo ucfirst($p['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
