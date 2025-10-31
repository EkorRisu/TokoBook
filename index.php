<?php
session_start();
require_once __DIR__ . '/db.php';

// --- DARK MODE ---
if (isset($_GET['toggle_theme']) && $_GET['toggle_theme'] === '1') {
    if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') {
        setcookie('theme', 'light', time() + (86400 * 30), '/');
    } else {
        setcookie('theme', 'dark', time() + (86400 * 30), '/');
    }
    header("Location: " . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
$theme = $_COOKIE['theme'] ?? 'light';

// --- Input pencarian & filter ---
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? intval($_GET['category_id']) : null;
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? floatval($_GET['min_price']) : null;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? floatval($_GET['max_price']) : null;

// --- Pagination ---
$perPage = 6;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $perPage;

// --- Ambil kategori ---
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

// --- Filter dinamis ---
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

// --- Hitung total ---
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
<title>TokoBook - Home</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* ======== Tema umum ======== */
body { transition: background-color 0.3s, color 0.3s; }
.navbar-brand { font-weight: bold; }
.book-card { transition: transform 0.2s; border: none; border-radius: 10px; overflow: hidden; }
.book-card:hover { transform: scale(1.02); }
.book-image { height: 300px; width: 100%; object-fit: cover; background-color: #f8f9fa; }
.card-body { padding: 1.5rem; display: flex; flex-direction: column; }
.card-title { font-size: 1.25rem; margin-bottom: 1rem; }
.pagination { justify-content: center; margin-top: 30px; }

/* ======== DARK MODE ======== */
body.dark-mode { background-color: #121212; color: #f5f5f5; }
body.dark-mode .card { background-color: #1e1e1e; color: #f5f5f5; }
body.dark-mode .navbar, body.dark-mode footer { background-color: #1f1f1f !important; color: #f5f5f5; }
body.dark-mode .btn-outline-primary { color: #f5f5f5; border-color: #f5f5f5; }
body.dark-mode .btn-outline-primary:hover { background-color: #f5f5f5; color: #1f1f1f; }
.theme-btn { border: none; background: transparent; color: white; font-size: 1.3rem; cursor: pointer; margin-left: 15px; }

/* ======== Live Search ======== */
#suggestions a { padding: 8px 12px; display: flex; align-items: center; }
#suggestions a:hover { background-color: #0d6efd; color: #fff; }
body.dark-mode #suggestions { background-color: #1e1e1e; border: 1px solid #333; }
</style>
</head>

<body class="<?= $theme === 'dark' ? 'dark-mode' : '' ?>">
<header>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">TokoBook</a>
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
                        <li class="nav-item"><a class="nav-link" href="logout.php">Logout (<?= htmlspecialchars($_SESSION['user']['username']); ?>)</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a href="?toggle_theme=1" class="theme-btn" title="Toggle dark mode">
                            <?= $theme === 'dark' ? '☀️' : '🌙' ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<main class="container my-5">
    <div class="row mb-4">
        <div class="col-md-10 mx-auto">
            <form method="get" class="row g-2 align-items-center">

                <!-- 🔍 Search -->
                <div class="col-md-3">
                    <div class="position-relative">
                        <input type="text" name="q" id="search-box" class="form-control" placeholder="Search title..." autocomplete="off"
                               value="<?= htmlspecialchars($q); ?>">
                        <div id="suggestions" class="list-group position-absolute w-100 shadow-sm"
                             style="z-index:1000; display:none;"></div>
                    </div>
                </div>

                <!-- 🔽 Filter Dropdown -->
                <div class="col-md-2 dropdown">
                    <button class="btn btn-outline-secondary w-100 dropdown-toggle" type="button" id="filterDropdown"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        Filter
                    </button>
                    <div class="dropdown-menu p-3" style="min-width:250px;">
                        <div class="mb-3">
                            <label for="category_id" class="form-label small mb-1">Kategori</label>
                            <select name="category_id" id="category_id" class="form-select form-select-sm">
                                <option value="">Semua Kategori</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id']; ?>" <?= ($category_id == $c['id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="min_price" class="form-label small mb-1">Harga Minimum</label>
                            <input type="number" step="0.01" name="min_price" id="min_price"
                                   class="form-control form-control-sm"
                                   placeholder="Min Price"
                                   value="<?= htmlspecialchars($min_price ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <label for="max_price" class="form-label small mb-1">Harga Maksimum</label>
                            <input type="number" step="0.01" name="max_price" id="max_price"
                                   class="form-control form-control-sm"
                                   placeholder="Max Price"
                                   value="<?= htmlspecialchars($max_price ?? ''); ?>">
                        </div>

                        <button type="submit" class="btn btn-primary btn-sm w-100">Terapkan Filter</button>
                    </div>
                </div>

                <!-- 🔎 Search Button -->
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                </div>

            </form>
        </div>
    </div>

    <section class="books">
        <?php if (count($books) === 0): ?>
            <div class="alert alert-info text-center">No books found.</div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($books as $b): ?>
                    <div class="col">
                        <article class="book-card card h-100 shadow-sm">
                            <?php if (!empty($b['image']) && file_exists(__DIR__ . '/assets/images/' . $b['image'])): ?>
                                <img src="assets/images/<?= htmlspecialchars($b['image']); ?>" class="book-image" alt="<?= htmlspecialchars($b['title']); ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/200x300?text=Book+Cover" class="book-image" alt="<?= htmlspecialchars($b['title']); ?>">
                            <?php endif; ?>
                            <div class="card-body">
                                <h3 class="card-title"><?= htmlspecialchars($b['title']); ?></h3>
                                <p>Author: <?= htmlspecialchars($b['author']); ?></p>
                                <p>Price: Rp <?= number_format($b['price'], 2); ?></p>
                                <div class="btn-group d-flex gap-2">
                                    <a href="book_detail.php?id=<?= $b['id']; ?>" class="btn btn-outline-primary btn-sm">Details</a>
                                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                                        <a href="admin/book_edit.php?id=<?= $b['id']; ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
                                        <a href="admin/book_delete.php?id=<?= $b['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
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
                            <li class="page-item"><a class="page-link" href="?page=<?= $page - 1; ?>">Previous</a></li>
                        <?php endif; ?>

                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <li class="page-item <?= ($p == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?= $p; ?>"><?= $p; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page + 1; ?>">Next</a></li>
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
    <p>&copy; <?= date('Y'); ?> TokoBook</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchBox = document.getElementById('search-box');
    const suggestions = document.getElementById('suggestions');
    searchBox.addEventListener('input', function () {
        const query = this.value.trim();
        if (query.length < 2) { suggestions.style.display = 'none'; return; }
        fetch('search_suggest.php?q=' + encodeURIComponent(query))
            .then(res => res.text())
            .then(html => {
                suggestions.innerHTML = html;
                suggestions.style.display = html.trim() ? 'block' : 'none';
            });
    });
    suggestions.addEventListener('click', e => {
        const item = e.target.closest('.suggestion-item');
        if (item) {
            searchBox.value = item.dataset.title;
            suggestions.style.display = 'none';
            searchBox.focus();
        }
    });
    document.addEventListener('click', e => {
        if (!suggestions.contains(e.target) && e.target !== searchBox) suggestions.style.display = 'none';
    });
});
</script>
</body>
</html>
