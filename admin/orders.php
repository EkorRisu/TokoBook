<?php
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// ==== PROSES UPDATE STATUS / CANCEL ORDER ====
if (isset($_GET['mark_paid'])) {
    $orderId = (int)$_GET['mark_paid'];
    $pdo->prepare("UPDATE orders SET status = 'selesai' WHERE id = ?")->execute([$orderId]);
    header("Location: orders.php?success=paid");
    exit;
}

if (isset($_POST['cancel_order'])) {
    $orderId = (int)$_POST['cancel_order'];
    $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$orderId]);
    header("Location: orders.php?success=cancel");
    exit;
}

// ==== AMBIL DATA USERS YANG MEMILIKI PESANAN ====
$users = $pdo->query("
    SELECT DISTINCT u.id, u.username 
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    ORDER BY u.username ASC
")->fetchAll(PDO::FETCH_ASSOC);

$selectedUser = isset($_GET['user_id']) ? (int)$_GET['user_id'] : ($users[0]['id'] ?? 0);

// ==== PAGINATION ====
$perPage = 2;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Hitung total pesanan user terpilih
$totalOrdersStmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ?");
$totalOrdersStmt->execute([$selectedUser]);
$totalOrders = $totalOrdersStmt->fetchColumn();
$totalPages = ceil($totalOrders / $perPage);

// Ambil pesanan user terpilih
$ordersStmt = $pdo->prepare("
    SELECT o.*, b.title, u.username 
    FROM orders o 
    JOIN books b ON o.book_id = b.id
    JOIN users u ON o.user_id = u.id
    WHERE o.user_id = ?
    ORDER BY o.ordered_at DESC
    LIMIT $perPage OFFSET $offset
");
$ordersStmt->execute([$selectedUser]);
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="../assets/logo.jpg" type="image/jpeg">
<title>TokoBook - Orders</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .navbar-brand { font-weight: bold; }
    .sidebar {
        min-height: 100vh;
        background-color: #f8f9fa;
        border-right: 1px solid #dee2e6;
    }
    .sidebar .nav-link {
        color: #333;
        padding: 0.75rem 1rem;
    }
    .sidebar .nav-link:hover {
        background-color: #e9ecef;
    }
    .sidebar .nav-link.active {
        background-color: #007bff;
        color: white !important;
    }
    .content {
        padding: 2rem;
        width: 100%;
    }
    .users-list {
        border-right: 1px solid #dee2e6;
        background-color: #fff;
        max-height: 75vh;
        overflow-y: auto;
    }
    .user-item {
        padding: 10px 15px;
        cursor: pointer;
        display: block;
        text-decoration: none;
        color: #333;
    }
    .user-item:hover {
        background-color: #f1f1f1;
    }
    .user-item.active {
        background-color: #007bff;
        color: #fff;
        font-weight: bold;
    }
    .table-responsive { margin-top: 1rem; }
    .pagination { justify-content: center; }
</style>
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/TokoBook/index.php">TokoBook</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php">Admin</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout (<?php echo htmlspecialchars($_SESSION['user']['username']); ?>)</a></li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<div class="d-flex">
    <!-- SIDEBAR -->
    <nav class="sidebar d-flex flex-column p-3">
        <h4 class="mb-3">Admin Menu</h4>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
            <li class="nav-item"><a class="nav-link" href="book_add.php">Add Book</a></li>
            <li class="nav-item"><a class="nav-link" href="categories.php">Categories</a></li>
            <li class="nav-item"><a class="nav-link" href="users.php">Users</a></li>
            <li class="nav-item"><a class="nav-link active" href="orders.php">Orders</a></li>
            <li class="nav-item"><a class="nav-link" href="report.php">Generate Report (CSV)</a></li>
            <li class="nav-item"><a class="nav-link" href="about_edit.php">Edit About</a></li>
            <li class="nav-item"><a class="nav-link" href="contacts.php">Contact Messages</a></li>
        </ul>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="content flex-grow-1">
        <div class="container-fluid">
            <h2 class="mb-4">Orders by User</h2>

            <!-- Pesan sukses -->
            <?php if (isset($_GET['success'])): ?>
                <?php if ($_GET['success'] === 'paid'): ?>
                    <div class="alert alert-success">Order marked as <b>selesai</b>.</div>
                <?php elseif ($_GET['success'] === 'cancel'): ?>
                    <div class="alert alert-danger">Order has been <b>canceled</b> and deleted.</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="row">
                <!-- Kolom kiri: daftar user -->
                <div class="col-md-3 users-list">
                    <h5 class="text-center py-2 bg-light mb-0">Users</h5>
                    <?php foreach ($users as $u): ?>
                        <a href="?user_id=<?php echo $u['id']; ?>" 
                           class="user-item <?php echo ($selectedUser == $u['id']) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($u['username']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Kolom kanan: pesanan user -->
                <div class="col-md-9">
                    <h5 class="mb-3">Orders for: 
                        <span class="text-primary">
                            <?php 
                                $currentUser = array_filter($users, fn($usr) => $usr['id'] == $selectedUser);
                                echo htmlspecialchars(array_values($currentUser)[0]['username'] ?? 'No user selected');
                            ?>
                        </span>
                    </h5>

                    <?php if (empty($orders)): ?>
                        <div class="alert alert-info mt-3">No orders found for this user.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Book</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Ordered At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($orders as $o): ?>
                                    <tr>
                                        <td><?php echo $o['id']; ?></td>
                                        <td><?php echo htmlspecialchars($o['title']); ?></td>
                                        <td><?php echo $o['quantity']; ?></td>
                                        <td>Rp <?php echo number_format($o['total_price'], 2); ?></td>
                                        <td>
                                            <?php if ($o['status'] == 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php elseif ($o['status'] == 'selesai'): ?>
                                                <span class="badge bg-success">Selesai</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo htmlspecialchars($o['status']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($o['ordered_at']); ?></td>
                                        <td>
                                            <?php if ($o['status'] == 'pending'): ?>
                                                <a href="?mark_paid=<?php echo $o['id']; ?>&user_id=<?php echo $selectedUser; ?>" 
                                                   class="btn btn-sm btn-outline-success"
                                                   onclick="return confirm('Tandai pesanan ini sebagai selesai?');">
                                                   Mark Paid
                                                </a>
                                                <form method="post" action="" style="display:inline;">
                                                    <input type="hidden" name="cancel_order" value="<?php echo $o['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('Hapus pesanan ini?');">
                                                        Cancel
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-success fw-bold">Selesai</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <nav>
                                <ul class="pagination mt-3">
                                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                        <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?user_id=<?php echo $selectedUser; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<footer class="bg-light py-3 text-center">
    <p>&copy; <?php echo date('Y'); ?> TokoBook</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
