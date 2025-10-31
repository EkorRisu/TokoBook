<?php
require_once __DIR__ . '/db.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') {
    exit; // tidak kirim apa pun kalau kosong
}

// Ambil data buku mirip (maks 5 hasil)
$stmt = $pdo->prepare("SELECT id, title FROM books WHERE title LIKE :q ORDER BY title LIMIT 5");
$stmt->execute([':q' => "%$q%"]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Kalau tidak ada hasil
if (!$books) {
    echo '<div class="list-group-item text-muted small">No results found</div>';
    exit;
}

// Kirim hasil suggestion (bisa diklik untuk mengisi input)
foreach ($books as $b) {
    echo '<div class="list-group-item list-group-item-action suggestion-item" data-title="' . htmlspecialchars($b['title']) . '">'
        . htmlspecialchars($b['title']) . '</div>';
}
