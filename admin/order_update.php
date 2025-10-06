<?php
session_start(); require_once __DIR__.'/../db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { header('Location: ../login.php'); exit; }
$id = isset($_GET['id'])?intval($_GET['id']):0; $status = $_GET['status']??'pending';
$stmt = $pdo->prepare('UPDATE orders SET status=:s WHERE id=:id'); $stmt->execute([':s'=>$status,':id'=>$id]);
header('Location: orders.php'); exit;
