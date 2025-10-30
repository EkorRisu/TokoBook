<?php
session_start();
require_once __DIR__ . '/db.php';

// --- Ambil file konten, judul, dan gambar ---
$about_file = __DIR__ . '/data/about.txt';
$about_title_file = __DIR__ . '/data/about_title.txt';
$about_image_file = __DIR__ . '/data/about_image.txt';

$content = file_exists($about_file) ? file_get_contents($about_file) : 'About content not found.';
$about_title = file_exists($about_title_file) ? file_get_contents($about_title_file) : 'Tentang TokoBook 📖';
$about_image = file_exists($about_image_file) ? trim(file_get_contents($about_image_file)) : 'toko.jpg';

// --- Logika pemotongan konten ---
$cut_length = 300;
$long_narration_available = strlen($content) > $cut_length;

if ($long_narration_available) {
    $short_content = substr($content, 0, $cut_length);
    $remaining_content = substr($content, $cut_length);
} else {
    $short_content = $content;
    $remaining_content = '';
}

// --- Pagination untuk rekomendasi buku ---
$items_per_page = 4;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;

$sql_count = 'SELECT COUNT(id) FROM books';
$total_items = $pdo->query($sql_count)->fetchColumn();
$total_pages = ceil($total_items / $items_per_page);
$current_page = max(1, min($current_page, $total_pages > 0 ? $total_pages : 1));
$offset = ($current_page - 1) * $items_per_page;

$sql_books = 'SELECT b.*, c.name as category_name 
              FROM books b 
              LEFT JOIN categories c ON b.category_id = c.id
              ORDER BY b.id DESC 
              LIMIT :limit OFFSET :offset';
$stmt_books = $pdo->prepare($sql_books);
$stmt_books->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
$stmt_books->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt_books->execute();
$books_on_page = $stmt_books->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="assets/logo.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="assets/logo.jpg" type="image/jpeg">
    <title>TokoBook - About Us</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand { font-weight: bold; }
        .about-content { line-height: 1.6; }
        .book-card { height: 100%; }
        .book-img { height: 250px; width: 100%; object-fit: cover; }
        /* --- Gambar gedung (persegi panjang) --- */
        .about-img {
            width: 100%;
            height: 350px;
            object-fit: cover;
            object-position: center;
            
            
        }
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
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
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
    <div class="row">
        <div class="col-md-10 mx-auto">

            <!-- Judul dinamis -->
            <h1 class="text-center mb-5 text-primary"><?php echo htmlspecialchars($about_title); ?></h1>

            <!-- Gambar gedung dinamis -->
            <div class="text-center mb-5">
                <img src="assets/<?php echo htmlspecialchars($about_image); ?>" 
                     class="img-fluid rounded shadow-lg about-img" 
                     alt="Gedung TokoBook">
            </div>

            <h2 class="mb-3 text-secondary">Kisah Kami</h2>
            <div class="about-content card p-4 shadow-sm mb-5">
                <p class="lead">
                    <?php echo nl2br(htmlspecialchars($short_content)); ?>
                    <?php if ($long_narration_available): ?>
                        <span id="dots">...</span>
                        <span id="more-text" style="display: none;"><?php echo nl2br(htmlspecialchars($remaining_content)); ?></span>
                    <?php endif; ?>
                </p>
                <?php if ($long_narration_available): ?>
                    <button onclick="readMoreLess()" id="read-more-btn" class="btn btn-link p-0 text-start text-primary fw-bold">
                        Baca Selengkapnya
                    </button>
                <?php endif; ?>
            </div>

            <h2 class="mb-4 text-success">Rekomendasi Buku</h2>
            <?php if ($total_items > 0): ?>
                <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
                    <?php foreach ($books_on_page as $book): ?>
                        <div class="col">
                            <div class="card book-card shadow-sm">
                                <?php 
                                    $imagePath = !empty($book['image']) && file_exists(__DIR__ . '/assets/images/' . $book['image']) 
                                        ? 'assets/images/' . htmlspecialchars($book['image']) 
                                        : 'https://via.placeholder.com/300x400?text=' . urlencode(htmlspecialchars($book['title']));
                                ?>
                                <img src="<?php echo $imagePath; ?>" class="card-img-top book-img" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                                    <p class="card-text text-muted small">Oleh: <?php echo htmlspecialchars($book['author']); ?></p>
                                    <?php if (!empty($book['category_name'])): ?>
                                        <p class="card-text"><span class="badge bg-info text-dark"><?php echo htmlspecialchars($book['category_name']); ?></span></p>
                                    <?php endif; ?>
                                    <a href="book_detail.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-outline-success">Lihat Detail</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Book page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo; Sebelumnya</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo ($current_page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                    <span aria-hidden="true">Berikutnya &raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning text-center">Belum ada buku yang tersedia di database.</div>
            <?php endif; ?>

        </div>
    </div>
</main>

<footer class="bg-light py-3 text-center mt-5">
    <p>&copy; <?php echo date('Y'); ?> TokoBook</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function readMoreLess() {
    const dots = document.getElementById("dots");
    const moreText = document.getElementById("more-text");
    const btnText = document.getElementById("read-more-btn");

    if (moreText.style.display === "none") {
        dots.style.display = "none";
        moreText.style.display = "inline";
        btnText.innerHTML = "Baca Sedikit"; 
    } else {
        dots.style.display = "inline";
        moreText.style.display = "none";
        btnText.innerHTML = "Baca Selengkapnya"; 
    }
}
</script>
</body>
</html>
