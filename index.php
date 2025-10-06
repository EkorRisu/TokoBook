<?php
session_start();
require_once __DIR__ . '/db.php';

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category_id']) && $_GET['category_id']!=='' ? intval($_GET['category_id']) : null;

// fetch categories for filter
$categories = $pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);

// build query with optional filters
$where = [];
$params = [];
if ($q) { $where[] = 'title LIKE :q'; $params[':q'] = '%'.$q.'%'; }
if ($category_id) { $where[] = 'category_id = :cid'; $params[':cid'] = $category_id; }
$sql = 'SELECT * FROM books' . (count($where)>0 ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon: use the project logo as tab icon -->
    <link rel="icon" href="assets/logo.jpg" type="image/jpeg">
    <link rel="shortcut icon" href="assets/logo.jpg" type="image/jpeg">
    <title>TokoBook - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .navbar-brand { font-weight: bold; }
        .book-card { 
            transition: transform 0.2s; 
            border: none; 
            border-radius: 10px; 
            overflow: hidden;
        }
        .book-card:hover { transform: scale(1.02); }
        .book-image { 
            height: 300px; 
            width: 100%; 
            object-fit: cover; 
            aspect-ratio: 2/3; /* Standard book cover ratio */
            background-color: #f8f9fa;
        }
        .card-body { 
            padding: 1.5rem; 
            display: flex; 
            flex-direction: column; 
        }
        .card-title { 
            font-size: 1.25rem; 
            margin-bottom: 1rem; 
        }
        .card-text { 
            margin-bottom: 0.75rem; 
        }
        .btn-group { 
            margin-top: auto; 
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
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item"><a class="nav-link" href="cart.php">Cart</a></li>
                        <li class="nav-item"><a class="nav-link" href="my_orders.php">Pesanan Saya</a></li>
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
    <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            <form method="get" class="input-group">
                <input type="text" name="q" class="form-control" placeholder="Search title..." value="<?php echo htmlspecialchars($q); ?>">
                <select name="category_id" class="form-select" style="max-width:220px">
                    <option value="">All categories</option>
                    <?php foreach($categories as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($category_id==$c['id'])?'selected':''; ?>><?php echo htmlspecialchars($c['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>

    <section class="books">
        <?php if (count($books)===0): ?>
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
                                <?php if (!empty($b['category_id'])):
                                    $cat = $pdo->prepare('SELECT name FROM categories WHERE id=:id'); $cat->execute([':id'=>$b['category_id']]); $catname = $cat->fetchColumn();
                                ?>
                                    <p class="card-text"><small class="text-muted"><?php echo htmlspecialchars($catname); ?></small></p>
                                <?php endif; ?>
                                <p class="card-text">Price: Rp <?php echo number_format($b['price'],2); ?></p>
                                <div class="btn-group d-flex gap-2">
                                    <a href="book_detail.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-primary btn-sm">Details</a>
                                    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role']==='admin'): ?>
                                        <a href="admin/book_edit.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
                                        <a href="admin/book_delete.php?id=<?php echo $b['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete?')">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role']==='admin'): ?>
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