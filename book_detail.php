<?php
session_start();
require_once __DIR__ . '/db.php';
$id = isset($_GET['id'])? intval($_GET['id']):0;
$stmt = $pdo->prepare('SELECT * FROM books WHERE id=:id');
$stmt->execute([':id'=>$id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$book){
    die('Book not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="assets/logo.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="assets/logo.jpg" type="image/jpeg">
    <link rel="icon" href="assets/logo.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="assets/logo.jpg" type="image/jpeg">
    <title>TokoBook - <?php echo htmlspecialchars($book['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand { font-weight: bold; }
        .book-detail { line-height: 1.6; }
        .book-card { transition: transform 0.2s; }
        .book-card:hover { transform: scale(1.01); }
    </style>
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/TokoBook/index.php">TokoBook</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
                        <?php if ($_SESSION['user']['role']==='admin'): ?>
                            <li class="nav-item"><a class="nav-link" href="admin/dashboard.php">Admin</a></li>
                        <?php endif; ?>
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['user']['username']); ?>)</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="container my-5">
    <div class="row">
        <div class="col-md-8 col-lg-6 mx-auto">
            <div class="book-card card shadow-sm">
                <?php if (!empty($book['image']) && file_exists(__DIR__ . '/assets/images/' . $book['image'])): ?>
                    <img src="assets/images/<?php echo htmlspecialchars($book['image']); ?>" class="img-fluid mb-3" alt="<?php echo htmlspecialchars($book['title']); ?>">
                <?php endif; ?>
                <div class="card-body">
                    <h2 class="card-title mb-4"><?php echo htmlspecialchars($book['title']); ?></h2>
                    <p class="card-text"><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
                    <p class="card-text"><strong>Price:</strong> Rp <?php echo number_format($book['price'],2); ?></p>
                    <?php if (!empty($book['category_id'])): $cat = $pdo->prepare('SELECT name FROM categories WHERE id=:id'); $cat->execute([':id'=>$book['category_id']]); $catname = $cat->fetchColumn(); ?>
                        <p class="card-text"><strong>Category:</strong> <?php echo htmlspecialchars($catname); ?></p>
                    <?php endif; ?>
                    <p class="card-text"><strong>Stock:</strong> <?php echo isset($book['stock'])?intval($book['stock']):0; ?></p>
                    <div class="book-detail card-text"><?php echo nl2br(htmlspecialchars($book['description'])); ?></div>
                    <?php if (isset($_SESSION['user'])): ?>
                        <?php $available = isset($book['stock'])?intval($book['stock']):0; ?>
                        <?php if ($available <= 0): ?>
                            <div class="alert alert-warning mt-3">Out of stock</div>
                        <?php else: ?>
                            <form method="post" action="cart.php" class="mt-4">
                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                <div class="row g-3 align-items-center">
                                    <div class="col-auto">
                                        <label for="qty" class="form-label">Quantity</label>
                                    </div>
                                    <div class="col-auto">
                                        <input type="number" class="form-control" id="qty" name="qty" value="1" min="1" max="<?php echo $available; ?>" style="width: 100px;">
                                    </div>
                                    <div class="col-auto">
                                        <button type="submit" class="btn btn-primary">Add to Cart</button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="mt-4"><a href="login.php" class="btn btn-outline-primary">Login to purchase</a></p>
                    <?php endif; ?>
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-outline-secondary">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<footer class="bg-light py-3 text-center">
    <p>&copy; <?php echo date('Y'); ?> TokoBook</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>