<?php
session_start();
require_once __DIR__.'/../db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

// Hitung pesan yang belum dibaca
$stmt = $pdo->query("SELECT COUNT(*) as unread FROM contacts WHERE is_read = 0");
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode(['unread' => (int)$result['unread']]);
