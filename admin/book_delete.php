<?php
session_start(); require_once __DIR__.'/../db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { header('Location: ../login.php'); exit; }
$id = isset($_GET['id'])?intval($_GET['id']):0;

// Check if book exists and get stock and image
$stmt = $pdo->prepare('SELECT stock, image FROM books WHERE id = :id');
$stmt->execute([':id' => $id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
	$_SESSION['flash'] = 'Book not found.';
	header('Location: dashboard.php'); exit;
}

// If stock column doesn't exist in DB, allow deletion (backwards compatible)
if (array_key_exists('stock', $book) && intval($book['stock']) > 0) {
	$_SESSION['flash'] = 'Cannot delete book while stock remains. Reduce stock to zero before deleting.';
	header('Location: dashboard.php'); exit;
}

// Check for pending orders referencing this book
$ord = $pdo->prepare('SELECT COUNT(*) as cnt FROM orders WHERE book_id = :id AND status = :st');
$ord->execute([':id' => $id, ':st' => 'pending']);
$o = $ord->fetch(PDO::FETCH_ASSOC);
if ($o && intval($o['cnt']) > 0) {
	$_SESSION['flash'] = 'Cannot delete book while there are pending orders. Wait until orders are paid/cancelled.';
	header('Location: dashboard.php'); exit;
}

// Proceed to delete book record
$stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
$stmt->execute([':id' => $id]);

// Remove image file if exists
if (!empty($book['image'])) {
	$imgPath = __DIR__ . '/../assets/images/' . $book['image'];
	if (file_exists($imgPath)) {
		@unlink($imgPath);
	}
}

$_SESSION['flash'] = 'Book deleted successfully.';
header('Location: dashboard.php'); exit;
