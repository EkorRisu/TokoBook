<?php
session_start(); require_once __DIR__.'/../db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') { header('Location: ../login.php'); exit; }
$id = isset($_GET['id'])?intval($_GET['id']):0;
$stmt = $pdo->prepare('DELETE FROM contacts WHERE id=:id');
$stmt->execute([':id'=>$id]);
header('Location: contacts.php'); exit;
