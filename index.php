<?php
session_start();
require_once __DIR__ . '/db.php';

// --- Ambil input pencarian ---
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? intval($_GET['category_id']) : null;
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? floatval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : null;

// --- Pagination setup ---
$perPage = 6;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// --- Ambil kategori untuk dropdown ---
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

// --- Bangun WHERE clause dinamis ---
$where = [];
$params = [];

if ($q) {
    $where[] = 'title LIKE :q';
    $params[':q'] = '%' . $q . '%';
}
if ($category_id) {
    $where[] = 'category_id = :cid';
    $params[':cid'] = $category_id;
}
if ($min_price !== null) {
    $where[] = 'price >= :minp';
    $params[':minp'] = $min_price;
}
if ($max_price !== null) {
    $where[] = 'price <= :maxp';
    $params[':maxp'] = $max_price;
}

$whereSql = count($where) ? ' WHERE ' . implode(' AND ', $where) : '';

// --- Hitung total buku ---
$countSql = 'SELECT COUNT(*) FROM books' . $whereSql;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalBooks = $countStmt->fetchColumn();
$totalPages = ceil($totalBooks / $perPage);

// --- Ambil data buku ---
$sql = 'SELECT * FROM books' . $whereSql . ' ORDER BY id DESC LIMIT :limit OFFSET :offset';
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="icon" href="assets/logo.jpg" type="image/jpeg">
<link rel="shortcut icon" href="assets/logo.jpg" type="image/jpeg">
<title>TokoBook - Home</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .navbar-brand { font-weight: bold; }
    .book-card { transition: transform 0.2s; border: none; border-radius: 10px; overflow: hidden; }
    .book-card:hover { transform: scale(1.02); }
    .book-image { height: 300px; width: 100%; object-fit: cover; background-color: #f8f9fa; }
    .card-body { padding: 1.5rem; display: flex; flex-direction: column; }
    .card-title { font-size: 1.25rem; margin-bottom: 1rem; }
    .btn-group { margin-top: auto; }
    .pagination { justify-content: center; margin-top: 30px; }
</style>
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/TokoBook/index.php">TokoBook</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
                        <li class="nav-item"><a class="nav-link" href="my_orders.php">Pesanan Saya</a></li>
                        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
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
    <div class="row mb-4">
        <div class="col-md-10 mx-auto">
            <form method="get" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <input type="text" name="q" class="form-control" placeholder="Search title..." value="<?php echo htmlspecialchars($q); ?>">
                </div>
                <div class="col-md-2">
                    <select name="category_id" class="form-select">
                        <option value="">All categories</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($category_id == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.01" name="min_price" class="form-control" placeholder="Min Price" value="<?php echo htmlspecialchars($min_price ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.01" name="max_price" class="form-control" placeholder="Max Price" value="<?php echo htmlspecialchars($max_price ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>
            </form>
        </div>
    </div>

    <section class="books">
        <?php if (count($books) === 0): ?>
            <div class="alert alert-info text-center" role="alert">No books found.</div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($books as $b): ?>
                    <div class="col">
                        <article class="book-card card h-100 shadow-sm">
                            <?php if (!empty($b['image']) && file_exists(__DIR__ . '/assets/images/' . $b['image'])): ?>
                                <img src="assets/images/<?php echo htmlspecialchars($b['image']); ?>" class="book-image" alt="<?php echo htmlspecialchars($b['title']); ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/200x300?text=Book+Cover" class="book-image" alt="<?php echo htmlspecialchars($b['title']); ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h3 class="card-title"><?php echo htmlspecialchars($b['title']); ?></h3>
                                <p class="card-text">Author: <?php echo htmlspecialchars($b['author']); ?></p>
                                <p class="card-text">Price: Rp <?php echo number_format($b['price'], 2); ?></p>
                                <div class="btn-group d-flex gap-2">
                                    <a href="book_detail.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-primary btn-sm">Details</a>
                                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                                        <a href="admin/book_edit.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
                                        <a href="admin/book_delete.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination justify-content-center mt-4">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?q=<?php echo urlencode($q); ?>&category_id=<?php echo urlencode($category_id); ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&page=<?php echo $page - 1; ?>">Previous</a></li>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?php echo ($p == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?q=<?php echo urlencode($q); ?>&category_id=<?php echo urlencode($category_id); ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?q=<?php echo urlencode($q); ?>&category_id=<?php echo urlencode($category_id); ?>&min_price=<?php echo $min_price; ?>&max_price=<?php echo $max_price; ?>&page=<?php echo $page + 1; ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
        <div class="text-center mt-4">
            <a href="admin/book_add.php" class="btn btn-success">Add new book</a>
        </div>
    <?php endif; ?>
</main>

<footer class="bg-light py-3 text-center">
    <p>&copy; <?php echo date('Y'); ?> TokoBook</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
