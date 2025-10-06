<?php
session_start(); 
require_once __DIR__.'/db.php';
if (!isset($_SESSION['user'])) { 
    header('Location: login.php'); 
    exit; 
}
$user_id = $_SESSION['user']['id'];
$orders = $pdo->prepare('SELECT o.*, b.title FROM orders o JOIN books b ON o.book_id=b.id WHERE o.user_id=:uid ORDER BY o.ordered_at DESC');
$orders->execute([':uid'=>$user_id]);
$rows = $orders->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="assets/logo.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="assets/logo.jpg" type="image/jpeg">
    <title>TokoBook - Pesanan Saya</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand { font-weight: bold; }
    </style>
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">TokoBook</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="my_orders.php">Pesanan Saya</a></li>
                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role']==='admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin/dashboard.php">Admin</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['user']['username']); ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="container my-5">
    <div class="row">
        <div class="col-md-10 mx-auto">
            <h2 class="mb-4">Pesanan Saya</h2>
            <?php if (empty($rows)): ?>
                <div class="alert alert-info text-center" role="alert">Belum ada pesanan.</div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Book</th>
                                        <th>Qty</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Ordered At</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($rows as $r): ?>
                                        <tr>
                                            <td><?php echo $r['id']; ?></td>
                                            <td><?php echo htmlspecialchars($r['title']); ?></td>
                                            <td><?php echo $r['quantity']; ?></td>
                                            <td>Rp <?php echo number_format($r['total_price'],2); ?></td>
                                            <td>
                                                <?php if ($r['status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php elseif ($r['status'] === 'paid'): ?>
                                                    <span class="badge bg-success">Paid</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($r['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($r['status'] === 'pending'): ?>
                                                    <?php require_once __DIR__ . '/csrf.php'; ?>
                                                    <div class="d-flex gap-2">
                                                        <form method="post" action="order_pay.php">
                                                            <input type="hidden" name="order_id" value="<?php echo $r['id']; ?>">
                                                            <?php echo csrf_input_field(); ?>
                                                            <button type="submit" class="btn btn-sm btn-success">Pay</button>
                                                        </form>
                                                        <form method="post" action="order_cancel.php" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                                            <input type="hidden" name="order_id" value="<?php echo $r['id']; ?>">
                                                            <?php echo csrf_input_field(); ?>
                                                            <button type="submit" class="btn btn-sm btn-danger">Cancel</button>
                                                        </form>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($r['ordered_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
            </div>
        </div>
    </div>
</main>

<footer class="bg-light py-3 text-center">
    <p>&copy; <?php echo date('Y'); ?> TokoBook</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>