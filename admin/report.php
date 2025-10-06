<?php
session_start(); require_once __DIR__.'/../db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { header('Location: ../login.php'); exit; }
$rows = $pdo->query('SELECT o.id,o.ordered_at,u.username,b.title,o.quantity,o.total_price,o.status FROM orders o JOIN users u ON o.user_id=u.id JOIN books b ON o.book_id=b.id ORDER BY o.ordered_at DESC')->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="orders_report.csv"');
$out = fopen('php://output','w');
fputcsv($out, array_keys($rows[0]??[]));
foreach($rows as $r) fputcsv($out, $r);
fclose($out);
exit;
