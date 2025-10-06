<?php
session_start(); require_once __DIR__ . '/../db.php'; require_once __DIR__ . '/../csrf.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { header('Location: ../login.php'); exit; }
// validate CSRF token
if (empty($_POST['csrf_token']) || !csrf_validate($_POST['csrf_token'])) { $_SESSION['flash'] = 'Invalid CSRF token.'; header('Location: orders.php'); exit; }
$order_id = isset($_POST['order_id'])?intval($_POST['order_id']):0;
if (!$order_id) { header('Location: orders.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
$stmt->execute([':id' => $order_id]);
$ord = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ord) { $_SESSION['flash'] = 'Order not found.'; header('Location: orders.php'); exit; }

$pdo->beginTransaction();
try{
    // restore stock if column exists
    $s = $pdo->prepare('SHOW COLUMNS FROM books LIKE "stock"');
    $s->execute();
    if ($s->fetch()){
        $up = $pdo->prepare('UPDATE books SET stock = stock + :q WHERE id = :id');
        $up->execute([':q' => $ord['quantity'], ':id' => $ord['book_id']]);
    }

    // delete the order
    $d = $pdo->prepare('DELETE FROM orders WHERE id = :id');
    $d->execute([':id' => $order_id]);

    $pdo->commit();
    $_SESSION['flash'] = 'Order cancelled and removed.';
} catch (Exception $e){
    $pdo->rollBack();
    $_SESSION['flash'] = 'Error cancelling order: ' . $e->getMessage();
}

header('Location: orders.php'); exit;

?>
