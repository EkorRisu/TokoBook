<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
// validate CSRF token
if (empty($_POST['csrf_token']) || !csrf_validate($_POST['csrf_token'])) { $_SESSION['flash'] = 'Invalid CSRF token.'; header('Location: my_orders.php'); exit; }
if (!isset($_SESSION['user'])) { header('Location: login.php'); exit; }
$order_id = isset($_POST['order_id'])?intval($_POST['order_id']):0;
if (!$order_id) { header('Location: my_orders.php'); exit; }

// Ensure the order belongs to the user and is pending
$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id AND user_id = :uid');
$stmt->execute([':id' => $order_id, ':uid' => $_SESSION['user']['id']]);
$ord = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$ord) { $_SESSION['flash'] = 'Order not found.'; header('Location: my_orders.php'); exit; }
if ($ord['status'] !== 'pending') { $_SESSION['flash'] = 'Order cannot be paid (not pending).'; header('Location: my_orders.php'); exit; }

// Mark as paid
$u = $pdo->prepare('UPDATE orders SET status = :s WHERE id = :id');
$u->execute([':s' => 'paid', ':id' => $order_id]);
$_SESSION['flash'] = 'Order marked as paid. Thank you.';
header('Location: my_orders.php'); exit;

?>
