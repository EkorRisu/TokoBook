<?php
session_start();
require_once __DIR__.'/../db.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') {
    header('Location: ../login.php');
    exit;
}
$messages = [];
// Ensure categories table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE)");
    $messages[] = 'Ensured table: categories';
} catch (Exception $e) {
    $messages[] = 'categories: '.$e->getMessage();
}
// Ensure category_id column
try {
    $pdo->exec("ALTER TABLE books ADD COLUMN category_id INT DEFAULT NULL");
    $messages[] = 'Ensured column: books.category_id';
} catch (Exception $e) {
    $messages[] = 'category_id: '.$e->getMessage();
}
// Ensure stock column
try {
    $pdo->exec("ALTER TABLE books ADD COLUMN stock INT DEFAULT 0");
    $messages[] = 'Ensured column: books.stock';
} catch (Exception $e) {
    $messages[] = 'stock: '.$e->getMessage();
}
// Ensure foreign key (only add if not present)
try {
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $chk = $pdo->prepare("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'books' AND REFERENCED_TABLE_NAME = 'categories' AND REFERENCED_COLUMN_NAME = 'id'");
    $chk->execute([$dbName]);
    if ($chk->fetchColumn() == 0) {
        try {
            $pdo->exec("ALTER TABLE books ADD CONSTRAINT fk_books_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL");
            $messages[] = 'Added foreign key: fk_books_category';
        } catch (Exception $e) {
            $messages[] = 'Adding FK failed: '.$e->getMessage();
        }
    } else {
        $messages[] = 'Foreign key fk_books_category already exists';
    }
} catch (Exception $e) {
    $messages[] = 'FK check error: '.$e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../assets/logo.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="../assets/logo.jpg" type="image/jpeg">
    <title>Ensure Schema - TokoBook</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <h3>Schema Ensure Results</h3>
    <div class="card mt-3">
        <div class="card-body">
            <ul>
                <?php foreach($messages as $m): ?>
                    <li><?php echo htmlspecialchars($m); ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="mt-3">Done. You can remove this file after verifying the schema.</p>
            <p><a href="dashboard.php" class="btn btn-secondary">Back to Admin Dashboard</a></p>
        </div>
    </div>
</div>
</body>
</html>
