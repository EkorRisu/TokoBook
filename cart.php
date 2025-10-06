<?php
session_start();
require_once __DIR__ . '/db.php';
if (!isset($_SESSION['user'])){ 
    header('Location: login.php'); 
    exit; 
}
$user_id = $_SESSION['user']['id'];
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $book_id = intval($_POST['book_id']);
    $requested = max(1,intval($_POST['qty']));
    // check stock
    $sstmt = $pdo->prepare('SELECT stock FROM books WHERE id=:id');
    $sstmt->execute([':id'=>$book_id]);
    $avail = (int)$sstmt->fetchColumn();
    $qty = min($requested, max(0, $avail));
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if ($qty<=0) {
        // nothing to add
    } else {
        if (isset($_SESSION['cart'][$book_id])) $_SESSION['cart'][$book_id] += $qty; else $_SESSION['cart'][$book_id] = $qty;
        // ensure we don't exceed stock overall
        if ($_SESSION['cart'][$book_id] > $avail) $_SESSION['cart'][$book_id] = $avail;
    }
    header('Location: cart.php'); 
    exit;
}
$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;
if ($cart){
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $stmt = $pdo->query("SELECT * FROM books WHERE id IN ($ids)");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $r){
        $requestedQty = $cart[$r['id']];
        $avail = (int)$r['stock'];
        // clamp quantity to available stock
        if ($requestedQty > $avail){
            $r['qty'] = $avail;
            // update session to reflect available stock
            $_SESSION['cart'][$r['id']] = $avail;
            $r['stock_exceeded'] = true;
        } else {
            $r['qty'] = $requestedQty;
            $r['stock_exceeded'] = false;
        }
        $r['subtotal'] = $r['qty'] * $r['price'];
        $total += $r['subtotal'];
        $items[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="assets/logo.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="assets/logo.jpg" type="image/jpeg">
    <title>TokoBook - Cart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand { font-weight: bold; }
        .table-responsive { margin-top: 1rem; }
    </style>
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/TokoBook/index.php">TokoBook</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="cart.php">Cart</a></li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item"><a class="nav-link" href="my_orders.php">Pesanan Saya</a></li>
                    <?php endif; ?>
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
            <h2 class="mb-4">Your Cart</h2>
            <?php if(empty($items)): ?>
                <div class="alert alert-info text-center" role="alert">
                    Cart is empty. <a href="index.php" class="alert-link">Shop now</a>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col">Title</th>
                                        <th scope="col">Quantity</th>
                                        <th scope="col">Price</th>
                                        <th scope="col">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($items as $i): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($i['title']); ?></td>
                                            <td><?php echo $i['qty']; ?></td>
                                            <td>Rp <?php echo number_format($i['price'],2); ?></td>
                                            <td>Rp <?php echo number_format($i['subtotal'],2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <h5>Total: Rp <?php echo number_format($total,2); ?></h5>
                        </div>
                        <?php require_once __DIR__ . '/csrf.php'; ?>
                        <form method="post" action="checkout.php" class="mt-4">
                            <div class="mb-3">
                                <label for="address" class="form-label">Shipping Address</label>
                                <textarea class="form-control" id="address" name="address" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="payment" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment" name="payment">
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="COD">COD</option>
                                </select>
                            </div>
                            <?php echo csrf_input_field(); ?>
                            <button type="submit" class="btn btn-primary w-100">Place Order</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-outline-secondary">Continue Shopping</a>
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