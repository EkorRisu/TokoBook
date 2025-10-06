<?php
session_start(); require_once __DIR__.'/../db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { header('Location: ../login.php'); exit; }
$id = intval($_GET['id'] ?? 0);

// Check if any books reference this category
$c = $pdo->prepare('SELECT COUNT(*) as cnt FROM books WHERE category_id = :id');
$c->execute([':id' => $id]);
$res = $c->fetch(PDO::FETCH_ASSOC);
if ($res && intval($res['cnt']) > 0) {
	$_SESSION['flash'] = 'Cannot delete category while books still use it. Reassign or delete those books first.';
	header('Location: categories.php'); exit;
}

$stmt = $pdo->prepare('DELETE FROM categories WHERE id=:id'); $stmt->execute([':id'=>$id]);
$_SESSION['flash'] = 'Category deleted.';
header('Location: categories.php'); exit;
