<?php
session_start();
require_once __DIR__ . '/db.php';
if (!isset($_SESSION['user'])){ 
    header('Location: login.php'); 
    exit; 
}
$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) { 
    $message = 'Cart empty';
    $alert_type = 'alert-info';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $address = trim($_POST['address']);
    $payment = $_POST['payment'];
    $user_id = $_SESSION['user']['id'];

    $ids = implode(',', array_map('intval', array_keys($cart)));
    $stmt = $pdo->query("SELECT * FROM books WHERE id IN ($ids)");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = 0;
    foreach($rows as $r){
        $qty = $cart[$r['id']];
        $total += $r['price'] * $qty;
    }
    // create orders for each book (simple)
    // Begin transaction: check stock (if available) and create orders while decrementing stock
    $pdo->beginTransaction();
    try{
        // Check stock availability for each book if stock column exists
        $stockStmt = $pdo->prepare('SELECT id, stock FROM books WHERE id = :id');
        foreach ($rows as $r) {
            $qty = $cart[$r['id']];
            // If 'stock' column exists in the result set, validate
            $s = $pdo->prepare('SHOW COLUMNS FROM books LIKE "stock"');
            $s->execute();
            $col = $s->fetch(PDO::FETCH_ASSOC);
            if ($col) {
                $stockStmt->execute([':id' => $r['id']]);
                $bk = $stockStmt->fetch(PDO::FETCH_ASSOC);
                if ($bk && isset($bk['stock']) && intval($bk['stock']) < $qty) {
                    throw new Exception('Insufficient stock for "' . $r['title'] . '". Available: ' . intval($bk['stock']) . ', requested: ' . $qty);
                }
            }
        }

        $ins = $pdo->prepare('INSERT INTO orders (user_id,book_id,quantity,total_price,payment_method,shipping_address,status) VALUES (:uid,:bid,:q,:tp,:pm,:sa,:st)');
        $updateStock = $pdo->prepare('UPDATE books SET stock = stock - :qty WHERE id = :id');
        $insertedIds = [];
        foreach($rows as $r){
            $qty = $cart[$r['id']];
            $ins->execute([':uid'=>$user_id,':bid'=>$r['id'],':q'=>$qty,':tp'=>$r['price']*$qty,':pm'=>$payment,':sa'=>$address,':st'=>'pending']);
            $insertedIds[] = $pdo->lastInsertId();

            // decrement stock if column exists
            $s = $pdo->prepare('SHOW COLUMNS FROM books LIKE "stock"');
            $s->execute();
            if ($s->fetch()) {
                $updateStock->execute([':qty' => $qty, ':id' => $r['id']]);
            }
        }

        $pdo->commit();
        unset($_SESSION['cart']);

        // prepare invoice content
        $invoiceDir = __DIR__ . '/data/invoices';
        if (!is_dir($invoiceDir)) mkdir($invoiceDir, 0755, true);
        $invoiceId = time() . '_' . $user_id;
        $invoiceHtml = '<!doctype html><html><head><meta charset="utf-8"><title>Invoice ' . $invoiceId . '</title><style>body{font-family:Arial,Helvetica,sans-serif}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ccc;padding:8px}</style></head><body>';
        $invoiceHtml .= '<h2>Invoice #' . $invoiceId . '</h2>';
        $invoiceHtml .= '<p>User: ' . htmlspecialchars($_SESSION['user']['username']) . ' (' . htmlspecialchars($_SESSION['user']['email'] ?? '') . ')</p>';
        $invoiceHtml .= '<p>Shipping address: ' . nl2br(htmlspecialchars($address)) . '</p>';
        $invoiceHtml .= '<p>Payment method: ' . htmlspecialchars($payment) . '</p>';
        $invoiceHtml .= '<table><thead><tr><th>Book</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead><tbody>';
        foreach($rows as $r){
            $qty = $cart[$r['id']];
            $subtotal = $r['price'] * $qty;
            $invoiceHtml .= '<tr><td>' . htmlspecialchars($r['title']) . '</td><td>' . $qty . '</td><td>Rp ' . number_format($r['price'],2) . '</td><td>Rp ' . number_format($subtotal,2) . '</td></tr>';
        }
        $invoiceHtml .= '</tbody></table>';
        $invoiceHtml .= '<p>Total: <strong>Rp ' . number_format($total,2) . '</strong></p>';
        $invoiceHtml .= '<p>Order IDs: ' . implode(', ', $insertedIds) . '</p>';
        $invoiceHtml .= '<p>Generated at: ' . date('Y-m-d H:i:s') . '</p>';
        $invoiceHtml .= '</body></html>';

        $invoiceHtmlPath = $invoiceDir . '/invoice_' . $invoiceId . '.html';
        file_put_contents($invoiceHtmlPath, $invoiceHtml);

        $pdfPath = $invoiceDir . '/invoice_' . $invoiceId . '.pdf';
        $pdfCreated = false;
        // try to use Dompdf if available
        if (file_exists(__DIR__ . '/vendor/autoload.php')){
            require_once __DIR__ . '/vendor/autoload.php';
            $domClass = '\\Dompdf\\Dompdf';
            if (class_exists($domClass)){
                $dompdf = new $domClass();
                $dompdf->loadHtml($invoiceHtml);
                $dompdf->setPaper('A4','portrait');
                $dompdf->render();
                file_put_contents($pdfPath, $dompdf->output());
                $pdfCreated = true;
            }
        }

        // prepare user message with download buttons
        $btnHtml = '<div class="d-flex gap-2 justify-content-center mt-3">';
        $btnHtml .= '<a class="btn btn-outline-primary btn-sm" href="data/invoices/invoice_' . $invoiceId . '.html" target="_blank">Download Invoice (HTML)</a>';
        if ($pdfCreated) {
            $btnHtml .= '<a class="btn btn-primary btn-sm" href="data/invoices/invoice_' . $invoiceId . '.pdf" target="_blank">Download Invoice (PDF)</a>';
        } else {
            $btnHtml .= '<button class="btn btn-secondary btn-sm" disabled>PDF not generated</button>';
        }
        $btnHtml .= '</div>';

        $message = 'Order placed successfully.';
        $message_html = '<div class="text-center">' . htmlspecialchars($message) . $btnHtml . '</div>';
        $alert_type = 'alert-success';
    } catch (Exception $e){ 
        $pdo->rollBack(); 
        $message = 'Error: ' . htmlspecialchars($e->getMessage());
        $alert_type = 'alert-danger';
    }
} else {
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="assets/logo.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="assets/logo.jpg" type="image/jpeg">
    <title>TokoBook - Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand { font-weight: bold; }
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
                    <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
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
        <div class="col-md-8 col-lg-6 mx-auto">
            <h2 class="mb-4 text-center">Checkout</h2>
            <div class="card shadow-sm p-4">
                <div class="alert <?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message_html ?? htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary">Back to Shop</a>
                </div>
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